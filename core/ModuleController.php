<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/Database/Database.php';

/**
 * ModuleController
 * Centralized logic for all Modules (Identity, Gateway, Permissions, Email Context)
 */
class ModuleController extends BaseController
{
    protected $db;
    protected $pdo;
    protected $user;

    protected $moduleId;
    protected $moduleCode;

    public function __construct()
    {
        $this->db = new Database();
        $this->pdo = $this->db->getConnection();

        // Ensure session is properly started using optimized config
        // Use SessionConfig if available (faster session handling)
        $sessionConfigPath = __DIR__ . '/Config/SessionConfig.php';
        if (file_exists($sessionConfigPath)) {
            require_once $sessionConfigPath;
            if (function_exists('startOptimizedSession')) {
                startOptimizedSession();
            }
        } elseif (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        require_once __DIR__ . '/Security/SecureSession.php';
        // Auto-refresh session data every 5 minutes to prevent stale permissions
        if (isset($_SESSION['user']['id'])) {
            $lastSync = $_SESSION['last_sync'] ?? 0;
            if (time() - $lastSync > 300) { // 5 minutes
                $refreshedUser = SecureSession::refreshUserData($this->pdo, $_SESSION['user']['id']);
                if (!$refreshedUser) {
                    // Session was destroyed (user deleted or deactivated)
                    http_response_code(401);
                    $msg = 'เซสชันหมดอายุหรือสิทธิ์การใช้งานถูกระงับ กรุณาเข้าสู่ระบบใหม่';
                    if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
                        echo json_encode(['success' => false, 'message' => $msg, 'error' => 'role_inactive']);
                    } else {
                        require_once __DIR__ . '/Config/Env.php';
                        $basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
                        header("Location: " . $basePath . "/public/index.php?error=role_inactive");
                    }
                    exit;
                }
            }
        }

        $this->user = $_SESSION['user'] ?? null;

        // 1. Identify Module (Sets moduleId, moduleCode, and EmailConfig context)
        $this->identifyModule();

        // 2. Gateway Check: Must have at least 'view' permission to access this module
        // We check this ONLY if we are in a controller action context (GET/POST)
        // to avoid blocking background scripts if any.
        if (isset($_GET['action'])) {
            $this->requireAuth(); // Must be logged in

            if (!$this->hasPermission('view')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access Denied: You do not have permission to access ' . ($this->moduleCode ?? 'this module')]);
                exit;
            }
        }
    }

    /**
     * Identify Module based on input (mid) or folder structure
     * AND Set Email Config Context
     */
    protected function identifyModule()
    {
        // 1. Check Module ID from URL (HR Services) - Highest Priority
        if (isset($_GET['mid']) && is_numeric($_GET['mid'])) {
            $this->moduleId = (int)$_GET['mid'];
            $_SESSION['current_module_id'] = $this->moduleId;
        }
        // 2. Check Session
        elseif (isset($_SESSION['current_module_id'])) {
            $this->moduleId = (int)$_SESSION['current_module_id'];
        }

        // Validate/Fetch Code from ID
        if ($this->moduleId) {
            $stmt = $this->pdo->prepare("SELECT code FROM core_modules WHERE id = ? LIMIT 1");
            $stmt->execute([$this->moduleId]);
            $this->moduleCode = $stmt->fetchColumn();

            if ($this->moduleCode) {
                // Set Email Config Context Dynamically
                $this->setEmailContext();
                return;
            }
        }

        // 3. Fallback: Path Discovery
        $moduleFolder = basename(dirname(__DIR__)); // Assumes structure: Modules/{Name}/Controllers/Controler.php -> ../ -> {Name}
        // This might fail if ModuleController is in core. 
        // We should rely on the CHILD CLASS location.
        // We can use Reflection or debug_backtrace, but simply assuming Child is in Modules/X/Controllers works if we use logic properly.

        // How to get Child's directory?
        // get_class($this) returns Child class name.
        // $reflector = new ReflectionClass($this);
        // $fn = $reflector->getFileName();
        // basename(dirname(dirname($fn))) ...

        try {
            $reflector = new ReflectionClass($this);
            $childPath = $reflector->getFileName(); // e.g. .../Modules/Dormitory/Controllers/BaseController.php

            // Assume format: .../Modules/{ModuleName}/...
            // Extract Module Name (support both / and \ for Windows)
            if (preg_match('/Modules[\\/\\\\]([^\\/\\\\]+)[\\/\\\\]/', $childPath, $matches)) {
                $folderName = $matches[1];

                $stmt = $this->pdo->prepare("SELECT id, code FROM core_modules WHERE path LIKE ? LIMIT 1");
                $stmt->execute(["%Modules/$folderName%"]);
                $module = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($module) {
                    $this->moduleId = $module['id'];
                    $this->moduleCode = $module['code'];

                    $_SESSION['current_module_id'] = $this->moduleId;
                    $this->setEmailContext();
                } else {
                    $this->moduleCode = strtoupper($folderName);
                }
            } else {
                // Fallback for non-standard paths
                $this->moduleCode = 'UNKNOWN';
            }
        } catch (Exception $e) {
            error_log("Module Identity Error: " . $e->getMessage());
        }
    }

    private function setEmailContext()
    {
        if ($this->moduleId) {
            require_once __DIR__ . '/Config/EmailConfig.php';
            EmailConfig::setModule($this->moduleId);
        }
    }

    /**
     * Check permissions (Standard Dynamic Logic)
     */
    protected function hasPermission($permission)
    {
        if (!$this->user || !isset($this->user['role_id'])) return false;

        if (!$this->moduleId) return false;

        static $perms = null;
        // Cache per request (static variable in method scope persists calls)
        if ($perms === null) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT can_view, can_edit, can_manage, can_delete 
                    FROM core_module_permissions 
                    WHERE role_id = ? AND module_id = ? 
                    LIMIT 1
                ");
                $stmt->execute([$this->user['role_id'], $this->moduleId]);
                $perms = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$perms) {
                    $perms = ['can_view' => 0, 'can_edit' => 0, 'can_manage' => 0, 'can_delete' => 0];
                }
            } catch (Exception $e) {
                return false;
            }
        }

        switch ($permission) {
            case 'view':
                return $perms['can_view'] == 1 || $perms['can_edit'] == 1 || $perms['can_manage'] == 1;
            case 'edit':
                return $perms['can_edit'] == 1 || $perms['can_manage'] == 1;
            case 'manage':
                return $perms['can_manage'] == 1;
            case 'delete':
                return $perms['can_delete'] == 1;
            case 'admin':
                return $perms['can_manage'] == 1;
            default:
                return false;
        }
    }

    protected function requirePermission($permission)
    {
        $this->requireAuth();
        if ($this->hasPermission($permission)) {
            return true;
        }
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ในการดำเนินการนี้ (' . $permission . ')']);
        exit;
    }

    protected function requireAuth()
    {
        if (!$this->user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
            exit;
        }
        return true;
    }
    /**
     * Get Client IP Address (Robust)
     * Supports X-Forwarded-For (Proxies/Load Balancers)
     */
    protected function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle comma-separated list
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        if ($ip === '::1') {
            return '127.0.0.1';
        }
        return $ip;
    }
    /**
     * Helper: Handle Not Found (404)
     * Redirects to 404 page for browsers, returns JSON for APIs
     */
    protected function notFound($message = 'Resource not found')
    {
        // Check if it's an API request (JSON)
        $isApi = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) ||
            (isset($_GET['ajax']));

        if ($isApi) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }

        // Redirect to 404 for normal page loads
        require_once __DIR__ . '/Config/Env.php';
        $basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
        if ($basePath === '') {
            $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            // Typical script is public/index.php -> dirname is .../public
            // Or api routing -> .../
            $basePath = preg_replace('#/public$#', '', $scriptDir);
            if (basename($basePath) === 'api') {
                $basePath = dirname($basePath);
            }
        }
        // Redirect to 404
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $isDockerPublic = (basename($docRoot) === 'public');

        if ($isDockerPublic) {
            header("Location: /404.php");
        } else {
            header("Location: " . $basePath . "/public/404.php");
        }
        exit;
    }
}
