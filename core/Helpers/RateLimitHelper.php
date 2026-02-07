<?php

/**
 * RateLimitHelper - API Rate Limiting
 * จำกัดจำนวน requests ต่อ IP/User ต่อนาที
 */

class RateLimitHelper
{
    private static $cacheDir = null;

    /**
     * Initialize cache directory
     */
    private static function getCacheDir()
    {
        if (self::$cacheDir === null) {
            self::$cacheDir = __DIR__ . '/../../logs/rate_limit';
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
        return self::$cacheDir;
    }

    /**
     * Get client identifier (IP + User ID if logged in)
     */
    private static function getClientKey()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userId = $_SESSION['user']['id'] ?? 'anon';
        return md5($ip . '_' . $userId);
    }

    /**
     * Check rate limit
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowSeconds Time window in seconds (default 60 = 1 minute)
     * @param string $resource Optional resource identifier for granular limits
     * @return bool True if allowed, False if rate limited
     */
    public static function check($maxRequests = 60, $windowSeconds = 60, $resource = 'global')
    {
        $key = self::getClientKey() . '_' . $resource;
        $cacheFile = self::getCacheDir() . '/' . $key . '.json';

        $now = time();
        $windowStart = $now - $windowSeconds;

        // Load existing requests
        $requests = [];
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && isset($data['requests'])) {
                // Filter out requests outside the window
                $requests = array_filter($data['requests'], function ($timestamp) use ($windowStart) {
                    return $timestamp > $windowStart;
                });
            }
        }

        // Check if limit exceeded
        if (count($requests) >= $maxRequests) {
            return false;
        }

        // Add current request
        $requests[] = $now;

        // Save updated requests
        file_put_contents($cacheFile, json_encode(['requests' => array_values($requests)]));

        return true;
    }

    /**
     * Enforce rate limit - returns 429 if exceeded
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowSeconds Time window in seconds
     * @param string $resource Optional resource identifier
     */
    public static function enforce($maxRequests = 60, $windowSeconds = 60, $resource = 'global')
    {
        if (!self::check($maxRequests, $windowSeconds, $resource)) {
            http_response_code(429);
            header('Retry-After: ' . $windowSeconds);
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $windowSeconds
            ]);
            exit;
        }
    }

    /**
     * Get remaining requests for current client
     */
    public static function getRemaining($maxRequests = 60, $windowSeconds = 60, $resource = 'global')
    {
        $key = self::getClientKey() . '_' . $resource;
        $cacheFile = self::getCacheDir() . '/' . $key . '.json';

        $windowStart = time() - $windowSeconds;
        $count = 0;

        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && isset($data['requests'])) {
                $requests = array_filter($data['requests'], function ($timestamp) use ($windowStart) {
                    return $timestamp > $windowStart;
                });
                $count = count($requests);
            }
        }

        return max(0, $maxRequests - $count);
    }

    /**
     * Reset rate limit for current client
     */
    public static function reset($resource = 'global')
    {
        $key = self::getClientKey() . '_' . $resource;
        $cacheFile = self::getCacheDir() . '/' . $key . '.json';

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    /**
     * Clean up old cache files (run periodically)
     */
    public static function cleanup($maxAgeSeconds = 3600)
    {
        $dir = self::getCacheDir();
        $files = glob($dir . '/*.json');
        $now = time();

        foreach ($files as $file) {
            if ($now - filemtime($file) > $maxAgeSeconds) {
                unlink($file);
            }
        }
    }

    /**
     * Add rate limit headers to response
     */
    public static function addHeaders($maxRequests = 60, $windowSeconds = 60, $resource = 'global')
    {
        $remaining = self::getRemaining($maxRequests, $windowSeconds, $resource);
        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . (time() + $windowSeconds));
    }
}
