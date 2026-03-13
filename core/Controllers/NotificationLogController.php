<?php

/**
 * Notification Log Controller
 * API endpoints for viewing unified notification history (Email & In-App)
 */

require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Config/SessionConfig.php';

class NotificationLogController
{
    private $conn;
    private $user;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();

        if (function_exists('startOptimizedSession')) {
            startOptimizedSession();
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
        }
        $this->user = $_SESSION['user'] ?? null;
    }

    public function processRequest()
    {
        header('Content-Type: application/json');

        $action = $_GET['action'] ?? 'list';

        if (!$this->user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        if (!$this->hasPermission()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        switch ($action) {
            case 'list':
                $this->listLogs();
                break;
            case 'stats':
                $this->getStats();
                break;
            case 'detail':
                $this->getDetail();
                break;
            default:
                $this->listLogs();
        }
    }

    private function hasPermission()
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT COALESCE(p.can_view, 0) as can_view 
                FROM core_modules cm
                LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = :role_id
                WHERE cm.code = 'NOTIFICATION_LOGS'
                LIMIT 1
            ");
            $stmt->bindValue(':role_id', $this->user['role_id'] ?? 0, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && $row['can_view'];
        } catch (Exception $e) {
            return false;
        }
    }

    private function listLogs()
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;

            $type = $_GET['type'] ?? '';
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';

            $conditions = ["1=1"];
            $params = [];

            if ($type) {
                $conditions[] = "type = :type";
                $params[':type'] = $type;
            }

            if ($status) {
                // In 'notifications' table, we treat all as 'success' for history purposes
                if ($status !== 'success') {
                    $conditions[] = "0=1"; // Force empty if searching for 'failed' in notifications
                }
            }

            if ($search) {
                $conditions[] = "(title LIKE :search OR message LIKE :search2)";
                $params[':search'] = "%$search%";
                $params[':search2'] = "%$search%";
            }

            if ($startDate) {
                $conditions[] = "DATE(created_at) >= :start_date";
                $params[':start_date'] = $startDate;
            }

            if ($endDate) {
                $conditions[] = "DATE(created_at) <= :end_date";
                $params[':end_date'] = $endDate;
            }

            $whereClause = implode(' AND ', $conditions);

            // Count total
            $countSql = "SELECT COUNT(*) FROM notifications WHERE $whereClause";
            $countStmt = $this->conn->prepare($countSql);
            foreach ($params as $key => $val) {
                $countStmt->bindValue($key, $val);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            // Get logs
            $sql = "SELECT n.id, n.user_id, n.type, u.fullname as recipient_name, u.EmpCode as recipient_code, 
                           n.title, n.message, n.data, n.link, n.is_read, n.read_at, n.created_at 
                    FROM notifications n
                    LEFT JOIN users u ON u.id = n.user_id
                    WHERE $whereClause 
                    ORDER BY n.created_at DESC 
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode([
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function getStats()
    {
        try {
            $stats = [];

            // Total
            $stmt = $this->conn->query("SELECT COUNT(*) FROM notifications");
            $stats['total'] = (int)$stmt->fetchColumn();

            // Read count
            $stmt = $this->conn->query("SELECT COUNT(*) FROM notifications WHERE is_read = 1");
            $stats['success'] = (int)$stmt->fetchColumn(); // Map read to success for UI consistency

            // Unread count
            $stmt = $this->conn->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
            $stats['failed'] = (int)$stmt->fetchColumn(); // Map unread to failed for UI consistency

            // Today count
            $stmt = $this->conn->query("SELECT COUNT(*) FROM notifications WHERE DATE(created_at) = CURDATE()");
            $stats['today'] = (int)$stmt->fetchColumn();

            // Counts by type
            $stmt = $this->conn->query("SELECT type, COUNT(*) as count FROM notifications GROUP BY type");
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function getDetail()
    {
        try {
            $id = (int)($_GET['id'] ?? 0);
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID required']);
                return;
            }

            $stmt = $this->conn->prepare("
                SELECT n.id, n.user_id, n.type, u.fullname as recipient_name, u.EmpCode as recipient_code, n.title, n.message, 'success' as status, n.data, n.link, n.is_read, n.read_at, n.created_at 
                FROM notifications n
                LEFT JOIN users u ON u.id = n.user_id
                WHERE n.id = :id
            ");

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $log = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$log) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Notification not found']);
                return;
            }

            echo json_encode(['success' => true, 'data' => $log]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
