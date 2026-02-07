<?php

/**
 * Dormitory Module - Maintenance Controller
 * ระบบแจ้งซ่อม
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../../../core/Services/NotificationService.php';

class MaintenanceController extends DormBaseController
{
    /**
     * รายการหมวดหมู่งานซ่อม
     */
    public function getCategories()
    {
        $stmt = $this->pdo->query("
            SELECT * FROM dorm_maintenance_categories 
            WHERE status = 'active'
            ORDER BY name
        ");
        return $this->success(['categories' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /**
     * รายการแจ้งซ่อมทั้งหมด
     */
    public function list()
    {
        $status = $_GET['status'] ?? null;
        $roomId = $_GET['room_id'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;
        $priority = $_GET['priority'] ?? null;

        $sql = "
            SELECT m.*, 
                   r.room_number, b.code as building_code, b.name as building_name,
                   c.name as category_name, c.icon as category_icon
            FROM dorm_maintenance_requests m
            LEFT JOIN dorm_rooms r ON m.room_id = r.id
            LEFT JOIN dorm_buildings b ON r.building_id = b.id
            LEFT JOIN dorm_maintenance_categories c ON m.category_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if ($status) {
            $sql .= " AND m.status = ?";
            $params[] = $status;
        }
        if ($roomId) {
            $sql .= " AND m.room_id = ?";
            $params[] = $roomId;
        }
        if ($categoryId) {
            $sql .= " AND m.category_id = ?";
            $params[] = $categoryId;
        }
        if ($priority) {
            $sql .= " AND m.priority = ?";
            $params[] = $priority;
        }

        $sql .= " ORDER BY 
            CASE m.priority 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                ELSE 4 
            END,
            m.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->success(['requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /**
     * ดูรายละเอียดแจ้งซ่อม
     */
    public function get($data)
    {
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            return $this->error('กรุณาระบุ ID');
        }

        $stmt = $this->pdo->prepare("
            SELECT m.*, 
                   r.room_number, b.code as building_code, b.name as building_name,
                   c.name as category_name, c.icon as category_icon
            FROM dorm_maintenance_requests m
            LEFT JOIN dorm_rooms r ON m.room_id = r.id
            LEFT JOIN dorm_buildings b ON r.building_id = b.id
            LEFT JOIN dorm_maintenance_categories c ON m.category_id = c.id
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            return $this->error('ไม่พบคำขอแจ้งซ่อม', 404);
        }

        // ดึงประวัติอัพเดท
        $stmt = $this->pdo->prepare("
            SELECT * FROM dorm_maintenance_updates 
            WHERE request_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$id]);
        $request['updates'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ดึงไฟล์แนบ
        $stmt = $this->pdo->prepare("
            SELECT * FROM dorm_maintenance_attachments 
            WHERE request_id = ?
        ");
        $stmt->execute([$id]);
        $request['attachments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['request' => $request]);
    }

    /**
     * สร้างคำขอแจ้งซ่อม
     */
    public function create($data)
    {
        $this->requireAuth();
        $this->requirePermission('edit'); // Require Edit permission to request maintenance

        $required = ['title', 'description', 'requester_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error("กรุณากรอก $field");
            }
        }

        $ticketNumber = $this->generateNumber('MT', 'dorm_maintenance_requests', 'ticket_number');

        $stmt = $this->pdo->prepare("
            INSERT INTO dorm_maintenance_requests 
            (ticket_number, room_id, category_id, requester_id, requester_name, 
             requester_email, requester_phone, title, description, location_detail, priority)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ticketNumber,
            $data['room_id'] ?? null,
            $data['category_id'] ?? null,
            $data['requester_id'] ?? ($this->user['id'] ?? null),
            $data['requester_name'],
            $data['requester_email'] ?? ($this->user['email'] ?? null),
            $data['requester_phone'] ?? null,
            $data['title'],
            $data['description'],
            $data['location_detail'] ?? null,
            $data['priority'] ?? 'medium'
        ]);

        $id = $this->pdo->lastInsertId();

        // เพิ่มอัพเดทแรก
        $stmt = $this->pdo->prepare("
            INSERT INTO dorm_maintenance_updates 
            (request_id, update_type, status_to, comment, updated_by)
            VALUES (?, 'status_change', 'open', 'สร้างคำขอแจ้งซ่อมใหม่', ?)
        ");
        $stmt->execute([$id, $data['requester_name']]);

        $this->logAudit('create_maintenance', 'maintenance', $id, null, $data);

        // Send email notification to admin
        try {
            require_once __DIR__ . '/../../../core/Services/EmailService.php';

            // Get admin and CC emails from system_settings (using dynamic module ID)
            $moduleId = $this->moduleId;
            $settings = EmailService::getModuleSettings($moduleId);
            $adminEmail = $settings['admin_emails'];
            $ccEmails = $settings['cc_emails'];

            if (!empty($adminEmail)) {
                // Get room info for notification
                $roomInfo = 'ไม่ระบุ';
                if (!empty($data['room_id'])) {
                    $stmt = $this->pdo->prepare("
                        SELECT CONCAT(b.code, r.room_number) as room 
                        FROM dorm_rooms r 
                        JOIN dorm_buildings b ON r.building_id = b.id 
                        WHERE r.id = ?
                    ");
                    $stmt->execute([$data['room_id']]);
                    $roomResult = $stmt->fetch(PDO::FETCH_ASSOC);
                    $roomInfo = $roomResult['room'] ?? 'ไม่ระบุ';
                }

                $priorityColors = [
                    'critical' => '#dc2626',
                    'high' => '#ea580c',
                    'medium' => '#f59e0b',
                    'low' => '#10b981'
                ];
                $priorityNames = [
                    'critical' => 'ฉุกเฉิน',
                    'high' => 'สูง',
                    'medium' => 'ปานกลาง',
                    'low' => 'ต่ำ'
                ];

                $priority = $data['priority'] ?? 'medium';
                $priorityColor = $priorityColors[$priority] ?? '#f59e0b';
                $priorityName = $priorityNames[$priority] ?? 'ปานกลาง';

                $subject = "🔧 แจ้งซ่อมใหม่ [{$priorityName}] - {$ticketNumber}";
                $body = "
                    <h2 style='color:#A21D21;'>มีการแจ้งซ่อมใหม่</h2>
                    <table style='width:100%; border-collapse:collapse;'>
                        <tr>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>เลขที่:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>{$ticketNumber}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>ห้อง:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>{$roomInfo}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>หัวข้อ:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>{$data['title']}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>ความเร่งด่วน:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>
                                <span style='background:{$priorityColor}; color:#fff; padding:4px 10px; border-radius:4px;'>{$priorityName}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>รายละเอียด:</strong></td>
                            <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>" . htmlspecialchars($data['description'] ?? '') . "</td>
                        </tr>
                    </table>
                ";

                // Send to all admin emails with CC
                $adminList = array_map('trim', explode(',', $adminEmail));
                foreach ($adminList as $email) {
                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        EmailService::sendTestEmail($email, $subject, $body);
                    }
                }
            }
        } catch (Exception $notifyError) {
            // Email failure should not break the maintenance request flow
            error_log('Maintenance notification failed: ' . $notifyError->getMessage());
        }

        // In-app notification to admins
        $this->notifyDormAdmins(
            'info',
            'มีแจ้งซ่อมใหม่',
            "{$ticketNumber}: {$data['title']}",
            "Modules/Dormitory/?page=maintenance"
        );

        return $this->success([
            'id' => $id,
            'ticket_number' => $ticketNumber
        ], 'สร้างคำขอแจ้งซ่อมสำเร็จ');
    }

    /**
     * อัพเดทสถานะ
     */
    public function updateStatus($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage'); // Require Manage permission

        $id = $data['id'] ?? null;
        $newStatus = $data['status'] ?? null;

        if (!$id || !$newStatus) {
            return $this->error('กรุณาระบุ ID และสถานะใหม่');
        }

        $validStatuses = ['open', 'assigned', 'in_progress', 'pending_parts', 'resolved', 'closed', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->error('สถานะไม่ถูกต้อง');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM dorm_maintenance_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            return $this->error('ไม่พบคำขอแจ้งซ่อม', 404);
        }

        $oldStatus = $request['status'];

        $this->pdo->beginTransaction();
        try {
            // อัพเดทสถานะ
            $updateFields = ['status = ?'];
            $updateParams = [$newStatus];

            if ($newStatus === 'resolved') {
                $updateFields[] = 'resolved_at = NOW()';
            }
            if ($newStatus === 'closed') {
                $updateFields[] = 'closed_at = NOW()';
            }
            if (!empty($data['assigned_to'])) {
                $updateFields[] = 'assigned_to = ?';
                $updateParams[] = $data['assigned_to'];
            }
            if (isset($data['actual_cost'])) {
                $updateFields[] = 'actual_cost = ?';
                $updateParams[] = $data['actual_cost'];
            }

            $updateParams[] = $id;

            $stmt = $this->pdo->prepare("
                UPDATE dorm_maintenance_requests 
                SET " . implode(', ', $updateFields) . "
                WHERE id = ?
            ");
            $stmt->execute($updateParams);

            // เพิ่มประวัติอัพเดท
            $stmt = $this->pdo->prepare("
                INSERT INTO dorm_maintenance_updates 
                (request_id, update_type, status_from, status_to, comment, updated_by)
                VALUES (?, 'status_change', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $oldStatus,
                $newStatus,
                $data['comment'] ?? null,
                $this->user['name'] ?? 'System'
            ]);

            $this->pdo->commit();
            $this->logAudit('update_maintenance_status', 'maintenance', $id, ['status' => $oldStatus], ['status' => $newStatus]);

            // Notify requester about status change
            if (!empty($request['requester_id'])) {
                $statusLabels = [
                    'open' => 'เปิด',
                    'assigned' => 'มอบหมายแล้ว',
                    'in_progress' => 'กำลังดำเนินการ',
                    'pending_parts' => 'รออะไหล่',
                    'resolved' => 'แก้ไขแล้ว',
                    'closed' => 'ปิดงาน',
                    'cancelled' => 'ยกเลิก'
                ];
                $statusLabel = $statusLabels[$newStatus] ?? $newStatus;
                NotificationService::create(
                    $request['requester_id'],
                    in_array($newStatus, ['resolved', 'closed']) ? 'success' : 'info',
                    'อัปเดตสถานะแจ้งซ่อม',
                    "{$request['ticket_number']} สถานะ: {$statusLabel}",
                    [],
                    "Modules/Dormitory/?page=request_history"
                );
            }

            return $this->success([], 'อัพเดทสถานะสำเร็จ');
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * เพิ่มความคิดเห็น
     */
    public function addComment($data)
    {
        $id = $data['id'] ?? $data['request_id'] ?? null;
        $comment = $data['comment'] ?? null;

        if (!$id || !$comment) {
            return $this->error('กรุณาระบุ ID และความคิดเห็น');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO dorm_maintenance_updates 
            (request_id, update_type, comment, updated_by)
            VALUES (?, 'comment', ?, ?)
        ");
        $stmt->execute([
            $id,
            $comment,
            $data['updated_by'] ?? $this->user['name'] ?? 'Anonymous'
        ]);

        return $this->success([], 'เพิ่มความคิดเห็นสำเร็จ');
    }

    /**
     * มอบหมายงาน
     */
    public function assign($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage'); // Require Manage permission

        $id = $data['id'] ?? null;
        $assignedTo = $data['assigned_to'] ?? null;

        if (!$id || !$assignedTo) {
            return $this->error('กรุณาระบุ ID และผู้รับผิดชอบ');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM dorm_maintenance_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            return $this->error('ไม่พบคำขอแจ้งซ่อม', 404);
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE dorm_maintenance_requests 
                SET assigned_to = ?, status = 'assigned'
                WHERE id = ?
            ");
            $stmt->execute([$assignedTo, $id]);

            $stmt = $this->pdo->prepare("
                INSERT INTO dorm_maintenance_updates 
                (request_id, update_type, status_from, status_to, comment, updated_by)
                VALUES (?, 'assignment', ?, 'assigned', ?, ?)
            ");
            $stmt->execute([
                $id,
                $request['status'],
                "มอบหมายงานให้ $assignedTo",
                $this->user['name'] ?? 'System'
            ]);

            $this->pdo->commit();
            $this->logAudit('assign_maintenance', 'maintenance', $id, null, ['assigned_to' => $assignedTo]);

            return $this->success([], 'มอบหมายงานสำเร็จ');
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * สรุปสถิติงานซ่อม
     */
    public function stats()
    {
        $stats = [];

        // สรุปตามสถานะ
        $stmt = $this->pdo->query("
            SELECT status, COUNT(*) as count
            FROM dorm_maintenance_requests
            GROUP BY status
        ");
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // สรุปตาม priority
        $stmt = $this->pdo->query("
            SELECT priority, COUNT(*) as count
            FROM dorm_maintenance_requests
            WHERE status NOT IN ('closed', 'cancelled')
            GROUP BY priority
        ");
        $stats['by_priority'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // สรุปตามหมวดหมู่
        $stmt = $this->pdo->query("
            SELECT c.name, COUNT(m.id) as count
            FROM dorm_maintenance_requests m
            LEFT JOIN dorm_maintenance_categories c ON m.category_id = c.id
            WHERE m.status NOT IN ('closed', 'cancelled')
            GROUP BY m.category_id
        ");
        $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // งานที่รอดำเนินการ
        $stmt = $this->pdo->query("
            SELECT COUNT(*) FROM dorm_maintenance_requests 
            WHERE status IN ('open', 'assigned')
        ");
        $stats['pending'] = $stmt->fetchColumn();

        // งานที่กำลังดำเนินการ
        $stmt = $this->pdo->query("
            SELECT COUNT(*) FROM dorm_maintenance_requests 
            WHERE status = 'in_progress'
        ");
        $stats['in_progress'] = $stmt->fetchColumn();

        // งานที่เสร็จแล้วเดือนนี้
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM dorm_maintenance_requests 
            WHERE status IN ('resolved', 'closed')
            AND MONTH(resolved_at) = MONTH(CURRENT_DATE())
            AND YEAR(resolved_at) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute();
        $stats['resolved_this_month'] = $stmt->fetchColumn();

        return $this->success(['stats' => $stats]);
    }
}
