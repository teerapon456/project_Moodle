<?php

class ApplicantModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail($email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM iga_applicants WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM iga_applicants WHERE applicant_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new applicant (is_active = 0 by default, pending email verification)
     */
    public function create($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO iga_applicants (email, password_hash, fullname, phone, level_id, is_active, created_at, updated_at)
            VALUES (:email, :password_hash, :fullname, :phone, :level_id, :is_active, NOW(), NOW())
        ");

        $success = $stmt->execute([
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':fullname' => $data['fullname'],
            ':phone' => $data['phone'] ?? null,
            ':level_id' => $data['level_id'] ?? null,
            ':is_active' => $data['is_active'] ?? 0  // Default: inactive until email verified
        ]);

        if ($success) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function verifyPassword($email, $password)
    {
        $applicant = $this->findByEmail($email);
        if ($applicant && password_verify($password, $applicant['password_hash'])) {
            return $applicant;
        }
        return false;
    }

    // ─── Email Verification Methods ─────────────────────────────

    /**
     * Generate and store a verification token for an applicant
     * Token expires in 1 hour
     * @return string The raw token (to be sent via email)
     */
    public function createVerificationToken($applicantId)
    {
        // Delete any existing unused tokens for this applicant
        $del = $this->pdo->prepare("DELETE FROM iga_email_verification_tokens WHERE applicant_id = :id AND is_used = 0");
        $del->execute([':id' => $applicantId]);

        $token = bin2hex(random_bytes(32)); // 64-char hex token
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->pdo->prepare("
            INSERT INTO iga_email_verification_tokens (applicant_id, token, expires_at)
            VALUES (:id, :token, :expires)
        ");
        $stmt->execute([
            ':id' => $applicantId,
            ':token' => $token,
            ':expires' => $expiresAt,
        ]);

        return $token;
    }

    /**
     * Verify a token and activate the applicant
     * @return array ['success' => bool, 'message' => string, 'applicant_id' => int|null]
     */
    public function verifyToken($token)
    {
        if (empty($token)) {
            return ['success' => false, 'message' => 'ลิงก์ไม่ถูกต้อง', 'applicant_id' => null];
        }

        $stmt = $this->pdo->prepare("
            SELECT t.*, a.is_active 
            FROM iga_email_verification_tokens t
            JOIN iga_applicants a ON t.applicant_id = a.applicant_id
            WHERE t.token = :token
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['success' => false, 'message' => 'โทเคนไม่ถูกต้องหรือไม่มีอยู่ในระบบ', 'applicant_id' => null];
        }

        // Already used
        if ((int)$row['is_used'] === 1) {
            return ['success' => false, 'message' => 'ลิงก์นี้ถูกใช้แล้ว กรุณาเข้าสู่ระบบ', 'applicant_id' => $row['applicant_id']];
        }

        // Expired
        if (strtotime($row['expires_at']) < time()) {
            return ['success' => false, 'message' => 'ลิงก์หมดอายุแล้ว กรุณาสมัครใหม่อีกครั้ง', 'applicant_id' => $row['applicant_id']];
        }

        // Already active
        if ((int)$row['is_active'] === 1) {
            $this->markTokenUsed($token);
            return ['success' => true, 'message' => 'บัญชีของคุณเปิดใช้งานอยู่แล้ว กรุณาเข้าสู่ระบบ', 'applicant_id' => $row['applicant_id']];
        }

        // Activate the applicant
        $this->activate($row['applicant_id']);
        $this->markTokenUsed($token);

        return ['success' => true, 'message' => 'ยืนยันอีเมลสำเร็จ! บัญชีของคุณพร้อมใช้งานแล้ว', 'applicant_id' => $row['applicant_id']];
    }

    /**
     * Activate an applicant account
     */
    public function activate($applicantId)
    {
        $stmt = $this->pdo->prepare("UPDATE iga_applicants SET is_active = 1, updated_at = NOW() WHERE applicant_id = :id");
        return $stmt->execute([':id' => $applicantId]);
    }

    /**
     * Mark a token as used
     */
    private function markTokenUsed($token)
    {
        $stmt = $this->pdo->prepare("UPDATE iga_email_verification_tokens SET is_used = 1 WHERE token = :token");
        return $stmt->execute([':token' => $token]);
    }
}
