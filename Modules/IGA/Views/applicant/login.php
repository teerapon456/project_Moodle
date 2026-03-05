<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IGA - MyHR Portal</title>
    <link rel="icon" type="image/png" href="<?= $assetBase ?>assets/images/brand/inteqc-logo.png">
    <!-- Google Fonts - Kanit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Kanit', sans-serif;
        }

        .login-card {
            max-width: 480px;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .login-header {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            padding: 3rem 2rem;
            text-align: center;
            color: #fff;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: #fff;
            border-radius: 100% 100% 0 0;
        }

        .login-body {
            padding: 2.5rem 2.5rem 3rem;
            background: #fff;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #64748b;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .tab-btn.active {
            color: #b91c1c;
            border-bottom-color: #b91c1c;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.1);
        }

        .btn-login {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            border: none;
            border-radius: 12px;
            padding: 0.875rem;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
            transition: all 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 20px -5px rgba(185, 28, 28, 0.4);
            filter: brightness(1.1);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: #94a3b8;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            padding: 0 1rem;
        }

        .sso-link {
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
        }

        .sso-link:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #1e293b;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <div class="mb-3 inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-3xl backdrop-blur-md">
                <i class="ri-graduation-cap-fill text-4xl text-white"></i>
            </div>
            <h3 class="text-2xl font-black mb-1">IGA Assessment</h3>
            <p class="text-white/70 text-sm font-medium">Integrity Global Assessment System</p>
        </div>

        <div class="login-body">
            <!-- Tabs -->
            <div class="flex border-b border-gray-100 mb-8 overflow-x-auto no-scrollbar">
                <button type="button" class="tab-btn active flex-1" onclick="switchTab('applicant')">
                    <i class="ri-user-heart-line me-1"></i> สำหรับผู้สมัคร
                </button>
                <button type="button" class="tab-btn flex-1" onclick="switchTab('employee')">
                    <i class="ri-user-settings-line me-1"></i> สำหรับพนักงาน
                </button>
            </div>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl mb-6 flex items-center gap-3 animate-pulse">
                    <i class="ri-error-warning-fill text-lg"></i>
                    <p class="text-xs font-bold"><?= htmlspecialchars($_SESSION['login_error']); ?></p>
                </div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['register_success'])): ?>
                <div class="bg-green-50 border border-green-100 text-green-600 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
                    <i class="ri-checkbox-circle-fill text-lg"></i>
                    <p class="text-xs font-bold"><?= htmlspecialchars($_SESSION['register_success']); ?></p>
                </div>
                <?php unset($_SESSION['register_success']); ?>
            <?php endif; ?>

            <form action="?action=authenticate" method="POST" id="loginForm">
                <?= CSRFMiddleware::getHiddenField() ?>
                <input type="hidden" name="login_type" id="loginType" value="applicant">

                <div class="mb-4">
                    <label id="userLabel" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">อีเมล (Email)</label>
                    <div class="relative">
                        <i id="userIcon" class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="username" id="username" class="form-control w-full pl-12" placeholder="your@email.com" required>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">รหัสผ่าน (Password)</label>
                    <div class="relative">
                        <i class="ri-lock-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" class="form-control w-full pl-12" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login w-full text-sm">
                    เข้าสู่ระบบ
                    <i class="ri-arrow-right-line ms-2"></i>
                </button>
            </form>

            <div id="applicantFooter" class="text-center mt-6">
                <p class="text-sm text-gray-500">
                    ยังไม่มีบัญชี?
                    <a href="/Modules/IGA/?action=register" class="text-primary font-bold hover:underline">สมัครสมาชิกใหม่</a>
                </p>
            </div>

            <div id="ssoSection" style="display: none;">
                <div class="divider">
                    <span>หรือ (OR)</span>
                </div>

                <a href="/auth/microsoft/login?redirect_to=/Modules/IGA/" class="sso-link">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft" class="w-4 h-4">
                    <span>เข้าสู่ระบบผ่าน Microsoft SSO</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            // Update tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('onclick').includes(type)) {
                    btn.classList.add('active');
                }
            });

            // Update form
            document.getElementById('loginType').value = type;
            const userLabel = document.getElementById('userLabel');
            const usernameInput = document.getElementById('username');
            const userIcon = document.getElementById('userIcon');
            const ssoSection = document.getElementById('ssoSection');

            if (type === 'employee') {
                userLabel.textContent = 'ชื่อผู้ใช้ หรือ อีเมล (Username / Email)';
                usernameInput.placeholder = 'ระบุชื่อผู้ใช้ หรือ อีเมล';
                userIcon.classList.remove('ri-mail-line');
                userIcon.classList.add('ri-user-line');
                document.getElementById('applicantFooter').style.display = 'none';
                ssoSection.style.display = 'block';
            } else {
                userLabel.textContent = 'อีเมล (Email)';
                usernameInput.placeholder = 'your@email.com';
                userIcon.classList.remove('ri-user-line');
                userIcon.classList.add('ri-mail-line');
                document.getElementById('applicantFooter').style.display = 'block';
                ssoSection.style.display = 'none';
            }
        }

        // Initialize view based on default tab
        window.onload = function() {
            switchTab('applicant');
        };
    </script>
</body>

</html>