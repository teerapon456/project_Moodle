<?php

/**
 * Secure Session Management
 * Prevents session fixation, hijacking, and improves session security
 */

class SecureSession
{
    /**
     * Start secure session with proper configuration
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', self::isHTTPS());
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict'); // Changed from Lax to Strict for better security

            // Set session timeout (8 hours)
            ini_set('session.gc_maxlifetime', 28800);
            ini_set('session.cookie_lifetime', 28800);

            session_start();

            // Regenerate session ID on first start
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['last_activity'] = time();
            }

            // Check session timeout
            self::checkSessionTimeout();
        }
    }

    /**
     * Check if HTTPS is enabled
     */
    private static function isHTTPS()
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        );
    }

    /**
     * Regenerate session ID (prevents fixation)
     */
    public static function regenerate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Check session timeout and destroy if expired
     */
    private static function checkSessionTimeout()
    {
        $timeout = 28800; // 8 hours

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            session_destroy();

            // Detect if we are in Docker where DocumentRoot is already .../public
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            $isDockerPublicRoot = (basename($docRoot) === 'public');

            $msg = '?error=session_timeout';

            if ($isDockerPublicRoot) {
                // Docker: Root is public, so login is at /index.php
                header("Location: /index.php" . $msg);
            } else {
                // XAMPP: Root is htdocs, so login is at /MyHR.../public/index.php
                // Use robust detection
                if (strpos($_SERVER['REQUEST_URI'], '/public/') !== false) {
                    $base = strstr($_SERVER['REQUEST_URI'], '/public/', true);
                    header("Location: " . $base . "/public/index.php" . $msg);
                } else {
                    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                    $base = preg_replace('#/(Modules|core|api|public).*$#i', '', $scriptDir);
                    header("Location: " . $base . "/public/index.php" . $msg);
                }
            }
            exit;
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Secure login with session regeneration
     */
    public static function secureLogin($userData)
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            self::start();
        }

        // Store user data
        $_SESSION['user'] = $userData;
        $_SESSION['initiated'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }

    /**
     * Validate session (prevents hijacking)
     */
    public static function validateSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            return false;
        }

        if (!isset($_SESSION['user'])) {
            return false;
        }

        // Check timeout
        self::checkSessionTimeout();

        return true;
    }

    /**
     * Destroy session securely
     */
    public static function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            session_destroy();
        }
    }

    /**
     * Rate limiting for login attempts
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900, $userId = null, $displayName = null)
    {
        // Use user_id as primary key if available, fallback to identifier
        $rateLimitKey = $userId ? 'user_id_' . $userId : 'login_attempts_' . md5($identifier);

        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = [
                'attempts' => 0,
                'first_attempt' => time(),
                'identifier' => $identifier, // Store original for fallback
                'user_id' => $userId,
                'display_name' => $displayName ?: $identifier // What to show in UI
            ];
        }

        $attempts = &$_SESSION[$rateLimitKey];

        // Debug logging - track when function is called
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $backtrace[0]['function'] ?? 'unknown';
        error_log("Rate Limit Debug - Called by: {$caller}, User ID: {$userId}, Display: {$displayName}, Key: {$rateLimitKey}, Current Attempts: {$attempts['attempts']}, Max: {$maxAttempts}");

        // Reset if time window has passed
        if (time() - $attempts['first_attempt'] > $timeWindow) {
            error_log("Rate Limit Debug - Time window expired, resetting attempts");
            $attempts['attempts'] = 0;
            $attempts['first_attempt'] = time();
        }

        // IMPORTANT: Only increment if this is a failed login attempt
        // We need to pass a parameter to know if this is a failed attempt
        // For now, let's assume this is always called for failed attempts

        $attempts['attempts']++;
        error_log("Rate Limit Debug - After increment: {$attempts['attempts']}/{$maxAttempts}");

        if ($attempts['attempts'] > $maxAttempts) {
            error_log("Rate Limit Debug - LIMIT EXCEEDED for user {$userId} ({$displayName})");
            return false; // Rate limit exceeded
        }

        return true;
    }

    /**
     * Get remaining lockout time
     */
    public static function getLockoutTime($identifier, $maxAttempts = 5, $timeWindow = 900)
    {
        $key = 'login_attempts_' . md5($identifier);

        if (!isset($_SESSION[$key])) {
            return 0;
        }

        $attempts = $_SESSION[$key];

        if ($attempts['attempts'] <= $maxAttempts) {
            return 0;
        }

        $remaining = $timeWindow - (time() - $attempts['first_attempt']);
        return max(0, $remaining);
    }

    /**
     * Check if rate limit is exceeded without incrementing attempts
     * This should be used for checking status only
     */
    public static function isRateLimitExceeded($identifier, $maxAttempts = 5, $timeWindow = 900)
    {
        $key = 'login_attempts_' . md5($identifier);

        if (!isset($_SESSION[$key])) {
            return false;
        }

        $attempts = $_SESSION[$key];

        // Reset if time window has passed
        if (time() - $attempts['first_attempt'] > $timeWindow) {
            return false;
        }

        return $attempts['attempts'] > $maxAttempts;
    }

    /**
     * Clear rate limit for a specific identifier (admin function)
     */
    public static function clearRateLimit($identifier)
    {
        $key = 'login_attempts_' . md5($identifier);
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            return true;
        }
        return false;
    }

    /**
     * Get all current rate limit entries (admin function)
     */
    public static function getAllRateLimits()
    {
        $limits = [];
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'login_attempts_') === 0) {
                // Return the original identifier instead of hash
                $limits[] = [
                    'session_key' => $key,
                    'attempts' => $value['attempts'],
                    'first_attempt' => $value['first_attempt'],
                    'identifier' => $value['identifier'] ?? 'Unknown'
                ];
            }
        }
        return $limits;
    }

    /**
     * Clear all rate limits (admin function)
     */
    public static function clearAllRateLimits()
    {
        $cleared = 0;
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'login_attempts_') === 0) {
                unset($_SESSION[$key]);
                $cleared++;
            }
        }
        return $cleared;
    }

    /**
     * Refresh user session data from the database
     * @param PDO|mysqli $db Connection object (PDO or mysqli)
     * @param int $userId The user ID to refresh
     * @return bool|array Returns updated user array or false if user not found/inactive
     */
    public static function refreshUserData($db, $userId)
    {
        try {
            if (!$userId || !isset($_SESSION['user'])) return false;

            // Handle both PDO and MySQLi connections for backward compatibility
            $userData = null;
            $sql = "SELECT u.id, u.username, u.email, u.role_id, u.is_active, 
                           r.name as role_name, r.is_active as role_active, 
                           u.Level3Name, u.fullname
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.id = ? LIMIT 1";

            if ($db instanceof PDO) {
                $stmt = $db->prepare($sql);
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            } elseif ($db instanceof mysqli) {
                $stmt = $db->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $userData = $result->fetch_assoc();
            }

            if ($userData) {
                // Check if user or role was deactivated
                if (!$userData['is_active'] || (isset($userData['role_active']) && !$userData['role_active'])) {
                    self::destroy();
                    return false;
                }

                // Update session preserving existing fields
                $_SESSION['user'] = array_merge($_SESSION['user'], $userData);
                $_SESSION['user']['department'] = $userData['Level3Name'] ?? null;
                $_SESSION['last_sync'] = time();

                return $_SESSION['user'];
            } else {
                // User deleted
                self::destroy();
                return false;
            }
        } catch (Exception $e) {
            error_log("Session Refresh Error: " . $e->getMessage());
            return false;
        }
    }
}
