<?php

require_once 'BaseController.php'; // Local BaseController which is CBBaseController
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Services/EmailService.php';
require_once __DIR__ . '/../../../core/Services/NotificationService.php';
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Security/InputSanitizer.php';
require_once __DIR__ . '/../Models/CarBookingModel.php';

/**
 * BookingController
 * Handles all car booking operations including search, request creation, approval workflow, and notifications.
 * Extends CBBaseController for shared functionality and permission handling.
 */
class BookingController extends CBBaseController
{
    private $conn;
    private $bookingModel;
    // private $user; // Handled by CBBaseController

    public function __construct($user = null)
    {
        parent::__construct($user); // Call parent constructor
        $this->conn = $this->pdo; // Use pdo from parent
        $this->bookingModel = new CarBookingModel($this->pdo);
    }

    /**
     * Search Employee query
     * Searches both local database and Microsoft Graph API (via MicrosoftAuthController)
     * 
     * @param string $query Search term (name, email)
     * @return array List of employees
     */


    /**
     * Helper: Get user ID from email
     */
    private function getUserIdByEmail($email)
    {
        return NotificationService::getUserIdByEmail($email);
    }

    /**
     * Helper: Send in-app notification
     */
    private function sendNotification($email, $type, $title, $message, $link = null)
    {
        try {
            $userId = $this->getUserIdByEmail($email);
            if ($userId) {
                NotificationService::create($userId, $type, $title, $message, [], $link);
            }
        } catch (\Exception $e) {
            error_log("Notification failed: " . $e->getMessage());
        }
    }

    /**
     * Helper: Send notification to admin emails from settings (Dynamic Module ID)
     */
    protected function sendNotificationToAdmins($type, $title, $message, $link = null)
    {
        NotificationService::sendToModuleAdmins($this->moduleId ?? 2, $type, $title, $message, $link);
    }

    public function create(array $data)
    {
        $this->requirePermission('edit');
        if (!$this->user || empty($this->user['id'])) {
            return $this->jsonError('ไม่พบผู้ใช้งาน');
        }
        $required = ['start_time', 'end_time', 'destination', 'purpose', 'approver_email'];
        foreach ($required as $field) {
            if (empty($data[$field])) return $this->jsonError("กรอกข้อมูลไม่ครบ ($field)");
        }

        // ตรวจจับ XSS/HTML injection
        $textFields = ['destination', 'purpose', 'driver_name'];
        $validation = InputSanitizer::validateNoHtml($data, $textFields);
        if (!$validation['valid']) {
            return $this->jsonError($validation['message']);
        }

        // Sanitize input data
        $data['destination'] = InputSanitizer::text($data['destination']);
        $data['purpose'] = InputSanitizer::text($data['purpose'] ?? '');
        $data['driver_name'] = InputSanitizer::text($data['driver_name'] ?? '');
        $data['approver_email'] = InputSanitizer::email($data['approver_email']);

        // Determine driver
        $inputDriverName = trim($data['driver_name'] ?? '');

        if (!empty($inputDriverName)) {
            // A driver was specified (possibly someone else without a user ID)
            $driverUserId = !empty($data['driver_user_id']) ? $data['driver_user_id'] : null;
            $driverName = $inputDriverName;
            $driverEmail = $data['driver_email'] ?? null;
        } else {
            // No driver specified -> Requester drives
            $driverUserId = $this->user['id'];
            $driverName = $this->user['fullname'] ?? $this->user['username'] ?? null;
            $driverEmail = $this->user['email'] ?? null;
        }

        // Resolve driver ID from email if missing
        if (!empty($inputDriverName) && empty($driverUserId)) {
            if (!empty($data['driver_email'])) {
                $resolvedId = $this->getUserIdByEmail($data['driver_email']);
                if ($resolvedId) {
                    $driverUserId = $resolvedId;
                }
            }
        }

        // supervisor optional user id
        $approverUserId = $data['approver_user_id'] ?? null;

        // Resolve approver ID from email if missing
        if (empty($approverUserId) && !empty($data['approver_email'])) {
            $resolvedAppId = $this->getUserIdByEmail($data['approver_email']);
            if ($resolvedAppId) {
                $approverUserId = $resolvedAppId;
            }
        }

        // passengers detail array -> json
        $passengerCount = 0;  // Default to 0 if no passengers
        $passengersDetail = null;
        $passengerUserIds = [];
        if (!empty($data['passengers_detail']) && is_array($data['passengers_detail'])) {
            $passengersDetail = json_encode($data['passengers_detail']);
            $passengerCount = count($data['passengers_detail']);
            foreach ($data['passengers_detail'] as $p) {
                if (is_array($p) && !empty($p['user_id'])) {
                    $passengerUserIds[] = (int)$p['user_id'];
                }
            }
        }

        $token = bin2hex(random_bytes(16));

        $bookingData = [
            ':uid' => $this->user['id'],
            ':driver_uid' => $driverUserId,
            // ':driver_name' => $driverName, // Removed: Column dropped
            // ':driver_email' => $driverEmail, // Removed: Column dropped
            // ':approver_email' => trim($data['approver_email']), // Removed: Column dropped
            ':approver_user_id' => $approverUserId ?: null,
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':destination' => $data['destination'],
            ':purpose' => $data['purpose'] ?? null,
            ':passengers' => $passengerCount,
            ':passengers_detail' => $passengersDetail,
            ':passenger_user_ids' => $passengerUserIds ? json_encode($passengerUserIds) : null,
            ':token' => $token
        ];

        $id = $this->bookingModel->createBooking($bookingData);
        $id = (int)$id;

        // If user has no default supervisor email yet, save the chosen approver as default (only once)
        if (!empty($data['approver_email'])) {
            $currentDefault = $this->bookingModel->getDefaultSupervisorEmail($this->user['id']);
            if (empty($currentDefault)) {
                $this->bookingModel->updateDefaultSupervisorEmail($this->user['id'], trim($data['approver_email']));
            }
        }

        // send email to approver with CC to driver + passengers
        $booking = $this->bookingModel->getById($id);
        if ($booking) {
            // Build CC list: driver + passengers
            $ccEmails = [];

            // Add driver email if different from requester
            if (!empty($driverEmail) && $driverEmail !== $this->user['email']) {
                $ccEmails[] = $driverEmail;
            }

            // Add passenger emails
            if (!empty($data['passengers_detail']) && is_array($data['passengers_detail'])) {
                foreach ($data['passengers_detail'] as $passenger) {
                    if (!empty($passenger['email']) && $passenger['email'] !== $this->user['email']) {
                        $ccEmails[] = $passenger['email'];
                    }
                }
            }

            EmailService::sendSupervisorApprovalEmail($booking, $booking['approver_email'], $token, $ccEmails);

            // Send in-app notification to supervisor
            $this->sendNotification(
                $booking['approver_email'],
                'info',
                'มีคำขอจองรถรอการอนุมัติ',
                "คำขอ #{$id} จาก {$this->user['fullname']} รอการอนุมัติ",
                "Modules/CarBooking/?page=approvals"
            );

            // Send notification to admins
            $this->sendNotificationToAdmins(
                'info',
                'มีคำขอจองรถใหม่',
                "คำขอ #{$id} ไปที่ {$data['destination']}",
                "Modules/CarBooking/?page=pending"
            );
        }

        // Log audit
        $this->logAudit('create_booking', 'booking', $id, null, [
            'destination' => $data['destination'],
            'purpose' => $data['purpose'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time']
        ]);

        return ['success' => true, 'id' => $id];
    }

    public function listMine()
    {
        if (!$this->user || empty($this->user['id'])) return [];
        return $this->bookingModel->listMine($this->user['id']);
    }

    public function listAll()
    {
        return $this->bookingModel->listAll();
    }

    /**
     * List bookings approved by supervisor (for manager to process)
     */
    /**
     * List bookings waiting for supervisor approval
     */
    public function listPendingSupervisor()
    {
        $this->requirePermission('manage');
        $bookings = $this->bookingModel->listPendingSupervisor();
        return $this->_populateDetails($bookings);
    }

    /**
     * List bookings pending MY approval (for regular users who are approvers)
     * Does not require manage permission - only requires authentication
     */
    public function listMyPendingApprovals()
    {
        $this->requireAuth();
        $userId = $this->user['id'] ?? null;
        if (empty($userId)) {
            return [];
        }
        $bookings = $this->bookingModel->listPendingByApproverId($userId);
        return $this->_populateDetails($bookings);
    }

    /**
     * List bookings waiting for manager approval
     */
    public function listPendingManager()
    {
        $this->requirePermission('manage');
        $bookings = $this->bookingModel->listPendingManager();
        return $this->_populateDetails($bookings);
    }

    /**
     * List approved bookings
     */
    public function listApproved()
    {
        $this->requirePermission('manage');
        $bookings = $this->bookingModel->listApproved();
        return $this->_populateDetails($bookings);
    }

    /**
     * Helper to populate passengers for bookings
     */
    private function _populateDetails($bookings)
    {
        foreach ($bookings as &$booking) {
            // Get passengers from JSON column 'passengers_detail'
            $passengers = [];
            if (!empty($booking['passengers_detail'])) {
                $decoded = json_decode($booking['passengers_detail'], true);
                if (is_array($decoded)) {
                    $passengers = $decoded;
                }
            }
            $booking['passengers'] = $passengers;
        }
        return $bookings;
    }

    /**
     * List bookings rejected by supervisor
     */
    public function listSupervisorRejected()
    {
        $this->requirePermission('manage');
        return $this->bookingModel->listSupervisorRejected();
    }

    /**
     * List bookings rejected by manager
     */
    public function listManagerRejected()
    {
        $this->requirePermission('manage');
        return $this->bookingModel->listManagerRejected();
    }


    public function approveByToken($token)
    {
        $booking = $this->bookingModel->getByToken($token);
        if (!$booking) return $this->jsonError('ไม่พบคำขอหรือลิงก์หมดอายุ');
        if ($booking['status'] !== 'pending_supervisor') return $this->jsonError('คำขอถูกดำเนินการแล้ว');

        $this->bookingModel->approveByToken($booking['id'], $booking['approver_email'], $booking['approver_user_id']);

        // Log audit
        $this->logAudit('supervisor_approve_token', 'booking', $booking['id'], ['status' => 'pending_supervisor'], ['status' => 'pending_manager']);

        return ['success' => true, 'booking_id' => $booking['id']];
    }

    public function getDetailsByToken($token)
    {
        $booking = $this->bookingModel->getByToken($token);
        if (!$booking) return $this->jsonError('ไม่พบคำขอหรือลิงก์หมดอายุ');

        // Prepare display data
        $userName = $this->bookingModel->getRequesterName($booking['user_id']);

        $isProcessed = ($booking['status'] !== 'pending_supervisor');
        $statusMsg = 'รออนุมัติ';
        if ($booking['status'] === 'pending_supervisor') $statusMsg = 'รอหัวหน้าอนุมัติ';
        if ($booking['status'] === 'pending_manager') $statusMsg = 'รอสายงาน IPCD อนุมัติ';
        if ($booking['status'] === 'approved') $statusMsg = 'อนุมัติแล้ว';
        if ($booking['status'] === 'rejected') $statusMsg = 'ถูกปฏิเสธ';
        if ($booking['status'] === 'cancelled') $statusMsg = 'ยกเลิกแล้ว';

        // Passengers display: prefer list of names/emails
        $passengerText = '-';
        if (!empty($booking['passengers_detail'])) {
            $decoded = json_decode($booking['passengers_detail'], true);
            if (is_array($decoded) && count($decoded) > 0) {
                $names = [];
                foreach ($decoded as $p) {
                    if (is_array($p)) {
                        $names[] = $p['name'] ?? $p['email'] ?? '';
                    } else {
                        $names[] = $p;
                    }
                }
                $names = array_filter($names);
                if (!empty($names)) {
                    $passengerText = implode(', ', $names);
                }
            }
        }
        if ($passengerText === '-' && !empty($booking['passengers'])) {
            $passengerText = $booking['passengers'] . ' คน';
        }

        return [
            'success' => true,
            'already_processed' => $isProcessed,
            'status' => $booking['status'],
            'status_message' => $statusMsg,
            'user_name' => $userName,
            'destination' => $booking['destination'],
            'purpose' => $booking['purpose'],
            'start_time' => $booking['start_time'],
            'end_time' => $booking['end_time'],
            'driver' => $booking['driver_name'],
            'passenger' => $passengerText,
            'token' => $token
        ];
    }

    public function rejectByToken($token, $reason = '')
    {
        $booking = $this->bookingModel->getByToken($token);
        if (!$booking) return $this->jsonError('ไม่พบคำขอหรือลิงก์หมดอายุ');
        if ($booking['status'] !== 'pending_supervisor') return $this->jsonError('คำขอถูกดำเนินการแล้ว');

        $this->bookingModel->rejectByToken($booking['id'], $reason, $booking['approver_email']);

        // Log audit
        $this->logAudit('supervisor_reject_token', 'booking', $booking['id'], ['status' => 'pending_supervisor'], [
            'status' => 'rejected',
            'reason' => $reason
        ]);

        return ['success' => true, 'booking_id' => $booking['id']];
    }

    public function assignCar($id, $carId, $note = '')
    {
        $this->requirePermission('manage');

        $this->bookingModel->assignCar($id, $carId, $note);

        // Log audit
        $this->logAudit('assign_car', 'booking', $id, null, ['assigned_car_id' => $carId, 'note' => $note]);

        return ['success' => true];
    }

    /**
     * Approve booking (for API call from manage.php)
     * Handles both supervisor and manager approval based on current status
     */
    public function approve($data)
    {
        $bookingId = $data['booking_id'] ?? $data['id'] ?? 0;
        if (!$bookingId) {
            return $this->jsonError('ไม่พบ booking_id');
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }

        // Supervisor approval (pending_supervisor -> pending_manager)
        if ($booking['status'] === 'pending_supervisor') {
            // Check if user is the assigned approver
            $isApprover = ($booking['approver_email'] === ($this->user['email'] ?? ''));

            // Check manage permission via model
            $roleId = $this->user['role_id'] ?? 0;
            $canManage = ($roleId == 1);
            if (!$canManage) {
                $canManage = $this->bookingModel->checkManagePermission($roleId);
            }

            if (!$isApprover && !$canManage) {
                return $this->jsonError('คุณไม่ใช่หัวหน้าที่ระบุไว้และไม่มีสิทธิ์จัดการระบบ');
            }

            $approverEmail = $this->user['email'] ?? $this->user['username'];
            $approverId = $this->user['id'] ?? null;
            $this->bookingModel->supervisorApprove($bookingId, $approverEmail, $approverId);

            // Log audit
            $this->logAudit('supervisor_approve', 'booking', $bookingId, ['status' => 'pending_supervisor'], ['status' => 'pending_manager']);

            // Real-time notification to requester
            NotificationService::create(
                $booking['user_id'],
                'info',
                'หัวหน้าอนุมัติแล้ว',
                'คำขอจองรถ #' . $bookingId . ' หัวหน้าอนุมัติแล้ว รอ IPCD พิจารณา',
                ['booking_id' => $bookingId],
                '/carbooking?view=bookings'
            );

            return ['success' => true, 'message' => 'อนุมัติโดยหัวหน้าสำเร็จ'];
        }

        // Manager approval (pending_manager -> approved)
        if ($booking['status'] === 'pending_manager') {
            $this->requirePermission('manage');

            $carId = $data['car_id'] ?? null;
            $fleetCardId = $data['fleet_card_id'] ?? null;
            $fleetAmount = $data['fleet_amount'] ?? null;

            $approverEmail = $this->user['email'] ?? $this->user['username'];
            $approverId = $this->user['id'] ?? null;

            $this->bookingModel->managerApprove($bookingId, $approverEmail, $approverId, $carId, $fleetCardId, $fleetAmount);

            // Log audit
            $this->logAudit('manager_approve', 'booking', $bookingId, ['status' => 'pending_manager'], [
                'status' => 'approved',
                'assigned_car_id' => $carId,
                'fleet_card_id' => $fleetCardId
            ]);

            // Real-time notification to requester
            $carInfo = $carId ? ' รถ: ' . ($booking['license_plate'] ?? '') : '';
            NotificationService::create(
                $booking['user_id'],
                'success',
                'อนุมัติจองรถสำเร็จ',
                'คำขอจองรถ #' . $bookingId . ' ได้รับอนุมัติแล้ว' . $carInfo,
                ['booking_id' => $bookingId],
                '/carbooking?view=bookings'
            );

            return ['success' => true, 'message' => 'อนุมัติโดย IPCD สำเร็จ'];
        }

        return $this->jsonError('สถานะคำขอไม่ถูกต้อง');
    }

    /**
     * Reject booking (for API call from manage.php)
     */
    public function reject($data)
    {
        $bookingId = $data['booking_id'] ?? $data['id'] ?? 0;
        $reason = $data['reason'] ?? '';

        if (!$bookingId) {
            return $this->jsonError('ไม่พบ booking_id');
        }
        if (!$reason) {
            return $this->jsonError('กรุณาระบุเหตุผล');
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }

        // Check Permissions
        if ($booking['status'] === 'pending_supervisor') {
            $isApprover = ($booking['approver_email'] === ($this->user['email'] ?? ''));

            // Allow if approver OR manager
            $canManage = false;
            $roleId = $this->user['role_id'] ?? 0;
            if ($roleId == 1) {
                $canManage = true;
            } else {
                $canManage = $this->bookingModel->checkManagePermission($roleId);
            }

            if (!$isApprover && !$canManage) {
                return $this->jsonError('คุณไม่ใช่หัวหน้าที่ระบุไว้และไม่มีสิทธิ์จัดการระบบ');
            }
        } else {
            // Other statuses require manage permission
            $this->requirePermission('manage');
        }

        // Determine new status based on who is rejecting
        $newStatus = 'rejected_supervisor';
        if ($booking['status'] === 'pending_manager') {
            $newStatus = 'rejected_manager';
        }

        $rejectedBy = $this->user['email'] ?? $this->user['username'];
        $this->bookingModel->rejectBooking($bookingId, $newStatus, $reason, $rejectedBy);

        // Log audit
        $this->logAudit('reject_booking', 'booking', $bookingId, ['status' => $booking['status']], [
            'status' => $newStatus,
            'rejection_reason' => $reason
        ]);

        // Real-time notification to requester
        NotificationService::create(
            $booking['user_id'],
            'error',
            'คำขอจองรถถูกปฏิเสธ',
            'คำขอ #' . $bookingId . ' ถูกปฏิเสธ: ' . mb_substr($reason, 0, 50),
            ['booking_id' => $bookingId, 'reason' => $reason],
            '/carbooking?view=bookings'
        );

        return ['success' => true, 'message' => 'ปฏิเสธคำขอสำเร็จ'];
    }

    /**
     * Assign car to booking (for API call from manage.php)
     */
    public function assign($data)
    {
        $bookingId = $data['booking_id'] ?? $data['id'] ?? 0;
        $carId = $data['car_id'] ?? null;
        $driverName = $data['driver_name'] ?? null;
        $fleetCardId = $data['fleet_card_id'] ?? null;
        $fleetAmount = $data['fleet_amount'] ?? null;

        if (!$bookingId) {
            return $this->jsonError('ไม่พบ booking_id');
        }

        $booking = $this->getById($bookingId);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }

        // Only manager can assign (from pending_manager -> approved)
        if ($booking['status'] !== 'pending_manager') {
            return $this->jsonError('คำขอต้องอยู่ในสถานะรอ IPCD อนุมัติ');
        }

        $stmt = $this->conn->prepare("
            UPDATE cb_bookings 
            SET status='approved', 
                manager_approved_at=NOW(), 
                manager_approved_user_id=:approver_uid,
                assigned_car_id=:car_id,
                fleet_card_id=:fleet_card_id,
                fleet_amount=:fleet_amount
            WHERE id=:id
        ");
        $stmt->execute([
            ':approver_uid' => $this->user['id'],
            ':car_id' => $carId ?: null,
            ':fleet_card_id' => $fleetCardId ?: null,
            ':fleet_amount' => $fleetAmount ?: null,
            ':id' => $bookingId
        ]);

        // Log audit
        $this->logAudit('manager_approve_assign', 'booking', $bookingId, ['status' => 'pending_manager'], [
            'status' => 'approved',
            'assigned_car_id' => $carId,
            'action' => 'assign_approve'
        ]);

        // Notify requester that booking is approved with car assigned
        if (!empty($booking['user_email'])) {
            $this->sendNotification(
                $booking['user_email'],
                'success',
                'คำขอจองรถได้รับอนุมัติแล้ว',
                "คำขอ #{$bookingId} ได้รับการอนุมัติและมอบหมายรถเรียบร้อย",
                "Modules/CarBooking/?page=request_history"
            );
        }

        return ['success' => true, 'message' => 'มอบหมายรถและอนุมัติสำเร็จ'];
    }





    /**
     * ยกเลิก/เพิกถอนคำขอที่อนุมัติแล้ว (สำหรับ manager)
     */
    public function revoke($id, $reason = '')
    {
        $this->requirePermission('manage');

        // Check if booking exists and is approved or in_use
        $booking = $this->bookingModel->getById($id);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }
        if ($booking['status'] !== 'approved') {
            return $this->jsonError('สามารถเพิกถอนได้เฉพาะคำขอที่อนุมัติแล้วเท่านั้น (สถานะปัจจุบัน: ' . $booking['status'] . ')');
        }

        $rejectedBy = $this->user['email'] ?? $this->user['username'] ?? 'System';
        $this->bookingModel->revokeBooking($id, $reason, $rejectedBy);

        // Send notification to requester
        if (!empty($booking['user_email'])) {
            EmailService::sendCancellationEmail($booking, $booking['user_email'], $reason);

            $this->sendNotification(
                $booking['user_email'],
                'error',
                'คำขอจองรถถูกเพิกถอน',
                "คำขอ #{$id} ถูกเพิกถอน" . ($reason ? ": {$reason}" : ""),
                "Modules/CarBooking/?page=bookings"
            );
        }

        // Log audit
        $this->logAudit('revoke_booking', 'booking', $id, ['status' => $booking['status']], [
            'status' => 'revoked',
            'reason' => $reason
        ]);

        return ['success' => true, 'message' => 'เพิกถอนคำขอสำเร็จ'];
    }

    /**
     * แก้ไขคำขอที่อนุมัติแล้ว (วันที่, รถ, บัตรเติมน้ำมัน, วงเงิน)
     */
    public function updateApproved($id, array $data)
    {
        $this->requirePermission('manage');
        $booking = $this->bookingModel->getById($id);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }
        if ($booking['status'] !== 'approved') {
            return $this->jsonError('สามารถแก้ไขได้เฉพาะคำขอที่อนุมัติแล้วเท่านั้น');
        }

        $updates = [];
        $params = [':id' => $id];

        // Update dates
        if (!empty($data['start_time'])) {
            $updates[] = 'start_time = :start_time';
            $params[':start_time'] = $data['start_time'];
        }
        if (!empty($data['end_time'])) {
            $updates[] = 'end_time = :end_time';
            $params[':end_time'] = $data['end_time'];
        }

        // Determine allocation type and clear the other
        $hasCarId = isset($data['assigned_car_id']) && !empty($data['assigned_car_id']);
        $hasFleetId = isset($data['fleet_card_id']) && !empty($data['fleet_card_id']);

        if ($hasCarId) {
            // Selected car -> clear fleet card fields
            $updates[] = 'assigned_car_id = :car_id';
            $params[':car_id'] = $data['assigned_car_id'];
            $updates[] = 'fleet_card_id = NULL';
            $updates[] = 'fleet_amount = NULL';
            $updates[] = "type = 'car'";
        } elseif ($hasFleetId) {
            // Selected fleet card -> clear car field
            $updates[] = 'fleet_card_id = :fleet_card_id';
            $params[':fleet_card_id'] = $data['fleet_card_id'];
            $updates[] = "type = 'fleet'";
            if (isset($data['fleet_amount'])) {
                $updates[] = 'fleet_amount = :fleet_amount';
                $params[':fleet_amount'] = $data['fleet_amount'] ?: null;
            }
            $updates[] = 'assigned_car_id = NULL';
        }

        // Update driver name - REMOVED as column is dropped
        /*
        if (isset($data['driver_name'])) {
            $updates[] = 'driver_name = :driver_name';
            $params[':driver_name'] = $data['driver_name'];
        }
        */

        if (empty($updates)) {
            return $this->jsonError('ไม่มีข้อมูลที่จะแก้ไข');
        }

        $this->bookingModel->updateApprovedBooking($id, $updates, $params);

        // Log audit
        $this->logAudit('update_booking', 'booking', $id, $booking, $data);

        // Send notification to user
        $updatedBooking = $this->bookingModel->getById($id);
        if ($updatedBooking && !empty($updatedBooking['user_email'])) {
            EmailService::sendBookingUpdateEmail($updatedBooking, $updatedBooking['user_email']);

            $this->sendNotification(
                $updatedBooking['user_email'],
                'info',
                'คำขอจองรถถูกแก้ไข',
                "คำขอ #{$id} มีการเปลี่ยนแปลง",
                "Modules/CarBooking/?page=bookings"
            );
        }

        return ['success' => true, 'message' => 'แก้ไขสำเร็จ'];
    }


    public function resendEmails($bookingId)
    {
        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'ไม่พบคำขอ'];
        }

        $status = $booking['status'] ?? '';

        // Pending supervisor: resend approval link to approver
        if ($status === 'pending_supervisor') {
            if (empty($booking['approver_email'])) {
                return ['success' => false, 'message' => 'ไม่พบอีเมลหัวหน้า'];
            }

            // Ensure user_email is set
            $userEmail = $booking['user_email'] ?? $booking['email'] ?? $booking['PersonnelEmail'] ?? '';
            $booking['user_email'] = $userEmail; // Backfill for EmailService usage

            // Build CC list: driver + passengers
            $ccEmails = [];
            if (!empty($booking['driver_email']) && $booking['driver_email'] !== $userEmail) {
                $ccEmails[] = $booking['driver_email'];
            }
            if (!empty($booking['passengers_detail'])) {
                $passengers = json_decode($booking['passengers_detail'], true);
                if (is_array($passengers)) {
                    foreach ($passengers as $p) {
                        $pEmail = is_array($p) ? ($p['email'] ?? '') : $p; // robust check
                        if (!empty($pEmail) && $pEmail !== $userEmail) {
                            $ccEmails[] = $pEmail;
                        }
                    }
                }
            }

            EmailService::sendSupervisorApprovalEmail($booking, $booking['approver_email'], $booking['approval_token'], $ccEmails);
            return ['success' => true, 'message' => 'ส่งอีเมลถึงหัวหน้าแล้ว'];
        }

        // Pending manager: resend notifications to managers
        if ($status === 'pending_manager') {
            $managers = $this->getManagerEmails();
            if (empty($managers)) {
                return ['success' => false, 'message' => 'ไม่พบอีเมลผู้จัดการ'];
            }
            foreach ($managers as $managerEmail) {
                EmailService::sendManagerNotificationEmail($booking, $managerEmail);
            }
            return ['success' => true, 'message' => 'ส่งอีเมลแจ้งผู้จัดการแล้ว'];
        }

        // Approved: resend approval to requester
        if ($status === 'approved') {
            if (empty($booking['user_email'])) {
                return ['success' => false, 'message' => 'ไม่พบอีเมลผู้ขอ'];
            }
            EmailService::sendUserApprovalEmail($booking, $booking['user_email']);
            return ['success' => true, 'message' => 'ส่งอีเมลยืนยันให้ผู้ขอแล้ว'];
        }

        // Rejected: resend rejection to requester
        if ($status === 'rejected') {
            if (empty($booking['user_email'])) {
                return ['success' => false, 'message' => 'ไม่พบอีเมลผู้ขอ'];
            }
            $rejectedBy = empty($booking['supervisor_approved_at']) ? 'supervisor' : 'manager';
            $reason = $booking['rejection_reason'] ?? '';
            EmailService::sendUserRejectionEmail($booking, $booking['user_email'], $reason, $rejectedBy);
            return ['success' => true, 'message' => 'ส่งอีเมลแจ้งผลปฏิเสธแล้ว'];
        }

        return ['success' => false, 'message' => 'สถานะนี้ไม่รองรับการส่งอีเมลซ้ำ'];
    }

    // Removed private getByToken and getById as they are duplicates of Model methods

    private function notifyAfterSupervisorDecision($booking, $approved, $reason = '')
    {
        // When supervisor approves → status is now 'pending_manager'
        // When supervisor rejects → status is 'rejected'

        $requesterEmail = $booking['user_email'] ?? $booking['email'] ?? null;
        $bookingId = $booking['id'];

        if ($approved) {
            // Supervisor approved → notify requester that it's pending manager approval
            if ($requesterEmail) {
                EmailService::sendUserSupervisorApprovalEmail($booking, $requesterEmail);

                $this->sendNotification(
                    $requesterEmail,
                    'success',
                    'หัวหน้างานอนุมัติแล้ว',
                    "คำขอ #{$bookingId} ผ่านการอนุมัติจากหัวหน้า รอผู้ดูแลรถ",
                    \Core\Helpers\UrlHelper::getBaseUrl() . "/Modules/CarBooking/?page=request_history"
                );
            }

            // Notify admins (from settings) that there's a booking to approve
            $managers = $this->getManagerEmails();
            if (!empty($managers)) {
                foreach ($managers as $managerEmail) {
                    // Send email to manager for notification
                    EmailService::sendManagerNotificationEmail($booking, $managerEmail);

                    $this->sendNotification(
                        $managerEmail,
                        'info',
                        'มีคำขอรอการอนุมัติ',
                        "คำขอ #{$bookingId} รอการอนุมัติจากผู้ดูแล",
                        \Core\Helpers\UrlHelper::getBaseUrl() . "/Modules/CarBooking/?page=pending"
                    );
                }
            }
        } else {
            // Supervisor rejected → notify requester
            if ($requesterEmail) {
                EmailService::sendUserRejectionEmail($booking, $requesterEmail, $reason, 'supervisor');

                $this->sendNotification(
                    $requesterEmail,
                    'error',
                    'หัวหน้างานไม่อนุมัติ',
                    "คำขอ #{$bookingId} ไม่ผ่านการอนุมัติ" . ($reason ? ": {$reason}" : ""),
                    "Modules/CarBooking/?page=request_history"
                );
            }
        }
    }

    private function getManagerEmails()
    {
        // Use getAdminEmails which fetches from settings (not permissions)
        return $this->getAdminEmails();
    }

    private function getAdminEmails()
    {
        $moduleId = $this->moduleId ?? 2;
        $settings = \EmailService::getModuleSettings($moduleId);
        $val = $settings['admin_emails'] ?? '';

        if (!$val) return [];
        $arr = json_decode($val, true);
        if (is_array($arr)) return $arr;
        return array_filter(array_map('trim', explode(',', $val)));
    }

    /**
     * Save default supervisor for current user
     */
    public function saveDefaultSupervisor($data)
    {
        if (!$this->user) {
            return $this->jsonError('ไม่พบข้อมูลผู้ใช้');
        }

        $email = $data['supervisor_email'] ?? '';
        $name = $data['supervisor_name'] ?? '';
        $id = $data['supervisor_id'] ?? null;

        if (!$email) {
            return $this->jsonError('กรุณาระบุอีเมลหัวหน้า');
        }

        try {
            // Update user's default supervisor in database (ID only)
            $this->bookingModel->saveDefaultSupervisor($this->user['id'], $id);

            // Update session
            $_SESSION['user']['default_supervisor_email'] = $email;
            $_SESSION['user']['default_supervisor_name'] = $name;
            $_SESSION['user']['default_supervisor_id'] = $id;

            // Log audit
            $this->logAudit('update_default_supervisor', 'user', $this->user['id'], null, ['supervisor_id' => $id]);

            return ['success' => true, 'message' => 'บันทึกหัวหน้าเริ่มต้นแล้ว'];
        } catch (Exception $e) {
            return $this->jsonError('ไม่สามารถบันทึกได้: ' . $e->getMessage());
        }
    }

    /**
     * Get available cars and fleet cards for a specific time range
     * A car/fleet is NOT available if:
     * 1. Its own status is not 'available'/'active'
     * 2. There's an active booking (approved, in_use, pending_return) that overlaps with the time range
     */
    public function getAvailableAssets($startTime, $endTime, $excludeBookingId = 0)
    {
        // 1. Get ALL cars
        $cars = $this->bookingModel->getAllCars();

        // 2. Get ALL fleet cards
        $fleets = $this->bookingModel->getAllFleetCards();

        // 3. Get ALL active bookings (approved, in_use, pending_return)
        $allActiveBookings = $this->bookingModel->getActiveBookings();

        // Convert request times to timestamp
        $requestStart = strtotime($startTime);
        $requestEnd = strtotime($endTime);

        // 4. Check each car
        foreach ($cars as &$c) {
            $carId = (int)$c['id'];
            $reason = '';
            $isAvailable = true;

            // Check 1: Car's own status
            if ($c['status'] !== 'available') {
                $isAvailable = false;
                $reason = 'สถานะรถ: ' . $c['status'];
            } else {
                // Check 2: Any overlapping active booking?
                foreach ($allActiveBookings as $booking) {
                    // Skip if not this car
                    if (empty($booking['assigned_car_id'])) continue;
                    if ((int)$booking['assigned_car_id'] !== $carId) continue;
                    // Skip the booking being approved
                    if ((int)$booking['id'] === (int)$excludeBookingId) continue;

                    // PRIORITY CHECK: If car is in_use or pending_return = NOT available until returned!
                    if ($booking['status'] === 'in_use' || $booking['status'] === 'pending_return') {
                        $isAvailable = false;
                        $statusLabel = $booking['status'] === 'in_use' ? 'กำลังใช้งาน' : 'รอยืนยันคืน';
                        $reason = $statusLabel . ' (ยังไม่คืนรถ)';
                        break;
                    }

                    // Check time overlap for approved status
                    $bookingStart = strtotime($booking['start_time']);
                    $bookingEnd = strtotime($booking['end_time']);

                    if ($requestStart < $bookingEnd && $requestEnd > $bookingStart) {
                        $isAvailable = false;
                        $reason = 'มีการจอง (' . date('d/m H:i', $bookingStart) . '-' . date('H:i', $bookingEnd) . ')';
                        break;
                    }
                }
            }

            $c['is_available'] = $isAvailable;
            $c['reason'] = $reason;
        }

        // 5. Check each fleet card (same logic)
        foreach ($fleets as &$f) {
            $fleetId = (int)$f['id'];
            $reason = '';
            $isAvailable = true;

            // Check 1: Fleet's own status
            if ($f['status'] !== 'active') {
                $isAvailable = false;
                $reason = 'สถานะบัตร: ' . $f['status'];
            } else {
                // Check 2: Any overlapping active booking?
                foreach ($allActiveBookings as $booking) {
                    if (empty($booking['fleet_card_id'])) continue;
                    if ((int)$booking['fleet_card_id'] !== $fleetId) continue;
                    if ((int)$booking['id'] === (int)$excludeBookingId) continue;

                    // PRIORITY CHECK: If fleet is in_use or pending_return = NOT available until returned!
                    if ($booking['status'] === 'in_use' || $booking['status'] === 'pending_return') {
                        $isAvailable = false;
                        $statusLabel = $booking['status'] === 'in_use' ? 'กำลังใช้งาน' : 'รอยืนยันคืน';
                        $reason = $statusLabel . ' (ยังไม่คืน)';
                        break;
                    }

                    // Check time overlap for approved status
                    $bookingStart = strtotime($booking['start_time']);
                    $bookingEnd = strtotime($booking['end_time']);

                    if ($requestStart < $bookingEnd && $requestEnd > $bookingStart) {
                        $isAvailable = false;
                        $reason = 'มีการจอง (' . date('d/m H:i', $bookingStart) . '-' . date('H:i', $bookingEnd) . ')';
                        break;
                    }
                }
            }

            $f['is_available'] = $isAvailable;
            $f['reason'] = $reason;
        }

        return ['cars' => $cars, 'fleet_cards' => $fleets];
    }

    private function jsonError($msg)
    {
        return ['success' => false, 'message' => $msg];
    }
    /**
     * Send email notification asynchronously
     * Called by frontend after successful action
     */
    public function sendEmailNotification()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $input['booking_id'] ?? 0;
        $type = $input['type'] ?? 'approval';

        if (!$id) return $this->jsonError('Missing ID');

        $booking = $this->bookingModel->getById($id);
        if (!$booking) return $this->jsonError('Booking not found');

        try {
            if ($type === 'approval') {
                // Manager Approval (Final)
                $requesterEmail = $booking['user_email'] ?? $booking['email'] ?? null;
                if ($requesterEmail) {
                    EmailService::sendUserApprovalEmail($booking, $requesterEmail);

                    $this->sendNotification(
                        $requesterEmail,
                        'success',
                        'คำขอจองรถได้รับอนุมัติ',
                        "คำขอ #{$id} ได้รับการอนุมัติแล้ว",
                        "Modules/CarBooking/?page=request_history"
                    );
                }
            } elseif ($type === 'supervisor_approval') {
                // Supervisor Approval (Intermediate)
                // Use existing logic to notify user and managers
                $this->notifyAfterSupervisorDecision($booking, true);
            } elseif ($type === 'rejection') {
                // Rejection (by Supervisor or Manager)
                $reason = $booking['rejection_reason'] ?? '';
                $requesterEmail = $booking['user_email'] ?? $booking['email'] ?? null;
                $rejecterRole = ($booking['status'] === 'rejected' && $booking['manager_approved_at']) ? 'manager' : 'supervisor';

                if ($requesterEmail) {
                    EmailService::sendUserRejectionEmail($booking, $requesterEmail, $reason, $rejecterRole);

                    $this->sendNotification(
                        $requesterEmail,
                        'error',
                        'คำขอจองรถไม่ได้รับอนุมัติ',
                        "คำขอ #{$id} ถูกปฏิเสธ" . ($reason ? ": {$reason}" : ""),
                        \Core\Helpers\UrlHelper::getBaseUrl() . "/Modules/CarBooking/?page=request_history"
                    );
                }
            }

            return ['success' => true];
        } catch (Exception $e) {
            error_log('Email Notification Error: ' . $e->getMessage());
            return $this->jsonError($e->getMessage());
        }
    }



    // ======================================
    // CAR RETURN FEATURE
    // ======================================

    /**
     * List bookings that are currently in use (approved but not returned)
     * For IPCD to track active car usage
     */
    public function listInUse()
    {
        $this->requirePermission('manage');
        $bookings = $this->bookingModel->listInUse();
        return $this->_populateDetails($bookings);
    }

    /**
     * List bookings waiting for return confirmation (reported by user)
     */
    public function listPendingReturn()
    {
        $this->requirePermission('manage');
        $bookings = $this->bookingModel->listPendingReturn();
        return $this->_populateDetails($bookings);
    }

    /**
     * User reports that they have returned the car
     * Changes status from 'approved' to 'pending_return'
     */
    public function reportReturn($data)
    {
        $bookingId = $data['id'] ?? $data['booking_id'] ?? 0;
        $notes = $data['notes'] ?? '';
        $actualReturnTime = $data['actual_return_time'] ?? null;

        if (!$bookingId) {
            return $this->jsonError('ไม่พบ booking_id');
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }

        // Only the owner or manager can report return (role_id 1 = Admin with manage perms)
        $isManager = (int)($this->user['role_id'] ?? 0) === 1;
        if ($booking['user_id'] != $this->user['id'] && !$isManager) {
            return $this->jsonError('คุณไม่มีสิทธิ์แจ้งคืนรถคำขอนี้');
        }

        if (!in_array($booking['status'], ['approved', 'in_use'])) {
            return $this->jsonError('สามารถแจ้งคืนได้เฉพาะคำขอที่อนุมัติแล้วหรือกำลังใช้งานเท่านั้น');
        }

        $this->bookingModel->reportReturn($bookingId, $actualReturnTime ?: date('Y-m-d H:i:s'), $notes);

        // Log audit
        $this->logAudit('report_return', 'booking', $bookingId, ['status' => 'approved'], [
            'status' => 'pending_return',
            'notes' => $notes
        ]);

        // Notify admins that user reported car return
        $this->sendNotificationToAdmins(
            'info',
            'มีผู้แจ้งคืนรถ',
            "คำขอ #{$bookingId} แจ้งคืนรถแล้ว รอยืนยัน",
            "Modules/CarBooking/?page=in-use"
        );

        // Notify requester that we received their return report
        if (!empty($booking['user_id'])) {
            try {
                NotificationService::create(
                    $booking['user_id'],
                    'info',
                    'รับการแจ้งคืนรถแล้ว',
                    "คำขอ #{$bookingId} ระบบรับการแจ้งคืนรถแล้ว รอ IPCD ยืนยัน",
                    ['booking_id' => $bookingId],
                    'Modules/CarBooking/?page=request_history'
                );
            } catch (\Exception $e) {
                error_log("Notification failed (report_return): " . $e->getMessage());
            }
        }

        return ['success' => true, 'message' => 'แจ้งคืนรถสำเร็จ รอ IPCD ยืนยัน'];
    }

    /**
     * IPCD confirms car return
     * Changes status to 'completed' - can confirm from 'approved' or 'pending_return'
     */
    public function confirmReturn($data)
    {
        $this->requirePermission('manage');

        $bookingId = $data['id'] ?? $data['booking_id'] ?? 0;
        $notes = $data['notes'] ?? '';
        $actualReturnTime = $data['actual_return_time'] ?? null;

        if (!$bookingId) {
            return $this->jsonError('ไม่พบ booking_id');
        }

        $booking = $this->bookingModel->getById($bookingId);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }

        if (!in_array($booking['status'], ['approved', 'in_use', 'pending_return'])) {
            return $this->jsonError('สามารถยืนยันคืนได้เฉพาะคำขอที่อนุมัติแล้ว, กำลังใช้งาน หรือรอยืนยันคืน');
        }

        $confirmedBy = $this->user['email'] ?? $this->user['username'] ?? 'IPCD';
        $this->bookingModel->confirmReturn($bookingId, $actualReturnTime ?: date('Y-m-d H:i:s'), $notes, $confirmedBy);

        // Log audit
        $this->logAudit('confirm_return', 'booking', $bookingId, ['status' => $booking['status']], [
            'status' => 'completed',
            'confirmed_by' => $confirmedBy
        ]);

        // Send notification to requester
        try {
            // Get requester details from booking object directly if available
            // User email is usually available in $booking['user_email'] from getById join
            if (!empty($booking['user_email'])) {
                // Get user ID by email via model or service
                // Wait, NotificationService::create needs ID.
                // Booking array from getById has user_id !!!
                $requesterId = $booking['user_id'];

                if ($requesterId) {
                    NotificationService::create(
                        $requesterId,
                        'success',
                        'คืนรถเรียบร้อยแล้ว',
                        "คำขอจอง #{$bookingId} ได้รับการยืนยันคืนรถแล้ว",
                        [],
                        "Modules/CarBooking/?page=request_history"
                    );
                }
            }
        } catch (\Exception $e) {
            error_log("Notification failed in confirmReturn: " . $e->getMessage());
        }

        return ['success' => true, 'message' => 'ยืนยันคืนรถสำเร็จ'];
    }

    public function cancel($id, $reason = '')
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        // 1. Get booking to check status
        $booking = $this->bookingModel->getById($id);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }

        // 2. Validate ownership (security check)
        $isAdmin = $this->hasPermission('manage');
        if ($booking['user_id'] != $this->user['id'] && !$isAdmin) {
            return $this->jsonError('คุณไม่มีสิทธิ์ยกเลิกคำขอนี้');
        }

        // 3. Prevent cancellation if in_use or later steps
        $cancellableStatuses = ['pending_supervisor', 'pending_manager', 'approved'];
        if (!in_array($booking['status'], $cancellableStatuses)) {
            return $this->jsonError('ไม่สามารถยกเลิกคำขอนี้ได้ (สถานะ: ' . $booking['status'] . ')');
        }

        // 4. Update status
        $affected = $this->bookingModel->cancelBooking($id, $reason, $this->user['id']);

        if ($affected > 0) {
            $this->logAudit('cancel_booking', 'booking', $id, null, ['status' => 'cancelled', 'reason' => $reason]);

            // Re-fetch to be safe or use existing $booking object if data didn't change much except status
            if ($booking && !empty($booking['user_email'])) {
                EmailService::sendCancellationEmail($booking, $booking['user_email'], $reason);

                // Send notification to requester
                $this->sendNotification(
                    $booking['user_email'],
                    'warning',
                    'คำขอจองรถถูกยกเลิก',
                    "คำขอ #{$id} ถูกยกเลิก" . ($reason ? ": {$reason}" : ""),
                    "Modules/CarBooking/?page=request_history"
                );
            }

            // Notify admins that requester cancelled (สอดคล้องกับ Dormitory)
            $this->sendNotificationToAdmins(
                'warning',
                'มีผู้ยกเลิกคำขอจองรถ',
                "คำขอ #{$id} ถูกยกเลิกโดยผู้ขอ" . ($reason ? ": " . mb_substr($reason, 0, 50) : ""),
                "Modules/CarBooking/?page=pending"
            );
        }
        return ['success' => true];
    }
}
