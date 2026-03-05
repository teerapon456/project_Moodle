<?php

namespace Core\Auth;

require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Helpers/IpHelper.php';
require_once __DIR__ . '/../Security/SecureSession.php';

use Core\Helpers\IpHelper;
use SecureSession;
use PDO;
use Exception;

/**
 * AuthService - Centralized authentication and permission logic
 */
class AuthService
{
    private $db;
    private $pdo;

    public function __construct()
    {
        $this->db = new \Database();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Authenticate user and check permissions/requirements
     * 
     * @param string $username
     * @param string $password
     * @param string|null $moduleCode Optional module code to check for 'view' permission
     * @param array $extra [latitude => float, longitude => float]
     * @return array [success => bool, message => string, user => array|null, code => string|null]
     */
    public function authenticate($username, $password, $moduleCode = null, $extra = [])
    {
        try {
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน', 'code' => 'incomplete_data'];
            }

            // Check Geolocation if mandatory
            if ($this->isGeoMandatory()) {
                if (empty($extra['latitude']) || empty($extra['longitude'])) {
                    return [
                        'success' => false,
                        'message' => 'กรุณาระบุตำแหน่งที่ตั้งก่อนเข้าสู่ระบบ',
                        'code' => 'location_required'
                    ];
                }
            }

            $stmt = $this->pdo->prepare("
                SELECT u.*, r.name as role_name, r.is_active as role_is_active
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE (u.username = :u OR u.email = :e) 
                AND u.is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([':u' => $username, ':e' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง', 'code' => 'invalid_credentials'];
            }

            if (isset($user['role_is_active']) && $user['role_is_active'] == 0) {
                return ['success' => false, 'message' => 'สิทธิ์การใช้งานของคุณถูกระงับ (Role Inactive)', 'code' => 'role_inactive'];
            }

            // Optional: Module Permission Check
            if ($moduleCode) {
                if (!$this->hasModulePermission($user['role_id'], $moduleCode)) {
                    return [
                        'success' => false,
                        'message' => 'คุณไม่มีสิทธิ์เข้าใช้งานในส่วนนี้',
                        'code' => 'no_permission'
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'user' => $user
            ];
        } catch (Exception $e) {
            error_log("AuthService Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ', 'code' => 'system_error'];
        }
    }

    /**
     * Check if Geolocation is mandatory
     */
    public function isGeoMandatory()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'mandatory_geolocation' LIMIT 1");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            return $val !== '0';
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Initialize session for an authenticated user
     * 
     * @param array $user User data from database
     * @param array $extra Geolocation or other metadata for logging
     */
    public function initializeSession($user, $extra = [])
    {
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'fullname' => $user['fullname'],
            'role_id' => $user['role_id'],
            'role' => $user['role_name'] ?? 'Unknown',
            'role_active' => (int)($user['role_is_active'] ?? 1),
            'user_active' => (int)($user['is_active'] ?? 1),
            'department' => $user['Level3Name'] ?? null,
            'position' => $user['PositionName'] ?? null,
            'default_supervisor_id' => $user['default_supervisor_id'] ?? null
        ];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user'] = $userData;
        $_SESSION['last_sync'] = time();

        // Log the successful login with geolocation if provided
        $this->logActivity(
            $user['id'],
            $user['fullname'] ?? $user['username'],
            'login',
            null,
            $extra
        );
    }

    /**
     * Check if a role has 'view' permission for a specific module
     */
    public function hasModulePermission($roleId, $moduleCode)
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 
            FROM core_modules cm 
            JOIN core_module_permissions cmp ON cm.id = cmp.module_id 
            WHERE cm.code = :code AND cmp.role_id = :role_id AND cmp.can_view = 1
            LIMIT 1
        ");
        $stmt->execute([':code' => $moduleCode, ':role_id' => $roleId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Log user activity
     */
    public function logActivity($userId, $username, $action, $details = null, $extra = [])
    {
        try {
            require_once __DIR__ . '/../Services/DeviceDetector.php';
            $detector = new \DeviceDetector($_SERVER['HTTP_USER_AGENT'] ?? '');

            $stmt = $this->pdo->prepare("
                INSERT INTO user_logins 
                (user_id, user_name, action, ip_address, user_agent, 
                 device_type, device_brand, device_model, os_name, os_version, 
                 client_type, client_name, client_version, latitude, longitude, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $lat = $extra['latitude'] ?? null;
            $lon = $extra['longitude'] ?? null;

            $stmt->execute([
                $userId,
                $username,
                $action,
                IpHelper::getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $detector->getDeviceType(),
                $detector->getDeviceBrand(),
                $detector->getDeviceModel(),
                $detector->getOSName(),
                $detector->getOSVersion(),
                'browser',
                $detector->getClientName(),
                $detector->getClientVersion(),
                $lat,
                $lon,
                $details
            ]);
        } catch (Exception $e) {
            error_log("Failed to log activity in AuthService: " . $e->getMessage());
        }
    }
}
