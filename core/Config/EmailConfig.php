<?php

require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/../Database/Database.php';

class EmailConfig
{
    // Static properties for configuration
    private static $initialized = false;
    private static $settings = [];
    private static $runtimeOverrides = [];
    private static $moduleId = null; // Current module ID

    // Fixed constants
    const SMTP_SECURE = 'tls';
    const SMTP_CHARSET = 'UTF-8';
    const SMTP_DEBUG = 1;
    const ENABLE_EMAILS = true;

    /**
     * Set Module ID for loading settings
     */
    public static function setModule($id)
    {
        self::$moduleId = $id;
        self::$initialized = false; // Force re-initialization
    }

    /**
     * Get current Module ID
     */
    public static function getModuleId()
    {
        return self::$moduleId;
    }

    /**
     * Initialize configuration from Database and Environment
     */
    private static function initialize()
    {
        if (self::$initialized) return;

        // 1. Load defaults from .env
        self::$settings = [
            'smtp_host' => Env::get('SMTP_HOST'),
            'smtp_port' => Env::get('SMTP_PORT'),
            'smtp_username' => Env::get('SMTP_USERNAME'),
            'smtp_password' => Env::get('SMTP_PASSWORD'),
            'smtp_from_email' => Env::get('SMTP_FROM_EMAIL'),
            'smtp_from_name' => Env::get('SMTP_FROM_NAME'),
            'manager_email' => Env::get('MANAGER_EMAIL'),
            'cc_email' => '',  // Optional CC email from database
            'base_url' => Env::getBaseUrl()
        ];

        // 2. Try to load from Database (override .env)
        if (self::$moduleId) {
            try {
                $database = new Database();
                $conn = $database->getConnection();
                if ($conn) {
                    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE module_id = ?");
                    $stmt->execute([self::$moduleId]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (array_key_exists($row['setting_key'], self::$settings)) {
                            self::$settings[$row['setting_key']] = $row['setting_value'];
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignore DB errors, fall back to .env
                error_log("Failed to load email settings from DB: " . $e->getMessage());
            }
        }

        self::$initialized = true;
    }

    /**
     * Set runtime overrides (for testing)
     */
    public static function setOverrides($config)
    {
        self::$runtimeOverrides = $config;
    }

    /**
     * Clear runtime overrides
     */
    public static function clearOverrides()
    {
        self::$runtimeOverrides = [];
    }

    private static function get($key)
    {
        // Check overrides first
        if (isset(self::$runtimeOverrides[$key])) {
            return self::$runtimeOverrides[$key];
        }

        self::initialize();
        return self::$settings[$key] ?? null;
    }

    // Static getter methods
    public static function SMTP_HOST()
    {
        return self::get('smtp_host');
    }
    public static function SMTP_PORT()
    {
        return self::get('smtp_port');
    }
    public static function SMTP_USERNAME()
    {
        return self::get('smtp_username');
    }
    public static function SMTP_PASSWORD()
    {
        return self::get('smtp_password');
    }
    public static function SMTP_FROM_EMAIL()
    {
        return self::get('smtp_from_email');
    }
    public static function SMTP_FROM_NAME()
    {
        return self::get('smtp_from_name');
    }
    public static function MANAGER_EMAIL()
    {
        return self::get('manager_email');
    }
    public static function CC_EMAIL()
    {
        return self::get('cc_email');
    }
    public static function BASE_URL()
    {
        return self::get('base_url');
    }

    public static function getEnableEmails()
    {
        return self::ENABLE_EMAILS;
    }
}
