<?php

/**
 * Dormitory Module - Building Controller
 * จัดการอาคาร
 */

require_once __DIR__ . '/BaseController.php';

class BuildingController extends DormBaseController
{
    /**
     * รายการอาคารทั้งหมด
     */
    public function list()
    {
        $this->requireAuth();
        $stmt = $this->pdo->query("

            SELECT b.*, 
                   COUNT(r.id) as total_rooms,
                   SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                   SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms
            FROM dorm_buildings b
            LEFT JOIN dorm_rooms r ON b.id = r.building_id
            WHERE b.status = 'active'
            GROUP BY b.id
            ORDER BY b.code
        ");

        return $this->success(['buildings' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /**
     * ดูรายละเอียดอาคาร
     */
    public function get($data)
    {
        $this->requireAuth();
        $id = $data['id'] ?? $_GET['id'] ?? null;

        if (!$id) {
            return $this->error('กรุณาระบุ ID อาคาร');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM dorm_buildings WHERE id = ?");
        $stmt->execute([$id]);
        $building = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$building) {
            return $this->error('ไม่พบอาคาร', 404);
        }

        // ดึงห้องพักในอาคาร
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   o.employee_name as occupant_name,
                   o.employee_id as occupant_employee_id
            FROM dorm_rooms r
            LEFT JOIN dorm_occupancies o ON r.id = o.room_id AND o.status = 'active'
            WHERE r.building_id = ?
            ORDER BY r.floor, r.room_number
        ");
        $stmt->execute([$id]);
        $building['rooms'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['building' => $building]);
    }

    /**
     * เพิ่มอาคารใหม่
     */
    public function create($data)
    {
        $this->requirePermission('manage');

        $required = ['name', 'code'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error("กรุณากรอก $field");
            }
        }

        // ตรวจสอบ code ซ้ำ
        $stmt = $this->pdo->prepare("SELECT id FROM dorm_buildings WHERE code = ?");
        $stmt->execute([$data['code']]);
        if ($stmt->fetch()) {
            return $this->error('รหัสอาคารนี้มีอยู่แล้ว');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO dorm_buildings (name, code, description, address, total_floors)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['code'],
            $data['description'] ?? null,
            $data['address'] ?? null,
            $data['total_floors'] ?? 1
        ]);

        $id = $this->pdo->lastInsertId();
        $this->logAudit('create_building', 'building', $id, null, $data);

        return $this->success(['id' => $id], 'เพิ่มอาคารสำเร็จ');
    }

    /**
     * แก้ไขอาคาร
     */
    public function update($data)
    {
        $this->requirePermission('manage');

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->error('กรุณาระบุ ID อาคาร');
        }

        // ดึงข้อมูลเดิม
        $stmt = $this->pdo->prepare("SELECT * FROM dorm_buildings WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old) {
            return $this->error('ไม่พบอาคาร', 404);
        }

        $stmt = $this->pdo->prepare("
            UPDATE dorm_buildings 
            SET name = ?, description = ?, address = ?, total_floors = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['name'] ?? $old['name'],
            $data['description'] ?? $old['description'],
            $data['address'] ?? $old['address'],
            $data['total_floors'] ?? $old['total_floors'],
            $data['status'] ?? $old['status'],
            $id
        ]);

        $this->logAudit('update_building', 'building', $id, $old, $data);

        return $this->success([], 'แก้ไขอาคารสำเร็จ');
    }

    /**
     * ลบอาคาร (Soft delete)
     */
    public function delete($data)
    {
        $this->requirePermission('manage');

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            return $this->error('กรุณาระบุ ID อาคาร');
        }

        // ตรวจสอบว่ามีห้องที่ถูกใช้งานอยู่หรือไม่
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM dorm_rooms r
            JOIN dorm_occupancies o ON r.id = o.room_id AND o.status = 'active'
            WHERE r.building_id = ?
        ");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return $this->error('ไม่สามารถลบอาคารได้ เนื่องจากมีผู้พักอาศัยอยู่');
        }

        $stmt = $this->pdo->prepare("UPDATE dorm_buildings SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAudit('delete_building', 'building', $id);

        return $this->success([], 'ลบอาคารสำเร็จ');
    }
}
