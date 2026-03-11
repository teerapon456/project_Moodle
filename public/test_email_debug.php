<?php

/**
 * Standalone Email Test Script with Debugging
 */

// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../core/Config/Env.php';
require_once __DIR__ . '/../core/Database/Database.php';

// Mock some server variables if needed for BASE_PATH etc.
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SCRIPT_NAME'] = '/test_email_debug.php';
}

$message = '';
$debugOutput = '';

// Pre-initialize for display
$smtpPort = Env::get('SMTP_PORT', 25);
$smtpSecure = Env::get('SMTP_SECURE', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['to'])) {
    $to = trim($_POST['to']);
    $subject = trim($_POST['subject'] ?: 'Test Email Debug');
    $body = trim($_POST['body'] ?: 'This is a test email sent from the debug script.');
    $customUser = trim($_POST['smtp_user'] ?? '');
    $customPass = trim($_POST['smtp_pass'] ?? '');
    $customPort = trim($_POST['smtp_port'] ?? '');
    $customSecure = trim($_POST['smtp_secure'] ?? '');

    // Load Composer autoloader
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        $message = "<div style='color:red'>Error: vendor/autoload.php not found.</div>";
    } else {
        require_once $autoloadPath;

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Enable debugging
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function ($str, $level) use (&$debugOutput) {
                $debugOutput .= "[$level] " . htmlspecialchars($str) . "<br>";
            };

            // Get Config from Env
            $smtpHost = Env::get('SMTP_HOST', '');
            $smtpPort = !empty($customPort) ? $customPort : Env::get('SMTP_PORT', 25);
            $smtpUsername = !empty($customUser) ? $customUser : Env::get('SMTP_USERNAME', '');
            $smtpPassword = !empty($customPass) ? $customPass : Env::get('SMTP_PASSWORD', '');
            $smtpFromEmail = Env::get('SMTP_FROM_EMAIL', 'e-hris@inteqc.com');
            $smtpFromName = Env::get('SMTP_FROM_NAME', 'MyHR Test Debug');
            $smtpSecure = ($customSecure !== '') ? $customSecure : Env::get('SMTP_SECURE', '');

            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->Port       = (int)$smtpPort;

            if (!empty($smtpUsername)) {
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUsername;
                $mail->Password = $smtpPassword;
            } else {
                $mail->SMTPAuth = false;
            }

            $mail->SMTPSecure = $smtpSecure;
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 15;

            // AutoTLS logic similar to EmailService
            if (empty($smtpSecure) && $smtpPort == 25) {
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPAutoTLS = true;
            }

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom($smtpFromEmail, $smtpFromName);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            if ($mail->send()) {
                $message = "<div style='color:green; font-weight:bold;'>Email SENT successfully to $to!</div>";
            } else {
                $message = "<div style='color:red; font-weight:bold;'>Email FAILED to send.</div>";
            }
        } catch (Exception $e) {
            $message = "<div style='color:red; font-weight:bold;'>PHPMailer Exception: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Email Test Debug</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background: #A21D21;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #82171a;
        }

        .debug-box {
            background: #222;
            color: #0f0;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: monospace;
            margin-top: 20px;
            font-size: 13px;
        }

        .config-info {
            background: #eef;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Email Delivery Debugger</h1>

        <div class="config-info">
            <strong>Current SMTP Configuration:</strong><br>
            Host: <?php echo Env::get('SMTP_HOST', 'NOT SET'); ?><br>
            Port: <?php echo $smtpPort; ?> (Env: <?php echo Env::get('SMTP_PORT', '25'); ?>)<br>
            From: <?php echo Env::get('SMTP_FROM_EMAIL', 'NOT SET'); ?><br>
            Secure: <?php echo $smtpSecure ?: 'None'; ?> (Env: <?php echo Env::get('SMTP_SECURE', 'None'); ?>)
        </div>

        <?php echo $message; ?>
        <form method="POST">
            <div class="form-group" style="display:flex; gap:10px;">
                <div style="flex:1">
                    <label>SMTP User:</label>
                    <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($_POST['smtp_user'] ?? ''); ?>" placeholder="e.g. e-hris@inteqc.com">
                </div>
                <div style="flex:1">
                    <label>SMTP Pass:</label>
                    <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($_POST['smtp_pass'] ?? ''); ?>" placeholder="e.g. hris@2025">
                </div>
            </div>
            <div class="form-group" style="display:flex; gap:10px;">
                <div style="flex:1">
                    <label>SMTP Port (Override):</label>
                    <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($_POST['smtp_port'] ?? '25'); ?>">
                </div>
                <div style="flex:1">
                    <label>Encryption:</label>
                    <select name="smtp_secure" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="" <?php echo ($_POST['smtp_secure'] ?? '') === '' ? 'selected' : ''; ?>>None (AutoTLS)</option>
                        <option value="tls" <?php echo ($_POST['smtp_secure'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS (STARTTLS)</option>
                        <option value="ssl" <?php echo ($_POST['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (Implicit)</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>To (Email Address / DL):</label>
                <input type="text" name="to" value="<?php echo htmlspecialchars($_POST['to'] ?? ''); ?>" required placeholder="user@example.com or dl-group@example.com">
            </div>
            <div class="form-group">
                <label>Subject:</label>
                <input type="text" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? 'MyHR Test - ' . date('Y-m-d H:i:s')); ?>">
            </div>
            <div class="form-group">
                <label>Message Body (HTML):</label>
                <textarea name="body" rows="4"><?php echo htmlspecialchars($_POST['body'] ?? 'Hello, this is a test email to verify delivery to distribution lists.'); ?></textarea>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit">Send Test Email & View Logs</button>
                <button type="button" onclick="window.location.href=window.location.pathname" style="background: #6c757d;">Clear & Refresh</button>
            </div>
        </form>

        <?php if ($debugOutput): ?>
            <h3>SMTP Debug Logs:</h3>
            <div class="debug-box">
                <?php echo $debugOutput; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>