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
     * Search Microsoft Graph API (Users, Groups, Contacts)
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
                // Prepare fuzzy search query and common select fields
                $searchQuery = urlencode("\"$query\" OR \"displayName:$query\" OR \"mail:$query\" OR \"userPrincipalName:$query\"");
                $commonSelect = "id,displayName,mail,userPrincipalName,department";

                // --- Part A: Search Users (Includes Individual & Shared Mailboxes) ---
                $usersUrl = "https://graph.microsoft.com/v1.0/users?\$search=$searchQuery&\$select=$commonSelect,proxyAddresses&\$top=30&\$count=true";
                $usersResponse = self::executeGraphRequest($usersUrl, $accessToken);

                if ($usersResponse && !empty($usersResponse['value'])) {
                    foreach ($usersResponse['value'] as $graphUser) {
                        $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'];
                        self::addUniqueResult($results, [
                            'id' => $graphUser['id'],
                            'name' => $graphUser['displayName'],
                            'email' => $email,
                            'department' => $graphUser['department'] ?? '',
                            'source' => 'microsoft',
                            'type' => 'user'
                        ]);
                    }
                }

                // --- Part B: Search Organizational Contacts (Crucial for external or legacy entries) ---
                $contactsUrl = "https://graph.microsoft.com/v1.0/contacts?\$search=$searchQuery&\$select=id,displayName,mail&\$top=15&\$count=true";
                $contactsResponse = self::executeGraphRequest($contactsUrl, $accessToken);

                if ($contactsResponse && !empty($contactsResponse['value'])) {
                    foreach ($contactsResponse['value'] as $contact) {
                        self::addUniqueResult($results, [
                            'id' => $contact['id'],
                            'name' => "[Contact] " . $contact['displayName'],
                            'email' => $contact['mail'] ?? '',
                            'department' => 'Contact',
                            'source' => 'microsoft',
                            'type' => 'contact'
                        ]);
                    }
                }

                // --- Part C: Search Groups (Distribution Lists, Security Groups, M365 Groups) ---
                // Crucial: Use $search on 'displayName' and 'mail' for groups
                $groupsUrl = "https://graph.microsoft.com/v1.0/groups?\$search=\"displayName:$query\" OR \"mail:$query\"&\$select=id,displayName,mail,description,groupTypes&\$top=15&\$count=true";
                $groupsResponse = self::executeGraphRequest($groupsUrl, $accessToken);

                if ($groupsResponse && !empty($groupsResponse['value'])) {
                    foreach ($groupsResponse['value'] as $group) {
                        if (empty($group['mail'])) continue;

                        $groupType = 'Group';
                        if (isset($group['groupTypes']) && in_array('Unified', $group['groupTypes'])) {
                            $groupType = 'M365 Group';
                        } elseif (empty($group['groupTypes'])) {
                            $groupType = 'Dist. List'; // Distribution list usually has empty groupTypes
                        }

                        self::addUniqueResult($results, [
                            'id' => $group['id'],
                            'name' => "[$groupType] " . $group['displayName'],
                            'email' => $group['mail'],
                            'department' => $group['description'] ?? $groupType,
                            'source' => 'microsoft',
                            'type' => 'group'
                        ]);
                    }
                }

                // --- Part D: Fallback / Exact Filter Search (Crucial for hidden/shared objects) ---
                if (count($results) < 5 || strpos($query, '@') !== false) {
                    $filter = "";
                    if (strpos($query, '@') !== false) {
                        $filter = urlencode("mail eq '$query' or userPrincipalName eq '$query' or proxyAddresses/any(a:a eq 'smtp:$query')");
                    } else {
                        $filter = urlencode("startswith(displayName,'$query') or startswith(mail,'$query')");
                    }

                    // Try User filtering
                    $filterUrl = "https://graph.microsoft.com/v1.0/users?\$filter=$filter&\$select=$commonSelect,proxyAddresses&\$top=10";
                    $filterResponse = self::executeGraphRequest($filterUrl, $accessToken);

                    if ($filterResponse && !empty($filterResponse['value'])) {
                        foreach ($filterResponse['value'] as $graphUser) {
                            $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'];
                            self::addUniqueResult($results, [
                                'id' => $graphUser['id'],
                                'name' => $graphUser['displayName'],
                                'email' => $email,
                                'department' => $graphUser['department'] ?? '',
                                'source' => 'microsoft',
                                'type' => 'user'
                            ]);
                        }
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
                SELECT id, username as code, fullname, email, Level3Name 
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
                    'department' => $row['Level3Name'], // Map Level3Name to department
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
     * Search ALL active employees (for Driver/Passenger selection)
     * No level restriction, just is_active = 1
     */
    public static function searchEmployee($query)
    {
        $results = [];

        try {
            $db = new Database();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("
                SELECT id, username as code, fullname, email, Level3Name 
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
                    'code' => $row['code'],
                    'name' => $row['fullname'],
                    'email' => $row['email'],
                    'department' => $row['Level3Name'], // Map Level3Name to department
                    'source' => 'local'
                ];
            }
        } catch (Exception $e) {
            error_log("UserSearchService::searchEmployee DB Error: " . $e->getMessage());
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
