<?php

class CarBookingModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check if a role has manage permission for car booking module
     */
    public function checkManagePermission($roleId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(p.can_manage, 0) as can_manage
                FROM core_modules cm
                LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = ?
                WHERE cm.code = 'CAR_BOOKING'
            ");
            $stmt->execute([$roleId]);
            $perm = $stmt->fetch(PDO::FETCH_ASSOC);
            return !empty($perm['can_manage']);
        } catch (Exception $e) {
            return false;
        }
    }





    public function getDefaultSupervisorEmail($userId)
    {
        $stmt = $this->pdo->prepare("SELECT default_supervisor_email FROM users WHERE id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchColumn();
    }

    public function updateDefaultSupervisorEmail($userId, $email)
    {
        $stmt = $this->pdo->prepare("UPDATE users SET default_supervisor_email = :email WHERE id = :uid");
        return $stmt->execute([':email' => $email, ':uid' => $userId]);
    }

    public function createBooking($data)
    {
        $sql = "
            INSERT INTO cb_bookings (
                user_id, driver_user_id, driver_name, driver_email,
                approver_email, approver_user_id,
                start_time, end_time, destination, purpose,
                passengers, passengers_detail, passenger_user_ids,
                status, approval_token, token_expires_at, created_at
            )
            VALUES (
                :uid, :driver_uid, :driver_name, :driver_email,
                :approver_email, :approver_user_id,
                :start_time, :end_time, :destination, :purpose,
                :passengers, :passengers_detail, :passenger_user_ids,
                'pending_supervisor', :token, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW()
            )
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT cb.*, 
                   u.fullname, u.username, u.email AS user_email, u.department AS user_department, u.PersonnelEmail,
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb
            LEFT JOIN users u ON cb.user_id = u.id
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByToken($token)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cb_bookings WHERE approval_token = :token AND token_expires_at > NOW() LIMIT 1");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRequesterName($userId)
    {
        $stmt = $this->pdo->prepare("SELECT fullname FROM users WHERE id = :uid");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchColumn();
    }

    public function listMine($userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT cb.*, u.username, u.fullname,
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb
            JOIN users u ON cb.user_id = u.id
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.user_id = :uid
            ORDER BY cb.created_at DESC
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAll()
    {
        $stmt = $this->pdo->query("
            SELECT cb.*, u.username, u.fullname,
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            ORDER BY cb.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listPendingSupervisor()
    {
        $stmt = $this->pdo->query("
            SELECT cb.*, u.username, u.fullname,
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.status = 'pending_supervisor' 
            ORDER BY cb.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * List bookings pending approval by a specific approver email
     */
    public function listPendingByApproverEmail($email)
    {
        $stmt = $this->pdo->prepare("
            SELECT cb.*, u.username, u.fullname,
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.approver_email = :email 
              AND cb.status = 'pending_supervisor'
            ORDER BY cb.created_at DESC
        ");
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listPendingManager()
    {
        $stmt = $this->pdo->query("
            SELECT cb.*, u.username, u.fullname,
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.status = 'pending_manager' 
            ORDER BY cb.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listApproved()
    {
        $stmt = $this->pdo->query("
            SELECT cb.*, u.username, u.fullname,
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.status = 'approved' 
            ORDER BY cb.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listSupervisorRejected()
    {
        $stmt = $this->pdo->query("
            SELECT cb.*, u.username, u.fullname 
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            WHERE cb.status = 'rejected' 
            AND cb.supervisor_approved_at IS NULL 
            ORDER BY cb.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listManagerRejected()
    {
        $stmt = $this->pdo->query("
            SELECT cb.*, u.username, u.fullname 
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            WHERE cb.status = 'rejected' 
            AND cb.supervisor_approved_at IS NOT NULL 
            ORDER BY cb.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approveByToken($id, $approverEmail, $approverId)
    {
        $stmt = $this->pdo->prepare("UPDATE cb_bookings SET status='pending_manager', supervisor_approved_at=NOW(), supervisor_approved_by=:approver, supervisor_approved_user_id=:approver_uid WHERE id=:id");
        return $stmt->execute([
            ':id' => $id,
            ':approver' => $approverEmail,
            ':approver_uid' => $approverId ?: null
        ]);
    }

    public function rejectByToken($id, $reason, $rejectedBy)
    {
        $stmt = $this->pdo->prepare("UPDATE cb_bookings SET status='rejected', rejected_at=NOW(), rejection_reason=:reason, rejected_by=:rejected_by WHERE id=:id");
        return $stmt->execute([
            ':reason' => $reason,
            ':id' => $id,
            ':rejected_by' => $rejectedBy
        ]);
    }

    public function assignCar($id, $carId, $note)
    {
        $stmt = $this->pdo->prepare("
            UPDATE cb_bookings SET assigned_car_id=:cid, assignment_note=:note WHERE id=:id
        ");
        return $stmt->execute([
            ':cid' => $carId ?: null,
            ':note' => $note,
            ':id' => $id
        ]);
    }

    public function supervisorApprove($id, $approverEmail)
    {
        $stmt = $this->pdo->prepare("
            UPDATE cb_bookings 
            SET status='pending_manager', 
                supervisor_approved_at=NOW(), 
                supervisor_approved_by=:approver
            WHERE id=:id
        ");
        return $stmt->execute([
            ':approver' => $approverEmail,
            ':id' => $id
        ]);
    }

    public function managerApprove($id, $approverEmail, $approverId, $carId, $fleetId, $fleetAmount)
    {
        $stmt = $this->pdo->prepare("
            UPDATE cb_bookings 
            SET status='approved', 
                manager_approved_at=NOW(), 
                manager_approved_by=:approver,
                manager_approved_user_id=:approver_id,
                assigned_car_id=:car_id,
                fleet_card_id=:fleet_id,
                fleet_amount=:fleet_amnt,
                type=:type
            WHERE id=:id
        ");
        return $stmt->execute([
            ':approver' => $approverEmail,
            ':approver_id' => $approverId,
            ':id' => $id,
            ':car_id' => $carId,
            ':fleet_id' => $fleetId,
            ':fleet_amnt' => $fleetAmount,
            ':type' => ($carId ? 'car' : 'fleet')
        ]);
    }

    public function rejectBooking($id, $status, $reason, $rejectedBy)
    {
        $stmt = $this->pdo->prepare("
            UPDATE cb_bookings 
            SET status=:new_status, 
                rejection_reason=:reason,
                rejected_by=:rejected_by,
                rejected_at=NOW()
            WHERE id=:id
        ");
        return $stmt->execute([
            ':new_status' => $status,
            ':reason' => $reason,
            ':rejected_by' => $rejectedBy,
            ':id' => $id
        ]);
    }

    public function updateApprovedBooking($id, $updates, $params)
    {
        $sql = "UPDATE cb_bookings SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function saveDefaultSupervisor($userId, $email, $name, $supervisorId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET default_supervisor_email = :email,
                default_supervisor_name = :name,
                default_supervisor_id = :sid
            WHERE id = :uid
        ");
        return $stmt->execute([
            ':email' => $email,
            ':name' => $name,
            ':sid' => $supervisorId,
            ':uid' => $userId
        ]);
    }

    public function getAllCars()
    {
        $stmt = $this->pdo->query("SELECT id, name, brand, model, license_plate, capacity, status FROM cb_cars ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllFleetCards()
    {
        $stmt = $this->pdo->query("SELECT id, card_number, department, credit_limit, status FROM cb_fleet_cards ORDER BY card_number ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveBookings()
    {
        $stmt = $this->pdo->query("
            SELECT id, assigned_car_id, fleet_card_id, start_time, end_time, status 
            FROM cb_bookings 
            WHERE status IN ('approved', 'in_use', 'pending_return')
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listInUse()
    {
        $stmt = $this->pdo->query("
            SELECT cb.*, u.username, u.fullname, u.email as user_email,
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, 
                   c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.status IN ('approved', 'in_use', 'pending_return')
            ORDER BY cb.end_time ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listPendingReturn()
    {
        $stmt = $this->pdo->query("
            SELECT cb.*, u.username, u.fullname, u.email as user_email,
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, 
                   c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.status = 'pending_return'
            ORDER BY cb.user_reported_return_at ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reportReturn($id, $actualTime, $notes)
    {
        $stmt = $this->pdo->prepare("
            UPDATE cb_bookings 
            SET status = 'pending_return',
                user_reported_return_at = NOW(),
                actual_return_time = :actual_time,
                return_notes = :notes
            WHERE id = :id
        ");
        return $stmt->execute([
            ':actual_time' => $actualTime,
            ':notes' => $notes,
            ':id' => $id
        ]);
    }

    public function confirmReturn($id, $actualTime, $notes, $confirmedBy)
    {
        $stmt = $this->pdo->prepare("
            UPDATE cb_bookings 
            SET status = 'completed',
                returned_at = NOW(),
                returned_confirmed_by = :confirmed_by,
                actual_return_time = COALESCE(actual_return_time, :actual_time),
                return_notes = CONCAT(COALESCE(return_notes, ''), :notes)
            WHERE id = :id
        ");
        return $stmt->execute([
            ':confirmed_by' => $confirmedBy,
            ':actual_time' => $actualTime,
            ':notes' => $notes ? "\n[IPCD] " . $notes : '',
            ':id' => $id
        ]);
    }

    public function revokeBooking($id, $reason, $rejectedBy)
    {
        $stmt = $this->pdo->prepare("
            UPDATE cb_bookings 
            SET status='revoked', 
                rejection_reason=:reason,
                rejected_at=NOW(),
                rejected_by=:rejected_by
            WHERE id=:id
        ");
        return $stmt->execute([
            ':reason' => $reason,
            ':rejected_by' => $rejectedBy,
            ':id' => $id
        ]);
    }

    public function cancelBooking($id, $reason, $userId)
    {
        $stmt = $this->pdo->prepare("UPDATE cb_bookings SET status='cancelled', rejection_reason=:reason WHERE id=:id AND user_id=:uid");
        $stmt->execute([
            ':reason' => $reason,
            ':id' => $id,
            ':uid' => $userId
        ]);
        return $stmt->rowCount(); // Return number of affected rows
    }

    public function getUserDetailsByEmail($email)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn();
    }
}
