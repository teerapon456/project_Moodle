<?php

/**
 * Activity Dashboard Controller
 * Provides API endpoints for activity statistics and logs
 */

require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Config/SessionConfig.php';

class ActivityController
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

        $action = $_GET['action'] ?? null;

        if (!$this->user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        // Check admin permission
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        switch ($action) {
            case 'dashboard-stats':
                $this->getDashboardStats();
                break;
            case 'top-users':
                $this->getTopUsers();
                break;
            case 'top-actions':
                $this->getTopActions();
                break;
            case 'activity-timeline':
                $this->getActivityTimeline();
                break;
            case 'login-history':
                $this->getLoginHistory();
                break;
            case 'device-stats':
                $this->getDeviceStats();
                break;
            case 'system-audit-summary':
                $this->getSystemAuditSummary();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }

    private function isAdmin()
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT COALESCE(p.can_view, 0) as can_view 
                FROM core_modules cm
                LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = :role_id
                WHERE cm.code = 'ACTIVITY_DASHBOARD'
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

    private function getDashboardStats()
    {
        try {
            $stats = [];

            // Total activities today
            $stmt = $this->conn->query("
                SELECT (
                    (SELECT COUNT(*) FROM cb_audit_logs WHERE DATE(created_at) = CURDATE()) + 
                    (SELECT COUNT(*) FROM user_logins WHERE DATE(created_at) = CURDATE())
                ) as total
            ");
            $stats['activities_today'] = (int)$stmt->fetchColumn();

            // Total activities this week
            $stmt = $this->conn->query("
                SELECT (
                    (SELECT COUNT(*) FROM cb_audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) + 
                    (SELECT COUNT(*) FROM user_logins WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
                ) as total
            ");
            $stats['activities_week'] = (int)$stmt->fetchColumn();

            // Unique users today  
            $stmt = $this->conn->query("
                SELECT COUNT(DISTINCT user_id) FROM (
                    SELECT user_id FROM cb_audit_logs WHERE DATE(created_at) = CURDATE() AND user_id IS NOT NULL
                    UNION
                    SELECT user_id FROM user_logins WHERE DATE(created_at) = CURDATE() AND user_id IS NOT NULL
                ) as combined
            ");
            $stats['active_users_today'] = (int)$stmt->fetchColumn();

            // Logins today
            $stmt = $this->conn->query("SELECT COUNT(*) FROM user_logins WHERE action = 'login' AND DATE(created_at) = CURDATE()");
            $stats['logins_today'] = (int)$stmt->fetchColumn();

            // Total registered users
            $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
            $stats['total_users'] = (int)$stmt->fetchColumn();

            // Activity trend (last 7 days)
            $stmt = $this->conn->query("
                SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM (
                    SELECT created_at FROM cb_audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    UNION ALL
                    SELECT created_at FROM user_logins WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                ) as combined
                GROUP BY DATE(created_at) 
                ORDER BY date
            ");
            $stats['activity_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Failed logins today
            $stmt = $this->conn->query("SELECT COUNT(*) FROM user_logins WHERE action = 'login_failed' AND DATE(created_at) = CURDATE()");
            $stats['failed_logins_today'] = (int)$stmt->fetchColumn();

            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function getTopUsers()
    {
        try {
            $days = (int)($_GET['days'] ?? 7);
            $limit = (int)($_GET['limit'] ?? 10);

            // Fetch from cb_audit_logs
            $stmt1 = $this->conn->prepare("
                SELECT 
                    a.user_id,
                    COALESCE(u.fullname, a.user_name, 'Unknown') as user_name,
                    u.Level3Name as department,
                    COUNT(*) as activity_count,
                    MAX(a.created_at) as last_activity
                FROM cb_audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY a.user_id, a.user_name, u.fullname, u.Level3Name
            ");
            $stmt1->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt1->execute();
            $data1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

            // Fetch from user_logins
            $stmt2 = $this->conn->prepare("
                SELECT 
                    a.user_id,
                    COALESCE(u.fullname, a.user_name, 'Unknown') as user_name,
                    u.Level3Name as department,
                    COUNT(*) as activity_count,
                    MAX(a.created_at) as last_activity
                FROM user_logins a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY a.user_id, a.user_name, u.fullname, u.Level3Name
            ");
            $stmt2->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt2->execute();
            $data2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            // Merge and Aggregate in PHP
            $combined = [];
            foreach (array_merge($data1, $data2) as $row) {
                $key = $row['user_id'] ? 'id_' . $row['user_id'] : 'name_' . $row['user_name'];
                if (!isset($combined[$key])) {
                    $combined[$key] = $row;
                } else {
                    $combined[$key]['activity_count'] += $row['activity_count'];
                    if ($row['last_activity'] > $combined[$key]['last_activity']) {
                        $combined[$key]['last_activity'] = $row['last_activity'];
                    }
                }
            }

            // Sort
            usort($combined, function ($a, $b) {
                return $b['activity_count'] <=> $a['activity_count'];
            });

            // Slice
            $result = array_slice(array_values($combined), 0, $limit);

            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            // Return 200 with error handling to avoid browser console red noise if possible, but keep 500 for critical
            // Actually, keep 500 but ensure message is clear.
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'TopUsers Error: ' . $e->getMessage()]);
        }
    }

    private function getTopActions()
    {
        try {
            $days = (int)($_GET['days'] ?? 7);
            $limit = (int)($_GET['limit'] ?? 10);

            // Audit Logs
            $stmt1 = $this->conn->prepare("
                SELECT action, entity_type, COUNT(*) as count
                FROM cb_audit_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY action, entity_type
            ");
            $stmt1->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt1->execute();
            $data1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

            // User Logins
            $stmt2 = $this->conn->prepare("
                SELECT action, 'auth' as entity_type, COUNT(*) as count
                FROM user_logins
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY action
            ");
            $stmt2->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt2->execute();
            $data2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            // Merge
            $combined = [];
            foreach (array_merge($data1, $data2) as $row) {
                $key = $row['action'] . '|' . $row['entity_type'];
                if (!isset($combined[$key])) {
                    $combined[$key] = $row;
                } else {
                    $combined[$key]['count'] += $row['count'];
                }
            }

            // Sort
            usort($combined, function ($a, $b) {
                return $b['count'] <=> $a['count'];
            });

            echo json_encode(['success' => true, 'data' => array_slice(array_values($combined), 0, $limit)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'TopActions Error: ' . $e->getMessage()]);
        }
    }

    private function getActivityTimeline()
    {
        try {
            $limit = (int)($_GET['limit'] ?? 15);
            $page = (int)($_GET['page'] ?? 1);
            $offset = ($page - 1) * $limit;

            // Get total count (from user_logins only)
            $countStmt = $this->conn->query("SELECT COUNT(*) FROM user_logins");
            $total = (int)$countStmt->fetchColumn();

            // Query specifically for user_logins timeline with device and location data
            $stmt = $this->conn->prepare("
                SELECT 
                    a.id,
                    a.user_id,
                    COALESCE(u.fullname, a.user_name, 'System') as user_name,
                    a.action,
                    'auth' as entity_type,
                    NULL as entity_id,
                    a.ip_address,
                    a.device_type,
                    a.device_brand,
                    a.device_model,
                    a.os_name,
                    a.os_version,
                    a.client_name,
                    a.client_version,
                    a.latitude,
                    a.longitude,
                    a.details,
                    a.created_at
                FROM user_logins a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset
            ");

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
            // Return 200 so frontend can parse JSON and show error (instead of browser generic 500)
            http_response_code(200);
            echo json_encode(['success' => false, 'message' => 'Timeline Error: ' . $e->getMessage()]);
        }
    }

    private function getLoginHistory()
    {
        try {
            $limit = (int)($_GET['limit'] ?? 50);
            $userId = $_GET['user_id'] ?? null;

            $sql = "
                SELECT 
                    a.id,
                    a.user_id,
                    COALESCE(u.fullname, a.user_name) as user_name,
                    u.Level3Name as department,
                    a.action,
                    a.ip_address,
                    a.user_agent,
                    a.created_at
                FROM user_logins a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.action IN ('login', 'logout')
            ";

            $params = [];
            if ($userId) {
                $sql .= " AND a.user_id = :user_id";
                $params[':user_id'] = $userId;
            }

            $sql .= " ORDER BY a.created_at DESC LIMIT :limit";

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function getDeviceStats()
    {
        try {
            $days = (int)($_GET['days'] ?? 30);
            $stmt = $this->conn->prepare("
                SELECT device_type, COUNT(*) as count 
                FROM user_logins 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  AND device_type IS NOT NULL
                GROUP BY device_type 
                ORDER BY count DESC
            ");
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function getSystemAuditSummary()
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $stmt = $this->conn->prepare("
                SELECT 
                    a.id, 
                    a.action, 
                    a.entity_type, 
                    COALESCE(u.fullname, a.user_name, 'System') as performed_by, 
                    a.created_at as performed_at 
                FROM cb_audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC 
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
