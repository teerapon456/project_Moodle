<?php
/**
 * verify_email.php
 * - ตรวจสอบโทเคนยืนยันอีเมล
 * - เปิดใช้งานผู้ใช้ (is_active = 1) และ mark token is_used = 1
 * - (ตัดออกแล้ว) ไม่ส่งอีเมล "ยืนยันอีเมลสำเร็จ" ซ้ำ
 */

date_default_timezone_set('Asia/Bangkok');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/functions.php'; // include config + autoload + helpers
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ----------------------------------------------------
// PROD error handling (แสดงผลหน้าเว็บให้น้อยสุด แต่ log ลงไฟล์)
// ----------------------------------------------------
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
if (!is_dir(__DIR__ . '/../logs')) @mkdir(__DIR__ . '/../logs', 0775, true);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

// ----------------------------------------------------
// DB & base url
// ----------------------------------------------------
if (!isset($conn) || !($conn instanceof mysqli)) {
    error_log('[verify_email] DB connection not initialized.');
    http_response_code(500);
    exit('DB not ready');
}
$conn->query("SET time_zone = '+07:00'");

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = "{$protocol}://{$host}";

// ----------------------------------------------------
// Helpers – โหลดเทมเพลตอีเมล & แทนค่า (คงไว้หากอนาคตต้องใช้ แต่ไฟล์นี้ไม่ส่งเมลอีกแล้ว)
// ----------------------------------------------------
function get_email_template(mysqli $conn, string $template_key, string $lang = 'en'): ?array {
    $stmt = $conn->prepare("SELECT subject_en, subject_th, subject_my, body_en, body_th, body_my 
                            FROM iga_email_templates 
                            WHERE template_key = ? LIMIT 1");
    $stmt->bind_param("s", $template_key);
    if (!$stmt->execute()) {
        error_log('[verify_email] get_email_template execute error: ' . $stmt->error);
        $stmt->close();
        return null;
    }
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $stmt->close();
        return null;
    }
    $row = $res->fetch_assoc();
    $stmt->close();

    $subj = $row['subject_' . $lang] ?? '';
    $body = $row['body_'   . $lang] ?? '';
    if (trim($subj) === '' && isset($row['subject_en'])) $subj = $row['subject_en'];
    if (trim($body) === '' && isset($row['body_en']))     $body = $row['body_en'];

    return ['subject' => (string)$subj, 'body' => (string)$body];
}

function build_template_vars(array $userRow, string $siteName, string $siteUrl, string $verifyLink): array {
    $fullName = $userRow['full_name'] ?? '';
    $firstName = $userRow['first_name'] ?? '';
    $lastName  = $userRow['last_name']  ?? '';
    if (!$firstName && !$lastName && $fullName) {
        $parts = preg_split('/\s+/', trim($fullName));
        $firstName = $parts[0] ?? '';
        $lastName  = count($parts) > 1 ? implode(' ', array_slice($parts,1)) : '';
    }

    $now = new DateTime('now', new DateTimeZone('Asia/Bangkok'));
    $contact = 'e-hris@inteqc.com';

    $txt = [
        'first_name'        => $firstName,
        'last_name'         => $lastName,
        'full_name'         => $fullName ?: trim($firstName . ' ' . $lastName),
        'username'          => $userRow['username'] ?? '',
        'email'             => $userRow['email'] ?? '',
        'site_name'         => $siteName,
        'site_url'          => $siteUrl,
        'current_date'      => $now->format('Y-m-d'),
        'current_year'      => $now->format('Y'),
        'verify_link'       => $verifyLink,
        'verification_link' => $verifyLink,
        'contact_email'     => $contact,
    ];

    $html = [];
    foreach ($txt as $k => $v) $html[$k] = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $html['verify_link_anchor']       = '<a href="'.$html['verify_link'].'">'.$html['verify_link'].'</a>';
    $html['verification_link_anchor'] = $html['verify_link_anchor'];
    $html['contact_email']            = '<a href="mailto:'.$html['contact_email'].'">'.$html['contact_email'].'</a>';

    return [$html, $txt];
}

function replace_placeholders(string $text, array $vars): string {
    if ($text === '' || empty($vars)) return $text;
    foreach ($vars as $k => $v) {
        $pattern = '/\{\s*' . preg_quote($k, '/') . '\s*\}/u';
        $text = preg_replace($pattern, (string)$v, $text);
    }
    return $text;
}

// ----------------------------------------------------
// รับ token
// ----------------------------------------------------
$token = $_GET['token'] ?? '';
if ($token === '') {
    set_alert(get_text('alert_invalid_link'), 'danger');
    header("Location: {$base_url}/register");
    exit;
}

try {
    $conn->begin_transaction();

    // NOTE: ตรวจสอบว่า key join ถูกต้องกับสคีมาของคุณ
    // ที่นี่ใช้ ev.user_id = u.person_id ตามโค้ดเดิมของคุณ
    $sql = "SELECT u.user_id, u.email, u.full_name, u.username, u.is_active, ev.expires_at, ev.is_used
            FROM iga_email_verification_tokens ev
            JOIN users u ON ev.user_id = u.person_id
            WHERE ev.token = ?
            FOR UPDATE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $stmt->close();
        $conn->rollback();
        set_alert(get_text('alert_invalid_token'), 'danger');
        header("Location: {$base_url}/register");
        exit;
    }

    $row = $res->fetch_assoc();
    $stmt->close();

    $userId   = $row['user_id'];
    $email    = $row['email'];
    $isActive = (int)$row['is_active'];
    $isUsed   = (int)$row['is_used'];
    $expTs    = strtotime((string)$row['expires_at']);

    // ตรวจสถานะ token
    if ($isUsed === 1) {
        set_alert(get_text('alert_token_already_used'), 'warning');
        $conn->commit();
        header("Location: {$base_url}/login");
        exit;
    }
    if (time() > $expTs) {
        set_alert(get_text('alert_token_expired'), 'danger');
        $conn->commit();
        header("Location: {$base_url}/register");
        exit;
    }
    if ($isActive === 1) {
        // ผู้ใช้ active อยู่แล้ว → mark token used และพาไป login
        $stmt = $conn->prepare("UPDATE iga_email_verification_tokens SET is_used = 1 WHERE token = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        set_alert(get_text('alert_email_already_verified'), 'info');
        header("Location: {$base_url}/login");
        exit;
    }

    // ยืนยันผู้ใช้ + mark token used
    $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE iga_email_verification_tokens SET is_used = 1 WHERE token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->close();

    // commit ธุรกรรม—และ **ไม่** ส่งอีเมลซ้ำ
    $conn->commit();

    // แจ้งผู้ใช้และพาไป login
    set_alert(get_text('alert_email_verified'), 'success');
    header("Location: {$base_url}/login");
    exit;

} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignore) {}
    error_log('[verify_email] Exception: ' . $e->getMessage());
    set_alert(get_text('alert_verification_failed'), 'danger');
    header("Location: {$base_url}/register");
    exit;
} finally {
    // ไม่จำเป็นต้องปิด $conn ที่นี่ ถ้าแอปส่วนอื่นยังใช้งานต่อ
}
