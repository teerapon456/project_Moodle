<?php

namespace IGA\Models;

class Applicant
{
    private $conn;
    private $table = 'iga_applicant';

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Find applicant by email
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find applicant by ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE applicant_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return (bool)$stmt->fetch();
    }

    /**
     * Create new applicant
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (email, password_hash, fullname, phone, identity_card, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data['email'],
            $data['password_hash'],
            $data['fullname'],
            $data['phone'] ?? null,
            $data['identity_card'] ?? null
        ]);

        return (int)$this->conn->lastInsertId();
    }

    /**
     * Update applicant
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE applicant_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $email, string $password): ?array
    {
        $applicant = $this->findByEmail($email);

        if (!$applicant || !$applicant['is_active']) {
            return null;
        }

        if (password_verify($password, $applicant['password_hash'])) {
            unset($applicant['password_hash']); // Don't return password
            return $applicant;
        }

        return null;
    }

    /**
     * Get all active applicants (for admin)
     */
    public function getAllActive(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT applicant_id, email, fullname, phone, created_at 
                FROM {$this->table} 
                WHERE is_active = 1 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
