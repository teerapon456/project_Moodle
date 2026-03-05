<?php

namespace Core\Security;

/**
 * CsrfHelper - Simple CSRF protection
 */
class CsrfHelper
{
    /**
     * Generate a new CSRF token and store it in session if it doesn't exist
     * 
     * @return string
     */
    public static function generateToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get the current CSRF token from session
     * 
     * @return string|null
     */
    public static function getToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['csrf_token'] ?? null;
    }

    /**
     * Validate a token against the one in session
     * 
     * @param string|null $token
     * @return bool
     */
    public static function validateToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Output a hidden input field with the CSRF token
     */
    public static function insertField()
    {
        $token = self::getToken() ?: self::generateToken();
        echo '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
