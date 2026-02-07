<?php

/**
 * Input Sanitizer & Security Helper
 * Prevents XSS, SQL Injection, and other attacks
 */

class InputSanitizer
{
    /**
     * Sanitize input for HTML output (XSS prevention)
     */
    public static function sanitize($input, $type = 'string')
    {
        if ($input === null) return null;

        switch ($type) {
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            case 'string':
                return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            case 'email':
                $email = filter_var(trim($input), FILTER_SANITIZE_EMAIL);
                return $email === false ? null : $email;

            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);

            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            case 'alpha':
                return preg_replace('/[^a-zA-Z]/', '', $input);

            case 'alphanum':
                return preg_replace('/[^a-zA-Z0-9]/', '', $input);

            case 'filename':
                // Remove dangerous characters from filename
                $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $input);
                $filename = preg_replace('/\.{2,}/', '.', $filename); // Remove multiple dots
                $filename = trim($filename, '.'); // Remove leading/trailing dots
                return $filename;

            default:
                return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    /**
     * Validate file upload security
     */
    public static function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'])
    {
        $errors = [];

        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }

        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            $errors[] = 'File too large (max 10MB)';
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }

        // Check MIME type
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = 'Invalid file type';
        }

        // Check for PHP tags in file content
        $content = file_get_contents($file['tmp_name']);
        if (strpos($content, '<?php') !== false || strpos($content, '<?') !== false) {
            $errors[] = 'File contains executable code';
        }

        return $errors;
    }

    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($originalName, $prefix = '')
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = self::sanitize(pathinfo($originalName, PATHINFO_FILENAME), 'filename');
        $timestamp = time();
        $random = bin2hex(random_bytes(4));

        return ($prefix ? $prefix . '_' : '') . $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * Sanitize array of inputs
     */
    public static function sanitizeArray($array, $rules = [])
    {
        $sanitized = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $rules[$key] ?? []);
            } else {
                $type = $rules[$key] ?? 'string';
                $sanitized[$key] = self::sanitize($value, $type);
            }
        }

        return $sanitized;
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRF($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken()
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
     * Methods merged from core/Helpers/InputSanitizer.php
     * ---------------------------------------------------
     */

    private static $dangerousPatterns = [
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/<\s*\/?\s*script\s*>/is',
        '/javascript:/is',
        '/on\w+\s*=/is',  // onclick=, onload=, etc.
        '/<\s*iframe/is',
        '/<\s*object/is',
        '/<\s*embed/is',
        '/<\s*form/is',
        '/<\s*input/is',
        '/<\s*button/is',
        '/<\s*style/is',
        '/<\s*link/is',
        '/<\s*meta/is',
    ];

    /**
     * Sanitize text input - remove HTML tags and dangerous content
     * @param string|null $input
     * @return string
     */
    public static function text($input)
    {
        if ($input === null || $input === '') {
            return '';
        }
        $input = (string)$input;
        $clean = strip_tags($input);
        foreach (self::$dangerousPatterns as $pattern) {
            $clean = preg_replace($pattern, '', $clean);
        }
        return trim($clean);
    }

    /**
     * Alias for sanitize('html')
     */
    public static function html($input)
    {
        return self::sanitize($input, 'html');
    }

    /**
     * Sanitize integer
     */
    public static function int($input)
    {
        return (int)filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize email address
     */
    public static function email($input)
    {
        if ($input === null || $input === '') {
            return '';
        }
        $email = filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        return $email === false ? '' : $email;
    }

    /**
     * Sanitize datetime string
     */
    public static function datetime($input)
    {
        if ($input === null || $input === '') {
            return null;
        }
        $clean = preg_replace('/[^0-9\-:T\s]/', '', $input);

        $formats = ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $clean);
            if ($dt) return $dt->format('Y-m-d H:i:s');
        }
        return null;
    }

    /**
     * Check if input contains dangerous content
     */
    public static function hasDangerousContent($input)
    {
        if ($input === null || $input === '') return false;
        $input = (string)$input;
        if ($input !== strip_tags($input)) return true;
        foreach (self::$dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) return true;
        }
        return false;
    }

    /**
     * Validate key fields for dangerous content
     */
    public static function validateNoHtml(array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && self::hasDangerousContent($data[$field])) {
                return [
                    'valid' => false,
                    'field' => $field,
                    'message' => "ข้อมูลในช่อง '$field' มีเนื้อหาที่ไม่อนุญาต (HTML/Script)"
                ];
            }
        }
        return ['valid' => true];
    }
}
