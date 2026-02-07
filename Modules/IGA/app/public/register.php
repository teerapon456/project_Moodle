<?php

/**
 * register.php (Completed)
 * - Commit ธุรกรรมก่อนส่งเมล (เมลล้มเหลวไม่ rollback ผู้ใช้/โทเคน)
 * - ปรับตรวจสอบ input, duplicate, CSRF
 * - แก้ฟอร์ม/ปิดแท็ก/alert ซ้ำซ้อน
 * - เก็บ log ปลอดภัย
 */

date_default_timezone_set('Asia/Bangkok');

// --- HTTPS ENFORCEMENT (ระวังเวลาทดสอบบน localhost ถ้าไม่มี https จะ redirect) ---
if (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
) {
    // OK
} else {
    $redirect_to_https = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: ' . $redirect_to_https);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php'; // ควร include config/db_connect ภายในนี้

// ตั้ง timezone DB (ถ้ามี $conn)
if (isset($conn) && $conn instanceof mysqli) {
    $conn->query("SET time_zone = '+07:00'");
}

// --- ERROR LOGGING (Production แนะนำปิด display_errors) ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
ini_set('error_log', $logDir . '/php-error.log');

// --- สลับภาษา ---
if (isset($_GET['lang'])) {
    $new_lang = preg_replace('/[^a-z]/i', '', $_GET['lang']);
    set_language($new_lang ?: 'en');
    header("Location: register");
    exit();
}

// ถ้า login แล้วเด้งออก
redirect_if_logged_in();

// base url
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = "{$protocol}://{$host}";

$page_title = get_text('register_page_title') . " - INTEQC GLOBAL ASSESSMENT";

// ค่าฟอร์ม (คืนค่าเดิมกรณี validation fail)
$username  = '';
$full_name = '';
$email     = '';
// สร้าง person_id แบบแฮชยาว 64 ตัวอักษร (hex)
if (!function_exists('generate_person_id')) {
    function generate_person_id(string $username, string $email): string
    {
        try {
            $rand = bin2hex(random_bytes(32)); // 256-bit entropy
        } catch (Throwable $e) {
            $rand = bin2hex(openssl_random_pseudo_bytes(32));
        }
        // ผลลัพธ์ 64 หลัก (SHA-256)
        return hash('sha256', 'pid|' . $username . '|' . $email . '|' . microtime(true) . '|' . $rand);
    }
}

function generate_user_id(): string
{
    // UUID v4 (36 char)
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}


// POST handler
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF
    if (!verify_csrf_token()) {
        set_alert(get_text('alert_invalid_csrf_token'), "danger");
        header("Location: register");
        exit();
    }

    // รับค่า
    $username         = trim($_POST['username'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');

    // Validate เบื้องต้น
    // Validate เบื้องต้น
    if ($username === '' || $password === '' || $confirm_password === '' || $full_name === '' || $email === '') {
        set_alert(get_text('alert_empty_fields'), "danger");
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        set_alert(get_text('alert_invalid_username_format'), "danger");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_alert(get_text('alert_invalid_email_format'), "danger");

        // ---- กติกาใหม่: a-z / A-Z / 0-9 เท่านั้น และ >= 8 ตัว ----
    } elseif (!preg_match('/^[A-Za-z0-9]{8,}$/', $password)) {
        set_alert(
            get_text('password_policy_hint')
                ?: 'Password must be at least 8 characters and contain only English letters or digits (A–Z, a–z, 0–9).',
            "danger"
        );
    } elseif ($password !== $confirm_password) {
        set_alert(get_text('alert_password_mismatch'), "danger");
    } else {
        // ตรวจซ้ำใน DB
        try {
            if (!isset($conn) || !($conn instanceof mysqli)) {
                throw new Exception('DB connection not ready.');
            }

            // username exists?
            $stmt_username = $conn->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
            $stmt_username->bind_param("s", $username);
            $stmt_username->execute();
            $res_u = $stmt_username->get_result();
            $exists_username = ($res_u && $res_u->num_rows > 0);
            $stmt_username->close();

            if ($exists_username) {
                set_alert(get_text('alert_username_exists') . ' ' . htmlspecialchars($username), "danger");
                goto render_page;
            }

            // email exists?
            $stmt_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $stmt_email->bind_param("s", $email);
            $stmt_email->execute();
            $res_e = $stmt_email->get_result();
            $exists_email = ($res_e && $res_e->num_rows > 0);
            $stmt_email->close();

            if ($exists_email) {
                set_alert(get_text('alert_email_exists') . ' ' . htmlspecialchars($email), "danger");
                goto render_page;
            }

            // เริ่มธุรกรรม: insert users (is_active=0) + insert token -> COMMIT
            $conn->begin_transaction();

            $default_role_id = 7; // applicant
            $is_active       = 0;
            $password_hash   = hash_password($password);

            // 👉 สร้าง person_id แบบ hash ก่อน
            $person_id = generate_person_id($username, $email);
            $user_id   = generate_user_id();   // <--- ใช้ UUID ใหม่

            $stmt = $conn->prepare("INSERT INTO users 
                (person_id, user_id, username, email, password_hash, full_name, role_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssii", $person_id, $user_id, $username, $email, $password_hash, $full_name, $default_role_id, $is_active);


            if (!$stmt->execute()) {
                throw new Exception("Failed to create user: " . $conn->error);
            }
            $stmt->close();

            // token 1 hr
            $verification_token = bin2hex(random_bytes(32));
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt_token = $conn->prepare("INSERT INTO iga_email_verification_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt_token->bind_param("sss", $person_id, $verification_token, $expiry_time);
            if (!$stmt_token->execute()) {
                throw new Exception("Failed to save verification token: " . $conn->error);
            }
            $stmt_token->close();

            // ✅ commit ก่อน แล้วค่อยพยายามส่งอีเมล
            $conn->commit();

            // พยายามส่งอีเมล (ถ้าพัง แค่ log; ไม่ rollback ผู้ใช้)
            $verification_link = "{$base_url}/verify_email.php?token={$verification_token}";
            $mail_ok = true;

            if (function_exists('send_verification_email')) {
                try {
                    $mail_ok = send_verification_email($email, $full_name ?: $username, $verification_link);
                } catch (Throwable $e) {
                    $mail_ok = false;
                    error_log('[register] send_verification_email() threw: ' . $e->getMessage());
                }
            } else {
                // ถ้าไม่มีฟังก์ชันนี้ในระบบ ให้เตือนใน log
                error_log('[register] Warning: send_verification_email() not found. Skip sending email.');
                $mail_ok = false;
            }

            if (!$mail_ok) {
                // ไม่ interrupt ผู้ใช้
                error_log("[register] Failed to send verification email to {$email}. Link={$verification_link}");
                // คุณจะ set_alert เป็น warning บอกผู้ใช้ให้กด “ส่งใหม่” ภายหลังได้เช่น:
                // set_alert(get_text('alert_register_success_email_verify_but_mail_failed'), 'warning');
            }

            // สมัครสำเร็จ → ไปหน้า login
            set_alert(get_text('alert_register_success_email_verify'), "success");
            header("Location: /login");
            exit();
        } catch (Throwable $e) {
            // ถ้า transaction ค้าง ให้ rollback ไว้ก่อน
            try {
                if ($conn && $conn->errno === 0) {
                    $conn->rollback();
                }
            } catch (Throwable $ignore) {
            }
            error_log('[register] Exception: ' . $e->getMessage());
            set_alert(get_text('alert_tech_error') ?: 'An error occurred during registration. Please try again.', "danger");
        }
    }
}

render_page:
$current_lang = $_SESSION['lang'] ?? 'en';
$lang_names = [
    'th' => 'ภาษาไทย',
    'en' => 'English',
    'my' => 'မြန်မာ'
];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/css/custom.css">
    <style>
        .register-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f8f9fa;
            padding: 20px
        }

        .register-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto
        }

        .register-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, .08);
            overflow: hidden
        }

        .register-header {
            background: #A21D21;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            color: #fff
        }

        .register-header h2 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600
        }

        .register-body {
            padding: 2rem
        }

        .form-control:focus {
            border-color: #A21D21;
            box-shadow: 0 0 0 .25rem rgba(162, 29, 33, .15)
        }

        .input-group-text {
            background: #f8f9fa;
            border-right: none
        }

        .password-toggle {
            cursor: pointer;
            border-left: none;
            padding: .5rem 1rem;
            display: flex;
            align-items: center;
            border-radius: 0 .375rem .375rem 0
        }

        .btn-primary-custom {
            background: #A21D21;
            border: none;
            padding: 12px;
            font-weight: 500;
            width: 100%
        }

        .btn-primary-custom:hover {
            background: #A21D21
        }

        .language-switcher {
            position: absolute;
            top: 1rem;
            right: 1rem
        }
    </style>
</head>

<body class="register-page">

    <div class="register-container">
        <div class="register-card">
            <div class="register-header position-relative">
                <h2><?php echo get_text('register_page_title'); ?></h2>

                <div class="language-switcher">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-globe me-2"></i><?php echo $lang_names[$current_lang] ?? 'English'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                            <li><a class="dropdown-item" href="?lang=en">English</a></li>
                            <li><a class="dropdown-item" href="?lang=th">ภาษาไทย</a></li>
                            <li><a class="dropdown-item" href="?lang=my">မြန်မာ</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="register-body">
                <?php echo get_alert(); ?>

                <form action="/register" method="POST" class="needs-validation" novalidate>
                    <?php echo generate_csrf_token(); ?>

                    <div class="mb-3">
                        <label for="full_name" class="form-label"><?php echo get_text('full_name_label'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                placeholder="<?php echo get_text('full_name_placeholder'); ?>"
                                value="<?php echo htmlspecialchars($full_name); ?>" required>
                            <div class="invalid-feedback"><?php echo get_text('full_name_required'); ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label"><?php echo get_text('username_label'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-at text-muted"></i></span>
                            <input type="text" class="form-control" id="username" name="username"
                                placeholder="<?php echo get_text('username_placeholder'); ?>"
                                value="<?php echo htmlspecialchars($username); ?>" required>
                            <div class="invalid-feedback"><?php echo get_text('username_required'); ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo get_text('email_label'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="<?php echo get_text('email_placeholder'); ?>"
                                value="<?php echo htmlspecialchars($email); ?>" required>
                            <div class="invalid-feedback"><?php echo get_text('email_required'); ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo get_text('password_label'); ?></label>
                        <div class="password-input-group input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                            <span class="password-toggle" data-target="password"><i class="fas fa-eye"></i></span>
                            <div class="invalid-feedback"><?php echo get_text('password_required'); ?></div>
                        </div>
                        <div class="form-text text-muted small mt-1"><?php echo get_text('password_strength_hint'); ?></div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label"><?php echo get_text('confirm_password_label'); ?></label>
                        <div class="password-input-group input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                            <span class="password-toggle" data-target="confirm_password"><i class="fas fa-eye"></i></span>
                            <div class="invalid-feedback"><?php echo get_text('password_required'); ?></div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary-custom">
                            <?php echo get_text('register_button'); ?>
                        </button>
                    </div>
                </form>

                <hr class="my-4">
                <p class="text-center mb-0 small text-muted">
                    <?php echo get_text('has_account'); ?>
                    <a href="/login" class="text-primary fw-bold text-decoration-none">
                        <?php echo get_text('login_here'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap validation
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // Toggle password visibility
        document.querySelectorAll('.password-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = btn.getAttribute('data-target');
                const input = document.getElementById(id);
                if (!input) return;
                const isPass = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPass ? 'text' : 'password');
                btn.querySelector('i').classList.toggle('fa-eye');
                btn.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>