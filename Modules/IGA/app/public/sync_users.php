<?php

/*************************************************
 * sync_users.php — Single file (UI + AJAX + worker)
 * - ใช้ EmpID จาก SQL Server เป็น user_id ใน MySQL
 * - ใช้ person_id เป็นคีย์หลักในการตรวจสอบ
 * - เพิ่มการป้องกัน Admin (user_id = 1)
 * - **แก้ไขให้สามารถรันจาก Command Line (CLI) ได้โดยอัตโนมัติ**
 *************************************************/
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists(\Dotenv\Dotenv::class)) {
        $envPath = dirname(__DIR__);
        try {
            $dotenv = \Dotenv\Dotenv::createImmutable($envPath);
            $dotenv->safeLoad();
        } catch (Throwable $e) {
            // If .env cannot be loaded, continue with defaults
        }
    }
}
set_time_limit(300); // Set to 300 seconds (5 minutes) to prevent timeout
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');
while (function_exists('ob_get_level') && ob_get_level() > 0) {
    @ob_end_flush();
}
date_default_timezone_set('Asia/Bangkok');

/* ===== CONFIG ===== */
// Helper function to clean env values (remove quotes)
function clean_env($key, $default = '')
{
    $value = getenv($key);
    if ($value === false) return $default;
    return trim($value, '"\'');
}

// SQL Server (source) - ค่าตรงๆจาก .env
$SQLSRV_HOST = '172.17.100.26';
$SQLSRV_DB = 'HRMULTI_INTEQC';
$SQLSRV_UID = 'HRIS';
$SQLSRV_PWD = 'Hris@2024';

// MySQL (target) - ค่าตรงๆจาก .env
$MYSQL_HOST = 'iga-db';
$MYSQL_DB = 'iga';
$MYSQL_UID = 'sa';
$MYSQL_PWD = 'iga@2025';

/* ===== DEBUG: แสดงค่า Config ===== */
if (isset($_GET['debug'])) {
    // แก้ไขการโหลด .env
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        if (class_exists(\Dotenv\Dotenv::class)) {
            $envPath = dirname(__DIR__);
            try {
                $dotenv = \Dotenv\Dotenv::createImmutable($envPath);
                $dotenv->safeLoad();
                $dotenvLoaded = true;
            } catch (Throwable $e) {
                $dotenvLoaded = false;
                $dotenvError = $e->getMessage();
            }
        }
    }

?>
    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Debug Configuration</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: #f5f5f5;
                padding: 20px;
            }

            .container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            h1 {
                color: #333;
                border-bottom: 3px solid #3b82f6;
                padding-bottom: 10px;
            }

            .section {
                margin: 20px 0;
            }

            .section h2 {
                color: #3b82f6;
                font-size: 18px;
                margin-bottom: 15px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            table td {
                padding: 10px;
                border-bottom: 1px solid #eee;
            }

            table td:first-child {
                font-weight: bold;
                color: #555;
                width: 200px;
            }

            table td:last-child {
                color: #333;
                font-family: 'Courier New', monospace;
            }

            .empty {
                color: #999;
                font-style: italic;
            }

            .set {
                color: #22c55e;
                font-weight: bold;
            }

            .btn {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 24px;
                background: #3b82f6;
                color: white;
                text-decoration: none;
                border-radius: 6px;
            }

            .btn:hover {
                background: #2563eb;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h1>🔍 Debug Configuration</h1>

            <div class="section">
                <h2>📄 .env File Status</h2>
                <table>
                    <tr>
                        <td>.env Path</td>
                        <td><?php echo __DIR__ . '/../'; ?></td>
                    </tr>
                    <tr>
                        <td>.env Exists</td>
                        <td><?php echo file_exists(__DIR__ . '/../.env') ? '<span class="set">✅ Yes</span>' : '<span class="empty">❌ No</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>Dotenv Loaded</td>
                        <td><?php echo $dotenvLoaded ? '<span class="set">✅ Success</span>' : '<span class="empty">❌ Failed: ' . $dotenvError . '</span>'; ?></td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <h2>📊 SQL Server Configuration</h2>
                <table>
                    <tr>
                        <td>SQLSRV_HOST</td>
                        <td><?php echo $SQLSRV_HOST ?: '<span class="empty">(empty)</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>SQLSRV_DB</td>
                        <td><?php echo $SQLSRV_DB ?: '<span class="empty">(empty)</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>SQLSRV_UID</td>
                        <td><?php echo $SQLSRV_UID ?: '<span class="empty">(empty)</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>SQLSRV_PWD</td>
                        <td><?php echo $SQLSRV_PWD ? '<span class="set">***SET***</span>' : '<span class="empty">(empty)</span>'; ?></td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <h2>🗄️ MySQL Configuration</h2>
                <table>
                    <tr>
                        <td>MYSQL_HOST</td>
                        <td><?php echo $MYSQL_HOST ?: '<span class="empty">(empty)</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>MYSQL_DB</td>
                        <td><?php echo $MYSQL_DB ?: '<span class="empty">(empty)</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>MYSQL_UID</td>
                        <td><?php echo $MYSQL_UID ?: '<span class="empty">(empty)</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>MYSQL_PWD</td>
                        <td><?php echo $MYSQL_PWD ? '<span class="set">***SET***</span>' : '<span class="empty">(empty)</span>'; ?></td>
                    </tr>
                </table>
            </div>

            <a href="?action=start" class="btn">▶️ เริ่ม Sync</a>
            <a href="?" class="btn" style="background: #6b7280;">🏠 กลับหน้าหลัก</a>
        </div>
    </body>

    </html>
<?php
    exit;
}

// CLI debug
if (php_sapi_name() === 'cli') {
    echo "=== DEBUG CONFIG VALUES ===\n";
    echo "SQLSRV_HOST: " . ($SQLSRV_HOST ?: '(empty)') . "\n";
    echo "SQLSRV_DB: " . ($SQLSRV_DB ?: '(empty)') . "\n";
    echo "SQLSRV_UID: " . ($SQLSRV_UID ?: '(empty)') . "\n";
    echo "SQLSRV_PWD: " . ($SQLSRV_PWD ? '***SET***' : '(empty)') . "\n";
    echo "MYSQL_HOST: " . ($MYSQL_HOST ?: '(empty)') . "\n";
    echo "MYSQL_DB: " . ($MYSQL_DB ?: '(empty)') . "\n";
    echo "MYSQL_UID: " . ($MYSQL_UID ?: '(empty)') . "\n";
    echo "MYSQL_PWD: " . ($MYSQL_PWD ? '***SET***' : '(empty)') . "\n";
    echo "===========================\n\n";
}

/* ===== Hash secret (อ่านจาก .env/secret ในจริงจัง) ===== */
$HASH_SECRET = 'CHANGE_ME_TO_RANDOM_LONG_SECRET';

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
function only_digits(?string $s): ?string
{
    if ($s === null) return null;
    $s = preg_replace('/\D+/', '', $s);
    return ($s === '') ? null : $s;
}

/* ===== ROUTER ===== */
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ตรวจสอบว่า action เป็น 'start' หรือรันจาก Command Line (cli)
if ($action === 'start' || php_sapi_name() === 'cli') {
    // ถ้าเป็น CLI ไม่ต้องส่ง headers สำหรับ EventSource
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
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
        'HASH_SECRET' => $HASH_SECRET,
    ]);
    exit;
}

/* ===== DEFAULT: WEB UI ===== */
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8" />
    <title>Sync Users</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
            max-width: 800px;
            margin: 40px auto;
            padding: 0 16px
        }

        button {
            background: #3b82f6;
            color: #fff;
            border: 0;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer
        }

        button[disabled] {
            opacity: .5;
            cursor: not-allowed
        }

        .wrap {
            height: 18px;
            background: #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 8px
        }

        .bar {
            height: 100%;
            width: 0;
            background: #22c55e;
            transition: width .2s
        }

        .meta {
            color: #4b5563;
            font-size: 14px;
            margin-top: 6px
        }

        .row {
            margin: 14px 0
        }

        .results-box {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px
        }
    </style>
</head>

<body>
    <h1>Sync Users (SQL Server ➜ MySQL)</h1>
    <div class="row">
        <button id="btn">เริ่ม Sync</button>
    </div>
    <div class="row meta">สถานะ: <b id="st">idle</b></div>
    <div class="wrap">
        <div id="bar" class="bar"></div>
    </div>
    <div class="row meta"><span id="pct">0%</span> — <span id="cnt">0/0</span></div>
    <div id="msg" class="meta"></div>

    <div id="results" class="results-box" style="display:none">
        <h3>ผลลัพธ์:</h3>
        <div id="results-insert">เพิ่มข้อมูลใหม่: 0 รายการ</div>
        <div id="results-update">อัปเดตข้อมูล: 0 รายการ</div>
    </div>

    <script>
        let timer = null;

        const el = {
            btn: document.getElementById('btn'),
            st: document.getElementById('st'),
            bar: document.getElementById('bar'),
            pct: document.getElementById('pct'),
            cnt: document.getElementById('cnt'),
            msg: document.getElementById('msg'),
            resultsBox: document.getElementById('results'),
            resultsInsert: document.getElementById('results-insert'),
            resultsUpdate: document.getElementById('results-update'),
        };

        el.btn.addEventListener('click', () => {
            el.btn.disabled = true;
            el.st.textContent = 'กำลังเริ่ม...';
            el.msg.textContent = '';
            el.bar.style.width = '0%';
            el.pct.textContent = '0%';
            el.cnt.textContent = '0/0';
            el.resultsBox.style.display = 'none';
            el.resultsInsert.textContent = 'เพิ่มข้อมูลใหม่: 0 รายการ';
            el.resultsUpdate.textContent = 'อัปเดตข้อมูล: 0 รายการ';

            const source = new EventSource('?action=start');

            source.onmessage = function(event) {
                try {
                    const d = JSON.parse(event.data);
                    const total = d.total || 0;
                    const done = d.done || 0;
                    const p = total > 0 ? Math.floor(done * 100 / total) : (d.status === 'running' ? 0 : 100);

                    el.bar.style.width = p + '%';
                    el.pct.textContent = p + '%';
                    el.cnt.textContent = done + '/' + total;
                    el.st.textContent = d.status || '';
                    el.msg.textContent = d.message || '';

                    if (d.status === 'finished' || d.status === 'error') {
                        source.close();
                        el.btn.disabled = false;
                        if (d.status === 'finished') {
                            el.resultsBox.style.display = 'block';
                            el.resultsInsert.textContent = `เพิ่มข้อมูลใหม่: ${d.inserted_count} รายการ`;
                            el.resultsUpdate.textContent = `อัปเดตข้อมูล: ${d.updated_count} รายการ`;
                        }
                    }
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    source.close();
                    el.btn.disabled = false;
                }
            };

            source.onerror = function() {
                console.error('EventSource failed.');
                source.close();
                el.st.textContent = 'error';
                el.msg.textContent = 'การเชื่อมต่อมีปัญหา';
                el.btn.disabled = false;
            };
        });
    </script>
</body>

</html>
<?php
/*************** WORKER ****************/
function run_worker(array $config)
{
    extract($config);

    $send_update = function (array $data) {
        // ในโหมด CLI จะไม่แสดงผลลัพธ์แบบ EventSource
        if (php_sapi_name() === 'cli') {
            echo "Status: " . ($data['status'] ?? 'unknown') . " | Message: " . ($data['message'] ?? '') . "\n";
            return;
        }
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
    };

    // Connect SQL Server (PDO)
    $conn_sqlsrv = null;
    try {
        $conn_sqlsrv = new PDO("sqlsrv:Server=$SQLSRV_HOST;Database=$SQLSRV_DB", $SQLSRV_UID, $SQLSRV_PWD);
        $conn_sqlsrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $send_update(['status' => 'error', 'total' => 0, 'done' => 0, 'message' => 'เชื่อม SQL Server ไม่ได้: ' . $e->getMessage()]);
        return;
    }

    // T-SQL query for counting (separate step)
    $tsql_count = <<<TSQL
    SELECT COUNT(*) AS TotalRows
    FROM uv_employee AS emp
    LEFT JOIN emAddress ed ON emp.EmpID = ed.RelatedID AND ed.AddressMode='info'
    WHERE LEN(emp.EmpCode)=10
    AND emp.EmpCode NOT LIKE '%[^0-9]%'
    AND emp.EmpCode NOT LIKE '193%'
    AND emp.OrgCode NOT IN ('INTEQC MART','PRIMATECH','LIVESTOCK')
    AND emp.EmpType='Monthly';
TSQL;

    $stmt_count = $conn_sqlsrv->query($tsql_count);
    $total = (int)($stmt_count->fetch(PDO::FETCH_ASSOC)['TotalRows'] ?? 0);
    $stmt_count->closeCursor();

    if ($total === 0) {
        $send_update(['status' => 'finished', 'total' => 0, 'done' => 0, 'message' => 'ไม่พบข้อมูลที่จะอัปเดต']);
        return;
    }

    $send_update(['status' => 'running', 'total' => $total, 'done' => 0, 'message' => "พบข้อมูล $total รายการ กำลังเตรียมการอัปเดต..."]);

    // T-SQL query for data fetching with CASE WHEN for OrgID
    $tsql_data = <<<TSQL
WITH EmployeeData AS (
      SELECT
           emp.PersonID, emp.EmpID, emp.FirstName, emp.LastName, emp.FullName,
           emp.IdentityCard, emp.Gender, emp.BirthDate, emp.Nationality,
           emp.EmpCode, emp.EmpLevelCode, emp.EmpType, emp.WorkingStatus,
           emp.OrgCode, emp.OrgUnitTypeName, emp.OrgUnitName,
           emp.OrgUnitCode AS OriginalOrgUnitCode, emp.OrgUnitCode,
           emp.FirstNameEng, emp.LastNameEng, ed.Email1,
           emp.PositionName, emp.WorkingDate
      FROM uv_employee AS emp
      LEFT JOIN emAddress ed ON emp.EmpID = ed.RelatedID AND ed.AddressMode='info'
      WHERE LEN(emp.EmpCode)=10
      AND emp.EmpCode NOT LIKE '%[^0-9]%'
      AND emp.EmpCode NOT LIKE '193%'
      AND emp.OrgCode NOT IN ('INTEQC MART','PRIMATECH','LIVESTOCK')
      AND emp.EmpType='Monthly'
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
      p.EmpLevelCode,
      p.EmpType, p.WorkingStatus,
      p.OrgCode,
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
      p.FullName AS full_name,
      CAST(4 AS INT) AS role_id,
      CONVERT(VARCHAR(19), GETDATE(), 120) AS created_at,
      CONVERT(VARCHAR(19), GETDATE(), 120) AS updated_at,
      CASE WHEN p.WorkingStatus='Working' THEN 1 ELSE 0 END AS is_active,
      COALESCE(LookupOrg.OrgUnitName, p.TruncatedOrgUnitCode) AS TruncatedOrgUnitName,
      p.Part1 AS Level1Code, COALESCE(LookupPart1.OrgUnitName, p.Part1) AS Level1Name,
      p.Part2 AS Level2Code, COALESCE(LookupPart2.OrgUnitName, p.Part2) AS Level2Name,
      p.Part3 AS Level3Code, COALESCE(LookupPart3.OrgUnitName, p.Part3) AS Level3Name,
      p.Part4 AS Level4Code, COALESCE(LookupPart4.OrgUnitName, p.Part4) AS Level4Name,
      p.Part5 AS Level5Code, COALESCE(LookupPart5.OrgUnitName, p.Part5) AS Level5Name
FROM PartData AS p
LEFT JOIN emOrgUnit AS LookupOrg
      ON p.TruncatedOrgUnitCode = LookupOrg.OrgUnitCode AND LookupOrg.IsDeleted=0 AND LookupOrg.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart1 ON p.Part1 = LookupPart1.OrgUnitCode AND LookupPart1.IsDeleted=0 AND LookupPart1.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart2 ON p.Part2 = LookupPart2.OrgUnitCode AND LookupPart2.IsDeleted=0 AND LookupPart2.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart3 ON p.Part3 = LookupPart3.OrgUnitCode AND LookupPart3.IsDeleted=0 AND LookupPart3.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart4 ON p.Part4 = LookupPart4.OrgUnitCode AND LookupPart4.IsDeleted=0 AND LookupPart4.IsInactive=0
LEFT JOIN emOrgUnit AS LookupPart5 ON p.Part5 = LookupPart5.OrgUnitCode AND LookupPart5.IsDeleted=0 AND LookupPart5.IsInactive=0;
TSQL;

    $stmt_sqlsrv = $conn_sqlsrv->query($tsql_data);
    if (!$stmt_sqlsrv) {
        $send_update(['status' => 'error', 'total' => 0, 'done' => 0, 'message' => 'คิวรี SQL Server ผิดพลาด: ' . $conn_sqlsrv->errorInfo()[2]]);
        return;
    }

    // Connect MySQL (PDO)
    $conn_mysql = null;
    try {
        $conn_mysql = new PDO("mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8mb4;port=$MYSQL_PORT", $MYSQL_UID, $MYSQL_PWD);
        $conn_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $send_update(['status' => 'error', 'total' => 0, 'done' => 0, 'message' => 'เชื่อม MySQL ไม่ได้: ' . $e->getMessage()]);
        return;
    }

    // Prepare separate UPDATE and INSERT statements
    // We will now use person_id to check for existence
    $checkSql = "SELECT user_id FROM `users` WHERE `person_id` = ?";
    $updSql = "
    UPDATE `users` SET
      email = ?, full_name = ?, is_active = ?, updated_at = ?,
      EmpCode = ?, PersonnelEmail = ?, IdentityCard = ?, Gender = ?, BirthDate = ?,
      Nationality = ?, emplevel_id = ?, EmpType = ?, WorkingStatus = ?, OrgID = ?,
      OrgUnitTypeName = ?, OrgUnitName = ?, OriginalOrgUnitCode = ?, OrgUnitCode = ?,
      TruncatedOrgUnitCode = ?, TruncatedOrgUnitName = ?, Level1Code = ?, Level1Name = ?,
      Level2Code = ?, Level2Name = ?, Level3Code = ?, Level3Name = ?, Level4Code = ?,
      Level4Name = ?, Level5Code = ?, Level5Name = ?
    WHERE person_id = ? AND user_id != 1
    ";

    $insSql = "
    INSERT INTO `users` (
      person_id, user_id, username, password_hash, email, full_name,
      role_id, created_at, updated_at, is_active,
      EmpCode, PersonnelEmail, IdentityCard, Gender, BirthDate, Nationality,
      emplevel_id, EmpType, WorkingStatus, OrgID, OrgUnitTypeName, OrgUnitName,
      OriginalOrgUnitCode, OrgUnitCode, TruncatedOrgUnitCode, TruncatedOrgUnitName,
      Level1Code, Level1Name, Level2Code, Level2Name, Level3Code, Level3Name,
      Level4Code, Level4Name, Level5Code, Level5Name
    ) VALUES (
      ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?,
      ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?,
      ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?
    )
    ";

    $check_stmt = $conn_mysql->prepare($checkSql);
    $upd_stmt = $conn_mysql->prepare($updSql);
    $ins_stmt = $conn_mysql->prepare($insSql);

    if (!$check_stmt || !$upd_stmt || !$ins_stmt) {
        $send_update(['status' => 'error', 'total' => 0, 'done' => 0, 'message' => 'เตรียมคำสั่ง MySQL ไม่ได้: ' . $conn_mysql->errorInfo()[2]]);
        return;
    }

    $done = 0;
    $inserted_count = 0;
    $updated_count = 0;
    $send_update(['status' => 'running', 'total' => $total, 'done' => 0, 'message' => 'เริ่มอัปเดต...']);

    while ($r = $stmt_sqlsrv->fetch(PDO::FETCH_ASSOC)) {
        // Map + hash sensitive fields using PHP functions
        $person_id = $r['PersonID'];
        $user_id = $r['EmpID']; // Use EmpID from SQL Server directly as user_id
        $username = $r['username'];
        // สร้างรหัสเริ่มต้นจากวันเกิด (MMDD) รองรับ 'YYYY-MM-DD' / 'YYYY-MM_DD' หรือ DateTime
        $defaultPwdPlain = '0101'; // fallback ถ้าไม่มีวันเกิด
        $birthRaw = $r['BirthDate'] ?? null;

        if ($birthRaw instanceof DateTime) {
            $defaultPwdPlain = $birthRaw->format('md'); // MMDD
        } else {
            $birthStr = trim((string)$birthRaw);
            if ($birthStr !== '') {
                // เผื่อมาจากรูปแบบ YYYY-MM_DD ให้แปลง '_' เป็น '-' ก่อน
                $birthStrNorm = str_replace('_', '-', $birthStr);

                // พยายามดึง MMDD ด้วย regex ก่อน (แม่นสุดสำหรับรูปแบบมาตรฐาน)
                if (preg_match('/^\d{4}-(\d{2})-(\d{2})$/', $birthStrNorm, $m)) {
                    $defaultPwdPlain = $m[1] . $m[2]; // MMDD
                } else {
                    // เผื่อรูปแบบอื่น ใช้ strtotime ช่วยตีความ
                    $ts = strtotime($birthStrNorm);
                    if ($ts !== false) {
                        $defaultPwdPlain = date('md', $ts);
                    }
                }
            }
        }

        // เข้ารหัสรหัสผ่าน (ใช้ bcrypt ตามเดิม)
        $pwd_hash = password_hash($defaultPwdPlain, PASSWORD_BCRYPT);

        $email = norm_email($r['PersonnelEmail']) ?? '';
        $full_name = $r['full_name'];
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

        // Convert EmpLevelCode from string (L00, L01, etc.) to integer
        $emp_level_str = $r['EmpLevelCode'];
        $mapped_emplevel_id = 0;
        switch ($emp_level_str) {
            case 'L00':
                $mapped_emplevel_id = 1;
                break;
            case 'L01':
                $mapped_emplevel_id = 2;
                break;
            case 'L02':
                $mapped_emplevel_id = 3;
                break;
            case 'L03':
                $mapped_emplevel_id = 4;
                break;
            case 'L04':
                $mapped_emplevel_id = 5;
                break;
            case 'L05':
                $mapped_emplevel_id = 6;
                break;
            case 'L06':
                $mapped_emplevel_id = 7;
                break;
            case 'L07':
                $mapped_emplevel_id = 8;
                break;
            case 'L08':
                $mapped_emplevel_id = 9;
                break;
            case 'L09':
                $mapped_emplevel_id = 10;
                break;
            case 'L10':
                $mapped_emplevel_id = 11;
                break;
            case 'L11':
                $mapped_emplevel_id = 12;
                break;
            case 'L12':
                $mapped_emplevel_id = 13;
                break;
            case 'L13':
                $mapped_emplevel_id = 14;
                break;
            case 'L14':
                $mapped_emplevel_id = 15;
                break;
            case 'L15':
                $mapped_emplevel_id = 16;
                break;
            default:
                $mapped_emplevel_id = NULL;
                break;
        }

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

        try {
            // First, check if the record with this person_id already exists in MySQL
            $check_stmt->execute([$person_id]);
            $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_user) {
                // If the record exists, check if it's the Admin account (user_id = 1)
                $existing_user_id = $existing_user['user_id'];
                if ($existing_user_id == 1) {
                    $done++;
                    $send_update([
                        'status' => 'running',
                        'status_detail' => 'ข้ามข้อมูล Admin',
                        'total' => $total,
                        'done' => $done,
                        'inserted_count' => $inserted_count,
                        'updated_count' => $updated_count,
                        'message' => "กำลังดำเนินการ: ข้ามข้อมูล Admin"
                    ]);
                    continue; // Skip to the next row
                }

                // If it's not the Admin account, UPDATE it
                $upd_stmt->execute([
                    $email,
                    $full_name,
                    $is_active,
                    $updated_at,
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
                    $person_id // WHERE condition
                ]);
                $updated_count++;
                $status_detail = 'อัปเดตข้อมูล';
            } else {
                // If the record does not exist, INSERT it
                $ins_stmt->execute([
                    $person_id,
                    $user_id,
                    $username,
                    $pwd_hash,
                    $email,
                    $full_name,
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
                    $lvl5_name
                ]);
                $inserted_count++;
                $status_detail = 'เพิ่มข้อมูลใหม่';
            }

            $done++;
            $send_update([
                'status' => 'running',
                'status_detail' => $status_detail,
                'total' => $total,
                'done' => $done,
                'inserted_count' => $inserted_count,
                'updated_count' => $updated_count,
                'message' => "กำลังดำเนินการ: $status_detail สำหรับ PersonID: $person_id"
            ]);
        } catch (PDOException $e) {
            $error_message = 'ข้อผิดพลาด: ' . $e->getMessage() . '. ที่แถว PersonID: ' . $person_id . '. ข้อมูลดิบ: ' . json_encode($r, JSON_UNESCAPED_UNICODE);
            $send_update([
                'status' => 'error',
                'total' => $total,
                'done' => $done,
                'message' => $error_message
            ]);
            // Stop the script on the first error to allow for debugging
            exit;
        }
    }

    $stmt_sqlsrv->closeCursor();
    $conn_sqlsrv = null;
    $conn_mysql = null;

    $send_update([
        'status' => 'finished',
        'total' => $total,
        'done' => $done,
        'inserted_count' => $inserted_count,
        'updated_count' => $updated_count,
        'message' => "สำเร็จ! เพิ่มข้อมูลใหม่ $inserted_count รายการ, อัปเดต $updated_count รายการ"
    ]);
}
?>