<?php
// Force Request for Client Hints (Critical for Android 11+ and detailed Model detection)
header("Accept-CH: Sec-CH-UA-Platform-Version, Sec-CH-UA-Model, Sec-CH-UA-Full-Version-List");

// Login page only. If already authenticated (and allowed), go straight to HR services.
require_once __DIR__ . '/../core/Config/SessionConfig.php';
if (function_exists('startOptimizedSession')) {
    startOptimizedSession();
} else {
    // Fallback
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

require_once __DIR__ . '/../core/Auth/AuthService.php';
require_once __DIR__ . '/../core/Security/CsrfHelper.php';

use Core\Auth\AuthService;
use Core\Security\CsrfHelper;

$auth = new AuthService();

// Path Calculation (restored for assets/links)
$basePathEnv = rtrim(Env::get('APP_BASE_PATH', ''), '/');
$basePath = $basePathEnv;
if ($basePath === '') {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $basePath = preg_replace('#/public$#', '', $scriptDir);
}
if ($basePath === '') {
    $basePath = '/';
}
$baseRoot = rtrim($basePath, '/');
$linkBase = ($baseRoot ? $baseRoot . '/' : '/');

$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot && is_dir($docRoot . '/assets')) {
    $assetBase = ($baseRoot ? $baseRoot : '') . '/';
} else {
    $assetBase = ($baseRoot ? $baseRoot : '') . '/public/';
}

// If logged-in and active + has HR portal view, redirect to HR services
$user = $_SESSION['user'] ?? null;
if ($user && !isset($_GET['error'])) {
    $roleId = $user['role_id'] ?? null;
    if ($roleId && $auth->hasModulePermission($roleId, 'HR_PORTAL')) {
        header('Location: ' . $linkBase . 'Modules/HRServices/public/index.php');
        exit;
    }
}

$mandatoryGeolocation = $auth->isGeoMandatory();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyHR Portal | Login</title>
    <meta name="description" content="MyHR Portal - ระบบจัดการทรัพยากรบุคคลออนไลน์ สำหรับพนักงานเข้าสู่ระบบ จองรถ หอพัก และบริการ HR อื่นๆ">
    <meta name="theme-color" content="#A21D21">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">
    <link rel="icon" type="image/png" href="assets/images/brand/inteqc-logo.png">

    <!-- Security: Referrer Policy -->
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <!-- Force Request for Client Hints -->
    <meta http-equiv="Accept-CH" content="Sec-CH-UA-Platform-Version, Sec-CH-UA-Model, Sec-CH-UA-Full-Version-List">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginBtn = document.getElementById('loginBtn');
            const msLoginBtn = document.getElementById('msLoginBtn');
            const locationStatus = document.getElementById('locationStatus');
            const latInput = document.getElementById('latitude');
            const lonInput = document.getElementById('longitude');
            const loginForm = document.getElementById('loginForm');

            const isGeoMandatory = <?= json_encode($mandatoryGeolocation) ?>;

            if (isGeoMandatory) {
                // Initially disable login
                if (loginBtn) {
                    loginBtn.disabled = true;
                    loginBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                if (msLoginBtn) {
                    msLoginBtn.disabled = true;
                    msLoginBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
            } else {
                if (locationStatus) locationStatus.style.display = 'none';
            }

            function showStatus(msg, isError = false) {
                if (locationStatus) {
                    locationStatus.innerHTML = isError ?
                        `<span class="text-red-500"><i class="fas fa-exclamation-circle"></i> ${msg}</span>` :
                        `<span class="text-blue-500"><i class="fas fa-spinner fa-spin"></i> ${msg}</span>`;
                }
            }

            function unlockLogin() {
                if (loginBtn) {
                    loginBtn.disabled = false;
                    loginBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                if (msLoginBtn) {
                    msLoginBtn.disabled = false;
                    msLoginBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                if (locationStatus) {
                    locationStatus.innerHTML = `<span class="text-green-500"><i class="fas fa-check-circle"></i> Location verify success</span>`;
                }
            }

            if (isGeoMandatory) {
                if ("geolocation" in navigator) {
                    showStatus("Verifying location...");
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            latInput.value = position.coords.latitude;
                            lonInput.value = position.coords.longitude;
                            unlockLogin();
                        },
                        function(error) {
                            let msg = "Location access denied.";
                            switch (error.code) {
                                case error.PERMISSION_DENIED:
                                    msg = "User denied the request for Geolocation.";
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    msg = "Location information is unavailable.";
                                    break;
                                case error.TIMEOUT:
                                    msg = "The request to get user location timed out.";
                                    break;
                                case error.UNKNOWN_ERROR:
                                    msg = "An unknown error occurred.";
                                    break;
                            }
                            showStatus(msg + " <br>Please allow location access to login.", true);
                        }, {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    showStatus("Geolocation is not supported by this browser.", true);
                }
            }
        });
    </script>

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="assets/css/tailwind.css">


    <!-- Custom CSS (will be gradually replaced) -->
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        /* Forgot Password Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-box {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* News Detail Modal */
        .news-detail-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            /* Higher than language switcher (9999) */
            padding: 20px;
        }

        .news-detail-modal.show {
            display: flex;
            animation: fadeIn 0.2s ease;
        }

        .news-detail-box {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 24px;
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE/Edge */
        }

        .news-detail-box::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }

        .news-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 16px;
            margin-bottom: 8px;
        }

        .news-detail-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-header i {
            color: #A21D21;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px 8px;
            line-height: 1;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .modal-body {
            padding: 24px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .ri-spin {
            animation: spin 1s linear infinite;
        }

        /* Language Selector in Login Form */
        .language-selector-login {
            position: relative;
            width: 100%;
        }

        .lang-dropdown-toggle-login {
            width: 100%;
            padding: 12px 16px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            color: #374151;
        }

        .lang-dropdown-toggle-login:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .lang-dropdown-toggle-login:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .lang-dropdown-menu-login {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
            margin-top: 4px;
            overflow: hidden;
        }

        .lang-dropdown-menu-login.show {
            display: block;
        }

        .lang-dropdown-menu-login .lang-option {
            width: 100%;
            padding: 12px 16px;
            border: none;
            background: white;
            text-align: left;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-size: 14px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .lang-dropdown-menu-login .lang-option:hover {
            background: #f3f4f6;
        }

        .lang-dropdown-menu-login .lang-option:first-child {
            border-bottom: 1px solid #f3f4f6;
        }

        /* Language Selector Top Right - Premium Glassmorphism */
        .login-lang-switcher {
            position: fixed;
            top: 24px;
            right: 24px;
            display: flex;
            padding: 4px;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 99px;
            /* Pill shape */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05),
                0 2px 4px -1px rgba(0, 0, 0, 0.03),
                inset 0 0 0 1px rgba(255, 255, 255, 0.5);
            z-index: 9999;
            gap: 2px;
        }

        .lang-btn {
            background: transparent;
            border: none;
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            /* Slate 500 */
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            outline: none;
        }

        .lang-btn:hover {
            color: #1e293b;
            /* Slate 800 */
            background: rgba(255, 255, 255, 0.5);
        }

        .lang-btn.active {
            background: #A21D21;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(162, 29, 33, 0.3),
                0 2px 4px -1px rgba(162, 29, 33, 0.1);
            font-weight: 600;
        }

        /* Subtle glow for active button */
        .lang-btn.active::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 99px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2);
            pointer-events: none;
        }

        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #4b5563;
        }

        /* Manual Button Style */
        .login-manual-btn {
            position: fixed;
            top: 24px;
            right: 180px;
            /* Position to the left of language switcher */
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 99px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05),
                0 2px 4px -1px rgba(0, 0, 0, 0.03),
                inset 0 0 0 1px rgba(255, 255, 255, 0.5);
            color: #A21D21;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            z-index: 9999;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-manual-btn:hover {
            background: rgba(255, 255, 255, 0.85);
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.08);
            color: #7f1d1d;
        }

        .login-manual-btn:active {
            transform: translateY(0);
        }

        /* Responsive adjust */
        @media (max-width: 640px) {
            .login-manual-btn span {
                display: none;
            }

            .login-manual-btn {
                right: 160px;
                padding: 6px 12px;
            }
        }
    </style>
    <script>
        window.APP_BASE_PATH = <?php echo json_encode($basePath); ?>;

        // Generate CSRF token for login
        <?php
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        ?>
        window.CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';
    </script>
    <script src="assets/js/csrf.js"></script>
    <!-- i18n Module -->
    <script src="assets/js/i18n.js"></script>
</head>

<body>
    <!-- Manual Button -->
    <a href="manual.html" target="_blank" class="login-manual-btn">
        <i class="ri-book-read-line"></i>
        <span>คู่มือการใช้งาน</span>
    </a>

    <!-- New Language Switcher (Fixed Position) -->
    <div class="login-lang-switcher">
        <button type="button" class="lang-btn" data-lang="th">TH</button>
        <button type="button" class="lang-btn" data-lang="en">EN</button>
        <button type="button" class="lang-btn" data-lang="mm">MM</button>
    </div>

    <!-- Language Selector Dropdown (Legacy - can be removed fully later if confirmed) -->
    <div id="language-selector" class="lang-dropdown" style="display: none;">
        <button type="button" class="lang-dropdown-toggle" id="lang-toggle">
            <span id="lang-current">TH</span>
            <i class="ri-arrow-down-s-line"></i>
        </button>
        <div class="lang-dropdown-menu" id="lang-menu">
            <button type="button" class="lang-option" data-lang="th" data-label="TH">ภาษาไทย</button>
            <button type="button" class="lang-option" data-lang="en" data-label="EN">English</button>
            <button type="button" class="lang-option" data-lang="mm" data-label="MM">မြန်မာဘာသာ</button>
        </div>
    </div>

    <div class="login-layout">
        <div class="login-visual">
            <div class="login-visual-title">MyHR PORTAL</div>
            <div class="login-visual-inner">
                <div class="news-module">
                    <div class="news-header">
                        <div>
                            <div class="news-title" data-i18n="login.hr_news_title">ข่าว HR ล่าสุด</div>
                        </div>
                    </div>
                    <div id="news-grid" class="news-grid"></div>
                    <div id="news-empty" style="display:none;color:#6b7280;" data-i18n="login.no_news">ยังไม่มีข่าวที่เผยแพร่</div>
                </div>
            </div>
        </div>

        <div class="login-panel" style="position: relative;">
            <div class="brand-mark">
                <img class="brand-logo" src="assets/images/brand/inteqc-logo.png" alt="INTEQC logo">
            </div>
            <form id="loginForm" style="width: 100%; display: flex; flex-direction: column; gap: 14px;">
                <?php \Core\Security\CsrfHelper::insertField(); ?>
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
                <div id="locationStatus" style="text-align: center; font-size: 0.85rem; padding: 4px; min-height: 24px;"></div>
                <button type="button" id="msLoginBtn" class="btn btn-ms" style="width: 100%;" onclick="loginWithMicrosoft()">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft logo">
                    <span data-i18n="login.microsoft">Sign in with Microsoft</span>
                </button>

                <!-- Language Selector Removed -->

                <div class="form-group">
                    <label for="username" data-i18n="login.username_label">USERNAME</label>
                    <input class="form-control" type="text" id="username" required data-i18n-placeholder="login.username_placeholder" placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="password" data-i18n="login.password_label">PASSWORD</label>
                    <div class="password-wrapper">
                        <input class="form-control" type="password" id="password" required data-i18n-placeholder="login.password_placeholder" placeholder="Enter your password">
                        <i class="ri-eye-line password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <div class="remember">
                    <input type="checkbox" id="remember">
                    <label for="remember" style="cursor: pointer;" data-i18n="login.remember">Remember me</label>
                </div>

                <div class="button-row">
                    <button type="submit" id="loginBtn" class="btn btn-primary" data-i18n="login.submit">Login</button>
                    <button type="button" class="btn btn-outline" onclick="window.location.reload()" data-i18n="common.cancel">Cancel</button>
                </div>

                <a href="#" class="forgot" data-i18n="login.forgot" onclick="openForgotPasswordModal(event)">Forgot Password ?</a>

                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f3f4f6; font-size: 0.75rem; color: #9ca3af; text-align: center; line-height: 1.5;">
                    หากมีข้อผิดพลาด หรือไม่สามารถใช้งานได้<br>
                    ติดต่อ: <span style="color: #4b5563; font-weight: 500;">นายปรเมศวร์ บัวศรี</span><br>
                    (เจ้าหน้าที่วิเคราะห์ข้อมูลทรัพยากรมนุษย์)<br>
                    <a href="mailto:porames_bua@inteqc.com" style="color: #A21D21; text-decoration: none;">porames_bua@inteqc.com</a> | 064-417-9612
                </div>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgot-password-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="ri-lock-password-line"></i> <span data-i18n="forgot.title">ลืมรหัสผ่าน</span></h3>
                <button type="button" class="modal-close" onclick="closeForgotPasswordModal()" aria-label="ปิดหน้าต่าง">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color:#6b7280;margin-bottom:16px;" data-i18n="forgot.instruction">กรอกอีเมลที่ลงทะเบียนไว้ เราจะส่งลิงก์สำหรับรีเซ็ตรหัสผ่านให้คุณ</p>
                <form id="forgot-password-form">
                    <div class="form-group">
                        <label for="forgot-email" data-i18n="forgot.email_label">EMAIL</label>
                        <input type="email" id="forgot-email" class="form-control" required data-i18n-placeholder="forgot.email_placeholder" placeholder="กรอกอีเมลของคุณ">
                    </div>
                    <div class="button-row" style="margin-top:20px;">
                        <button type="submit" class="btn btn-primary" id="forgot-submit-btn">
                            <span id="forgot-btn-text" data-i18n="forgot.submit">ส่งลิงก์รีเซ็ต</span>
                            <span id="forgot-btn-loading" style="display:none;"><i class="ri-loader-4-line ri-spin"></i> <span data-i18n="forgot.sending">กำลังส่ง...</span></span>
                        </button>
                        <button type="button" class="btn btn-outline" onclick="closeForgotPasswordModal()" data-i18n="common.cancel">ยกเลิก</button>
                    </div>
                </form>
                <div id="forgot-success" style="display:none;text-align:center;padding:20px 0;">
                    <div style="width:60px;height:60px;background:#10b981;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="ri-check-line" style="font-size:30px;color:white;"></i>
                    </div>
                    <h4 style="color:#10b981;margin-bottom:8px;" data-i18n="forgot.success_title">ส่งอีเมลสำเร็จ!</h4>
                    <p style="color:#6b7280;" data-i18n="forgot.success_message">หากอีเมลนี้มีอยู่ในระบบ เราได้ส่งลิงก์รีเซ็ตรหัสผ่านไปแล้ว กรุณาตรวจสอบกล่องจดหมายของคุณ</p>
                </div>
            </div>
        </div>
    </div>

    <div id="notification"></div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'no_permission'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('คุณไม่มีสิทธิ์เข้าถึงบริการนี้', 'error');
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        </script>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'role_inactive'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('บัญชีหรือ Role นี้ถูกปิดใช้งาน', 'error');
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        </script>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'session_expired'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่', 'error');
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        </script>
    <?php endif; ?>
    <script>
        const BASE_PATH = (window.APP_BASE_PATH || '').replace(/\/$/, '');

        // API base resolution: compute candidate endpoints and try them when calling backend.
        const API_BASE_URL = BASE_PATH + '/routes.php';

        // Helper to try multiple API entrypoints and return first successful response
        async function apiFetch(path, options = {}) {
            const cleanedPath = path.startsWith('/') ? path : '/' + path;
            const candidates = [
                `${BASE_PATH}/routes.php${cleanedPath}`,
                `${BASE_PATH}/public/routes.php${cleanedPath}`,
                `/routes.php${cleanedPath}`,
                `/public/routes.php${cleanedPath}`
            ];

            for (const url of candidates) {
                try {
                    const res = await fetch(url, options);
                    // If response is valid (even 401/403/500), return it. 
                    // Only retry if it's strictly a 404 (endpoint not found).
                    if (res && res.status !== 404) return res;
                    // if 404, continue to next candidate
                } catch (e) {
                    // network error — try next
                }
            }
            // As a last resort, try original API_BASE_URL
            return fetch(`${API_BASE_URL}${cleanedPath}`, options);
        }

        // Helper to resolve asset URLs - handles relative paths stored in database
        function resolveAssetUrl(url) {
            if (!url) return '';
            // If already absolute URL (http/https), return as-is
            if (url.startsWith('http://') || url.startsWith('https://')) return url;
            // If relative path starting with /public, prepend BASE_PATH
            if (url.startsWith('/public')) return BASE_PATH + url;
            // If relative path starting with /, prepend BASE_PATH
            if (url.startsWith('/')) return BASE_PATH + url;
            return url;
        }

        function showNotification(message, type = 'error') {
            // Try to use existing notify if available
            if (window.notify) {
                window.notify(message, type);
                return;
            }
            // Fallback: toast notification
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:14px 24px;border-radius:8px;font-weight:500;z-index:9999;animation:slideIn 0.3s ease;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            toast.style.background = type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#3b82f6';
            toast.style.color = '#fff';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
            }, 4500);
            setTimeout(() => toast.remove(), 5000);
        }

        window.loginWithMicrosoft = function() {
            const remember = document.getElementById('remember').checked ? 1 : 0;
            const lat = document.getElementById('latitude').value;
            const lon = document.getElementById('longitude').value;
            window.location.href = `${API_BASE_URL}/auth/microsoft/login?remember=${remember}&lat=${lat}&lon=${lon}`;
        };

        // Forgot Password Modal Functions
        function openForgotPasswordModal(e) {
            if (e) e.preventDefault();
            document.getElementById('forgot-password-modal').style.display = 'flex';
            document.getElementById('forgot-password-form').style.display = 'block';
            document.getElementById('forgot-success').style.display = 'none';
            document.getElementById('forgot-email').value = '';
            document.getElementById('forgot-email').focus();
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgot-password-modal').style.display = 'none';
        }

        // Handle forgot password form submission
        document.addEventListener('DOMContentLoaded', function() {
            const forgotForm = document.getElementById('forgot-password-form');
            if (forgotForm) {
                forgotForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const email = document.getElementById('forgot-email').value.trim();
                    const btnText = document.getElementById('forgot-btn-text');
                    const btnLoading = document.getElementById('forgot-btn-loading');
                    const submitBtn = document.getElementById('forgot-submit-btn');

                    if (!email) {
                        showNotification(I18n.t('forgot.error_email_required'), 'error');
                        return;
                    }

                    // Show loading state
                    btnText.style.display = 'none';
                    btnLoading.style.display = 'inline';
                    submitBtn.disabled = true;

                    try {
                        const response = await apiFetch('/auth?action=forgot-password', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                email: email
                            })
                        });

                        const data = await response.json();

                        // Show success message (always, to prevent email enumeration)
                        document.getElementById('forgot-password-form').style.display = 'none';
                        document.getElementById('forgot-success').style.display = 'block';

                    } catch (err) {
                        console.error('Forgot password error:', err);
                        showNotification(I18n.t('forgot.error_generic'), 'error');
                    } finally {
                        btnText.style.display = 'inline';
                        btnLoading.style.display = 'none';
                        submitBtn.disabled = false;
                    }
                });
            }

            // Close modal when clicking outside
            document.getElementById('forgot-password-modal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeForgotPasswordModal();
                }
            });
        });

        async function loadLoginNews() {
            const grid = document.getElementById('news-grid');
            const empty = document.getElementById('news-empty');
            if (!grid) return;
            try {
                const res = await apiFetch('/hrnews/published?limit=3', {
                    credentials: 'include'
                });
                if (!res.ok) throw new Error('load failed');
                const data = await res.json();
                window.LOGIN_NEWS_CACHE = data || [];
                if (!data || !data.length) {
                    empty.style.display = 'block';
                    grid.innerHTML = '';
                    return;
                }
                empty.style.display = 'none';
                grid.innerHTML = data.map((item) => {
                    const tag = item.is_pinned ?
                        `<div class="news-tag" data-i18n="login.pinned">${I18n.t('login.pinned')}</div>` :
                        `<div class="news-tag soft" data-i18n="login.news">${I18n.t('login.news')}</div>`;

                    const heroUrl = resolveAssetUrl(item.hero_image);
                    const hero = heroUrl ? `<div style="width:90px;height:90px;background:url('${heroUrl}') center/cover;border-radius:8px;flex-shrink:0;"></div>` : '';
                    const cls = 'news-card';
                    return `
                        <article class="${cls}">
                            ${hero}
                            <div class="news-card-body">
                                ${tag}
                                <h4>${item.title || ''}</h4>
                                <p>${item.summary || ''}</p>
                                <div class="news-meta-row">
                                    <span><i class="ri-time-line"></i> ${item.publish_at ? new Date(item.publish_at).toLocaleString('th-TH') : 'แสดงตลอด'}</span>
                                </div>
                            </div>
                            <div class="news-card-actions">
                                <button type="button" class="btn-icon edit news-readmore" data-id="${item.id}" data-i18n="login.view_details">${I18n.t('login.view_details')}</button>
                            </div>
                        </article>
                    `;
                }).join('');
                grid.querySelectorAll('.news-readmore').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const id = e.currentTarget.getAttribute('data-id');
                        if (window.renderNewsDetail) {
                            window.renderNewsDetail(id);
                        }
                    });
                });
            } catch (_) {
                grid.innerHTML = '<div style="color:#ef4444;">โหลดข่าวไม่สำเร็จ</div>';
            }
        }

        function renderNewsDetail(id) {
            const modal = document.getElementById('news-detail-modal');
            if (!modal || !window.LOGIN_NEWS_CACHE) return;
            const item = window.LOGIN_NEWS_CACHE.find(n => String(n.id) === String(id));
            if (!item) return;

            const publishText = item.publish_at ? new Date(item.publish_at).toLocaleString('th-TH') : I18n.t('news.immediately');

            modal.querySelector('.news-detail-title').textContent = item.title || '';
            modal.querySelector('.news-detail-meta').textContent = publishText;
            modal.querySelector('.news-detail-summary').textContent = item.summary || '';
            modal.querySelector('.news-detail-body').textContent = item.content || item.summary || '';

            const linkEl = modal.querySelector('.news-detail-link');
            if (linkEl) {
                if (item.link_url) {
                    linkEl.style.display = 'inline-flex';
                    linkEl.href = item.link_url;
                } else {
                    linkEl.style.display = 'none';
                }
            }
            const heroEl = modal.querySelector('.news-detail-hero');
            if (heroEl) {
                heroEl.style.display = 'none';
                heroEl.style.backgroundImage = '';
                heroEl.removeAttribute('data-full');
            }
            const bodyImages = (item.attachments || []).filter(att => (att.attachment_type === 'body_image') || (att.mime_type === 'body_image') || ((att.mime_type || '').startsWith('image/')));
            renderGallery(bodyImages);
            const attWrap = modal.querySelector('.news-detail-attachments');
            if (attWrap) {
                const atts = Array.isArray(item.attachments) ? item.attachments : [];
                const isImageMime = (att) => {
                    if (att.attachment_type === 'body_image') return true;
                    if (att.attachment_type === 'file') return false;
                    const mt = att.mime_type || '';
                    return mt === 'body_image' || mt.startsWith('image/');
                };
                const isLink = (att) => (att.attachment_type === 'link') || (att.mime_type || '') === 'link';
                const files = atts.filter(a => !isImageMime(a) && !isLink(a));
                const links = atts.filter(a => isLink(a));

                if (!files.length && !links.length) {
                    attWrap.style.display = 'none';
                    attWrap.innerHTML = '';
                } else {
                    attWrap.style.display = 'flex';
                    const sections = [];
                    if (files.length) {
                        sections.push(`<div class="att-section"><div class="att-title">${I18n.t('news.attachments')}</div><div class="att-list">${files.map(att => `<a class="btn-icon" style="text-decoration:none;" href="${resolveAssetUrl(att.file_url) || '#'}" target="_blank" rel="noopener">📎 ${att.file_name}</a>`).join('')}</div></div>`);
                    }
                    if (links.length) {
                        sections.push(`<div class="att-section"><div class="att-title">${I18n.t('news.links')}</div><div class="att-list">${links.map(att => `<a class="btn-icon" style="text-decoration:none;" href="${att.file_url || '#'}" target="_blank" rel="noopener">🔗 ${att.file_name || att.file_url}</a>`).join('')}</div></div>`);
                    }
                    attWrap.innerHTML = sections.join('');
                }
            }
            // Re-apply translations for static elements in modal that might have been reset
            I18n.apply();
            modal.classList.add('show');
        }

        (function bindNewsDetailModal() {
            const modal = document.createElement('div');
            modal.id = 'news-detail-modal';
            modal.className = 'news-detail-modal'; // CSS class handles z-index now
            modal.innerHTML = `
                <div class="news-detail-box">
                    <div class="news-detail-header">
                        <div>
                            <div class="news-detail-title"></div>
                            <div class="admin-sub news-detail-meta" style="color:#6b7280;font-size:0.9rem;"></div>
                        </div>
                        <button class="btn-icon cancel" id="news-detail-close" data-i18n="news.close">ปิด</button>
                    </div>
                    <div class="news-detail-hero" style="display:none; position:relative; cursor:pointer;" data-i18n-title="news.view_full" title="คลิกเพื่อดูเต็ม">
                        <div class="hero-overlay" data-i18n="news.view_full">ดูภาพเต็ม</div>
                    </div>
                    <div class="news-body-gallery" id="news-body-gallery" style="display:none;">
                        <div class="gallery-track" id="gallery-track"></div>
                        <button class="gallery-nav prev" id="gallery-prev">&#9664;</button>
                        <button class="gallery-nav next" id="gallery-next">&#9654;</button>
                        <div class="gallery-dots" id="gallery-dots"></div>
                        <button class="gallery-expand" id="gallery-expand" title="ขยาย/ย่อ">⤢</button>
                    </div>
                    <div class="news-detail-summary" style="color:#4b5563;"></div>
                    <div class="news-detail-body"></div>
                    <a class="btn-icon news-detail-link" target="_blank" rel="noopener" href="#" style="width:fit-content;display:none;" data-i18n="news.open_link">เปิดลิงก์หลัก</a>
                    <div class="news-detail-attachments" style="display:none;gap:8px;flex-wrap:wrap;"></div>
                </div>
            `;
            document.body.appendChild(modal);
            const close = () => modal.classList.remove('show');
            modal.addEventListener('click', (e) => {
                if (e.target.id === 'news-detail-modal') close();
            });
            modal.querySelector('#news-detail-close').addEventListener('click', close);
            const heroEl = modal.querySelector('.news-detail-hero');
            if (heroEl) {
                heroEl.addEventListener('click', (e) => {
                    const full = e.currentTarget.getAttribute('data-full');
                    if (full) window.open(full, '_blank', 'noopener');
                });
            }
            window.renderNewsDetail = renderNewsDetail;
        })();

        function renderGallery(images) {
            const gallery = document.getElementById('news-body-gallery');
            const track = document.getElementById('gallery-track');
            const dots = document.getElementById('gallery-dots');
            const prev = document.getElementById('gallery-prev');
            const next = document.getElementById('gallery-next');
            const expand = document.getElementById('gallery-expand');
            if (!gallery || !track || !dots || !prev || !next) return;
            if (!images || !images.length) {
                gallery.style.display = 'none';
                track.innerHTML = '';
                dots.innerHTML = '';
                return;
            }
            gallery.style.display = 'block';
            track.innerHTML = images.map((img, idx) => `
                <div class="gallery-slide ${idx === 0 ? 'active' : ''}" style="background-image:url('${resolveAssetUrl(img.file_url)}');"></div>
            `).join('');
            dots.innerHTML = images.map((_, idx) => `<span class="dot ${idx === 0 ? 'active' : ''}" data-idx="${idx}"></span>`).join('');

            let current = 0;
            const slides = track.querySelectorAll('.gallery-slide');
            const dotEls = dots.querySelectorAll('.dot');

            const showSlide = (i) => {
                current = (i + slides.length) % slides.length;
                slides.forEach((s, idx) => s.classList.toggle('active', idx === current));
                dotEls.forEach((d, idx) => d.classList.toggle('active', idx === current));
            };

            prev.onclick = () => showSlide(current - 1);
            next.onclick = () => showSlide(current + 1);
            dotEls.forEach(d => d.addEventListener('click', () => showSlide(parseInt(d.dataset.idx, 10))));

            let timer = setInterval(() => showSlide(current + 1), 4000);
            gallery.addEventListener('mouseenter', () => {
                clearInterval(timer);
            });
            gallery.addEventListener('mouseleave', () => {
                timer = setInterval(() => showSlide(current + 1), 4000);
            });
            if (expand) {
                expand.onclick = () => {
                    gallery.classList.toggle('fullscreen');
                    const isFull = gallery.classList.contains('fullscreen');
                    if (isFull) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                };
            }
        }

        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            // Initialize i18n
            I18n.init('th', BASE_PATH).then(() => {
                // Update language dropdown display
                updateLangDropdown();
            });

            // Language dropdown toggle
            const langToggle = document.getElementById('lang-toggle');
            const langMenu = document.getElementById('lang-menu');

            langToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                langMenu.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', () => {
                langMenu.classList.remove('show');
            });

            // Language option selection
            document.querySelectorAll('.lang-option').forEach(opt => {
                opt.addEventListener('click', () => {
                    const lang = opt.dataset.lang;
                    const label = opt.dataset.label;
                    I18n.setLocale(lang).then(() => {
                        document.getElementById('lang-current').textContent = label;
                        document.getElementById('lang-current-login').textContent = label;
                        langMenu.classList.remove('show');
                        langMenuLogin.classList.remove('show');
                    });
                });
            });

            // Login form language selector
            const langToggleLogin = document.getElementById('lang-toggle-login');
            const langMenuLogin = document.getElementById('lang-menu-login');

            if (langToggleLogin && langMenuLogin) {
                langToggleLogin.addEventListener('click', (e) => {
                    e.stopPropagation();
                    langMenuLogin.classList.toggle('show');
                });

                // Close when clicking outside
                document.addEventListener('click', () => {
                    langMenuLogin.classList.remove('show');
                });

                // Update current language display
                const savedLocale = localStorage.getItem('i18n_locale') || 'th';
                const labels = {
                    th: 'TH',
                    en: 'EN',
                    mm: 'MM'
                };
                // document.getElementById('lang-current-login').textContent = labels[savedLocale] || labels.th;
            }

            // --- NEW LANGUAGE SWITCHER LOGIC ---
            function updateActiveLang(lang) {
                document.querySelectorAll('.lang-btn').forEach(btn => {
                    if (btn.dataset.lang === lang) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }

            // Init active state from local storage or default
            const initialLang = localStorage.getItem('i18n_locale') || 'th';
            updateActiveLang(initialLang);

            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const lang = btn.dataset.lang;
                    updateActiveLang(lang);
                    I18n.setLocale(lang);
                });
            });

            function updateLangDropdown() {
                const current = I18n.getLocale();
                const labels = {
                    th: 'TH',
                    en: 'EN',
                    mm: 'MM'
                };
                document.getElementById('lang-current').textContent = labels[current] || labels.th;
            }

            // ใช้ global variable เพื่อป้องกันการ submit ซ้ำจากหลาย listeners
            window.isLoginSubmitting = false;

            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                // ป้องกันการ submit ซ้ำจากทุก listeners
                if (window.isLoginSubmitting) {
                    console.log('Login already in progress, ignoring...');
                    return;
                }

                window.isLoginSubmitting = true;

                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                const rememberMe = document.getElementById('remember').checked;

                console.log('Attempting login with username:', username);

                try {
                    const response = await apiFetch('/auth/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        credentials: 'include',
                        body: JSON.stringify({
                            username,
                            password,
                            'remember-me': rememberMe,
                            '_csrf_token': document.querySelector('input[name="_csrf_token"]')?.value,
                            latitude: document.getElementById('latitude')?.value || null,
                            longitude: document.getElementById('longitude')?.value || null
                        })
                    });

                    console.log('Login response status:', response.status);
                    const data = await response.json();
                    console.log('Login response data:', data);

                    if (response.ok) {
                        window.location.href = <?php echo json_encode($linkBase . 'Modules/HRServices/public/index.php?login_success=1'); ?>;
                    } else {
                        // Check for specific error code or fallback
                        if (data.code === 'invalid_credentials' || response.status === 401) {
                            showNotification(I18n.t('login.invalid_credentials'));
                        } else {
                            showNotification(data.message || I18n.t('login.error'));
                        }
                    }
                } catch (err) {
                    console.error('Login error:', err);
                    showNotification(I18n.t('login.error'));
                } finally {
                    // รอเวลาสักครู่ก่อนปลดล็อกเพื่อป้องกัน race condition
                    setTimeout(() => {
                        window.isLoginSubmitting = false;
                    }, 1000);
                }
            });
        }

        loadLoginNews();

        // Password Toggle Logic
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('ri-eye-line');
                this.classList.toggle('ri-eye-off-line');
            });
        }
    </script>
    <!-- Under Development Modal -->
    <div id="devModal" class="fixed inset-0 z-[9999] flex items-center justify-center backdrop-blur-sm opacity-0 invisible transition-all duration-300" style="background-color: rgba(0, 0, 0, 0.85);">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4 p-6 transform scale-95 transition-all duration-300">
            <div class="text-center">
                <div class="w-12 h-12 bg-yellow-50 text-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-hammer-line text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">ระบบอยู่ระหว่างการพัฒนา</h3>
                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    เว็บไซต์นี้กำลังอยู่ในช่วงทดสอบ (Beta) <br>
                    อาจพบข้อผิดพลาดหรือการเปลี่ยนแปลงข้อมูลได้ <br>
                    หากพบปัญหาในการใช้งาน ต้องขออภัยมา ณ ที่นี้
                </p>
                <div class="space-y-3">
                    <button onclick="closeDevModal()" class="w-full py-2.5 px-4 bg-gray-900 hover:bg-black text-white rounded-lg font-medium transition-colors">
                        รับทราบ
                    </button>
                    <div class="flex items-center justify-center gap-2">
                        <input type="checkbox" id="dontShowAgain" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <label for="dontShowAgain" class="text-xs text-gray-500 cursor-pointer">ไม่ต้องแสดงในวันนี้</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const lastSeen = localStorage.getItem('devModalSeenDate');
            const today = new Date().toDateString();

            // Show if never seen OR (seen before BUT not today AND checkbox was checked)
            // Logic: 
            // 1. If never seen or closed without checkbox -> Show every reload? User asked for "popup or cookie".
            // Let's make it: Show ONCE per session (browser close) usually, or "Don't show today" if checked.
            // Simplified: Always show unless "Don't show today" was checked previously today.

            if (lastSeen !== today) {
                // Not seen today (or never seen)
                setTimeout(() => {
                    const modal = document.getElementById('devModal');
                    const content = modal.querySelector('div');
                    modal.classList.remove('opacity-0', 'invisible');
                    content.classList.remove('scale-95');
                    content.classList.add('scale-100');
                }, 500);
            }
        });

        function closeDevModal() {
            const modal = document.getElementById('devModal');
            const content = modal.querySelector('div');
            const dontShow = document.getElementById('dontShowAgain').checked;

            if (dontShow) {
                localStorage.setItem('devModalSeenDate', new Date().toDateString());
            }

            modal.classList.add('opacity-0', 'invisible');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
        }
    </script>
</body>

</html>