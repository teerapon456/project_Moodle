<?php

/**
 * Email Log Controller
 * API endpoints for viewing email sending history
 */

require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Config/SessionConfig.php';

class EmailLogController
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

        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        // Ensure table exists
        $this->ensureTableExists();

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

    private function ensureTableExists()
    {
        try {
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS `email_logs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `recipient_email` VARCHAR(255) NOT NULL,
                    `subject` VARCHAR(500),
                    `body_preview` TEXT,
                    `body_html` LONGTEXT,
                    `status` VARCHAR(20) DEFAULT 'pending',
                    `error_message` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("Could not create email_logs table: " . $e->getMessage());
        }
    }

    private function isAdmin()
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT COALESCE(p.can_view, 0) as can_view 
                FROM core_modules cm
                LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = :role_id
                WHERE cm.code = 'EMAIL_LOGS'
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

            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';

            $conditions = ["1=1"];
            $params = [];

            if ($status) {
                $conditions[] = "status = :status";
                $params[':status'] = $status;
            }

            if ($search) {
                $conditions[] = "(recipient_email LIKE :search OR subject LIKE :search2)";
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
            $countSql = "SELECT COUNT(*) FROM email_logs WHERE $whereClause";
            $countStmt = $this->conn->prepare($countSql);
            foreach ($params as $key => $val) {
                $countStmt->bindValue($key, $val);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            // Get logs
            $sql = "SELECT id, recipient_email, subject, body_preview, status, error_message, created_at 
                    FROM email_logs 
                    WHERE $whereClause 
                    ORDER BY created_at DESC 
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

            // Total emails
            $stmt = $this->conn->query("SELECT COUNT(*) FROM email_logs");
            $stats['total'] = (int)$stmt->fetchColumn();

            // Success count (includes 'sent' and 'success')
            $stmt = $this->conn->query("SELECT COUNT(*) FROM email_logs WHERE status IN ('success', 'sent')");
            $stats['success'] = (int)$stmt->fetchColumn();

            // Failed count
            $stmt = $this->conn->query("SELECT COUNT(*) FROM email_logs WHERE status NOT IN ('success', 'sent', 'pending')");
            $stats['failed'] = (int)$stmt->fetchColumn();

            // Today count
            $stmt = $this->conn->query("SELECT COUNT(*) FROM email_logs WHERE DATE(created_at) = CURDATE()");
            $stats['today'] = (int)$stmt->fetchColumn();

            // Last 7 days trend
            $stmt = $this->conn->query("
                SELECT DATE(created_at) as date, 
                       SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                       SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM email_logs 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at) 
                ORDER BY date
            ");
            $stats['trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

            $stmt = $this->conn->prepare("SELECT * FROM email_logs WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $log = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$log) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Log not found']);
                return;
            }

            echo json_encode(['success' => true, 'data' => $log]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
