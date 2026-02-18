<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../../../core/Services/EmailService.php';
require_once __DIR__ . '/../../../core/Services/NotificationService.php';
require_once __DIR__ . '/../../../core/Helpers/UrlHelper.php';
require_once __DIR__ . '/../Models/BookingModel.php';

class BookingController extends DormBaseController
{
    private $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new BookingModel($this->pdo);
    }

    /**
     * Show User Request Form
     */
    /**
     * Get Data for User Request Form (called by View)
     */
    public function getBookingFormData()
    {
        $this->requireAuth();
        $user = $this->user;
        $userId = $user['id'];

        // Fetch Room Types for dropdown
        $roomTypes = $this->bookingModel->getRoomTypes();

        // Check if user has current active room
        $currentOccupancy = $this->bookingModel->getCurrentOccupancy($userId);

        // Fetch User's Request History
        $myRequests = $this->bookingModel->getUserReservations($userId);

        // Fetch Maintenance History (Active Room)
        $myMaintenanceRequests = [];
        if (!empty($currentOccupancy['room_id'])) {
            $myMaintenanceRequests = $this->bookingModel->getMaintenanceRequestsByRoom($currentOccupancy['room_id']);
        }

        // Check for specific pending requests
        $hasPendingMoveIn = false;
        foreach ($myRequests as $req) {
            if ($req['request_type'] == 'move_in' && $req['status'] == 'pending') {
                $hasPendingMoveIn = true;
                break;
            }
        }

        // Map user data for view
        $userData = [
            'fullname' => $user['fullname'],
            'department' => $user['department'] ?? '-',
            'position' => $user['position'] ?? '-',
            'start_date' => $user['start_date'] ?? '-'
        ];

        return compact('userData', 'roomTypes', 'currentOccupancy', 'myRequests', 'myMaintenanceRequests', 'hasPendingMoveIn');
    }

    /**
     * Get Data for Request History Page
     */
    public function getRequestHistoryData()
    {
        $this->requireAuth();
        $user = $this->user;

        // Fetch User's Request History
        $myRequests = $this->bookingModel->getUserReservations($user['id']);

        // Fetch User's Maintenance History (by requester_id to show all history)
        $myMaintenanceRequests = $this->bookingModel->getMaintenanceRequestsByUser($user['id']);

        return compact('myRequests', 'myMaintenanceRequests');
    }

    /**
     * Show Admin Management Page
     */
    public function manage()
    {
        $this->requireAuth();
        $this->requirePermission('manage'); // Staff or Manager or Admin

        require_once __DIR__ . '/../Views/booking_manage.php';
    }

    /**
     * API: Submit Request
     */
    /**
     * API: Submit Request
     */
    public function store($input)
    {
        $this->requireAuth();
        $this->requirePermission('edit'); // Require Edit permission to request
        $userId = $this->user['id'];

        $requestType = $input['request_type'] ?? 'move_in';

        // Map inputs based on request type
        $reason = $input['reason'] ?? '';
        $checkInDate = $input['check_in_date'] ?? '';
        $roomTypePref = $input['room_type_preference'] ?? null;

        switch ($requestType) {
            case 'move_out':
                $reason = $input['move_out_reason'] ?? '';
                $checkInDate = $input['move_out_date'] ?? '';
                break;
            case 'change_room':
                $reason = $input['change_reason'] ?? '';
                $checkInDate = $input['change_date'] ?? '';
                $roomTypePref = $input['change_room_type'] ?? null;
                break;
            case 'add_relative':
                $reason = $input['add_relative_reason'] ?? '';
                break;
        }

        if (empty($checkInDate)) {
            $checkInDate = date('Y-m-d');
        }

        // Server-side Validation for Move In
        if ($requestType === 'move_in') {
            // Check active occupancy
            if ($this->bookingModel->hasActiveOccupancy($userId)) {
                return $this->error('คุณมีห้องพักอยู่แล้ว ไม่สามารถขอเข้าพักใหม่ได้');
            }

            // Check pending request
            if ($this->bookingModel->hasPendingRequest($userId, 'move_in')) {
                return $this->error('คุณมีคำขอเข้าพักที่รอการอนุมัติอยู่แล้ว');
            }
        }

        // Server-side Validation for Move Out / Change Room / Add Relative / Remove Relative
        if (in_array($requestType, ['move_out', 'change_room', 'add_relative', 'remove_relative'])) {
            // Check active occupancy - must have a room for these actions
            if (!$this->bookingModel->hasActiveOccupancy($userId)) {
                return $this->error('คุณยังไม่มีห้องพัก ไม่สามารถดำเนินการนี้ได้');
            }
        }

        // For remove_relative: validate that indices are provided
        $removeRelativeIndices = null;
        if ($requestType === 'remove_relative') {
            if (!empty($input['remove_relative_indices'])) {
                $removeRelativeIndices = $input['remove_relative_indices'];
            }
        }

        // Relative Info - now supports multiple relatives via JSON array
        $hasRelative = isset($input['has_relative']) ? 1 : 0;
        $relativeDetails = null;
        $relativesCount = 0;

        // For move_in - relatives_json, For add_relative - add_relatives_json
        if ($hasRelative && !empty($input['relatives_json'])) {
            $relativesData = json_decode($input['relatives_json'], true);
            if (is_array($relativesData) && count($relativesData) > 0) {
                $relativeDetails = $input['relatives_json'];
                $relativesCount = count($relativesData);
            } else {
                $hasRelative = 0;
            }
        }

        // For add_relative request type
        if ($requestType === 'add_relative' && !empty($input['add_relatives_json'])) {
            $relativesData = json_decode($input['add_relatives_json'], true);
            if (is_array($relativesData) && count($relativesData) > 0) {
                // Check Capacity BEFORE accepting request
                $occupancy = $this->bookingModel->getCurrentOccupancy($userId);
                if ($occupancy) {
                    require_once __DIR__ . '/../Services/RoomService.php';
                    $roomService = new RoomService($this->pdo); // Just to reuse getRoom/count logic if public, or manual check
                    // Manual check for efficiency
                    $roomId = $occupancy['room_id'];
                    $room = $this->bookingModel->getRoomById($roomId); // Need this method in model or use raw query
                    if ($room) {
                        // Get current occupants count
                        // Quick query to count occupants in this room
                        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(1 + COALESCE(accompanying_persons, 0)), 0) FROM dorm_occupancies WHERE room_id = ? AND status = 'active'");
                        $stmt->execute([$roomId]);
                        $currentCount = $stmt->fetchColumn();

                        $addCount = count($relativesData);

                        if (($currentCount + $addCount) > $room['capacity']) {
                            return $this->error("ห้องพักของคุณเต็มแล้ว (ความจุ: {$room['capacity']}, ปัจจุบัน: $currentCount) ไม่สามารถเพิ่มญาติได้");
                        }
                    }
                }

                $hasRelative = 1;
                $relativeDetails = $input['add_relatives_json'];
                $relativesCount = count($relativesData);
            }
            // Store reason
            if (!empty($input['add_relative_reason'])) {
                $reason = $input['add_relative_reason'];
            }
        }

        // File Uploads
        $documentPaths = [];
        $uploadDir = __DIR__ . '/../public/uploads/dorm_docs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileKeys = ['id_card', 'house_reg', 'marriage_cert', 'birth_cert', 'passport'];
        foreach ($fileKeys as $key) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                // Create sub-directory for this doc type
                $typeDir = $uploadDir . $key . '/';
                if (!is_dir($typeDir)) mkdir($typeDir, 0777, true);

                $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);

                // Naming: Username_Date_DocType_Unique
                $username = !empty($this->user['username']) ? $this->user['username'] : $userId;
                $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', $username);
                $date = date('Y-m-d');
                $unique = uniqid();

                $filename = "{$cleanName}_{$date}_{$key}_{$unique}.{$ext}";

                if (move_uploaded_file($_FILES[$key]['tmp_name'], $typeDir . $filename)) {
                    // Store strict relative path (assumes public/ is webroot)
                    // For Module assets, we need to prefix with Module name if serving via Modules path
                    $documentPaths[$key] = 'Modules/Dormitory/public/uploads/dorm_docs/' . $key . '/' . $filename;
                }
            }
        }

        // Insert into DB
        try {
            $requestId = $this->bookingModel->createReservation([
                ':requester_id' => $userId,
                ':request_type' => $requestType,
                ':reason' => $reason,
                ':room_type_preference' => $roomTypePref,
                ':has_relative' => $hasRelative,
                ':relative_details' => $relativeDetails,
                ':document_paths' => json_encode($documentPaths, JSON_UNESCAPED_UNICODE),
                ':check_in' => $checkInDate
            ]);

            // Send Email Notification
            $this->logAudit('create_request', 'dorm_reservation', $requestId);
            EmailService::sendDormRequestReceived($this->user['email'], $this->user['fullname'], $requestType);

            // Notify Admin (Hardcoded or Settings)
            EmailService::sendDormRequestNotificationToAdmin([
                'fullname' => $this->user['fullname'],
                'request_type' => $requestType
            ]);
            // Notify admins via in-app notification
            $requestTypeLabel = match ($requestType) {
                'move_in' => 'ขอเข้าพัก',
                'move_out' => 'ขอย้ายออก',
                'change_room' => 'ขอเปลี่ยนห้อง',
                'add_relative' => 'ขอเพิ่มญาติ',
                'remove_relative' => 'ขอนำญาติออก',
                default => $requestType
            };
            $this->notifyDormAdmins(
                'info',
                'มีคำขอหอพักใหม่',
                "คำขอ#{$requestId}: {$this->user['fullname']} {$requestTypeLabel}",
                "Modules/Dormitory/?page=requests"
            );

            return $this->success([], 'ส่งคำขอเรียบร้อยแล้ว');
        } catch (PDOException $e) {
            return $this->error('Database Error: ' . $e->getMessage());
        }
    }

    /**
     * API: Get Request List (for Admin)
     */
    public function listRequests()
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $status = $_GET['status'] ?? 'pending';
        $requests = $this->bookingModel->getRequestsByStatus($status);

        // Enrich change_room and move_out requests with current occupancy relative data
        foreach ($requests as &$req) {
            if (in_array($req['request_type'], ['change_room', 'move_out'])) {
                $occupancy = $this->bookingModel->getCurrentOccupancy($req['requester_id']);
                if ($occupancy) {
                    // Store occupancy relative info so admin can see it
                    $req['occupancy_accompanying_persons'] = $occupancy['accompanying_persons'] ?? 0;
                    $req['occupancy_accompanying_details'] = $occupancy['accompanying_details'] ?? null;
                    $req['current_room_number'] = $occupancy['room_number'] ?? '';
                    $req['current_building_name'] = $occupancy['building_name'] ?? '';
                }
            }
        }
        unset($req);

        return $this->success(['requests' => $requests]);
    }

    /**
     * API: Get Available Rooms
     */
    public function getAvailableRooms()
    {
        try {
            $this->requireAuth();
            $this->requirePermission('manage');

            $availableRooms = $this->bookingModel->getAvailableRooms();

            return $this->success(['data' => $availableRooms]);
        } catch (Exception $e) {
            return $this->error('Error fetching rooms: ' . $e->getMessage());
        }
    }

    /**
     * API: Approve Request
     */
    /**
     * API: Approve Request
     */
    public function approve($input)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $requestId = $input['id'];
        $roomId = !empty($input['room_id']) ? $input['room_id'] : null;
        $keyDate = $input['key_pickup_date'];
        $adminRemark = $input['admin_remark'] ?? '';

        try {
            $this->pdo->beginTransaction();

            // Check current status first
            $currentStatus = $this->bookingModel->getReservationById($requestId)['status'] ?? null;

            if ($currentStatus !== 'pending') {
                $this->pdo->rollBack();
                return $this->error('คำขอนี้ถูกดำเนินการไปแล้ว');
            }

            // Update Reservation
            $this->bookingModel->approveReservation($requestId, $roomId, $keyDate, $adminRemark, $this->user['id']);

            // Get Request Details for Email
            $request = $this->bookingModel->getReservationWithUser($requestId);

            // If Move In -> Create Occupancy using RoomService
            if ($request['request_type'] == 'move_in') {
                require_once __DIR__ . '/../Services/RoomService.php';
                $roomService = new RoomService($this->pdo);

                // Parse accompanying persons data from request
                $accompanyingPersons = 0;
                $accompanyingDetails = null;
                if ($request['has_relative'] && !empty($request['relative_details'])) {
                    $relativesData = json_decode($request['relative_details'], true);
                    if (is_array($relativesData)) {
                        $accompanyingPersons = count($relativesData);
                        $accompanyingDetails = $request['relative_details'];
                    }
                }

                $userData = [
                    'employee_id' => $request['requester_id'],
                    'employee_name' => $request['fullname'],
                    'employee_email' => $request['email'],
                    'department' => $request['department'],
                    'check_in_date' => $keyDate,
                    'accompanying_persons' => $accompanyingPersons,
                    'accompanying_details' => $accompanyingDetails
                ];

                try {
                    $roomService->checkIn($roomId, $userData, $this->user['id']);
                } catch (Exception $e) {
                    // Logic error (e.g. room full) - should rollback
                    $this->pdo->rollBack();
                    return $this->error($e->getMessage());
                }
            } elseif ($request['request_type'] == 'move_out') {
                // ... (Existing Move Out Logic) ...
                require_once __DIR__ . '/../Services/RoomService.php';
                $roomService = new RoomService($this->pdo);

                // Find active occupancy for this user
                $occupancy = $this->bookingModel->getCurrentOccupancy($request['requester_id']);

                if (!$occupancy) {
                    // Fallback
                    $stmt = $this->pdo->prepare("SELECT * FROM dorm_occupancies WHERE employee_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
                    $stmt->execute([$request['requester_id']]);
                    $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                if ($occupancy) {
                    try {
                        $roomService->checkOut($occupancy['id'], $keyDate);
                    } catch (Exception $e) {
                        $this->pdo->rollBack();
                        return $this->error('CheckOut Error: ' . $e->getMessage());
                    }
                } else {
                    $this->pdo->rollBack();
                    return $this->error("ไม่พบข้อมูลการเข้าพักปัจจุบันของผู้ใช้นี้ - ไม่สามารถดำเนินการย้ายออกได้");
                }
            } elseif ($request['request_type'] == 'change_room') {
                // Change Room Logic
                require_once __DIR__ . '/../Services/RoomService.php';
                $roomService = new RoomService($this->pdo);

                $occupancy = $this->bookingModel->getCurrentOccupancy($request['requester_id']);
                if (!$occupancy) {
                    // Fallback
                    $stmt = $this->pdo->prepare("SELECT * FROM dorm_occupancies WHERE employee_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
                    $stmt->execute([$request['requester_id']]);
                    $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                if ($occupancy) {
                    try {
                        // Change Room (Move user + existing relatives)
                        // If request has NEW relative details, we might need to merge or replace?
                        // User requirement: "Take relatives with them". changeRoom service method copies existing data.
                        // If they wanted to add/remove relatives during move, they should have done add/remove request? 
                        // Or maybe change_room form allows updating relatives? 
                        // Assuming standard "move everyone" for now as per `changeRoom` implementation.
                        $roomService->changeRoom($occupancy['id'], $roomId, $keyDate, $this->user['id']);
                    } catch (Exception $e) {
                        $this->pdo->rollBack();
                        return $this->error('ChangeRoom Error: ' . $e->getMessage());
                    }
                } else {
                    $this->pdo->rollBack();
                    return $this->error("ไม่พบข้อมูลการเข้าพักปัจจุบัน - ไม่สามารถย้ายห้องได้");
                }
            } elseif ($request['request_type'] == 'add_relative') {
                // Add Relative Logic
                require_once __DIR__ . '/../Services/RoomService.php';
                $roomService = new RoomService($this->pdo);

                $occupancy = $this->bookingModel->getCurrentOccupancy($request['requester_id']);
                if (!$occupancy) {
                    // Fallback
                    $stmt = $this->pdo->prepare("SELECT * FROM dorm_occupancies WHERE employee_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
                    $stmt->execute([$request['requester_id']]);
                    $occupancy = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                if ($occupancy) {
                    try {
                        // Update Room ID to current room (since we don't select new room)
                        $roomId = $occupancy['room_id'];
                        // Update reservation record to reflect the actual room used (current room)
                        // We need to re-execute the update statement in approveReservation? 
                        // Actually approveReservation updates room_id. We should pass the current room id to it.
                        // But wait, approveReservation is caled BEFORE this block at line 308.
                        // So at line 308, $roomId must be set correctly.
                        // Logic fix: For add_relative, client might send empty room_id. We must fetch current room ID BEFORE line 308?
                        // OR: We update it again here? 
                        // Better: In the beginning of `approve` method, if type is add_relative, force $roomId = current_occupancy_room_id.

                        // But wait, if I change it here, line 308 already ran.
                        // I'll update lines 308 execution flow in a separate edit if needed, or just update it again here manually? 
                        // Actually, let's just use `addRelative` service. `approveReservation` stores the decision. 
                        // If `approveReservation` stored NULL or empty for valid `add_relative`, it's messy but not fatal for the `dorm_reservations` table (just log).
                        // The critical part is `dorm_occupancies`.

                        $roomService->addRelative($occupancy['id'], $request['relative_details']);

                        // Fix the reservation record to show correct room
                        $this->bookingModel->approveReservation($requestId, $roomId, $keyDate, $adminRemark, $this->user['id']);
                    } catch (Exception $e) {
                        $this->pdo->rollBack();
                        return $this->error('AddRelative Error: ' . $e->getMessage());
                    }
                } else {
                    $this->pdo->rollBack();
                    return $this->error("ไม่พบข้อมูลการเข้าพักปัจจุบัน - ไม่สามารถเพิ่มญาติได้");
                }
            }

            $this->pdo->commit();

            // Send Email
            try {
                if ($request) {
                    EmailService::sendDormRequestApproved($request['email'], $request['fullname'], $request['request_type'], $keyDate, $adminRemark);
                }
            } catch (Exception $emailEx) {
                error_log("Email sending failed: " . $emailEx->getMessage());
            }

            // Audit Log
            $this->logAudit('approve_request', 'dorm_reservation', $requestId, ['status' => 'pending'], ['status' => 'approved', 'room_id' => $roomId]);

            // Real-time notification (non-blocking) - ข้อความตามประเภทคำขอ
            $approveMessage = match ($request['request_type']) {
                'move_in' => 'คำขอเข้าพักได้รับการอนุมัติ กรุณารับกุญแจ ' . $keyDate,
                'move_out' => 'คำขอย้ายออกได้รับการอนุมัติแล้ว',
                'change_room' => 'คำขอเปลี่ยนห้องได้รับการอนุมัติแล้ว' . ($keyDate ? ' วันที่ดำเนินการ: ' . $keyDate : ''),
                'add_relative' => 'คำขอเพิ่มญาติได้รับการอนุมัติแล้ว',
                'remove_relative' => 'คำขอนำญาติออกได้รับการอนุมัติแล้ว',
                default => 'คำขอหอพักได้รับการอนุมัติแล้ว',
            };
            try {
                NotificationService::create(
                    $request['requester_id'],
                    'success',
                    'อนุมัติคำขอหอพักแล้ว',
                    $approveMessage,
                    ['request_id' => $requestId],
                    \Core\Helpers\UrlHelper::getBaseUrl() . '/Modules/Dormitory/?page=my-room'
                );
            } catch (Exception $notifEx) {
                error_log("Notification failed: " . $notifEx->getMessage());
            }

            return $this->success([], 'อนุมัติคำขอเรียบร้อยแล้ว');
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->error('Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
        }
    }

    /**
     * API: Reject Request
     */
    public function reject($input)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $requestId = $input['id'];
        $reason = $input['reject_reason'];

        try {
            $this->pdo->beginTransaction();

            // Check status (Lock row)
            $reservation = $this->bookingModel->getReservationById($requestId, true);
            $currentStatus = $reservation['status'] ?? null;

            if ($currentStatus !== 'pending') {
                $this->pdo->rollBack();
                return $this->error('คำขอนี้ถูกดำเนินการไปแล้ว');
            }

            $this->bookingModel->updateStatus($requestId, 'rejected', $reason, $this->user['id']);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('Error: ' . $e->getMessage());
        }

        // Email (After commit)
        $request = $this->bookingModel->getReservationWithUser($requestId);

        if ($request) {
            EmailService::sendDormRequestRejected($request['email'], $request['fullname'], $request['request_type'], $reason);
        }

        // Audit Log
        $this->logAudit('reject_request', 'dorm_reservation', $requestId, ['status' => 'pending'], ['status' => 'rejected', 'reason' => $reason]);

        // Real-time notification - ข้อความตามประเภทคำขอ
        if ($request) {
            $rejectLabel = match ($request['request_type']) {
                'move_in' => 'เข้าพัก',
                'move_out' => 'ย้ายออก',
                'change_room' => 'เปลี่ยนห้อง',
                'add_relative' => 'เพิ่มญาติ',
                'remove_relative' => 'นำญาติออก',
                default => 'หอพัก',
            };
            NotificationService::create(
                $request['requester_id'],
                'error',
                'คำขอหอพักถูกปฏิเสธ',
                'คำขอ' . $rejectLabel . 'ถูกปฏิเสธ: ' . mb_substr($reason, 0, 50),
                ['request_id' => $requestId],
                \Core\Helpers\UrlHelper::getBaseUrl() . '/Modules/Dormitory/?page=booking_form'
            );
        }

        return $this->success([], 'ปฏิเสธคำขอเรียบร้อยแล้ว');
    }

    /**
     * API: Cancel Request (User Side)
     */
    public function cancel($input)
    {
        $this->requireAuth();

        $requestId = $input['id'] ?? null;
        $reason = $input['reason'] ?? 'User cancelled';

        if (!$requestId) {
            return $this->error('กรุณาระบุ ID คำขอ');
        }

        try {
            $this->pdo->beginTransaction();

            // Check Ownership & Status
            $request = $this->bookingModel->getReservationById($requestId, true);

            if (!$request) {
                $this->pdo->rollBack();
                return $this->error('ไม่พบคำขอ');
            }

            // Must be owner OR Admin
            $isOwner = $request['requester_id'] == $this->user['id'];
            $isAdmin = $this->hasPermission('manage'); // Check if user has manage permission loosely

            if (!$isOwner && !$isAdmin) {
                $this->pdo->rollBack();
                return $this->error('คุณไม่มีสิทธิ์ยกเลิกคำขอนี้');
            }

            if ($request['status'] !== 'pending') {
                $this->pdo->rollBack();
                return $this->error('ไม่สามารถยกเลิกคำขอที่ดำเนินการไปแล้วได้');
            }

            // Update Status
            $this->bookingModel->updateStatus($requestId, 'cancelled', $reason);

            $this->pdo->commit();

            // Audit Log
            $this->logAudit('cancel_request', 'dorm_reservation', $requestId, ['status' => 'pending'], ['status' => 'cancelled', 'reason' => $reason]);

            // Notify admins
            $this->notifyDormAdmins(
                'warning',
                'คำขอหอพักถูกยกเลิก',
                "คำขอ #{$requestId} ถูกยกเลิก: {$reason}",
                "Modules/Dormitory/?page=requests"
            );

            return $this->success([], 'ยกเลิกคำขอเรียบร้อยแล้ว');
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('Error: ' . $e->getMessage());
        }
    }
}
