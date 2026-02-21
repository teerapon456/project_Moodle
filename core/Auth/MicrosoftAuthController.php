<?php
require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Config/MicrosoftOAuthConfig.php';
require_once __DIR__ . '/../Config/Env.php';
require_once __DIR__ . '/../Security/SecureSession.php';

// Note: This requires composer autoload. Run: composer install
// For now, we'll implement a simple version without the library
// In production, use: require_once __DIR__ . '/../../vendor/autoload.php';

class MicrosoftAuthController
{
    private $conn;
    private $db;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function processRequest()
    {
        require_once __DIR__ . '/../Config/SessionConfig.php';
        if (function_exists('startOptimizedSession')) {
            startOptimizedSession();
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        if ($method === 'GET') {
            switch ($action) {
                case 'login':
                    $this->initiateLogin();
                    break;
                case 'callback':
                    $this->handleCallback();
                    break;
                case 'search':
                    $query = $_GET['q'] ?? '';
                    $this->searchUsers($query);
                    break;
                case 'import':
                    $this->importUser();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['message' => 'Invalid action']);
            }
        } elseif ($method === 'POST') {
            switch ($action) {
                case 'link':
                    $this->linkAccount();
                    break;
                case 'unlink':
                    $this->unlinkAccount();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['message' => 'Invalid action']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['message' => 'Method not allowed']);
        }
    }

    /**
     * Step 1: Redirect user to Microsoft login page
     */
    private function initiateLogin()
    {
        if (!MicrosoftOAuthConfig::isConfigured()) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Microsoft OAuth is not configured. Please set CLIENT_ID and CLIENT_SECRET in MicrosoftOAuthConfig.php'
            ]);
            return;
        }

        // Generate random state for CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        // Also set a short-lived cookie as a fallback in case session is lost during redirect
        try {
            $secure = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false));
            setcookie('oauth_state', $state, [
                'expires' => time() + 600, // 10 minutes
                'path' => '/',
                'secure' => $secure,
                'httponly' => false,
                'samesite' => 'None'
            ]);
        } catch (Exception $e) {
            $this->logOauthDebug('initiateLogin: setcookie failed: ' . $e->getMessage());
        }

        // Store remember me preference
        try {
            if (isset($_GET['remember']) && $_GET['remember'] == '1') {
                $secure = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false));
                setcookie('oauth_remember', '1', [
                    'expires' => time() + 600, // 10 minutes
                    'path' => '/',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'None'
                ]);
            }
        } catch (Exception $e) {
            $this->logOauthDebug('initiateLogin: setcookie remember failed: ' . $e->getMessage());
        }

        // Store geolocation if provided
        try {
            if (isset($_GET['lat']) && isset($_GET['lon'])) {
                $secure = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false));
                setcookie('oauth_lat', $_GET['lat'], [
                    'expires' => time() + 600,
                    'path' => '/',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'None'
                ]);
                setcookie('oauth_lon', $_GET['lon'], [
                    'expires' => time() + 600,
                    'path' => '/',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'None'
                ]);
            }
        } catch (Exception $e) {
            $this->logOauthDebug('initiateLogin: setcookie geo failed: ' . $e->getMessage());
        }

        // Build authorization URL
        $params = [
            'client_id' => MicrosoftOAuthConfig::CLIENT_ID(),
            'response_type' => 'code',
            'redirect_uri' => MicrosoftOAuthConfig::REDIRECT_URI(),
            'response_mode' => 'query',
            'scope' => implode(' ', MicrosoftOAuthConfig::SCOPES),
            'state' => $state
        ];

        $this->logOauthDebug('initiateLogin: state=' . $state . ', redirect_uri=' . MicrosoftOAuthConfig::REDIRECT_URI());

        // DEBUG: Log remember parameter
        // $rememberParam = $_GET['remember'] ?? 'null';
        // $this->logOauthDebug('initiateLogin: remember param = ' . $rememberParam);

        $authUrl = MicrosoftOAuthConfig::getAuthorizationUrl() . '?' . http_build_query($params);

        // Redirect to Microsoft login
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Step 2: Handle callback from Microsoft with authorization code
     */
    private function handleCallback()
    {
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        // Check for errors
        if ($error) {
            $errorDescription = $_GET['error_description'] ?? 'Unknown error';
            $this->redirectToFrontend('error', urlencode($errorDescription));
            return;
        }

        // Validate state (CSRF protection)
        $sessionState = $_SESSION['oauth_state'] ?? null;
        $cookieState = $_COOKIE['oauth_state'] ?? null;

        if ($state && $sessionState && $state === $sessionState) {
            // ok
        } elseif ($state && $cookieState && $state === $cookieState) {
            // Session state missing but cookie matches - restore session value
            $_SESSION['oauth_state'] = $cookieState;
            $this->logOauthDebug('handleCallback: restored oauth_state from cookie');
        } else {
            $this->logOauthDebug('handleCallback: state mismatch', [
                'received_state' => $state,
                'session_state' => $sessionState,
                'cookie_state' => $cookieState
            ]);
            $this->redirectToFrontend('error', 'Invalid state parameter');
            return;
        }

        if (!$code) {
            $this->redirectToFrontend('error', 'No authorization code received');
            return;
        }

        // Exchange code for access token
        $tokenData = $this->exchangeCodeForToken($code);
        if (!$tokenData) {
            $this->redirectToFrontend('error', 'Failed to get access token');
            return;
        }

        // Get user info from Microsoft Graph API
        $userInfo = $this->getUserInfo($tokenData['access_token']);
        if (!$userInfo) {
            $this->redirectToFrontend('error', 'Failed to get user information');
            return;
        }

        // Rate limiting check for Microsoft login
        // Try to find user first to get user_id for consistent rate limiting
        $msIdentifier = $userInfo['userPrincipalName'] ?? $userInfo['mail'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userId = null;
        $displayName = $msIdentifier;

        // Try to find existing user by Microsoft ID or email
        $userQuery = "SELECT u.id, u.username, u.email, u.Level3Name FROM users u 
                      WHERE u.microsoft_id = :msId 
                         OR u.email = :email
                      LIMIT 1";
        $userStmt = $this->conn->prepare($userQuery);
        $msId = $userInfo['id'];
        $email = $userInfo['mail'] ?? $userInfo['userPrincipalName'];
        $userStmt->bindParam(":msId", $msId);
        $userStmt->bindParam(":email", $email);
        $userStmt->execute();

        if ($userStmt->rowCount() > 0) {
            $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
            $userId = $userRow['id'];
            // Use email for display if available, otherwise username
            $displayName = !empty($userRow['email']) ? $userRow['email'] : $userRow['username'];
        }

        // Debug logging
        error_log("Microsoft Auth Debug - User: {$msIdentifier}, User ID: {$userId}, Display: {$displayName}");

        // Use the same rate limit logic as local login
        if (!SecureSession::checkRateLimit($msIdentifier, 5, 900, $userId, $displayName)) {
            $lockoutTime = SecureSession::getLockoutTime($msIdentifier, 5, 900);
            $this->logActivity('login_failed', $userId, $displayName, null, null, 'Rate limit exceeded');
            $this->redirectToFrontend('error', 'Too many login attempts. Try again in ' . ceil($lockoutTime / 60) . ' minutes.');
            return;
        }

        // Check Geolocation Requirement
        try {
            $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'mandatory_geolocation' LIMIT 1");
            $stmt->execute();
            $geoVal = $stmt->fetchColumn();
            $isGeoMandatory = ($geoVal !== '0'); // Default to mandatory if not set or 1

            if ($isGeoMandatory) {
                $lat = $_COOKIE['oauth_lat'] ?? null;
                $lon = $_COOKIE['oauth_lon'] ?? null;
                if (empty($lat) || empty($lon)) {
                    $this->logActivity('login_failed', $userId, $displayName, null, null, 'Location required');
                    $this->redirectToFrontend('error', urlencode('กรุณาระบุตำแหน่งที่ตั้งก่อนเข้าสู่ระบบ (Location required)'));
                    return;
                }
            }
        } catch (Exception $e) {
            // fallback to allowing login if setting check fails, or could be stricter
        }

        // Find or create user in database
        $user = $this->findOrCreateUser($userInfo);
        if (!$user) {
            $this->redirectToFrontend('error', 'Failed to create user account');
            return;
        }

        if ((isset($user['is_active']) && !$user['is_active']) || (isset($user['role_active']) && !$user['role_active'])) {
            if (session_status() === PHP_SESSION_NONE) {
                // Should already be started, but just in case
                if (function_exists('startOptimizedSession')) {
                    startOptimizedSession();
                } else {
                    session_start();
                }
            }
            session_destroy();
            $this->logActivity('login_failed', $user['id'], $user['fullname'] ?? $user['username'], null, null, 'User/Role inactive');
            $this->redirectToFrontend('error', 'role_inactive');
            return;
        }

        if (!$this->hasPortalView($user['role_id'])) {
            if (session_status() === PHP_SESSION_NONE) {
                if (function_exists('startOptimizedSession')) {
                    startOptimizedSession();
                } else {
                    session_start();
                }
            }
            session_destroy();
            $this->logActivity('login_failed', $user['id'], $user['fullname'] ?? $user['username'], null, null, 'No portal permissions');
            $this->redirectToFrontend('error', 'no_permission');
            return;
        }

        // Store user and access token in session
        if ($user) {
            $user['department'] = $user['Level3Name'] ?? $user['department'] ?? null;
        }
        $_SESSION['user'] = $user;
        $_SESSION['access_token'] = $tokenData['access_token'];

        // Log Microsoft login activity
        $lat = $_COOKIE['oauth_lat'] ?? null;
        $lon = $_COOKIE['oauth_lon'] ?? null;
        $this->logActivity('login', $user['id'], $user['fullname'] ?? $user['username'], $lat, $lon);

        // Clear oauth_state cookie (cleanup)
        try {
            $secure = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false));
            setcookie('oauth_state', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $secure,
                'httponly' => false,
                'samesite' => 'None'
            ]);
        } catch (Exception $e) {
            // ignore
        }



        // Handle Remember Me
        $rememberCookie = $_COOKIE['oauth_remember'] ?? 'null';

        if (isset($_COOKIE['oauth_remember']) && $_COOKIE['oauth_remember'] === '1') {
            try {

                require_once __DIR__ . '/AuthController.php';
                $auth = new AuthController();
                $auth->createRememberToken($user['id']);

                $this->logOauthDebug('handleCallback: createRememberToken called');

                // Clear cookie
                $secure = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false));
                setcookie('oauth_remember', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'None'
                ]);
            } catch (Exception $e) {
                $this->logOauthDebug('Failed to set remember token: ' . $e->getMessage());
            }
        }

        // Clear geolocation cookies
        try {
            $secure = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false));
            setcookie('oauth_lat', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'None'
            ]);
            setcookie('oauth_lon', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'None'
            ]);
        } catch (Exception $e) {
            // ignore
        }

        // Redirect to frontend dashboard
        $this->redirectToFrontend('success', null, $user);
    }

    /**
     * Search users (Local DB + Microsoft Graph)
     */
    public function searchUsers($query)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $results = [];

        // 1. Search Microsoft Graph API
        if (isset($_SESSION['access_token'])) {
            $accessToken = $_SESSION['access_token'];
            $commonSelect = "id,displayName,mail,userPrincipalName,department";

            // --- Strategy A: Fuzzy Search (Users) ---
            $searchQuery = urlencode("\"$query\" OR \"displayName:$query\" OR \"mail:$query\" OR \"userPrincipalName:$query\"");
            $graphUrl = "https://graph.microsoft.com/v1.0/users?\$search=$searchQuery&\$select=$commonSelect,proxyAddresses&\$top=15&\$count=true";

            $graphResponse = $this->executeGraphRequest($graphUrl, $accessToken);
            if ($graphResponse && !empty($graphResponse['value'])) {
                foreach ($graphResponse['value'] as $graphUser) {
                    $this->addUniqueGraphResult($results, $graphUser);
                }
            }

            // --- Strategy C: Fuzzy Search (Groups) ---
            $groupsUrl = "https://graph.microsoft.com/v1.0/groups?\$search=$searchQuery&\$select=id,displayName,mail,description&\$top=10&\$count=true";
            $groupsResponse = $this->executeGraphRequest($groupsUrl, $accessToken);
            if ($groupsResponse && !empty($groupsResponse['value'])) {
                foreach ($groupsResponse['value'] as $group) {
                    if (empty($group['mail'])) continue;
                    $this->addUniqueGraphResult($results, [
                        'id' => $group['id'],
                        'displayName' => "[Group] " . $group['displayName'],
                        'mail' => $group['mail'],
                        'userPrincipalName' => $group['mail'],
                        'department' => $group['description'] ?? 'Group'
                    ]);
                }
            }

            // --- Strategy C: Fuzzy Search (Contacts) ---
            $contactsUrl = "https://graph.microsoft.com/v1.0/contacts?\$search=$searchQuery&\$select=id,displayName,mail&\$top=10&\$count=true";
            $contactsResponse = $this->executeGraphRequest($contactsUrl, $accessToken);
            if ($contactsResponse && !empty($contactsResponse['value'])) {
                foreach ($contactsResponse['value'] as $contact) {
                    if (empty($contact['mail'])) continue;
                    $this->addUniqueGraphResult($results, [
                        'id' => $contact['id'],
                        'displayName' => "[Contact] " . $contact['displayName'],
                        'mail' => $contact['mail'],
                        'userPrincipalName' => $contact['mail']
                    ]);
                }
            }

            // --- Strategy D: Exact Email Fallback (Strict - crucial for Shared Mailboxes) ---
            if (strpos($query, '@') !== false) {
                $filter = urlencode("mail eq '$query' or userPrincipalName eq '$query' or proxyAddresses/any(a:a eq 'smtp:$query')");
                $filterUrl = "https://graph.microsoft.com/v1.0/users?\$filter=$filter&\$select=$commonSelect,proxyAddresses&\$top=5";

                $filterResponse = $this->executeGraphRequest($filterUrl, $accessToken);
                if ($filterResponse && !empty($filterResponse['value'])) {
                    foreach ($filterResponse['value'] as $graphUser) {
                        $this->addUniqueGraphResult($results, $graphUser);
                    }
                }
            }
        }

        echo json_encode($results);
    }
    /**
     * Helper to add Graph user to results avoiding duplicates
     */
    private function addUniqueGraphResult(&$results, $graphUser)
    {
        $email = $graphUser['mail'] ?? $graphUser['userPrincipalName'];
        if (empty($email)) return;

        foreach ($results as $item) {
            if (strtolower($item['email'] ?? '') === strtolower($email)) {
                return;
            }
        }

        $results[] = [
            'id' => $graphUser['id'],
            'displayName' => $graphUser['displayName'],
            'email' => $email,
            'fullname' => $graphUser['displayName'],
            'mail' => $email,
            'userPrincipalName' => $graphUser['userPrincipalName'] ?? '',
            'source' => 'microsoft'
        ];
    }

    /**
     * Helper for Graph API calls
     */
    private function executeGraphRequest($url, $accessToken)
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
     * Import user from Microsoft Graph
     */
    private function importUser()
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return;
        }

        $microsoftId = $_GET['id'] ?? '';
        if (!$microsoftId) {
            http_response_code(400);
            echo json_encode(['message' => 'Microsoft ID is required']);
            return;
        }

        if (!isset($_SESSION['access_token'])) {
            http_response_code(400);
            echo json_encode(['message' => 'No access token available']);
            return;
        }

        // Fetch full user details from Graph
        $accessToken = $_SESSION['access_token'];
        $graphUrl = "https://graph.microsoft.com/v1.0/users/$microsoftId?\$select=id,displayName,mail,userPrincipalName";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $graphUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $graphUser = json_decode($response, true);

            $userInfo = [
                'id' => $graphUser['id'],
                'displayName' => $graphUser['displayName'],
                'mail' => $graphUser['mail'] ?? $graphUser['userPrincipalName'],
                'userPrincipalName' => $graphUser['userPrincipalName']
            ];

            $newUser = $this->findOrCreateUser($userInfo);

            if ($newUser) {
                echo json_encode([
                    'success' => true,
                    'user' => $newUser
                ]);
                return;
            }
        }

        http_response_code(500);
        echo json_encode(['message' => 'Failed to import user']);
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken($code)
    {
        $tokenUrl = MicrosoftOAuthConfig::getTokenUrl();

        $params = [
            'client_id' => MicrosoftOAuthConfig::CLIENT_ID(),
            'client_secret' => MicrosoftOAuthConfig::CLIENT_SECRET(),
            'code' => $code,
            'redirect_uri' => MicrosoftOAuthConfig::REDIRECT_URI(),
            'grant_type' => 'authorization_code'
        ];

        $this->logOauthDebug('exchangeCodeForToken: token_url=' . $tokenUrl . ', params=' . json_encode(['redirect_uri' => $params['redirect_uri']]));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->logOauthDebug('Token exchange failed: ' . $response);
            return null;
        }

        $this->logOauthDebug('Token exchange succeeded');
        return json_decode($response, true);
    }

    /**
     * Get user information from Microsoft Graph API
     */
    private function getUserInfo($accessToken)
    {
        $this->logOauthDebug('getUserInfo: calling Graph API');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, MicrosoftOAuthConfig::GRAPH_API_ENDPOINT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->logOauthDebug('Failed to get user info: ' . $response);
            return null;
        }

        $this->logOauthDebug('getUserInfo: success');
        return json_decode($response, true);
    }

    /**
     * Find existing user by Microsoft ID or create new one
     */
    private function findOrCreateUser($userInfo)
    {
        $microsoftId = $userInfo['id'];
        $email = $userInfo['mail'] ?? $userInfo['userPrincipalName'];
        $displayName = $userInfo['displayName'] ?? '';

        // Split display name into first and last name (simple approach)
        $nameParts = explode(' ', $displayName, 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        // Check if user exists by Microsoft ID
        $query = "SELECT u.id, u.username, u.email, u.role_id, u.is_active, r.name as role, r.is_active as role_active, u.microsoft_id, u.default_supervisor_email, u.fullname, u.department, u.Level3Name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.microsoft_id = :microsoft_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':microsoft_id', $microsoftId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // User exists, return their info
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Check if email already exists (user might want to link account)
        $query = "SELECT id FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Email exists but not linked - update existing user
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            $query = "UPDATE users SET microsoft_id = :microsoft_id, microsoft_email = :microsoft_email WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':microsoft_id', $microsoftId);
            $stmt->bindParam(':microsoft_email', $email);
            $stmt->bindParam(':id', $existingUser['id']);
            $stmt->execute();

            // Fetch updated user
            $query = "SELECT u.id, u.username, u.email, u.role_id, u.is_active, r.name as role, r.is_active as role_active, u.microsoft_id, u.default_supervisor_email, u.fullname, u.department, u.Level3Name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $existingUser['id']);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Create new user
        $username = strtolower(str_replace(' ', '.', $displayName)) ?: 'user' . time();

        // Get default role id for 'user'
        $roleQuery = "SELECT id FROM roles WHERE name = 'user' LIMIT 1";
        $roleStmt = $this->conn->prepare($roleQuery);
        $roleStmt->execute();
        $role_id = $roleStmt->fetchColumn();

        if (!$role_id) {
            // Fallback: try to find ANY role to avoid constraint error, or default to 1
            $roleQuery = "SELECT id FROM roles ORDER BY id ASC LIMIT 1";
            $roleStmt = $this->conn->prepare($roleQuery);
            $roleStmt->execute();
            $role_id = $roleStmt->fetchColumn() ?: 1;
        }

        $query = "INSERT INTO users (username, email, role_id, microsoft_id, microsoft_email, password_hash) 
                  VALUES (:username, :email, :role_id, :microsoft_id, :microsoft_email, NULL)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role_id', $role_id);
        $stmt->bindParam(':microsoft_id', $microsoftId);
        $stmt->bindParam(':microsoft_email', $email);

        if ($stmt->execute()) {
            $userId = $this->conn->lastInsertId();

            // Fetch the new user
            $query = "SELECT u.id, u.username, u.email, u.role_id, u.is_active, r.name as role, r.is_active as role_active, u.microsoft_id, u.default_supervisor_email, u.fullname, u.department, u.Level3Name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return null;
    }

    private function hasPortalView($roleId)
    {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            if (!$conn) {
                return false;
            }
            $portalCode = 'HR_SERVICES';
            $sql = "SELECT COALESCE(p.can_view, 0) as can_view
                    FROM core_modules cm
                    LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = :role_id
                    WHERE cm.code = :code
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmt->bindValue(':code', $portalCode);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (bool)$row['can_view'] : false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Link existing account with Microsoft ID (for users who already have password accounts)
     */
    private function linkAccount()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $accessToken = $data->access_token ?? null;

        if (!$accessToken) {
            http_response_code(400);
            echo json_encode(['message' => 'Access token required']);
            return;
        }

        // Get Microsoft user info
        $userInfo = $this->getUserInfo($accessToken);
        if (!$userInfo) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid access token']);
            return;
        }

        $microsoftId = $userInfo['id'];
        $microsoftEmail = $userInfo['mail'] ?? $userInfo['userPrincipalName'];

        // Update user with Microsoft ID
        $query = "UPDATE users SET microsoft_id = :microsoft_id, microsoft_email = :microsoft_email WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':microsoft_id', $microsoftId);
        $stmt->bindParam(':microsoft_email', $microsoftEmail);
        $stmt->bindParam(':id', $userId);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['message' => 'Account linked successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to link account']);
        }
    }

    /**
     * Unlink Microsoft account
     */
    private function unlinkAccount()
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];

        // Clear Microsoft ID from user
        $query = "UPDATE users SET microsoft_id = NULL, microsoft_email = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['message' => 'Account unlinked successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to unlink account']);
        }
    }

    /**
     * Redirect to frontend with result
     */
    private function redirectToFrontend($status, $error = null, $user = null)
    {
        $basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
        if ($basePath === '') {
            $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            $basePath = preg_replace('#/api$#', '', $scriptDir);
        }
        if ($basePath === '') {
            $basePath = '/';
        }
        $baseRoot = rtrim($basePath, '/');
        $baseUrl = ($baseRoot ? $baseRoot : '') . '/public/';
        $moduleBase = ($baseRoot ? $baseRoot . '/' : '/') . 'Modules/HRServices/public/';

        if ($status === 'success' && $user) {
            // Store user data in localStorage via JavaScript
            $userData = json_encode($user);
            $isProfileIncomplete = empty($user['fullname']) || (empty($user['department']) && empty($user['Level3Name']));
            $isProfileIncompleteJson = json_encode($isProfileIncomplete);

            // Set Content-Type header so browser renders HTML instead of showing source
            header('Content-Type: text/html; charset=UTF-8');

            echo "
            <!DOCTYPE html>
            <html>
            <head>
                <title>Login Successful</title>
            </head>
            <body>
                <script>
                    localStorage.setItem('user', '" . addslashes($userData) . "');
                    localStorage.setItem('is_profile_incomplete', " . $isProfileIncompleteJson . ");
                    window.location.href = '{$moduleBase}index.php?login_success=1';
                </script>
            </body>
            </html>
            ";
        } else {
            $errorParam = $error ? urlencode($error) : 'no_permission';
            header("Location: {$baseUrl}index.php?error={$errorParam}");
        }
        exit;
    }

    /**
     * Append debug message to storage/logs/ms_oauth_debug.log
     */
    private function logOauthDebug($message, $context = null)
    {
        try {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/ms_oauth_debug.log';
            $entry = '[' . date('c') . '] ' . $message;
            if ($context !== null) {
                $entry .= ' ' . json_encode($context);
            }
            $entry .= "\n";
            file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("Failed to write OAuth debug log: " . $e->getMessage());
        }
    }

    /**
     * Log user activity to user_logins table
     */
    private function logActivity($action, $userId = null, $userName = null, $latitude = null, $longitude = null, $details = null)
    {
        // User requested to only log 'login' actions
        if ($action === 'logout') {
            return;
        }

        try {
            require_once __DIR__ . '/../Services/DeviceDetector.php';
            $detector = new \DeviceDetector($_SERVER['HTTP_USER_AGENT'] ?? '');

            $stmt = $this->conn->prepare("
                INSERT INTO user_logins 
                (user_id, user_name, action, ip_address, user_agent, 
                 device_type, device_brand, device_model, os_name, os_version, 
                 client_type, client_name, client_version, latitude, longitude, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Validate simple format if present
            if ($latitude !== null && !is_numeric($latitude)) $latitude = null;
            if ($longitude !== null && !is_numeric($longitude)) $longitude = null;

            $stmt->execute([
                $userId,
                $userName ?? 'Unknown',
                $action,
                $this->getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $detector->getDeviceType(),
                $detector->getDeviceBrand(),
                $detector->getDeviceModel(),
                $detector->getOSName(),
                $detector->getOSVersion(),
                'browser',
                $detector->getClientName(),
                $detector->getClientVersion(),
                $latitude,
                $longitude,
                $details,
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Failed to log Microsoft activity: " . $e->getMessage());
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIp()
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        return 'unknown';
    }
}
