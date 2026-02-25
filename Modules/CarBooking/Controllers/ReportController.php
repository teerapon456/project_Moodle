<?php

/**
 * Car Booking Module - Report Controller
 */

require_once __DIR__ . '/BaseController.php';

class CBReportController extends CBBaseController
{
    /**
     * Get booking statistics summary
     */
    public function summary()
    {
        $this->requirePermission('view');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        // Overall stats
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status IN ('pending_supervisor', 'pending_manager') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM cb_bookings
            WHERE DATE(start_time) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->success(['stats' => $stats, 'start_date' => $startDate, 'end_date' => $endDate]);
    }

    /**
     * Get monthly booking trend
     */
    public function monthly()
    {
        $this->requirePermission('view');

        $months = intval($_GET['months'] ?? 6);

        $sql = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' OR status = 'completed' THEN 1 ELSE 0 END) as approved
            FROM cb_bookings
            WHERE start_time >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$months]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['monthly' => $data]);
    }

    /**
     * Get car usage report
     */
    public function carUsage()
    {
        $this->requirePermission('manage');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $sql = "
            SELECT 
                c.id,
                c.name,
                c.brand,
                c.model,
                c.license_plate,
                COUNT(b.id) as usage_count,
                SUM(TIMESTAMPDIFF(HOUR, b.start_time, b.end_time)) as total_hours
            FROM cb_cars c
            LEFT JOIN cb_bookings b ON b.assigned_car_id = c.id 
                AND b.status IN ('approved', 'completed')
                AND DATE(b.start_time) BETWEEN ? AND ?
            GROUP BY c.id
            ORDER BY usage_count DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['car_usage' => $data]);
    }

    /**
     * Export bookings to CSV
     */
    public function export()
    {
        $this->requirePermission('manage');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $status = $_GET['status'] ?? '';

        $sql = "
            SELECT 
                b.id,
                u.fullname as requester,
                b.destination,
                b.purpose,
                b.start_time,
                b.end_time,
                b.status,
                c.name as car_name,
                c.license_plate,
                drv.fullname as driver_name,
                b.created_at
            FROM cb_bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN cb_cars c ON b.assigned_car_id = c.id
            LEFT JOIN users drv ON b.driver_user_id = drv.id
            WHERE DATE(b.start_time) BETWEEN ? AND ?
        ";
        $params = [$startDate, $endDate];

        if ($status) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY b.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['bookings' => $data, 'count' => count($data)]);
    }

    /**
     * Export bookings to CSV (Excel compatible)
     */
    public function exportExcel()
    {
        $this->requirePermission('manage');

        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');

        // Calculate Start and End Date
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // Fetch Data
        $sql = "
            SELECT 
                b.created_at,
                u.fullname as requester,
                u.Level3Name as department,
                b.destination,
                b.purpose,
                b.start_time,
                b.end_time,
                b.start_time,
                b.end_time,
                drv.fullname as driver_name,
                b.passengers_detail,
                c.brand,
                c.model,
                c.license_plate,
                fc.card_number as fleet_card_number,
                b.fleet_amount,
                sup.fullname as supervisor_name,
                b.supervisor_approved_at,
                man.fullname as manager_name,
                b.manager_approved_at,
                b.status
            FROM cb_bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN cb_cars c ON b.assigned_car_id = c.id
            LEFT JOIN users drv ON b.driver_user_id = drv.id
            LEFT JOIN cb_fleet_cards fc ON b.fleet_card_id = fc.id
            LEFT JOIN users sup ON b.supervisor_approved_user_id = sup.id
            LEFT JOIN users man ON b.manager_approved_user_id = man.id
            WHERE DATE(b.start_time) BETWEEN ? AND ?
            ORDER BY b.start_time ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare CSV Output
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=car_booking_report_' . $year . '_' . $month . '.csv');

        $output = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 support
        fputs($output, "\xEF\xBB\xBF");

        // Header Row
        fputcsv($output, [
            'วันที่ทำรายการ',
            'ผู้ขอ',
            'แผนก',
            'ปลายทาง',
            'วัตถุประสงค์',
            'เวลาเริ่ม',
            'เวลาสิ้นสุด',
            'คนขับ',
            'ผู้โดยสาร',
            'ยี่ห้อ/รุ่น',
            'ทะเบียนรถ',
            'เลขบัตรน้ำมัน',
            'ยอดเงินน้ำมัน',
            'หัวหน้าอนุมัติ',
            'วันที่อนุมัติ(หัวหน้า)',
            'IPCD อนุมัติ',
            'วันที่อนุมัติ(IPCD)',
            'สถานะ'
        ]);

        // Data Rows
        foreach ($rows as $row) {
            $brandModel = trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''));

            // Parse passengers
            $passengerText = '-';
            if (!empty($row['passengers_detail'])) {
                $decoded = json_decode($row['passengers_detail'], true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $names = [];
                    foreach ($decoded as $p) {
                        if (is_array($p)) {
                            $names[] = $p['name'] ?? $p['email'] ?? '';
                        } else {
                            $names[] = $p;
                        }
                    }
                    $names = array_filter($names);
                    if (!empty($names)) {
                        $passengerText = implode(', ', $names);
                    }
                }
            }

            fputcsv($output, [
                $row['created_at'],
                $row['requester'],
                $row['department'] ?: '-',
                $row['destination'],
                $row['purpose'],
                $row['start_time'],
                $row['end_time'],
                $row['driver_name'],
                $passengerText,
                $brandModel ?: '-',
                $row['license_plate'] ?: '-',
                $row['fleet_card_number'] ?: '-',
                $row['fleet_amount'] ?: '-',
                $row['supervisor_name'] ?: '-',
                $row['supervisor_approved_at'] ?: '-',
                $row['manager_name'] ?: '-',
                $row['manager_approved_at'] ?: '-',
                $this->getStatusLabel($row['status'])
            ]);
        }

        fclose($output);
        exit;
    }

    private function getStatusLabel($status)
    {
        switch ($status) {
            case 'approved':
                return 'อนุมัติแล้ว';
            case 'rejected':
            case 'rejected_supervisor':
            case 'rejected_manager':
                return 'ถูกปฏิเสธ';
            case 'completed':
                return 'เสร็จสิ้น';
            case 'in_use':
                return 'กำลังใช้งาน';
            case 'pending_return':
                return 'รอคืนรถ';
            case 'pending_supervisor':
                return 'รอหัวหน้าอนุมัติ';
            case 'pending_manager':
                return 'รอ IPCD ตรวจสอบ';
            case 'cancelled':
                return 'ยกเลิก';
            case 'revoked':
                return 'ยกเลิกโดยแอดมิน';
            default:
                return $status;
        }
    }
}
