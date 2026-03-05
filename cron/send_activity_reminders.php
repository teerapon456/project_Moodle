<?php
// cron/send_activity_reminders.php
// Run this via cron: 0 8 * * * php /path/to/cron/send_activity_reminders.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database/Database.php';
require_once __DIR__ . '/../core/Services/EmailService.php';

// use Core\Helpers\MailHelper; // Removed
use Dotenv\Dotenv;

// Load Env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// DB Connection
$db = new Database();
$conn = $db->getConnection();

echo "[" . date('Y-m-d H:i:s') . "] Starting Activity Reminder Job...\n";

// Configuration: Days to remind before
$daysAhead = 7;

// Query Activities starting in exactly X days (or within range if preferred, let's do exactly 7 days for now to avoid spam)
// Or better: Next 7 days
$sql = "
    SELECT a.id, a.name, a.start_date, a.end_date, a.status, c.owner_id, u.email, u.fullname
    FROM ya_activities a
    JOIN ya_calendars c ON a.calendar_id = c.id
    JOIN users u ON c.owner_id = u.id
    WHERE a.start_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
    AND a.status != 'completed'
    AND a.status != 'cancelled'
    -- Prevent duplicate sending logic would require a 'last_reminded_at' column, 
    -- but for simplicity we'll just check today's batch or assume daily run covers specific date.
    -- To be safe, let's pick activities starting exactly 'tomorrow' or 'in 3 days' or 'in 7 days'.
    -- Let's do: Starts in exactly 7 days OR starts tomorrow.
    AND (
        DATE(a.start_date) = DATE(DATE_ADD(NOW(), INTERVAL 7 DAY)) 
        OR 
        DATE(a.start_date) = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))
    )
";

$stmt = $conn->prepare($sql);
$stmt->execute([$daysAhead]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($activities) . " activities to remind.\n";

$sentCount = 0;
foreach ($activities as $act) {
    echo "Processing: {$act['name']} ({$act['email']})... ";

    $subject = "Reminder: Upcoming Activity - " . $act['name'];
    $startDate = date('d M Y', strtotime($act['start_date']));

    $body = "
        <h2>Activity Reminder</h2>
        <p>Dear {$act['fullname']},</p>
        <p>This is a reminder that the following activity is coming up:</p>
        <ul>
            <li><strong>Activity:</strong> {$act['name']}</li>
            <li><strong>Start Date:</strong> {$startDate}</li>
            <li><strong>Status:</strong> " . ucfirst($act['status']) . "</li>
        </ul>
        <p>Please log in to the MyHR Portal to view details or update progress.</p>
        <br>
        <p>Best Regards,<br>MyHR System</p>
    ";

    $sent = EmailService::sendRawEmail($act['email'], $subject, $body);

    if ($sent) {
        echo "Sent!\n";
        $sentCount++;
    } else {
        echo "Failed.\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Job Complete. Sent $sentCount emails.\n";
