<?php

require_once __DIR__ . '/../../../core/Database/Database.php';

class RoomService
{
    protected $pdo;

    public function __construct($pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            $db = new Database();
            $this->pdo = $db->getConnection();
        }
    }

    /**
     * Check In User and Update Room Status
     */
    public function checkIn($roomId, $userData, $creatorId = null)
    {
        // 1. Check Capacity (including accompanying persons)
        $room = $this->getRoom($roomId);
        if (!$room) throw new Exception('ไม่พบห้องพัก');

        $currentOccupants = $this->countActiveOccupants($roomId);
        $newPersonsCount = 1 + ($userData['accompanying_persons'] ?? 0);

        if (($currentOccupants + $newPersonsCount) > $room['capacity']) {
            throw new Exception("ห้องพักไม่เพียงพอ (ปัจจุบัน: {$currentOccupants} คน, ต้องการเพิ่ม: {$newPersonsCount} คน, ความจุ: {$room['capacity']} คน)");
        }

        // 2. Create Occupancy Record 
        // Try with accompanying_persons columns first, fall back to old query if not exist
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO dorm_occupancies 
                (room_id, employee_id, employee_name, employee_email, department, check_in_date, notes, accompanying_persons, accompanying_details, created_by, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $roomId,
                $userData['employee_id'],
                $userData['employee_name'],
                $userData['employee_email'] ?? null,
                $userData['department'] ?? null,
                $userData['check_in_date'] ?? date('Y-m-d'),
                $userData['notes'] ?? null,
                $userData['accompanying_persons'] ?? 0,
                $userData['accompanying_details'] ?? null,
                $creatorId
            ]);
        } catch (PDOException $e) {
            // Column doesn't exist - use old INSERT without accompanying columns
            $stmt = $this->pdo->prepare("
                INSERT INTO dorm_occupancies 
                (room_id, employee_id, employee_name, employee_email, department, check_in_date, notes, created_by, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $roomId,
                $userData['employee_id'],
                $userData['employee_name'],
                $userData['employee_email'] ?? null,
                $userData['department'] ?? null,
                $userData['check_in_date'] ?? date('Y-m-d'),
                $userData['notes'] ?? null,
                $creatorId
            ]);
        }

        $occupancyId = $this->pdo->lastInsertId();

        // 3. Update Room Status
        $this->updateRoomStatus($roomId);

        return $occupancyId;
    }

    /**
     * Check Out User and Update Room Status
     */
    public function checkOut($occupancyId, $checkOutDate = null, $force = false)
    {
        $checkOutDate = $checkOutDate ?: date('Y-m-d');

        // 1. Get Occupancy
        $stmt = $this->pdo->prepare("SELECT * FROM dorm_occupancies WHERE id = ? AND status = 'active'");
        $stmt->execute([$occupancyId]);
        $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$occupancy) throw new Exception('ไม่พบข้อมูลการเข้าพักหรือ Check-out ไปแล้ว');

        // 2. Check Unpaid Invoices
        if (!$force) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM dorm_invoices WHERE occupancy_id = ? AND status IN ('pending', 'overdue')");
            $stmt->execute([$occupancyId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("มีบิลค้างชำระ กรุณาชำระก่อนหรือใช้ Force Checkout");
            }
        }

        // 3. Update Occupancy
        $stmt = $this->pdo->prepare("UPDATE dorm_occupancies SET status = 'checked_out', check_out_date = ? WHERE id = ?");
        $stmt->execute([$checkOutDate, $occupancyId]);

        // 4. Update Room Status
        $this->updateRoomStatus($occupancy['room_id']);

        return true;
    }

    /**
     * Recalculate and Update Room Status Based on Occupants
     */
    public function updateRoomStatus($roomId)
    {
        $room = $this->getRoom($roomId);
        if (!$room) return;

        $occupants = $this->countActiveOccupants($roomId);

        $newStatus = 'available';
        if ($occupants > 0) {
            $newStatus = 'occupied';
            // Logic: If has ANY occupant -> occupied. 
            // If full -> occupied.
            // If maintenance -> maintenance (should be handled separately, but assuming status logic here overrides maintenance if people are in it? 
            // Usually maintenance implies empty. If people are in, it's occupied.
            // If we want 'full' status vs 'occupied', we can add logic. But current schema uses 'occupied'.
        }

        // Preserve maintenance status if no one is in it?
        if ($occupants == 0 && $room['status'] === 'maintenance') {
            return; // Keep maintenance
        }

        $stmt = $this->pdo->prepare("UPDATE dorm_rooms SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $roomId]);
    }

    private function getRoom($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dorm_rooms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Count total active occupants including accompanying persons
     */
    private function countActiveOccupants($roomId)
    {
        // Try with accompanying_persons first, fall back to simple count if column doesn't exist
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(1 + COALESCE(accompanying_persons, 0)), 0) 
                FROM dorm_occupancies 
                WHERE room_id = ? AND status = 'active'
            ");
            $stmt->execute([$roomId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            // Column doesn't exist yet - fall back to simple count
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM dorm_occupancies WHERE room_id = ? AND status = 'active'");
            $stmt->execute([$roomId]);
            return (int)$stmt->fetchColumn();
        }
    }
    /**
     * Change Room
     * Moves user and their relatives + notes to a new room
     */
    public function changeRoom($occupancyId, $newRoomId, $moveDate, $creatorId)
    {
        // 1. Get Current Occupancy
        $stmt = $this->pdo->prepare("SELECT * FROM dorm_occupancies WHERE id = ? AND status = 'active'");
        $stmt->execute([$occupancyId]);
        $currentOccupancy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentOccupancy) throw new Exception('ไม่พบข้อมูลการเข้าพักปัจจุบัน');

        // 2. Prepare Data for New Room
        $userData = [
            'employee_id' => $currentOccupancy['employee_id'],
            'employee_name' => $currentOccupancy['employee_name'],
            'employee_email' => $currentOccupancy['employee_email'],
            'department' => $currentOccupancy['department'],
            'check_in_date' => $moveDate,
            'notes' => $currentOccupancy['notes'],
            // Copied relative data
            'accompanying_persons' => $currentOccupancy['accompanying_persons'],
            'accompanying_details' => $currentOccupancy['accompanying_details']
        ];

        // 3. Check Out from Old Room (force=true to skip bill check during internal move)
        $this->checkOut($occupancyId, $moveDate, true);

        // 4. Check In to New Room
        $newOccupancyId = $this->checkIn($newRoomId, $userData, $creatorId);

        return $newOccupancyId;
    }

    /**
     * Add Relative to Existing Occupancy
     */
    public function addRelative($occupancyId, $additionalRelativesData)
    {
        // 1. Get Current Occupancy
        $stmt = $this->pdo->prepare("SELECT * FROM dorm_occupancies WHERE id = ? AND status = 'active'");
        $stmt->execute([$occupancyId]);
        $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$occupancy) throw new Exception('ไม่พบข้อมูลการเข้าพักปัจจุบัน');

        $roomId = $occupancy['room_id'];
        $room = $this->getRoom($roomId);
        if (!$room) throw new Exception('ไม่พบห้องพัก');

        // 2. Calculate New Count
        // Parse existing
        $existingRelatives = [];
        if (!empty($occupancy['accompanying_details'])) {
            $decoded = json_decode($occupancy['accompanying_details'], true);
            if (is_array($decoded)) $existingRelatives = $decoded;
        }

        // Parse new
        $newRelatives = [];
        if (is_string($additionalRelativesData)) {
            $decodedNew = json_decode($additionalRelativesData, true);
            if (is_array($decodedNew)) $newRelatives = $decodedNew;
        } elseif (is_array($additionalRelativesData)) {
            $newRelatives = $additionalRelativesData;
        }

        // Combine
        $allRelatives = array_merge($existingRelatives, $newRelatives);
        $totalRelativesCount = count($allRelatives);

        // 3. Check Capacity
        // Current occupants in room (excluding this user's OLD count, adding NEW count)
        // Optimization: just check if (room.capacity - current_occupants + old_relative_count) >= (1 + new_relative_count)
        // Or simpler: verify room capacity vs (current active occupants in room + newly added count)
        // wait, 'countActiveOccupants' returns total. 
        // We are increasing the count for THIS occupancy by (new - old).
        $increase = count($newRelatives); // We are ADDING these people.

        $currentRoomOccupants = $this->countActiveOccupants($roomId);

        if (($currentRoomOccupants + $increase) > $room['capacity']) {
            throw new Exception("ห้องพักเต็ม ไม่สามารถเพิ่มญาติได้ (ว่าง: " . ($room['capacity'] - $currentRoomOccupants) . ", ต้องการเพิ่ม: $increase)");
        }

        // 4. Update Occupancy
        $stmt = $this->pdo->prepare("
            UPDATE dorm_occupancies 
            SET accompanying_persons = ?, accompanying_details = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $totalRelativesCount,
            json_encode($allRelatives, JSON_UNESCAPED_UNICODE),
            $occupancyId
        ]);

        // 5. Update Room Status
        $this->updateRoomStatus($roomId);

        return true;
    }
}
