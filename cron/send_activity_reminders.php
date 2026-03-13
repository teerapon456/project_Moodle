<?php
// cron/send_activity_reminders.php
// Run this via cron: 0 8 * * * php /path/to/cron/send_activity_reminders.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database/Database.php';
require_once __DIR__ . '/../core/Services/EmailService.php';
require_once __DIR__ . '/../core/Services/NotificationService.php';
require_once __DIR__ . '/../core/Helpers/UrlHelper.php';

use Dotenv\Dotenv;
use Core\Helpers\UrlHelper;

// Load Env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// DB Connection
$db = new Database();
$conn = $db->getConnection();

echo "[" . date('Y-m-d H:i:s') . "] Starting Activity & Milestone Reminder Job...\n";

// --- 1. Activity Owner Reminders (Existing Logic) ---
$sqlActivities = "
    SELECT a.id, a.name, a.start_date, a.status, u.email, u.fullname
    FROM ya_activities a
    JOIN ya_calendars c ON a.calendar_id = c.id
    JOIN users u ON c.owner_id = u.id
    WHERE a.status NOT IN ('completed', 'cancelled')
    AND (
        DATE(a.start_date) = DATE(DATE_ADD(NOW(), INTERVAL 7 DAY)) 
        OR 
        DATE(a.start_date) = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))
    )
";

$stmtAct = $conn->query($sqlActivities);
$activities = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($activities) . " activities for owner reminders.\n";

foreach ($activities as $act) {
    $subject = "Reminder: Upcoming Activity - " . $act['name'];
    $startDate = date('d M Y', strtotime($act['start_date']));
    $body = "
        <div style='font-family: sans-serif;'>
            <h2>Activity Reminder</h2>
            <p>Dear {$act['fullname']},</p>
            <p>This is a reminder that the activity <strong>{$act['name']}</strong> is starting on <strong>{$startDate}</strong>.</p>
            <p>Please log in to the MyHR Portal to manage this activity.</p>
        </div>
    ";
    
    // Send Email & Notification with error handling
    try {
        $emailResult = EmailService::sendRawEmail($act['email'], $subject, $body);
        $notifResult = NotificationService::sendToEmail($act['email'], 'info', $subject, "Activity '{$act['name']}' is starting on {$startDate}.", [], "Modules/YearlyActivity/index.php?page=summary_5w2h&id={$act['id']}");
        
        $status = ($emailResult && $notifResult) ? "SUCCESS" : "PARTIAL FAILURE";
        echo "[$status] Sent reminder to owner {$act['fullname']} for activity {$act['name']}\n";
    } catch (\Exception $e) {
        echo "[ERROR] Failed to send reminder to {$act['fullname']}: " . $e->getMessage() . "\n";
    }
}

// --- 2. Milestone R & A Role Reminders (New Requirement) ---
$sqlMilestones = "
    SELECT 
        m.id as milestone_id, 
        m.name as milestone_name, 
        m.start_date, 
        m.due_date,
        m.activity_id,
        a.name as activity_name,
        r.user_id,
        r.role,
        u.email,
        u.fullname,
        DATEDIFF(m.start_date, NOW()) as days_until
    FROM ya_milestones m
    JOIN ya_activities a ON m.activity_id = a.id
    JOIN ya_milestone_rasci r ON m.id = r.milestone_id
    JOIN users u ON r.user_id = u.id
    WHERE m.status NOT IN ('completed', 'cancelled')
    AND r.role IN ('R', 'A')
    AND (
        DATE(m.start_date) = DATE(DATE_ADD(NOW(), INTERVAL 14 DAY))
        OR DATE(m.due_date) = DATE(DATE_ADD(NOW(), INTERVAL 14 DAY))
        OR DATE(m.start_date) = DATE(DATE_ADD(NOW(), INTERVAL 7 DAY))
        OR DATE(m.due_date) = DATE(DATE_ADD(NOW(), INTERVAL 7 DAY))
        OR DATE(m.start_date) = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))
        OR DATE(m.due_date) = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))
    )
";

$stmtMs = $conn->query($sqlMilestones);
if (!$stmtMs) {
    echo "SQL Error:\n";
    print_r($conn->errorInfo());
    exit;
}
$milestones = $stmtMs->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($milestones) . " milestone assignments (R/A) to remind.\n";

foreach ($milestones as $ms) {
    $roleName = ($ms['role'] === 'R') ? 'Responsible (ผู้รับผิดชอบหลัก)' : 'Accountable (ผู้อนุมัติ/ผู้รับผิดชอบผล)';
    $daysCount = abs($ms['days_until']);
    $daysText = $daysCount . " วัน";
    $startDate = date('d M Y', strtotime($ms['start_date']));
    
    $subject = "แจ้งเตือน: Milestone '{$ms['milestone_name']}' จะเริ่มในอีก {$daysText}";
    
    $detailUrl = UrlHelper::url("Modules/YearlyActivity/index.php?page=summary_5w2h&id={$ms['activity_id']}");
    $inAppUrl = "Modules/YearlyActivity/index.php?page=summary_5w2h&id={$ms['activity_id']}";
    
    $body = "
        <div style='font-family: sans-serif;'>
            <h2 style='color: #d32f2f;'>แจ้งเตือนการเริ่ม Milestone</h2>
            <p>เรียนคุณ {$ms['fullname']},</p>
            <p>Milestone <strong>{$ms['milestone_name']}</strong> ภายใต้กิจกรรม <strong>{$ms['activity_name']}</strong> กำลังจะเริ่มต้นในอีก <strong>{$daysText}</strong> ({$startDate})</p>
            <p>คุณได้รับมอบหมายในบทบาท: <strong>{$roleName}</strong></p>
            <p>กรุณาตรวจสอบความพร้อมและเตรียมการดำเนินการตามแผนที่วางไว้</p>
            <hr>
            <p><a href='{$detailUrl}' style='background: #d32f2f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>ดูรายละเอียดใน MyHR</a></p>
            <br>
            <p>ขอแสดงความนับถือ,<br>ระบบ MyHR Portal</p>
        </div>
    ";

    // Send Email & Notification with error handling
    try {
        $emailResult = EmailService::sendRawEmail($ms['email'], $subject, $body);
        
        // Send In-App Notification
        $notifId = NotificationService::create(
            $ms['user_id'], 
            'warning', 
            $subject, 
            "Milestone '{$ms['milestone_name']}' ภายใต้กิจกรรม '{$ms['activity_name']}' จะเริ่มในวันที่ {$startDate}", 
            [], 
            $inAppUrl
        );

        $status = ($emailResult && $notifId) ? "SUCCESS" : "PARTIAL FAILURE";
        echo "[$status] Sent reminder to {$ms['fullname']} ({$ms['role']}) for milestone {$ms['milestone_name']}\n";
    } catch (\Exception $e) {
        echo "[ERROR] Failed to send milestone reminder to {$ms['fullname']}: " . $e->getMessage() . "\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Job Complete.\n";
