<?php

/**
 * Environment configuration loader
 * Loads environment variables from .env file
 */

class Env
{
    private static $loaded = false;

    /**
     * Load environment variables from .env file
     */
    public static function load()
    {
        if (self::$loaded) {
            return;
        }

        // Look for .env in common locations (project root, core/)
        $possiblePaths = [
            __DIR__ . '/../../.env', // project root (core/Config/../../.env)
            dirname(__DIR__, 2) . '/.env', // More robust root check
            __DIR__ . '/../.env',    // fallback inside core
        ];

        $envFile = null;
        foreach ($possiblePaths as $path) {
            $realPath = realpath($path);
            if ($realPath && file_exists($realPath)) {
                $envFile = $realPath;
                break;
            }
        }

        if (!$envFile) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse variable assignment
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
                ) {
                    $value = substr($value, 1, -1);
                }

                // Set environment variable
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Get environment variable
     */
    public static function get($key, $default = null)
    {
        self::load();

        // 1. ลองหาจาก $_ENV (ที่โหลดจากไฟล์ .env)
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // 2. ถ้าไม่เจอ ลองหาจากระบบ OS (สำหรับ Render / Docker)
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }

        // 3. ถ้าไม่เจอจริงๆ ให้คืนค่า default
        return $default;
    }

    /**
     * Check if environment variable exists
     */
    public static function has($key)
    {
        self::load();

        return isset($_ENV[$key]);
    }

    /**
     * Get base URL automatically from server variables
     * Falls back to APP_URL or BASE_URL from .env if available
     */
    public static function getBaseUrl()
    {
        self::load();

        // Check if explicitly set in .env
        if (!empty($_ENV['APP_URL'])) {
            return rtrim($_ENV['APP_URL'], '/');
        }
        if (!empty($_ENV['BASE_URL'])) {
            return rtrim($_ENV['BASE_URL'], '/');
        }

        // Auto-detect from server variables
        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Get the script directory path
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);

        // Remove '/routes.php' or '/index.php' if present in the path
        $basePath = str_replace(['\\', '/routes.php', '/index.php'], ['/', '', ''], $scriptDir);
        $basePath = rtrim($basePath, '/');

        return $protocol . '://' . $host . $basePath;
    }
}

// Auto-load environment variables
Env::load();

// Set default timezone to Asia/Bangkok
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Bangkok'));
