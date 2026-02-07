<?php

/**
 * ValidationHelper - Input validation and sanitization utilities
 * ป้องกัน XSS, SQL Injection และ validate ข้อมูล
 */

class ValidationHelper
{
    /**
     * Sanitize string input - ป้องกัน XSS
     */
    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email format
     */
    public static function isEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate integer
     */
    public static function isInt($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate positive integer (ID)
     */
    public static function isPositiveInt($value)
    {
        return self::isInt($value) && (int)$value > 0;
    }

    /**
     * Validate date format (Y-m-d)
     */
    public static function isDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate datetime format (Y-m-d H:i:s)
     */
    public static function isDateTime($datetime)
    {
        return self::isDate($datetime, 'Y-m-d H:i:s') || self::isDate($datetime, 'Y-m-d H:i');
    }

    /**
     * Validate string length
     */
    public static function isLength($input, $min = 0, $max = null)
    {
        $len = mb_strlen($input);
        if ($len < $min) return false;
        if ($max !== null && $len > $max) return false;
        return true;
    }

    /**
     * Validate phone number (Thai format)
     */
    public static function isPhone($phone)
    {
        return preg_match('/^0[0-9]{8,9}$/', preg_replace('/[^0-9]/', '', $phone));
    }

    /**
     * Validate enum value
     */
    public static function isEnum($value, array $allowed)
    {
        return in_array($value, $allowed, true);
    }

    /**
     * Validate required fields - return missing field names
     */
    public static function validateRequired(array $data, array $required)
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    /**
     * Validate and return errors array
     * $rules = ['email' => 'required|email', 'age' => 'required|int|min:18']
     */
    public static function validate(array $data, array $rules)
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                $params = explode(':', $rule);
                $ruleName = $params[0];
                $ruleParam = $params[1] ?? null;

                $error = self::checkRule($field, $value, $ruleName, $ruleParam);
                if ($error) {
                    $errors[$field] = $error;
                    break; // Stop at first error for this field
                }
            }
        }

        return $errors;
    }

    private static function checkRule($field, $value, $rule, $param)
    {
        switch ($rule) {
            case 'required':
                if ($value === null || trim($value) === '') {
                    return "กรุณากรอก $field";
                }
                break;
            case 'email':
                if ($value && !self::isEmail($value)) {
                    return "รูปแบบอีเมลไม่ถูกต้อง";
                }
                break;
            case 'int':
                if ($value && !self::isInt($value)) {
                    return "$field ต้องเป็นตัวเลข";
                }
                break;
            case 'min':
                if (is_numeric($value) && $value < $param) {
                    return "$field ต้องมากกว่าหรือเท่ากับ $param";
                }
                if (is_string($value) && mb_strlen($value) < $param) {
                    return "$field ต้องมีอย่างน้อย $param ตัวอักษร";
                }
                break;
            case 'max':
                if (is_numeric($value) && $value > $param) {
                    return "$field ต้องน้อยกว่าหรือเท่ากับ $param";
                }
                if (is_string($value) && mb_strlen($value) > $param) {
                    return "$field ต้องไม่เกิน $param ตัวอักษร";
                }
                break;
            case 'date':
                if ($value && !self::isDate($value)) {
                    return "รูปแบบวันที่ไม่ถูกต้อง (Y-m-d)";
                }
                break;
            case 'phone':
                if ($value && !self::isPhone($value)) {
                    return "รูปแบบเบอร์โทรไม่ถูกต้อง";
                }
                break;
        }
        return null;
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
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get CSRF hidden input HTML
     */
    public static function csrfField()
    {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }
}
