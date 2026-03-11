<?php

class BookingModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getRoomTypes()
    {
        $stmt = $this->pdo->query("SELECT * FROM dorm_room_types WHERE status = 'active'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCurrentOccupancy($userId)
    {
        $stmt = $this->pdo->prepare("SELECT EmpCode FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $empCode = $stmt->fetchColumn() ?: $userId; // fallback if null

        $stmt = $this->pdo->prepare("
            SELECT o.*, r.room_number, b.name as building_name 
            FROM dorm_occupancies o
            JOIN dorm_rooms r ON o.room_id = r.id
            JOIN dorm_buildings b ON r.building_id = b.id
            WHERE o.employee_id IN (?, ?) 
              AND o.status = 'active'
        ");
        $stmt->execute([$userId, $empCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserReservations($userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM dorm_reservations 
            WHERE requester_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMaintenanceRequestsByRoom($roomId)
    {
        $stmt = $this->pdo->prepare("
            SELECT m.*, c.name as category_name 
            FROM dorm_maintenance_requests m
            LEFT JOIN dorm_maintenance_categories c ON m.category_id = c.id
            WHERE m.room_id = ? 
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$roomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMaintenanceRequestsByUser($userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT m.*, c.name as category_name 
            FROM dorm_maintenance_requests m
            LEFT JOIN dorm_maintenance_categories c ON m.category_id = c.id
            WHERE m.requester_id = ? 
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasActiveOccupancy($userId)
    {
        $stmt = $this->pdo->prepare("SELECT EmpCode FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $empCode = $stmt->fetchColumn() ?: $userId;

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM dorm_occupancies 
            WHERE employee_id IN (?, ?) 
              AND status = 'active'
        ");
        $stmt->execute([$userId, $empCode]);
        return $stmt->fetchColumn() > 0;
    }

    public function hasPendingRequest($userId, $type)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM dorm_reservations WHERE requester_id = ? AND request_type = ? AND status = 'pending'");
        $stmt->execute([$userId, $type]);
        return $stmt->fetchColumn() > 0;
    }

    public function createReservation($data)
    {
        $sql = "
            INSERT INTO dorm_reservations 
            (requester_id, request_type, reason, room_type_preference, has_relative, relative_details, document_paths, check_in, status, supervisor_email, approval_token, token_expires_at)
            VALUES (:requester_id, :request_type, :reason, :room_type_preference, :has_relative, :relative_details, :document_paths, :check_in, :status, :supervisor_email, :approval_token, :token_expires_at)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':requester_id' => $data['requester_id'],
            ':request_type' => $data['request_type'],
            ':reason' => $data['reason'] ?? null,
            ':room_type_preference' => $data['room_type_preference'] ?? null,
            ':has_relative' => $data['has_relative'] ?? 0,
            ':relative_details' => $data['relative_details'] ?? null,
            ':document_paths' => $data['document_paths'] ?? null,
            ':check_in' => $data['check_in'] ?? null,
            ':status' => $data['status'] ?? 'pending_supervisor',
            ':supervisor_email' => $data['supervisor_email'] ?? null,
            ':approval_token' => $data['approval_token'] ?? null,
            ':token_expires_at' => $data['token_expires_at'] ?? null,
        ]);
        return $this->pdo->lastInsertId();
    }

    public function getRequestsList($status, $supervisorEmail = null, $search = null, $type = 'all')
    {
        $sql = "
            SELECT r.*, u.fullname, u.Level3Name as department, u.email, rt.name as room_type_name,
                   ar.room_number as assigned_room_number, ab.name as assigned_building_name
            FROM dorm_reservations r
            JOIN users u ON r.requester_id = u.id
            LEFT JOIN dorm_room_types rt ON r.room_type_preference = rt.id
            LEFT JOIN dorm_rooms ar ON r.room_id = ar.id
            LEFT JOIN dorm_buildings ab ON ar.building_id = ab.id
            WHERE 1=1
        ";
        $params = [];

        if ($status && $status !== 'all') {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }

        if ($type && $type !== 'all') {
            $sql .= " AND r.request_type = ?";
            $params[] = $type;
        }

        if ($supervisorEmail) {
            $sql .= " AND r.supervisor_email = ?";
            $params[] = $supervisorEmail;
        }

        if ($search) {
            $searchTerm = "%$search%";
            $sql .= " AND (u.fullname LIKE ? OR u.Level3Name LIKE ? OR r.reason LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRequestsByStatus($status)
    {
        return $this->getRequestsList($status);
    }

    public function getAvailableRooms()
    {
        $sql = "
            SELECT r.id, r.room_number, r.floor , r.status, r.room_type as type, b.name as building_name, r.capacity,
                   (SELECT COUNT(*) FROM dorm_occupancies o WHERE o.room_id = r.id AND o.status = 'active') as current_occupants,
                   (SELECT COALESCE(SUM(COALESCE(o.accompanying_persons, 0)), 0) FROM dorm_occupancies o WHERE o.room_id = r.id AND o.status = 'active') as current_relatives,
                   (SELECT GROUP_CONCAT(u.fullname SEPARATOR ', ') FROM dorm_occupancies o JOIN users u ON o.employee_id = u.id WHERE o.room_id = r.id AND o.status = 'active') as occupant_names
            FROM dorm_rooms r
            JOIN dorm_buildings b ON r.building_id = b.id
            WHERE r.status != 'maintenance'
            ORDER BY b.name, r.room_number
        ";
        $stmt = $this->pdo->query($sql);
        $allRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $availableRooms = [];
        foreach ($allRooms as $room) {
            if ($room['current_occupants'] < $room['capacity']) {
                $room['free_spots'] = $room['capacity'] - $room['current_occupants'];
                $availableRooms[] = $room;
            }
        }
        return $availableRooms;
    }

    public function getRequestsBySupervisor($supervisorEmail, $status)
    {
        return $this->getRequestsList($status, $supervisorEmail);
    }

    public function getReservationById($id, $lock = false)
    {
        $sql = "SELECT * FROM dorm_reservations WHERE id = ?";
        if ($lock) {
            $sql .= " FOR UPDATE";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getReservationWithUser($id)
    {
        $sql = "SELECT r.*, u.email, u.fullname, u.Level3Name as department, u.EmpCode FROM dorm_reservations r JOIN users u ON r.requester_id = u.id WHERE r.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status, $cancelReason = null, $approverId = null)
    {
        $sql = "UPDATE dorm_reservations SET status = ?, cancel_reason = ?, approver_id = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $cancelReason, $approverId, $id]);
    }

    public function saveDefaultSupervisor($userId, $supervisorId)
    {
        $sql = "UPDATE users SET default_supervisor_id = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$supervisorId, $userId]);
    }

    public function getUserWithSupervisor($userId)
    {
        $sql = "SELECT u.*, 
                       s.fullname as default_supervisor_name, 
                       s.email as default_supervisor_email
                FROM users u
                LEFT JOIN users s ON u.default_supervisor_id = s.id
                WHERE u.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function approveSupervisor($id, $userId = null)
    {
        $sql = "UPDATE dorm_reservations 
                SET status = 'pending_manager', supervisor_approved_at = NOW(), supervisor_approved_user_id = ?, token_expires_at = NULL, approval_token = NULL 
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$userId, $id]);
    }

    public function rejectSupervisor($id, $reason, $rejectedBy, $userId = null)
    {
        $sql = "UPDATE dorm_reservations 
                SET status = 'rejected_supervisor', rejection_reason = ?, rejected_by = ?, supervisor_approved_user_id = ?, token_expires_at = NULL, approval_token = NULL, approved_at = NOW() 
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$reason, $rejectedBy, $userId, $id]);
    }

    public function rejectManager($id, $reason, $rejectedBy, $userId = null)
    {
        $sql = "UPDATE dorm_reservations 
                SET status = 'rejected_manager', rejection_reason = ?, rejected_by = ?, manager_approved_user_id = ?, manager_approved_at = NOW()
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$reason, $rejectedBy, $userId, $id]);
    }

    public function approveReservation($id, $roomId, $keyDate, $remark, $approverId)
    {
        $sql = "
            UPDATE dorm_reservations 
            SET status = 'approved', room_id = ?, key_pickup_date = ?, admin_remark = ?, approver_id = ?, manager_approved_user_id = ?, manager_approved_at = NOW(), approved_at = NOW(), check_out = NULL
            WHERE id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$roomId, $keyDate, $remark, $approverId, $approverId, $id]);
    }

    public function getReservationByToken($token)
    {
        $sql = "SELECT r.*, u.email as requester_email_address, u.fullname as requester_name 
                FROM dorm_reservations r 
                JOIN users u ON r.requester_id = u.id 
                WHERE r.approval_token = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getRoomById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dorm_rooms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateToken($id, $token, $expiresAt)
    {
        $sql = "UPDATE dorm_reservations SET approval_token = ?, token_expires_at = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$token, $expiresAt, $id]);
    }
}
