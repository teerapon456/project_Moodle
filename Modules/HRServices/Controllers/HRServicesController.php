<?php

require_once __DIR__ . '/../../../core/Database/Database.php';

class ModuleController
{
    private $conn;
    private $currentRoleId;
    private $currentRoleActive;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->currentRoleId = $_SESSION['user']['role_id'] ?? null;
        $this->currentRoleActive = $_SESSION['user']['role_active'] ?? 1;
    }

    private function requireAuth()
    {
        if (!$this->currentRoleId) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return false;
        }
        if (!$this->ensureRoleActive()) {
            http_response_code(403);
            echo json_encode(['message' => 'Role นี้ถูกปิดใช้งาน']);
            return false;
        }
        return true;
    }

    private function canManageAnyModule()
    {
        if (!$this->conn || !$this->currentRoleId) {
            return false;
        }

        $sql = "SELECT 1 FROM core_module_permissions WHERE role_id = :role_id AND can_manage = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':role_id', $this->currentRoleId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    private function ensureRoleActive()
    {
        if ($this->currentRoleActive === 0) {
            return false;
        }
        if ($this->currentRoleActive === 1) {
            return true;
        }
        if (!$this->conn || !$this->currentRoleId) {
            return false;
        }
        $sql = "SELECT is_active FROM roles WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $this->currentRoleId, PDO::PARAM_INT);
        $stmt->execute();
        $active = (int)$stmt->fetchColumn();
        $this->currentRoleActive = $active;
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['role_active'] = $active;
        }
        return $active === 1;
    }

    public function processRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'list';

        if ($method === 'GET') {
            switch ($action) {
                case 'list':
                    $this->listModules();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['message' => 'Invalid action']);
            }
        } elseif ($method === 'POST') {
            switch ($action) {
                case 'save_service':
                    $this->saveService();
                    break;
                case 'delete_service':
                    $this->deleteService();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['message' => 'Invalid action']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['message' => 'Method not allowed']);
        }
    }

    private function listModules()
    {
        if (!$this->conn) {
            http_response_code(500);
            echo json_encode(['message' => 'Database connection failed']);
            return;
        }

        $statusFilter = $_GET['status'] ?? null;
        $params = [];
        $where = '';
        if ($statusFilter && in_array($statusFilter, ['ready', 'soon', 'maintenance'])) {
            $where = 'WHERE status = :status';
            $params[':status'] = $statusFilter;
        }

        $sql = "SELECT id, module_id, name, name_translations, category, icon, icon_color, status, path FROM hr_services $where ORDER BY category ASC, id ASC";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($modules);
    }

    private function saveService()
    {
        if (!$this->conn) {
            http_response_code(500);
            echo json_encode(['message' => 'Database connection failed']);
            return;
        }
        if (!$this->requireAuth() || !$this->canManageAnyModule()) {
            http_response_code(403);
            echo json_encode(['message' => 'ไม่มีสิทธิจัดการบริการ']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        // Current/Existing values (Default to empty/defaults for new record)
        $current = [
            'module_id' => null,
            'name' => '',
            'name_translations' => null,
            'category' => '',
            'icon' => '',
            'icon_color' => '#3B82F6',
            'status' => 'ready',
            'path' => '#'
        ];

        if ($id > 0) {
            // Fetch existing data
            $stmt = $this->conn->prepare("SELECT * FROM hr_services WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Merge existing data into defaults, preferring existing for fields not in $data later
                $current = array_merge($current, $existing);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Service not found']);
                return;
            }
        }

        // Resolve params: Use $data value if set, otherwise fallback to $current (existing or default)
        $moduleId = array_key_exists('module_id', $data) ? (int)$data['module_id'] : $current['module_id'];
        $name = array_key_exists('name', $data) ? trim($data['name']) : $current['name'];
        $category = array_key_exists('category', $data) ? trim($data['category']) : $current['category'];
        $icon = array_key_exists('icon', $data) ? trim($data['icon']) : $current['icon'];
        $iconColor = array_key_exists('icon_color', $data) ? trim($data['icon_color']) : $current['icon_color'];
        $status = array_key_exists('status', $data) ? $data['status'] : $current['status'];
        $path = array_key_exists('path', $data) ? trim($data['path']) : $current['path'];

        // Handle name_translations
        if (array_key_exists('name_translations', $data)) {
            $nameTranslations = $data['name_translations'];
            if ($nameTranslations && is_array($nameTranslations)) {
                $nameTranslations = json_encode($nameTranslations, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $nameTranslations = $current['name_translations']; // Keep existing
        }

        // Helper to handles Base64 Image Upload
        if (!empty($data['custom_icon'])) {
            $base64Image = $data['custom_icon'];

            // Validate Base64
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

                // Allow only specific types
                if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                    $base64Image = base64_decode($base64Image);

                    if ($base64Image !== false) {
                        // Create directory if not exists
                        $uploadDir = __DIR__ . '/../public/assets/images/icons/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        // Generate unique filename
                        $filename = 'icon_' . uniqid() . '.' . $type;
                        $filepath = $uploadDir . $filename;

                        // Save file
                        if (file_put_contents($filepath, $base64Image)) {
                            // Update icon path (relative to asset base)
                            $icon = 'Modules/HRServices/public/assets/images/icons/' . $filename;
                            // Reset icon color since we use image
                            $iconColor = '';
                        }
                    }
                }
            }
        }

        // Validation (Only if creating new or if name/category are being wiped out to empty)
        // Note: If updating only status, name and category might strictly be logically required but we pulled them from DB so they shouldn't be empty unless they were empty before.
        if ($name === '' || $category === '') {
            http_response_code(400);
            echo json_encode(['message' => 'กรุณากรอกชื่อและหมวด']);
            return;
        }
        if (!in_array($status, ['ready', 'soon', 'maintenance'])) {
            $status = 'ready';
        }

        if ($id > 0) {
            $sql = "UPDATE hr_services SET module_id = :module_id, name = :name, name_translations = :name_translations, category = :category, icon = :icon, icon_color = :icon_color, status = :status, path = :path WHERE id = :id";
        } else {
            // For Insert, if nameTranslations was null, create default
            if (empty($nameTranslations)) {
                $nameTranslations = json_encode(['en' => $name, 'th' => $name], JSON_UNESCAPED_UNICODE);
            }
            $sql = "INSERT INTO hr_services (module_id, name, name_translations, category, icon, icon_color, status, path) VALUES (:module_id, :name, :name_translations, :category, :icon, :icon_color, :status, :path)";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':module_id', $moduleId ?: null, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':name_translations', $nameTranslations);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':icon', $icon);
        $stmt->bindValue(':icon_color', $iconColor);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':path', $path);
        if ($id > 0) {
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {
            echo json_encode(['message' => 'บันทึกสำเร็จ']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'บันทึกไม่สำเร็จ']);
        }
    }

    private function deleteService()
    {
        if (!$this->conn) {
            http_response_code(500);
            echo json_encode(['message' => 'Database connection failed']);
            return;
        }
        if (!$this->requireAuth() || !$this->canManageAnyModule()) {
            http_response_code(403);
            echo json_encode(['message' => 'ไม่มีสิทธิลบบริการ']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['message' => 'ไม่พบบริการที่จะลบ']);
            return;
        }

        $stmt = $this->conn->prepare("DELETE FROM hr_services WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['message' => 'ลบสำเร็จ']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'ลบไม่สำเร็จ']);
        }
    }
}
