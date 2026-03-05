<?php

namespace Core\Helpers;

/**
 * IpHelper - Centralized IP detection for Docker/Reverse Proxy environments
 */
class IpHelper
{
    /**
     * Get the accurate client IP address
     * 
     * @return string
     */
    public static function getClientIp()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Standard proxy header
            'HTTP_X_REAL_IP',       // Nginx real ip
            'REMOTE_ADDR'            // Fallback
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // If there are multiple IPs in X-Forwarded-For, take the first one
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Basic validation (optional, can be expanded)
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return 'unknown';
    }
}
