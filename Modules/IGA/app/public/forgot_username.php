<?php
// forgot-username.php (Completed: use iga_email_templates system)
date_default_timezone_set('Asia/Bangkok');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php'; // must include config + autoload + (db) $conn
// PHPMailer is autoloaded via composer in functions.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// use PHPMailer\PHPMailer\SMTP; // uncomment if you want ->SMTPDebug

// --- DB timezone (ignore errors) ---
if (isset($conn) && $conn instanceof mysqli) {
    @$conn->query("SET time_zone = '+07:00'");
}

// --- Debug (development only) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
if (!is_dir(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0775, true);
}
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

// --- Base URLs for assets ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$project_root_dir = basename(dirname(__DIR__)); // e.g. integrity_test
$base_url = "{$protocol}://{$host}/{$project_root_dir}";
$assets_base_url = "{$base_url}";

// --- Page text ---
$page_title = get_text('forgot_username') . " - " . (get_text('app_name') ?: 'INTEQC GLOBAL ASSESSMENT');

$email = '';
$success = false;

// ------------ Helpers ------------
/**
 * Quick sanitize HTML for email (basic; for production consider HTMLPurifier)
 */
function sanitize_email_html_min($html)
{
    // remove script/iframe
    $html = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $html);
    $html = preg_replace('#<iframe\b[^>]*>(.*?)</iframe>#is', '', $html);
    // remove inline on* events
    $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/si', '', $html);
    // neutralize javascript: links
    $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:.*?\2/si', '$1="#"', $html);
    return $html;
}

/**
 * Load a template row by key from email_templates
 */
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

/**
 * Pick subject/body by current UI language with fallback
 */
function pick_lang_field(array $row, string $base, string $lang): string
{
    $cand = $row[$base . '_' . $lang] ?? '';
    if (!empty($cand)) return $cand;
    // fallback to EN -> TH -> MY
    foreach (['en', 'th', 'my'] as $l) {
        if (!empty($row[$base . '_' . $l])) return $row[$base . '_' . $l];
    }
    return '';
}

/**
 * Replace placeholders in text (HTML or plain text)
 * $vars should already be HTML-escaped for HTML mode
 */
function replace_placeholders(string $text, array $vars): string
{
    if ($text === '') return '';
    // support both {full_name} and {username_full_name} etc.
    foreach ($vars as $key => $val) {
        $text = str_replace('{' . $key . '}', $val, $text);
    }
    return $text;
}

/**
 * Build variables for HTML and TEXT separately
 */
function build_template_vars(array $userRow, string $siteName, string $siteUrl): array
{
    // Derive name parts
    $fullName = $userRow['full_name'] ?? '';
    $firstName = '';
    $lastName  = '';
    if (!empty($fullName)) {
        $parts = preg_split('/\s+/', trim($fullName));
        $firstName = $parts[0] ?? '';
        $lastName  = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : '';
    }

    $now = new DateTime('now', new DateTimeZone('Asia/Bangkok'));
    $current_date_th = $now->format('Y-m-d');

    // TEXT values (no HTML)
    $VTXT = [
        'first_name'          => $firstName ?: ($userRow['first_name'] ?? ''),
        'last_name'           => $lastName ?: ($userRow['last_name'] ?? ''),
        'full_name'           => $fullName ?: (($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? '')),
        'username_full_name'  => $fullName ?: (($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? '')),
        'username'            => $userRow['username'] ?? '',
        'email'               => $userRow['email'] ?? '',
        'recovered_username'  => $userRow['username'] ?? '',
        'site_name'           => $siteName,
        'site_url'            => $siteUrl,
        'current_date'        => $current_date_th,
        'current_year'        => $now->format('Y'),
        'contact_email'       => 'e-hris@inteqc.com',
    ];

    // HTML values (escape; linkify contact_email)
    $VHTML = $VTXT;
    foreach ($VHTML as $k => $v) {
        $VHTML[$k] = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
    // Linkify contact email for HTML
    $VHTML['contact_email'] = '<a href="mailto:' . $VHTML['contact_email'] . '">' . $VHTML['contact_email'] . '</a>';

    return [$VHTML, $VTXT];
}

// ------------ Form submit ------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_alert(get_text('alert_invalid_email_format'), "danger");
    } else {
        // Find user by email
        $user = null;
        if (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare("SELECT user_id, full_name, username, email FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc() ?: null;
            $res->free();
            $stmt->close();
        }

        // Always show generic success to prevent enumeration
        $genericSuccess = function () use (&$success) {
            $success = true;
            set_alert(get_text('alert_username_sent'), "success");
        };

        // If user exists, try to send email using template system
        if ($user) {
            // Load template row
            $tpl = load_email_template($conn, 'username_reminder');

            // Choose language
            $lang = $_SESSION['lang'] ?? 'en';

            // Subject & Body from template or fallback
            if ($tpl) {
                $subject_tpl = pick_lang_field($tpl, 'subject', $lang);
                $body_tpl    = pick_lang_field($tpl, 'body', $lang);
            } else {
                // Fallback to language strings
                $subject_tpl = (get_text('username_recovery_subject') ?: 'Your username');
                $body_tpl    = (get_text('username_recovery_email_body_template') ?: "Hello {username_full_name},\nYour username is: {recovered_username}\nIf you didn't request this, please contact {contact_email}.");
            }

            // Build replacement vars (HTML/TXT)
            [$vars_html, $vars_txt] = build_template_vars($user, (get_text('app_name') ?: 'INTEQC GLOBAL ASSESSMENT'), $base_url);

            // Replace placeholders
            $subject_final = replace_placeholders($subject_tpl, $vars_txt); // subject should be plain text
            $body_html     = replace_placeholders($body_tpl, $vars_html);
            $body_text     = replace_placeholders(strip_tags($body_tpl), $vars_txt);

            // Minimal sanitize HTML
            $body_html = sanitize_email_html_min($body_html);

            // Send email
            try {
                $mail = new PHPMailer(true);
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // uncomment for debugging
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = false;
                $mail->CharSet    = 'UTF-8';

                $mail->Port = SMTP_PORT;

                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($user['email'], $user['full_name'] ?: $user['username']);

                $mail->isHTML(true);
                $mail->Subject = $subject_final;
                $mail->Body    = nl2br($body_html); // ensure newlines are kept if template uses \n
                $mail->AltBody = str_replace("\n", "\r\n", $body_text);

                $mail->send();

                // Always generic success
                $genericSuccess();
            } catch (Exception $e) {
                error_log('Mailer Error (Username Recovery): ' . $e->getMessage() . ' for email: ' . $email);
                // In production you might still show generic success; here we show a safe generic success to avoid enumeration
                $genericSuccess();
            }
        } else {
            // Email not found -> still generic
            $genericSuccess();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                            <?php echo htmlspecialchars(get_text('forgot_username')); ?>
                        </h2>

                        <?php echo get_alert(); ?>

                        <?php if (!$success): ?>
                            <form action="/forgot-username" method="POST" novalidate>
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <?php echo htmlspecialchars(get_text('email_label')); ?>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary-custom btn-lg">
                                        <?php echo htmlspecialchars(get_text('send_username')); ?>
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