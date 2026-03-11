<?php

namespace Core\Helpers;

class UrlHelper
{
    private static $baseUrl = null;
    private static $basePath = null;

    /**
     * Get the base path - auto-detected from project structure.
     * Works for XAMPP (/myhr_services), Docker (/), or any deployment.
     */
    public static function getBasePath()
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        // 1. First try environment variable (explicit override)
        $envPath = getenv('APP_BASE_PATH');
        if ($envPath !== false && $envPath !== '') {
            self::$basePath = $envPath;
            return self::$basePath;
        }

        // 2. Auto-detect from script path and project root
        $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        if ($scriptPath && $scriptName) {
            // Find project root by looking for composer.json or .env
            $projectRoot = self::findProjectRoot($scriptPath);

            if ($projectRoot) {
                // Get document root
                $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
                $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
                $projectRoot = str_replace('\\', '/', $projectRoot);

                // Calculate base path relative to document root
                if ($docRoot && strpos($projectRoot, $docRoot) === 0) {
                    self::$basePath = substr($projectRoot, strlen($docRoot));
                } else {
                    self::$basePath = '';
                }
            } else {
                self::$basePath = '';
            }
        } else {
            self::$basePath = '';
        }

        return self::$basePath;
    }

    /**
     * Find project root by looking for marker files (composer.json, .env)
     */
    private static function findProjectRoot($startPath)
    {
        $dir = dirname($startPath);
        $maxLevels = 10; // Prevent infinite loop

        while ($maxLevels-- > 0) {
            // Check for project markers
            if (file_exists($dir . '/composer.json') || file_exists($dir . '/.env')) {
                return $dir;
            }

            $parent = dirname($dir);
            if ($parent === $dir) {
                break; // Reached filesystem root
            }
            $dir = $parent;
        }

        return null;
    }

    /**
     * Calculate and return the base URL of the application.
     * Example: http://localhost/myhr_services or http://localhost
     */
    public static function getBaseUrl()
    {
        if (self::$baseUrl !== null) {
            return self::$baseUrl;
        }

        // 0. Check APP_URL from environment (Priority)
        $envUrl = getenv('APP_URL');
        if ($envUrl !== false && $envUrl !== '') {
            self::$baseUrl = rtrim($envUrl, '/');
            return self::$baseUrl;
        }

        // 1. Protocol - respect proxy headers if present (e.g., Render, Heroku)
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = strtok($_SERVER['HTTP_X_FORWARDED_PROTO'], ',');
        } else {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https" : "http";
        }

        // 2. Host - prefer X-Forwarded-Host when behind a proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
        } else {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        }

        // 3. Base path from environment
        $basePath = self::getBasePath();

        self::$baseUrl = "{$protocol}://{$host}{$basePath}";

        return self::$baseUrl;
    }

    /**
     * Generate a full URL for a given path.
     * @param string $path Path relative to app root (e.g., 'dormitory/dashboard')
     * @return string Full URL
     */
    public static function url($path = '')
    {
        $base = self::getBaseUrl();
        $path = ltrim($path, '/');
        return $base . '/' . $path;
    }

    /**
     * Generate a path (without host) for a given relative path.
     * @param string $path Path relative to app root
     * @return string Path with base path prefix
     */
    public static function path($path = '')
    {
        $basePath = self::getBasePath();
        $path = ltrim($path, '/');
        return $basePath . '/' . $path;
    }

    /**
     * Get the asset base path - handles Docker vs XAMPP environment.
     * In Docker (DocumentRoot = public/): returns basePath + '/'
     * In XAMPP (DocumentRoot = htdocs): returns basePath + '/public/'
     * @return string Asset base path with trailing slash
     */
    public static function getAssetBase()
    {
        $basePath = self::getBasePath();
        $baseRoot = rtrim($basePath, '/');

        // Check if DocumentRoot points to public/ folder (Docker) or htdocs (XAMPP)
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        if ($docRoot && is_dir($docRoot . '/assets')) {
            // Docker: DocumentRoot is public/, assets are at /assets/
            return ($baseRoot ? $baseRoot : '') . '/';
        } else {
            // XAMPP: DocumentRoot is htdocs, assets are at /public/assets/
            return ($baseRoot ? $baseRoot : '') . '/public/';
        }
    }

    /**
     * Get the link base path for navigation links.
     * @return string Link base path with trailing slash
     */
    public static function getLinkBase()
    {
        $basePath = self::getBasePath();
        $baseRoot = rtrim($basePath, '/');
        return ($baseRoot ? $baseRoot . '/' : '/');
    }

    /**
     * Get the current full URL including query strings.
     * @param bool $includeQuery Whether to include query parameters
     * @return string Full URL
     */
    public static function getCurrentUrl($includeQuery = true)
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        if (!$includeQuery) {
            $uri = explode('?', $uri)[0];
        }

        return $protocol . "://" . $host . $uri;
    }
}
