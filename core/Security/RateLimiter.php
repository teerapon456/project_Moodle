<?php

/**
 * Rate Limiting Middleware
 * ป้องกันการโจมตีแบบ Brute Force และ DoS
 */

class RateLimiter
{
    private static $limits = [
        'login' => ['attempts' => 5, 'window' => 900],      // 5 attempts per 15 minutes
        'api' => ['attempts' => 100, 'window' => 60],        // 100 requests per minute
        'upload' => ['attempts' => 10, 'window' => 60],       // 10 uploads per minute
        'register' => ['attempts' => 3, 'window' => 3600],    // 3 registrations per hour
        'forgot_password' => ['attempts' => 3, 'window' => 3600], // 3 password resets per hour
        'default' => ['attempts' => 60, 'window' => 60]       // 60 requests per minute
    ];

    /**
     * Check if request is allowed
     */
    public static function check($type, $identifier = null)
    {
        $identifier = $identifier ?? self::getIdentifier();
        $limit = self::$limits[$type] ?? self::$limits['default'];

        $key = 'rate_limit_' . md5($type . '_' . $identifier);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time(),
                'window_start' => time()
            ];
        }

        $data = &$_SESSION[$key];
        $now = time();

        // Reset if window has passed
        if ($now - $data['window_start'] > $limit['window']) {
            $data['attempts'] = 0;
            $data['window_start'] = $now;
        }

        $data['attempts']++;

        if ($data['attempts'] > $limit['attempts']) {
            $remainingTime = $limit['window'] - ($now - $data['window_start']);
            return [
                'allowed' => false,
                'remaining_time' => $remainingTime,
                'attempts' => $data['attempts'],
                'limit' => $limit['attempts']
            ];
        }

        return [
            'allowed' => true,
            'remaining_attempts' => $limit['attempts'] - $data['attempts'],
            'attempts' => $data['attempts'],
            'limit' => $limit['attempts']
        ];
    }

    /**
     * Get client identifier for rate limiting
     */
    private static function getIdentifier()
    {
        // Use IP address as primary identifier (correctly detected via IpHelper)
        require_once __DIR__ . '/../Helpers/IpHelper.php';
        $ip = \Core\Helpers\IpHelper::getClientIp();

        // If user is logged in, use user ID for more specific limiting
        if (isset($_SESSION['user']['id'])) {
            return 'user_' . $_SESSION['user']['id'];
        }

        return 'ip_' . $ip;
    }

    /**
     * Reset rate limit for specific identifier
     */
    public static function reset($type, $identifier = null)
    {
        $identifier = $identifier ?? self::getIdentifier();
        $key = 'rate_limit_' . md5($type . '_' . $identifier);

        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            return true;
        }

        return false;
    }

    /**
     * Get rate limit status
     */
    public static function getStatus($type, $identifier = null)
    {
        $identifier = $identifier ?? self::getIdentifier();
        $limit = self::$limits[$type] ?? self::$limits['default'];
        $key = 'rate_limit_' . md5($type . '_' . $identifier);

        if (!isset($_SESSION[$key])) {
            return [
                'attempts' => 0,
                'limit' => $limit['attempts'],
                'remaining_time' => 0,
                'reset_time' => 0
            ];
        }

        $data = $_SESSION[$key];
        $now = time();
        $remainingTime = max(0, $limit['window'] - ($now - $data['window_start']));

        return [
            'attempts' => $data['attempts'],
            'limit' => $limit['attempts'],
            'remaining_time' => $remainingTime,
            'reset_time' => $data['window_start'] + $limit['window']
        ];
    }

    /**
     * Middleware function for API endpoints
     */
    public static function protect($type)
    {
        $result = self::check($type);

        if (!$result['allowed']) {
            http_response_code(429);
            header('Retry-After: ' . $result['remaining_time']);
            echo json_encode([
                'success' => false,
                'message' => 'Rate limit exceeded',
                'retry_after' => $result['remaining_time'],
                'attempts' => $result['attempts'],
                'limit' => $result['limit']
            ]);
            exit;
        }

        return true;
    }

    /**
     * Advanced rate limiting with sliding window
     */
    public static function checkSlidingWindow($type, $identifier = null, $maxRequests = 100, $windowSeconds = 60)
    {
        $identifier = $identifier ?? self::getIdentifier();
        $key = 'sliding_rate_' . md5($type . '_' . $identifier);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        $requests = &$_SESSION[$key];
        $now = time();

        // Remove old requests outside the window
        $requests = array_filter($requests, function ($timestamp) use ($now, $windowSeconds) {
            return $now - $timestamp < $windowSeconds;
        });

        // Add current request
        $requests[] = $now;

        if (count($requests) > $maxRequests) {
            // Find the oldest request to calculate retry after
            $oldest = min($requests);
            $retryAfter = $windowSeconds - ($now - $oldest);

            return [
                'allowed' => false,
                'retry_after' => $retryAfter,
                'requests' => count($requests),
                'limit' => $maxRequests
            ];
        }

        return [
            'allowed' => true,
            'requests' => count($requests),
            'limit' => $maxRequests
        ];
    }

    /**
     * Database-based rate limiting for distributed systems
     */
    public static function checkDatabase($type, $identifier = null, $pdo = null)
    {
        if (!$pdo) {
            return self::check($type, $identifier);
        }

        $identifier = $identifier ?? self::getIdentifier();
        $limit = self::$limits[$type] ?? self::$limits['default'];

        $table = 'rate_limits';
        $now = time();
        $windowStart = $now - $limit['window'];

        // Clean old entries
        $cleanupSql = "DELETE FROM $table WHERE type = ? AND created_at < ?";
        $cleanupStmt = $pdo->prepare($cleanupSql);
        $cleanupStmt->execute([$type, $windowStart]);

        // Count recent attempts
        $countSql = "SELECT COUNT(*) as count FROM $table WHERE type = ? AND identifier = ? AND created_at > ?";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([$type, $identifier, $windowStart]);
        $count = $countStmt->fetchColumn();

        if ($count >= $limit['attempts']) {
            return [
                'allowed' => false,
                'attempts' => $count,
                'limit' => $limit['attempts']
            ];
        }

        // Record this attempt
        $insertSql = "INSERT INTO $table (type, identifier, created_at) VALUES (?, ?, ?)";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([$type, $identifier, $now]);

        return [
            'allowed' => true,
            'attempts' => $count + 1,
            'limit' => $limit['attempts']
        ];
    }

    /**
     * Create rate limits table for database-based limiting
     */
    public static function createTable($pdo)
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                identifier VARCHAR(255) NOT NULL,
                created_at INT NOT NULL,
                INDEX idx_type_identifier (type, identifier),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        return $pdo->exec($sql);
    }
}
