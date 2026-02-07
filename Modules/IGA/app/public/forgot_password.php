<?php
// forgot_password.php — Old-working style, upgraded to DB templates + safe SMTP relay (no auth)
declare(strict_types=1);
date_default_timezone_set('Asia/Bangkok');

/* 0) Session ก่อนทุกอย่าง */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 1) Errors & logging (ไม่พ่นบนจอในโปรดักชัน) */
error_reporting(E_ALL);
$displayErrors = getenv('APP_DISPLAY_ERRORS') === '1' ? '1' : '0';
ini_set('display_errors', $displayErrors);
ini_set('log_errors', '1');
$logDir  = __DIR__ . '/../logs';
$logFile = $logDir . '/php-error.log';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('error_log', $logFile);

/* 2) Includes (functions.php ของคุณรวม config + autoload + $conn) */
require_once __DIR__ . '/../includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* 3) ตั้ง timezone ที่ DB (ถ้าเป็น MySQL/MariaDB) */

if (isset($conn) && $conn instanceof mysqli) {
    @$conn->query("SET time_zone = '+07:00'");
}

/* 4) Base URL */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = "{$protocol}://{$host}"; // เหมือนเวอร์ชันเก่าใช้โดเมนราก

/* 5) Page strings */
$page_title = get_text('forgot_password') . ' - ' . (get_text('app_name') ?: 'INTEQC GLOBAL ASSESSMENT');

/* ===== Helpers (ยกจากเวอร์ชันใหม่มาใช้) ===== */
function sanitize_email_html_min(string $html): string
{
    $html = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $html);
    $html = preg_replace('#<iframe\b[^>]*>(.*?)</iframe>#is', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/si', '', $html);
    $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:.*?\2/si', '$1="#"', $html);
    return $html;
}

function load_email_template(mysqli $conn, string $template_key): ?array
{
    $sql = "SELECT * FROM iga_email_templates WHERE template_key = ? LIMIT 1";
    if (!$stmt = $conn->prepare($sql)) return null;
    $stmt->bind_param("s", $template_key);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: null;
    $res->free();
    $stmt->close();
    return $row;
}

function pick_lang_field(array $row, string $base, string $lang): string
{
    $cand = $row[$base . '_' . $lang] ?? '';
    if ($cand !== '') return $cand;
    foreach (['en', 'th', 'my'] as $l) {
        if (!empty($row[$base . '_' . $l])) return $row[$base . '_' . $l];
    }
    return '';
}

function replace_placeholders(string $text, array $vars): string
{
    if ($text === '') return '';
    foreach ($vars as $k => $v) {
        $text = str_replace('{' . $k . '}', $v, $text);
    }
    return $text;
}

function build_template_vars(array $userRow, string $siteName, string $siteUrl, string $resetLink): array
{
    $fullName  = $userRow['full_name'] ?? '';
    $firstName = '';
    $lastName = '';
    if ($fullName) {
        $parts = preg_split('/\s+/', trim($fullName));
        $firstName = $parts[0] ?? '';
        $lastName  = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : '';
    }
    $now = new DateTime('now', new DateTimeZone('Asia/Bangkok'));

    // Plain text vars (สำหรับ subject และ AltBody)
    $txt = [
        'first_name'      => $firstName ?: ($userRow['first_name'] ?? ''),
        'last_name'       => $lastName  ?: ($userRow['last_name']  ?? ''),
        'full_name'       => $fullName ?: trim(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? '')),
        'username'        => $userRow['username'] ?? '',
        'email'           => $userRow['email'] ?? '',
        'site_name'       => $siteName,
        'site_url'        => $siteUrl,
        'reset_link'      => $resetLink,
        'reset_link_href' => $resetLink,
        'current_date'    => $now->format('Y-m-d'),
        'current_year'    => $now->format('Y'),
        'contact_email'   => 'e-hris@inteqc.com',
    ];

    // HTML vars (escape + linkify)
    $html = [];
    foreach ($txt as $k => $v) {
        $html[$k] = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
    $html['reset_link_anchor'] = '<a href="' . $html['reset_link'] . '">' . $html['reset_link'] . '</a>';
    $html['contact_email']     = '<a href="mailto:' . $html['contact_email'] . '">' . $html['contact_email'] . '</a>';

    return [$html, $txt];
}

/* 6) SMTP config แบบเวอร์ชันเก่า (relay ไม่ auth) + รองรับคอนสแตนต์ */
function cfg($const, $var, $default = null)
{
    if (defined($const)) return constant($const);
    if (isset($GLOBALS[$var]) && $GLOBALS[$var] !== '') return $GLOBALS[$var];
    $env = getenv($const);
    if ($env !== false && $env !== '') return $env;
    return $default;
}
$smtpHost    = cfg('SMTP_HOST', 'smtp_host', 'localhost');
$smtpPort    = (int) cfg('SMTP_PORT', 'smtp_port', 25);
$fromEmail   = cfg('SMTP_FROM_EMAIL', 'smtp_from_email', 'no-reply@localhost');
$fromName    = cfg('SMTP_FROM_NAME', 'smtp_from_name', (get_text('app_name') ?: 'INTEQC GLOBAL ASSESSMENT'));
$smtpCharset = cfg('SMTP_CHARSET', 'smtp_charset', 'UTF-8');
// ใช้ relay ภายใน → ไม่ auth
$smtpAuth    = false;

/* 7) State หน้า */
$email   = '';
$success = false;
$inline  = null;

/* 8) Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $inline = ['type' => 'danger', 'text' => (get_text('alert_invalid_email_format') ?: 'อีเมลไม่ถูกต้อง')];
        set_alert($inline['text'], 'danger');
    } else {
        // หา user
        $user = null;
        if (isset($conn) && $conn instanceof mysqli) {
            if ($stmt = $conn->prepare('SELECT user_id, full_name, username, email FROM users WHERE email = ? LIMIT 1')) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $res  = $stmt->get_result();
                $user = $res->fetch_assoc() ?: null;
                $res->free();
                $stmt->close();
            } else {
                error_log('forgot_password: prepare user query failed: ' . $conn->error);
            }
        } else {
            error_log('forgot_password: $conn invalid');
        }

        // ตอบสำเร็จแบบ generic (กัน enumeration)
        $ok = function () use (&$success, &$inline) {
            $success = true;
            $msg = (get_text('alert_reset_password_sent') ?: 'ถ้ามีบัญชีกับเรา ระบบได้ส่งลิงก์รีเซ็ตรหัสผ่านไปที่อีเมลของคุณแล้ว');
            $inline = ['type' => 'success', 'text' => $msg];
            set_alert($msg, 'success');
        };

        if ($user && $conn instanceof mysqli) {
            // 1) ลบ token เก่า
            if ($del = $conn->prepare('DELETE FROM password_resets WHERE user_id = ?')) {
                $del->bind_param('s', $user['user_id']);
                if (!$del->execute()) error_log('forgot_password: delete token failed: ' . $del->error);
                $del->close();
            }

            // 2) สร้าง token ใหม่
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            if ($ins = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')) {
                $ins->bind_param('sss', $user['user_id'], $token, $expires_at);
                if (!$ins->execute()) error_log('forgot_password: insert token failed: ' . $ins->error);
                $ins->close();
            }

            // 3) ลิงก์รีเซ็ต — ยึดแบบไฟล์จริง (เหมือนเวอร์ชันเก่า)
            $reset_link = $base_url . '/reset_password.php?token=' . $token;
            // ถ้าคุณมี routing ที่ /reset-password ให้สลับเป็นบรรทัดล่างแทน
            // $reset_link = $base_url . '/reset-password?token=' . $token;

            // 4) โหลดเทมเพลตจาก DB
            $tpl  = load_email_template($conn, 'password_reset');
            $lang = $_SESSION['lang'] ?? 'en';

            if ($tpl) {
                $subject_tpl = pick_lang_field($tpl, 'subject', $lang);
                $body_tpl    = pick_lang_field($tpl, 'body',    $lang);
            } else {
                // fallback เหมือนใหม่ แต่คงสไตล์เก่า
                $subject_tpl = (get_text('password_reset_subject') ?: 'Password reset request');
                $body_tpl    = (get_text('password_reset_email_body_template') ?:
                    "Hello {full_name},\nWe received a request to reset your password on {site_name}.\n" .
                    "Click the link below to set a new password:\n{reset_link}\n\n" .
                    "If you did not request this, please contact {contact_email}."
                );
            }

            // 5) เตรียมตัวแปรแทนที่
            [$vars_html, $vars_txt] = build_template_vars(
                $user,
                (get_text('app_name') ?: 'INTEQC GLOBAL ASSESSMENT'),
                $base_url,
                $reset_link
            );

            $subject_final = replace_placeholders($subject_tpl, $vars_txt);   // subject = plain text
            $body_html     = sanitize_email_html_min(replace_placeholders($body_tpl, $vars_html));
            $body_text     = replace_placeholders(strip_tags($body_tpl), $vars_txt);
            $alt_plain     = preg_replace(
                "/\r?\n/",
                "\r\n",
                html_entity_decode($body_text, ENT_QUOTES, 'UTF-8')
            );

            // 6) ส่งเมล (SMTP relay, ไม่มี auth — เหมือนเวอร์ชันเก่า)
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host     = $smtpHost;
                $mail->Port     = $smtpPort;
                $mail->SMTPAuth = false;           // สำคัญ: relay ภายใน
                $mail->CharSet  = $smtpCharset;
                $mail->SMTPDebug   = 0;           // ไม่พ่นบนจอ
                $mail->Debugoutput = function ($s) {
                    error_log('PHPMailer: ' . trim($s));
                };

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($user['email'], $user['full_name'] ?: ($user['username'] ?: $user['email']));

                $mail->isHTML(true);
                $mail->Subject = $subject_final;
                $mail->Body    = nl2br($body_html);  // เผื่อเทมเพลตมี \n
                $mail->AltBody = $alt_plain;

                $mail->send();
                $ok();
            } catch (Exception $e) {
                error_log('forgot_password: mail failed for ' . $email . ' | ' . $e->getMessage());
                $ok(); // ยังคงตอบ success แบบ generic
            }
        } else {
            // ไม่พบ user → ตอบเหมือนกันเสมอ
            $ok();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/custom.css">
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-header-custom"></div>
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4 text-primary-custom">
                            <?php echo htmlspecialchars(get_text('forgot_password')); ?>
                        </h2>

                        <?php
                        // flash จากระบบเดิม
                        // echo get_alert();
                        // inline alert (เพื่อให้ผู้ใช้เห็นทันทีแม้ไม่มี redirect)
                        if (!empty($inline)) {
                            $t = htmlspecialchars($inline['type'], ENT_QUOTES, 'UTF-8');
                            $x = htmlspecialchars($inline['text'], ENT_QUOTES, 'UTF-8');
                            echo "<div class='alert alert-{$t}'>{$x}</div>";
                        }
                        ?>

                        <?php if (!$success): ?>
                            <form action="/forgot-password" method="POST" novalidate>
                                <div class="mb-3">
                                    <label for="email" class="form-label"><?php echo htmlspecialchars(get_text('email_label')); ?></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary-custom btn-lg">
                                        <?php echo htmlspecialchars(get_text('forgot_password')); ?>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <hr class="my-4">
                        <p class="text-center mb-0 small">
                            <a href="login.php" class="text-primary-custom">&larr; <?php echo get_text('go_back'); ?></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>