<?php

require_once 'BaseController.php'; // Local BaseController which is CBBaseController
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Services/EmailService.php';
require_once __DIR__ . '/../../../core/Services/NotificationService.php';
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Security/InputSanitizer.php';

/**
 * BookingController
 * Handles all car booking operations including search, request creation, approval workflow, and notifications.
 * Extends CBBaseController for shared functionality and permission handling.
 */
class BookingController extends CBBaseController
{
    private $conn;
    // private $user; // Handled by CBBaseController

    public function __construct($user = null)
    {
        parent::__construct($user); // Call parent constructor
        $this->conn = $this->pdo; // Use pdo from parent
    }

    /**
     * Search Employee query
     * Searches both local database and Microsoft Graph API (via MicrosoftAuthController)
     * 
     * @param string $query Search term (name, email)
     * @return array List of employees
     */
    public function searchEmployee($query)
    {
        // Handle array input from BaseController::processRequest
        if (is_array($query)) {
            $query = $query['query'] ?? '';
        }
        if (strlen($query) < 2) {
            return ['success' => true, 'employees' => []];
        }

        $results = [];

        // 1. ค้นหาจาก DB
        try {
            $stmt = $this->conn->prepare("
                SELECT id, username as code, fullname as name, email, department
                FROM users 
                WHERE (username LIKE ? OR fullname LIKE ? OR email LIKE ?) 
                LIMIT 10
            ");
            $term = "%$query%";
            $stmt->execute([$term, $term, $term]);
            $dbResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($dbResults as $emp) {
                $results[] = [
                    'id' => $emp['id'],
                    'code' => $emp['code'],
                    'name' => $emp['name'],
                    'email' => $emp['email'],
                    'department' => $emp['department'],
                    'source' => 'database'
                ];
            }
        } catch (Exception $e) {
            // ignore DB errors
        }

        // 2. ค้นหาจาก Microsoft Graph
        // ใช้ App Token (Client Credentials) เพื่อให้ค้นหาได้เสมอ ไม่ต้องพึ่ง user login
        $accessToken = $_SESSION['access_token'] ?? null;

        // ถ้าไม่มี user token ให้ใช้ app token แทน
        if (!$accessToken) {
            require_once __DIR__ . '/../../../core/Config/MicrosoftOAuthConfig.php';

            // Clear any expired cached token first
            if (isset($_SESSION['ms_app_token'])) {
                unset($_SESSION['ms_app_token']);
                unset($_SESSION['ms_app_token_expires']);
                // [DISABLED FOR PRODUCTION] error_log("MS Search: Cleared cached token to force refresh");
            }

            $accessToken = MicrosoftOAuthConfig::getAppAccessToken();
        }

        if ($accessToken) {
            try {
                $searchQuery = urlencode("\"displayName:$query\" OR \"mail:$query\"");
                $graphUrl = "https://graph.microsoft.com/v1.0/users?\$search=$searchQuery&\$select=id,displayName,mail,department&\$top=10";



                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $graphUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                    'ConsistencyLevel: eventual'
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                // $curlError = curl_error($ch); // Removed as per user's implicit instruction (not in diff but in context)
                curl_close($ch);

                if ($httpCode === 200) {
                    $data = json_decode($response, true);

                    if (!empty($data['value'])) {
                        foreach ($data['value'] as $msUser) {
                            $email = $msUser['mail'] ?? '';
                            // Avoid duplicates
                            $exists = false;
                            foreach ($results as $r) {
                                if (!empty($r['email']) && strtolower($r['email']) === strtolower($email)) {
                                    $exists = true;
                                    break;
                                }
                            }
                            if (!$exists && $email) {
                                $results[] = [
                                    'id' => null,
                                    'code' => $msUser['id'] ?? '',
                                    'name' => $msUser['displayName'] ?? '',
                                    'email' => $email,
                                    'department' => $msUser['department'] ?? '',
                                    'source' => 'microsoft'
                                ];
                            }
                        }
                    }
                } else {
                    error_log("MS Search Failed: HTTP $httpCode - " . substr($response, 0, 500));
                }
            } catch (Exception $e) {
                error_log("MS Search Exception: " . $e->getMessage());
            }
        } else {
            error_log("MS Search: No access token available");
        }

        return ['success' => true, 'employees' => array_slice($results, 0, 15)];
    }

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

        // supervisor optional user id
        $approverUserId = $data['approver_user_id'] ?? null;

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
        $stmt = $this->conn->prepare("
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
        ");
        $stmt->execute([
            ':uid' => $this->user['id'],
            ':driver_uid' => $driverUserId,
            ':driver_name' => $driverName,
            ':driver_email' => $driverEmail,
            ':approver_email' => trim($data['approver_email']),
            ':approver_user_id' => $approverUserId ?: null,
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':destination' => $data['destination'],
            ':purpose' => $data['purpose'] ?? null,
            ':passengers' => $passengerCount,
            ':passengers_detail' => $passengersDetail,
            ':passenger_user_ids' => $passengerUserIds ? json_encode($passengerUserIds) : null,
            ':token' => $token
        ]);
        $id = (int)$this->conn->lastInsertId();

        // If user has no default supervisor email yet, save the chosen approver as default (only once)
        if (!empty($data['approver_email'])) {
            $chk = $this->conn->prepare("SELECT default_supervisor_email FROM users WHERE id = :uid LIMIT 1");
            $chk->execute([':uid' => $this->user['id']]);
            $currentDefault = $chk->fetchColumn();
            if (empty($currentDefault)) {
                $upd = $this->conn->prepare("UPDATE users SET default_supervisor_email = :email WHERE id = :uid");
                $upd->execute([
                    ':email' => trim($data['approver_email']),
                    ':uid' => $this->user['id']
                ]);
            }
        }

        // send email to approver with CC to driver + passengers
        $booking = $this->getById($id);
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
        $stmt = $this->conn->prepare("
            SELECT cb.*, 
                   c.name AS assigned_car_name, c.brand AS assigned_car_brand, c.model AS assigned_car_model, c.license_plate AS assigned_car_plate,
                   fc.card_number AS fleet_card_number, fc.department AS fleet_card_department
            FROM cb_bookings cb
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.user_id = :uid
            ORDER BY cb.created_at DESC
        ");
        $stmt->bindValue(':uid', $this->user['id'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAll()
    {
        $stmt = $this->conn->query("
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

    /**
     * List bookings approved by supervisor (for manager to process)
     */
    /**
     * List bookings waiting for supervisor approval
     */
    public function listPendingSupervisor()
    {
        $this->requirePermission('manage');
        $stmt = $this->conn->query("
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
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->_populateDetails($bookings);
    }

    /**
     * List bookings waiting for manager approval
     */
    public function listPendingManager()
    {
        $this->requirePermission('manage');
        $stmt = $this->conn->query("
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
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->_populateDetails($bookings);
    }

    /**
     * List approved bookings
     */
    public function listApproved()
    {
        $this->requirePermission('manage');
        $stmt = $this->conn->query("
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
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        // Rejected by supervisor (before supervisor approval)
        // supervisor_approved_at IS NULL means supervisor never approved
        $stmt = $this->conn->query("
            SELECT cb.*, u.username, u.fullname 
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            WHERE cb.status = 'rejected' 
            AND cb.supervisor_approved_at IS NULL 
            ORDER BY cb.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * List bookings rejected by manager
     */
    public function listManagerRejected()
    {
        $this->requirePermission('manage');
        // Rejected by manager (after supervisor approval)
        // supervisor_approved_at IS NOT NULL means supervisor approved, then manager rejected
        $stmt = $this->conn->query("
            SELECT cb.*, u.username, u.fullname 
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id = u.id 
            WHERE cb.status = 'rejected' 
            AND cb.supervisor_approved_at IS NOT NULL 
            ORDER BY cb.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function approveByToken($token)
    {
        $booking = $this->getByToken($token);
        if (!$booking) return $this->jsonError('ไม่พบคำขอหรือลิงก์หมดอายุ');
        if ($booking['status'] !== 'pending_supervisor') return $this->jsonError('คำขอถูกดำเนินการแล้ว');

        $stmt = $this->conn->prepare("UPDATE cb_bookings SET status='pending_manager', supervisor_approved_at=NOW(), supervisor_approved_by=:approver, supervisor_approved_user_id=:approver_uid WHERE id=:id");
        $stmt->bindValue(':id', $booking['id'], PDO::PARAM_INT);
        $stmt->bindValue(':approver', $booking['approver_email'], PDO::PARAM_STR);
        $stmt->bindValue(':approver_uid', $booking['approver_user_id'] ?: null, PDO::PARAM_INT);
        $stmt->execute();

        // Log audit
        $this->logAudit('supervisor_approve_token', 'booking', $booking['id'], ['status' => 'pending_supervisor'], ['status' => 'pending_manager']);

        return ['success' => true, 'booking_id' => $booking['id']];
    }

    public function getDetailsByToken($token)
    {
        $booking = $this->getByToken($token);
        if (!$booking) return $this->jsonError('ไม่พบคำขอหรือลิงก์หมดอายุ');

        // Prepare display data
        $stmt = $this->conn->prepare("SELECT fullname FROM users WHERE id = :uid");
        $stmt->execute([':uid' => $booking['user_id']]);
        $userName = $stmt->fetchColumn();

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
        $booking = $this->getByToken($token);
        if (!$booking) return $this->jsonError('ไม่พบคำขอหรือลิงก์หมดอายุ');
        if ($booking['status'] !== 'pending_supervisor') return $this->jsonError('คำขอถูกดำเนินการแล้ว');

        $stmt = $this->conn->prepare("UPDATE cb_bookings SET status='rejected', rejected_at=NOW(), rejection_reason=:reason, rejected_by=:rejected_by WHERE id=:id");
        $stmt->execute([
            ':reason' => $reason,
            ':id' => $booking['id'],
            ':rejected_by' => $booking['approver_email']
        ]);

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

        $stmt = $this->conn->prepare("
            UPDATE cb_bookings SET assigned_car_id=:cid, assignment_note=:note WHERE id=:id
        ");
        $stmt->execute([
            ':cid' => $carId ?: null,
            ':note' => $note,
            ':id' => $id
        ]);

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

        $booking = $this->getById($bookingId);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }

        // Supervisor approval (pending_supervisor -> pending_manager)
        if ($booking['status'] === 'pending_supervisor') {
            // Check if user is the assigned approver
            $isApprover = ($booking['approver_email'] === ($this->user['email'] ?? ''));

            // Check manage permission manually
            $roleId = $this->user['role_id'] ?? 0;
            $canManage = ($roleId == 1);
            if (!$canManage) {
                $chk = $this->pdo->prepare("SELECT can_manage FROM core_module_permissions WHERE module_id=2 AND role_id=?");
                $chk->execute([$roleId]);
                $canManage = (bool)$chk->fetchColumn();
            }

            if (!$isApprover && !$canManage) {
                return $this->jsonError('คุณไม่ใช่หัวหน้าที่ระบุไว้และไม่มีสิทธิ์จัดการระบบ');
            }
            $stmt = $this->conn->prepare("
                UPDATE cb_bookings 
                SET status='pending_manager', 
                    supervisor_approved_at=NOW(), 
                    supervisor_approved_by=:approver
                WHERE id=:id
            ");
            $stmt->execute([
                ':approver' => $this->user['email'] ?? $this->user['username'],
                ':id' => $bookingId
            ]);

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
            $this->requirePermission('manage');

            $carId = $data['car_id'] ?? null;
            $fleetCardId = $data['fleet_card_id'] ?? null;
            $fleetAmount = $data['fleet_amount'] ?? null;
            $stmt = $this->conn->prepare("
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
            $stmt->execute([
                ':approver' => $this->user['email'] ?? $this->user['username'],
                ':approver_id' => $this->user['id'] ?? null,
                ':id' => $bookingId,
                ':car_id' => $carId,
                ':fleet_id' => $fleetCardId,
                ':fleet_amnt' => $fleetAmount,
                ':type' => ($carId ? 'car' : 'fleet')
            ]);

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

        $booking = $this->getById($bookingId);
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
                $chk = $this->pdo->prepare("SELECT can_manage FROM core_module_permissions WHERE module_id=2 AND role_id=?");
                $chk->execute([$roleId]);
                $canManage = (bool)$chk->fetchColumn();
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

        $stmt = $this->conn->prepare("
            UPDATE cb_bookings 
            SET status=:new_status, 
                rejection_reason=:reason,
                rejected_by=:rejected_by,
                rejected_at=NOW()
            WHERE id=:id
        ");
        $stmt->execute([
            ':new_status' => $newStatus,
            ':reason' => $reason,
            ':rejected_by' => $this->user['email'] ?? $this->user['username'],
            ':id' => $bookingId
        ]);

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
                manager_approved_by=:approver,
                assigned_car_id=:car_id,
                driver_name=:driver_name,
                fleet_card_id=:fleet_card_id,
                fleet_amount=:fleet_amount
            WHERE id=:id
        ");
        $stmt->execute([
            ':approver' => $this->user['email'] ?? $this->user['username'],
            ':car_id' => $carId ?: null,
            ':driver_name' => $driverName ?: null,
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



    public function cancel($id, $reason = '')
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        // 1. Get booking to check status
        $booking = $this->getById($id);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }

        // 2. Validate ownership (security check)
        $isAdmin = $this->hasPermission('manage');
        if ($booking['user_id'] != $this->user['id'] && !$isAdmin) {
            return $this->jsonError('คุณไม่มีสิทธิ์ยกเลิกคำขอนี้');
        }

        // 3. Prevent cancellation if in_use or later steps
        // Allow cancel only if: pending_..., approved
        // Block if: in_use, pending_return, completed, rejected..., revoked, cancelled
        $cancellableStatuses = ['pending_supervisor', 'pending_manager', 'approved'];
        if (!in_array($booking['status'], $cancellableStatuses)) {
            return $this->jsonError('ไม่สามารถยกเลิกคำขอนี้ได้ (สถานะ: ' . $booking['status'] . ')');
        }

        // 4. Update status
        $stmt = $this->conn->prepare("UPDATE cb_bookings SET status='cancelled', rejection_reason=:reason WHERE id=:id AND user_id=:uid");
        $stmt->execute([
            ':reason' => $reason,
            ':id' => $id,
            ':uid' => $this->user['id']
        ]);

        if ($stmt->rowCount() > 0) {
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
        }
        return ['success' => true];
    }

    /**
     * ยกเลิก/เพิกถอนคำขอที่อนุมัติแล้ว (สำหรับ manager)
     */
    public function revoke($id, $reason = '')
    {
        $this->requirePermission('manage');

        // Check if booking exists and is approved or in_use
        $booking = $this->getById($id);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }
        if ($booking['status'] !== 'approved') {
            return $this->jsonError('สามารถเพิกถอนได้เฉพาะคำขอที่อนุมัติแล้วเท่านั้น (สถานะปัจจุบัน: ' . $booking['status'] . ')');
        }

        // Use 'revoked' status instead of 'cancelled'
        $stmt = $this->conn->prepare("
            UPDATE cb_bookings 
            SET status='revoked', 
                rejection_reason=:reason,
                rejected_at=NOW(),
                rejected_by=:rejected_by
            WHERE id=:id
        ");
        $stmt->execute([
            ':reason' => $reason,
            ':rejected_by' => $this->user['email'] ?? $this->user['username'] ?? 'System',
            ':id' => $id
        ]);

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
        $booking = $this->getById($id);
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

        // Update driver name
        if (isset($data['driver_name'])) {
            $updates[] = 'driver_name = :driver_name';
            $params[':driver_name'] = $data['driver_name'];
        }

        if (empty($updates)) {
            return $this->jsonError('ไม่มีข้อมูลที่จะแก้ไข');
        }

        $sql = "UPDATE cb_bookings SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        // Log audit
        $this->logAudit('update_booking', 'booking', $id, $booking, $data);

        // Send notification to user
        $updatedBooking = $this->getById($id);
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
        $booking = $this->getById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'ไม่พบคำขอ'];
        }

        $status = $booking['status'] ?? '';

        // Pending supervisor: resend approval link to approver
        if ($status === 'pending_supervisor') {
            if (empty($booking['approver_email'])) {
                return ['success' => false, 'message' => 'ไม่พบอีเมลหัวหน้า'];
            }

            // Build CC list: driver + passengers
            $ccEmails = [];
            if (!empty($booking['driver_email']) && $booking['driver_email'] !== $booking['user_email']) {
                $ccEmails[] = $booking['driver_email'];
            }
            if (!empty($booking['passengers_detail'])) {
                $passengers = json_decode($booking['passengers_detail'], true);
                if (is_array($passengers)) {
                    foreach ($passengers as $p) {
                        if (!empty($p['email']) && $p['email'] !== $booking['user_email']) {
                            $ccEmails[] = $p['email'];
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

    private function getByToken($token)
    {
        // Clean token if it contains garbage (e.g. :1)
        $token = explode(':', $token)[0];

        $stmt = $this->conn->prepare("SELECT * FROM cb_bookings WHERE approval_token=:t LIMIT 1");
        $stmt->bindValue(':t', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getById($id)
    {
        $stmt = $this->conn->prepare("
            SELECT cb.*,
               u.username as user_name, u.fullname as user_fullname, u.email as user_email,
               c.brand, c.model, c.license_plate,
               fc.card_number as fleet_card_number
            FROM cb_bookings cb 
            JOIN users u ON cb.user_id=u.id 
            LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
            LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
            WHERE cb.id=:id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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
                    "Modules/CarBooking/?page=request_history"
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
                        "Modules/CarBooking/?page=pending"
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
            // Update user's default supervisor in database
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET default_supervisor_email = :email,
                    default_supervisor_name = :name,
                    default_supervisor_id = :sid
                WHERE id = :uid
            ");
            $stmt->execute([
                ':email' => $email,
                ':name' => $name,
                ':sid' => $id,
                ':uid' => $this->user['id']
            ]);

            // Update session
            $_SESSION['user']['default_supervisor_email'] = $email;
            $_SESSION['user']['default_supervisor_name'] = $name;
            $_SESSION['user']['default_supervisor_id'] = $id;

            // Log audit
            $this->logAudit('update_default_supervisor', 'user', $this->user['id'], null, ['supervisor_email' => $email]);

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
        $stmt = $this->conn->query("SELECT id, name, brand, model, license_plate, capacity, status FROM cb_cars ORDER BY name ASC");
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Get ALL fleet cards
        $stmt = $this->conn->query("SELECT id, card_number, department, credit_limit, status FROM cb_fleet_cards ORDER BY card_number ASC");
        $fleets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Get ALL active bookings (approved, in_use, pending_return)
        $stmt = $this->conn->query("
            SELECT id, assigned_car_id, fleet_card_id, start_time, end_time, status 
            FROM cb_bookings 
            WHERE status IN ('approved', 'in_use', 'pending_return')
        ");
        $allActiveBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        $booking = $this->getById($id);
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
                $reason = $booking['reject_reason'] ?? '';
                $requesterEmail = $booking['user_email'] ?? $booking['email'] ?? null;
                $rejecterRole = ($booking['status'] === 'rejected' && $booking['manager_approved_at']) ? 'manager' : 'supervisor';

                if ($requesterEmail) {
                    EmailService::sendUserRejectionEmail($booking, $requesterEmail, $reason, $rejecterRole);

                    $this->sendNotification(
                        $requesterEmail,
                        'error',
                        'คำขอจองรถไม่ได้รับอนุมัติ',
                        "คำขอ #{$id} ถูกปฏิเสธ" . ($reason ? ": {$reason}" : ""),
                        "Modules/CarBooking/?page=request_history"
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
        $stmt = $this->conn->query("
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
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->_populateDetails($bookings);
    }

    /**
     * List bookings waiting for return confirmation (reported by user)
     */
    public function listPendingReturn()
    {
        $this->requirePermission('manage');
        $stmt = $this->conn->query("
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
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        $booking = $this->getById($bookingId);
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

        $stmt = $this->conn->prepare("
            UPDATE cb_bookings 
            SET status = 'pending_return',
                user_reported_return_at = NOW(),
                actual_return_time = :actual_time,
                return_notes = :notes
            WHERE id = :id
        ");
        $stmt->execute([
            ':actual_time' => $actualReturnTime ?: date('Y-m-d H:i:s'),
            ':notes' => $notes,
            ':id' => $bookingId
        ]);

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

        $booking = $this->getById($bookingId);
        if (!$booking) {
            return $this->jsonError('ไม่พบคำขอ');
        }

        if (!in_array($booking['status'], ['approved', 'in_use', 'pending_return'])) {
            return $this->jsonError('สามารถยืนยันคืนได้เฉพาะคำขอที่อนุมัติแล้ว, กำลังใช้งาน หรือรอยืนยันคืน');
        }

        $stmt = $this->conn->prepare("
            UPDATE cb_bookings 
            SET status = 'completed',
                returned_at = NOW(),
                returned_confirmed_by = :confirmed_by,
                actual_return_time = COALESCE(actual_return_time, :actual_time),
                return_notes = CONCAT(COALESCE(return_notes, ''), :notes)
            WHERE id = :id
        ");
        $stmt->execute([
            ':confirmed_by' => $this->user['email'] ?? $this->user['username'] ?? 'IPCD',
            ':actual_time' => $actualReturnTime ?: date('Y-m-d H:i:s'),
            ':notes' => $notes ? "\n[IPCD] " . $notes : '',
            ':id' => $bookingId
        ]);

        // Log audit
        $this->logAudit('confirm_return', 'booking', $bookingId, ['status' => $booking['status']], [
            'status' => 'completed',
            'confirmed_by' => $this->user['email'] ?? $this->user['username']
        ]);

        // Send notification to requester
        try {
            require_once __DIR__ . '/../../../core/Services/NotificationService.php';

            // Get requester user_id from booking
            $stmtUser = $this->conn->prepare("SELECT u.id FROM users u WHERE u.email = ?");
            $stmtUser->execute([$booking['requester_email']]);
            $requesterId = $stmtUser->fetchColumn();

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
        } catch (\Exception $e) {
            error_log("Notification failed in confirmReturn: " . $e->getMessage());
        }

        return ['success' => true, 'message' => 'ยืนยันคืนรถสำเร็จ'];
    }
}
