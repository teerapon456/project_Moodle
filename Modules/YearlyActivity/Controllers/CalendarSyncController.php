<?php
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
require_once __DIR__ . '/../Helpers/PermissionHelper.php';


/**
 * CalendarSyncController
 * Handles OAuth authentication and event synchronization with external calendars (Microsoft Outlook, Google Calendar).
 */
class CalendarSyncController
{
    private $db;
    private $conn;
    private $perm;

    // OAuth endpoints
    private $outlookAuthUrl;
    private $outlookTokenUrl;
    private $googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    private $googleTokenUrl = 'https://oauth2.googleapis.com/token';

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->perm = YAPermissionHelper::getInstance();

        // Fix: Use tenant specific endpoint
        $tenantId = getenv('MICROSOFT_TENANT_ID') ?: 'common';
        $this->outlookAuthUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize";
        $this->outlookTokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
    }

    // Get user's calendar sync settings
    public function getUserSync($userId = null)
    {
        if (!$this->conn) return null;

        $userId = $userId ?: $this->perm->getUserId();
        $stmt = $this->conn->prepare("SELECT * FROM ya_calendar_sync WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Check if user has active sync connection
    public function checkConnectionStatus()
    {
        $sync = $this->getUserSync();
        return $sync && !empty($sync['access_token']) && $sync['sync_enabled'];
    }

    // Generate OAuth URL for provider
    public function getAuthUrl($provider, $redirectUri)
    {
        $clientId = $this->getClientId($provider);
        $state = bin2hex(random_bytes(16));

        // Store state in session
        if (function_exists('startOptimizedSession')) \startOptimizedSession();
        else if (session_status() === PHP_SESSION_NONE) session_start();

        $_SESSION['calendar_oauth_state'] = $state;
        $_SESSION['calendar_oauth_provider'] = $provider;

        if ($provider === 'outlook') {
            $scopes = urlencode('Calendars.ReadWrite offline_access');
            return "{$this->outlookAuthUrl}?client_id={$clientId}&response_type=code&redirect_uri=" . urlencode($redirectUri) . "&scope={$scopes}&state={$state}";
        } elseif ($provider === 'google') {
            $scopes = urlencode('https://www.googleapis.com/auth/calendar');
            return "{$this->googleAuthUrl}?client_id={$clientId}&response_type=code&redirect_uri=" . urlencode($redirectUri) . "&scope={$scopes}&state={$state}&access_type=offline";
        }

        return null;
    }

    // Handle OAuth callback
    public function handleCallback($code, $state, $redirectUri)
    {
        if (function_exists('startOptimizedSession')) \startOptimizedSession();
        else if (session_status() === PHP_SESSION_NONE) session_start();

        // Verify state
        if ($state !== ($_SESSION['calendar_oauth_state'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid state'];
        }

        $provider = $_SESSION['calendar_oauth_provider'] ?? '';
        unset($_SESSION['calendar_oauth_state'], $_SESSION['calendar_oauth_provider']);

        // Exchange code for tokens
        $tokens = $this->exchangeCodeForTokens($provider, $code, $redirectUri);

        if (!$tokens) {
            return ['success' => false, 'message' => 'Failed to get tokens'];
        }

        // Save sync settings
        return $this->saveSync($provider, $tokens);
    }

    // Exchange authorization code for tokens
    private function exchangeCodeForTokens($provider, $code, $redirectUri)
    {
        $clientId = $this->getClientId($provider);
        $clientSecret = $this->getClientSecret($provider);

        $tokenUrl = $provider === 'outlook' ? $this->outlookTokenUrl : $this->googleTokenUrl;

        $postData = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]);

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    // Save sync settings
    private function saveSync($provider, $tokens)
    {
        if (!$this->conn) return ['success' => false, 'message' => 'No DB connection'];

        $userId = $this->perm->getUserId();
        $expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));

        // Upsert
        $existing = $this->getUserSync($userId);

        if ($existing) {
            $sql = "UPDATE ya_calendar_sync SET 
                provider = :provider, access_token = :access, refresh_token = :refresh, 
                token_expires_at = :expires, sync_enabled = 1
                WHERE user_id = :user_id";
        } else {
            $sql = "INSERT INTO ya_calendar_sync (user_id, provider, access_token, refresh_token, token_expires_at, sync_enabled)
                    VALUES (:user_id, :provider, :access, :refresh, :expires, 1)";
        }

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':user_id' => $userId,
            ':provider' => $provider,
            ':access' => $tokens['access_token'] ?? '',
            ':refresh' => $tokens['refresh_token'] ?? '',
            ':expires' => $expiresAt
        ]);

        return ['success' => $result];
    }

    // Disconnect calendar sync
    public function disconnect()
    {
        if (!$this->conn) return false;

        $userId = $this->perm->getUserId();
        $stmt = $this->conn->prepare("DELETE FROM ya_calendar_sync WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $userId]);
    }

    // Sync activities to external calendar
    public function syncActivities()
    {
        if (!$this->conn) return ['success' => false, 'message' => 'No DB connection'];

        $sync = $this->getUserSync();
        if (!$sync || !$sync['sync_enabled']) {
            return ['success' => false, 'message' => 'Sync not enabled'];
        }

        // Check token expiry and refresh if needed
        if (strtotime($sync['token_expires_at']) < time()) {
            $this->refreshToken($sync);
            $sync = $this->getUserSync(); // Reload
        }

        // Get activities to sync
        require_once __DIR__ . '/ActivityController.php';
        $activityController = new ActivityController();
        $activities = $activityController->getActivities([]);

        // DEBUG LOGGING
        file_put_contents(__DIR__ . '/../../sync_debug.log', date('Y-m-d H:i:s') . " Start Sync. User: {$this->perm->getUserId()}. Found " . count($activities) . " activities.\n", FILE_APPEND);

        $synced = 0;
        foreach ($activities as $act) {
            $logPrefix = date('Y-m-d H:i:s') . " [Act {$act['id']}]: ";
            if ($this->createCalendarEvent($sync, $act)) {
                $synced++;
                file_put_contents(__DIR__ . '/../../sync_debug.log', $logPrefix . "Success.\n", FILE_APPEND);
            } else {
                file_put_contents(__DIR__ . '/../../sync_debug.log', $logPrefix . "Failed.\n", FILE_APPEND);
            }
        }

        // Update last sync time
        $stmt = $this->conn->prepare("UPDATE ya_calendar_sync SET last_sync_at = NOW() WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $this->perm->getUserId()]);

        return ['success' => true, 'synced' => $synced];
    }

    // Create event in external calendar
    private function createCalendarEvent($sync, $activity)
    {
        if ($sync['provider'] === 'outlook') {
            return $this->createOutlookEvent($sync['access_token'], $activity);
        } elseif ($sync['provider'] === 'google') {
            return $this->createGoogleEvent($sync['access_token'], $activity);
        }
        return false;
    }

    // Create Outlook calendar event
    private function createOutlookEvent($accessToken, $activity)
    {
        $url = 'https://graph.microsoft.com/v1.0/me/calendar/events';

        $startDate = date('Y-m-d', strtotime($activity['start_date']));
        $endDate = $activity['end_date'] ? date('Y-m-d', strtotime($activity['end_date'])) : $startDate;

        $event = [
            'subject' => $activity['name'],
            'body' => ['contentType' => 'HTML', 'content' => $activity['description'] ?? ''],
            'start' => ['dateTime' => $startDate . 'T09:00:00', 'timeZone' => 'Asia/Bangkok'],
            'end' => ['dateTime' => $endDate . 'T17:00:00', 'timeZone' => 'Asia/Bangkok'],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            $logMsg = date('Y-m-d H:i:s') . " Sync Error [Activity {$activity['id']}]: HTTP $httpCode. Response: $response. Curl Error: $curlError" . PHP_EOL;
            file_put_contents(__DIR__ . '/../../sync_error.log', $logMsg, FILE_APPEND);
            return false;
        }

        return true;
    }

    // Create Google calendar event
    private function createGoogleEvent($accessToken, $activity)
    {
        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

        $startDate = date('Y-m-d', strtotime($activity['start_date']));
        $endDate = $activity['end_date'] ? date('Y-m-d', strtotime($activity['end_date'])) : $startDate;

        $event = [
            'summary' => $activity['name'],
            'description' => $activity['description'] ?? '',
            'start' => ['date' => $startDate],
            'end' => ['date' => $endDate],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            $logMsg = date('Y-m-d H:i:s') . " Google Sync Error [Activity {$activity['id']}]: HTTP $httpCode. Response: $response. Curl Error: $curlError" . PHP_EOL;
            file_put_contents(__DIR__ . '/../../sync_error.log', $logMsg, FILE_APPEND);
            return false;
        }

        return true;
    }



    // Refresh access token
    private function refreshToken($sync)
    {
        $tokenUrl = $sync['provider'] === 'outlook' ? $this->outlookTokenUrl : $this->googleTokenUrl;

        $postData = http_build_query([
            'client_id' => $this->getClientId($sync['provider']),
            'client_secret' => $this->getClientSecret($sync['provider']),
            'refresh_token' => $sync['refresh_token'],
            'grant_type' => 'refresh_token'
        ]);

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($ch);
        curl_close($ch);

        $tokens = json_decode($response, true);
        if (isset($tokens['access_token'])) {
            $this->saveSync($sync['provider'], $tokens);
        }
    }

    // Generate ICS file for activity
    public function generateICS($activityId)
    {
        require_once __DIR__ . '/ActivityController.php';
        $activityController = new ActivityController();
        $activity = $activityController->getActivityById($activityId);

        if (!$activity) return null;

        $uid = uniqid('ya-') . '@myhr';
        $now = gmdate('Ymd\THis\Z');
        $startDate = date('Ymd', strtotime($activity['start_date']));
        $endDate = date('Ymd', strtotime(($activity['end_date'] ?: $activity['start_date']) . ' +1 day'));

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//MyHR Portal//Yearly Activity//EN\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:{$now}\r\n";
        $ics .= "DTSTART;VALUE=DATE:{$startDate}\r\n";
        $ics .= "DTEND;VALUE=DATE:{$endDate}\r\n";
        // Fixed: title -> name
        $ics .= "SUMMARY:" . $this->escapeICS($activity['name'] ?? 'Untitled') . "\r\n";
        if (!empty($activity['description'])) {
            $ics .= "DESCRIPTION:" . $this->escapeICS($activity['description']) . "\r\n";
        }
        $status = isset($activity['status']) ? ($activity['status'] === 'completed' ? 'COMPLETED' : 'CONFIRMED') : 'CONFIRMED';
        $ics .= "STATUS:" . $status . "\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    // Download ICS file
    public function downloadICS($activityId)
    {
        $ics = $this->generateICS($activityId);
        if (!$ics) return;

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=activity_' . $activityId . '.ics');
        echo $ics;
    }

    // Export all activities to ICS
    public function exportAllToICS()
    {
        require_once __DIR__ . '/ActivityController.php';
        $activityController = new ActivityController();
        $activities = $activityController->getActivities([]);

        $now = gmdate('Ymd\THis\Z');
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//MyHR Portal//Yearly Activity//EN\r\n";
        $ics .= "X-WR-CALNAME:Yearly Activities\r\n";

        foreach ($activities as $activity) {
            $uid = 'ya-' . $activity['id'] . '@myhr';
            $startDate = date('Ymd', strtotime($activity['start_date']));
            $endDate = date('Ymd', strtotime(($activity['end_date'] ?: $activity['start_date']) . ' +1 day'));

            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:{$uid}\r\n";
            $ics .= "DTSTAMP:{$now}\r\n";
            $ics .= "DTSTART;VALUE=DATE:{$startDate}\r\n";
            $ics .= "DTEND;VALUE=DATE:{$endDate}\r\n";
            // Fixed: title -> name
            $ics .= "SUMMARY:" . $this->escapeICS($activity['name'] ?? 'Untitled') . "\r\n";
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=yearly_activities.ics');
        echo $ics;
    }

    private function escapeICS($text)
    {
        // Fixed: Ensure string to avoid deprecation warning
        $text = (string)($text ?? '');
        return str_replace(["\r\n", "\n", "\r", ",", ";"], ["\\n", "\\n", "\\n", "\\,", "\\;"], $text);
    }

    // Get client ID from config (placeholder - should be from .env or config)
    private function getClientId($provider)
    {
        // In production, load from environment variables
        // Fix: Use MICROSOFT_ prefix as per .env file
        return getenv($provider === 'outlook' ? 'MICROSOFT_CLIENT_ID' : 'GOOGLE_CLIENT_ID') ?: '';
    }

    private function getClientSecret($provider)
    {
        return getenv($provider === 'outlook' ? 'MICROSOFT_CLIENT_SECRET' : 'GOOGLE_CLIENT_SECRET') ?: '';
    }
}
