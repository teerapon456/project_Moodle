<?php

/**
 * Car Booking Module - Settings Controller
 */

require_once __DIR__ . '/BaseController.php';

class CBSettingsController extends CBBaseController
{
    /**
     * Get all settings
     */
    public function getAll()
    {
        $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE module_id = ?");
        $stmt->execute([2]); // Module ID 2 for Car Booking
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $this->success(['settings' => $settings]);
    }

    /**
     * Save settings
     */
    public function save()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Settings to save
        $allowedKeys = ['admin_emails', 'cc_emails', 'manager_email'];

        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) {
                $this->saveSetting($key, $data[$key]);
            }
        }

        // Log audit
        $this->logAudit('update_settings', 'settings', 0, null, $data);

        return $this->success(['message' => 'บันทึกสำเร็จ']);
    }

    /**
     * Save a single setting
     */
    private function saveSetting($key, $value)
    {
        // Insert or Update using ON DUPLICATE KEY UPDATE logic
        $stmt = $this->pdo->prepare("
            INSERT INTO system_settings (module_id, setting_key, setting_value, updated_at) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        $stmt->execute([2, $key, $value, $value]);
    }

    /**
     * Get setting by key
     */
    public function get($key)
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM system_settings WHERE module_id = ? AND setting_key = ?");
        $stmt->execute([2, $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : null;
    }
}
