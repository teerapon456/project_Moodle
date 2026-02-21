<?php
require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Config/SessionConfig.php';
require_once __DIR__ . '/../Security/SecureSession.php';
require_once __DIR__ . '/../Security/InputSanitizer.php';



class AuthController
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function processRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? null;

        if ($method === 'POST' && $action === 'login') {
            $this->login();
        } elseif ($method === 'POST' && $action === 'register') {
            $this->register();
        } elseif ($method === 'GET' && $action === 'me') {
            $this->me();
        } elseif ($method === 'POST' && $action === 'logout') {
            $this->logout();
        } elseif ($method === 'POST' && $action === 'forgot-password') {
            $this->forgotPassword();
        } elseif ($method === 'POST' && $action === 'reset-password') {
            $this->resetPassword();
        } elseif ($method === 'GET' && $action === 'verify-reset-token') {
            $this->verifyResetToken();
        } elseif ($method === 'GET' && $action === 'rate-limits') {
            $this->getRateLimits();
        } elseif ($method === 'POST' && $action === 'clear-rate-limit') {
            $this->clearRateLimit();
        } elseif ($method === 'POST' && $action === 'clear-all-rate-limits') {
            $this->clearAllRateLimits();
        } elseif ($method === 'GET' && $action === 'refresh') {
            $this->refreshSession();
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Invalid request"]);
        }
    }

    private function refreshSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('startOptimizedSession')) {
                startOptimizedSession();
            } else {
                session_start();
            }
        }

        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $refreshedUser = SecureSession::refreshUserData($this->conn, $_SESSION['user']['id']);
        if ($refreshedUser) {
            echo json_encode(['success' => true, 'user' => $refreshedUser]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Session expired or privileges suspended']);
        }
    }

    private function login()
    {
        if (!$this->conn) {
            http_response_code(500);
            echo json_encode(["message" => "Database connection failed"]);
            return;
        }

        // Rate limiting check
        // Get identifier from login attempt (username/email) instead of just IP
        $data = json_decode(file_get_contents("php://input"));
        $loginIdentifier = $data->username ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Sanitize identifier for storage - support both username and email
        if (filter_var($loginIdentifier, FILTER_VALIDATE_EMAIL)) {
            $identifier = InputSanitizer::sanitize($loginIdentifier, 'email');
        } else {
            $identifier = InputSanitizer::sanitize($loginIdentifier, 'alphanum');
        }

        // Fallback to IP if identifier is empty or invalid
        if (empty($identifier) || $identifier === 'unknown') {
            $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        if (!SecureSession::checkRateLimit($identifier, 5, 900)) {
            $lockoutTime = SecureSession::getLockoutTime($identifier, 5, 900);
            http_response_code(429);
            echo json_encode([
                "message" => "Too many login attempts. Try again in " . ceil($lockoutTime / 60) . " minutes."
            ]);
            return;
        }

        $portalModuleCode = 'HR_SERVICES';

        $hasPortalView = function ($roleId) use ($portalModuleCode) {
            try {
                $sql = "SELECT COALESCE(p.can_view, 0) as can_view
                        FROM core_modules cm
                        LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = :role_id
                        WHERE cm.code = :code
                        LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
                $stmt->bindValue(':code', $portalModuleCode);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? (bool)$row['can_view'] : false;
            } catch (Exception $e) {
                return false;
            }
        };

        $isGeoMandatory = function () {
            try {
                $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'mandatory_geolocation' LIMIT 1");
                $stmt->execute();
                $val = $stmt->fetchColumn();
                return $val !== '0'; // Default to mandatory if not set or set to 1
            } catch (Exception $e) {
                return true;
            }
        };

        // Sanitize input
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->username) && !empty($data->password)) {

            // Use original username for rate limiting (not sanitized)
            $username = $data->username;

            // Try to find user first to get user_id for rate limiting
            $userId = null;
            $displayName = $username; // Default to username

            // Sanitize for database query (different from rate limit)
            if (filter_var($data->username, FILTER_VALIDATE_EMAIL)) {
                $dbUsername = InputSanitizer::sanitize($data->username, 'email');
            } else {
                $dbUsername = InputSanitizer::sanitize($data->username, 'alphanum');
            }

            // Quick query to get user_id for rate limiting
            $userQuery = "SELECT id, username, email FROM users u 
                          WHERE u.username = :username1 
                             OR u.email = :username2
                          LIMIT 1";
            $userStmt = $this->conn->prepare($userQuery);
            $userStmt->bindParam(":username1", $username);
            $userStmt->bindParam(":username2", $username);
            $userStmt->execute();

            if ($userStmt->rowCount() > 0) {
                $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
                $userId = $userRow['id'];
                // Use email for display if available, otherwise username
                $displayName = !empty($userRow['email']) ? $userRow['email'] : $userRow['username'];
            }



            // Rate limiting check with user_id and display name
            if (!SecureSession::checkRateLimit($username, 5, 900, $userId, $displayName)) {
                $lockoutTime = SecureSession::getLockoutTime($username, 5, 900);
                http_response_code(429);
                echo json_encode([
                    "message" => "Too many login attempts. Try again in " . ceil($lockoutTime / 60) . " minutes."
                ]);
                return;
            }

            // Check Geolocation Requirement
            if ($isGeoMandatory()) {
                if (empty($data->latitude) || empty($data->longitude)) {
                    http_response_code(403);
                    echo json_encode([
                        "message" => "กรุณาระบุตำแหน่งที่ตั้งก่อนเข้าสู่ระบบ",
                        "code" => "location_required"
                    ]);
                    // Optional: Log this failure too?
                    $this->logActivity('login_failed', null, $username, null, null, 'Location required');
                    return;
                }
            }



            // Now do the actual authentication with password
            $query = "SELECT u.id, u.username, u.password_hash, u.role_id, u.is_active as user_is_active, r.name as role, r.is_active as role_is_active, u.email, u.default_supervisor_email, u.fullname, u.Level3Name 
                      FROM users u 
                      LEFT JOIN roles r ON u.role_id = r.id 
                      WHERE u.username = :username1 
                         OR u.email = :username2
                      ORDER BY CASE WHEN u.username = :username1 THEN 1 ELSE 2 END
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username1", $username);
            $stmt->bindParam(":username2", $username);
            $stmt->execute();



            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($data->password, $row['password_hash'])) {
                    if (isset($row['user_is_active']) && !$row['user_is_active']) {
                        http_response_code(403);
                        echo json_encode(["message" => "บัญชีผู้ใช้นี้ถูกปิดใช้งาน"]);
                        $this->logActivity('login_failed', $row['id'], $row['fullname'] ?? $row['username'], $data->latitude ?? null, $data->longitude ?? null, 'User inactive');
                        return;
                    }
                    if (isset($row['role_is_active']) && $row['role_is_active'] === '0') {
                        http_response_code(403);
                        echo json_encode(["message" => "Role นี้ถูกปิดใช้งาน"]);
                        $this->logActivity('login_failed', $row['id'], $row['fullname'] ?? $row['username'], $data->latitude ?? null, $data->longitude ?? null, 'Role inactive');
                        return;
                    }

                    if (!$hasPortalView($row['role_id'])) {
                        http_response_code(403);
                        echo json_encode(["message" => "ไม่มีสิทธิ์เข้าถึง HR Portal"]);
                        $this->logActivity('login_failed', $row['id'], $row['fullname'] ?? $row['username'], $data->latitude ?? null, $data->longitude ?? null, 'No portal permissions');
                        return;
                    }

                    // Use secure session login
                    $userData = [
                        "id" => $row['id'],
                        "username" => $row['username'],
                        "role_id" => $row['role_id'],
                        "role" => $row['role'], // Keep role name for backward compatibility
                        "role_active" => isset($row['role_is_active']) ? (int)$row['role_is_active'] : 1,
                        "user_active" => isset($row['user_is_active']) ? (int)$row['user_is_active'] : 1,
                        "email" => $row['email'],
                        "default_supervisor_email" => $row['default_supervisor_email'],
                        "fullname" => $row['fullname'],
                        "department" => $row['Level3Name']
                    ];

                    // Use regular session for now
                    if (function_exists('startOptimizedSession')) {
                        startOptimizedSession();
                    } else {
                        if (session_status() === PHP_SESSION_NONE) session_start();
                    }

                    $_SESSION['user'] = $userData;

                    $isProfileIncomplete = empty($row['fullname']) || empty($row['Level3Name']);

                    // Log login activity
                    $this->logActivity('login', $row['id'], $row['fullname'] ?? $row['username'], $data->latitude ?? null, $data->longitude ?? null);

                    // Handle Remember Me
                    if (!empty($data->{'remember-me'})) {
                        $this->createRememberToken($row['id']);
                    }

                    http_response_code(200);
                    echo json_encode([
                        "message" => "Login successful",
                        "user" => $userData,
                        "is_profile_incomplete" => $isProfileIncomplete
                    ]);
                } else {
                    // Log failed login (invalid password)
                    $this->logActivity('login_failed', $row['id'], $row['fullname'] ?? $row['username'], $data->latitude ?? null, $data->longitude ?? null, 'Invalid credentials');

                    // Unified error for invalid password
                    http_response_code(401);
                    echo json_encode(["code" => "invalid_credentials"]);
                }
            } else {
                // Log failed login (user not found)
                $this->logActivity('login_failed', null, $username, $data->latitude ?? null, $data->longitude ?? null, 'User not found');

                // Unified error for user not found
                http_response_code(401);
                echo json_encode(["code" => "invalid_credentials"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
        }
    }

    private function register()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->username) && !empty($data->password) && !empty($data->email)) {
            // Get default role id for 'user'
            $roleQuery = "SELECT id FROM roles WHERE name = 'user'";
            $roleStmt = $this->conn->prepare($roleQuery);
            $roleStmt->execute();
            $roleId = $roleStmt->fetchColumn();

            if (!$roleId) {
                // Fallback if role not found (should not happen if migration ran)
                $roleId = 1;
            }

            $query = "INSERT INTO users (username, password_hash, email, role_id) VALUES (:username, :password, :email, :role_id)";
            $stmt = $this->conn->prepare($query);

            $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

            $stmt->bindParam(":username", $data->username);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":email", $data->email);
            $stmt->bindParam(":role_id", $roleId);

            try {
                if ($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode(["message" => "User created"]);
                } else {
                    http_response_code(503);
                    echo json_encode(["message" => "Unable to create user"]);
                }
            } catch (PDOException $e) {
                http_response_code(400);
                echo json_encode(["message" => "User already exists or error: " . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
        }
    }

    private function me()
    {
        // Use regular session validation for now
        if (function_exists('startOptimizedSession')) {
            startOptimizedSession();
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
        }

        if (isset($_SESSION['user'])) {
            http_response_code(200);
            echo json_encode(["user" => $_SESSION['user']]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Not authenticated"]);
        }
    }

    private function logout()
    {
        // Use regular session destroy for now
        if (function_exists('startOptimizedSession')) {
            startOptimizedSession();
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
        }

        // Log logout activity before destroying session
        if (isset($_SESSION['user'])) {
            $this->logActivity('logout', $_SESSION['user']['id'], $_SESSION['user']['fullname'] ?? $_SESSION['user']['username']);
        }

        // Clear remember token from database and cookie
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            try {
                $stmt = $this->conn->prepare("DELETE FROM user_remember_tokens WHERE token = ?");
                $stmt->execute([$token]);
            } catch (Exception $e) {
                error_log("Failed to delete remember token on logout: " . $e->getMessage());
            }

            // Unset cookie
            $cookieParams = session_get_cookie_params();
            setcookie(
                'remember_token',
                '',
                time() - 3600,
                '/',
                $cookieParams['domain'],
                true, // Secure
                true  // HTTP Only
            );
        }

        session_destroy();
        http_response_code(200);
        echo json_encode(["message" => "Logged out"]);
    }

    /**
     * Log user activity to cb_audit_logs table
     */
    protected function logActivity($action, $userId = null, $userName = null, $latitude = null, $longitude = null, $details = null)
    {
        // User requested to only log 'login' actions, not 'logout'
        if ($action === 'logout') {
            return;
        }

        try {
            // Updated: Log to dedicated user_logins table instead of cb_audit_logs
            require_once __DIR__ . '/../Services/DeviceDetector.php';
            $detector = new \DeviceDetector($_SERVER['HTTP_USER_AGENT'] ?? '');

            $stmt = $this->conn->prepare("
                INSERT INTO user_logins 
                (user_id, user_name, action, ip_address, user_agent, 
                 device_type, device_brand, device_model, os_name, os_version, 
                 client_type, client_name, client_version, latitude, longitude, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Updated: Use passed parameters for geolocation
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
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }

    /**
     * Get client IP address
     * @todo REFACTOR: This method is duplicated in ModuleController.php. Consider consolidating into a shared Helper.
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

    /**
     * Get all rate limits (admin only)
     */
    private function getRateLimits()
    {
        // Check if user is admin
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(["message" => "Admin access required"]);
            return;
        }

        $limits = SecureSession::getAllRateLimits();
        $formattedLimits = [];

        foreach ($limits as $limit) {
            $formattedLimits[] = [
                'session_key' => $limit['session_key'],
                'identifier' => $limit['identifier'], // Now shows actual identifier
                'attempts' => $limit['attempts'],
                'first_attempt' => date('Y-m-d H:i:s', $limit['first_attempt']),
                'remaining_time' => SecureSession::getLockoutTime($limit['identifier'])
            ];
        }

        echo json_encode([
            "success" => true,
            "rate_limits" => $formattedLimits,
            "total" => count($formattedLimits)
        ]);
    }

    /**
     * Clear specific rate limit (admin only)
     */
    private function clearRateLimit()
    {
        // Check if user is admin
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(["message" => "Admin access required"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        $sessionKey = $data->session_key ?? null;

        if (!$sessionKey) {
            http_response_code(400);
            echo json_encode(["message" => "Session key is required"]);
            return;
        }

        // Clear by session key instead of identifier
        $cleared = isset($_SESSION[$sessionKey]);
        if ($cleared) {
            unset($_SESSION[$sessionKey]);
        }

        echo json_encode([
            "success" => $cleared,
            "message" => $cleared ? "Rate limit cleared successfully" : "Rate limit not found"
        ]);
    }

    /**
     * Clear all rate limits (admin only)
     */
    private function clearAllRateLimits()
    {
        // Check if user is admin
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(["message" => "Admin access required"]);
            return;
        }

        $cleared = SecureSession::clearAllRateLimits();

        echo json_encode([
            "success" => true,
            "message" => "Cleared {$cleared} rate limit entries",
            "cleared_count" => $cleared
        ]);
    }

    /**
     * Check if current user is admin
     */
    private function isAdmin()
    {
        // Check session user
        if (isset($_SESSION['user'])) {
            $user = $_SESSION['user'];
            // Check if user has admin role (role_id = 1 typically)
            return isset($user['role_id']) && $user['role_id'] == 1;
        }

        // Check database for admin role
        try {
            $stmt = $this->conn->prepare("SELECT role_id FROM users WHERE id = ? AND role_id = 1");
            $stmt->execute([$_SESSION['user']['id'] ?? 0]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Handle forgot password request - sends reset link via email
     */
    private function forgotPassword()
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents("php://input"));
        $email = trim($data->email ?? '');

        // Always return success message to prevent email enumeration
        $successResponse = function () {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "หากอีเมลนี้มีอยู่ในระบบ เราได้ส่งลิงก์สำหรับรีเซ็ตรหัสผ่านไปแล้ว"
            ]);
        };

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "กรุณากรอกอีเมลให้ถูกต้อง"]);
            return;
        }

        try {
            // Find user by email
            $stmt = $this->conn->prepare("SELECT id, email, fullname, username FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Return success anyway to prevent enumeration
                $successResponse();
                return;
            }

            // Delete old tokens for this user
            $stmt = $this->conn->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user['id']);
            $stmt->execute();

            // Generate new token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Insert new token
            $stmt = $this->conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
            $stmt->bindParam(':user_id', $user['id']);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expires_at', $expiresAt);
            $stmt->execute();

            // Build reset link
            require_once __DIR__ . '/../Helpers/UrlHelper.php';
            $baseUrl = \Core\Helpers\UrlHelper::getBaseUrl();
            // In Docker: DocumentRoot = public/, so no /public prefix needed
            // In XAMPP: DocumentRoot = htdocs, so /public prefix is needed
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            $isDocker = $docRoot && file_exists($docRoot . '/reset_password.php');
            $publicPath = $isDocker ? '' : '/public';
            $resetLink = $baseUrl . $publicPath . "/reset_password.php?token=" . $token;

            // Send email
            require_once __DIR__ . '/../Services/EmailService.php';
            $userName = $user['fullname'] ?: $user['username'] ?: 'ผู้ใช้';

            $subject = "รีเซ็ตรหัสผ่าน - MyHR Portal";
            $body = $this->buildResetEmailBody($userName, $resetLink, $expiresAt);

            EmailService::sendTestEmail($user['email'], $subject, $body);

            $successResponse();
        } catch (Exception $e) {
            error_log("forgotPassword error: " . $e->getMessage());
            // Still return success to prevent enumeration
            $successResponse();
        }
    }

    /**
     * Verify reset token is valid
     */
    private function verifyResetToken()
    {
        header('Content-Type: application/json');

        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            http_response_code(400);
            echo json_encode(["valid" => false, "message" => "Token ไม่ถูกต้อง"]);
            return;
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT pr.id, pr.user_id, pr.expires_at, pr.is_used, u.email 
                FROM password_resets pr 
                JOIN users u ON pr.user_id = u.id 
                WHERE pr.token = :token 
                LIMIT 1
            ");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                http_response_code(400);
                echo json_encode(["valid" => false, "message" => "ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้อง"]);
                return;
            }

            if ($reset['is_used']) {
                http_response_code(400);
                echo json_encode(["valid" => false, "message" => "ลิงก์นี้ถูกใช้งานแล้ว"]);
                return;
            }

            if (strtotime($reset['expires_at']) < time()) {
                http_response_code(400);
                echo json_encode(["valid" => false, "message" => "ลิงก์รีเซ็ตรหัสผ่านหมดอายุแล้ว"]);
                return;
            }

            http_response_code(200);
            echo json_encode([
                "valid" => true,
                "email" => $reset['email']
            ]);
        } catch (Exception $e) {
            error_log("verifyResetToken error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["valid" => false, "message" => "เกิดข้อผิดพลาด"]);
        }
    }

    /**
     * Reset password with token
     */
    private function resetPassword()
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents("php://input"));
        $token = trim($data->token ?? '');
        $password = $data->password ?? '';
        $confirmPassword = $data->confirm_password ?? '';

        if (empty($token)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Token ไม่ถูกต้อง"]);
            return;
        }

        if (empty($password) || strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร"]);
            return;
        }

        if ($password !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "รหัสผ่านไม่ตรงกัน"]);
            return;
        }

        try {
            // Verify token
            $stmt = $this->conn->prepare("
                SELECT pr.id, pr.user_id, pr.expires_at, pr.is_used 
                FROM password_resets pr 
                WHERE pr.token = :token 
                LIMIT 1
            ");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้อง"]);
                return;
            }

            if ($reset['is_used']) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ลิงก์นี้ถูกใช้งานแล้ว"]);
                return;
            }

            if (strtotime($reset['expires_at']) < time()) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ลิงก์รีเซ็ตรหัสผ่านหมดอายุแล้ว"]);
                return;
            }

            // Update password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->conn->prepare("UPDATE users SET password_hash = :password WHERE id = :user_id");
            $stmt->bindParam(':password', $passwordHash);
            $stmt->bindParam(':user_id', $reset['user_id']);
            $stmt->execute();

            // Mark token as used
            $stmt = $this->conn->prepare("UPDATE password_resets SET is_used = 1 WHERE id = :id");
            $stmt->bindParam(':id', $reset['id']);
            $stmt->execute();

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "เปลี่ยนรหัสผ่านสำเร็จ กรุณาเข้าสู่ระบบใหม่"
            ]);
        } catch (Exception $e) {
            error_log("resetPassword error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง"]);
        }
    }

    /**
     * Build HTML email body for password reset
     */
    private function buildResetEmailBody($userName, $resetLink, $expiresAt)
    {
        $expireTime = date('d/m/Y H:i', strtotime($expiresAt));

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Kanit', Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #A21D21, #c62828); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
                .button { display: inline-block; background: #A21D21; color: white !important; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
                .footer { background: #374151; color: #9ca3af; padding: 20px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px; }
                .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0;'>MyHR Portal</h1>
                    <p style='margin:5px 0 0;'>รีเซ็ตรหัสผ่าน</p>
                </div>
                <div class='content'>
                    <p>สวัสดี <strong>{$userName}</strong>,</p>
                    <p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ คลิกปุ่มด้านล่างเพื่อตั้งรหัสผ่านใหม่:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>รีเซ็ตรหัสผ่าน</a>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ หมายเหตุ:</strong> ลิงก์นี้จะหมดอายุใน 1 ชั่วโมง ({$expireTime})
                    </div>
                    
                    <p>หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน กรุณาเพิกเฉยอีเมลนี้ รหัสผ่านของคุณจะไม่ถูกเปลี่ยนแปลง</p>
                    
                    <p style='color: #6b7280; font-size: 12px; margin-top: 20px;'>
                        หากปุ่มไม่ทำงาน ให้คัดลอกลิงก์นี้ไปวางในเบราว์เซอร์:<br>
                        <a href='{$resetLink}' style='color: #A21D21;'>{$resetLink}</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " MyHR Portal - INTEQC Group</p>
                    <p>อีเมลนี้ส่งโดยอัตโนมัติ กรุณาอย่าตอบกลับ</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Create and store remember token
     */
    public function createRememberToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days

        try {
            // Lazy migration: Create table if not exists (Robustness)
            $this->conn->exec("CREATE TABLE IF NOT EXISTS `user_remember_tokens` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int NOT NULL,
              `token` varchar(64) NOT NULL,
              `user_agent` text NULL,
              `expires_at` datetime NOT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `token_index` (`token`),
              KEY `user_id_index` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $stmt = $this->conn->prepare("INSERT INTO user_remember_tokens (user_id, token, user_agent, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $token,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $expiry
            ]);

            // Set secure cookie
            $cookieParams = session_get_cookie_params();
            setcookie(
                'remember_token',
                $token,
                time() + (30 * 24 * 60 * 60),
                '/',
                $cookieParams['domain'],
                true, // Secure (HTTPS only)
                true  // HTTP Only
            );
        } catch (Exception $e) {
            error_log("Failed to create remember token: " . $e->getMessage());
        }
    }

    /**
     * Attempt to log in using remember token
     * Static method to be called by middleware
     */
    public static function attemptAutoLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('startOptimizedSession')) {
                startOptimizedSession();
            } else {
                session_start();
            }
        }

        if (isset($_SESSION['user'])) return $_SESSION['user'];
        if (!isset($_COOKIE['remember_token'])) return null;

        $token = $_COOKIE['remember_token'];

        try {
            // Create independent connection to avoid potential static scope issues if reused weirdly
            $db = new Database();
            $conn = $db->getConnection();
            if (!$conn) return null;

            // Clean up expired tokens periodically (1% chance)
            if (rand(1, 100) === 1) {
                try {
                    $conn->exec("DELETE FROM user_remember_tokens WHERE expires_at < NOW()");
                } catch (Exception $e) {
                }
            }

            // JOIN to get full user data
            $stmt = $conn->prepare("
                SELECT u.id, u.username, u.role_id, u.is_active as user_is_active, 
                       r.name as role, r.is_active as role_is_active, 
                       u.email, u.fullname, u.Level3Name, u.default_supervisor_email
                FROM user_remember_tokens urt
                JOIN users u ON urt.user_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE urt.token = ? AND urt.expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Check if user/role is active
                if ((isset($row['user_is_active']) && !$row['user_is_active']) ||
                    (isset($row['role_is_active']) && $row['role_is_active'] === '0')
                ) {
                    return null;
                }

                $userData = [
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "role_id" => $row['role_id'],
                    "role" => $row['role'],
                    "role_active" => isset($row['role_is_active']) ? (int)$row['role_is_active'] : 1,
                    "user_active" => isset($row['user_is_active']) ? (int)$row['user_is_active'] : 1,
                    "email" => $row['email'],
                    "default_supervisor_email" => $row['default_supervisor_email'],
                    "fullname" => $row['fullname'],
                    "department" => $row['Level3Name']
                ];

                $_SESSION['user'] = $userData;
                $_SESSION['last_activity'] = time();

                // Log auto-login (optional, might spam logs if frequent)
                // (new AuthController())->logActivity('auto-login', $row['id'], $row['fullname']);

                return $userData;
            }
        } catch (Exception $e) {
            error_log("Auto login failed: " . $e->getMessage());
        }
        return null;
    }
}
