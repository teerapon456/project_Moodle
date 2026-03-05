<?php

require_once __DIR__ . '/InputSanitizer.php';

/**
 * CSRF Protection Middleware
 * ป้องกันการโจมตีแบบ Cross-Site Request Forgery
 */

class CSRFMiddleware
{
    private static $cachedBody = null;

    /**
     * Get the parsed request body (handles JSON)
     */
    public static function getParsedBody()
    {
        if (self::$cachedBody !== null) {
            return self::$cachedBody;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            self::$cachedBody = json_decode($input, true) ?? [];
        } else {
            self::$cachedBody = $_POST;
        }

        return self::$cachedBody;
    }

    /**
     * Validate CSRF token for API requests
     */
    public static function validateRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Skip CSRF validation for GET requests (read-only operations)
        if ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS') {
            return true;
        }

        // For POST, PUT, DELETE requests, validate CSRF token
        $token = null;

        // 1. Check HTTP Header (preferred for AJAX)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // 2. Check POST/Body
        if (!$token) {
            $body = self::getParsedBody();
            if (isset($body['_csrf_token'])) {
                $token = $body['_csrf_token'];
            }
        }

        // 3. Fallback to $_POST (for standard form submits)
        if (!$token && isset($_POST['_csrf_token'])) {
            $token = $_POST['_csrf_token'];
        }

        // 4. Fallback to $_GET (rarely used for state changes)
        if (!$token && isset($_GET['_csrf_token'])) {
            $token = $_GET['_csrf_token'];
        }

        if (!$token) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'CSRF token missing',
                'code' => 'csrf_missing'
            ]);
            return false;
        }

        if (!InputSanitizer::validateCSRF($token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid CSRF token',
                'code' => 'csrf_invalid'
            ]);
            return false;
        }

        return true;
    }

    /**
     * Generate CSRF token for forms
     */
    public static function generateToken()
    {
        return InputSanitizer::generateCSRFToken();
    }

    /**
     * Get CSRF token as HTML input field
     */
    public static function getHiddenField()
    {
        $token = self::generateToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get CSRF token as meta tag for JavaScript
     */
    public static function getMetaTag()
    {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate AJAX requests with custom header
     */
    public static function validateAjaxRequest()
    {
        // Check for custom header
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            return InputSanitizer::validateCSRF($token);
        }

        // Check for token in request body
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input && isset($input['_csrf_token'])) {
            return InputSanitizer::validateCSRF($input['_csrf_token']);
        }

        return false;
    }

    /**
     * Middleware function to be called at the beginning of API endpoints
     */
    public static function protect()
    {
        // Skip for safe methods
        $safeMethods = ['GET', 'HEAD', 'OPTIONS'];
        if (in_array($_SERVER['REQUEST_METHOD'], $safeMethods)) {
            return true;
        }

        return self::validateRequest();
    }

    /**
     * Check if request should be protected (whitelist for certain endpoints)
     */
    public static function shouldProtect($endpoint)
    {
        // Define endpoints that don't need CSRF protection
        $unprotectedEndpoints = [
            'auth/login',
            'auth/register',
            'auth/me',
            'auth/logout',
            'auth/forgot-password',
            'auth/reset-password'
        ];

        return !in_array($endpoint, $unprotectedEndpoints);
    }
}
