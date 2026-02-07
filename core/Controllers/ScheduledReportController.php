<?php

/**
 * Scheduled Report Controller
 * API for managing scheduled reports
 */

require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Config/SessionConfig.php';
require_once __DIR__ . '/../Services/ReportGenerator.php';
require_once __DIR__ . '/../Services/EmailService.php';

class ScheduledReportController
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

        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'list';

        if (!$this->user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        if (!$this->hasAccess()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $this->ensureTableExists();

        // Define which actions require manage permission
        $writeActions = ['create', 'update', 'delete', 'toggle', 'run'];

        if (in_array($action, $writeActions) && !$this->hasAccess(true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ (ต้องการ can_manage)']);
            return;
        }

        switch ($action) {
            case 'list':
                $this->listReports();
                break;
            case 'create':
                if ($method === 'POST') $this->createReport();
                break;
            case 'update':
                if ($method === 'POST' || $method === 'PUT') $this->updateReport();
                break;
            case 'delete':
                if ($method === 'POST' || $method === 'DELETE') $this->deleteReport();
                break;
            case 'toggle':
                if ($method === 'POST') $this->toggleStatus();
                break;
            case 'run':
                if ($method === 'POST') $this->runReport();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }

    private function hasAccess($requireManage = false)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT COALESCE(p.can_view, 0) as can_view, COALESCE(p.can_manage, 0) as can_manage
                FROM core_modules cm
                LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = :role_id
                WHERE cm.code = 'SCHEDULED_REPORTS'
                LIMIT 1
            ");
            $stmt->bindValue(':role_id', $this->user['role_id'] ?? 0, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !$row['can_view']) {
                return false;
            }

            if ($requireManage && !$row['can_manage']) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function ensureTableExists()
    {
        try {
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS `scheduled_reports` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `report_type` VARCHAR(50) NOT NULL,
                    `schedule_type` ENUM('daily', 'weekly', 'monthly') NOT NULL,
                    `schedule_time` TIME NOT NULL DEFAULT '08:00:00',
                    `schedule_day` INT DEFAULT NULL,
                    `recipients` TEXT NOT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `last_sent_at` DATETIME DEFAULT NULL,
                    `created_by` INT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("Could not create scheduled_reports table: " . $e->getMessage());
        }
    }

    private function listReports()
    {
        try {
            $stmt = $this->conn->query("
                SELECT sr.*, u.fullname as creator_name
                FROM scheduled_reports sr
                LEFT JOIN users u ON sr.created_by = u.id
                ORDER BY sr.created_at DESC
            ");

            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($reports as &$r) {
                $r['recipients'] = json_decode($r['recipients'], true) ?: [];
            }

            echo json_encode(['success' => true, 'data' => $reports]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function createReport()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name']) || empty($data['report_type']) || empty($data['schedule_type']) || empty($data['recipients'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                return;
            }

            $stmt = $this->conn->prepare("
                INSERT INTO scheduled_reports 
                (name, report_type, schedule_type, schedule_time, schedule_day, recipients, is_active, created_by)
                VALUES (:name, :report_type, :schedule_type, :schedule_time, :schedule_day, :recipients, :is_active, :created_by)
            ");
            $stmt->execute([
                ':name' => $data['name'],
                ':report_type' => $data['report_type'],
                ':schedule_type' => $data['schedule_type'],
                ':schedule_time' => $data['schedule_time'] ?? '08:00',
                ':schedule_day' => $data['schedule_day'] ?? null,
                ':recipients' => json_encode($data['recipients']),
                ':is_active' => $data['is_active'] ?? 1,
                ':created_by' => $this->user['id']
            ]);

            echo json_encode(['success' => true, 'id' => $this->conn->lastInsertId()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function updateReport()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Report ID required']);
                return;
            }

            $stmt = $this->conn->prepare("
                UPDATE scheduled_reports SET
                    name = :name,
                    report_type = :report_type,
                    schedule_type = :schedule_type,
                    schedule_time = :schedule_time,
                    schedule_day = :schedule_day,
                    recipients = :recipients
                WHERE id = :id
            ");
            $stmt->execute([
                ':name' => $data['name'],
                ':report_type' => $data['report_type'],
                ':schedule_type' => $data['schedule_type'],
                ':schedule_time' => $data['schedule_time'] ?? '08:00',
                ':schedule_day' => $data['schedule_day'] ?? null,
                ':recipients' => json_encode($data['recipients']),
                ':id' => $id
            ]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function deleteReport()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Report ID required']);
                return;
            }

            $stmt = $this->conn->prepare("DELETE FROM scheduled_reports WHERE id = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function toggleStatus()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Report ID required']);
                return;
            }

            $stmt = $this->conn->prepare("UPDATE scheduled_reports SET is_active = !is_active WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Get new status
            $stmt = $this->conn->prepare("SELECT is_active FROM scheduled_reports WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $newStatus = (int)$stmt->fetchColumn();

            echo json_encode(['success' => true, 'is_active' => $newStatus]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function runReport()
    {
        // Prevent HTML errors from breaking JSON
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Report ID required']);
                return;
            }

            // Get report config
            $stmt = $this->conn->prepare("SELECT * FROM scheduled_reports WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Report not found']);
                return;
            }

            $recipients = json_decode($report['recipients'], true) ?: [];
            if (empty($recipients)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No recipients configured']);
                return;
            }

            // Validate report type (check if truncated due to old enum schema)
            if (empty($report['report_type'])) {
                throw new Exception("Report type is missing or invalid. Please check database schema (ENUM vs VARCHAR).");
            }

            // Generate report
            $dateRange = $report['schedule_type'] === 'daily' ? 'last_24_hours' : ($report['schedule_type'] === 'weekly' ? 'last_7_days' : 'last_month');

            $generator = new ReportGenerator($dateRange);
            $reportData = $generator->generate($report['report_type']);
            $emailBody = $generator->buildEmailBody($report['report_type'], $reportData);

            // Generate CSV attachment
            $csvPath = $generator->generateCsvFile($report['report_type']);
            $csvName = "report_" . $report['report_type'] . "_" . date('Y-m-d') . ".csv";

            // Send to all recipients
            $sentCount = 0;
            foreach ($recipients as $email) {
                try {
                    EmailService::sendRawEmail(
                        $email,
                        "[MyHR Portal] {$report['name']}",
                        $emailBody,
                        $csvPath,
                        $csvName
                    );
                    $sentCount++;
                } catch (Exception $e) {
                    error_log("Failed to send report to $email: " . $e->getMessage());
                }
            }

            // Clean up temp file
            if ($csvPath && file_exists($csvPath)) {
                @unlink($csvPath);
            }

            // Update last_sent_at
            $stmt = $this->conn->prepare("UPDATE scheduled_reports SET last_sent_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode(['success' => true, 'sent_count' => $sentCount, 'total_recipients' => count($recipients)]);
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log("ScheduledReportController Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
