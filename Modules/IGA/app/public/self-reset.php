<?php
// /self-reset.php  (สร้างโทเคนแล้วเด้งไปหน้า reset-password ทันที)
date_default_timezone_set('Asia/Bangkok');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/functions.php'; // ต้องมี $conn, set_alert(), get_text()

if (!isset($conn) || !$conn instanceof mysqli) {
    set_alert('DB connection error', 'danger');
    header('Location: /login'); exit;
}
$conn->query("SET time_zone = '+07:00'");

// ต้องล็อกอินเท่านั้น
if (empty($_SESSION['user_id'])) {
    set_alert(get_text('please_login_first') ?: 'Please login first.', 'warning');
    header('Location: /login'); exit;
}

$user_id = $_SESSION['user_id'];

// (ทางเลือก) ป้องกันสแปม: ถ้ามีโทเคนล่าสุดภายใน 1 นาที ให้ใช้ของเดิมหรือบล็อกไว้
// ลบโทเคนเก่าๆ ของ user นี้
$stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->close();

// ออกโทเคนใหม่ (อายุ 10 นาที)
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', time() + 10*60);

$stmt = $conn->prepare(
  "INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())"
);
$stmt->bind_param("sss", $user_id, $token, $expires_at);
$stmt->execute();
$stmt->close();

// Redirect ไปหน้า reset พร้อม token
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$lang   = $_SESSION['lang'] ?? 'th';

$reset_url = "{$scheme}://{$host}/reset-password?token={$token}&lang=" . urlencode($lang);

// (ทางเลือก) ใส่ flash ว่าโทเคนหมดอายุใน 10 นาที
set_alert(get_text('reset_link_ready') ?: 'Reset link is ready. It will expire in 10 minutes.', 'info');

header("Location: {$reset_url}");
exit;
