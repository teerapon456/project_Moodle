<?php

class PermissionModel
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    public function canManageModule($roleId, $moduleId)
    {
        if (!$this->conn || !$roleId) {
            return false;
        }

        $sql = "SELECT can_manage FROM core_module_permissions WHERE module_id = :module_id AND role_id = :role_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':module_id', $moduleId, PDO::PARAM_INT);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    public function canManageAnyModule($roleId)
    {
        if (!$this->conn || !$roleId) {
            return false;
        }

        $sql = "SELECT 1 FROM core_module_permissions WHERE role_id = :role_id AND can_manage = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    public function getRoleActiveStatus($roleId)
    {
        if (!$this->conn || !$roleId) {
            return 0;
        }
        $sql = "SELECT is_active FROM roles WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getRoles()
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $sql = "SELECT id, name, description, is_active FROM roles ORDER BY id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsers()
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $sql = "SELECT u.id, u.username, u.email, u.role_id, u.is_active AS user_active,
                       r.name AS role, r.is_active AS role_active
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                ORDER BY u.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPermissions($roleId)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $sql = "
            SELECT m.id as module_id, m.code, m.name,
                   COALESCE(p.can_view, 0) as can_view,
                   COALESCE(p.can_edit, 0) as can_edit,
                   COALESCE(p.can_delete, 0) as can_delete,
                   COALESCE(p.can_manage, 0) as can_manage
            FROM core_modules m
            LEFT JOIN core_module_permissions p
              ON p.module_id = m.id AND p.role_id = :role_id
            ORDER BY m.id ASC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCoreModules()
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $sql = "
            SELECT m.id, m.code, m.name, m.description, m.path, m.is_active,
                   MAX(s.icon) as icon, 
                   MAX(s.icon_color) as icon_color, 
                   MAX(s.custom_icon_path) as custom_icon_path
            FROM core_modules m
            LEFT JOIN hr_services s ON m.id = s.module_id
            GROUP BY m.id
            ORDER BY m.id ASC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkModulePermission($roleId, $code, $moduleId)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $sql = "
            SELECT m.id, m.code, m.name,
                   COALESCE(p.can_view, 0) as can_view,
                   COALESCE(p.can_edit, 0) as can_edit,
                   COALESCE(p.can_delete, 0) as can_delete,
                   COALESCE(p.can_manage, 0) as can_manage
            FROM core_modules m
            LEFT JOIN core_module_permissions p
              ON p.module_id = m.id AND p.role_id = :role_id
            WHERE (:code <> '' AND m.code = :code) OR (:module_id > 0 AND m.id = :module_id)
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':code', $code);
        $stmt->bindValue(':module_id', $moduleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveRole($id, $name, $description, $isActive)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        if ($id > 0) {
            $sql = "UPDATE roles SET name = :name, description = :description, is_active = :is_active WHERE id = :id";
        } else {
            $sql = "INSERT INTO roles (name, description, is_active) VALUES (:name, :description, :is_active)";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':is_active', $isActive, PDO::PARAM_INT);
        if ($id > 0) {
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    public function savePermission($roleId, $moduleId, $canView, $canEdit, $canDelete, $canManage)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $sql = "
            INSERT INTO core_module_permissions (module_id, role_id, can_view, can_edit, can_delete, can_manage)
            VALUES (:module_id, :role_id, :can_view, :can_edit, :can_delete, :can_manage)
            ON DUPLICATE KEY UPDATE
                can_view = VALUES(can_view),
                can_edit = VALUES(can_edit),
                can_delete = VALUES(can_delete),
                can_manage = VALUES(can_manage),
                updated_at = CURRENT_TIMESTAMP
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':module_id', $moduleId, PDO::PARAM_INT);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':can_view', $canView, PDO::PARAM_INT);
        $stmt->bindValue(':can_edit', $canEdit, PDO::PARAM_INT);
        $stmt->bindValue(':can_delete', $canDelete, PDO::PARAM_INT);
        $stmt->bindValue(':can_manage', $canManage, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateUserRole($userId, $roleId)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $sql = "UPDATE users SET role_id = :role_id WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateUser($userId, $roleId = null, $userActive = null)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $fields = [];
        $params = [':id' => $userId];
        if ($roleId !== null) {
            $fields[] = 'role_id = :role_id';
            $params[':role_id'] = ($roleId > 0) ? $roleId : null;
        }
        if ($userActive !== null) {
            $fields[] = 'is_active = :is_active';
            $params[':is_active'] = $userActive ? 1 : 0;
        }

        if (empty($fields)) {
            throw new Exception('no fields to update');
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        return $stmt->execute();
    }

    public function checkUserExists($username, $email)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        return (bool)$stmt->fetch();
    }

    public function createUser($username, $email, $fullname, $hashedPassword, $roleId)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $stmt = $this->conn->prepare("
            INSERT INTO users (username, email, fullname, password_hash, role_id, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");

        if ($stmt->execute([$username, $email, $fullname, $hashedPassword, $roleId])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getRoleModulePermissions($roleId)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $sql = "SELECT cm.id, cm.code, cm.path, cm.name, cm.is_active, 
                       COALESCE(p.can_view, 0) as can_view, 
                       COALESCE(p.can_edit, 0) as can_edit, 
                       COALESCE(p.can_delete, 0) as can_delete, 
                       COALESCE(p.can_manage, 0) as can_manage 
                FROM core_modules cm 
                LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = :role_id 
                WHERE cm.is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPermissionManagementModuleCode()
    {
        if (!$this->conn) return 'PERMISSION_MANAGEMENT';

        try {
            $sql = "SELECT code FROM core_modules WHERE path LIKE '%modules/manage.php%' OR name LIKE '%permission%' OR code LIKE 'PERMISSION%' ORDER BY id ASC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $code = $stmt->fetchColumn();
            return $code ?: 'PERMISSION_MANAGEMENT';
        } catch (Exception $e) {
            return 'PERMISSION_MANAGEMENT';
        }
    }

    public function getSystemSettings($moduleId = 0)
    {
        if (!$this->conn) throw new Exception('Database connection failed');

        $sql = "SELECT id, module_id, setting_key, setting_value, created_at, updated_at FROM system_settings WHERE module_id = :module_id OR :module_id = 0 ORDER BY id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':module_id', $moduleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveSystemSetting($key, $value, $moduleId = 0)
    {
        if (!$this->conn) throw new Exception('Database connection failed');
        $moduleId = (int)$moduleId;

        // Fetch old value for audit logging
        $oldValue = null;
        try {
            $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key AND module_id = :mid LIMIT 1");
            $stmt->bindValue(':key', $key);
            $stmt->bindValue(':mid', $moduleId, PDO::PARAM_INT);
            $stmt->execute();
            $oldValue = $stmt->fetchColumn();
        } catch (Exception $e) { /* Ignore */
        }

        $sql = "INSERT INTO system_settings (module_id, setting_key, setting_value) 
                VALUES (:module_id, :setting_key, :setting_value)
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_at = CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':module_id', $moduleId, PDO::PARAM_INT);
        $stmt->bindValue(':setting_key', $key);
        $stmt->bindValue(':setting_value', $value);
        $result = $stmt->execute();

        if ($result && (string)$oldValue !== (string)$value) {
            // Log to audit_log
            try {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $user = $_SESSION['user']['username'] ?? $_SESSION['user']['fullname'] ?? 'system';
                $logSql = "INSERT INTO audit_log (table_name, record_id, column_name, old_value, new_value, action_type, performed_by) 
                          VALUES ('system_settings', :mid, :column, :old, :new, 'UPDATE', :user)";
                $logStmt = $this->conn->prepare($logSql);
                $logStmt->bindValue(':mid', $moduleId, PDO::PARAM_INT);
                $logStmt->bindValue(':column', $key);
                $logStmt->bindValue(':old', (string)$oldValue);
                $logStmt->bindValue(':new', (string)$value);
                $logStmt->bindValue(':user', $user);
                $logStmt->execute();
            } catch (Exception $e) { /* Ignore logging errors */
            }
        }

        return $result;
    }

    public function getAuditLogs($limit = 50, $page = 1)
    {
        $limit = (int)$limit;
        $offset = ((int)$page - 1) * $limit;

        // Get total count
        $countStmt = $this->conn->query("SELECT COUNT(*) FROM audit_log WHERE table_name = 'system_settings'");
        $total = (int)$countStmt->fetchColumn();

        // Get logs
        $stmt = $this->conn->prepare("
            SELECT * FROM audit_log 
            WHERE table_name = 'system_settings' 
            ORDER BY performed_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit)
        ];
    }
}
