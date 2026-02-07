<?php
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../Helpers/PermissionHelper.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

/**
 * PDFController - Generates PDF reports using mPDF
 */
class PDFController
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // Generate Activity Report
    public function generateActivityReportHTML()
    {
        if (!$this->conn) return '';

        $activities = $this->conn->query("
            SELECT * FROM ya_activities 
            ORDER BY start_date DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $total = count($activities);
        $byStatus = [];
        foreach ($activities as $a) {
            $status = $a['status'] ?? 'unknown';
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
        }

        return $this->buildReportHTML('Yearly Activities Report', $activities, $byStatus, $total);
    }

    // Generate RASCI Report
    public function generateRasciReportHTML()
    {
        if (!$this->conn) return '';

        $activities = $this->conn->query("SELECT id, name as title FROM ya_activities ORDER BY start_date")->fetchAll(PDO::FETCH_ASSOC);

        $users = $this->conn->query("
            SELECT DISTINCT u.id, u.fullname 
            FROM users u 
            JOIN ya_milestone_rasci r ON u.id = r.user_id
        ")->fetchAll(PDO::FETCH_ASSOC);

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

        return $this->buildRasciHTML($activities, $users, $assignments);
    }

    // Generate Risk Report
    public function generateRiskReportHTML()
    {
        if (!$this->conn) return '';
        $risks = $this->conn->query("SELECT * FROM ya_milestone_risks ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        return $this->buildRiskHTML($risks);
    }

    // Build main report HTML
    private function buildReportHTML($title, $activities, $byStatus, $total)
    {
        $date = date('F j, Y');
        $statusColors = ['completed' => '#10b981', 'progress' => '#f59e0b', 'planned' => '#6366f1', 'hold' => '#8b5cf6', 'cancelled' => '#ef4444', 'proposed' => '#9ca3af', 'in_progress' => '#f59e0b', 'incoming' => '#3b82f6'];

        $html = $this->getHTMLHeader($title);

        $html .= "<div class='summary'>";
        $html .= "<h2>Summary</h2>";
        $html .= "<div class='stats'>";
        $html .= "<div class='stat'><div class='stat-value'>{$total}</div><div class='stat-label'>Total Activities</div></div>";
        foreach ($byStatus as $status => $count) {
            $color = $statusColors[$status] ?? '#6b7280';
            $pct = $total > 0 ? round(($count / $total) * 100) : 0;
            $html .= "<div class='stat'><div class='stat-value' style='color: {$color}'>{$count}</div><div class='stat-label'>" . ucfirst(str_replace('_', ' ', $status)) . " ({$pct}%)</div></div>";
        }
        $html .= "</div></div>";

        $html .= "<h2>Activities</h2>";
        $html .= "<table><thead><tr><th>Activity Name</th><th>Type</th><th>Status</th><th>Progress</th><th>Start</th><th>End</th></tr></thead><tbody>";

        foreach ($activities as $a) {
            $color = $statusColors[$a['status']] ?? '#6b7280';
            $statusLabel = ucfirst(str_replace('_', ' ', $a['status']));
            $progress = $a['progress'] ?? 0;

            $html .= "<tr>";
            $html .= "<td><strong>" . htmlspecialchars($a['name']) . "</strong></td>";
            $html .= "<td>" . htmlspecialchars($a['type']) . "</td>";
            $html .= "<td><span class='badge' style='background: {$color}'>{$statusLabel}</span></td>";
            $html .= "<td>{$progress}%</td>";
            $html .= "<td>" . ($a['start_date'] ? date('M j, Y', strtotime($a['start_date'])) : '-') . "</td>";
            $html .= "<td>" . ($a['end_date'] ? date('M j, Y', strtotime($a['end_date'])) : '-') . "</td>";
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";
        $html .= "</body></html>";
        return $html;
    }

    // Build RASCI report HTML
    private function buildRasciHTML($activities, $users, $assignments)
    {
        $html = $this->getHTMLHeader('RASCI Matrix Report');

        $html .= "<h2>RASCI Matrix</h2>";
        $html .= "<p class='legend'><strong>R</strong>=Responsible, <strong>A</strong>=Accountable, <strong>S</strong>=Support, <strong>C</strong>=Consulted, <strong>I</strong>=Informed</p>";
        $html .= "<table><thead><tr><th>Activity</th>";

        foreach ($users as $u) {
            $html .= "<th class='user-col'>" . htmlspecialchars($u['fullname']) . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        foreach ($activities as $a) {
            $html .= "<tr><td><strong>" . htmlspecialchars($a['title']) . "</strong></td>";
            foreach ($users as $u) {
                $role = $assignments[$a['id']][$u['id']] ?? '';
                $html .= "<td class='role-cell'>{$role}</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";
        $html .= "</body></html>";
        return $html;
    }

    // Build Risk report HTML
    private function buildRiskHTML($risks)
    {
        $html = $this->getHTMLHeader('Risk Assessment Report');

        $impactColors = ['critical' => '#ef4444', 'high' => '#f97316', 'medium' => '#eab308', 'low' => '#22c55e'];

        $html .= "<h2>Risk Register</h2>";
        $html .= "<table><thead><tr><th>Risk Description</th><th>Probability</th><th>Impact</th><th>Mitigation</th></tr></thead><tbody>";

        foreach ($risks as $r) {
            $impactVal = $r['impact'];
            $probVal = $r['probability'];

            $impactColor = '#22c55e'; // Low
            if ($impactVal >= 4) $impactColor = '#ef4444'; // Crit
            elseif ($impactVal == 3) $impactColor = '#f97316'; // High/Med

            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($r['risk_description']) . "</td>";
            $html .= "<td>{$probVal}</td>";
            $html .= "<td><span class='badge' style='background: {$impactColor}'>{$impactVal}</span></td>";
            $html .= "<td>" . htmlspecialchars($r['mitigation_plan'] ?? '') . "</td>";
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";
        $html .= "</body></html>";
        return $html;
    }

    // HTML header optimized for mPDF
    private function getHTMLHeader($title)
    {
        $date = date('F j, Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <style>
        body { font-family: 'Garuda'; color: #333; line-height: 1.5; font-size: 11pt; }
        h1 { color: #6366f1; font-size: 20pt; margin-bottom: 5px; }
        h2 { color: #333; border-bottom: 2px solid #6366f1; padding-bottom: 5px; margin-top: 20px; font-size: 14pt; }
        .meta { color: #6b7280; margin-bottom: 20px; font-size: 9pt; }
        .summary { background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .stats { margin-top: 10px; }
        .stat { display: inline-block; width: 30%; text-align: center; vertical-align: top; margin-bottom: 10px; }
        .stat-value { font-size: 16pt; font-weight: bold; color: #6366f1; }
        .stat-label { font-size: 10pt; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 10pt; table-layout: fixed; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #6366f1; color: white; font-weight: bold; }
        tr:nth-child(even) { background: #f9fafb; }
        .badge { padding: 2px 6px; border-radius: 4px; color: white; font-size: 9pt; text-transform: uppercase; font-weight: bold; }
        .role-cell { text-align: center; font-weight: bold; }
        .user-col { font-size: 9pt; text-align: center; width: 60px; transform: rotate(-90deg); height: 100px; }
        .legend { background: #fef3c7; padding: 8px; border-radius: 5px; font-size: 10pt; border: 1px solid #fcd34d; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>{$title}</h1>
    <p class="meta">Generated on {$date} | MyHR Portal - Yearly Activity System</p>
HTML;
    }

    // Output report
    public function outputReport($type = 'activities')
    {
        switch ($type) {
            case 'rasci':
                $html = $this->generateRasciReportHTML();
                $filename = 'rasci_report.pdf';
                break;
            case 'risks':
                $html = $this->generateRiskReportHTML();
                $filename = 'risk_report.pdf';
                break;
            default:
                $html = $this->generateActivityReportHTML();
                $filename = 'activities_report.pdf';
        }

        try {
            // Fix: Muti-byte strings support
            // Use /tmp for temp files to avoid permission issues
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'default_font' => 'Garuda', // Use Thai capable font if available, or default
                'tempDir' => '/tmp/mpdf'    // Use system temp directory
            ]);

            // Auto-script support for Thai if configured in mPDF, otherwise regular utf-8
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;

            $mpdf->WriteHTML($html);
            $mpdf->Output($filename, 'I'); // I = Inline, D = Download
            exit;
        } catch (\Mpdf\MpdfException $e) {
            echo "PDF Error: " . $e->getMessage();
        }
    }
}
