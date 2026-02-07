<?php
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../Helpers/PermissionHelper.php';

class ExportController
{
    private $db;
    private $conn;
    private $perm;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->perm = YAPermissionHelper::getInstance();
    }

    // Export activities to CSV
    public function exportActivitiesCSV()
    {
        if (!$this->conn) return;

        // Fixed Query: title -> name, activity_type -> type, removed parent_id, removed priority
        $activities = $this->conn->query("
            SELECT id, name, description, type, status, start_date, end_date, created_at
            FROM ya_activities 
            ORDER BY start_date DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=activities_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        // BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header
        fputcsv($output, ['ID', 'Name', 'Description', 'Type', 'Status', 'Start Date', 'End Date', 'Created']);

        foreach ($activities as $act) {
            fputcsv($output, [
                $act['id'],
                $act['name'],
                $act['description'],
                $act['type'],
                $act['status'],
                $act['start_date'],
                $act['end_date'],
                $act['created_at']
            ]);
        }

        fclose($output);
    }

    // Export RASCI Matrix to CSV
    public function exportRasciCSV()
    {
        if (!$this->conn) return;

        // Get activities
        $activities = $this->conn->query("SELECT id, name as title FROM ya_activities ORDER BY start_date")->fetchAll(PDO::FETCH_ASSOC);

        // Get users from members involved in RASCI
        $users = $this->conn->query("
            SELECT DISTINCT u.id, u.fullname 
            FROM users u 
            JOIN ya_milestone_rasci r ON u.id = r.user_id
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Get assignments via Milestones
        $rasci = $this->conn->query("
            SELECT m.activity_id, r.user_id, r.role 
            FROM ya_milestone_rasci r
            JOIN ya_milestones m ON r.milestone_id = m.id
        ")->fetchAll(PDO::FETCH_ASSOC);

        $assignments = [];
        foreach ($rasci as $r) {
            $current = $assignments[$r['activity_id']][$r['user_id']] ?? '';
            if (strpos($current, $r['role']) === false) {
                $assignments[$r['activity_id']][$r['user_id']] = $current ? "$current,{$r['role']}" : $r['role'];
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=rasci_matrix_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header row
        $header = ['Activity'];
        foreach ($users as $u) $header[] = $u['fullname'];
        fputcsv($output, $header);

        // Data rows
        foreach ($activities as $act) {
            $row = [$act['title']];
            foreach ($users as $u) {
                $row[] = $assignments[$act['id']][$u['id']] ?? '';
            }
            fputcsv($output, $row);
        }

        fclose($output);
    }

    // Export Risks to CSV
    public function exportRisksCSV()
    {
        if (!$this->conn) return;

        // Fixed: Use ya_milestone_risks and available columns
        $risks = $this->conn->query("
            SELECT id, milestone_id, risk_description, probability, impact, mitigation_plan
            FROM ya_milestone_risks ORDER BY id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=risks_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['ID', 'Milestone ID', 'Risk Description', 'Probability', 'Impact', 'Mitigation Plan']);

        foreach ($risks as $risk) {
            fputcsv($output, array_values($risk));
        }

        fclose($output);
    }

    // Export Report Summary to CSV
    public function exportReportCSV()
    {
        if (!$this->conn) return;

        $activities = $this->conn->query("SELECT * FROM ya_activities ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);

        // Calculate stats
        $byStatus = [];
        $byType = [];
        foreach ($activities as $a) {
            $status = $a['status'] ?? 'unknown';
            $type = $a['type'] ?? 'other'; // Fixed type column

            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=report_summary_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['YEARLY ACTIVITY REPORT', date('Y-m-d')]);
        fputcsv($output, []);
        fputcsv($output, ['STATUS BREAKDOWN']);
        fputcsv($output, ['Status', 'Count']);
        foreach ($byStatus as $status => $count) {
            fputcsv($output, [$status, $count]);
        }
        fputcsv($output, []);
        fputcsv($output, ['TYPE BREAKDOWN']);
        fputcsv($output, ['Type', 'Count']);
        foreach ($byType as $type => $count) {
            fputcsv($output, [$type, $count]);
        }
        fputcsv($output, []);
        fputcsv($output, ['ACTIVITY LIST']);
        fputcsv($output, ['Name', 'Type', 'Status', 'Start', 'End']);
        foreach ($activities as $a) {
            fputcsv($output, [$a['name'], $a['type'], $a['status'], $a['start_date'], $a['end_date']]);
        }

        fclose($output);
    }

    // Import activities from Excel
    public function importActivitiesFromExcel($file, $calendarId)
    {
        if (!$this->conn || !$this->perm->canEdit()) {
            return ['success' => false, 'message' => 'No permission'];
        }

        if (!file_exists($file) || !is_readable($file)) {
            return ['success' => false, 'message' => 'Cannot read file'];
        }

        if (empty($calendarId)) {
            return ['success' => false, 'message' => 'Target Calendar ID required'];
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error loading Excel file: ' . $e->getMessage()];
        }

        // Remove Header
        $header = array_shift($rows);

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            // Expected Format: Name, Type, Status, Start Date, End Date, Description
            // Adjust indices based on assumed template. Let's assume:
            // 0: Name, 1: Type, 2: Status, 3: Start, 4: End, 5: Description

            // Skip empty rows
            if (empty($row[0])) continue;

            try {
                $sql = "INSERT INTO ya_activities (calendar_id, name, type, status, start_date, end_date, description, created_by)
                        VALUES (:cal_id, :name, :type, :status, :start, :end, :desc, :user)";
                $stmt = $this->conn->prepare($sql);

                // Date formatting helper
                $formatDate = function ($val) {
                    if (empty($val)) return null;
                    if (is_numeric($val)) {
                        // Excel date serial
                        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
                    }
                    return date('Y-m-d', strtotime($val));
                };

                $stmt->execute([
                    ':cal_id' => $calendarId,
                    ':name'   => $row[0],
                    ':type'   => $row[1] ?? 'event',
                    ':status' => $row[2] ?? 'planned',
                    ':start'  => $formatDate($row[3] ?? null),
                    ':end'    => $formatDate($row[4] ?? null),
                    ':desc'   => $row[5] ?? '',
                    ':user'   => $this->perm->getUserId()
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . " error: " . $e->getMessage();
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ];
    }

    public function downloadTemplate()
    {
        // Fallback to CSV to avoid PhpSpreadsheet dependency issues
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="activity_import_template.csv"');
        header('Cache-Control: max-age=0'); // No cache

        $output = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        $headers = ['Activity Name', 'Type (event/project)', 'Status (planned/in_progress)', 'Start Date (YYYY-MM-DD)', 'End Date (YYYY-MM-DD)', 'Description'];
        fputcsv($output, $headers);

        // Example Row
        $example = ['Annual General Meeting', 'event', 'planned', date('Y-m-d'), date('Y-m-d', strtotime('+1 day')), 'Meeting with shareholders'];
        fputcsv($output, $example);

        fclose($output);
        exit;
    }
}
