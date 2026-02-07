<?php
// verify-result.php
date_default_timezone_set('Asia/Bangkok');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ถ้ามีระบบ i18n / header รวมอยู่แล้ว จะใช้แบบนี้ก็ได้
// require_once __DIR__ . '/../includes/functions.php';
// require_once __DIR__ . '/../includes/header.php';

// รับสถานะจาก query string
$status = $_GET['status'] ?? 'unknown';

// ตั้งค่าข้อความตามสถานะ
$title   = 'สถานะการยืนยันอีเมล';
$message = 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ กรุณาลองใหม่อีกครั้ง';
$badge   = 'secondary';
$showLoginButton    = false;
$showRegisterButton = false;

switch ($status) {
    case 'success':
        $title   = 'ยืนยันอีเมลสำเร็จ';
        $message = 'บัญชีของคุณพร้อมใช้งานแล้ว คุณสามารถเข้าสู่ระบบด้วยชื่อผู้ใช้และรหัสผ่านที่ลงทะเบียนไว้';
        $badge   = 'success';
        $showLoginButton = true;
        break;

    case 'already_active':
        $title   = 'ยืนยันอีเมลไว้แล้ว';
        $message = 'อีเมลนี้เคยถูกยืนยันไปแล้ว คุณสามารถเข้าสู่ระบบได้ทันที หากลืมรหัสผ่านสามารถใช้เมนู “ลืมรหัสผ่าน” ได้';
        $badge   = 'info';
        $showLoginButton = true;
        break;

    case 'already_used':
        $title   = 'ลิงก์นี้ถูกใช้ไปแล้ว';
        $message = 'ลิงก์ยืนยันนี้ถูกใช้ไปแล้ว หากคุณยังไม่สามารถเข้าสู่ระบบได้ กรุณาลองขออีเมลยืนยันใหม่อีกครั้ง หรือแจ้งผู้ดูแลระบบ';
        $badge   = 'warning';
        $showLoginButton = true;
        break;

    case 'expired':
        $title   = 'ลิงก์ยืนยันหมดอายุ';
        $message = 'ลิงก์ยืนยันอีเมลนี้หมดอายุแล้ว กรุณาลงทะเบียนใหม่ หรือขอให้เจ้าหน้าที่ส่งลิงก์ยืนยันอีกครั้ง';
        $badge   = 'danger';
        $showRegisterButton = true;
        break;

    case 'invalid_token':
    case 'invalid_link':
        $title   = 'ลิงก์ไม่ถูกต้อง';
        $message = 'ไม่พบข้อมูลสำหรับลิงก์นี้ อาจเป็นลิงก์ที่ไม่ถูกต้อง หรือเคยถูกใช้ไปแล้ว กรุณาตรวจสอบอีเมลอีกครั้งหรือสมัครใหม่';
        $badge   = 'danger';
        $showRegisterButton = true;
        break;

    case 'error':
    default:
        $title   = 'ไม่สามารถยืนยันอีเมลได้';
        $message = 'ระบบไม่สามารถดำเนินการได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง หรือแจ้งผู้ดูแลระบบ';
        $badge   = 'danger';
        $showRegisterButton = true;
        break;
}

// base url แบบง่าย (ถ้าพี่มีตัวแปร $base_url อยู่แล้วจะใช้ตัวนั้นแทนก็ได้)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl  = "{$protocol}://{$host}";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- ถ้าระบบพี่มี Bootstrap อยู่แล้ว ตัด link นี้ออกได้ -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .verify-card {
            max-width: 480px;
            width: 100%;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .verify-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .verify-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .verify-message {
            color: #4b5563;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="verify-card">
    <div class="verify-icon">
        <?php if ($badge === 'success'): ?>
            ✅
        <?php elseif ($badge === 'info'): ?>
            ℹ️
        <?php elseif ($badge === 'warning'): ?>
            ⚠️
        <?php else: ?>
            ❌
        <?php endif; ?>
    </div>
    <h1 class="verify-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="verify-message"><?= nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')); ?></p>

    <span class="badge bg-<?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8'); ?> mb-3">
        <?= htmlspecialchars(strtoupper($badge), ENT_QUOTES, 'UTF-8'); ?>
    </span>

    <div class="d-flex justify-content-center gap-2 mt-2">
        <?php if ($showLoginButton): ?>
            <a href="<?= $baseUrl; ?>/login" class="btn btn-primary">
                ไปหน้าเข้าสู่ระบบ
            </a>
        <?php endif; ?>

        <?php if ($showRegisterButton): ?>
            <a href="<?= $baseUrl; ?>/register" class="btn btn-outline-secondary">
                กลับไปหน้าสมัครใช้งาน
            </a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
