<?php

/**
 * Session Configuration
 * เรียกใช้ไฟล์นี้ก่อน session_start() ทุกครั้ง
 * แก้ปัญหา Antivirus scanning C:\xampp\tmp ทำให้ session ช้า
 */

// Only configure if session not started yet
if (session_status() === PHP_SESSION_NONE) {
    // Use Redis for session storage
    // Check if Redis extension is loaded and host is available
    if (extension_loaded('redis') && ($redisHost = getenv('REDIS_HOST') ?: 'redis')) {
        ini_set('session.save_handler', 'redis');
        // tcp://HOST:PORT?auth=PASSWORD
        ini_set('session.save_path', "tcp://$redisHost:6379");
    } else {
        // Fallback to file system if Redis is not available
        $sessionPath = __DIR__ . '/../../storage/sessions';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0755, true);
        }
        ini_set('session.save_handler', 'files');
        session_save_path($sessionPath);
    }

    // Configure session cookie for Docker environment
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    session_set_cookie_params([
        'lifetime' => 0, // Session cookie
        'path' => '/', // Root path for all subdirectories
        'domain' => '', // Auto-detect domain
        'secure' => $isHttps, // Only secure if HTTPS
        'httponly' => true, // Prevent JavaScript access
        'samesite' => 'Lax' // Allow cross-site navigation
    ]);

    // Set additional session settings for reliability
    ini_set('session.gc_maxlifetime', 28800); // 8 hours
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
}

/**
 * Start session with optimized settings
 * - Checks if session is already active
 * - Prevents header sent errors
 * - Uses custom save path to avoid AV scanning
 * 
 * @return void
 */
function startOptimizedSession()
{
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
}

/**
 * Start session, read data, and close immediately for performance
 * Use this for read-only session access (e.g., SSE streams, status checks)
 * to avoid session file locking which blocks other requests.
 * 
 * @return array Session data copy
 */
function startSessionReadOnly()
{
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $data = $_SESSION ?? [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    return $data;
}
