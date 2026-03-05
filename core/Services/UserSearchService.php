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
     * Unified Search - Searches both Local DB and Microsoft Graph
     * 
     * @param string $query Search query
     * @param array $options Search options (e.g. min_level, type_filter)
     * @return array Results array
     */
    public static function searchUnified($query, $options = [])
    {
        $results = [];
        $query = trim($query);
        if (empty($query)) return $results;

        $minLevel = $options['min_level'] ?? 0;
        $limit = $options['limit'] ?? 20;

        // --- PART 1: Local Database Search ---
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $sql = "SELECT id, username as code, fullname, email, Level3Name as department, PositionName as position, emplevel_id
                    FROM users 
                    WHERE is_active = 1 ";

            if ($minLevel > 0) {
                $sql .= " AND emplevel_id >= " . (int)$minLevel;
            }

            $sql .= " AND (username LIKE ? OR fullname LIKE ? OR email LIKE ?) LIMIT " . (int)$limit;

            $stmt = $conn->prepare($sql);
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['fullname'],
                    'email' => $row['email'],
                    'department' => $row['department'] ?? '',
                    'position' => $row['position'] ?? '',
                    'source' => 'local',
                    'type' => ($row['emplevel_id'] >= 7) ? 'manager' : 'user'
                ];
            }
        } catch (Exception $e) {
            error_log("UserSearchService::searchUnified Local DB Error: " . $e->getMessage());
        }

        // --- PART 2: Microsoft Graph Search ---
        if (count($results) < $limit) {
            try {
                $sessionToken = $_SESSION['access_token'] ?? null;
                $appToken = MicrosoftOAuthConfig::getAppAccessToken();

                // Try session token first, if it exists
                $accessToken = $sessionToken ?: $appToken;

                if ($accessToken) {
                    $searchQuery = urlencode("\"$query\" OR \"displayName:$query\" OR \"mail:$query\" OR \"userPrincipalName:$query\"");
                    $commonSelect = "id,displayName,mail,userPrincipalName,department";

                    // 1. Users
                    $usersUrl = "https://graph.microsoft.com/v1.0/users?\$search=$searchQuery&\$select=$commonSelect&\$top=15&\$count=true";
                    $usersResponse = self::executeGraphRequest($usersUrl, $accessToken);

                    // Fallback to app token if session token failed (e.g. 401)
                    if ($usersResponse === null && $sessionToken && $appToken) {
                        $usersResponse = self::executeGraphRequest($usersUrl, $appToken);
                    }

                    if ($usersResponse && !empty($usersResponse['value'])) {
                        foreach ($usersResponse['value'] as $gu) {
                            $email = $gu['mail'] ?? $gu['userPrincipalName'];
                            if (empty($email)) continue;

                            self::addUniqueResult($results, [
                                'id' => $gu['id'],
                                'name' => $gu['displayName'],
                                'email' => $email,
                                'department' => $gu['department'] ?? '',
                                'source' => 'microsoft',
                                'type' => 'user'
                            ]);
                        }
                    }

                    // 2. Groups (Distribution Lists)
                    $groupsUrl = "https://graph.microsoft.com/v1.0/groups?\$search=\"displayName:$query\" OR \"mail:$query\"&\$select=id,displayName,mail,description,groupTypes&\$top=10&\$count=true";
                    $groupsResponse = self::executeGraphRequest($groupsUrl, $accessToken);

                    // Fallback to app token
                    if ($groupsResponse === null && $sessionToken && $appToken) {
                        $groupsResponse = self::executeGraphRequest($groupsUrl, $appToken);
                    }

                    if ($groupsResponse && !empty($groupsResponse['value'])) {
                        foreach ($groupsResponse['value'] as $group) {
                            if (empty($group['mail'])) continue;
                            self::addUniqueResult($results, [
                                'id' => $group['id'],
                                'name' => "[Group] " . $group['displayName'],
                                'email' => $group['mail'],
                                'department' => $group['description'] ?? 'Group',
                                'source' => 'microsoft',
                                'type' => 'group'
                            ]);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("UserSearchService::searchUnified MS Graph Error: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Search Microsoft Graph API (Legacy wrapper)
     */
    public static function searchEmail($query)
    {
        return [
            'success' => true,
            'users' => self::searchUnified($query)
        ];
    }

    /**
     * Search managers (min level 7)
     */
    public static function searchManager($query)
    {
        $results = self::searchUnified($query, ['min_level' => 7]);
        return [
            'success' => true,
            'users' => $results,
            'employees' => $results
        ];
    }

    /**
     * Search all employees
     */
    public static function searchEmployee($query)
    {
        $results = self::searchUnified($query);
        return [
            'success' => true,
            'users' => $results,
            'employees' => $results
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
