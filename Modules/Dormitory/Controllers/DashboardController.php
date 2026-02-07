<?php

/**
 * Dormitory Module - Dashboard Controller
 * สรุปภาพรวมระบบหอพัก
 */

require_once __DIR__ . '/BaseController.php';

class DashboardController extends DormBaseController
{
    /**
     * ข้อมูลสรุป Dashboard
     */
    public function getSummary()
    {
        $summary = [];

        // สถิติอาคารและห้องพัก
        $stmt = $this->pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM dorm_buildings WHERE status = 'active') as total_buildings,
                (SELECT COUNT(*) FROM dorm_rooms) as total_rooms,
                (SELECT COUNT(*) FROM dorm_rooms WHERE status = 'available') as available_rooms,
                (SELECT COUNT(*) FROM dorm_rooms WHERE status = 'occupied') as occupied_rooms,
                (SELECT COUNT(*) FROM dorm_rooms WHERE status = 'maintenance') as maintenance_rooms,
                (SELECT COUNT(*) FROM dorm_occupancies WHERE status = 'active') as total_occupants,
                (SELECT COUNT(*) FROM dorm_occupancies WHERE status = 'active' AND employee_id NOT LIKE 'TEMP_%') as employee_occupants,
                (SELECT COUNT(*) FROM dorm_occupancies WHERE status = 'active' AND employee_id LIKE 'TEMP_%') as temp_occupants,
                (SELECT COALESCE(SUM(COALESCE(accompanying_persons, 0)), 0) FROM dorm_occupancies WHERE status = 'active') as relative_occupants
        ");
        $summary['rooms'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // อัตราการใช้งาน
        if ($summary['rooms']['total_rooms'] > 0) {
            $summary['rooms']['occupancy_rate'] = round(
                ($summary['rooms']['occupied_rooms'] / $summary['rooms']['total_rooms']) * 100,
                1
            );
        } else {
            $summary['rooms']['occupancy_rate'] = 0;
        }

        // สถิติบิลเดือนนี้
        $currentMonth = date('Y-m');
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_invoices,
                COALESCE(SUM(total_amount), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as paid_amount,
                COALESCE(SUM(total_amount - paid_amount), 0) as outstanding,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status IN ('pending', 'partial') THEN 1 ELSE 0 END) as pending_count
            FROM dorm_invoices
            WHERE month_cycle = ? AND status != 'cancelled'
        ");
        $stmt->execute([$currentMonth]);
        $summary['billing'] = $stmt->fetch(PDO::FETCH_ASSOC);
        $summary['billing']['month_cycle'] = $currentMonth;

        // ยอดค้างชำระทั้งหมด
        $stmt = $this->pdo->query("
            SELECT COALESCE(SUM(total_amount - paid_amount), 0) as total
            FROM dorm_invoices
            WHERE status IN ('pending', 'partial', 'overdue')
        ");
        $summary['billing']['total_outstanding'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // สถิติงานซ่อม
        $stmt = $this->pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM dorm_maintenance_requests WHERE status = 'open') as open_count,
                (SELECT COUNT(*) FROM dorm_maintenance_requests WHERE status = 'in_progress') as in_progress_count,
                (SELECT COUNT(*) FROM dorm_maintenance_requests WHERE status IN ('open', 'assigned') AND priority IN ('critical', 'high')) as urgent_count
        ");
        $summary['maintenance'] = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->success(['summary' => $summary]);
    }

    /**
     * รายการงานซ่อมล่าสุด
     */
    public function getRecentMaintenance()
    {
        $limit = (int)($_GET['limit'] ?? 5);
        if ($limit < 1 || $limit > 50) $limit = 5;

        $sql = "
            SELECT m.id, m.ticket_number, m.title, m.priority, m.status, m.created_at,
                   r.room_number, b.code as building_code,
                   c.name as category_name, c.icon as category_icon
            FROM dorm_maintenance_requests m
            LEFT JOIN dorm_rooms r ON m.room_id = r.id
            LEFT JOIN dorm_buildings b ON r.building_id = b.id
            LEFT JOIN dorm_maintenance_categories c ON m.category_id = c.id
            ORDER BY m.created_at DESC
            LIMIT {$limit}
        ";
        $stmt = $this->pdo->query($sql);

        return $this->success(['requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /**
     * บิลค้างชำระ
     */
    public function getOverdueInvoices()
    {
        $stmt = $this->pdo->query("
            SELECT i.id, i.invoice_number, i.month_cycle, i.total_amount, i.paid_amount, 
                   i.due_date, i.status,
                   r.room_number, b.code as building_code,
                   o.employee_name
            FROM dorm_invoices i
            JOIN dorm_rooms r ON i.room_id = r.id
            JOIN dorm_buildings b ON r.building_id = b.id
            JOIN dorm_occupancies o ON i.occupancy_id = o.id
            WHERE i.status IN ('pending', 'partial', 'overdue')
            ORDER BY i.due_date ASC
            LIMIT 10
        ");

        return $this->success(['invoices' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /**
     * Check-in/Check-out ล่าสุด
     */
    public function getRecentOccupancy()
    {
        $limit = (int)($_GET['limit'] ?? 5);
        if ($limit < 1 || $limit > 50) $limit = 5;

        $sql = "
            SELECT o.employee_name, o.employee_id, o.check_in_date, o.check_out_date, o.status,
                   r.room_number, b.code as building_code
            FROM dorm_occupancies o
            JOIN dorm_rooms r ON o.room_id = r.id
            JOIN dorm_buildings b ON r.building_id = b.id
            ORDER BY o.created_at DESC
            LIMIT {$limit}
        ";
        $stmt = $this->pdo->query($sql);

        return $this->success(['occupancies' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /**
     * สรุปรายเดือน (สำหรับกราฟ)
     */
    public function getMonthlyStats()
    {
        $months = $_GET['months'] ?? 6;

        $stats = [];

        // ยอดเรียกเก็บรายเดือน
        $stmt = $this->pdo->prepare("
            SELECT month_cycle, 
                   SUM(total_amount) as total_amount,
                   SUM(paid_amount) as paid_amount
            FROM dorm_invoices
            WHERE month_cycle >= DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH), '%Y-%m')
              AND status != 'cancelled'
            GROUP BY month_cycle
            ORDER BY month_cycle
        ");
        $stmt->execute([(int)$months]);
        $stats['billing'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // จำนวนงานซ่อมรายเดือน
        $stmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month_cycle,
                   COUNT(*) as total_requests,
                   SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved_count
            FROM dorm_maintenance_requests
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month_cycle
        ");
        $stmt->execute([(int)$months]);
        $stats['maintenance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['stats' => $stats]);
    }
}
