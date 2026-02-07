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
            WHERE DATE(created_at) BETWEEN ? AND ?
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
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
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
                b.driver_name,
                b.created_at
            FROM cb_bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN cb_cars c ON b.assigned_car_id = c.id
            WHERE DATE(b.created_at) BETWEEN ? AND ?
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
                b.destination,
                b.purpose,
                b.start_time,
                b.end_time,
                b.driver_name,
                c.license_plate,
                b.fleet_card_number,
                b.fleet_amount,
                b.status
            FROM cb_bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN cb_cars c ON b.assigned_car_id = c.id
            WHERE DATE(b.created_at) BETWEEN ? AND ?
            ORDER BY b.created_at ASC
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
        fputcsv($output, ['วันที่ทำรายการ', 'ผู้ขอ', 'ปลายทาง', 'วัตถุประสงค์', 'เวลาเริ่ม', 'เวลาสิ้นสุด', 'คนขับ', 'ทะเบียนรถ', 'เลขบัตรน้ำมัน', 'ยอดเงินน้ำมัน', 'สถานะ']);

        // Data Rows
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['created_at'],
                $row['requester'],
                $row['destination'],
                $row['purpose'],
                $row['start_time'],
                $row['end_time'],
                $row['driver_name'],
                $row['license_plate'] ?: '-',
                $row['fleet_card_number'] ?: '-',
                $row['fleet_amount'] ?: '-',
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
                return 'ถูกปฏิเสธ';
            case 'completed':
                return 'เสร็จสิ้น';
            case 'pending_supervisor':
                return 'รอหัวหน้าอนุมัติ';
            case 'pending_manager':
                return 'รอ IPCD ตรวจสอบ';
            case 'cancelled':
                return 'ยกเลิก';
            default:
                return $status;
        }
    }
}
