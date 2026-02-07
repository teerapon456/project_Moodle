<?php
session_start();
ini_set('max_execution_time', 120); // ตั้งค่าเวลาสูงสุดเป็น 120 วินาที
set_time_limit(120); // ฟังก์ชันสำรองสำหรับตั้งค่าเวลาสูงสุด
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/../includes/functions.php';


// สมมติว่าไฟล์ logs จะอยู่ในโฟลเดอร์ 'logs' ที่อยู่ระดับเดียวกับ 'public' และ 'includes'
define('LOG_FILE', __DIR__ . '/../../logs/app.log');

ini_set('display_errors', 0); // ไม่แสดง error บนหน้าเว็บจริงเพื่อความปลอดภัย
ini_set('log_errors', 1); // เปิดใช้งานการบันทึก error
ini_set('error_log', LOG_FILE); // กำหนดไฟล์สำหรับบันทึก error

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
  $log_message = sprintf(
    "[%s] PHP Error: [%d] %s in %s on line %d\n",
    date('Y-m-d H:i:s'),
    $errno,
    $errstr,
    $errfile,
    $errline
  );
  error_log($log_message, 3, LOG_FILE);
  // หากเป็น error ร้ายแรง ให้หยุดการทำงาน
  if ($errno === E_USER_ERROR || $errno === E_RECOVERABLE_ERROR || $errno === E_PARSE) {
  }
  return true;
});

// ตั้งค่า exception handler เพื่อบันทึก Uncaught Exceptions
set_exception_handler(function (Throwable $exception) {
  $log_message = sprintf(
    "[%s] Uncaught Exception: %s in %s on line %d\n",
    date('Y-m-d H:i:s'),
    $exception->getMessage(),
    $exception->getFile(),
    $exception->getLine()
  );
  error_log($log_message, 3, LOG_FILE);
});

// **NEW: CSRF Token Generation**
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (isset($conn)) {
  $conn->query("SET time_zone = '+07:00'");
}

// ตรวจสอบว่ามีค่า 'lang' ส่งมาใน URL (จากการเลือก dropdown) หรือไม่
if (isset($_GET['lang'])) {
  $new_lang = htmlspecialchars(trim($_GET['lang']));
  set_language($new_lang);
  // บันทึก log การเปลี่ยนภาษา
  error_log(sprintf("[%s] User changed language to: %s via GET parameter.\n", date('Y-m-d H:i:s'), $new_lang), 3, LOG_FILE);
  header("Location: /login.php");
  exit();
}

// Define base_url for absolute paths (ยังคงจำเป็นสำหรับ CSS/JS)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$project_root_dir = basename(dirname(__DIR__));
$base_url = "{$protocol}://{$host}/{$project_root_dir}";
$assets_base_url = $base_url;

$page_title = get_text('login_page_title') . " - INTEQC GLOBAL ASSESSMENT";
$username = '';


// Check if form data has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // **NEW: CSRF Token Validation**
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    error_log(sprintf(
      "[%s] CSRF attack detected for user '%s' (IP: %s).\n",
      date('Y-m-d H:i:s'),
      $_POST['username'] ?? 'N/A',
      $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    ), 3, LOG_FILE);
    set_alert(get_text('alert_csrf_detected'), "danger");
    header("Location: /login.php");
    exit();
  }

  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $remember_me = isset($_POST['remember_me']);

  if ($username === '' || $password === '') {
    set_alert(get_text('alert_empty_fields'), "danger");
    error_log(sprintf("[%s] Login failed: Empty username or password for attempt with username '%s'.\n", date('Y-m-d H:i:s'), $username), 3, LOG_FILE);
  } else {
    try {
      // ดึงข้อมูลเฉพาะตาม username (ไม่กรองสถานะที่ SQL เพื่อเช็คเป็นเคสๆ)
      $sql = "SELECT 
                u.user_id, u.username, u.password_hash, u.full_name, 
                u.is_active AS user_is_active, u.emplevel_id, u.OrgUnitName,
                r.role_name, r.is_active AS role_is_active
              FROM users u
              LEFT JOIN roles r ON u.role_id = r.role_id
              WHERE u.username = ?
              LIMIT 1";

      error_log(sprintf("[%s] Prepared SQL Query: %s\n", date('Y-m-d H:i:s'), $sql), 3, LOG_FILE);

      $stmt = $conn->prepare($sql);
      if (!$stmt) {
        $error_message = "Failed to prepare statement: " . $conn->error;
        error_log(sprintf("[%s] Login DB Error: %s\n", date('Y-m-d H:i:s'), $error_message), 3, LOG_FILE);
        throw new Exception($error_message);
      }

      $stmt->bind_param("s", $username);
      error_log(sprintf("[%s] Binding parameter 'username': %s\n", date('Y-m-d H:i:s'), $username), 3, LOG_FILE);

      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows !== 1) {
        // เคส: ไม่พบผู้ใช้
        set_alert(get_text('alert_user_not_found'), "danger");
        error_log(sprintf("[%s] Login failed: Username '%s' not found.\n", date('Y-m-d H:i:s'), $username), 3, LOG_FILE);
        $stmt->close();
      } else {
        $user = $result->fetch_assoc();
        $stmt->close();

        // เคส: ผู้ใช้ถูกปิดใช้งาน
        if ((int)$user['user_is_active'] !== 1) {
          set_alert(get_text('alert_account_deactivated'), "warning");
          error_log(sprintf("[%s] Login blocked: User '%s' inactive.\n", date('Y-m-d H:i:s'), $username), 3, LOG_FILE);
        }
        // เคส: บทบาทถูกปิดใช้งาน
        elseif (isset($user['role_is_active']) && (int)$user['role_is_active'] !== 1) {
          set_alert(get_text('alert_role_inactive'), "warning");
          error_log(sprintf("[%s] Login blocked: Role inactive for username '%s' (role: %s).\n", date('Y-m-d H:i:s'), $username, $user['role_name'] ?? 'N/A'), 3, LOG_FILE);
        }
        // เคส: ตรวจรหัสผ่าน
        else {
          if (!verify_password($password, $user['password_hash'])) {
            set_alert(get_text('alert_invalid_password'), "danger");
            error_log(sprintf("[%s] Login failed: Invalid password for username '%s'.\n", date('Y-m-d H:i:s'), $username), 3, LOG_FILE);
          } else {
            // สำเร็จ
            session_regenerate_id(true);
            $_SESSION['user_id']          = $user['user_id'];
            $_SESSION['username']         = $user['username'];
            $_SESSION['role_name']        = $user['role_name'] ?? '';
            $_SESSION['full_name']        = $user['full_name'] ?? '';
            $_SESSION['user_emplevel_id'] = $user['emplevel_id'] ?? null;
            $_SESSION['user_orgunitname'] = $user['OrgUnitName'] ?? null;
            $_SESSION['last_activity']    = time();

            // Remember Me
            if ($remember_me) {
              $token = bin2hex(random_bytes(32));
              $token_hash = hash('sha256', $token);
              $expiry = time() + (30 * 24 * 60 * 60);
              $stmt_token = $conn->prepare("INSERT INTO remember_me_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
              if (!$stmt_token) {
                $error_message = "Failed to prepare remember me token statement: " . $conn->error;
                error_log(sprintf("[%s] Login DB Error (Remember Me): %s\n", date('Y-m-d H:i:s'), $error_message), 3, LOG_FILE);
                throw new Exception($error_message);
              }
              $expiry_str = date('Y-m-d H:i:s', $expiry);
              $stmt_token->bind_param("sss", $user['user_id'], $token_hash, $expiry_str);
              $stmt_token->execute();
              $stmt_token = null;

              setcookie('remember_me', $user['user_id'] . ':' . $token, [
                'expires'  => $expiry,
                'path'     => '/',
                'httponly' => true,
                'secure'   => is_https(),
                'samesite' => 'Lax',
              ]);
              error_log(sprintf("[%s] Remember Me cookie set for user '%s'.\n", date('Y-m-d H:i:s'), $username), 3, LOG_FILE);
            }

            set_alert(get_text('alert_login_success', htmlspecialchars($user['full_name'] ?? '')), "success");
            // รีเจน CSRF token หลังล็อกอิน
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // เส้นทางสำหรับ role
            $redirect_to = '';
            if (in_array(($user['role_name'] ?? ''), ['admin', 'super_user', 'editor', 'Super_user_Recruitment'], true)) {
              $redirect_to = '/admin';
              error_log(sprintf("[%s] User '%s' (Role: %s) redirected to admin dashboard.\n", date('Y-m-d H:i:s'), $username, $user['role_name']), 3, LOG_FILE);
            } else {
              $redirect_to = '/user/guide';
              error_log(sprintf("[%s] User '%s' (Role: %s) redirected to user guide.\n", date('Y-m-d H:i:s'), $username, $user['role_name'] ?? 'N/A'), 3, LOG_FILE);
            }

            header("Location: " . $redirect_to);
            exit();
          }
        }
      }
    } catch (Exception $e) {
      set_alert(get_text('alert_tech_error', $e->getMessage()), "danger");
      error_log(sprintf(
        "[%s] Login Exception: %s (Code: %s, File: %s, Line: %d)\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getCode(),
        $e->getFile(),
        $e->getLine()
      ), 3, LOG_FILE);
    }
  }
}

?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?></title>
  <link rel="icon" type="image/png" href="/images/favicon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/custom.css">
</head>

<body class="login-page">
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="language-switcher">
          <div class="dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-globe me-1"></i>
              <?php
              $current_lang = $_SESSION['lang'] ?? 'th';
              echo $current_lang === 'th' ? get_text('lang_thai') : ($current_lang === 'en' ? get_text('lang_english') : get_text('lang_burmese'));
              ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
              <li>
                <form action="" method="GET" class="px-2">
                  <?php
                  foreach ($_GET as $key => $value) {
                    if ($key !== 'lang') {
                      echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                  }
                  ?>
                  <button type="submit" name="lang" value="th" class="dropdown-item d-flex justify-content-between align-items-center <?php echo $current_lang === 'th' ? 'active' : ''; ?>">
                    <?php echo get_text('lang_thai'); ?>
                    <?php if ($current_lang === 'th'): ?>
                      <i class="fas fa-check text-primary"></i>
                    <?php endif; ?>
                  </button>
                </form>
              </li>
              <li>
                <form action="" method="GET" class="px-2">
                  <?php
                  foreach ($_GET as $key => $value) {
                    if ($key !== 'lang') {
                      echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                  }
                  ?>
                  <button type="submit" name="lang" value="en" class="dropdown-item d-flex justify-content-between align-items-center <?php echo $current_lang === 'en' ? 'active' : ''; ?>">
                    <?php echo get_text('lang_english'); ?>
                    <?php if ($current_lang === 'en'): ?>
                      <i class="fas fa-check text-primary"></i>
                    <?php endif; ?>
                  </button>
                </form>
              </li>
              <li>
                <form action="" method="GET" class="px-2">
                  <?php
                  foreach ($_GET as $key => $value) {
                    if ($key !== 'lang') {
                      echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                  }
                  ?>
                  <button type="submit" name="lang" value="my" class="dropdown-item d-flex justify-content-between align-items-center <?php echo $current_lang === 'my' ? 'active' : ''; ?>">
                    <?php echo get_text('lang_burmese'); ?>
                    <?php if ($current_lang === 'my'): ?>
                      <i class="fas fa-check text-primary"></i>
                    <?php endif; ?>
                  </button>
                </form>
              </li>
            </ul>
          </div>
        </div>
        <div class="login-logo">
          <i class="fas fa-clipboard-check"></i>
        </div>
        <h1 class="login-title"><?php echo get_text('login_page_title'); ?></h1>
        <p class="login-subtitle"><?php echo get_text('login_subtitle'); ?></p>
      </div>

      <div class="login-body">
        <?php echo get_alert(); ?>
        <div class="login-body">
          <form action="/login.php" method="POST" class="login-form needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="mb-4">
              <label for="username" class="form-label"><?php echo get_text('username_label'); ?></label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                <input type="text" class="form-control" id="username" name="username"
                  placeholder="<?php echo get_text('username_placeholder'); ?>"
                  value="<?php echo htmlspecialchars($username); ?>" required>
                <div class="invalid-feedback">
                  <?php echo get_text('username_required'); ?>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label for="password" class="form-label"><?php echo get_text('password_label'); ?></label>
              <div class="password-input-group input-group">
                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" class="form-control" id="password" name="password"
                  placeholder="••••••••" required>
                <span class="password-toggle" data-target="password">
                  <i class="fas fa-eye"></i>
                </span>
                <div class="invalid-feedback">
                  <?php echo get_text('password_required'); ?>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                <label class="form-check-label" for="remember_me">
                  <?php echo get_text('remember_me'); ?>
                </label>
              </div>
              <div class="forgot-links">
                <a href="/forgot-password" class="me-2"><?php echo get_text('forgot_password'); ?></a>
                <a href="/forgot-username"><?php echo get_text('forgot_username'); ?></a>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
              <?php echo get_text('login_button'); ?>
            </button>

            <div class="text-center mt-4">
              <p class="mb-0 text-muted">
                <?php echo get_text('no_account_yet'); ?>
                <a href="/register" class="text-primary fw-bold">
                  <?php echo get_text('create_account_here'); ?>
                </a>
              </p>
            </div>
          </form>
          <hr class="my-4">
          <div class="login-footer-links d-flex flex-column gap-2">
            <a class="btn btn-link p-0"
              href="/view-pdf.php?f=applicants"
              target="_blank" rel="noopener noreferrer">
              <?php echo get_text('guide_for_applicants'); ?>
            </a>

            <a class="btn btn-link p-0"
              href="/view-pdf.php?f=associates"
              target="_blank" rel="noopener noreferrer">
              <?php echo get_text('guide_for_associates'); ?>
            </a>
          </div>
        </div>
      </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/custom.js"></script>
</body>

</html>