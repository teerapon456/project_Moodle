<?php

/**
 * Car Booking Module - Base Controller
 */

require_once __DIR__ . '/../../../core/ModuleController.php';
require_once __DIR__ . '/../../../core/Services/NotificationService.php';
require_once __DIR__ . '/../../../core/Security/InputSanitizer.php';
require_once __DIR__ . '/../../../core/Security/SecureSession.php';

class CBBaseController extends ModuleController
{
    // Inherits: $db, $pdo, $user, $moduleId, $moduleCode from ModuleController

    public function __construct($user = null)
    {
        // Parent handles DB, User, IdentifyModule, EmailConfig, and Gateway Permissions
        // Note: Parent constructor fetches $_SESSION['user'] if $user is null
        // We should call parent first, then override if needed.
        parent::__construct();

        if ($user) {
            $this->user = $user;
        }
    }

    // hasPermission, requirePermission, requireAuth, identifyModule REMOVED (Inherited)

    /**
     * Process incoming API requests
     */
    public function processRequest()
    {
        // Validate session first
        if (!SecureSession::validateSession()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Session expired or invalid']);
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? null;

        $json = json_decode(file_get_contents('php://input'), true) ?? [];
        $input = array_merge($_GET, $_POST, $json);

        // Sanitize all inputs
        $sanitizedInput = $this->sanitizeInput($input, $action);

        if ($action && method_exists($this, $action)) {
            try {
                $reflection = new ReflectionMethod($this, $action);
                $params = $reflection->getParameters();

                if (count($params) > 0) {
                    $response = $this->$action($sanitizedInput);
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
     * Sanitize input based on action type
     */
    private function sanitizeInput($input, $action)
    {
        $rules = $this->getInputSanitizationRules($action);
        return InputSanitizer::sanitizeArray($input, $rules);
    }

    /**
     * Define sanitization rules for different actions
     */
    private function getInputSanitizationRules($action)
    {
        $commonRules = [
            'id' => 'int',
            'user_id' => 'int',
            'page' => 'int',
            'limit' => 'int',
            'offset' => 'int'
        ];

        $actionRules = [
            'create_booking' => [
                'destination' => 'string',
                'purpose' => 'html',
                'start_time' => 'string',
                'end_time' => 'string',
                'passengers' => 'int',
                'driver_name' => 'string',
                'driver_email' => 'email'
            ],
            'update_booking' => [
                'destination' => 'string',
                'purpose' => 'html',
                'start_time' => 'string',
                'end_time' => 'string',
                'driver_name' => 'string',
                'driver_email' => 'email',
                'fleet_amount' => 'float'
            ],
            'create_car' => [
                'name' => 'string',
                'brand' => 'string',
                'model' => 'string',
                'license_plate' => 'alphanum',
                'capacity' => 'int',
                'type' => 'string'
            ],
            'update_car' => [
                'name' => 'string',
                'brand' => 'string',
                'model' => 'string',
                'license_plate' => 'alphanum',
                'capacity' => 'int',
                'type' => 'string'
            ]
        ];

        return array_merge($commonRules, $actionRules[$action] ?? []);
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
     * Check permission
     */
    protected function requirePermission($permission)
    {
        $this->requireAuth();

        if ($this->hasPermission($permission)) {
            return true;
        }

        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ในการดำเนินการนี้ (' . $permission . ')']);
        exit;
    }

    /**
     * Helper: Log audit action
     */
    protected function logAudit($action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO cb_audit_logs 
                (user_id, user_name, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->user['id'] ?? null,
                $this->user['fullname'] ?? $this->user['username'] ?? 'System',
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
            error_log("Car Booking audit log failed: " . $e->getMessage());
        }
    }

    /**
     * Get audit logs list (Admin only)
     */
    public function listAuditLogs()
    {
        $this->requireAuth();
        $this->requirePermission('manage'); // Admin only

        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $limit = 20;
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

        try {
            // Count total
            $countSql = "SELECT COUNT(*) FROM cb_audit_logs a WHERE $whereClause";
            $stmt = $this->pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Get data with user fullname
            $sql = "
                SELECT a.id, a.user_id, 
                       COALESCE(u.fullname, a.user_name, CONCAT('ID: ', a.user_id)) as display_name,
                       a.user_name, a.action, a.entity_type, a.entity_id, 
                       a.old_values, a.new_values, a.ip_address, a.created_at
                FROM cb_audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE $whereClause
                ORDER BY a.created_at DESC
                LIMIT $limit OFFSET $offset
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get distinct actions for filter dropdown
            $actions = $this->pdo->query("SELECT DISTINCT action FROM cb_audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

            // Get distinct entity types for filter dropdown
            $entityTypes = $this->pdo->query("SELECT DISTINCT entity_type FROM cb_audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN);

            return $this->success([
                'logs' => $logs,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit),
                'actions' => $actions,
                'entity_types' => $entityTypes
            ]);
        } catch (Exception $e) {
            return $this->error('ไม่สามารถโหลด Audit Log ได้: ' . $e->getMessage());
        }
    }
}
