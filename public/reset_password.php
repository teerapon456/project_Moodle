<?php
// Reset Password Page
// Accessed via email link with token parameter

require_once __DIR__ . '/../core/Config/SessionConfig.php';
require_once __DIR__ . '/../core/Config/Env.php';

$basePathEnv = rtrim(Env::get('APP_BASE_PATH', ''), '/');
$basePath = $basePathEnv;
if ($basePath === '') {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $basePath = preg_replace('#/public$#', '', $scriptDir);
}
if ($basePath === '') {
    $basePath = '/';
}
$baseRoot = rtrim($basePath, '/');
$assetBase = ($baseRoot ? $baseRoot : '') . '/public/';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน | MyHR Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/brand/inteqc-logo.png">

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="assets/css/tailwind.css">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #1f2937 0%, #374151 50%, #4b5563 100%);
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .input-focus:focus {
            border-color: #A21D21;
            box-shadow: 0 0 0 3px rgba(162, 29, 33, 0.1);
        }
    </style>
</head>

<body class="flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="assets/images/brand/inteqc-logo.png" alt="Logo" class="h-16 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-white">MyHR Portal</h1>
        </div>

        <!-- Card -->
        <div class="glass-card rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary to-primary-light p-6 text-center">
                <i class="ri-lock-password-line text-4xl text-white mb-2"></i>
                <h2 class="text-xl font-semibold text-white">รีเซ็ตรหัสผ่าน</h2>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- Loading State -->
                <div id="loading-state" class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
                    <p class="text-gray-600">กำลังตรวจสอบลิงก์...</p>
                </div>

                <!-- Error State -->
                <div id="error-state" class="hidden text-center py-8">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-error-warning-line text-3xl text-red-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">ลิงก์ไม่ถูกต้อง</h3>
                    <p id="error-message" class="text-gray-600 mb-6">ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว</p>
                    <a href="index.php" class="inline-flex items-center gap-2 text-primary hover:underline">
                        <i class="ri-arrow-left-line"></i> กลับไปหน้าล็อกอิน
                    </a>
                </div>

                <!-- Reset Form -->
                <form id="reset-form" class="hidden space-y-6">
                    <div>
                        <p class="text-gray-600 text-sm mb-4">
                            กรุณาตั้งรหัสผ่านใหม่สำหรับบัญชี: <strong id="user-email" class="text-primary"></strong>
                        </p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่านใหม่</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required minlength="6"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus transition-all duration-200 pr-12"
                                placeholder="อย่างน้อย 6 ตัวอักษร">
                            <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="ri-eye-line" id="password-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">ยืนยันรหัสผ่านใหม่</label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg input-focus transition-all duration-200 pr-12"
                                placeholder="กรอกรหัสผ่านอีกครั้ง">
                            <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="ri-eye-line" id="confirm_password-icon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Password strength indicator -->
                    <div id="password-strength" class="hidden">
                        <div class="flex gap-1 mb-1">
                            <div class="h-1 flex-1 rounded bg-gray-200" id="strength-1"></div>
                            <div class="h-1 flex-1 rounded bg-gray-200" id="strength-2"></div>
                            <div class="h-1 flex-1 rounded bg-gray-200" id="strength-3"></div>
                            <div class="h-1 flex-1 rounded bg-gray-200" id="strength-4"></div>
                        </div>
                        <p class="text-xs text-gray-500" id="strength-text"></p>
                    </div>

                    <button type="submit" id="submit-btn"
                        class="w-full bg-gradient-to-r from-primary to-primary-light text-white py-3 rounded-lg font-medium hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                        <i class="ri-lock-line"></i>
                        <span>เปลี่ยนรหัสผ่าน</span>
                    </button>
                </form>

                <!-- Success State -->
                <div id="success-state" class="hidden text-center py-8">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-check-line text-3xl text-green-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">เปลี่ยนรหัสผ่านสำเร็จ!</h3>
                    <p class="text-gray-600 mb-6">คุณสามารถใช้รหัสผ่านใหม่เข้าสู่ระบบได้แล้ว</p>
                    <a href="index.php"
                        class="inline-flex items-center gap-2 bg-gradient-to-r from-primary to-primary-light text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 transition-opacity">
                        <i class="ri-login-box-line"></i> ไปหน้าล็อกอิน
                    </a>
                </div>
            </div>
        </div>

        <!-- Back link -->
        <div class="text-center mt-6">
            <a href="index.php" class="text-gray-300 hover:text-white transition-colors">
                <i class="ri-arrow-left-line"></i> กลับไปหน้าล็อกอิน
            </a>
        </div>
    </div>

    <script>
        const BASE_PATH = <?php echo json_encode($basePath); ?>.replace(/\/$/, '');
        const API_BASE_URL = BASE_PATH + '/routes.php';

        // Get token from URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');

        // Elements
        const loadingState = document.getElementById('loading-state');
        const errorState = document.getElementById('error-state');
        const resetForm = document.getElementById('reset-form');
        const successState = document.getElementById('success-state');
        const errorMessage = document.getElementById('error-message');
        const userEmail = document.getElementById('user-email');

        // Verify token on page load
        async function verifyToken() {
            if (!token) {
                showError('ไม่พบ Token ในลิงก์');
                return;
            }

            try {
                const response = await fetch(`${API_BASE_URL}/auth?action=verify-reset-token&token=${encodeURIComponent(token)}`);
                const data = await response.json();

                if (data.valid) {
                    loadingState.classList.add('hidden');
                    resetForm.classList.remove('hidden');
                    userEmail.textContent = data.email;
                } else {
                    showError(data.message || 'ลิงก์ไม่ถูกต้อง');
                }
            } catch (err) {
                console.error('Verify error:', err);
                showError('เกิดข้อผิดพลาดในการตรวจสอบลิงก์');
            }
        }

        function showError(message) {
            loadingState.classList.add('hidden');
            errorState.classList.remove('hidden');
            errorMessage.textContent = message;
        }

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '-icon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ri-eye-off-line';
            } else {
                input.type = 'password';
                icon.className = 'ri-eye-line';
            }
        }

        // Password strength checker
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthDiv = document.getElementById('password-strength');

            if (password.length === 0) {
                strengthDiv.classList.add('hidden');
                return;
            }

            strengthDiv.classList.remove('hidden');

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password)) strength++;

            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
            const texts = ['อ่อนมาก', 'อ่อน', 'ปานกลาง', 'แข็งแรง'];

            for (let i = 1; i <= 4; i++) {
                const bar = document.getElementById('strength-' + i);
                bar.className = 'h-1 flex-1 rounded ' + (i <= strength ? colors[strength - 1] : 'bg-gray-200');
            }
            document.getElementById('strength-text').textContent = 'ความแข็งแรง: ' + texts[strength - 1];
        });

        // Form submission
        resetForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submit-btn');

            if (password !== confirmPassword) {
                alert('รหัสผ่านไม่ตรงกัน');
                return;
            }

            if (password.length < 6) {
                alert('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div> กำลังดำเนินการ...';

            try {
                const response = await fetch(`${API_BASE_URL}/auth?action=reset-password`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        token: token,
                        password: password,
                        confirm_password: confirmPassword
                    })
                });

                const data = await response.json();

                if (data.success) {
                    resetForm.classList.add('hidden');
                    successState.classList.remove('hidden');
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="ri-lock-line"></i> <span>เปลี่ยนรหัสผ่าน</span>';
                }
            } catch (err) {
                console.error('Reset error:', err);
                alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ri-lock-line"></i> <span>เปลี่ยนรหัสผ่าน</span>';
            }
        });

        // Initialize
        verifyToken();
    </script>
</body>

</html>