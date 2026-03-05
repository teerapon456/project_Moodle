<?php

require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../Models/ApplicantModel.php';
require_once __DIR__ . '/../../../core/Security/SecureSession.php';
require_once __DIR__ . '/../../../core/Services/EmailService.php';
require_once __DIR__ . '/../../../core/Security/CSRFMiddleware.php';

class ApplicantAuthController
{
    private $pdo;
    private $applicantModel;

    public function __construct()
    {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->applicantModel = new ApplicantModel($this->pdo);
    }

    public function processRequest()
    {
        $action = $_GET['action'] ?? 'login';

        switch ($action) {
            case 'index':
            case 'login':
                $this->login();
                break;
            case 'authenticate':
                $this->authenticate();
                break;
            case 'register':
                $this->register();
                break;
            case 'register_process':
                $this->registerProcess();
                break;
            case 'verify':
                $this->verify();
                break;
            case 'logout':
                $this->logout();
                break;
            default:
                $this->login();
                break;
        }
    }

    private function login()
    {
        // If already logged in as applicant, redirect to dashboard
        if (isset($_SESSION['applicant_id'])) {
            header("Location: /Modules/IGA/?page=dashboard");
            exit;
        }

        // Show login view
        $redirectTo = $_GET['redirect_to'] ?? '';
        header('Content-Type: text/html; charset=UTF-8');
        include __DIR__ . '/../Views/applicant/login.php';
    }

    private function authenticate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Modules/IGA/?action=login");
            exit;
        }

        $username = trim($_POST['username'] ?? ''); // Standardized field name
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = "กรุณากรอกอีเมลและรหัสผ่าน";
            header("Location: /Modules/IGA/?action=login");
            exit;
        }

        $applicant = $this->applicantModel->verifyPassword($username, $password);

        if ($applicant) {
            if ($applicant['is_active'] != 1) {
                $_SESSION['login_error'] = "บัญชีของคุณยังไม่ได้ยืนยันอีเมล กรุณาตรวจสอบอีเมลของคุณ";
                header("Location: /Modules/IGA/?action=login");
                exit;
            }

            // Set session variables specific to Applicant
            $_SESSION['user_id'] = 'APP_' . $applicant['applicant_id']; // To avoid conflict with employee user_id
            $_SESSION['applicant_id'] = $applicant['applicant_id'];
            $_SESSION['user'] = [
                'id' => 'APP_' . $applicant['applicant_id'],
                'email' => $applicant['email'],
                'fullname' => $applicant['fullname'],
                'is_applicant' => true,
            ];

            $redirectTo = $_POST['redirect_to'] ?? '/Modules/IGA/?page=dashboard';
            if (strpos($redirectTo, 'http') === 0) {
                $redirectTo = '/Modules/IGA/?page=dashboard';
            }

            header("Location: " . $redirectTo);
            exit;
        } else {
            $_SESSION['login_error'] = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
            header("Location: /Modules/IGA/?action=login");
            exit;
        }
    }

    private function register()
    {
        // Show registration view
        header('Content-Type: text/html; charset=UTF-8');
        include __DIR__ . '/../Views/applicant/register.php';
    }

    private function registerProcess()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Modules/IGA/?action=register");
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($email) || empty($password) || empty($fullname)) {
            $_SESSION['register_error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
            header("Location: /Modules/IGA/?action=register");
            exit;
        }

        if ($password !== $confirm_password) {
            $_SESSION['register_error'] = "รหัสผ่านไม่ตรงกัน";
            header("Location: /Modules/IGA/?action=register");
            exit;
        }

        // Check format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['register_error'] = "รูปแบบอีเมลไม่ถูกต้อง";
            header("Location: /Modules/IGA/?action=register");
            exit;
        }

        // Check if email exists
        if ($this->applicantModel->findByEmail($email)) {
            $_SESSION['register_error'] = "อีเมลนี้มีอยู่ในระบบแล้ว";
            header("Location: /Modules/IGA/?action=register");
            exit;
        }

        $data = [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'fullname' => $fullname,
            'phone' => $phone,
            'is_active' => 0 // ❗ Inactive until email verified
        ];

        $applicantId = $this->applicantModel->create($data);

        if ($applicantId) {
            // Generate verification token
            $token = $this->applicantModel->createVerificationToken($applicantId);

            // Build verification link
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
            }
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $verificationLink = "{$protocol}://{$host}/Modules/IGA/?action=verify&token={$token}";

            // Send verification email
            $emailSent = $this->sendVerificationEmail($email, $fullname, $verificationLink);

            if ($emailSent) {
                $_SESSION['register_success'] = "สมัครสมาชิกสำเร็จ! กรุณาตรวจสอบอีเมลของคุณเพื่อยืนยันบัญชี";
            } else {
                // Registration succeeded but email failed — log error, still show success
                error_log("[ApplicantAuth] Verification email failed to send to {$email}. Link: {$verificationLink}");
                $_SESSION['register_success'] = "สมัครสมาชิกสำเร็จ! กรุณาตรวจสอบอีเมลของคุณเพื่อยืนยันบัญชี (หากไม่ได้รับอีเมล กรุณาติดต่อผู้ดูแลระบบ)";
            }

            header("Location: /Modules/IGA/?action=login");
            exit;
        } else {
            $_SESSION['register_error'] = "เกิดข้อผิดพลาดในการลงทะเบียน โปรดลองอีกครั้ง";
            header("Location: /Modules/IGA/?action=register");
            exit;
        }
    }

    /**
     * Handle email verification link
     */
    private function verify()
    {
        $token = $_GET['token'] ?? '';
        $result = $this->applicantModel->verifyToken($token);

        // Pass result to the view
        $verifySuccess = $result['success'];
        $verifyMessage = $result['message'];

        header('Content-Type: text/html; charset=UTF-8');
        include __DIR__ . '/../Views/applicant/verify.php';
    }

    /**
     * Send verification email using MailHelper
     */
    private function sendVerificationEmail($toEmail, $toName, $verificationLink)
    {
        $siteName = Env::get('SMTP_FROM_NAME', 'MyHR Portal');
        $subject = "[{$siteName}] กรุณายืนยันอีเมลของคุณ";

        // Style the email better
        $body = '
        <div style="font-family: \'Kanit\', sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #eef2f6;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #b91c1c, #991b1b); padding: 40px 30px; text-align: center; color: #ffffff;">
                <div style="font-size: 40px; margin-bottom: 10px;">✉️</div>
                <h1 style="color: #ffffff; font-size: 24px; margin: 0; font-weight: 700;">ยืนยันอีเมลของคุณ</h1>
                <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin-top: 8px;">Integrity Global Assessment (IGA)</p>
            </div>

            <!-- Body -->
            <div style="padding: 40px 30px; color: #334155;">
                <p style="font-size: 18px; color: #1e293b; margin-bottom: 16px;">สวัสดีครับคุณ <strong>' . htmlspecialchars($toName) . '</strong>,</p>
                <p style="font-size: 15px; line-height: 1.8; margin-bottom: 24px;">
                    ขอบคุณที่สนใจสมัครเข้าร่วมระบบทดสอบ IGA ของเรา กรุณาคลิกปุ่มด้านล่างเพื่อยืนยันอีเมลและเปิดใช้งานบัญชีผู้สมัครของคุณ
                </p>

                <!-- CTA Button -->
                <div style="text-align: center; margin: 36px 0;">
                    <a href="' . htmlspecialchars($verificationLink) . '" 
                       style="display: inline-block; background: #b91c1c; 
                              color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 12px; 
                              font-size: 16px; font-weight: 700; box-shadow: 0 4px 12px rgba(185, 28, 28, 0.25);">
                        ยืนยันตัวตน และเข้าสู่ระบบ
                    </a>
                </div>

                <p style="font-size: 13px; color: #64748b; line-height: 1.6; background: #f8fafc; padding: 16px; border-radius: 8px;">
                    <strong>หมายเหตุ:</strong> ลิงก์นี้จะหมดอายุภายใน 1 ชั่วโมง หากคุณไม่ได้เป็นผู้สมัครสมาชิก กรุณาเพิกเฉยต่ออีเมลฉบับนี้
                </p>
            </div>

            <!-- Footer -->
            <div style="background: #f1f5f9; padding: 24px 30px; text-align: center;">
                <p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . ' — ระบบส่งอีเมลอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            </div>
        </div>';

        // Use system-wide EmailService for logging and reliable sending
        return EmailService::sendTestEmail($toEmail, $subject, $body);
    }

    private function logout()
    {
        // Unset applicant specific session variables
        unset($_SESSION['applicant_id']);
        if (isset($_SESSION['user']) && isset($_SESSION['user']['is_applicant']) && $_SESSION['user']['is_applicant']) {
            unset($_SESSION['user_id']);
            unset($_SESSION['user']);
        }

        session_destroy();
        header("Location: /Modules/IGA/?action=login");
        exit;
    }
}
