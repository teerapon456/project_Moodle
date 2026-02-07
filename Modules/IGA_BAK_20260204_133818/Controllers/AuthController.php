<?php

require_once 'IGABaseController.php';

class AuthController extends IGABaseController
{
    // Allow public access to auth methods
    protected function requireAuth()
    {
        return true;
    }

    // Allow public access (view/execute)
    protected function hasPermission($permission)
    {
        return true;
    }

    // Override render to NOT use main layout for auth pages
    protected function renderAuth($viewName, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . '/../Views/' . $viewName . '.php';
        if (file_exists($viewPath)) {
            // Include Main Layout Header Structure (simplified) or just full HTML page
            require_once __DIR__ . '/../Views/layouts/main.php';
            // WAIT - main.php includes sidebar/header which requires login session possibly.
            // Auth pages should be standalone HTML.
            // So we just include the view directly, assuming the view has full HTML structure or we make a simple auth layout.
            // Our previous steps created Views/auth/login.php with full HTML structure? No, they are `div` mostly, need header.
            // Let's create a minimal layout wrapper here or just assume login.php has full structure.
            // Looking at STEP 1839 content... it has `div` but no `<html>` `<body>`.
            // So we need a wrapper.
?>
            <!DOCTYPE html>
            <html lang="th">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>เข้าสู่ระบบ - IGA</title>
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
                <!-- Tailwind CSS (Local) -->
                <link rel="stylesheet" href="../../public/assets/css/tailwind.css">
                <style>
                    body {
                        font-family: 'Kanit', sans-serif;
                    }
                </style>
            </head>

            <body>
                <?php require $viewPath; ?>
            </body>

            </html>
<?php
        } else {
            die("View not found: $viewName");
        }
    }

    public function login()
    {
        if (isset($_SESSION['iga_applicant']) || isset($_SESSION['user'])) {
            header('Location: index.php?controller=exam&action=index');
            exit;
        }
        $this->renderAuth('auth/login');
    }

    public function register()
    {
        if (isset($_SESSION['iga_applicant']) || isset($_SESSION['user'])) {
            header('Location: index.php?controller=exam&action=index');
            exit;
        }
        $this->renderAuth('auth/register');
    }

    public function loginPost()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->renderAuth('auth/login', ['error' => 'กรุณากรอกอีเมลและรหัสผ่าน']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM iga_applicants WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Success
            $_SESSION['iga_applicant'] = [
                'id' => $user['applicant_id'],
                'fullname' => $user['full_name'],
                'email' => $user['email'],
                'role' => 'applicant' // Distinguish from 'user' (employee)
            ];

            // Login success - Redirect to Exam Dashboard
            header('Location: index.php?controller=exam&action=index');
            exit;
        } else {
            $this->renderAuth('auth/login', ['error' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง']);
        }
    }

    public function registerPost()
    {
        $fullname = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $org = $_POST['organization'] ?? '';

        if ($password !== $confirm) {
            $this->renderAuth('auth/register', ['error' => 'รหัสผ่านไม่ตรงกัน']);
            return;
        }

        // Check exists
        $stmt = $this->pdo->prepare("SELECT 1 FROM iga_applicants WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $this->renderAuth('auth/register', ['error' => 'อีเมลนี้ถูกใช้งานแล้ว']);
            return;
        }

        // Create
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO iga_applicants (email, password_hash, full_name, phone, organization, is_active) VALUES (?, ?, ?, ?, ?, 1)");

        try {
            $stmt->execute([$email, $hash, $fullname, $phone, $org]);
            header('Location: index.php?controller=auth&action=login&registered=1');
            exit;
        } catch (Exception $e) {
            $this->renderAuth('auth/register', ['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    public function logout()
    {
        // Only clear applicant session, leave main portal session if any?
        // Or logout everything? User requested separate flows.
        // If logged in as applicant, clear applicant.
        if (isset($_SESSION['iga_applicant'])) {
            unset($_SESSION['iga_applicant']);
            header('Location: index.php?controller=auth&action=login');
            exit;
        }

        // If employee, maybe redirect to main portal logout?
        // Or just redirect to auth page?
        // If employee logs out here, do we kill portal session?
        // Usually logout means logout from current context.
        if (isset($_SESSION['user'])) {
            header('Location: ../../public/logout.php'); // Assuming main logout
            exit;
        }

        header('Location: index.php?controller=auth&action=login');
    }
}
