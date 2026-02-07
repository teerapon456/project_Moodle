<?php

require_once __DIR__ . '/../../../core/BaseController.php';
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../Models/PermissionModel.php';

class PermissionController extends BaseController
{
    private $conn;
    private $model;
    private $currentRoleId;
    private $currentRoleActive;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->model = new PermissionModel($this->conn);

        $this->currentRoleId = $_SESSION['user']['role_id'] ?? null;
        $this->currentRoleActive = $_SESSION['user']['role_active'] ?? 1;
    }

    private function requireAuth()
    {
        if (!$this->currentRoleId) {
            http_response_code(401);
            throw new Exception('Not authenticated');
        }
        if (!$this->ensureRoleActive()) {
            http_response_code(403);
            throw new Exception('Role นี้ถูกปิดใช้งาน');
        }
        return true;
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

    // LIST ACTIONS (GET)

    public function roles()
    {
        return $this->model->getRoles();
    }

    public function users()
    {
        return $this->model->getUsers();
    }

    public function permissions()
    {
        $roleId = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;
        if ($roleId <= 0) {
            throw new Exception('role_id required');
        }

        return $this->model->getPermissions($roleId);
    }

    public function core_modules()
    {
        return $this->model->getCoreModules();
    }

    public function check_permission()
    {
        $this->requireAuth();

        $code = trim($_GET['code'] ?? '');
        $moduleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;

        if ($code === '' && $moduleId <= 0) {
            throw new Exception('code or module_id required');
        }

        $row = $this->model->checkModulePermission($this->currentRoleId, $code, $moduleId);

        if (!$row) {
            http_response_code(404);
            throw new Exception('Module not found');
        }

        if (empty($row['can_view'])) {
            http_response_code(403);
            throw new Exception('ไม่มีสิทธิ์เข้าถึงโมดูลนี้');
        }

        return $row;
    }

    // SAVE ACTIONS (POST)

    public function save_role($data = [])
    {
        $this->requireAuth();
        if (!$this->model->canManageAnyModule($this->currentRoleId)) {
            http_response_code(403);
            throw new Exception('ไม่มีสิทธิจัดการ Role');
        }

        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if ($name === '') {
            throw new Exception('กรุณากรอกชื่อ Role');
        }

        if ($this->model->saveRole($id, $name, $description, $isActive)) {
            return ['message' => 'บันทึกสำเร็จ'];
        } else {
            throw new Exception('บันทึกไม่สำเร็จ');
        }
    }

    public function save_permission($data = [])
    {
        $this->requireAuth();

        $roleId = intval($data['role_id'] ?? 0);
        $moduleId = intval($data['module_id'] ?? 0);
        $canView = !empty($data['can_view']) ? 1 : 0;
        $canEdit = !empty($data['can_edit']) ? 1 : 0;
        $canDelete = !empty($data['can_delete']) ? 1 : 0;
        $canManage = !empty($data['can_manage']) ? 1 : 0;

        if ($roleId <= 0 || $moduleId <= 0) {
            throw new Exception('role_id and module_id required');
        }

        // Check permission to manage
        if (
            !$this->model->canManageModule($this->currentRoleId, $moduleId) &&
            !$this->model->canManageAnyModule($this->currentRoleId)
        ) {
            http_response_code(403);
            throw new Exception('ไม่มีสิทธิ์แก้ไขสิทธิ์โมดูลนี้');
        }

        if ($this->model->savePermission($roleId, $moduleId, $canView, $canEdit, $canDelete, $canManage)) {
            return ['message' => 'Saved'];
        } else {
            throw new Exception('Failed to save');
        }
    }

    public function assign_role($data = [])
    {
        $this->requireAuth();

        $roleId = intval($data['role_id'] ?? 0);
        $userId = intval($data['user_id'] ?? 0);

        if ($roleId <= 0 || $userId <= 0) {
            throw new Exception('role_id and user_id required');
        }

        if (!$this->model->canManageAnyModule($this->currentRoleId)) {
            http_response_code(403);
            throw new Exception('ไม่มีสิทธิกำหนด Role ให้ผู้ใช้');
        }

        if ($this->model->updateUserRole($userId, $roleId)) {
            return ['message' => 'Role updated'];
        } else {
            throw new Exception('Failed to update role');
        }
    }

    public function save_user($data = [])
    {
        $this->requireAuth();
        if (!$this->model->canManageAnyModule($this->currentRoleId)) {
            http_response_code(403);
            throw new Exception('ไม่มีสิทธิ์แก้ไขผู้ใช้');
        }

        $userId = intval($data['user_id'] ?? 0);
        $roleId = isset($data['role_id']) ? intval($data['role_id']) : null;
        $userActive = isset($data['user_active']) ? (int)$data['user_active'] : null;

        if ($userId <= 0) {
            throw new Exception('user_id required');
        }

        if ($this->model->updateUser($userId, $roleId, $userActive)) {
            return ['message' => 'User updated'];
        } else {
            throw new Exception('Failed to update user');
        }
    }

    public function create_user($data = [])
    {
        $this->requireAuth();
        if (!$this->model->canManageAnyModule($this->currentRoleId)) {
            http_response_code(403);
            throw new Exception('ไม่มีสิทธิ์สร้างผู้ใช้');
        }

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $fullname = trim($data['fullname'] ?? '');
        $password = $data['password'] ?? '';
        $roleId = intval($data['role_id'] ?? 0);

        if (empty($username) || empty($email) || empty($password) || $roleId <= 0) {
            throw new Exception('กรุณากรอก username, email, password และ role');
        }

        if ($this->model->checkUserExists($username, $email)) {
            throw new Exception('Username หรือ Email นี้มีอยู่แล้ว');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($newId = $this->model->createUser($username, $email, $fullname, $hashedPassword, $roleId)) {
            return ['message' => 'สร้างผู้ใช้สำเร็จ', 'id' => $newId];
        } else {
            throw new Exception('สร้างผู้ใช้ไม่สำเร็จ');
        }
    }
}
