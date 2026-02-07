<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../../../core/Services/EmailService.php';
require_once __DIR__ . '/../../../core/Services/NotificationService.php';

class BookingController extends DormBaseController
{
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

        // Fetch Room Types for dropdown
        $stmt = $this->pdo->query("SELECT * FROM dorm_room_types WHERE status = 'active'");
        $roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if user has current active room
        $stmt = $this->pdo->prepare("
            SELECT o.*, r.room_number, b.name as building_name 
            FROM dorm_occupancies o
            JOIN dorm_rooms r ON o.room_id = r.id
            JOIN dorm_buildings b ON r.building_id = b.id
            WHERE o.employee_id = ? AND o.status = 'active'
        ");
        $stmt->execute([$user['id']]);
        $currentOccupancy = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch User's Request History
        $stmt = $this->pdo->prepare("
            SELECT * FROM dorm_reservations 
            WHERE requester_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $myRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch User's Maintenance History
        // Fetch Maintenance History (Active Room)
        $myMaintenanceRequests = [];
        if (!empty($currentOccupancy['room_id'])) {
            $stmt = $this->pdo->prepare("
                SELECT m.*, c.name as category_name 
                FROM dorm_maintenance_requests m
                LEFT JOIN dorm_maintenance_categories c ON m.category_id = c.id
                WHERE m.room_id = ? 
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([$currentOccupancy['room_id']]);
            $myMaintenanceRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt = $this->pdo->prepare("
            SELECT * FROM dorm_reservations 
            WHERE requester_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $myRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch User's Maintenance History (by requester_id to show all history)
        $stmt = $this->pdo->prepare("
            SELECT m.*, c.name as category_name 
            FROM dorm_maintenance_requests m
            LEFT JOIN dorm_maintenance_categories c ON m.category_id = c.id
            WHERE m.requester_id = ? 
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $myMaintenanceRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM dorm_occupancies WHERE employee_id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() > 0) {
                return $this->error('คุณมีห้องพักอยู่แล้ว ไม่สามารถขอเข้าพักใหม่ได้');
            }

            // Check pending request
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM dorm_reservations WHERE requester_id = ? AND request_type = 'move_in' AND status = 'pending'");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() > 0) {
                return $this->error('คุณมีคำขอเข้าพักที่รอการอนุมัติอยู่แล้ว');
            }
        }

        // Server-side Validation for Move Out / Change Room / Add Relative / Remove Relative
        if (in_array($requestType, ['move_out', 'change_room', 'add_relative', 'remove_relative'])) {
            // Check active occupancy - must have a room for these actions
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM dorm_occupancies WHERE employee_id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() == 0) {
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
        $uploadDir = __DIR__ . '/../../../public/uploads/dorm_docs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileKeys = ['id_card', 'house_reg', 'marriage_cert', 'birth_cert', 'passport'];
        foreach ($fileKeys as $key) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
                $filename = $userId . '_' . $key . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$key]['tmp_name'], $uploadDir . $filename)) {
                    $documentPaths[$key] = 'public/uploads/dorm_docs/' . $filename;
                }
            }
        }

        // Insert into DB
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO dorm_reservations 
                (requester_id, request_type, reason, room_type_preference, has_relative, relative_details, document_paths, check_in, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $userId,
                $requestType,
                $reason,
                $roomTypePref,
                $hasRelative,
                $relativeDetails,
                json_encode($documentPaths, JSON_UNESCAPED_UNICODE),
                $checkInDate
            ]);

            // Send Email Notification
            $requestId = $this->pdo->lastInsertId();
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

        $sql = "
            SELECT r.*, u.fullname, u.department, u.email, rt.name as room_type_name,
                   ar.room_number as assigned_room_number, ab.name as assigned_building_name
            FROM dorm_reservations r
            JOIN users u ON r.requester_id = u.id
            LEFT JOIN dorm_room_types rt ON r.room_type_preference = rt.id
            LEFT JOIN dorm_rooms ar ON r.room_id = ar.id
            LEFT JOIN dorm_buildings ab ON ar.building_id = ab.id
            WHERE r.status = ?
            ORDER BY r.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

            $sql = "
                SELECT r.id, r.room_number, r.status, r.room_type as type, b.name as building_name, r.capacity,
                       (SELECT COUNT(*) FROM dorm_occupancies o WHERE o.room_id = r.id AND o.status = 'active') as current_occupants
                FROM dorm_rooms r
                JOIN dorm_buildings b ON r.building_id = b.id
                WHERE r.status != 'maintenance'
                ORDER BY b.name, r.room_number
            ";
            $stmt = $this->pdo->query($sql);
            $allRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Filter for available spots
            $availableRooms = [];
            foreach ($allRooms as $room) {
                if ($room['current_occupants'] < $room['capacity']) {
                    $room['free_spots'] = $room['capacity'] - $room['current_occupants'];
                    $availableRooms[] = $room;
                }
            }

            return $this->success(['data' => $availableRooms]);
        } catch (Exception $e) {
            return $this->error('Error fetching rooms: ' . $e->getMessage());
        }
    }

    /**
     * API: Approve Request
     */
    public function approve($input)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        $requestId = $input['id'];
        $roomId = $input['room_id'];
        $keyDate = $input['key_pickup_date'];
        $adminRemark = $input['admin_remark'] ?? '';

        try {
            $this->pdo->beginTransaction();

            // Check current status first
            $stmt = $this->pdo->prepare("SELECT status FROM dorm_reservations WHERE id = ?");
            $stmt->execute([$requestId]);
            $currentStatus = $stmt->fetchColumn();

            if ($currentStatus !== 'pending') {
                $this->pdo->rollBack();
                return $this->error('คำขอนี้ถูกดำเนินการไปแล้ว');
            }

            // Update Reservation
            $stmt = $this->pdo->prepare("
                UPDATE dorm_reservations 
                SET status = 'approved', room_id = ?, key_pickup_date = ?, admin_remark = ?, approver_id = ?, approved_at = NOW(), check_out = NULL
                WHERE id = ?
            ");
            $stmt->execute([$roomId, $keyDate, $adminRemark, $this->user['id'], $requestId]);

            // Get Request Details for Email
            $stmt = $this->pdo->prepare("SELECT r.*, u.email, u.fullname FROM dorm_reservations r JOIN users u ON r.requester_id = u.id WHERE r.id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

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

            // Real-time notification (non-blocking)
            try {
                NotificationService::create(
                    $request['requester_id'],
                    'success',
                    'อนุมัติคำขอหอพักแล้ว',
                    $request['request_type'] == 'move_in' ? 'คำขอเข้าพักได้รับการอนุมัติ กรุณารับกุญแจ ' . $keyDate : 'คำขอย้ายออกได้รับการอนุมัติ',
                    ['request_id' => $requestId],
                    '/dormitory?view=my-room'
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
            $stmt = $this->pdo->prepare("SELECT status FROM dorm_reservations WHERE id = ? FOR UPDATE");
            $stmt->execute([$requestId]);
            $currentStatus = $stmt->fetchColumn();

            if ($currentStatus !== 'pending') {
                $this->pdo->rollBack();
                return $this->error('คำขอนี้ถูกดำเนินการไปแล้ว');
            }

            $stmt = $this->pdo->prepare("
                UPDATE dorm_reservations 
                SET status = 'rejected', cancel_reason = ?, approver_id = ?, approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $this->user['id'], $requestId]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('Error: ' . $e->getMessage());
        }

        // Email (After commit)
        $stmt = $this->pdo->prepare("SELECT r.*, u.email, u.fullname FROM dorm_reservations r JOIN users u ON r.requester_id = u.id WHERE r.id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($request) {
            EmailService::sendDormRequestRejected($request['email'], $request['fullname'], $request['request_type'], $reason);
        }

        // Audit Log
        $this->logAudit('reject_request', 'dorm_reservation', $requestId, ['status' => 'pending'], ['status' => 'rejected', 'reason' => $reason]);

        // Real-time notification
        if ($request) {
            NotificationService::create(
                $request['requester_id'],
                'error',
                'คำขอหอพักถูกปฏิเสธ',
                'คำขอ' . ($request['request_type'] == 'move_in' ? 'เข้าพัก' : 'ย้ายออก') . 'ถูกปฏิเสธ: ' . mb_substr($reason, 0, 50),
                ['request_id' => $requestId],
                '/dormitory?view=booking_form'
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
            $stmt = $this->pdo->prepare("SELECT * FROM dorm_reservations WHERE id = ? FOR UPDATE");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $this->pdo->rollBack();
                return $this->error('ไม่พบคำขอ');
            }

            // Must be owner OR Admin
            // If user is admin/manager, they might want to "Revoke" (handled separately or allow here?)
            // For now, restrict to Owner for consistency with "User Cancel".
            // Adding Admin override capability just in case.
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
            $stmt = $this->pdo->prepare("
                UPDATE dorm_reservations 
                SET status = 'cancelled', cancel_reason = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $requestId]);

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
