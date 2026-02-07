<?php
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../Helpers/PermissionHelper.php';

class SettingsController
{
    private $db;
    private $conn;
    private $perm;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->perm = YAPermissionHelper::getInstance();
    }

    public function saveSetting()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            return;
        }

        $userId = $this->perm->getUserId();
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        $key = $_POST['key'] ?? '';
        $value = $_POST['value'] ?? '';

        if (!$key) {
            echo json_encode(['success' => false, 'message' => 'Missing key']);
            return;
        }

        try {
            // Upsert setting
            $sql = "INSERT INTO ya_user_settings (user_id, setting_key, setting_value) 
                    VALUES (:uid, :key, :val) 
                    ON DUPLICATE KEY UPDATE setting_value = :val2";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':uid' => $userId,
                ':key' => $key,
                ':val' => $value,
                ':val2' => $value
            ]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getSettings($userId = null)
    {
        if (!$userId) $userId = $this->perm->getUserId();

        $sql = "SELECT setting_key, setting_value FROM ya_user_settings WHERE user_id = :uid";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':uid' => $userId]);

        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
