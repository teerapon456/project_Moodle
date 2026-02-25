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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("SELECT u.*, r.name as role_name 
                                    FROM users u 
                                    LEFT JOIN roles r ON u.role_id = r.id 
                                    WHERE (u.username = :u OR u.email = :e) 
                                    AND u.is_active = 1 
                                    LIMIT 1");
            $stmt->execute([':u' => $username, ':e' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {

                // Permission Check
                $moduleCode = 'DORMITORY';
                $roleId = $user['role_id'];

                $permStmt = $conn->prepare("
                    SELECT 1 
                    FROM core_modules cm 
                    JOIN core_module_permissions cmp ON cm.id = cmp.module_id 
                    WHERE cm.code = :code AND cmp.role_id = :role_id AND cmp.can_view = 1
                ");
                $permStmt->execute([':code' => $moduleCode, ':role_id' => $roleId]);

                if (!$permStmt->fetchColumn()) {
                    $error = "คุณไม่มีสิทธิ์เข้าใช้งานระบบหอพัก";
                } else {
                    // Login Success
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'fullname' => $user['fullname'],
                        'role_id' => $user['role_id'],
                        'role' => $user['role_name'],
                        'department' => $user['Level3Name'], // Use Level3Name for department
                        'position' => $user['PositionName'], // Use PositionName
                        'default_supervisor_id' => $user['default_supervisor_id']
                    ];

                    // Log login
                    try {
                        $logStmt = $conn->prepare("INSERT INTO user_logins (user_id, user_name, action, ip_address, user_agent, created_at) VALUES (?, ?, 'login', ?, ?, NOW())");
                        $logStmt->execute([
                            $user['id'],
                            $user['username'],
                            $_SERVER['REMOTE_ADDR'],
                            $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                    } catch (Exception $e) { /* Ignore log error */
                    }

                    header("Location: index.php");
                    exit;
                }
            } else {
                $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        } catch (Exception $e) {
            $error = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
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
    <title>เข้าสู่ระบบ - หอพัก (Dormitory)</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $publicUrl ?>/assets/css/tailwind.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">ระบบหอพัก</h1>
            <p class="text-gray-600">กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
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
        <div class="text-center mt-4 text-sm text-gray-500">
            <a href="<?= $baseUrl ?>" class="text-blue-500 hover:text-blue-800">กลับหน้าหลัก Portal</a>
        </div>
    </div>
</body>

</html>