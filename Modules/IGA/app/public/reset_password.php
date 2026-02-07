<?php
// reset-password.php (Completed with password policy + i18n)
date_default_timezone_set('Asia/Bangkok');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php'; // include config + autoload + $conn

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!isset($conn) || !$conn instanceof mysqli) {
    set_alert('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาติดต่อผู้ดูแลระบบ', 'danger');

    if (ob_get_level()) {
        ob_end_clean();
    }
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/custom.css">
    <style>.error-card{max-width:500px;}</style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="container error-card">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Error</h2>'
        . get_alert() .
        '<p class="text-center mt-3"><a href="login.php" class="btn btn-primary-custom">กลับไปหน้าล็อกอิน</a></p>
            </div>
        </div>
    </div>
</body>
</html>';
    exit();
}

$conn->query("SET time_zone = '+07:00'");

// --- Debug (development only) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
if (!is_dir(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0775, true);
}
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

// --- Base URLs for assets & links ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = "{$protocol}://{$host}";
$assets_base_url = "{$base_url}";

$page_title = get_text('reset_password') . " - " . (get_text('app_name') ?: 'INTEQC GLOBAL ASSESSMENT');

// --- Language switcher on this page ---
if (isset($_GET['lang'])) {
    $new_lang = htmlspecialchars(trim($_GET['lang']));
    set_language($new_lang);

    $current_url_params = $_GET;
    unset($current_url_params['lang']);
    $redirect_url = $_SERVER['PHP_SELF'];
    if (!empty($current_url_params)) {
        $query_string = http_build_query($current_url_params);
        if (!empty($query_string)) {
            $redirect_url .= '?' . $query_string;
        }
    }
    header("Location: " . $redirect_url);
    exit();
}

// --- Validate token for form display ---
$token = $_GET['token'] ?? '';
$show_form = false;
if ($token) {
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (strtotime($row['expires_at']) > time()) {
            $show_form = true;
            $user_id = $row['user_id'];
        } else {
            set_alert(get_text('alert_token_expired'), "danger");
        }
    } else {
        set_alert(get_text('alert_token_invalid'), "danger");
    }
    $stmt->close();
}

// --- Handle submit: change password with policy ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password'], $_POST['token'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'] ?? '';


    // กฎรหัสผ่าน: อย่างน้อย 8 ตัวอักษร (ไม่บังคับรูปแบบอื่น)
    $password_ok = (bool)preg_match('/^.{8,}$/', $new_password);

    if (!$password_ok) {
        $policy_msg = get_text('alert_password_policy_fail')
            ?: 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
        set_alert($policy_msg, "danger");
        // ถ้า token เดิมยังใช้ได้ ให้แสดงฟอร์มต่อ
        $show_form = true;
    } else {
        // ตรวจ token -> user_id
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $user_id = $row['user_id']; // user_id เป็น string ในระบบคุณ
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            // อัปเดตรหัสผ่าน (user_id เป็น string => 'ss')
            $stmt2 = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt2->bind_param("ss", $password_hash, $user_id);
            $stmt2->execute();
            $stmt2->close();

            // ลบ token
            $stmt_delete = $conn->prepare("UPDATE password_resets SET is_used = 1 WHERE token = ?");
            $stmt_delete->bind_param("s", $token);
            $stmt_delete->execute();
            $stmt_delete->close();

            set_alert(get_text('alert_password_reset_success'), "success");
            header("refresh:3;url=login.php");
            exit();
        } else {
            set_alert(get_text('alert_token_invalid'), "danger");
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_text('reset_password'); ?> - INTEQC GLOBAL ASSESSMENT</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/custom.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .bg-primary-custom {
            background-color: #A21D21 !important;
        }

        .text-primary-custom {
            color: #A21D21 !important;
        }

        .btn-primary-custom {
            background-color: #A21D21;
            border-color: #A21D21;
            color: #fff;
        }

        .btn-primary-custom:hover {
            background-color: #8a191c;
            border-color: #8a191c;
            color: #fff;
        }

        .card {
            border: none;
            border-radius: 0.75rem;
        }

        .shadow-lg {
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, .175) !important;
        }

        .language-switcher-top-right {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .language-switcher-top-right .form-select-sm {
            min-width: 120px;
        }

        .card .card-body {
            padding-top: 20px;
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="language-switcher-top-right">
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="d-flex align-items-center">
            <?php
            foreach ($_GET as $key => $value) {
                if ($key !== 'lang') {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }
            }
            ?>
            <select name="lang" id="lang_select_top" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="th" <?php echo (($_SESSION['lang'] ?? '') == 'th') ? 'selected' : ''; ?>><?php echo get_text('lang_thai'); ?></option>
                <option value="en" <?php echo (($_SESSION['lang'] ?? '') == 'en') ? 'selected' : ''; ?>><?php echo get_text('lang_english'); ?></option>
                <option value="my" <?php echo (($_SESSION['lang'] ?? '') == 'my') ? 'selected' : ''; ?>><?php echo get_text('lang_burmese'); ?></option>
            </select>
        </form>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg mt-5">
                    <div class="card-header bg-primary-custom text-white text-center py-3">
                        <h2 class="card-title h4 mb-0"><?php echo get_text('reset_password'); ?></h2>
                    </div>
                    <div class="card-body p-5">
                        <?php echo get_alert(); ?>

                        <?php if ($show_form): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" novalidate>
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                                <div class="mb-3">
                                    <label for="new_password" class="form-label"><?php echo get_text('new_password'); ?></label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="new_password"
                                        name="new_password"
                                        required
                                        pattern=".{8,}"
                                        title="<?php
                                                echo addslashes(
                                                    get_text('password_policy_hint') ?: 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'
                                                );
                                                ?>">
                                    <small class="text-muted d-block mt-1">
                                        <?php
                                        echo get_text('password_policy_hint') ?: 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
                                        ?>
                                    </small>

                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary-custom btn-lg">
                                        <?php echo get_text('reset_password'); ?>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p class="text-center text-muted"><?php echo get_text('alert_invalid_or_expired_token_instruction'); ?></p>
                        <?php endif; ?>

                        <hr class="my-4">

                        <div class="text-center">
                            <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="history.back()">
                                &larr; <?php echo get_text('go_back'); ?>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
if (ob_get_level()) {
    ob_end_flush();
}
