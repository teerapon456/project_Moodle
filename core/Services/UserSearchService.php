<?php

require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Config/MicrosoftOAuthConfig.php';

/**
 * UserSearchService
 * Centralized service for searching users from local DB and Microsoft Graph
 */
class UserSearchService
{
    /**
     * Search active users from local DB ONLY
     * Used by Settings pages for admin user management
     * 
     * @param string $query Search query
     * @return array JSON response with users array
     */
    public static function searchUsers($query)
    {
        $results = [];

        // Search local database (active users only)
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("
                SELECT id, username as code, fullname, email, department 
                FROM users 
                WHERE is_active = 1 
                AND (username LIKE ? OR fullname LIKE ? OR email LIKE ?)
                LIMIT 15
            ");

            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = [
                    'id' => $row['id'],
                    'name' => $row['fullname'],
                    'email' => $row['email'],
                    'department' => $row['department'] ?? '',
                    'source' => 'local'
                ];
            }
        } catch (Exception $e) {
            error_log("UserSearchService::searchUsers DB Error: " . $e->getMessage());
        }

        return [
            'success' => true,
            'users' => $results
        ];
    }

    /**
     * Search Microsoft Graph API ONLY (Users & Groups)
     * 
     * @param string $query Search query
     * @return array JSON response with results array
     */
    public static function searchEmail($query)
    {
        $results = [];

        try {
            // Priority 1: Use delegated token from session (if user logged in via MS)
            $accessToken = $_SESSION['access_token'] ?? null;

            // Priority 2: Use application-level token (if configured and not blocked)
            if (!$accessToken) {
                $accessToken = MicrosoftOAuthConfig::getAppAccessToken();
            }

            if ($accessToken) {
                // Prepare fuzzy search query
                $searchQuery = urlencode("\"displayName:$query\" OR \"mail:$query\" OR \"userPrincipalName:$query\"");

                // --- Part A: Search Users (Includes Individual & Shared Mailboxes) ---
                $usersUrl = "https://graph.microsoft.com/v1.0/users?\$search=$searchQuery&\$select=id,displayName,mail,userPrincipalName,department&\$top=15";
                $usersResponse = self::executeGraphRequest($usersUrl, $accessToken);

                if ($usersResponse && !empty($usersResponse['value'])) {
                    foreach ($usersResponse['value'] as $graphUser) {
                        $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'];
                        self::addUniqueResult($results, [
                            'id' => $graphUser['id'],
                            'name' => $graphUser['displayName'],
                            'email' => $email,
                            'department' => $graphUser['department'] ?? '',
                            'source' => 'microsoft'
                        ]);
                    }
                }

                // --- Part B: Search Groups (Includes Distribution Lists & Security Groups) ---
                $groupsUrl = "https://graph.microsoft.com/v1.0/groups?\$search=$searchQuery&\$select=id,displayName,mail,description&\$top=10";
                $groupsResponse = self::executeGraphRequest($groupsUrl, $accessToken);

                if ($groupsResponse && !empty($groupsResponse['value'])) {
                    foreach ($groupsResponse['value'] as $group) {
                        if (empty($group['mail'])) continue;

                        self::addUniqueResult($results, [
                            'id' => $group['id'],
                            'name' => "[Group] " . $group['displayName'],
                            'email' => $group['mail'],
                            'department' => $group['description'] ?? 'Group',
                            'source' => 'microsoft'
                        ]);
                    }
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'No Microsoft access token available. Please login with Microsoft or check Azure configuration.',
                    'users' => []
                ];
            }
        } catch (Exception $e) {
            error_log("UserSearchService::searchEmail MS Graph Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'users' => []
            ];
        }

        return [
            'success' => true,
            'users' => $results
        ];
    }

    /**
     * Search only active employees for operational forms (e.g. Car Booking)
     * This ONLY searches the local database and filters by level
     */
    public static function searchManager($query)
    {
        $results = [];

        try {
            $db = new Database();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("
                SELECT id, username as code, fullname, email, department 
                FROM users 
                WHERE is_active = 1 
                AND emplevel_id >= 7
                AND (username LIKE ? OR fullname LIKE ? OR email LIKE ?)
                LIMIT 15
            ");

            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['fullname'],
                    'email' => $row['email'],
                    'department' => $row['department'],
                    'source' => 'local'
                ];
            }
        } catch (Exception $e) {
            error_log("UserSearchService::searchManager DB Error: " . $e->getMessage());
        }

        return [
            'success' => true,
            'users' => $results
        ];
    }

    /**
     * Helper to execute MS Graph Request
     */
    private static function executeGraphRequest($url, $accessToken)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'ConsistencyLevel: eventual'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200) ? json_decode($response, true) : null;
    }

    /**
     * Helper to add a result to the array if it doesn't already exist by email
     */
    private static function addUniqueResult(&$results, $newItem)
    {
        foreach ($results as $item) {
            if (strtolower($item['email'] ?? '') === strtolower($newItem['email'] ?? '')) {
                return false;
            }
        }
        $results[] = $newItem;
        return true;
    }
}
