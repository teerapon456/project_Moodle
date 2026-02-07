<?php
require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/../Helpers/UrlHelper.php';

use Core\Helpers\UrlHelper;

/**
 * Microsoft OAuth Configuration
 * 
 * Setup Instructions:
 * 1. Go to https://portal.azure.com
 * 2. Navigate to "Azure Active Directory" > "App registrations"
 * 3. Click "New registration"
 * 4. Set name: "Car Booking System"
 * 5. Set redirect URI: http://localhost/car_booking/routes.php/auth/microsoft/callback
 * 6. After creation, copy the "Application (client) ID" to .env as MICROSOFT_CLIENT_ID
 * 7. Go to "Certificates & secrets" > "New client secret"
 * 8. Copy the secret value to .env as MICROSOFT_CLIENT_SECRET
 * 9. Go to "API permissions" > "Add permission" > "Microsoft Graph" > "Delegated permissions"
 * 10. Add: openid, profile, email, User.Read
 */

class MicrosoftOAuthConfig
{
    // Load from environment variables for security
    private static $clientId;
    private static $clientSecret;
    private static $redirectUri;
    private static $tenant;

    // Initialize configuration from environment
    private static function init()
    {
        if (self::$clientId === null) {
            self::$clientId = Env::get('MICROSOFT_CLIENT_ID');
            self::$clientSecret = Env::get('MICROSOFT_CLIENT_SECRET');

            // Auto-detect redirect URI if not specified in .env
            $envRedirectUri = Env::get('MICROSOFT_REDIRECT_URI');
            if ($envRedirectUri && $envRedirectUri !== '') {
                self::$redirectUri = $envRedirectUri;
            } else {
                // Auto-generate using UrlHelper
                // Use query parameter format for servers without mod_rewrite (e.g., Android)
                self::$redirectUri = UrlHelper::url('auth/microsoft/callback');
            }

            self::$tenant = Env::get('MICROSOFT_TENANT_ID');
        }
    }

    // Constants that delegate to environment values
    public static function CLIENT_ID()
    {
        self::init();
        return self::$clientId;
    }

    public static function CLIENT_SECRET()
    {
        self::init();
        return self::$clientSecret;
    }

    public static function REDIRECT_URI()
    {
        self::init();
        return self::$redirectUri;
    }

    public static function TENANT()
    {
        self::init();
        return self::$tenant;
    }


    // OAuth scopes - what information we want from Microsoft
    const SCOPES = [
        'openid',           // Required for authentication
        'profile',          // User's profile information
        'email',            // User's email address
        'User.Read',        // Read user's profile via Microsoft Graph API
        'User.ReadBasic.All', // Read all users' basic profiles
        'offline_access'    // Get refresh token
    ];

    // Microsoft Graph API endpoint
    const GRAPH_API_ENDPOINT = 'https://graph.microsoft.com/v1.0/me';

    /**
     * Get authorization URL
     */
    public static function getAuthorizationUrl()
    {
        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize',
            self::TENANT()
        );
    }

    /**
     * Get token URL
     */
    public static function getTokenUrl()
    {
        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
            self::TENANT()
        );
    }

    /**
     * Check if OAuth is configured
     */
    public static function isConfigured()
    {
        $clientId = self::CLIENT_ID();
        $clientSecret = self::CLIENT_SECRET();

        return $clientId !== ''
            && $clientId !== 'YOUR_CLIENT_ID_HERE'
            && $clientSecret !== ''
            && $clientSecret !== 'YOUR_CLIENT_SECRET_HERE';
    }

    /**
     * Get application-level access token using Client Credentials flow
     * This doesn't require user login - uses app permissions
     */
    public static function getAppAccessToken()
    {
        // Check cache first (token valid for ~1 hour)
        if (isset($_SESSION['ms_app_token']) && isset($_SESSION['ms_app_token_expires'])) {
            if (time() < $_SESSION['ms_app_token_expires']) {
                return $_SESSION['ms_app_token'];
            }
        }

        if (!self::isConfigured()) {
            return null;
        }

        try {
            $tokenUrl = self::getTokenUrl();
            $postData = [
                'client_id' => self::CLIENT_ID(),
                'client_secret' => self::CLIENT_SECRET(),
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (!empty($data['access_token'])) {
                    // Cache the token
                    $_SESSION['ms_app_token'] = $data['access_token'];
                    $_SESSION['ms_app_token_expires'] = time() + ($data['expires_in'] ?? 3600) - 60; // 1 min buffer
                    return $data['access_token'];
                }
            }
        } catch (Exception $e) {
            // Ignore errors
        }

        return null;
    }
}
