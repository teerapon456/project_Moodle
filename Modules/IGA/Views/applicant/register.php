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
            padding: 2rem 1rem;
        }

        .auth-card {
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

        .auth-header {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            padding: 3rem 2rem;
            text-align: center;
            color: #fff;
            position: relative;
        }

        .auth-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: #fff;
            border-radius: 100% 100% 0 0;
        }

        .auth-body {
            padding: 2.5rem 2.5rem 3rem;
            background: #fff;
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

        .btn-primary-maroon {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            border: none;
            border-radius: 12px;
            padding: 0.875rem;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
            transition: all 0.2s;
        }

        .btn-primary-maroon:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(185, 28, 28, 0.4);
            filter: brightness(1.1);
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <div class="auth-header">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-2xl backdrop-blur-md mb-4">
                <i class="ri-user-add-line text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold">สมัครสมาชิกใหม่</h3>
            <p class="text-sm opacity-90 mt-1">กรุณากรอกข้อมูลเพื่อลงทะเบียนผู้สมัคร</p>
        </div>
        <div class="auth-body">
            <?php if (isset($_SESSION['register_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ri-error-warning-line me-2 text-lg"></i>
                        <small><?php echo htmlspecialchars($_SESSION['register_error']); ?></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['register_error']); ?>
            <?php endif; ?>

            <form action="?action=register_process" method="POST">
                <?= CSRFMiddleware::getHiddenField() ?>
                <div class="mb-3">
                    <label for="fullname" class="form-label text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="ri-user-line"></i>
                        </span>
                        <input type="text" class="form-control pl-10 bg-slate-50 border-slate-100" id="fullname" name="fullname" placeholder="ชื่อจริง นามสกุล" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">อีเมล <span class="text-danger">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="ri-mail-line"></i>
                        </span>
                        <input type="email" class="form-control pl-10 bg-slate-50 border-slate-100" id="email" name="email" placeholder="email@example.com" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">เบอร์โทรศัพท์</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="ri-phone-line"></i>
                        </span>
                        <input type="tel" class="form-control pl-10 bg-slate-50 border-slate-100" id="phone" name="phone" placeholder="0xx-xxx-xxxx">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">รหัสผ่าน <span class="text-danger">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="ri-lock-line"></i>
                        </span>
                        <input type="password" class="form-control pl-10 bg-slate-50 border-slate-100" id="password" name="password" placeholder="อย่างน้อย 6 ตัวอักษร" minlength="6" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="form-label text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="ri-lock-password-line"></i>
                        </span>
                        <input type="password" class="form-control pl-10 bg-slate-50 border-slate-100" id="confirm_password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง" minlength="6" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary-maroon w-100 shadow-sm">
                    <i class="ri-checkbox-circle-line me-2"></i>สร้างบัญชีผู้ใช้งาน
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="text-sm text-slate-500">
                    มีบัญชีอยู่แล้ว?
                    <a href="/Modules/IGA/?action=login" class="text-primary font-bold hover:underline">เข้าสู่ระบบ</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>