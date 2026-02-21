<?php

/**
 * AuthMiddleware
 * Centralized logic for Session Authentication and Redirects
 * Handles Docker vs XAMPP environment differences automatically.
 */

class AuthMiddleware
{
    /**
     * Check if user is logged in. If not, redirect to login page.
     * 
     * @param string $linkBase Optional base path for XAMPP (e.g. /MyHR/)
     * @return array|null Returns $user array if authenticated
     */
    public static function checkLogin($linkBase = '/')
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            // Use SessionConfig if available
            $sessionConfig = __DIR__ . '/../../Config/SessionConfig.php';
            if (file_exists($sessionConfig)) {
                require_once $sessionConfig;
                if (function_exists('startOptimizedSession')) {
                    startOptimizedSession();
                } else {
                    session_start();
                }
            } else {
                session_start();
            }
        }

        // Try to restore session from remember token if not logged in
        if (empty($_SESSION['user'])) {
            require_once __DIR__ . '/../Auth/AuthController.php';
            AuthController::attemptAutoLogin();
        }

        require_once __DIR__ . '/SecureSession.php';
        // Auto-refresh session data every 5 minutes to prevent stale permissions
        if (isset($_SESSION['user']['id'])) {
            $lastSync = $_SESSION['last_sync'] ?? 0;
            if (time() - $lastSync > 300) { // 5 minutes
                // Assuming Database connection is available or we temporarily instantiate one
                require_once __DIR__ . '/../Database/Database.php';
                $db = new \Database();
                $refreshedUser = SecureSession::refreshUserData($db->getConnection(), $_SESSION['user']['id']);
                if (!$refreshedUser) {
                    self::redirectToLogin($linkBase, 'role_inactive');
                }
            }
        }

        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            self::redirectToLogin($linkBase, 'session_expired');
        }

        return $user;
    }

    /**
     * Redirect to Login page with environment awareness
     * 
     * @param string $linkBase
     * @param string $error Error code (e.g. 'session_expired', 'no_permission')
     */
    public static function redirectToLogin($linkBase = '/', $error = '')
    {
        // Detect Docker Public Root
        // If DocumentRoot ends in 'public', we are likely in Docker container mapping public directly
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $isDockerPublic = (basename(rtrim($docRoot, '/')) === 'public');

        // Construct Login Path
        if ($isDockerPublic) {
            $loginPath = '/index.php';
        } else {
            // Ensure linkBase ends with / if not empty
            $base = rtrim($linkBase, '/');
            $loginPath = ($base ? $base : '') . '/public/index.php';
        }

        // Add error param
        if ($error) {
            $loginPath .= '?error=' . urlencode($error);
        }

        header("Location: " . $loginPath);
        exit;
    }

    /**
     * Check permissions (Optional Helper)
     */
    public static function checkPermission($condition, $linkBase = '/', $error = 'no_permission')
    {
        if (!$condition) {
            self::redirectToLogin($linkBase, $error);
        }
    }
}
