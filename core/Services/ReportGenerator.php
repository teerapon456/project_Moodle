<?php

/**
 * Report Generator Service
 * Generates report content for different report types
 */

require_once __DIR__ . '/../Database/Database.php';

class ReportGenerator
{
    private $conn;
    private $dateRange;

    public function __construct($dateRange = 'last_7_days')
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->dateRange = $dateRange;
    }

    /**
     * Generate report based on type
     */
    public function generate($reportType)
    {
        switch ($reportType) {
            case 'activity_summary':
                return $this->generateActivitySummary();
            case 'login_report':
                return $this->generateLoginReport();
            case 'email_stats':
                return $this->generateEmailStats();
            case 'car_booking_summary':
                return $this->generateCarBookingSummary();
            case 'dormitory_summary':
                return $this->generateDormitorySummary();
            default:
                throw new Exception("Unknown report type: $reportType");
        }
    }

    /**
     * Activity Summary Report
     */
    private function generateActivitySummary()
    {
        $data = [
            'title' => 'รายงานสรุปกิจกรรม',
            'period' => $this->getPeriodLabel(),
            'generated_at' => date('Y-m-d H:i:s')
        ];

        // Total activities
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM cb_audit_logs 
            WHERE created_at >= " . $this->getDateCondition()
        );
        $data['total_activities'] = (int)$stmt->fetchColumn();

        // Unique users
        $stmt = $this->conn->query(
            "
            SELECT COUNT(DISTINCT user_id) FROM cb_audit_logs 
            WHERE user_id IS NOT NULL AND created_at >= " . $this->getDateCondition()
        );
        $data['unique_users'] = (int)$stmt->fetchColumn();

        // Top users
        $stmt = $this->conn->query("
            SELECT COALESCE(u.fullname, a.user_name) as name, COUNT(*) as count
            FROM cb_audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.created_at >= " . $this->getDateCondition('a.created_at') . "
            AND a.user_id IS NOT NULL
            GROUP BY a.user_id, a.user_name, u.fullname
            ORDER BY count DESC
            LIMIT 5
        ");
        $data['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top actions
        $stmt = $this->conn->query("
            SELECT action, COUNT(*) as count
            FROM cb_audit_logs
            WHERE created_at >= " . $this->getDateCondition() . "
            GROUP BY action
            ORDER BY count DESC
            LIMIT 5
        ");
        $data['top_actions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * Login Report
     */
    private function generateLoginReport()
    {
        $data = [
            'title' => 'รายงาน Login/Logout',
            'period' => $this->getPeriodLabel(),
            'generated_at' => date('Y-m-d H:i:s')
        ];

        // Login count
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM user_logins 
            WHERE action = 'login' AND created_at >= " . $this->getDateCondition()
        );
        $data['login_count'] = (int)$stmt->fetchColumn();

        // Logout count
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM user_logins 
            WHERE action = 'logout' AND created_at >= " . $this->getDateCondition()
        );
        $data['logout_count'] = (int)$stmt->fetchColumn();

        // Unique users logged in
        $stmt = $this->conn->query(
            "
            SELECT COUNT(DISTINCT user_id) FROM user_logins 
            WHERE action = 'login' AND user_id IS NOT NULL AND created_at >= " . $this->getDateCondition()
        );
        $data['unique_logins'] = (int)$stmt->fetchColumn();

        // Recent logins
        $stmt = $this->conn->query("
            SELECT COALESCE(u.fullname, a.user_name) as name, a.ip_address, a.created_at
            FROM user_logins a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.action = 'login' AND a.created_at >= " . $this->getDateCondition('a.created_at') . "
            ORDER BY a.created_at DESC
            LIMIT 10
        ");
        $data['recent_logins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * Email Stats Report
     */
    private function generateEmailStats()
    {
        $data = [
            'title' => 'รายงานสถิติการส่ง Email',
            'period' => $this->getPeriodLabel(),
            'generated_at' => date('Y-m-d H:i:s')
        ];

        // Total emails
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM email_logs 
            WHERE created_at >= " . $this->getDateCondition()
        );
        $data['total_emails'] = (int)$stmt->fetchColumn();

        // Success count
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM email_logs 
            WHERE status IN ('success', 'sent') AND created_at >= " . $this->getDateCondition()
        );
        $data['success_count'] = (int)$stmt->fetchColumn();

        // Failed count
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM email_logs 
            WHERE status NOT IN ('success', 'sent', 'pending') AND created_at >= " . $this->getDateCondition()
        );
        $data['failed_count'] = (int)$stmt->fetchColumn();

        // Success rate
        $data['success_rate'] = $data['total_emails'] > 0
            ? round(($data['success_count'] / $data['total_emails']) * 100, 1)
            : 0;

        return $data;
    }

    /**
     * Car Booking Summary Report
     */
    private function generateCarBookingSummary()
    {
        $data = [
            'title' => 'รายงานสรุปการใช้รถ',
            'period' => $this->getPeriodLabel(),
            'generated_at' => date('Y-m-d H:i:s')
        ];

        // Total bookings
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM cb_bookings 
            WHERE created_at >= " . $this->getDateCondition()
        );
        $data['total_bookings'] = (int)$stmt->fetchColumn();

        // Approved bookings
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM cb_bookings 
            WHERE (status LIKE 'approved%' OR status IN ('completed', 'in_use', 'returned')) AND created_at >= " . $this->getDateCondition()
        );
        $data['approved'] = (int)$stmt->fetchColumn();

        // Pending bookings
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM cb_bookings 
            WHERE status LIKE 'pending%' AND created_at >= " . $this->getDateCondition()
        );
        $data['pending'] = (int)$stmt->fetchColumn();

        // Rejected bookings
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM cb_bookings 
            WHERE status LIKE 'reject%' AND created_at >= " . $this->getDateCondition()
        );
        $data['rejected'] = (int)$stmt->fetchColumn();

        // Cancelled/Revoked bookings
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM cb_bookings 
            WHERE (status LIKE 'cancel%' OR status LIKE 'revoke%') AND created_at >= " . $this->getDateCondition()
        );
        $data['cancelled'] = (int)$stmt->fetchColumn();

        // Top cars used
        $stmt = $this->conn->query("
            SELECT c.license_plate, c.brand, c.model, COUNT(b.id) as count
            FROM cb_bookings b
            JOIN cb_cars c ON b.assigned_car_id = c.id
            WHERE b.created_at >= " . $this->getDateCondition('b.created_at') . "
            GROUP BY c.id, c.license_plate, c.brand, c.model
            ORDER BY count DESC
            LIMIT 5
        ");
        $data['top_cars'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top users
        $stmt = $this->conn->query("
            SELECT u.fullname as name, COUNT(b.id) as count
            FROM cb_bookings b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.created_at >= " . $this->getDateCondition('b.created_at') . "
            GROUP BY b.user_id, u.fullname
            ORDER BY count DESC
            LIMIT 5
        ");
        $data['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * Dormitory Summary Report
     */
    private function generateDormitorySummary()
    {
        $data = [
            'title' => 'รายงานสรุปหอพัก',
            'period' => $this->getPeriodLabel(),
            'generated_at' => date('Y-m-d H:i:s')
        ];

        // Total requests
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM dorm_requests 
            WHERE created_at >= " . $this->getDateCondition()
        );
        $data['total_requests'] = (int)$stmt->fetchColumn();

        // Approved requests
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM dorm_requests 
            WHERE status = 'approved' AND created_at >= " . $this->getDateCondition()
        );
        $data['approved'] = (int)$stmt->fetchColumn();

        // Pending requests
        $stmt = $this->conn->query(
            "
            SELECT COUNT(*) FROM dorm_requests 
            WHERE status = 'pending' AND created_at >= " . $this->getDateCondition()
        );
        $data['pending'] = (int)$stmt->fetchColumn();

        // Total occupied rooms
        $stmt = $this->conn->query("SELECT COUNT(*) FROM dorm_rooms WHERE status = 'occupied'");
        $data['occupied_rooms'] = (int)$stmt->fetchColumn();

        // Total available rooms
        $stmt = $this->conn->query("SELECT COUNT(*) FROM dorm_rooms WHERE status = 'available'");
        $data['available_rooms'] = (int)$stmt->fetchColumn();

        // Occupancy rate
        $totalRooms = $data['occupied_rooms'] + $data['available_rooms'];
        $data['occupancy_rate'] = $totalRooms > 0
            ? round(($data['occupied_rooms'] / $totalRooms) * 100, 1)
            : 0;

        return $data;
    }

    /**
     * Build HTML email body from report data
     */
    public function buildEmailBody($reportType, $data)
    {
        $actionLabels = [
            'login' => 'เข้าสู่ระบบ',
            'logout' => 'ออกจากระบบ',
            'create_booking' => 'สร้างคำขอ',
            'approve_request' => 'อนุมัติ',
            'reject_request' => 'ปฏิเสธ'
        ];

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Kanit', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #A21D21, #c62828); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .header p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }
                .content { padding: 30px; }
                .stat-grid { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
                .stat-box { flex: 1; min-width: 120px; background: #f9fafb; border-radius: 8px; padding: 15px; text-align: center; }
                .stat-box .value { font-size: 28px; font-weight: bold; color: #A21D21; }
                .stat-box .label { font-size: 12px; color: #6b7280; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
                th { background: #f9fafb; font-size: 12px; color: #6b7280; text-transform: uppercase; }
                .footer { background: #374151; color: #9ca3af; padding: 20px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$data['title']}</h1>
                    <p>ช่วงเวลา: {$data['period']}</p>
                </div>
                <div class='content'>";

        switch ($reportType) {
            case 'activity_summary':
                $html .= "
                    <div class='stat-grid'>
                        <div class='stat-box'>
                            <div class='value'>{$data['total_activities']}</div>
                            <div class='label'>กิจกรรมทั้งหมด</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value'>{$data['unique_users']}</div>
                            <div class='label'>ผู้ใช้งาน</div>
                        </div>
                    </div>
                    <h3 style='margin:0 0 10px; color:#374151;'>👤 ผู้ใช้งานมากที่สุด</h3>
                    <table>";
                foreach ($data['top_users'] as $u) {
                    $html .= "<tr><td>{$u['name']}</td><td style='text-align:right;font-weight:bold;color:#A21D21;'>{$u['count']}</td></tr>";
                }
                $html .= "</table>
                    <h3 style='margin:20px 0 10px; color:#374151;'>📊 กิจกรรมที่ทำบ่อย</h3>
                    <table>";
                foreach ($data['top_actions'] as $a) {
                    $label = $actionLabels[$a['action']] ?? $a['action'];
                    $html .= "<tr><td>{$label}</td><td style='text-align:right;font-weight:bold;'>{$a['count']}</td></tr>";
                }
                $html .= "</table>";
                break;

            case 'login_report':
                $html .= "
                    <div class='stat-grid'>
                        <div class='stat-box'>
                            <div class='value'>{$data['login_count']}</div>
                            <div class='label'>Login</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value'>{$data['logout_count']}</div>
                            <div class='label'>Logout</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value'>{$data['unique_logins']}</div>
                            <div class='label'>Users</div>
                        </div>
                    </div>
                    <h3 style='margin:0 0 10px; color:#374151;'>🕐 Login ล่าสุด</h3>
                    <table>
                        <tr><th>ผู้ใช้</th><th>IP</th><th>เวลา</th></tr>";
                foreach ($data['recent_logins'] as $l) {
                    $time = date('d/m H:i', strtotime($l['created_at']));
                    $html .= "<tr><td>{$l['name']}</td><td>{$l['ip_address']}</td><td>{$time}</td></tr>";
                }
                $html .= "</table>";
                break;

            case 'email_stats':
                $rateColor = $data['success_rate'] >= 90 ? '#10b981' : ($data['success_rate'] >= 70 ? '#f59e0b' : '#ef4444');
                $html .= "
                    <div class='stat-grid'>
                        <div class='stat-box'>
                            <div class='value'>{$data['total_emails']}</div>
                            <div class='label'>ทั้งหมด</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:#10b981;'>{$data['success_count']}</div>
                            <div class='label'>สำเร็จ</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:#ef4444;'>{$data['failed_count']}</div>
                            <div class='label'>ไม่สำเร็จ</div>
                        </div>
                            <div class='stat-box'>
                            <div class='value' style='color:{$rateColor};'>{$data['success_rate']}%</div>
                            <div class='label'>Success Rate</div>
                        </div>
                    </div>";
                break;

            case 'car_booking_summary':
                $html .= "
                    <div class='stat-grid'>
                        <div class='stat-box'>
                            <div class='value'>{$data['total_bookings']}</div>
                            <div class='label'>จองทั้งหมด</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:#10b981;'>{$data['approved']}</div>
                            <div class='label'>อนุมัติ</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:#f59e0b;'>{$data['pending']}</div>
                            <div class='label'>รออนุมัติ</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:#ef4444;'>{$data['rejected']}</div>
                            <div class='label'>ไม่อนุมัติ</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:#6b7280;'>{$data['cancelled']}</div>
                            <div class='label'>ยกเลิก</div>
                        </div>
                    </div>
                    <h3 style='margin:0 0 10px; color:#374151;'>🚗 รถที่ใช้บ่อย</h3>
                    <table>";
                foreach ($data['top_cars'] as $c) {
                    $html .= "<tr><td>{$c['license_plate']} ({$c['brand']} {$c['model']})</td><td style='text-align:right;font-weight:bold;color:#A21D21;'>{$c['count']}</td></tr>";
                }
                $html .= "</table>
                    <h3 style='margin:20px 0 10px; color:#374151;'>👤 ผู้จองมากที่สุด</h3>
                    <table>";
                foreach ($data['top_users'] as $u) {
                    $html .= "<tr><td>{$u['name']}</td><td style='text-align:right;font-weight:bold;'>{$u['count']}</td></tr>";
                }
                $html .= "</table>";
                break;

            case 'dormitory_summary':
                $rateColor = $data['occupancy_rate'] >= 80 ? '#ef4444' : ($data['occupancy_rate'] >= 50 ? '#f59e0b' : '#10b981');
                $html .= "
                    <div class='stat-grid'>
                        <div class='stat-box'>
                            <div class='value'>{$data['total_requests']}</div>
                            <div class='label'>คำขอทั้งหมด</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:#10b981;'>{$data['approved']}</div>
                            <div class='label'>อนุมัติ</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:#f59e0b;'>{$data['pending']}</div>
                            <div class='label'>รออนุมัติ</div>
                        </div>
                    </div>
                    <h3 style='margin:20px 0 10px; color:#374151;'>🏠 สถานะห้องพัก</h3>
                    <div class='stat-grid'>
                        <div class='stat-box'>
                            <div class='value'>{$data['occupied_rooms']}</div>
                            <div class='label'>ห้องมีคนพัก</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:#10b981;'>{$data['available_rooms']}</div>
                            <div class='label'>ห้องว่าง</div>
                        </div>
                        <div class='stat-box'>
                            <div class='value' style='color:{$rateColor};'>{$data['occupancy_rate']}%</div>
                            <div class='label'>อัตราการใช้งาน</div>
                        </div>
                    </div>";
                break;
        }

        $html .= "
                </div>
                <div class='footer'>
                    <p>รายงานนี้สร้างโดย MyHR Portal อัตโนมัติ<br>เวลา: {$data['generated_at']}</p>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Generate CSV file with raw data
     * @return string|null Path to CSV file or null if failed
     */
    public function generateCsvFile($reportType)
    {
        $tempDir = sys_get_temp_dir();
        $filename = "report_{$reportType}_" . date('Y-m-d_His') . ".csv";
        $filepath = $tempDir . DIRECTORY_SEPARATOR . $filename;

        try {
            $fp = fopen($filepath, 'w');
            if (!$fp) return null;

            // Add BOM for Excel UTF-8 support
            fwrite($fp, "\xEF\xBB\xBF");

            switch ($reportType) {
                case 'activity_summary':
                    fputcsv($fp, ['Action', 'User', 'IP Address', 'Created At']);
                    $stmt = $this->conn->query("
                        SELECT a.action, COALESCE(u.fullname, a.user_name) as user, a.ip_address, a.created_at
                        FROM cb_audit_logs a
                        LEFT JOIN users u ON a.user_id = u.id
                        WHERE a.created_at >= " . $this->getDateCondition('a.created_at') . "
                        ORDER BY a.created_at DESC
                        LIMIT 500
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($fp, array_values($row));
                    }
                    break;

                case 'login_report':
                    fputcsv($fp, ['User', 'Action', 'IP Address', 'Created At']);
                    $stmt = $this->conn->query("
                        SELECT COALESCE(u.fullname, a.user_name) as user, a.action, a.ip_address, a.created_at
                        FROM user_logins a
                        LEFT JOIN users u ON a.user_id = u.id
                        WHERE a.action IN ('login', 'logout') AND a.created_at >= " . $this->getDateCondition('a.created_at') . "
                        ORDER BY a.created_at DESC
                        LIMIT 500
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($fp, array_values($row));
                    }
                    break;

                case 'email_stats':
                    fputcsv($fp, ['Recipient', 'Subject', 'Status', 'Created At']);
                    $stmt = $this->conn->query("
                        SELECT recipient_email, subject, status, created_at
                        FROM email_logs
                        WHERE created_at >= " . $this->getDateCondition() . "
                        ORDER BY created_at DESC
                        LIMIT 500
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($fp, array_values($row));
                    }
                    break;

                case 'car_booking_summary':
                    fputcsv($fp, ['ID', 'Requester', 'Car', 'Purpose', 'Status', 'Start Date', 'End Date', 'Created At']);
                    $stmt = $this->conn->query("
                        SELECT b.id, u.fullname as requester, 
                               CASE 
                                   WHEN c.id IS NOT NULL THEN CONCAT(c.brand, ' ', c.model, ' (', c.license_plate, ')')
                                   WHEN f.id IS NOT NULL THEN CONCAT('Fleet Card: ', f.card_number)
                                   ELSE '-'
                               END as car,
                               b.purpose, b.status, b.start_time, b.end_time, b.created_at
                        FROM cb_bookings b
                        LEFT JOIN users u ON b.user_id = u.id
                        LEFT JOIN cb_cars c ON b.assigned_car_id = c.id
                        LEFT JOIN cb_fleet_cards f ON b.fleet_card_id = f.id
                        WHERE b.created_at >= " . $this->getDateCondition('b.created_at') . "
                        ORDER BY b.created_at DESC
                        LIMIT 500
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($fp, array_values($row));
                    }
                    break;

                case 'dormitory_summary':
                    fputcsv($fp, ['ID', 'User', 'Type', 'Status', 'Room', 'Created At']);
                    $stmt = $this->conn->query("
                        SELECT r.id, COALESCE(u.fullname, r.employee_name) as user, r.request_type as type, 
                               r.status, COALESCE(rm.room_number, '-') as room, r.created_at
                        FROM dorm_requests r
                        LEFT JOIN users u ON r.user_id = u.id
                        LEFT JOIN dorm_rooms rm ON r.room_id = rm.id
                        WHERE r.created_at >= " . $this->getDateCondition('r.created_at') . "
                        ORDER BY r.created_at DESC
                        LIMIT 500
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($fp, array_values($row));
                    }
                    break;

                default:
                    fclose($fp);
                    unlink($filepath);
                    return null;
            }

            fclose($fp);
            return $filepath;
        } catch (Exception $e) {
            error_log("CSV generation failed: " . $e->getMessage());
            if (isset($fp)) fclose($fp);
            return null;
        }
    }

    private function getDateCondition($columnName = 'created_at')
    {
        switch ($this->dateRange) {
            case 'last_24_hours':
                return "DATE_SUB(NOW(), INTERVAL 1 DAY)";
            case 'last_7_days':
                return "DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'last_30_days':
                return "DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case 'last_month':
                // First day of previous month to Last day of previous month
                return "'" . date('Y-m-01 00:00:00', strtotime('last month')) . "' AND $columnName <= '" . date('Y-m-t 23:59:59', strtotime('last month')) . "'";
            default:
                return "DATE_SUB(NOW(), INTERVAL 7 DAY)";
        }
    }

    private function getPeriodLabel()
    {
        switch ($this->dateRange) {
            case 'last_24_hours':
                return '24 ชั่วโมงที่ผ่านมา';
            case 'last_7_days':
                return '7 วันที่ผ่านมา';
            case 'last_30_days':
                return '30 วันที่ผ่านมา';
            case 'last_month':
                return 'เดือนที่แล้ว (' . date('F Y', strtotime('last month')) . ')';
            default:
                return '7 วันที่ผ่านมา';
        }
    }
}
