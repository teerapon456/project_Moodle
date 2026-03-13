<?php
// cron/sync_users.php

// 1. Load Portal Database Connection
require_once __DIR__ . '/../core/Database/Database.php';
require_once __DIR__ . '/../core/Services/EmailService.php';
require_once __DIR__ . '/../vendor/autoload.php';

echo "[SYNC] Starting User Synchronization at " . date('Y-m-d H:i:s') . "\n";

try {
    // ---------------------------------------------------------
    // 1. Connect to Databases
    // ---------------------------------------------------------

    // Portal DB (Using standard Database helper)
    $db = new Database();
    $portalConn = $db->getConnection();
    $portalConn->exec("SET NAMES 'utf8mb4'");
    echo "[SYNC] Connected to Portal DB.\n";

    // Moodle DB (On the same host as Portal DB now)
    $moodleDb   = Env::get('MOODLE_DB_NAME') ?? 'moodle';
    $dbHost     = Env::get('DB_HOST', 'db');
    $dbUser     = Env::get('DB_USER');
    $dbPass     = Env::get('DB_PASS');
    
    $dsn = "mysql:host=$dbHost;dbname=$moodleDb;charset=utf8mb4";
    $moodleConn = new PDO($dsn, $dbUser, $dbPass);
    $moodleConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $moodleConn->exec("SET NAMES 'utf8mb4'");
    echo "[SYNC] Connected to Moodle DB ($moodleDb).\n";

    // ---------------------------------------------------------
    // 2. Fetch Users from Portal
    // ---------------------------------------------------------
    $sql = "SELECT id, username, email, fullname, Level3Name, OrgUnitName, is_active FROM users";
    $stmt = $portalConn->prepare($sql);
    $stmt->execute();
    $portalUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "[SYNC] Found " . count($portalUsers) . " users in Portal.\n";

    $stats = ['created' => 0, 'updated' => 0, 'suspended' => 0, 'errors' => 0];

    // ---------------------------------------------------------
    // 3. Sync Logic
    // ---------------------------------------------------------
    foreach ($portalUsers as $user) {
        $username = trim(strtolower($user['username'] ?? ''));
        $email = trim(strtolower($user['email'] ?? ''));

        if (empty($username) || empty($email)) continue;

        // Split Fullname
        $parts = explode(' ', trim($user['fullname']));
        $firstname = array_shift($parts) ?: 'User';
        $lastname = implode(' ', $parts) ?: '-';

        $department = $user['Level3Name'] ?: '';
        $institution = $user['OrgUnitName'] ?: '';
        $suspended = ($user['is_active'] == 1) ? 0 : 1;
        $authMethod = 'db'; // Changed from 'myhrauth' as requested

        try {
            // Check if user exists in Moodle
            $checkStmt = $moodleConn->prepare("SELECT id, username FROM mdl_user WHERE username = ?");
            $checkStmt->execute([$username]);
            $moodleUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($moodleUser) {
                // UPDATE
                $updateSql = "UPDATE mdl_user SET 
                    email = ?, 
                    firstname = ?, 
                    lastname = ?, 
                    department = ?, 
                    institution = ?, 
                    suspended = ?,
                    timemodified = ?
                    WHERE id = ?";
                $updateStmt = $moodleConn->prepare($updateSql);
                $updateStmt->execute([
                    $email, $firstname, $lastname, $department, $institution,
                    $suspended, time(), $moodleUser['id']
                ]);
                $stats['updated']++;
                if ($suspended === 1) $stats['suspended']++;
            } else {
                // CREATE
                $passwordHash = password_hash(bin2hex(random_bytes(10)), PASSWORD_BCRYPT);

                $insertSql = "INSERT INTO mdl_user (
                    auth, confirmed, policyagreed, deleted, suspended, mnethostid, 
                    username, password, email, firstname, lastname, 
                    department, institution, city, country, lang, 
                    timezone, firstaccess, lastaccess, timecreated, timemodified
                ) VALUES (
                    ?, 1, 0, 0, ?, 1, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, 'Bangkok', 'TH', 'th', 
                    'Asia/Bangkok', 0, 0, ?, ?
                )";

                $insertStmt = $moodleConn->prepare($insertSql);
                $insertStmt->execute([
                    $authMethod, $suspended, $username, $passwordHash, $email, 
                    $firstname, $lastname, $department, $institution,
                    time(), time()
                ]);
                $stats['created']++;
            }
        } catch (Exception $e) {
            echo "[ERROR] Failed to sync user {$username}: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }

    echo "[SYNC] Completed.\n";
    $summaryLog = "Summary:\n" .
        "- Created: {$stats['created']}\n" .
        "- Updated: {$stats['updated']}\n" .
        "- Suspended: {$stats['suspended']}\n" .
        "- Errors: {$stats['errors']}\n";
    echo $summaryLog;

    // ---------------------------------------------------------
    // 4. Send Email Notification
    // ---------------------------------------------------------
    try {
        // Fetch target email from Permission module (ID 3)
        $stmtEmail = $portalConn->prepare("SELECT setting_value FROM system_settings WHERE module_id = 3 AND setting_key = 'notification_email'");
        $stmtEmail->execute();
        $toEmail = $stmtEmail->fetchColumn();

        if ($toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $subject = "User Sync Report (Portal -> Moodle) - " . date('d/m/Y');
            $messageHtml = "
                <div style='font-family: sans-serif; max-width: 600px; border: 1px solid #eee; padding: 20px;'>
                    <h2 style='color: #2c3e50;'>สรุปผลการซิงค์ข้อมูลผู้ใช้</h2>
                    <p><strong>ระบบต้นทาง:</strong> MyHR Portal<br>
                    <strong>ระบบปลายทาง:</strong> Moodle LMS<br>
                    <strong>เวลา:</strong> " . date('d/m/Y H:i:s') . "</p>
                    <hr style='border: 0; border-top: 1px solid #eee;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0;'>สร้างใหม่ (Created):</td>
                            <td style='padding: 8px 0; text-align: right; font-weight: bold;'>{$stats['created']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0;'>อัปเดตข้อมูล (Updated):</td>
                            <td style='padding: 8px 0; text-align: right; font-weight: bold;'>{$stats['updated']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0;'>ระงับสิทธิ์ (Suspended):</td>
                            <td style='padding: 8px 0; text-align: right; font-weight: bold;'>{$stats['suspended']}</td>
                        </tr>
                        <tr style='color: " . ($stats['errors'] > 0 ? '#e74c3c' : '#27ae60') . ";'>
                            <td style='padding: 8px 0; font-weight: bold;'>ข้อผิดพลาด (Errors):</td>
                            <td style='padding: 8px 0; text-align: right; font-weight: bold;'>{$stats['errors']}</td>
                        </tr>
                    </table>
                    <p style='font-size: 12px; color: #7f8c8d; margin-top: 20px;'>* รายงานนี้ถูกส่งอัตโนมัติจากระบบ MyHR Portal</p>
                </div>
            ";

            $sent = EmailService::sendMail($toEmail, $subject, $messageHtml);
            if ($sent) {
                echo "[SYNC] Notification email sent to $toEmail.\n";
            } else {
                echo "[SYNC] Failed to send notification email.\n";
            }
        }
    } catch (Exception $e) {
        echo "[SYNC] Notification Error: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "[CRITICAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
