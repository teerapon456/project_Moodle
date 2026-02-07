<?php
// เริ่มต้น Session
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db_connect.php';

// ถ้ามี remember_me → ลบจาก DB และเคลียร์คุกกี้
if (!empty($_COOKIE['remember_me'])) {
  [$uid, $raw] = explode(':', $_COOKIE['remember_me'], 2);
  if (!empty($uid) && !empty($raw)) {
    $hash = hash('sha256', $raw);
    $stmt = $conn->prepare("DELETE FROM remember_me_tokens WHERE user_id = ? AND token_hash = ?");
    if ($stmt) {
      $stmt->bind_param("ss", $uid, $hash);
      $stmt->execute();
    }
  }
  setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/']);
  unset($_COOKIE['remember_me']);
}

// เคลียร์ session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"] ?? '', $params["secure"] ?? false, $params["httponly"] ?? true);
}
// ล้างตัวแปร Session ทั้งหมด
$_SESSION = array();

// ทำลาย Session
session_destroy();

header("Location: /login.php");
exit;



// Redirect ไปยังหน้า Login
header("Location: /login");
exit();
?>