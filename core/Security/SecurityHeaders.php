<?php

/**
 * Security Headers Middleware
 * เพิ่ม HTTP headers สำหรับความปลอดภัย
 */

class SecurityHeaders
{
    /**
     * Apply all security headers
     */
    public static function applyAll()
    {
        self::applyContentSecurityPolicy();
        self::applyXSSProtection();
        self::applyFrameOptions();
        self::applyContentTypeOptions();
        self::applyReferrerPolicy();
        self::applyPermissionsPolicy();
        self::applyStrictTransportSecurity();
    }

    /**
     * Content Security Policy (CSP)
     */
    public static function applyContentSecurityPolicy()
    {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://www.google.com https://www.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https: http:",
            "connect-src 'self' https://graph.microsoft.com https://login.microsoftonline.com",
            "frame-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests"
        ];

        header("Content-Security-Policy: " . implode('; ', $csp));
    }

    /**
     * XSS Protection
     */
    public static function applyXSSProtection()
    {
        header("X-XSS-Protection: 1; mode=block");
    }

    /**
     * Frame Options (Clickjacking protection)
     */
    public static function applyFrameOptions()
    {
        header("X-Frame-Options: DENY");
    }

    /**
     * Content Type Options
     */
    public static function applyContentTypeOptions()
    {
        header("X-Content-Type-Options: nosniff");
    }

    /**
     * Referrer Policy
     */
    public static function applyReferrerPolicy()
    {
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }

    /**
     * Permissions Policy
     */
    public static function applyPermissionsPolicy()
    {
        $permissions = [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()',
            'autoplay=()',
            'encrypted-media=()',
            'fullscreen=()',
            'picture-in-picture=()'
        ];

        header("Permissions-Policy: " . implode(', ', $permissions));
    }

    /**
     * Strict Transport Security (HTTPS only)
     */
    public static function applyStrictTransportSecurity()
    {
        // Only apply if HTTPS is enabled
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
    }

    /**
     * Development headers (less strict for debugging)
     */
    public static function applyDevelopment()
    {
        // Less strict CSP for development
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://www.google.com https://www.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https: http:",
            "connect-src 'self' https://graph.microsoft.com https://login.microsoftonline.com ws://localhost:8080",
            "frame-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];

        header("Content-Security-Policy: " . implode('; ', $csp));
        header("X-XSS-Protection: 1; mode=block");
        header("X-Frame-Options: SAMEORIGIN"); // Allow same origin for development
        header("X-Content-Type-Options: nosniff");
    }

    /**
     * API specific headers
     */
    public static function applyAPI()
    {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: no-referrer");
        
        // CORS headers for API
        header("Access-Control-Allow-Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost'));
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With");
        header("Access-Control-Max-Age: 3600");
    }

    /**
     * Upload headers (for file upload endpoints)
     */
    public static function applyUpload()
    {
        // More relaxed for uploads but still secure
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "connect-src 'self'"
        ];

        header("Content-Security-Policy: " . implode('; ', $csp));
        header("X-Content-Type-Options: nosniff");
    }

    /**
     * Apply headers based on environment
     */
    public static function applyByEnvironment()
    {
        $env = $_ENV['APP_ENV'] ?? 'development';
        
        switch ($env) {
            case 'production':
                self::applyAll();
                break;
            case 'development':
                self::applyDevelopment();
                break;
            default:
                self::applyDevelopment();
                break;
        }
    }

    /**
     * Custom CSP for specific pages
     */
    public static function applyCustomCSP($directives)
    {
        if (is_array($directives)) {
            $csp = [];
            foreach ($directives as $directive => $sources) {
                $csp[] = $directive . ' ' . $sources;
            }
            header("Content-Security-Policy: " . implode('; ', $csp));
        }
    }

    /**
     * Report-Only mode for testing CSP
     */
    public static function applyCSPReportOnly()
    {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "connect-src 'self'",
            "frame-src 'self'",
            "object-src 'none'"
        ];

        header("Content-Security-Policy-Report-Only: " . implode('; ', $csp));
        
        // Add report endpoint
        header("Content-Security-Policy-Report-Only: " . implode('; ', $csp) . "; report-uri /csp-violation-report");
    }
}
