<?php

/**
 * Security Configuration
 * การตั้งค่าความปลอดภัยระบบ
 */

class SecurityConfig
{
    /**
     * Get security configuration array
     */
    public static function getConfig()
    {
        return [
            'session' => [
                'lifetime' => 28800, // 8 hours
                'regenerate_id' => true,
                'secure_cookies' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'),
                'httponly_cookies' => true,
                'samesite' => 'Lax'
            ],

            'rate_limiting' => [
                'login' => ['attempts' => 5, 'window' => 900],      // 5 attempts per 15 minutes
                'api' => ['attempts' => 100, 'window' => 60],        // 100 requests per minute
                'upload' => ['attempts' => 10, 'window' => 60],       // 10 uploads per minute
                'register' => ['attempts' => 3, 'window' => 3600],    // 3 registrations per hour
                'forgot_password' => ['attempts' => 3, 'window' => 3600], // 3 password resets per hour
                'default' => ['attempts' => 60, 'window' => 60]       // 60 requests per minute
            ],

            'file_upload' => [
                'max_size' => 10 * 1024 * 1024, // 10MB
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
                'scan_content' => true,
                'quarantine_suspicious' => true
            ],

            'password_policy' => [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => false,
                'max_age_days' => 90
            ],

            'csrf' => [
                'token_length' => 32,
                'token_expiry' => 3600, // 1 hour
                'exempt_routes' => ['auth/login', 'auth/microsoft', 'hrnews/published']
            ],

            'security_headers' => [
                'csp' => true,
                'xss_protection' => true,
                'frame_options' => true,
                'content_type_options' => true,
                'referrer_policy' => true,
                'permissions_policy' => true,
                'hsts' => true
            ],

            'audit' => [
                'log_all_requests' => false,
                'log_failed_logins' => true,
                'log_admin_actions' => true,
                'log_file_changes' => true,
                'retention_days' => 90
            ],

            'encryption' => [
                'algorithm' => 'AES-256-CBC',
                'key_rotation_days' => 90
            ],

            'monitoring' => [
                'enable_real_time_alerts' => true,
                'alert_email' => $_ENV['MANAGER_EMAIL'] ?? null,
                'scan_interval' => 3600, // 1 hour
                'auto_cleanup' => true
            ]
        ];
    }

    /**
     * Get specific configuration section
     */
    public static function get($section)
    {
        $config = self::getConfig();
        return $config[$section] ?? [];
    }

    /**
     * Check if security feature is enabled
     */
    public static function isEnabled($feature)
    {
        $config = self::getConfig();

        switch ($feature) {
            case 'rate_limiting':
                return $config['rate_limiting']['default']['attempts'] > 0;
            case 'csrf_protection':
                return $config['csrf']['token_length'] > 0;
            case 'file_scanning':
                return $config['file_upload']['scan_content'];
            case 'security_headers':
                return $config['security_headers']['csp'];
            case 'audit_logging':
                return $config['audit']['log_failed_logins'];
            case 'encryption':
                return !empty($_ENV['ENCRYPTION_KEY']) && $_ENV['ENCRYPTION_KEY'] !== 'your_encryption_key_here';
            default:
                return false;
        }
    }

    /**
     * Validate configuration
     */
    public static function validate()
    {
        $errors = [];
        $config = self::getConfig();

        // Check required environment variables
        $requiredEnv = ['ENCRYPTION_KEY', 'JWT_SECRET'];
        foreach ($requiredEnv as $env) {
            if (empty($_ENV[$env]) || $_ENV[$env] === 'your_' . strtolower(str_replace('_', '', $env)) . '_here') {
                $errors[] = "Missing or default value for $env";
            }
        }

        // Check session configuration
        if ($config['session']['lifetime'] < 300) {
            $errors[] = "Session lifetime should be at least 5 minutes";
        }

        // Check file upload limits
        if ($config['file_upload']['max_size'] > 50 * 1024 * 1024) {
            $errors[] = "File upload size should not exceed 50MB";
        }

        // Check rate limiting
        if ($config['rate_limiting']['login']['attempts'] > 20) {
            $errors[] = "Login rate limit should not exceed 20 attempts";
        }

        return $errors;
    }

    /**
     * Get security score (0-100)
     */
    public static function getSecurityScore()
    {
        $score = 0;
        $maxScore = 100;

        // Environment variables (20 points)
        if (self::isEnabled('encryption')) $score += 10;
        if (!empty($_ENV['JWT_SECRET']) && $_ENV['JWT_SECRET'] !== 'your_jwt_secret_key_here') $score += 10;

        // Session security (15 points)
        $session = self::get('session');
        if ($session['regenerate_id']) $score += 5;
        if ($session['secure_cookies']) $score += 5;
        if ($session['httponly_cookies']) $score += 5;

        // Rate limiting (15 points)
        if (self::isEnabled('rate_limiting')) $score += 15;

        // CSRF protection (15 points)
        if (self::isEnabled('csrf_protection')) $score += 15;

        // File upload security (15 points)
        if (self::isEnabled('file_scanning')) $score += 10;
        if (!empty(self::get('file_upload')['allowed_types'])) $score += 5;

        // Security headers (10 points)
        if (self::isEnabled('security_headers')) $score += 10;

        // Audit logging (10 points)
        if (self::isEnabled('audit_logging')) $score += 10;

        return min($score, $maxScore);
    }

    /**
     * Get security recommendations
     */
    public static function getRecommendations()
    {
        $recommendations = [];
        $config = self::getConfig();

        if (!self::isEnabled('encryption')) {
            $recommendations[] = "Set strong ENCRYPTION_KEY in .env file";
        }

        if (empty($_ENV['JWT_SECRET']) || $_ENV['JWT_SECRET'] === 'your_jwt_secret_key_here') {
            $recommendations[] = "Set strong JWT_SECRET in .env file";
        }

        if ($config['session']['lifetime'] > 3600) {
            $recommendations[] = "Consider reducing session lifetime to 1 hour or less";
        }

        if (!$config['file_upload']['scan_content']) {
            $recommendations[] = "Enable file content scanning for better security";
        }

        if (!$config['audit']['log_admin_actions']) {
            $recommendations[] = "Enable admin action logging for better audit trail";
        }

        if (empty($config['monitoring']['alert_email'])) {
            $recommendations[] = "Set MANAGER_EMAIL for security alerts";
        }

        return $recommendations;
    }

    /**
     * Apply security configuration
     */
    public static function apply()
    {
        // Apply security headers
        if (self::isEnabled('security_headers')) {
            SecurityHeaders::applyByEnvironment();
        }

        // Start secure session
        if (session_status() === PHP_SESSION_NONE) {
            SecureSession::start();
        }

        // Set session cookie parameters
        $session = self::get('session');
        if (session_status() === PHP_SESSION_ACTIVE) {
            $cookieParams = session_get_cookie_params();
            session_set_cookie_params([
                'lifetime' => $session['lifetime'],
                'path' => $cookieParams['path'],
                'domain' => $cookieParams['domain'],
                'secure' => $session['secure_cookies'],
                'httponly' => $session['httponly_cookies'],
                'samesite' => $session['samesite']
            ]);
        }
    }
}
