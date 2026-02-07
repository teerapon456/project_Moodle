<?php

namespace IGA\Controllers;

class AuthController
{
    private $conn;
    private const IGA_MODULE_ID = 5; // IGA module ID in core_modules

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function showLogin()
    {
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        require __DIR__ . '/../Views/login.php';
    }

    public function login()
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $userType = $_POST['user_type'] ?? 'employee';

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = "กรุณากรอก Username และ Password";
            header("Location: ?page=login");
            exit;
        }

        if ($userType === 'applicant') {
            $this->loginApplicant($username, $password);
        } else {
            $this->loginEmployee($username, $password);
        }
    }

    /**
     * Employee Login: Query users table, verify password, check IGA permission
     */
    private function loginEmployee(string $username, string $password)
    {
        // 1. Find user by username OR email
        $sql = "SELECT user_id, username, email, password_hash, role_id, fullname, is_active 
                FROM users 
                WHERE (username = ? OR email = ?) AND is_active = 1 
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['login_error'] = "ไม่พบบัญชีผู้ใช้หรือบัญชีถูกระงับ";
            header("Location: ?page=login");
            exit;
        }

        // 2. Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $_SESSION['login_error'] = "รหัสผ่านไม่ถูกต้อง";
            header("Location: ?page=login");
            exit;
        }

        // 3. Check IGA module permission
        $roleId = (int)$user['role_id'];
        $hasAccess = $this->checkModulePermission($roleId, self::IGA_MODULE_ID);

        if (!$hasAccess) {
            $_SESSION['login_error'] = "คุณไม่มีสิทธิ์เข้าใช้งานระบบ IGA";
            header("Location: ?page=login");
            exit;
        }

        // 4. Success - Set session
        $_SESSION['iga_user_id'] = $user['user_id'];
        $_SESSION['iga_user_type'] = 'employee';
        $_SESSION['iga_role_id'] = $roleId;
        $_SESSION['iga_username'] = $user['username'];
        $_SESSION['iga_fullname'] = $user['fullname'] ?? $user['username'];
        $_SESSION['iga_email'] = $user['email'];

        header("Location: ?page=dashboard");
        exit;
    }

    /**
     * Applicant Login: Query iga_applicant table by email
     */
    private function loginApplicant(string $email, string $password)
    {
        // 1. Find applicant by email
        $sql = "SELECT applicant_id, email, password_hash, fullname, is_active 
                FROM iga_applicant 
                WHERE email = ? AND is_active = 1 
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        $applicant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$applicant) {
            $_SESSION['login_error'] = "ไม่พบบัญชีผู้สมัครหรือบัญชีถูกระงับ";
            header("Location: ?page=login");
            exit;
        }

        // 2. Verify password
        if (!password_verify($password, $applicant['password_hash'])) {
            $_SESSION['login_error'] = "รหัสผ่านไม่ถูกต้อง";
            header("Location: ?page=login");
            exit;
        }

        // 3. Success - Set session (Applicants have automatic IGA access)
        $_SESSION['iga_user_id'] = $applicant['applicant_id'];
        $_SESSION['iga_user_type'] = 'applicant';
        $_SESSION['iga_role_id'] = 7; // Applicant role
        $_SESSION['iga_username'] = $applicant['email'];
        $_SESSION['iga_fullname'] = $applicant['fullname'];
        $_SESSION['iga_email'] = $applicant['email'];

        header("Location: ?page=dashboard");
        exit;
    }

    /**
     * Check if role has permission to view module
     */
    private function checkModulePermission(int $roleId, int $moduleId): bool
    {
        $sql = "SELECT can_view FROM core_module_permissions 
                WHERE role_id = ? AND module_id = ? 
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$roleId, $moduleId]);
        $perm = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $perm && $perm['can_view'] == 1;
    }

    public function showRegister()
    {
        $error = $_SESSION['register_error'] ?? null;
        $success = $_SESSION['register_success'] ?? null;
        unset($_SESSION['register_error'], $_SESSION['register_success']);
        require __DIR__ . '/../Views/register.php';
    }

    public function register()
    {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($fullname) || empty($email) || empty($password)) {
            $_SESSION['register_error'] = "กรุณากรอกข้อมูลที่จำเป็น (ชื่อ, อีเมล, รหัสผ่าน)";
            header("Location: ?page=register");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['register_error'] = "รูปแบบอีเมลไม่ถูกต้อง";
            header("Location: ?page=register");
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['register_error'] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
            header("Location: ?page=register");
            exit;
        }

        if ($password !== $confirmPassword) {
            $_SESSION['register_error'] = "รหัสผ่านไม่ตรงกัน";
            header("Location: ?page=register");
            exit;
        }

        // Check if email already exists
        $checkSql = "SELECT applicant_id FROM iga_applicant WHERE email = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $_SESSION['register_error'] = "อีเมลนี้ถูกใช้งานแล้ว";
            header("Location: ?page=register");
            exit;
        }

        // Hash password and generate activation token
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $activationToken = bin2hex(random_bytes(32)); // 64 char token

        // Insert with is_active=0 (pending activation)
        $sql = "INSERT INTO iga_applicant (email, password_hash, fullname, phone, is_active, activation_token, created_at) 
                VALUES (?, ?, ?, ?, 0, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([$email, $passwordHash, $fullname, $phone ?: null, $activationToken]);

        if ($result) {
            // Send activation email
            $this->sendActivationEmail($fullname, $email, $activationToken);

            $_SESSION['register_success'] = "สมัครสมาชิกสำเร็จ! กรุณาตรวจสอบอีเมลของคุณเพื่อยืนยันบัญชี";
            header("Location: ?page=register");
            exit;
        } else {
            $_SESSION['register_error'] = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
            header("Location: ?page=register");
            exit;
        }
    }

    /**
     * Activate account via token
     */
    public function activate()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $_SESSION['login_error'] = "ลิงก์ยืนยันไม่ถูกต้อง";
            header("Location: ?page=login");
            exit;
        }

        // Find applicant by token
        $sql = "SELECT applicant_id, is_active FROM iga_applicant WHERE activation_token = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$token]);
        $applicant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$applicant) {
            $_SESSION['login_error'] = "ลิงก์ยืนยันไม่ถูกต้องหรือหมดอายุ";
            header("Location: ?page=login");
            exit;
        }

        if ($applicant['is_active'] == 1) {
            $_SESSION['login_error'] = "บัญชีนี้ได้รับการยืนยันแล้ว";
            header("Location: ?page=login");
            exit;
        }

        // Activate account
        $updateSql = "UPDATE iga_applicant SET is_active = 1, activation_token = NULL, email_verified_at = NOW() WHERE applicant_id = ?";
        $updateStmt = $this->conn->prepare($updateSql);
        $updateStmt->execute([$applicant['applicant_id']]);

        $_SESSION['login_error'] = null;
        $_SESSION['activation_success'] = "ยืนยันบัญชีสำเร็จ! คุณสามารถเข้าสู่ระบบได้แล้ว";
        header("Location: ?page=login");
        exit;
    }

    /**
     * Send activation email to new applicant
     */
    private function sendActivationEmail(string $fullname, string $email, string $token): void
    {
        try {
            // Get email template
            $sql = "SELECT subject_th, body_th FROM email_templates WHERE template_key = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['iga_welcome']);
            $template = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$template) {
                error_log("IGA: Welcome email template not found");
                return;
            }

            // Build activation URL
            $activationUrl = "https://172.17.100.55:8443/iga/?page=activate&token=" . $token;

            // Replace placeholders
            $replacements = [
                '{full_name}' => $fullname,
                '{email}' => $email,
                '{registration_date}' => date('d/m/Y H:i'),
                '{login_url}' => $activationUrl, // Use activation link instead of login
                '{site_name}' => 'INTEQC Global Assessment'
            ];

            $subject = str_replace(array_keys($replacements), array_values($replacements), $template['subject_th']);
            $body = str_replace(array_keys($replacements), array_values($replacements), $template['body_th']);

            // Update button text in body
            $body = str_replace('เข้าสู่ระบบ</a>', 'ยืนยันบัญชี</a>', $body);
            $body = str_replace('Login Now</a>', 'Activate Account</a>', $body);

            // Use MailHelper
            require_once __DIR__ . '/../../../core/Helpers/MailHelper.php';
            \Core\Helpers\MailHelper::send($email, $subject, $body);
        } catch (\Exception $e) {
            error_log("IGA: Failed to send activation email: " . $e->getMessage());
        }
    }

    public function logout()
    {
        // Clear all IGA session data
        $keys = ['iga_user_id', 'iga_user_type', 'iga_role_id', 'iga_username', 'iga_fullname', 'iga_email'];
        foreach ($keys as $key) {
            unset($_SESSION[$key]);
        }

        header("Location: ?page=login");
        exit;
    }
}
