<?php
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
require_once __DIR__ . '/../../core/Database/Database.php';
require_once __DIR__ . '/../../core/Helpers/UrlHelper.php';

use Core\Helpers\UrlHelper;

startOptimizedSession();

$basePath = UrlHelper::getBasePath();
$baseUrl = UrlHelper::getBaseUrl();
$assetBase = UrlHelper::getAssetBase();
$publicUrl = rtrim($assetBase, '/');

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = null;
$redirectTo = $_REQUEST['redirect_to'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['_csrf_token'] ?? '';
    $redirectTo = $_POST['redirect_to'] ?? 'index.php';

    require_once __DIR__ . '/../../core/Security/CsrfHelper.php';
    require_once __DIR__ . '/../../core/Auth/AuthService.php';

    if (!\Core\Security\CsrfHelper::validateToken($csrfToken)) {
        $error = "Session expired or invalid token. Please try again.";
    } elseif (!empty($username) && !empty($password)) {
        $auth = new \Core\Auth\AuthService();
        $result = $auth->authenticate($username, $password, 'CAR_BOOKING');

        if ($result['success']) {
            $auth->initializeSession($result['user']);
            // Final safety check for redirect destination
            if (empty($redirectTo) || strpos($redirectTo, 'login.php') !== false) {
                $redirectTo = 'index.php';
            }
            header("Location: " . $redirectTo);
            exit;
        } else {
            $error = $result['message'];
        }
    } else {
        $error = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - จองรถ (Car Booking)</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $publicUrl ?>/assets/css/tailwind.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
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

<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">ระบบจองรถ</h1>
            <p class="text-gray-600">กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php
            require_once __DIR__ . '/../../core/Security/CsrfHelper.php';
            \Core\Security\CsrfHelper::insertField();
            ?>
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectTo) ?>">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                    ชื่อผู้ใช้ / อีเมล
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" name="username" type="text" placeholder="ระบุชื่อผู้ใช้ หรือ อีเมล" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    รหัสผ่าน
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" type="password" placeholder="ระบุรหัสผ่าน" required>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                    เข้าสู่ระบบ
                </button>
            </div>
        </form>

        <div class="divider">
            <span>หรือ (OR)</span>
        </div>

        <?php
        $ssoRedirect = !empty($redirectTo) && $redirectTo !== 'index.php' ? $redirectTo : '/Modules/CarBooking/';
        ?>
        <a href="/auth/microsoft/login?redirect_to=<?= urlencode($ssoRedirect) ?>" class="sso-link">
            <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft" class="w-4 h-4">
            <span>เข้าสู่ระบบผ่าน Microsoft SSO</span>
        </a>

        <div class="text-center mt-6 text-sm text-gray-500">
            <a href="/" class="text-blue-500 hover:text-blue-800">กลับหน้าหลัก Portal</a>
        </div>
    </div>
</body>

</html>