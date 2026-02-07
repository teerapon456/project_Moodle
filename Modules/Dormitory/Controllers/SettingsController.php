<?php

/**
 * Dormitory Module - Settings Controller
 * จัดการการตั้งค่าระบบหอพัก
 */

require_once __DIR__ . '/BaseController.php';

class SettingsController extends DormBaseController
{
    /**
     * ดึงการตั้งค่าทั้งหมด
     */
    public function getSettings()
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE module_id = ?");
        $stmt->execute([20]); // Module ID 20 for Dormitory
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $this->success(['settings' => $settings]);
    }

    /**
     * ดึงการตั้งค่าตามคีย์
     */
    public function getSetting($data)
    {
        $key = $data['key'] ?? null;
        if (!$key) {
            return $this->error('กรุณาระบุ setting key');
        }

        $stmt = $this->pdo->prepare("SELECT setting_value FROM system_settings WHERE module_id = ? AND setting_key = ?");
        $stmt->execute([20, $key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->success(['value' => $result['setting_value'] ?? null]);
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function saveSetting($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $key = $data['key'] ?? null;
        $value = $data['value'] ?? '';

        if (!$key) {
            return $this->error('กรุณาระบุ setting key');
        }

        // Upsert
        $stmt = $this->pdo->prepare("
            INSERT INTO system_settings (module_id, setting_key, setting_value, updated_at) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        $stmt->execute([20, $key, $value]);

        return $this->success(['message' => 'บันทึกการตั้งค่าสำเร็จ']);
    }

    /**
     * บันทึกการตั้งค่าหลายรายการ
     */
    public function saveSettings($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $settings = $data['settings'] ?? [];
        if (empty($settings)) {
            return $this->error('ไม่มีการตั้งค่าที่จะบันทึก');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO system_settings (module_id, setting_key, setting_value, updated_at) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");

        foreach ($settings as $key => $value) {
            $stmt->execute([20, $key, $value]);
        }

        return $this->success(['message' => 'บันทึกการตั้งค่าสำเร็จ']);
    }

    /**
     * ทดสอบส่งอีเมล
     */
    public function testEmail($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $email = $data['email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('รูปแบบอีเมลไม่ถูกต้อง');
        }

        // Use the core EmailService which uses PHPMailer with SMTP
        require_once __DIR__ . '/../../../core/Services/EmailService.php';

        $subject = '[ทดสอบ] ระบบแจ้งเตือนหอพัก';
        $body = '
            <h2 style="color:#A21D21;">ทดสอบระบบแจ้งเตือน</h2>
            <p>นี่คืออีเมลทดสอบจากระบบหอพัก (Dormitory Module)</p>
            <p>หากคุณได้รับอีเมลนี้ แสดงว่าการตั้งค่าอีเมลทำงานได้ถูกต้อง</p>
            <p style="color:#6b7280; font-size:12px;">กรุณาเพิกเฉยหากได้รับอีเมลนี้โดยไม่ได้ตั้งใจ</p>
        ';

        $result = EmailService::sendTestEmail($email, $subject, $body);

        if ($result) {
            return $this->success(['message' => 'ส่งอีเมลทดสอบสำเร็จ']);
        } else {
            return $this->error('ไม่สามารถส่งอีเมลได้ กรุณาตรวจสอบการตั้งค่า SMTP ใน .env');
        }
    }

    // ===================== ROOM TYPES MANAGEMENT =====================

    /**
     * ดึงประเภทห้องทั้งหมด
     */
    public function getRoomTypes()
    {
        $stmt = $this->pdo->query("SELECT * FROM dorm_room_types ORDER BY id ASC");
        return $this->success(['room_types' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /**
     * สร้างประเภทห้องใหม่
     */
    public function createRoomType($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $name = trim($data['name'] ?? '');
        $code = trim($data['code'] ?? '');
        $capacity = (int)($data['capacity'] ?? 1);
        $monthlyRent = (float)($data['monthly_rent'] ?? 0);
        $description = trim($data['description'] ?? '');

        if (empty($name)) {
            return $this->error('กรุณาระบุชื่อประเภทห้อง');
        }

        if (empty($code)) {
            // Auto-generate code from name
            $code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name));
        }

        // Check for duplicate code
        $stmt = $this->pdo->prepare("SELECT id FROM dorm_room_types WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            return $this->error('รหัสประเภทห้องนี้มีอยู่แล้ว');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO dorm_room_types (name, code, capacity, monthly_rent, description, status)
            VALUES (?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$name, $code, $capacity, $monthlyRent, $description]);

        return $this->success(['id' => $this->pdo->lastInsertId()], 'สร้างประเภทห้องสำเร็จ');
    }

    /**
     * อัพเดทประเภทห้อง
     */
    public function updateRoomType($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            return $this->error('ไม่พบ ID ประเภทห้อง');
        }

        $name = trim($data['name'] ?? '');
        $code = trim($data['code'] ?? '');
        $capacity = (int)($data['capacity'] ?? 1);
        $monthlyRent = (float)($data['monthly_rent'] ?? 0);
        $description = trim($data['description'] ?? '');
        $status = $data['status'] ?? 'active';

        if (empty($name)) {
            return $this->error('กรุณาระบุชื่อประเภทห้อง');
        }

        // Check for duplicate code (excluding self)
        $stmt = $this->pdo->prepare("SELECT id FROM dorm_room_types WHERE code = ? AND id != ?");
        $stmt->execute([$code, $id]);
        if ($stmt->fetch()) {
            return $this->error('รหัสประเภทห้องนี้มีอยู่แล้ว');
        }

        $stmt = $this->pdo->prepare("
            UPDATE dorm_room_types 
            SET name = ?, code = ?, capacity = ?, monthly_rent = ?, description = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $code, $capacity, $monthlyRent, $description, $status, $id]);

        return $this->success([], 'อัพเดทประเภทห้องสำเร็จ');
    }

    /**
     * ลบประเภทห้อง (soft delete - set status to inactive)
     */
    public function deleteRoomType($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            return $this->error('ไม่พบ ID ประเภทห้อง');
        }

        // Check if any rooms are using this type
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM dorm_rooms WHERE room_type = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Soft delete if in use
            $stmt = $this->pdo->prepare("UPDATE dorm_room_types SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            return $this->success([], 'ปิดใช้งานประเภทห้องแล้ว (มีห้องใช้งานอยู่ ' . $count . ' ห้อง)');
        } else {
            // Hard delete if not in use
            $stmt = $this->pdo->prepare("DELETE FROM dorm_room_types WHERE id = ?");
            $stmt->execute([$id]);
            return $this->success([], 'ลบประเภทห้องสำเร็จ');
        }
    }
}
