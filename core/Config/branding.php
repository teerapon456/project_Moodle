<?php
require_once __DIR__ . '/database.php';

/**
 * Load branding settings from database
 * Returns array with app_name, app_description, navbar_color, logo_path, favicon_path
 */
function loadBrandingSettings()
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    try {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('app_name', 'app_description', 'navbar_color', 'logo_path', 'favicon_path')";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $settings = [
            'app_name' => 'ระบบจองรถ',
            'app_description' => 'บริการจองรถสำหรับองค์กร',
            'navbar_color' => '#A21D21',
            'logo_path' => null,
            'favicon_path' => null
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $cache = $settings;
        return $settings;
    } catch (Exception $e) {
        error_log("Error loading branding settings: " . $e->getMessage());
        return [
            'app_name' => 'ระบบจองรถ',
            'app_description' => 'บริการจองรถสำหรับองค์กร',
            'navbar_color' => '#A21D21',
            'logo_path' => null,
            'favicon_path' => null
        ];
    }
}
