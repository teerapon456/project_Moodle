<?php

require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../Models/HRServicesModel.php';

class ModuleController
{
    private $model;
    private $currentRoleId;
    private $currentRoleActive;

    public function __construct()
    {
        $this->model = new HRServicesModel();
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
        return $this->model->canManageAnyModule($this->currentRoleId);
    }

    private function ensureRoleActive()
    {
        if ($this->currentRoleActive === 0) {
            return false;
        }
        if ($this->currentRoleActive === 1) {
            return true;
        }

        $active = $this->model->getRoleActiveStatus($this->currentRoleId);
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
        $statusFilter = $_GET['status'] ?? null;
        $modules = $this->model->listServices($statusFilter);
        echo json_encode($modules);
    }

    private function saveService()
    {
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
            $existing = $this->model->getById($id);

            if ($existing) {
                $current = array_merge($current, $existing);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Service not found']);
                return;
            }
        }

        require_once __DIR__ . '/../../../core/Security/InputSanitizer.php';

        // Resolve params: Use $data value if set, otherwise fallback to $current (existing or default)
        $moduleId = array_key_exists('module_id', $data) ? (int)$data['module_id'] : $current['module_id'];
        $name = array_key_exists('name', $data) ? InputSanitizer::sanitize($data['name']) : $current['name'];
        $category = array_key_exists('category', $data) ? InputSanitizer::sanitize($data['category']) : $current['category'];
        $icon = array_key_exists('icon', $data) ? InputSanitizer::sanitize($data['icon']) : $current['icon'];
        $iconColor = array_key_exists('icon_color', $data) ? InputSanitizer::sanitize($data['icon_color']) : $current['icon_color'];
        $status = array_key_exists('status', $data) ? InputSanitizer::sanitize($data['status']) : $current['status'];
        $path = array_key_exists('path', $data) ? InputSanitizer::sanitize($data['path']) : $current['path'];

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

        // Validation
        if ($name === '' || $category === '') {
            http_response_code(400);
            echo json_encode(['message' => 'กรุณากรอกชื่อและหมวด']);
            return;
        }
        if (!in_array($status, ['ready', 'soon', 'maintenance'])) {
            $status = 'ready';
        }

        $serviceData = [
            'module_id' => $moduleId,
            'name' => $name,
            'name_translations' => $nameTranslations,
            'category' => $category,
            'icon' => $icon,
            'icon_color' => $iconColor,
            'status' => $status,
            'path' => $path
        ];

        if ($id > 0) {
            // For Insert, if nameTranslations was null, create default
            if (empty($nameTranslations)) {
                $serviceData['name_translations'] = json_encode(['en' => $name, 'th' => $name], JSON_UNESCAPED_UNICODE);
            }
            $success = $this->model->update($id, $serviceData);
        } else {
            if (empty($nameTranslations)) {
                $serviceData['name_translations'] = json_encode(['en' => $name, 'th' => $name], JSON_UNESCAPED_UNICODE);
            }
            $success = $this->model->create($serviceData);
        }

        if ($success) {
            echo json_encode(['message' => 'บันทึกสำเร็จ']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'บันทึกไม่สำเร็จ']);
        }
    }

    private function deleteService()
    {
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

        if ($this->model->delete($id)) {
            echo json_encode(['message' => 'ลบสำเร็จ']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'ลบไม่สำเร็จ']);
        }
    }
}
