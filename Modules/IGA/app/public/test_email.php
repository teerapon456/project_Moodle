<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เรียกใช้ไฟล์ functions.php (ซึ่งจะเรียก db_connect.php และอื่นๆ ต่อไป)
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$test_email = 'poowadej_n@inteqc.com';
$test_full_name = 'Test User';
$test_verification_link = 'https://your-website.com/verify.php?token=test';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Send Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 40px; background-color: #f4f7f9; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        h1, h2 { border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .error-box { background-color: #fce8e6; border: 1px solid #f7c6c4; border-radius: 5px; padding: 15px; margin-top: 20px; }
        .error-details { background-color: #2d2d2d; color: #f1f1f1; padding: 15px; border-radius: 4px; font-family: "Courier New", Courier, monospace; font-size: 14px; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Starting Email Send Test... 📧</h1>
        <p>Sending test email to: <strong><?php echo htmlspecialchars($test_email); ?></strong></p>
        <hr>

        <?php
        try {
            // เรียกใช้ฟังก์ชันส่งอีเมล
            $result = send_verification_email($test_email, $test_full_name, $test_verification_link);

            if ($result['success']) {
                echo "<h2>✅ Test Email Sent Successfully!</h2>";
                echo "<p>Please check the inbox for <strong>" . htmlspecialchars($test_email) . "</strong>.</p>";
            } else {
                echo "<h2>❌ Failed to Send Test Email.</h2>";
                echo '<div class="error-box">';
                echo '<h3>Detailed Error Information:</h3>';
                // แสดงผล Error ที่ได้จากฟังก์ชัน (ข้อมูลถูก escape ไว้แล้ว)
                echo '<div class="error-details">' . ($result['error'] ?? 'No specific error message was returned.') . '</div>';
                echo '</div>';
            }

        } catch (Throwable $e) {
            // ดักจับ Fatal Error ที่อาจเกิดขึ้นก่อนเรียกฟังก์ชันส่งอีเมล
            echo "<h2>❌ A PHP Fatal Error Occurred!</h2>";
            echo "<p>This type of error usually happens before the email function can run. It indicates a problem with file includes, syntax, or configuration.</p>";
            echo '<div class="error-box">';
            echo '<h3>Error Details:</h3>';
            echo "<pre class='error-details'>" . htmlspecialchars($e->getMessage()) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
