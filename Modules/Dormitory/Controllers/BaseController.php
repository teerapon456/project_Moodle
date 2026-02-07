<?php

/**
 * Dormitory Module - Base Controller
 * Base class for all Dormitory controllers.
 * Inherits from Core\ModuleController to provide:
 * - Database connection ($this->pdo)
 * - User session ($this->user)
 * - Module identification & permissions
 * - Helper methods via NotificationService
 */

require_once __DIR__ . '/../../../core/ModuleController.php';
require_once __DIR__ . '/../../../core/Services/NotificationService.php';

class DormBaseController extends ModuleController
{
    // Inherits: $db, $pdo, $user, $moduleId, $moduleCode from ModuleController

    public function __construct()
    {
        // Parent handles DB, User, IdentifyModule, EmailConfig, and Gateway Permissions
        parent::__construct();
    }

    // hasPermission, requirePermission, requireAuth, identifyModule RE MOVED (Inherited)

    /**
     * Process incoming API requests
     */
    public function processRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? null;

        $json = json_decode(file_get_contents('php://input'), true) ?? [];
        $input = array_merge($_GET, $_POST, $json);

        if ($action && method_exists($this, $action)) {
            try {
                $reflection = new ReflectionMethod($this, $action);
                $params = $reflection->getParameters();

                if (count($params) > 0) {
                    $response = $this->$action($input);
                } else {
                    $response = $this->$action();
                }

                if ($response !== null) {
                    echo json_encode($response);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->notFound("Action '$action' not found.");
        }
    }

    /**
     * Helper: Send success response
     */
    protected function success($data = [], $message = 'สำเร็จ')
    {
        return array_merge(['success' => true, 'message' => $message], $data);
    }

    /**
     * Helper: Send error response
     */
    protected function error($message, $code = 400)
    {
        http_response_code($code);
        return ['success' => false, 'message' => $message];
    }

    /**
     * Helper: Log audit action
     */
    protected function logAudit($action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO dorm_audit_logs 
                (user_id, user_name, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->user['id'] ?? null,
                $this->user['name'] ?? 'System',
                $action,
                $entityType,
                $entityId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $this->getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Silently fail audit log
            error_log("Audit log failed: " . $e->getMessage());
        }
    }

    /**
     * Helper: Generate unique ticket/invoice number
     */
    protected function generateNumber($prefix, $table, $column)
    {
        $year = date('Y');
        $month = date('m');

        $stmt = $this->pdo->prepare("SELECT MAX($column) FROM $table WHERE $column LIKE ?");
        $stmt->execute([$prefix . $year . $month . '%']);
        $lastNumber = $stmt->fetchColumn();

        if ($lastNumber) {
            $seq = intval(substr($lastNumber, -4)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . $year . $month . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get audit logs list
     */
    public function listAuditLogs()
    {
        $this->requireAuth();
        $this->requirePermission('admin');

        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $action = $_GET['action_filter'] ?? '';
        $entityType = $_GET['entity_type'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $search = $_GET['search'] ?? '';

        $conditions = ["1=1"];
        $params = [];

        if ($action) {
            $conditions[] = "action = ?";
            $params[] = $action;
        }
        if ($entityType) {
            $conditions[] = "entity_type = ?";
            $params[] = $entityType;
        }
        if ($startDate) {
            $conditions[] = "DATE(a.created_at) >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $conditions[] = "DATE(a.created_at) <= ?";
            $params[] = $endDate;
        }
        if ($search) {
            $conditions[] = "(user_name LIKE ? OR action LIKE ? OR entity_type LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = implode(' AND ', $conditions);

        // Count total
        $countSql = "SELECT COUNT(*) FROM dorm_audit_logs a WHERE $whereClause";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get data with user fullname
        $sql = "
            SELECT a.id, a.user_id, 
                   COALESCE(u.fullname, a.user_name, CONCAT('ID: ', a.user_id)) as display_name,
                   a.user_name, a.action, a.entity_type, a.entity_id, 
                   a.old_values, a.new_values, a.ip_address, a.created_at
            FROM dorm_audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE $whereClause
            ORDER BY a.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get distinct actions for filter dropdown
        $actions = $this->pdo->query("SELECT DISTINCT action FROM dorm_audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

        // Get distinct entity types for filter dropdown
        $entityTypes = $this->pdo->query("SELECT DISTINCT entity_type FROM dorm_audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN);

        return $this->success([
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit),
            'actions' => $actions,
            'entity_types' => $entityTypes
        ]);
    }

    /**
     * Helper: Notify all dormitory admins (from system_settings)
     */
    protected function notifyDormAdmins($type, $title, $message, $link = null)
    {
        NotificationService::sendToModuleAdmins($this->moduleId ?? 20, $type, $title, $message, $link);
    }
}
