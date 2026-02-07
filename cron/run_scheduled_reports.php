<?php

/**
 * Cron script to run scheduled reports
 * Should be run every minute via cron/scheduler
 */

require_once __DIR__ . '/../core/Database/Database.php';
require_once __DIR__ . '/../core/Services/EmailService.php';
require_once __DIR__ . '/../core/Services/ReportGenerator.php';
require_once __DIR__ . '/../core/Config/Env.php';

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

$database = new Database();
$conn = $database->getConnection();

echo "[" . date('Y-m-d H:i:s') . "] Checking scheduled reports...\n";

// Get due reports
// Daily: time matches
// Weekly: day matches AND time matches
// Monthly: day matches AND time matches
$currentDay = date('j'); // 1-31
$currentWeekDay = date('N'); // 1 (Mon) - 7 (Sun)
$currentTime = date('H:i');

$sql = "
    SELECT * FROM scheduled_reports 
    WHERE is_active = 1 
    AND (
        (schedule_type = 'daily' AND DATE_FORMAT(schedule_time, '%H:%i') = :time)
        OR 
        (schedule_type = 'weekly' AND schedule_day = :weekday AND DATE_FORMAT(schedule_time, '%H:%i') = :time)
        OR
        (schedule_type = 'monthly' AND schedule_day = :day AND DATE_FORMAT(schedule_time, '%H:%i') = :time)
    )
    AND (last_sent_at IS NULL OR DATE(last_sent_at) < CURDATE())
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':time' => $currentTime,
        ':weekday' => $currentWeekDay,
        ':day' => $currentDay
    ]);

    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($reports) . " due reports.\n";

    foreach ($reports as $report) {
        processReport($report, $conn);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

function processReport($report, $conn)
{
    echo "Processing report: {$report['name']} (ID: {$report['id']})\n";

    try {
        $recipients = json_decode($report['recipients'], true) ?: [];
        if (empty($recipients)) {
            echo "- No recipients, skipping.\n";
            return;
        }

        // Determine date range
        $dateRange = 'last_7_days'; // Default
        if ($report['schedule_type'] === 'daily') $dateRange = 'last_24_hours';
        elseif ($report['schedule_type'] === 'weekly') $dateRange = 'last_7_days';
        elseif ($report['schedule_type'] === 'monthly') $dateRange = 'last_30_days';

        // Generate report
        $generator = new ReportGenerator($dateRange);
        $reportData = $generator->generate($report['report_type']);
        $emailBody = $generator->buildEmailBody($report['report_type'], $reportData);

        // Generate CSV attachment
        $csvPath = $generator->generateCsvFile($report['report_type']);
        $csvName = "report_" . $report['report_type'] . "_" . date('Y-m-d') . ".csv";

        // Send emails
        $sent = 0;
        foreach ($recipients as $email) {
            try {
                EmailService::sendRawEmail(
                    $email,
                    "[MyHR Portal] {$report['name']}",
                    $emailBody,
                    $csvPath,
                    $csvName
                );
                $sent++;
            } catch (Exception $e) {
                echo "- Failed to send to $email: " . $e->getMessage() . "\n";
            }
        }

        // Clean up temp file
        if ($csvPath && file_exists($csvPath)) {
            @unlink($csvPath);
        }

        // Update last sent time
        if ($sent > 0) {
            $stmt = $conn->prepare("UPDATE scheduled_reports SET last_sent_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $report['id']]);
            echo "- Sent to $sent recipients.\n";
        }
    } catch (Exception $e) {
        echo "- Error generating report: " . $e->getMessage() . "\n";
    }
}
