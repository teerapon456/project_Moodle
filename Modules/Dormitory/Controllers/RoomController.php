<?php

/**
 * Dormitory Module - Room Controller
 * จัดการห้องพัก
 */

require_once __DIR__ . '/BaseController.php';

class RoomController extends DormBaseController
{
    /**
     * ค้นหาพนักงาน
     */
    public function searchEmployee($data)
    {
        $this->requireAuth();
        $query = $data['query'] ?? '';

        if (strlen($query) < 2) {
            return $this->success(['employees' => []]);
        }

        // ค้นหาจากตาราง users
        $stmt = $this->pdo->prepare("
            SELECT username as code, fullname as name, email, department, id
            FROM users 
            WHERE (username LIKE ? OR fullname LIKE ? OR email LIKE ?) 
            AND is_active = 1
            LIMIT 10
        ");
        $term = "%$query%";
        $stmt->execute([$term, $term, $term]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['employees' => $employees]);
    }

    /**
     * รายการห้องพักทั้งหมด
     */
    public function list()
    {
        $this->requireAuth();
        $this->requirePermission('view');

        $buildingId = $_GET['building_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $floor = $_GET['floor'] ?? null;

        // 1. ดึงข้อมูลห้องพัก (ไม่ Join Occupancies เพื่อป้องกัน Row ซ้ำ)
        $sql = "
            SELECT r.*, 
                   b.name as building_name,
                   b.code as building_code
            FROM dorm_rooms r
            JOIN dorm_buildings b ON r.building_id = b.id
            WHERE 1=1
        ";
        $params = [];

        if ($buildingId) {
            $sql .= " AND r.building_id = ?";
            $params[] = $buildingId;
        }
        if ($status) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }
        if (isset($_GET['room_type']) && $_GET['room_type']) {
            $sql .= " AND r.room_type = ?";
            $params[] = $_GET['room_type'];
        }
        if ($floor) {
            $sql .= " AND r.floor = ?";
            $params[] = $floor;
        }

        $sql .= " ORDER BY b.code, r.floor, r.room_number";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. ดึงข้อมูลผู้พักอาศัยของห้องเหล่านี้
        if (!empty($rooms)) {
            $roomIds = array_column($rooms, 'id');
            // สร้าง placeholder (?,?,?) สำหรับ IN clause
            $placeholders = str_repeat('?,', count($roomIds) - 1) . '?';

            $sqlOcc = "
                SELECT * 
                FROM dorm_occupancies 
                WHERE room_id IN ($placeholders) 
                AND status = 'active'
            ";

            $stmtOcc = $this->pdo->prepare($sqlOcc);
            $stmtOcc->execute($roomIds);
            $occupants = $stmtOcc->fetchAll(PDO::FETCH_ASSOC);

            // Group occupants by room_id
            $occupantsByRoom = [];
            foreach ($occupants as $occ) {
                $occupantsByRoom[$occ['room_id']][] = $occ;
            }

            // Merge กลับเข้าไปใน rooms
            foreach ($rooms as &$room) {
                $room['occupants'] = $occupantsByRoom[$room['id']] ?? [];
            }
        }

        return $this->success(['rooms' => $rooms]);
    }

    /**
     * ดูรายละเอียดห้องพัก
     */
    public function get($data)
    {
        $this->requireAuth();
        $this->requirePermission('view');

        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            return $this->error('กรุณาระบุ ID ห้อง');
        }

        $stmt = $this->pdo->prepare("
            SELECT r.*, b.name as building_name, b.code as building_code
            FROM dorm_rooms r
            JOIN dorm_buildings b ON r.building_id = b.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            return $this->error('ไม่พบห้องพัก', 404);
        }

        // ดึงผู้พักอาศัยปัจจุบัน
        $stmt = $this->pdo->prepare("
            SELECT * FROM dorm_occupancies 
            WHERE room_id = ? AND status = 'active'
        ");
        $stmt->execute([$id]);
        $room['current_occupants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ดึงประวัติการพักอาศัย
        $stmt = $this->pdo->prepare("
            SELECT * FROM dorm_occupancies 
            WHERE room_id = ? 
            ORDER BY check_in_date DESC
            LIMIT 10
        ");
        $stmt->execute([$id]);
        $room['occupancy_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ดึงบิลล่าสุด
        $stmt = $this->pdo->prepare("
            SELECT * FROM dorm_invoices 
            WHERE room_id = ? 
            ORDER BY month_cycle DESC
            LIMIT 6
        ");
        $stmt->execute([$id]);
        $room['recent_invoices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['room' => $room]);
    }

    /**
     * เพิ่มห้องพักใหม่
     */
    public function create($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');


        $required = ['building_id', 'room_number'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error("กรุณากรอก $field");
            }
        }

        // ตรวจสอบห้องซ้ำ
        $stmt = $this->pdo->prepare("
            SELECT id FROM dorm_rooms WHERE building_id = ? AND room_number = ?
        ");
        $stmt->execute([$data['building_id'], $data['room_number']]);
        if ($stmt->fetch()) {
            return $this->error('หมายเลขห้องนี้มีอยู่แล้วในอาคาร');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO dorm_rooms (building_id, room_number, floor, room_type, capacity, monthly_rent, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['building_id'],
            $data['room_number'],
            $data['floor'] ?? 1,
            $data['room_type'] ?? 'single',
            $data['capacity'] ?? 1,
            $data['monthly_rent'] ?? 0,
            $data['description'] ?? null
        ]);

        $id = $this->pdo->lastInsertId();
        $this->logAudit('create_room', 'room', $id, null, $data);

        return $this->success(['id' => $id], 'เพิ่มห้องพักสำเร็จ');
    }

    /**
     * แก้ไขห้องพัก
     */
    public function update($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');


        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->error('กรุณาระบุ ID ห้อง');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM dorm_rooms WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old) {
            return $this->error('ไม่พบห้องพัก', 404);
        }

        $stmt = $this->pdo->prepare("
            UPDATE dorm_rooms 
            SET room_number = ?, floor = ?, room_type = ?, capacity = ?, 
                monthly_rent = ?, status = ?, description = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['room_number'] ?? $old['room_number'],
            $data['floor'] ?? $old['floor'],
            $data['room_type'] ?? $old['room_type'],
            $data['capacity'] ?? $old['capacity'],
            $data['monthly_rent'] ?? $old['monthly_rent'],
            $data['status'] ?? $old['status'],
            $data['description'] ?? $old['description'],
            $id
        ]);

        $this->logAudit('update_room', 'room', $id, $old, $data);

        return $this->success([], 'แก้ไขห้องพักสำเร็จ');
    }

    /**
     * ลบห้องพัก
     */
    public function delete($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');


        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            return $this->error('กรุณาระบุ ID ห้อง');
        }

        // ตรวจสอบว่ามีผู้พักอาศัยอยู่หรือไม่
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM dorm_occupancies WHERE room_id = ? AND status = 'active'
        ");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return $this->error('ไม่สามารถลบห้องได้ เนื่องจากมีผู้พักอาศัยอยู่');
        }

        $stmt = $this->pdo->prepare("DELETE FROM dorm_rooms WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAudit('delete_room', 'room', $id);

        return $this->success([], 'ลบห้องพักสำเร็จ');
    }

    /**
     * Check-in ผู้พักอาศัย
     */
    public function checkIn($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');


        $required = ['room_id', 'employee_id', 'employee_name', 'check_in_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error("กรุณากรอก $field");
            }
        }

        // ตรวจสอบห้องและ capacity
        $stmt = $this->pdo->prepare("SELECT status, capacity FROM dorm_rooms WHERE id = ?");
        $stmt->execute([$data['room_id']]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            return $this->error('ไม่พบห้องพัก', 404);
        }

        // นับจำนวนผู้เข้าพักปัจจุบัน (เฉพาะ active เท่านั้น)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM dorm_occupancies WHERE room_id = ? AND status = 'active'");
        $stmt->execute([$data['room_id']]);
        $currentOccupants = (int)$stmt->fetchColumn();

        if ($currentOccupants >= $room['capacity']) {
            return $this->error("ห้องพักเต็มแล้ว (ปัจจุบันมี {$currentOccupants} คน จาก Capacity {$room['capacity']})");
        }

        // ตรวจสอบว่าพนักงานพักอยู่ห้องอื่นหรือไม่ (เฉพาะพนักงานประจำ)
        if (strpos($data['employee_id'], 'TEMP_') === false) {
            $stmt = $this->pdo->prepare("
                SELECT o.employee_name, o.room_id, r.room_number, b.code as building_code
                FROM dorm_occupancies o
                JOIN dorm_rooms r ON o.room_id = r.id
                JOIN dorm_buildings b ON r.building_id = b.id
                WHERE o.employee_id = ? AND o.status = 'active'
            ");
            $stmt->execute([$data['employee_id']]);
            $existingOccupancy = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existingOccupancy) {
                $roomLabel = $existingOccupancy['building_code'] . $existingOccupancy['room_number'];
                return $this->error("พนักงาน \"{$existingOccupancy['employee_name']}\" พักอยู่ที่ห้อง {$roomLabel} แล้ว");
            }
        }

        // Use RoomService
        require_once __DIR__ . '/../Services/RoomService.php';
        $roomService = new RoomService();

        $this->pdo->beginTransaction();
        try {
            $occupancyId = $roomService->checkIn($data['room_id'], $data, $this->user['id']);

            $this->pdo->commit();
            $this->logAudit('check_in', 'occupancy', $occupancyId, null, $data);

            return $this->success(['id' => $occupancyId], 'Check-in สำเร็จ');
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Check-out ผู้พักอาศัย
     */
    public function checkOut($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');


        $occupancyIds = $data['occupancy_ids'] ?? [];
        if (isset($data['occupancy_id'])) {
            $occupancyIds[] = $data['occupancy_id'];
        }

        if (empty($occupancyIds)) {
            return $this->error('กรุณาระบุ ID การพักอาศัย');
        }

        $checkOutDate = $data['check_out_date'] ?? date('Y-m-d');
        $force = !empty($data['force']);

        require_once __DIR__ . '/../Services/RoomService.php';
        $roomService = new RoomService();

        $this->pdo->beginTransaction();
        try {
            foreach ($occupancyIds as $occupancyId) {
                $roomService->checkOut($occupancyId, $checkOutDate, $force);

                // Fetch info for Audit Log
                $this->logAudit('check_out', 'occupancy', $occupancyId, null, ['check_out_date' => $checkOutDate]);
            }

            $this->pdo->commit();
            return $this->success([], 'Check-out สำเร็จ');
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error($e->getMessage());
        }
    }

    /**
     * ลบผู้ติดตาม (ญาติ) จาก occupancy โดยไม่ต้อง checkout คนหลัก
     */
    public function removeRelative($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $occupancyId = $data['occupancy_id'] ?? null;
        $relativeIndex = $data['relative_index'] ?? null; // Index ใน array ของญาติ

        if (!$occupancyId || $relativeIndex === null) {
            return $this->error('กรุณาระบุ occupancy_id และ relative_index');
        }

        try {
            // ดึงข้อมูล occupancy
            $stmt = $this->pdo->prepare("SELECT * FROM dorm_occupancies WHERE id = ? AND status = 'active'");
            $stmt->execute([$occupancyId]);
            $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$occupancy) {
                return $this->error('ไม่พบข้อมูลการพักอาศัย');
            }

            // Parse ข้อมูลญาติ
            $relatives = [];
            if (!empty($occupancy['accompanying_details'])) {
                $relatives = json_decode($occupancy['accompanying_details'], true) ?: [];
            }

            if (!isset($relatives[$relativeIndex])) {
                return $this->error('ไม่พบข้อมูลผู้ติดตามที่ระบุ');
            }

            $removedRelative = $relatives[$relativeIndex];

            // ลบญาติออกจาก array
            array_splice($relatives, $relativeIndex, 1);

            // อัพเดท database
            $newCount = count($relatives);
            $newDetails = $newCount > 0 ? json_encode($relatives, JSON_UNESCAPED_UNICODE) : null;

            $stmt = $this->pdo->prepare("
                UPDATE dorm_occupancies 
                SET accompanying_persons = ?, accompanying_details = ?
                WHERE id = ?
            ");
            $stmt->execute([$newCount, $newDetails, $occupancyId]);

            // อัพเดทสถานะห้อง
            require_once __DIR__ . '/../Services/RoomService.php';
            $roomService = new RoomService();
            $roomService->updateRoomStatus($occupancy['room_id']);

            // Log
            $this->logAudit(
                'remove_relative',
                'occupancy',
                $occupancyId,
                ['relative' => $removedRelative],
                ['remaining_count' => $newCount]
            );

            return $this->success([], 'ลบผู้ติดตามสำเร็จ: ' . ($removedRelative['name'] ?? 'Unknown'));
        } catch (Exception $e) {
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * สรุปสถิติห้องพัก
     */
    public function stats()
    {
        $this->requireAuth();
        $this->requirePermission('view');

        $stats = [];

        // จำนวนห้องตาม status
        $stmt = $this->pdo->query("
            SELECT status, COUNT(*) as count 
            FROM dorm_rooms 
            GROUP BY status
        ");
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // จำนวนห้องตามอาคาร
        $stmt = $this->pdo->query("
            SELECT b.name, 
                   COUNT(r.id) as total,
                   SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available,
                   SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied
            FROM dorm_buildings b
            LEFT JOIN dorm_rooms r ON b.id = r.building_id
            WHERE b.status = 'active'
            GROUP BY b.id
        ");
        $stats['by_building'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // จำนวนผู้พักอาศัยปัจจุบัน
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM dorm_occupancies WHERE status = 'active'");
        $stats['total_occupants'] = $stmt->fetchColumn();

        return $this->success(['stats' => $stats]);
    }

    /**
     * ประวัติการเข้าพักทั้งหมด (สำหรับหน้า History)
     */
    /**
     * ประวัติการเข้าพักทั้งหมด (สำหรับหน้า History)
     */
    public function history($data = [])
    {
        $this->requireAuth();
        $this->requirePermission('view');


        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $buildingId = $_GET['building_id'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $conditions = ["1=1"];
        $params = [];

        if ($search) {
            $conditions[] = "(o.employee_name LIKE ? OR o.employee_id LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($buildingId) {
            $conditions[] = "r.building_id = ?";
            $params[] = $buildingId;
        }

        if ($startDate) {
            $conditions[] = "o.check_in_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $conditions[] = "o.check_in_date <= ?";
            $params[] = $endDate;
        }

        // Count total
        $sqlCount = "
            SELECT COUNT(*) 
            FROM dorm_occupancies o
            JOIN dorm_rooms r ON o.room_id = r.id
            WHERE " . implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare($sqlCount);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get Data
        $sql = "
            SELECT o.*, r.room_number, r.floor, r.room_type, b.name as building_name, b.code as building_code
            FROM dorm_occupancies o
            JOIN dorm_rooms r ON o.room_id = r.id
            JOIN dorm_buildings b ON r.building_id = b.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY o.check_in_date DESC
            LIMIT $limit OFFSET $offset
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success([
            'history' => $history,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit)
        ]);
    }

    /**
     * ค้นหาห้องของผู้ใช้จาก email
     */
    public function getMyRoom()
    {
        $email = $_GET['email'] ?? $this->user['email'] ?? null;

        if (!$email) {
            return $this->error('กรุณาระบุ email');
        }

        // 1. หา room_id ของ user คนนี้
        $stmt = $this->pdo->prepare("
            SELECT room_id 
            FROM dorm_occupancies 
            WHERE employee_email = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$occupancy) {
            return $this->success(['room' => null, 'message' => 'ไม่พบห้องที่ลงทะเบียน']);
        }

        $roomId = $occupancy['room_id'];

        // 2. ดึงข้อมูลห้อง
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   b.name as building_name,
                   b.code as building_code,
                   r.room_type as room_type_name
            FROM dorm_rooms r
            JOIN dorm_buildings b ON r.building_id = b.id
            WHERE r.id = ?
        ");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            return $this->error('ไม่พบข้อมูลห้องพัก');
        }

        // 3. ดึงข้อมูลผู้พักอาศัยทั้งหมดในห้องนี้ (Active only)
        $stmt = $this->pdo->prepare("
            SELECT id, employee_id, employee_name, employee_email, department, check_in_date,
                   COALESCE(accompanying_persons, 0) as accompanying_persons,
                   accompanying_details
            FROM dorm_occupancies
            WHERE room_id = ? AND status = 'active'
            ORDER BY check_in_date ASC
        ");
        $stmt->execute([$roomId]);
        $occupants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $room['occupants'] = $occupants;

        return $this->success(['room' => $room]);
    }
}
