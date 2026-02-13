<?php
// Modules/HRServices/Models/HRServicesModel.php

require_once __DIR__ . '/../../../core/Database/Database.php';

class HRServicesModel
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Check if a role can manage any module
     */
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

    /**
     * Check if a role is active
     */
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

    /**
     * List services with optional status filter
     */
    public function listServices($statusFilter = null)
    {
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get single service by ID
     */
    public function getById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM hr_services WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new service
     */
    public function create($data)
    {
        $sql = "INSERT INTO hr_services (module_id, name, name_translations, category, icon, icon_color, status, path) VALUES (:module_id, :name, :name_translations, :category, :icon, :icon_color, :status, :path)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':module_id', $data['module_id'] ?: null, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':name_translations', $data['name_translations']);
        $stmt->bindValue(':category', $data['category']);
        $stmt->bindValue(':icon', $data['icon']);
        $stmt->bindValue(':icon_color', $data['icon_color']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':path', $data['path']);
        return $stmt->execute();
    }

    /**
     * Update an existing service
     */
    public function update($id, $data)
    {
        $sql = "UPDATE hr_services SET module_id = :module_id, name = :name, name_translations = :name_translations, category = :category, icon = :icon, icon_color = :icon_color, status = :status, path = :path WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':module_id', $data['module_id'] ?: null, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':name_translations', $data['name_translations']);
        $stmt->bindValue(':category', $data['category']);
        $stmt->bindValue(':icon', $data['icon']);
        $stmt->bindValue(':icon_color', $data['icon_color']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':path', $data['path']);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete a service
     */
    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM hr_services WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
