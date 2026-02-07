<?php

// Check if session has not been started, then start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifies a CSRF token value provided explicitly (e.g., from JSON body or header).
 * @param string|null $token The token value to verify.
 * @return bool True if token is valid, false otherwise.
 */
function verify_csrf_token_value($token)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';

// ควรวาง is_https ให้เรียกใช้ได้ทุกไฟล์ (เช่นใน includes/functions.php)
if (!function_exists('is_https')) {
  function is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') return true;
    return false;
  }
}

// (ทางเลือก) ตั้งค่า domain กลาง ถ้าต้องใช้ข้ามซับโดเมน ให้ใส่ .yourdomain.com
const REMEMBER_COOKIE_NAME = 'remember_me';
const REMEMBER_COOKIE_PATH = '/';
// const REMEMBER_COOKIE_DOMAIN = '.yourdomain.com'; // ถ้าไม่ข้ามซับโดเมน ให้คอมเมนต์ไว้

function auto_login_from_remember_me(mysqli $conn): void {
  if (!empty($_SESSION['user_id'])) return; // มี session แล้ว ข้าม
  if (empty($_COOKIE[REMEMBER_COOKIE_NAME])) return; // ไม่มีคุกกี้ ข้าม

  // รูปแบบคุกกี้: user_id:raw_token
  $parts = explode(':', $_COOKIE[REMEMBER_COOKIE_NAME], 2);
  if (count($parts) !== 2) { clear_remember_me_cookie(); return; }

  [$uid, $rawToken] = $parts;
  if ($uid === '' || $rawToken === '' || !ctype_xdigit($rawToken)) {
    clear_remember_me_cookie(); 
    return;
  }

  $tokenHash = hash('sha256', $rawToken);

  // หา token ใน DB
  $sql = "SELECT 
            t.user_id, t.expires_at, 
            u.username, u.full_name, u.is_active AS user_is_active, u.emplevel_id, u.OrgUnitName,
            r.role_name, r.is_active AS role_is_active
          FROM remember_me_tokens t
          JOIN users u ON u.user_id = t.user_id
          LEFT JOIN roles r ON u.role_id = r.role_id
          WHERE t.user_id = ? AND t.token_hash = ?
          LIMIT 1";
  $stmt = $conn->prepare($sql);
  if (!$stmt) { clear_remember_me_cookie(); return; }

  $stmt->bind_param("ss", $uid, $tokenHash);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  // ไม่พบหรือหมดอายุ → เคลียร์ทิ้ง
  if (!$row || strtotime($row['expires_at']) < time()) {
    clear_remember_me_cookie();
    // (ทางเลือก) ลบ row token ที่หมดอายุออกจาก DB ด้วยก็ได้
    return;
  }

  // กันเคสผู้ใช้/บทบาทถูกปิดใช้งาน
  if (!empty($row['user_is_active']) && (int)$row['user_is_active'] !== 1) {
    clear_remember_me_cookie();
    return;
  }
  if (isset($row['role_is_active']) && (int)$row['role_is_active'] !== 1) {
    clear_remember_me_cookie();
    return;
  }

  // ผ่าน → ออก session ใหม่ + ผูกผู้ใช้
  session_regenerate_id(true);
  $_SESSION['user_id']          = $row['user_id'];
  $_SESSION['username']         = $row['username'] ?? '';
  $_SESSION['role_name']        = $row['role_name'] ?? '';
  $_SESSION['full_name']        = $row['full_name'] ?? '';
  $_SESSION['user_emplevel_id'] = $row['emplevel_id'] ?? null;
  $_SESSION['user_orgunitname'] = $row['OrgUnitName'] ?? null;
  $_SESSION['last_activity']    = time();

  // **Token Rotation (แนะนำ)**
  $newToken  = bin2hex(random_bytes(32));
  $newHash   = hash('sha256', $newToken);
  $expiry    = time() + (30 * 24 * 60 * 60);
  $expiryStr = date('Y-m-d H:i:s', $expiry);

  // เลือกนโยบาย: ลบของเก่า + เพิ่มของใหม่ (single-device) 
  // ถ้าต้องการ multi-device ให้ "ไม่ลบ" อันเก่า
  $conn->begin_transaction();
  try {
    $del = $conn->prepare("DELETE FROM remember_me_tokens WHERE user_id = ? AND token_hash = ?");
    if (!$del) throw new Exception("Prepare delete failed");
    $del->bind_param("ss", $uid, $tokenHash);
    $del->execute();

    $ins = $conn->prepare("INSERT INTO remember_me_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    if (!$ins) throw new Exception("Prepare insert failed");
    $ins->bind_param("sss", $uid, $newHash, $expiryStr);
    $ins->execute();

    $conn->commit();
  } catch (Throwable $e) {
    $conn->rollback();
    // ถ้าโรเตชันล้มเหลว ก็ยังล็อกอินได้ แต่ไม่ออกโทเคนใหม่
    // (ทางเลือก) clear cookie เพื่อบังคับ login ครั้งต่อไป
  }

  // เซ็ตคุกกี้ใหม่ (ใช้ is_https())
  $cookieOpts = [
    'expires'  => $expiry,
    'path'     => REMEMBER_COOKIE_PATH,
    'httponly' => true,
    'secure'   => is_https(),
    'samesite' => 'Lax',
  ];
  // ถ้าต้องใช้ข้ามซับโดเมน ให้เปิดบรรทัดนี้:
  // $cookieOpts['domain'] = REMEMBER_COOKIE_DOMAIN;

  setcookie(REMEMBER_COOKIE_NAME, $uid . ':' . $newToken, $cookieOpts);
}

function clear_remember_me_cookie(): void {
  if (!empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
    $cookieOpts = [
      'expires' => time() - 3600,
      'path'    => REMEMBER_COOKIE_PATH,
      // ถ้าตอนเซ็ตมี domain ต้องเคลียร์ด้วย domain เดียวกัน
      // 'domain'  => REMEMBER_COOKIE_DOMAIN,
    ];
    setcookie(REMEMBER_COOKIE_NAME, '', $cookieOpts);
    unset($_COOKIE[REMEMBER_COOKIE_NAME]);
  }
}


// Check if session has not been started, then start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();                                                                                                  
}
if (!defined('BASE_URL')) {
    $protocol = "http://";
    // Check standard HTTPS variable
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $protocol = "https://";
    }
    // Check for Forwarded-Proto header (common in reverse proxy/load balancer setups)
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = "https://";
    }
    
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . $host);
}

ini_set('display_errors', 0); 
ini_set('log_errors', 1);     
ini_set('error_log', LOG_FILE); 

// กำหนด Error Handler เพื่อบันทึก error/exception ที่ไม่ถูกจัดการ
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // กรอง error ที่ไม่ต้องการบันทึก เช่น @ suppression
    if (!(error_reporting() & $errno)) {
        return false;
    }
    $log_message = sprintf("[%s] PHP Error: [%d] %s in %s on line %d\n", date('Y-m-d H:i:s'), $errno, $errstr, $errfile, $errline);
    error_log($log_message, 3, LOG_FILE);
    return true;
});

set_exception_handler(function (Throwable $exception) {
    $log_message = sprintf("[%s] Uncaught Exception: %s in %s on line %d\n", date('Y-m-d H:i:s'), $exception->getMessage(), $exception->getFile(), $exception->getLine());
    error_log($log_message, 3, LOG_FILE);

});

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Checks if a user is logged in.
 * @return bool True if logged in, False otherwise.
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user has a specific role.
 * @param string $role_name The role name to check (e.g., 'admin', 'user').
 * @return bool True if the user has the role, False otherwise.
 */
function has_role($role_name)
{
    if (!is_logged_in()) {
        return false;
    }
    // Check if the role in session matches the specified role
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === $role_name;
}

/**
 * Redirects to the login page if the user is not logged in.
 */
function require_login()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        error_log(sprintf("[%s] DEBUG: session_start() called inside require_login.\n", date('Y-m-d H:i:s')), 3, LOG_FILE);
    }
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        error_log(sprintf("[%s] DEBUG: User not logged in (user_id missing/empty) in require_login. Redirecting to login.php.\n", date('Y-m-d H:i:s')), 3, LOG_FILE);
        header("Location: /login"); // ปรับพาธให้ตรงกับโครงสร้างของคุณ
        exit();
    }
}

/**
 * Requires admin privileges. Redirects to login if not logged in or shows 403 if not admin.
 * @return void
 */
function require_admin()
{
    require_login();
    
    if (!has_role('admin') && !has_role('super_user')) {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
}

/**
 * Redirects if the user is not logged in or doesn't have the correct role.
 * @param string|null $required_role The required role (e.g., 'admin', 'user'). If null, only checks for login.
 */
function redirect_if_not_logged_in(...$allowed_roles)
{
    // ตรวจสอบว่า session_start() ถูกเรียกแล้ว
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        error_log(sprintf("[%s] DEBUG: session_start() called inside redirect_if_not_logged_in.\n", date('Y-m-d H:i:s')), 3, LOG_FILE);
    }

    // เพิ่ม Log เพื่อตรวจสอบสถานะการเข้าสู่ระบบและบทบาท
    $user_id_in_session = $_SESSION['user_id'] ?? 'N/A';
    $role_name_in_session = $_SESSION['role_name'] ?? 'N/A';

    error_log(sprintf(
        "[%s] DEBUG: Inside redirect_if_not_logged_in. Session User ID: %s, Session Role: %s. Allowed Roles: %s\n",
        date('Y-m-d H:i:s'),
        $user_id_in_session,
        $role_name_in_session,
        implode(', ', $allowed_roles)
    ), 3, LOG_FILE);


    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        error_log(sprintf("[%s] DEBUG: User not logged in (user_id missing/empty). Redirecting to login.php.\n", date('Y-m-d H:i:s')), 3, LOG_FILE);
        header("Location: /login"); // ปรับพาธให้ตรงกับโครงสร้างของคุณ
        exit();
    }

    if (!empty($allowed_roles) && !in_array($role_name_in_session, $allowed_roles)) {
        error_log(sprintf(
            "[%s] DEBUG: User role '%s' not allowed for this page. Redirecting to login.php.\n",
            date('Y-m-d H:i:s'),
            $role_name_in_session
        ), 3, LOG_FILE);
        // คุณอาจต้องการเปลี่ยนเส้นทางไปหน้าอื่นที่ไม่ใช่ login.php ถ้าเป็นแค่เรื่องบทบาทไม่ตรง
        header("Location: /login"); // ปรับพาธให้ตรงกับโครงสร้างของคุณ
        exit();
    }

    // เพิ่ม Log หากผ่านการตรวจสอบทั้งหมด
    error_log(sprintf(
        "[%s] DEBUG: User ID %s (Role: %s) successfully passed redirect_if_not_logged_in check.\n",
        date('Y-m-d H:i:s'),
        $user_id_in_session,
        $role_name_in_session
    ), 3, LOG_FILE);
}

/**
 * Redirects to the appropriate dashboard if the user is already logged in.
 * 💡 ปรับปรุง: ใช้พาธที่ถูกต้องเมื่อถูกเรียกจาก public/login.php
 */
function redirect_if_logged_in()
{
    if (is_logged_in()) {
        if (has_role('admin')) {
            header("Location: ../admin/"); // พาธจาก public/ ไปยัง views/admin/
        } else {
            header("Location: ../user/"); // พาธจาก public/ ไปยัง views/user/
        }
        exit();
    }
}

/**
 * Hashes a password.
 * @param string $password The password to hash.
 * @return string The hashed password.
 */
function hash_password($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifies a password against a hash.
 * @param string $password_input The password entered by the user.
 * @param string $password_hash The hashed password from the database.
 * @return bool True if passwords match, False otherwise.
 */
function verify_password($password_input, $password_hash)
{
    return password_verify($password_input, $password_hash);
}

/**
 * Sets an alert message for display (uses Bootstrap alerts).
 * @param string $message The message to display.
 * @param string $type The alert type (e.g., 'info', 'success', 'warning', 'danger').
 */
function set_alert($message, $type = 'info')
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

/**
 * Retrieves and displays the alert message.
 * @return string HTML for the alert, or an empty string if no alert.
 */
function get_alert()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $type = $alert['type'];
        $message = htmlspecialchars($alert['message']);
        
        // Define custom alert styles based on type
        $styles = [
            'success' => 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;',
            'danger' => 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;',
            'warning' => 'background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba;',
            'info' => 'background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;',
            'primary' => 'background-color: #cce5ff; color: #004085; border: 1px solid #b8daff;',
            'secondary' => 'background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db;',
        ];
        
        // Default style if type not found
        $style = $styles[$type] ?? 'background-color: #f8f9fa; color: #383d41; border: 1px solid #e2e3e5;';
        
        // Clear session alert after retrieval
        unset($_SESSION['alert']);
        
        return sprintf('
            <div class="alert alert-dismissible fade show mb-4" role="alert" style="%s">
                <div class="d-flex align-items-center">
                    <i class="%s me-2"></i>
                    <div class="flex-grow-1">%s</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>',
            $style,
            $type === 'success' ? 'fas fa-check-circle me-2' : 
                ($type === 'danger' ? 'fas fa-times-circle me-2' : 
                ($type === 'warning' ? 'fas fa-exclamation-triangle me-2' : 'fas fa-info-circle me-2')),
            $message
        );
    }
    return '';
}

// --- START: CSRF Protection Functions ---
/**
 * Generates a CSRF token and stores it in the session.
 * Outputs a hidden input field with the token.
 */
function generate_csrf_token()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32)); // Generates a 64-character hex string
    }
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($_SESSION['_csrf_token']) . '">';
}

/**
 * Verifies the CSRF token submitted via POST request.
 * @return bool True if token is valid, false otherwise.
 */
function verify_csrf_token()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    // Check if token exists in session and POST data
    if (!isset($_POST['_csrf_token']) || !isset($_SESSION['_csrf_token'])) {
        return false;
    }
    // Compare tokens
    if (hash_equals($_SESSION['_csrf_token'], $_POST['_csrf_token'])) {
        // Token is valid, keep it for session-based usage
        return true;
    }
    return false;
}
// --- CSRF Token Helpers ---

/**
 * Generates a CSRF token and stores it in the session if one doesn't exist
 * @return string The generated CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Gets the CSRF token name (key) used in forms
 * @return string The CSRF token name
 */
function csrf_token_name() {
    return '_csrf_token';
}

/**
 * Generates a hidden input field with the CSRF token
 * @return string HTML input field with the CSRF token
 */
function csrf_token_input() {
    return '<input type="hidden" name="' . csrf_token_name() . '" value="' . csrf_token() . '">';
}

/**
 * Verifies if the submitted CSRF token is valid
 * @param string $token The token to verify (optional, will check POST data if not provided)
 * @return bool True if token is valid, false otherwise
 */
function verify_csrf($token = null) {
    if ($token === null) {
        $token = $_POST[csrf_token_name()] ?? '';
    }
    
    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['_csrf_token'], $token);
}

// --- END: CSRF Protection Functions ---

/**
 * Formats a datetime string to Thai format.
 * @param string|null $datetime_str Datetime string (e.g., 'YYYY-MM-DD HH:MM:SS').
 * @param bool $include_time True to include time, false for date only.
 * @return string Formatted Thai datetime, or empty string if no data.
 */
function thai_datetime_format($datetime_str, $include_time = true)
{
    if (empty($datetime_str)) {
        return '';
    }
    $timestamp = strtotime($datetime_str);
    if ($timestamp === false) {
        return htmlspecialchars($datetime_str); // Return original if invalid date string
    }
    // Thai month names
    $thai_months = array(
        '01' => 'ม.ค.',
        '02' => 'ก.พ.',
        '03' => 'มี.ค.',
        '04' => 'เม.ย.',
        '05' => 'พ.ค.',
        '06' => 'มิ.ย.',
        '07' => 'ก.ค.',
        '08' => 'ส.ค.',
        '09' => 'ก.ย.',
        '10' => 'ต.ค.',
        '11' => 'พ.ย.',
        '12' => 'ธ.ค.'
    );

    $day = date('d', $timestamp);
    $month_num = date('m', $timestamp);
    $year = date('Y', $timestamp) + 543; // Buddhist Era

    $formatted_date = $day . ' ' . $thai_months[$month_num] . ' ' . $year;

    if ($include_time) {
        $time = date('H:i', $timestamp);
        return $formatted_date . ' ' . $time . ' น.';
    }
    return $formatted_date;
}

/**
 * Formats time spent (in seconds) into a human-readable string.
 * @param int|null $seconds Number of seconds spent.
 * @return string Formatted time (e.g., "1 ชม. 30 นาที 15 วินาที").
 */
function formatTimeSpent($seconds)
{
    if ($seconds === null) {
        return get_text('time_not_completed');
    }
    $seconds = (int) $seconds;
    if ($seconds < 0) {
        return "N/A";
    }

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $remaining_seconds = $seconds % 60;

    $time_parts = [];
    if ($hours > 0) {
        $time_parts[] = $hours . " " . get_text('hours_abbr');
    }
    if ($minutes > 0) {
        $time_parts[] = $minutes . " " . get_text('minutes_abbr');
    }
    // Display seconds if no hours and minutes, or if remaining seconds exist
    if ($remaining_seconds > 0 || empty($time_parts)) {
        $time_parts[] = $remaining_seconds . " " . get_text('seconds_abbr');
    }

    if (empty($time_parts)) {
        return "0 " . get_text('seconds_abbr');
    }
    return implode(" ", $time_parts);
}

/**
 * Helper function to get role_id from role_name.
 * @param string $roleName The role name to find the ID for.
 * @param array $rolesArray Array of all roles from the database.
 * @return int|null The role_id if found, or null if not found.
 */
function get_role_id_by_name($roleName, $rolesArray)
{
    foreach ($rolesArray as $role) {
        if ($role['role_name'] === $roleName) {
            return (int)$role['role_id'];
        }
    }
    return null;
}

// Function for language management
function load_language_file($lang_code)
{
    // Corrected path: from 'includes/', go up one level ('../') to '/', then into 'languages/'
    $lang_file = __DIR__ . '/../languages/' . $lang_code . '.php';

    if (file_exists($lang_file)) {
        return require $lang_file;
    }
    // If specified language file not found, fallback to default English
    error_log("Language file not found for: " . $lang_code . ". Loading default 'en.php'.");
    // Corrected path for default English too
    return require __DIR__ . '/../languages/en.php';
}

function get_user_language()
{
    // 1. Check in Session first (if manually set or previously selected)
    // แก้ไข: เพิ่ม 'lo' เข้าไปใน Array ภาษาที่รองรับ
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['th', 'en', 'my', 'lo'])) {
        return $_SESSION['lang'];
    }

    // 2. Check from HTTP Accept-Language Header of the browser
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $accepted_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($accepted_languages as $lang) {
            $lang_code = strtolower(substr($lang, 0, 2)); // Get first 2 chars (en, th, my, lo)
            if ($lang_code === 'th') return 'th';
            if ($lang_code === 'en') return 'en';
            if ($lang_code === 'my') return 'my'; // Check for Burmese
        }
    }

    // 3. Default value if no match found
    return 'en'; // Set English as default
}

// Load language texts when the application starts
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = get_user_language();
}
// This line calls load_language_file
$GLOBALS['lang_data'] = load_language_file($_SESSION['lang']);

/**
 * Retrieves translated text based on a key and optional arguments for sprintf.
 * @param string $key The key for the translated text.
 * @param mixed ...$args Optional arguments to be passed to sprintf for placeholder replacement.
 * @return string The translated text, or an error indicator if not found or invalid type.
 */
function get_text($key, ...$args)
{
    global $lang_data; // Assuming $lang_data holds your loaded language array

    // Retrieve the text for the given key, falling back to the key itself if not found
    $text = $lang_data[$key] ?? $key;

    // If additional arguments are provided, treat the retrieved text as a sprintf format string
    if (!empty($args)) {
        // Process each argument to ensure it's a string
        $processed_args = [];
        foreach ($args as $arg) {
            if (is_array($arg) || is_object($arg)) {
                $processed_args[] = json_encode($arg);
            } else {
                $processed_args[] = (string)$arg;
            }
        }
        
        try {
            return vsprintf($text, $processed_args);
        } catch (Throwable $e) {
            error_log("Error in get_text for key '$key': " . $e->getMessage() . " | Args: " . json_encode($args));
            // Return the text with placeholders if formatting fails
            return $text . ' ' . implode(' ', array_filter($processed_args, 'strlen'));
        }
    }

    // If no additional arguments, return the text as is
    return $text;
}

/**
 * Function to change the current language.
 * @param string $lang_code The language code to set (e.g., 'th', 'en', 'my', 'lo').
 * @return bool True if language was set successfully, false otherwise.
 */
function set_language($lang_code)
{
    // แก้ไข: เพิ่ม 'lo' เข้าไปใน Array ของภาษาที่รองรับ
    if (in_array($lang_code, ['th', 'en', 'my'])) {
        $_SESSION['lang'] = $lang_code;
        // Load the new language immediately
        $GLOBALS['lang_data'] = load_language_file($lang_code);
        return true;
    }
    return false;
}

if (!function_exists('redirect_to')) {
    /**
     * เปลี่ยนเส้นทางไปยัง URL ที่กำหนดและหยุดการทำงานของสคริปต์
     *
     * @param string $location URL ที่ต้องการเปลี่ยนเส้นทางไป
     */
    function redirect_to($location)
    {
        header('Location: ' . $location);
        exit(); // สำคัญมาก: ต้องเรียก exit() เพื่อให้แน่ใจว่าไม่มีโค้ดอื่นทำงานต่อ
    }
}


/**
 * Checks if the current user is an admin
 * @return bool True if user is admin, false otherwise
 */
function is_admin() {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    return $_SESSION['user_role'] === 'admin';
}

// --- START: Remember Me Functions (ใหม่) ---

/**
 * ฟังก์ชันสำหรับตรวจสอบและจัดการ Remember Me cookie
 * ควรเรียกใช้ในทุกหน้าที่ต้องการให้ Remember Me ทำงาน (เช่น header.php หรือ functions.php ที่ถูก include โดย header)
 * @param mysqli $conn การเชื่อมต่อฐานข้อมูล
 */
function handle_remember_me($conn)
{
    // ถ้าผู้ใช้ยังไม่ได้ล็อกอินและมีคุกกี้ remember_me อยู่
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
        list($user_id, $token) = explode(':', $_COOKIE['remember_me'] ?? '');

        if (!empty($user_id) && !empty($token)) {
            $user_id = (int)$user_id;
            $token_hash = hash('sha256', $token);

            // ตรวจสอบ token_hash ในฐานข้อมูล
            // 💡 เพิ่ม u.is_active ใน WHERE clause เพื่อไม่ให้ล็อกอินถ้าบัญชีถูกปิดใช้งาน
            $stmt = $conn->prepare("SELECT u.user_id, u.username, r.role_name, u.full_name
                                   FROM remember_me_tokens rmt
                                   JOIN users u ON rmt.user_id = u.user_id
                                   JOIN roles r ON u.role_id = r.role_id
                                   WHERE rmt.user_id = ? AND rmt.token_hash = ? AND rmt.expires_at > NOW() AND u.is_active = 1");
            $stmt->bind_param("is", $user_id, $token_hash);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // ถ้า token ถูกต้องและยังไม่หมดอายุ, ตั้งค่า Session
                session_regenerate_id(true); // สร้าง Session ID ใหม่เพื่อความปลอดภัย
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['last_activity'] = time(); // สำหรับ session timeout
                $_SESSION['has_seen_guide'] = false; // <--- **บรรทัดที่เพิ่ม**


                // ลบ token เก่าทิ้งแล้วสร้างใหม่ เพื่อป้องกัน Replay Attack
                $delete_stmt = $conn->prepare("DELETE FROM remember_me_tokens WHERE user_id = ? AND token_hash = ?");
                $delete_stmt->bind_param("is", $user_id, $token_hash);
                $delete_stmt->execute();
                $delete_stmt->close();

                $new_token = bin2hex(random_bytes(32));
                $new_token_hash = hash('sha256', $new_token);
                $expiry = time() + (30 * 24 * 60 * 60); // 30 วัน

                $insert_stmt = $conn->prepare("INSERT INTO remember_me_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iss", $user['user_id'], $new_token_hash, date('Y-m-d H:i:s', $expiry));
                $insert_stmt->execute();
                $insert_stmt->close();

                // ตั้งค่าคุกกี้ remember_me ที่เบราว์เซอร์ของผู้ใช้
                setcookie('remember_me', $user['user_id'] . ':' . $new_token, [
                    'expires' => $expiry,
                    'path' => '/',
                    'httponly' => true,
                    'secure' => isset($_SERVER['HTTPS']), // ตั้งค่าเป็น true ถ้าใช้ HTTPS
                    'samesite' => 'Lax'
                ]);
            } else {
                // ถ้า token ไม่ถูกต้อง, ไม่พบใน DB, หมดอายุ หรือบัญชีถูกปิดใช้งาน ให้ลบคุกกี้ออก
                setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/']);
            }
            $stmt->close(); // ปิด statement ของการตรวจสอบ token
        }
    }
}

/**
 * ฟังก์ชันสำหรับ Logout เพื่อล้าง Session และลบคุกกี้ Remember Me
 */
function logout()
{
    session_unset();
    session_destroy();
    // ลบ Remember Me cookie ด้วย
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/']);
    }
    header("Location: /login");
    exit();
}

/**
 * Generates HTML for test result alerts
 * @param bool $has_unchecked_short_answer Whether there are unchecked short answer questions
 * @param string $pass_fail_status The pass/fail status of the test
 * @param array $test_info Test information array containing min_passing_score
 * @param float $user_percentage_score The user's percentage score
 * @return string HTML for the alert
 */
function get_test_result_alert($has_unchecked_short_answer, $pass_fail_status, $test_info, $user_percentage_score) {
    $message = '';
    $alert_class = '';
    $icon = '';
    $reasons = [];
    
    // Check for pending review
    if ($has_unchecked_short_answer && !$test_info['show_result_immediately']) {
        $message = get_text('alert_results_pending_review');
        $alert_class = 'info';
        $icon = 'info-circle';
    } else {
        // Check all failure conditions
        if ($pass_fail_status === 'failed_critical' || $pass_fail_status === 'failed') {
            if ($pass_fail_status === 'failed_critical') {
                $reasons[] = get_text('reason_failed_critical_questions');
            }
            
            // Check for minimum passing score failure
            if (isset($test_info['min_passing_score']) && $test_info['min_passing_score'] > 0) {
                if ($user_percentage_score < $test_info['min_passing_score']) {
                    $reasons[] = sprintf(
                        get_text('reason_failed_passing_score'),
                        number_format($test_info['min_passing_score'], 2),
                        number_format($user_percentage_score, 2)
                    );
                }
            }
        } 
        // If passed, show the passing requirement
        else if ($pass_fail_status === 'passed' && isset($test_info['min_passing_score']) && $test_info['min_passing_score'] > 0) {
            $reasons[] = sprintf(
                get_text('test_passing_score_requirement'),
                number_format($test_info['min_passing_score'], 2)
            );
        }
        
        if (!empty($reasons)) {
            $message .= implode(' และ ', $reasons);
            $alert_class = 'danger';
            $icon = 'times-circle';
        } else {
            $alert_class = 'success';
            $icon = 'check-circle';
        }
    }
    
    if (!empty($message)) {
        return sprintf(
            '<div class="alert alert-%s text-center" role="alert"><i class="fas %s me-2"></i>%s</div>',
            htmlspecialchars($alert_class, ENT_QUOTES),
            htmlspecialchars($icon, ENT_QUOTES),
            $message
        );
    }
    
    return '';
}

function send_verification_email($to_email, $to_name, $verification_link)
{
    $mail = new PHPMailer(true);

    try {
        
        // --- การตั้งค่าเซิร์ฟเวอร์ SMTP ที่สำคัญ ต้องอยู่ในบล็อกนี้! ---
        $mail->isSMTP();                                           // ตั้งค่าให้ใช้ SMTP
        $mail->Host       = SMTP_HOST;                             // กำหนด SMTP server หลักของคุณ (จาก config.php)
        $mail->SMTPAuth   = false;                                  // เปิดใช้งาน SMTP authentication
        $mail->SMTPDebug  = 0;                                     // 0=off, 2=client/server messages
        $mail->Port       = SMTP_PORT;                             // TCP port ที่จะเชื่อมต่อ (จาก config.php)


        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        $site_name_for_email = defined('SMTP_FROM_NAME') ? htmlspecialchars(SMTP_FROM_NAME) : 'INTEQC GLOBAL ASSESSMENT';

        $mail->Subject = get_text('email_verify_subject', $site_name_for_email);

        $html_body = get_text(
            'email_verify_body',
            htmlspecialchars($to_name),
            htmlspecialchars($verification_link),
            htmlspecialchars($verification_link)
        );
        $mail->Body = $html_body;


        $mail->send();
        error_log("Email verification sent to: {$to_email}");
        return true;
    } catch (Exception $e) {
        error_log("Email verification to {$to_email} failed. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}