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

        .btn-primary-maroon {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            border: none;
            border-radius: 12px;
            padding: 0.875rem;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
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
                <?php if ($verifySuccess): ?>
                    <i class="ri-checkbox-circle-line text-3xl text-emerald-300"></i>
                <?php else: ?>
                    <i class="ri-error-warning-line text-3xl text-yellow-200"></i>
                <?php endif; ?>
            </div>
            <h3 class="text-2xl font-bold"><?= $verifySuccess ? 'ยืนยันสำเร็จ!' : 'ไม่สามารถยืนยันได้' ?></h3>
            <p class="text-sm opacity-90 mt-1">ระบบยืนยันตัวตนสำหรับผู้ใช้งาน</p>
        </div>
        <div class="auth-body text-center">
            <p class="text-slate-600 mb-8 leading-relaxed">
                <?= htmlspecialchars($verifyMessage) ?>
            </p>
            <a href="/Modules/IGA/?action=login" class="btn-primary-maroon w-100 shadow-sm">
                <i class="ri-login-box-line me-2"></i>เข้าสู่ระบบตอนนี้
            </a>
        </div>
    </div>
</body>

</html>