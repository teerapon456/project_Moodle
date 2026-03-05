<?php

require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../Models/ApplicantModel.php';
require_once __DIR__ . '/../../../core/Security/SecureSession.php';

class AuthController
{
    private $pdo;

    public function __construct()
    {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }

    public function handleLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php");
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $loginType = $_POST['login_type'] ?? 'applicant';

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
            header("Location: index.php?page=login");
            exit;
        }

        if ($loginType === 'employee') {
            $this->authenticateEmployee($username, $password);
        } else {
            $this->authenticateApplicant($username, $password);
        }
    }

    private function authenticateEmployee($username, $password)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, r.name as role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE (u.username = :u OR u.email = :e) 
                AND u.is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([':u' => $username, ':e' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check IGA access
                require_once __DIR__ . '/../../../core/Helpers/PermissionHelper.php';
                $perms = userHasModuleAccess('IGA', (int)$user['role_id'], $this->pdo);

                if (empty($perms['can_view'])) {
                    $_SESSION['login_error'] = "คุณไม่มีสิทธิ์เข้าถึงโมดูล IGA";
                    header("Location: index.php?page=login");
                    exit;
                }

                // Set session variables identical to Portal
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'fullname' => $user['fullname'],
                    'role_id' => $user['role_id'],
                    'role' => $user['role_name'],
                    'department' => $user['Level3Name'],
                    'position' => $user['PositionName'],
                    'OrgUnitName' => $user['OrgUnitName'],
                    'emplevel_id' => $user['emplevel_id'],
                    'emptype' => $user['EmpType'] ?? 'employee'
                ];

                header("Location: index.php?page=dashboard");
                exit;
            } else {
                $_SESSION['login_error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                header("Location: index.php?page=login");
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['login_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
            header("Location: index.php?page=login");
            exit;
        }
    }

    private function authenticateApplicant($email, $password)
    {
        $applicantModel = new ApplicantModel($this->pdo);
        $applicant = $applicantModel->verifyPassword($email, $password);

        if ($applicant) {
            if ($applicant['is_active'] != 1) {
                $_SESSION['login_error'] = "บัญชีของคุณยังไม่ได้ยืนยันอีเมล กรุณาตรวจสอบอีเมลของคุณ";
                header("Location: index.php?page=login");
                exit;
            }

            $_SESSION['applicant_id'] = $applicant['applicant_id'];
            $_SESSION['user'] = [
                'id' => 'APP_' . $applicant['applicant_id'],
                'email' => $applicant['email'],
                'fullname' => $applicant['fullname'],
                'is_applicant' => true,
                'emptype' => 'applicant'
            ];

            header("Location: index.php?page=dashboard");
            exit;
        } else {
            $_SESSION['login_error'] = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
            header("Location: index.php?page=login");
            exit;
        }
    }
}
