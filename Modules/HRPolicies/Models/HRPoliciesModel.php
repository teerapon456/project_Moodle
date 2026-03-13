<?php
// Modules/HRPolicies/Models/HRPoliciesModel.php

class HRPoliciesModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAllPolicies()
    {
        $stmt = $this->conn->prepare("
            SELECT id, title, content, category, is_active, created_at, updated_at 
            FROM hr_policies 
            ORDER BY is_active DESC, updated_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPolicyById($id)
    {
        $stmt = $this->conn->prepare("
            SELECT id, title, content, category, is_active, created_at, updated_at 
            FROM hr_policies 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createPolicy($title, $category, $content, $isActive, $createdBy)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO hr_policies (title, category, content, is_active, created_by, created_at, updated_at) 
            VALUES (:title, :category, :content, :is_active, :created_by, NOW(), NOW())
        ");
        return $stmt->execute([
            ':title' => $title,
            ':category' => $category,
            ':content' => $content,
            ':is_active' => $isActive,
            ':created_by' => $createdBy
        ]);
    }

    public function updatePolicy($id, $title, $category, $content, $isActive, $updatedBy)
    {
        // 1. Snapshot the current policy into history table
        $stmtHistory = $this->conn->prepare("
            INSERT INTO hr_policies_history (policy_id, title, category, content, is_active, updated_by)
            SELECT id, title, category, content, is_active, :updated_by FROM hr_policies WHERE id = :id
        ");
        $stmtHistory->execute([':updated_by' => $updatedBy, ':id' => $id]);

        // 2. Update the main table
        $stmt = $this->conn->prepare("
            UPDATE hr_policies 
            SET title = :title, 
                category = :category, 
                content = :content, 
                is_active = :is_active, 
                updated_at = NOW() 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':title' => $title,
            ':category' => $category,
            ':content' => $content,
            ':is_active' => $isActive,
            ':id' => $id
        ]);
    }

    public function getPolicyHistory($id)
    {
        $stmt = $this->conn->prepare("
            SELECT h.*, u.fullname, u.username
            FROM hr_policies_history h
            LEFT JOIN users u ON h.updated_by = u.id
            WHERE h.policy_id = :id 
            ORDER BY h.updated_at DESC
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deletePolicy($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM hr_policies WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
