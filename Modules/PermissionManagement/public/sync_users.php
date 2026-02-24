<?php

/*************************************************
 * sync_users.php — PermissionManagement Module
 * - Sync users from SQL Server (HRIS) to MySQL
 * - ใช้ EmpID จาก SQL Server เป็น user_id ใน MySQL
 * - ใช้ person_id เป็นคีย์หลักในการตรวจสอบ
 * - เพิ่มการป้องกัน Admin (user_id = 1)
 * - เปรียบเทียบข้อมูลก่อน update (skip ถ้าเหมือนเดิม)
 * - บันทึก Log การเปลี่ยนแปลงทั้ง insert และ update
 *************************************************/
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Security/AuthMiddleware.php';

// Use optimized session configuration
require_once __DIR__ . '/../../../core/Config/SessionConfig.php';

set_time_limit(600); // 10 minutes for large sync jobs
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');
while (function_exists('ob_get_level') && ob_get_level() > 0) {
    @ob_end_flush();
}
date_default_timezone_set('Asia/Bangkok');

/* ===== CONFIG ===== */
// SQL Server (source) - Use Env or hardcoded defaults
$SQLSRV_HOST = Env::get('SQLSRV_HOST', '172.17.100.26');
$SQLSRV_DB = Env::get('SQLSRV_DB', 'HRMULTI_INTEQC');
$SQLSRV_UID = Env::get('SQLSRV_UID', 'HRIS');
$SQLSRV_PWD = Env::get('SQLSRV_PWD', 'Hris@2024');

// MySQL (target) - Use Env config directly
$MYSQL_HOST = Env::get('DB_HOST', 'localhost');
$MYSQL_DB = Env::get('DB_NAME') ?? Env::get('DB_DATABASE', 'myhr');
$MYSQL_UID = Env::get('DB_USER') ?? Env::get('DB_USERNAME', 'root');
$MYSQL_PWD = Env::get('DB_PASS') ?? Env::get('DB_PASSWORD', '');
$MYSQL_PORT = Env::get('DB_PORT', 3306);

// Include Composer Autoload (for PHPMailer)
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../core/Services/EmailService.php';

/* ===== Hash secret ===== */

$HASH_SECRET = Env::get('HASH_SECRET', 'secret_key');

/* ===== hash helpers ===== */
function norm_email($v): ?string
{
    if ($v === null) return null;
    $v = strtolower(trim((string)$v));
    if ($v === '') return null;
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
}

function phash(?string $val): ?string
{
    global $HASH_SECRET;
    if ($val === null) return null;
    $val = trim($val);
    if ($val === '') return null;
    return hash_hmac('sha256', $val, $HASH_SECRET);
}

/* ===== Helper Functions ===== */

function only_digits(?string $s): ?string
{
    if ($s === null) return null;
    $s = preg_replace('/\D+/', '', $s);
    return ($s === '') ? null : $s;
}

function sendEmailNotify($subject, $messageHtml, $conn)
{
    global $conn_mysql;
    $conn = $conn ?? $conn_mysql; // Fallback

    try {
        $moduleId = getPermissionModuleId($conn);
        if (!$moduleId) return;

        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE module_id = ? AND setting_key = 'notification_email'");
        $stmt->execute([$moduleId]);
        $toEmail = $stmt->fetchColumn();

        if ($toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            // Use EmailService (same as CarBooking, Dormitory, etc.)
            $sent = EmailService::sendTestEmail($toEmail, $subject, $messageHtml);

            if ($sent) {
                echo "Email sent to $toEmail.\n";
            } else {
                echo "Failed to send email.\n";
            }
        }
    } catch (Exception $e) {
        echo "Email Error: " . $e->getMessage() . "\n";
    }
}

function mapEmpLevel($code)
{
    $map = [
        'L00' => 1,
        'L01' => 2,
        'L02' => 3,
        'L03' => 4,
        'L04' => 5,
        'L05' => 6,
        'L06' => 7,
        'L07' => 8,
        'L08' => 9,
        'L09' => 10,
        'L10' => 11,
        'L11' => 12,
        'L12' => 13,
        'L13' => 14,
        'L14' => 15,
        'L15' => 16,
        'L16' => 17,
    ];
    return $map[$code] ?? null;
}

/* ===== ROUTER & LOGIC ===== */
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Handle CLI arguments (parse key=value)
if (php_sapi_name() === 'cli') {
    foreach ($argv as $arg) {
        $e = explode('=', $arg, 2);
        if (count($e) == 2) {
            $_GET[$e[0]] = $e[1];
            $_REQUEST[$e[0]] = $e[1];
            if ($e[0] === 'action') $action = $e[1];
        }
    }
}

// Initialize DB connection for settings (needed for API)
$conn_mysql_settings = null;
try {
    $conn_mysql_settings = new PDO("mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8mb4;port=$MYSQL_PORT", $MYSQL_UID, $MYSQL_PWD);
    $conn_mysql_settings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Note: 'system_settings' table is assumed to exist with 'module_id' FK to 'core_modules'
} catch (Exception $e) {
    // Fail silently
}

// Get explicit module ID from request if available
$explicitModuleId = $_REQUEST['module_id'] ?? null;
if ($explicitModuleId && !is_numeric($explicitModuleId)) {
    $explicitModuleId = null;
}

// Helper to get Module ID dynamically based on file location
function getPermissionModuleId($conn, $explicitId = null)
{
    if ($explicitId && is_numeric($explicitId)) return (int)$explicitId;
    if (!$conn) return null;
    try {
        // Dectect Module Name from directory structure
        // Expected path: /path/to/Modules/{ModuleName}/public/...
        $currentDir = str_replace('\\', '/', __DIR__);
        $parts = explode('/Modules/', $currentDir);

        $moduleName = 'PermissionManagement'; // Default fallback
        if (count($parts) > 1) {
            $subPath = $parts[1];
            $folders = explode('/', $subPath);
            if (!empty($folders[0])) {
                $moduleName = $folders[0];
            }
        }

        // Try to find by Name or Code (using the detected folder name)
        $stmt = $conn->prepare("SELECT id FROM core_modules WHERE name = ? OR code = ? LIMIT 1");
        $stmt->execute([$moduleName, $moduleName]);
        $id = $stmt->fetchColumn();

        if ($id) return $id;

        // Last resort: Try common variations if folder name doesn't match DB exactly involved spacing
        // e.g. "PermissionManagement" folder vs "Permission Management" in DB
        $variations = [
            $moduleName,
            preg_replace('/(?<!^)[A-Z]/', ' $0', $moduleName), // Add space before caps
            str_replace('_', ' ', $moduleName),
            strtoupper(preg_replace('/(?<!^)[A-Z]/', '_$0', $moduleName)) // Camel to SNAKE_UPPER (PermissionManagement -> PERMISSION_MANAGEMENT)
        ];

        $stmt = $conn->prepare("SELECT id FROM core_modules WHERE name = ? OR code = ? LIMIT 1");
        foreach (array_unique($variations) as $var) {
            $stmt->execute([$var, $var]);
            if ($rowId = $stmt->fetchColumn()) return $rowId;
        }

        return null;
    } catch (Exception $e) {
        return null;
    }
}

if ($action === 'get_settings') {
    header('Content-Type: application/json');
    AuthMiddleware::checkLogin();

    $settings = [
        'auto_sync_enabled' => '0',
        'auto_sync_time' => '02:00',
        'notification_email' => ''
    ];


    if ($conn_mysql_settings) {
        $moduleId = getPermissionModuleId($conn_mysql_settings, $explicitModuleId);
        if ($moduleId) {
            $stmt = $conn_mysql_settings->prepare("SELECT setting_key, setting_value FROM system_settings WHERE module_id = ? AND setting_key IN ('auto_sync_enabled', 'auto_sync_time', 'notification_email')");
            $stmt->execute([$moduleId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    echo json_encode($settings);
    exit;
}

if ($action === 'save_settings') {
    header('Content-Type: application/json');
    AuthMiddleware::checkLogin();

    try {
        $moduleId = getPermissionModuleId($conn_mysql_settings, $explicitModuleId);
        if (!$moduleId) {
            throw new Exception("Module 'PermissionManagement' not found in core_modules table.");
        }

        $enabled = isset($_POST['auto_sync_enabled']) ? '1' : '0';
        $time = $_POST['auto_sync_time'] ?? '02:00';
        $email = $_POST['notification_email'] ?? '';

        // Helper to upsert
        $upsert = function ($key, $val) use ($conn_mysql_settings, $moduleId) {
            // Check if exists
            $stmt = $conn_mysql_settings->prepare("SELECT COUNT(*) FROM system_settings WHERE module_id = ? AND setting_key = ?");
            $stmt->execute([$moduleId, $key]);
            $exists = $stmt->fetchColumn() > 0;

            if ($exists) {
                $stmt = $conn_mysql_settings->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE module_id = ? AND setting_key = ?");
                $stmt->execute([$val, $moduleId, $key]);
            } else {
                $stmt = $conn_mysql_settings->prepare("INSERT INTO system_settings (module_id, setting_key, setting_value, updated_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$moduleId, $key, $val]);
            }
        };

        $upsert('auto_sync_enabled', $enabled);
        $upsert('auto_sync_time', $time);
        $upsert('notification_email', $email);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_last_sync') {
    header('Content-Type: application/json');
    AuthMiddleware::checkLogin();

    try {
        // Use existing settings connection or create new one if needed
        $conn = $conn_mysql_settings;
        if (!$conn) {
            $conn = new PDO("mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8mb4;port=$MYSQL_PORT", $MYSQL_UID, $MYSQL_PWD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $stmt = $conn->query("SELECT synced_at FROM user_sync_log ORDER BY id DESC LIMIT 1");
        $date = $stmt->fetchColumn();

        $dateStr = '-';
        if ($date) {
            $dt = new DateTime($date);
            $dateStr = $dt->format('d/m/Y H:i');
        }

        echo json_encode(['last_sync' => $dateStr]);
    } catch (Exception $e) {
        echo json_encode(['last_sync' => '-']);
    }
    exit;
}

if ($action === 'start' || $action === 'auto_run' || php_sapi_name() === 'cli') {
    // Check auto_run condition
    if ($action === 'auto_run' && $conn_mysql_settings) {
        $moduleId = getPermissionModuleId($conn_mysql_settings, $explicitModuleId);
        $config = [];

        if ($moduleId) {
            $stmt = $conn_mysql_settings->prepare("SELECT setting_key, setting_value FROM system_settings WHERE module_id = ?");
            $stmt->execute([$moduleId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $config[$row['setting_key']] = $row['setting_value'];
            }
        }

        $enabled = $config['auto_sync_enabled'] ?? '0';
        $targetTime = $config['auto_sync_time'] ?? '02:00';

        if ($enabled !== '1') {
            echo "Auto sync is disabled.\n";
            exit;
        }

        // Check time if running via auto_run (CRON should run every minute)
        // Only run if current time matches target time exactly (H:i)
        $currentTime = date('H:i');
        if ($currentTime !== $targetTime) {
            echo "Skipping: Current time ($currentTime) does not match scheduled time ($targetTime).\n";
            exit;
        }

        echo "Auto Sync Started at $currentTime...\n";
    }

    if (php_sapi_name() !== 'cli' && $action !== 'auto_run') {
        @apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 0);
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('Content-Encoding: none');
        // Disable output buffering for SSE
        while (ob_get_level()) ob_end_flush();
        ob_implicit_flush(true);
    }

    run_worker([
        'SQLSRV_HOST' => $SQLSRV_HOST,
        'SQLSRV_DB' => $SQLSRV_DB,
        'SQLSRV_UID' => $SQLSRV_UID,
        'SQLSRV_PWD' => $SQLSRV_PWD,
        'MYSQL_HOST' => $MYSQL_HOST,
        'MYSQL_DB' => $MYSQL_DB,
        'MYSQL_UID' => $MYSQL_UID,
        'MYSQL_PWD' => $MYSQL_PWD,
        'MYSQL_PORT' => $MYSQL_PORT,
        'HASH_SECRET' => $HASH_SECRET,
    ]);
    exit;
}

// Default: return error for web access without action
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid action. Use ?action=start']);
exit;

/*************** WORKER ****************/
function run_worker(array $config)
{
    extract($config);

    $send_update = function (array $data) {
        if (php_sapi_name() === 'cli') {
            echo "Status: " . ($data['status'] ?? 'unknown') . " | Message: " . ($data['message'] ?? '') . "\n";
            return;
        }
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    };

    // Connect MySQL (Needed early for notify fallback)
    $conn_mysql = null;
    try {
        $conn_mysql = new PDO("mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8mb4;port=$MYSQL_PORT", $MYSQL_UID, $MYSQL_PWD);
        $conn_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $send_update(['status' => 'error', 'total' => 0, 'done' => 0, 'message' => 'เชื่อม MySQL ไม่ได้: ' . $e->getMessage()]);
        return;
    }

    // Connect SQL Server via ODBC
    $conn_sqlsrv = null;
    try {
        $dsn = "odbc:Driver={ODBC Driver 18 for SQL Server};Server=$SQLSRV_HOST;Database=$SQLSRV_DB;TrustServerCertificate=yes";
        $conn_sqlsrv = new PDO($dsn, $SQLSRV_UID, $SQLSRV_PWD);
        $conn_sqlsrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $msg = 'เชื่อม SQL Server ไม่ได้: ' . $e->getMessage();
        $send_update(['status' => 'error', 'total' => 0, 'done' => 0, 'message' => $msg]);
        sendEmailNotify("Connection Error", $msg, $conn_mysql);
        return;
    }

    // Count query
    $tsql_count = <<<TSQL
    SELECT COUNT(*) AS TotalRows
    FROM uv_employee AS emp
    LEFT JOIN emAddress ed ON emp.EmpID = ed.RelatedID AND ed.AddressMode='info'
    WHERE LEN(emp.EmpCode)=10
    AND emp.EmpCode NOT LIKE '%[^0-9]%'
    AND emp.EmpCode NOT LIKE '193%'
    AND emp.OrgCode NOT IN ('INTEQC MART','PRIMATECH','LIVESTOCK')
    AND emp.EmpType IN ('Monthly','Daily')
    AND emp.EmplevelCode != 'L00'
TSQL;

    $stmt_count = $conn_sqlsrv->query($tsql_count);
    $total = (int)($stmt_count->fetch(PDO::FETCH_ASSOC)['TotalRows'] ?? 0);
    $stmt_count->closeCursor();

    if ($total === 0) {
        $send_update(['status' => 'finished', 'total' => 0, 'done' => 0, 'message' => 'ไม่พบข้อมูลที่จะอัปเดต']);
        return;
    }

    $send_update(['status' => 'running', 'total' => $total, 'done' => 0, 'message' => "พบข้อมูล $total รายการ กำลังเตรียมการอัปเดต..."]);

    // Data query
    $tsql_data = <<<TSQL
WITH EmployeeData AS (
      SELECT
           emp.PersonID, emp.EmpID, emp.FirstName, emp.LastName, emp.FullName,
           emp.IdentityCard, emp.Gender, emp.BirthDate, emp.Nationality,
           emp.EmpCode, emp.EmpLevelCode, emp.EmpType, emp.WorkingStatus,
           emp.OrgCode, emp.OrgUnitTypeName, emp.OrgUnitName,
           emp.OrgUnitCode AS OriginalOrgUnitCode, emp.OrgUnitCode,
           emp.FirstNameEng, emp.LastNameEng, ed.Email1,
           emp.PositionID, emp.PositionCode, emp.PositionName, emp.PositionNameEng, emp.StartDate
      FROM uv_employee AS emp
      LEFT JOIN emAddress ed ON emp.EmpID = ed.RelatedID AND ed.AddressMode='info'
      WHERE LEN(emp.EmpCode)=10
      AND emp.EmpCode NOT LIKE '%[^0-9]%'
      AND emp.EmpCode NOT LIKE '193%'
      AND emp.OrgCode NOT IN ('INTEQC MART','PRIMATECH','LIVESTOCK')
      AND emp.EmpType IN ('Monthly','Daily')
      AND emp.EmplevelCode != 'L00'
),
SplitData AS (
      SELECT ed.*,
           ed.OrgUnitCode AS OrgUnitCodeToSplit,
           CHARINDEX('-', ed.OrgUnitCode) AS pos1,
           CHARINDEX('-', ed.OrgUnitCode, CHARINDEX('-', ed.OrgUnitCode)+1) AS pos2,
           CHARINDEX('-', ed.OrgUnitCode, CHARINDEX('-', ed.OrgUnitCode, CHARINDEX('-', ed.OrgUnitCode)+1)+1) AS pos3,
           CHARINDEX('-', ed.OrgUnitCode, CHARINDEX('-', ed.OrgUnitCode, CHARINDEX('-', ed.OrgUnitCode, CHARINDEX('-', ed.OrgUnitCode)+1)+1)+1) AS pos4
      FROM EmployeeData ed
),
PartData AS (
      SELECT sd.*,
           (LEN(OrgUnitCodeToSplit)-LEN(REPLACE(OrgUnitCodeToSplit,'-',''))) AS dash_count,
           CASE WHEN (LEN(OrgUnitCodeToSplit)-LEN(REPLACE(OrgUnitCodeToSplit,'-',''))) >= 4 THEN SUBSTRING(OrgUnitCodeToSplit,1,pos4-1)
                ELSE OrgUnitCodeToSplit END AS TruncatedOrgUnitCode,
           CASE WHEN pos1>0 THEN SUBSTRING(OrgUnitCodeToSplit,1,pos1-1) ELSE OrgUnitCodeToSplit END AS Part1,
           CASE WHEN (LEN(OrgUnitCodeToSplit)-LEN(REPLACE(OrgUnitCodeToSplit,'-',''))) >= 1
                THEN (CASE WHEN pos2>0 THEN SUBSTRING(OrgUnitCodeToSplit,1,pos2-1) ELSE OrgUnitCodeToSplit END) ELSE NULL END AS Part2,
           CASE WHEN (LEN(OrgUnitCodeToSplit)-LEN(REPLACE(OrgUnitCodeToSplit,'-',''))) >= 2
                THEN (CASE WHEN pos3>0 THEN SUBSTRING(OrgUnitCodeToSplit,1,pos3-1) ELSE OrgUnitCodeToSplit END) ELSE NULL END AS Part3,
           CASE WHEN (LEN(OrgUnitCodeToSplit)-LEN(REPLACE(OrgUnitCodeToSplit,'-',''))) >= 3
                THEN (CASE WHEN pos4>0 THEN SUBSTRING(OrgUnitCodeToSplit,1,pos4-1) ELSE OrgUnitCodeToSplit END) ELSE NULL END AS Part4,
           CASE WHEN (LEN(OrgUnitCodeToSplit)-LEN(REPLACE(OrgUnitCodeToSplit,'-',''))) >= 4
                THEN OrgUnitCodeToSplit ELSE NULL END AS Part5
      FROM SplitData sd
)
SELECT
      p.PersonID, p.EmpID, p.EmpCode, p.FullName, p.IdentityCard,
      p.Email1 AS PersonnelEmail, p.Gender, p.BirthDate, p.Nationality,
      p.EmpLevelCode, p.EmpType, p.WorkingStatus, p.OrgCode,
      CASE p.OrgCode
           WHEN 'INTEQC GLOBAL' THEN 1
           WHEN 'INTEQC FOODS' THEN 2
           WHEN 'INTEQCFLOURMILL' THEN 3
           WHEN 'INTEQCSWINEFARM' THEN 4
           WHEN 'INTEQC SWINE BREEDER FARM' THEN 5
           WHEN 'LABINTER' THEN 6
           ELSE 0
      END AS OrgID,
      p.OrgUnitTypeName, p.OrgUnitName,
      p.OriginalOrgUnitCode, p.OrgUnitCode, p.TruncatedOrgUnitCode,
      p.EmpID AS user_id,
      p.EmpCode AS username,
      p.Email1 AS email,
      p.FullName AS fullname,
      CAST(4 AS INT) AS role_id,
      CONVERT(VARCHAR(19), GETDATE(), 120) AS created_at,
      CONVERT(VARCHAR(19), GETDATE(), 120) AS updated_at,
      CASE WHEN p.WorkingStatus='Working' THEN 1 ELSE 0 END AS is_active,
      p.PositionID, p.PositionCode, p.PositionName, p.PositionNameEng, p.StartDate,
      COALESCE(LookupOrg.OrgUnitName, p.TruncatedOrgUnitCode) AS TruncatedOrgUnitName,
      p.Part1 AS Level1Code, COALESCE(LookupPart1.OrgUnitName, p.Part1) AS Level1Name,
      p.Part2 AS Level2Code, COALESCE(LookupPart2.OrgUnitName, p.Part2) AS Level2Name,
      p.Part3 AS Level3Code, COALESCE(LookupPart3.OrgUnitName, p.Part3) AS Level3Name,
      p.Part4 AS Level4Code, COALESCE(LookupPart4.OrgUnitName, p.Part4) AS Level4Name,
      p.Part5 AS Level5Code, COALESCE(LookupPart5.OrgUnitName, p.Part5) AS Level5Name
FROM PartData AS p
LEFT JOIN emOrgUnit AS LookupOrg ON p.TruncatedOrgUnitCode = LookupOrg.OrgUnitCode AND LookupOrg.IsDeleted=0 AND LookupOrg.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart1 ON p.Part1 = LookupPart1.OrgUnitCode AND LookupPart1.IsDeleted=0 AND LookupPart1.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart2 ON p.Part2 = LookupPart2.OrgUnitCode AND LookupPart2.IsDeleted=0 AND LookupPart2.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart3 ON p.Part3 = LookupPart3.OrgUnitCode AND LookupPart3.IsDeleted=0 AND LookupPart3.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart4 ON p.Part4 = LookupPart4.OrgUnitCode AND LookupPart4.IsDeleted=0 AND LookupPart4.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart5 ON p.Part5 = LookupPart5.OrgUnitCode AND LookupPart5.IsDeleted=0 AND LookupPart5.IsInactive=0;
TSQL;

    $stmt_sqlsrv = $conn_sqlsrv->query($tsql_data);
    if (!$stmt_sqlsrv) {
        $msg = 'คิวรี SQL Server ผิดพลาด: ' . $conn_sqlsrv->errorInfo()[2];
        $send_update(['status' => 'error', 'total' => 0, 'done' => 0, 'message' => $msg]);
        sendEmailNotify("Query Error", $msg, $conn_mysql);
        return;
    }

    $done = 0;
    $inserted_count = 0;
    $updated_count = 0;
    $unchanged_count = 0;
    $error_count = 0;
    $last_error = '';
    $processed_ids = []; // Keep track of all processed person_ids
    $send_update(['status' => 'running', 'total' => $total, 'done' => 0, 'message' => 'เริ่มอัปเดต...']);

    // Prepare helper statements to avoid recreating them in loop
    $check_stmt = $conn_mysql->prepare("SELECT * FROM users WHERE person_id = ? LIMIT 1");
    $check_username_stmt = $conn_mysql->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");

    // LOG Statement
    $log_stmt = $conn_mysql->prepare("
        INSERT INTO user_sync_log (
            action, person_id, emp_code, fullname, field_name, old_value, new_value, synced_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    // INSERT Statement
    $ins_stmt = $conn_mysql->prepare("
        INSERT INTO users (
            person_id, user_id, username, password_hash, email, fullname, role_id, created_at, updated_at, is_active,
            EmpCode, PersonnelEmail, IdentityCard, Gender, BirthDate, Nationality,
            emplevel_id, EmpType, WorkingStatus, OrgID, OrgUnitTypeName, OrgUnitName,
            OriginalOrgUnitCode, OrgUnitCode, TruncatedOrgUnitCode, TruncatedOrgUnitName,
            Level1Code, Level1Name, Level2Code, Level2Name, Level3Code, Level3Name,
            Level4Code, Level4Name, Level5Code, Level5Name,
            PositionID, PositionCode, PositionName, PositionNameEng, StartDate
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?
        )
    ");

    while ($r = $stmt_sqlsrv->fetch(PDO::FETCH_ASSOC)) {
        $person_id = $r['PersonID'];
        $user_id = $r['EmpID'];

        // Track ID
        if ($person_id) {
            $processed_ids[] = $person_id;
        }

        $emp_code_raw = trim((string)$r['EmpCode']);
        $username = $emp_code_raw; // Use EmpCode as username explicitly
        $fullname_raw = $r['fullname'];

        // Password generation from birthdate (DDMMYYYY)
        $defaultPwdPlain = '01012000'; // Default fallback length 8
        $birthRaw = $r['BirthDate'] ?? null;
        if ($birthRaw instanceof DateTime) {
            $defaultPwdPlain = $birthRaw->format('dmY');
        } else {
            $birthStr = trim((string)$birthRaw);
            if ($birthStr !== '') {
                $birthStrNorm = str_replace('_', '-', $birthStr);
                // Capture Year($1), Month($2), Day($3)
                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $birthStrNorm, $m)) {
                    $defaultPwdPlain = $m[3] . $m[2] . $m[1]; // DDMMYYYY
                } else {
                    $ts = strtotime($birthStrNorm);
                    if ($ts !== false) $defaultPwdPlain = date('dmY', $ts);
                }
            }
        }
        $pwd_hash = password_hash($defaultPwdPlain, PASSWORD_BCRYPT);

        $email = norm_email($r['PersonnelEmail']) ?? '';
        $fullname = $r['fullname'];
        $role_id = $r['role_id'];
        $created_at = $r['created_at'];
        $updated_at = $r['updated_at'];
        $is_active = $r['is_active'];

        $empcode_for_col = phash($r['EmpCode']);
        $personnel_hashed = phash(strtolower(trim((string)$r['PersonnelEmail'])));
        $identity_hashed = phash(only_digits($r['IdentityCard']));

        $org_id = $r['OrgID'];
        $gender = $r['Gender'];
        $birth_date = ($r['BirthDate'] instanceof DateTime) ? $r['BirthDate']->format('Y-m-d') : null;
        if (!$birth_date && !empty($r['BirthDate'])) {
            $birth_date = date('Y-m-d', strtotime($r['BirthDate']));
        }
        $nationality = $r['Nationality'];
        $mapped_emplevel_id = mapEmpLevel($r['EmpLevelCode']);
        $emp_type = $r['EmpType'];
        $work_status = $r['WorkingStatus'];
        $unit_type = $r['OrgUnitTypeName'];
        $unit_name = $r['OrgUnitName'];
        $orig_unit = $r['OriginalOrgUnitCode'];
        $unit_code = $r['OrgUnitCode'];
        $trunc_code = $r['TruncatedOrgUnitCode'];
        $trunc_name = $r['TruncatedOrgUnitName'];
        $lvl1_code = $r['Level1Code'];
        $lvl1_name = $r['Level1Name'];
        $lvl2_code = $r['Level2Code'];
        $lvl2_name = $r['Level2Name'];
        $lvl3_code = $r['Level3Code'];
        $lvl3_name = $r['Level3Name'];
        $lvl4_code = $r['Level4Code'];
        $lvl4_name = $r['Level4Name'];
        $lvl5_code = $r['Level5Code'];
        $lvl5_name = $r['Level5Name'];

        $position_id = $r['PositionID'] ?? null;
        $position_code = $r['PositionCode'] ?? null;
        $position_name = $r['PositionName'] ?? null;
        $position_name_eng = $r['PositionNameEng'] ?? null;
        $start_date = ($r['StartDate'] instanceof DateTime) ? $r['StartDate']->format('Y-m-d H:i:s') : null;
        if (!$start_date && !empty($r['StartDate'])) {
            $start_date = date('Y-m-d H:i:s', strtotime($r['StartDate']));
        }

        // New data to compare for UPDATE
        // Protected fields NOT included (won't be overwritten):
        // - password_hash (user may have changed it)
        // - role_id (admin assigned)
        // - microsoft_id, microsoft_email (OAuth link)
        // - default_supervisor_* (manually assigned)
        // - created_at (keep original)
        $newData = [
            'email' => $email,
            'fullname' => $fullname,
            'is_active' => $is_active,
            'EmpCode' => $empcode_for_col,
            'PersonnelEmail' => $personnel_hashed,
            'IdentityCard' => $identity_hashed,
            'Gender' => $gender,
            'BirthDate' => $birth_date,
            'Nationality' => $nationality,
            'emplevel_id' => $mapped_emplevel_id,
            'EmpType' => $emp_type,
            'WorkingStatus' => $work_status,
            'OrgID' => $org_id,
            'OrgUnitTypeName' => $unit_type,
            'OrgUnitName' => $unit_name,
            'OriginalOrgUnitCode' => $orig_unit,
            'OrgUnitCode' => $unit_code,
            'TruncatedOrgUnitCode' => $trunc_code,
            'TruncatedOrgUnitName' => $trunc_name,
            'Level1Code' => $lvl1_code,
            'Level1Name' => $lvl1_name,
            'Level2Code' => $lvl2_code,
            'Level2Name' => $lvl2_name,
            'Level3Code' => $lvl3_code,
            'Level3Name' => $lvl3_name,
            'Level4Code' => $lvl4_code,
            'Level4Name' => $lvl4_name,
            'Level5Code' => $lvl5_code,
            'Level5Name' => $lvl5_name,
            'PositionID' => $position_id,
            'PositionCode' => $position_code,
            'PositionName' => $position_name,
            'PositionNameEng' => $position_name_eng,
            'StartDate' => $start_date,
            'password_hash' => $pwd_hash, // Added for reset (Subject to verification check)
        ];

        try {
            // Begin transaction for this user
            $conn_mysql->beginTransaction();

            $check_stmt->execute([$person_id]);
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

            // Fallback: Check by Username (EmpCode) if PersonID not found
            if (!$existing && !empty($emp_code_raw)) {
                // Clean emp code for lookup (remove any invisible chars)
                $lookup_code = only_digits($emp_code_raw);
                if ($lookup_code) {
                    $check_username_stmt->execute([$lookup_code]);
                    $existing = $check_username_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        // Force update person_id to match source (Data Correction)
                        $fix_pid_stmt = $conn_mysql->prepare("UPDATE users SET person_id = ? WHERE user_id = ?");
                        $fix_pid_stmt->execute([$person_id, $existing['user_id']]);

                        $existing['person_id'] = $person_id; // update local var for subsequent logic
                    }
                }
            }

            if ($existing) {
                // Skip admin user
                if ($existing['user_id'] == 1) {
                    $conn_mysql->commit();
                    $done++;
                    continue;
                }

                // Compare and find changes
                $changes = [];
                foreach ($newData as $field => $newVal) {
                    $oldVal = $existing[$field] ?? null;
                    // Normalize comparison
                    $oldNorm = ($oldVal === null || $oldVal === '') ? null : (string)$oldVal;
                    $newNorm = ($newVal === null || $newVal === '') ? null : (string)$newVal;

                    if ($oldNorm !== $newNorm) {
                        $changes[$field] = ['old' => $oldVal, 'new' => $newVal];
                    }
                }

                if (empty($changes)) {
                    // No changes, skip
                    $conn_mysql->commit();
                    $unchanged_count++;
                    $done++;
                    continue;
                }

                // Build dynamic UPDATE query with only changed fields
                $setClauses = [];
                $updateParams = [];
                foreach ($changes as $field => $vals) {
                    $setClauses[] = "`$field` = ?";
                    $updateParams[] = $vals['new'];

                    // Log the change
                    $log_stmt->execute([
                        'update',
                        $person_id,
                        $emp_code_raw,
                        $fullname_raw,
                        $field,
                        $vals['old'],
                        $vals['new']
                    ]);
                }
                $setClauses[] = "`updated_at` = ?";
                $updateParams[] = date('Y-m-d H:i:s');
                $updateParams[] = $person_id;

                $updateSql = "UPDATE `users` SET " . implode(', ', $setClauses) . " WHERE person_id = ? AND user_id != '1111'";
                $upd_stmt = $conn_mysql->prepare($updateSql);
                $upd_stmt->execute($updateParams);

                $updated_count++;
                $status_detail = 'อัปเดต ' . count($changes) . ' ฟิลด์';
            } else {
                // INSERT new record
                $ins_stmt->execute([
                    $person_id,
                    $user_id,
                    $username,
                    $pwd_hash,
                    $email,
                    $fullname,
                    $role_id,
                    $created_at,
                    $updated_at,
                    $is_active,
                    $empcode_for_col,
                    $personnel_hashed,
                    $identity_hashed,
                    $gender,
                    $birth_date,
                    $nationality,
                    $mapped_emplevel_id,
                    $emp_type,
                    $work_status,
                    $org_id,
                    $unit_type,
                    $unit_name,
                    $orig_unit,
                    $unit_code,
                    $trunc_code,
                    $trunc_name,
                    $lvl1_code,
                    $lvl1_name,
                    $lvl2_code,
                    $lvl2_name,
                    $lvl3_code,
                    $lvl3_name,
                    $lvl4_code,
                    $lvl4_name,
                    $lvl5_code,
                    $lvl5_name,
                    $position_id,
                    $position_code,
                    $position_name,
                    $position_name_eng,
                    $start_date
                ]);

                // Log insertion
                $log_stmt->execute([
                    'insert',
                    $person_id,
                    $emp_code_raw,
                    $fullname_raw,
                    'NEW_USER',
                    null,
                    "EmpCode: $emp_code_raw, Email: $email"
                ]);

                $inserted_count++;
                $status_detail = 'เพิ่มข้อมูลใหม่';
            }

            // Commit transaction
            $conn_mysql->commit();

            $done++;
            // Send updates every 10 records to reduce overhead
            if ($done % 10 == 0 || $done == $total) {
                $send_update([
                    'status' => 'running',
                    'total' => $total,
                    'done' => $done,
                    'inserted_count' => $inserted_count,
                    'updated_count' => $updated_count,
                    'unchanged_count' => $unchanged_count,
                    'message' => "กำลังดำเนินการ: $done/$total (ใหม่: $inserted_count, อัปเดต: $updated_count, ไม่เปลี่ยนแปลง: $unchanged_count)"
                ]);
            }
        } catch (PDOException $e) {
            // Rollback only current record
            if ($conn_mysql->inTransaction()) {
                $conn_mysql->rollBack();
            }

            if (php_sapi_name() === 'cli') {
                echo "Error Processing Record ($person_id): " . $e->getMessage() . "\n";
            }

            // Log error but continue with next record
            $error_count++;
            $last_error = $e->getMessage();
            $done++;

            // Log error to sync_log table (New transaction)
            try {
                $log_stmt->execute([
                    'update',
                    $person_id,
                    $emp_code_raw ?? '',
                    $fullname_raw ?? '',
                    'ERROR',
                    null,
                    'Error: ' . $last_error
                ]);
            } catch (Exception $logEx) {
                // Ignore log errors
            }

            // Continue with next record instead of stopping
            continue;
        }
    }

    // Handle Ghost Users: Deactivate users not in sync list (and active)
    $deactivated_count = 0;
    if (!empty($processed_ids)) {
        try {
            // Can't use huge IN clause with thousands of IDs, might hit limits.
            // Better to fetch all person_id from DB locally and diff array (usually faster for < 10k records)
            $all_db_users = $conn_mysql->query("SELECT person_id, fullname, EmpCode FROM users WHERE user_id != '1111' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

            // Convert processed to map for fast lookup
            $processed_map = array_flip($processed_ids);

            $ghost_update_stmt = $conn_mysql->prepare("UPDATE users SET is_active = 0, WorkingStatus = 'Retired', updated_at = NOW() WHERE person_id = ?");

            foreach ($all_db_users as $u) {
                if (!isset($processed_map[$u['person_id']])) {
                    // This user is in DB but NOT in current sync -> Deactivate
                    $ghost_update_stmt->execute([$u['person_id']]);

                    // Log deactivation
                    $log_stmt->execute([
                        'update',
                        $u['person_id'],
                        $u['EmpCode'],
                        $u['fullname'],
                        'SYSTEM_DEACTIVATE',
                        'Active',
                        'Retired (Missing from source)'
                    ]);

                    $deactivated_count++;
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the whole sync
            $error_count++;
            $last_error = "Ghost User cleanup failed: " . $e->getMessage();
        }
    }

    $stmt_sqlsrv->closeCursor();
    $conn_sqlsrv = null;

    // Build summary message
    $summary_html = "<h3>รายงานผลการดึงข้อมูลพนักงานจาก HRIS (SQL Server)</h3>";
    $summary_html .= "<ul>";
    $summary_html .= "<li>เพิ่มพนักงานใหม่: <b>$inserted_count</b> คน</li>";
    $summary_html .= "<li>อัปเดตข้อมูล: <b>$updated_count</b> คน</li>";
    $summary_html .= "<li>ข้อมูลคงเดิม: <b>$unchanged_count</b> คน</li>";
    $summary_html .= "<li>ระงับสิทธิ์ใช้งาน (ออก/ย้าย): <b>$deactivated_count</b> คน</li>";

    $summary_text = "สำเร็จ! ใหม่ $inserted_count, อัปเดต $updated_count, คงเดิม $unchanged_count";
    if ($deactivated_count > 0) {
        $summary_text .= ", ปิดใช้งาน $deactivated_count";
    }

    if ($error_count > 0) {
        $summary_text .= ", ผิดพลาด $error_count";
        $summary_html .= "<li>ผิดพลาด: <b style='color:red;'>$error_count</b> รายการ</li>";
        $summary_html .= "</ul><p style='color:red;'><b>Error details:</b> $last_error</p>";
        sendEmailNotify("แจ้งเตือน: ซิงค์พนักงานเสร็จสิ้น (พบข้อผิดพลาด $error_count รายการ)", $summary_html, $conn_mysql);
    } else {
        $summary_html .= "</ul><p>สถานะ: <b>สำเร็จ 100%</b></p>";
        sendEmailNotify("แจ้งเตือน: ซิงค์พนักงานเสร็จสมบูรณ์", $summary_html, $conn_mysql);
    }

    $send_update([
        'status' => 'finished',
        'total' => $total,
        'done' => $done,
        'inserted_count' => $inserted_count,
        'updated_count' => $updated_count,
        'unchanged_count' => $unchanged_count,
        'error_count' => $error_count,
        'deactivated_count' => $deactivated_count, // Send back explicitly if needed
        'message' => $summary_text
    ]);

    $conn_mysql = null;
}
