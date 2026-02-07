<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - INTEQC GLOBAL ASSESSMENT</title>
    <meta name="theme-color" content="#991B1B">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Tailwind -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .register-card {
            width: 100%;
            max-width: 450px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .card-header {
            background-color: #991B1B;
            padding: 25px 20px;
            text-align: center;
            color: white;
        }

        .header-icon-circle {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            color: #991B1B;
            font-size: 28px;
        }

        .btn-red {
            background-color: #991B1B;
            color: white;
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background 0.2s;
        }

        .btn-red:hover {
            background-color: #7f1d1d;
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-group label {
            display: block;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .input-wrapper {
            display: flex;
        }

        .input-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-right: none;
            border-radius: 4px 0 0 4px;
            padding: 0 12px;
            color: #6b7280;
        }

        .input-field {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 0 4px 4px 0;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .input-field:focus {
            border-color: #991B1B;
        }

        .error-box {
            background: #fef2f2;
            color: #991B1B;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }

        .success-box {
            background: #f0fdf4;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="register-card">
        <!-- Header -->
        <div class="card-header">
            <div class="header-icon-circle">
                <i class="ri-user-add-line"></i>
            </div>
            <h1 class="text-xl font-bold mb-1">สมัครสมาชิก</h1>
            <p class="text-xs opacity-90 uppercase tracking-wide">Create Applicant Account</p>
        </div>

        <!-- Body -->
        <div class="p-6">

            <?php if (!empty($error)): ?>
                <div class="error-box">
                    <i class="ri-error-warning-line mr-1"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-box">
                    <i class="ri-checkbox-circle-line mr-1"></i>
                    <?= htmlspecialchars($success) ?>
                    <br><a href="?page=login" class="text-green-700 font-bold underline mt-2 inline-block">เข้าสู่ระบบ</a>
                </div>
            <?php else: ?>

                <form action="?page=register&action=submit" method="POST" id="register-form">

                    <div class="input-group">
                        <label>ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                        <div class="input-wrapper">
                            <div class="input-icon"><i class="ri-user-line"></i></div>
                            <input type="text" name="fullname" class="input-field" placeholder="กรอกชื่อ-นามสกุล" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>อีเมล <span class="text-red-500">*</span></label>
                        <div class="input-wrapper">
                            <div class="input-icon"><i class="ri-mail-line"></i></div>
                            <input type="email" name="email" class="input-field" placeholder="example@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>เบอร์โทรศัพท์</label>
                        <div class="input-wrapper">
                            <div class="input-icon"><i class="ri-phone-line"></i></div>
                            <input type="tel" name="phone" class="input-field" placeholder="0812345678" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>รหัสผ่าน <span class="text-red-500">*</span></label>
                        <div class="input-wrapper">
                            <div class="input-icon"><i class="ri-lock-line"></i></div>
                            <input type="password" name="password" class="input-field" placeholder="อย่างน้อย 6 ตัวอักษร" required minlength="6">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>ยืนยันรหัสผ่าน <span class="text-red-500">*</span></label>
                        <div class="input-wrapper">
                            <div class="input-icon"><i class="ri-lock-line"></i></div>
                            <input type="password" name="confirm_password" class="input-field" placeholder="กรอกรหัสผ่านอีกครั้ง" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn-red text-lg shadow-lg mt-4">
                        <i class="ri-user-add-line mr-1"></i> สมัครสมาชิก
                    </button>

                </form>

            <?php endif; ?>

            <!-- Back to Login -->
            <div class="text-center mt-6 text-sm text-gray-600">
                มีบัญชีอยู่แล้ว? <a href="?page=login" class="text-blue-600 font-bold hover:underline">เข้าสู่ระบบ</a>
            </div>

        </div>
    </div>

</body>

</html>