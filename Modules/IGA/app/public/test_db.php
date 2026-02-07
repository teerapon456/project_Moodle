<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$status_message = '';
$status_class = '';
$db_details = [];

try {
    // 1. เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูลของคุณ
    // ถ้าไฟล์นี้ทำงานผิดพลาด มันจะโยน Exception หรือ die() ซึ่งจะถูกดักจับโดย catch block
    require_once __DIR__ . '/../includes/db_connect.php';

    // 2. ตรวจสอบว่าตัวแปร $conn ถูกสร้างขึ้นและเป็นการเชื่อมต่อที่ถูกต้องหรือไม่
    if (isset($conn) && $conn instanceof mysqli) {
        $status_message = '✅ Connection Successful!';
        $status_class = 'success';

        // 3. ดึงข้อมูลเพิ่มเติมจากฐานข้อมูลเพื่อยืนยันว่าการเชื่อมต่อสมบูรณ์
        $result = $conn->query("SELECT @@version as version, DATABASE() as db_name;");
        $db_info = $result->fetch_assoc();
        
        $db_details['Status'] = 'Connected';
        $db_details['Database Host'] = $conn->host_info;
        $db_details['Database Name'] = htmlspecialchars($db_info['db_name']);
        $db_details['Server Version'] = htmlspecialchars($db_info['version']);
        $db_details['Character Set'] = $conn->character_set_name();

        // 4. ปิดการเชื่อมต่อ
        $conn->close();

    } else {
        // กรณีนี้ไม่น่าจะเกิดขึ้นถ้า db_connect.php ทำงานถูกต้อง แต่ใส่ไว้เพื่อความปลอดภัย
        throw new RuntimeException('Connection script ran without errors, but the $conn variable is not a valid mysqli object.');
    }

} catch (Throwable $e) {
    // 5. หากเกิดข้อผิดพลาดใดๆ ในระหว่างการเชื่อมต่อ
    $status_message = '❌ Connection Failed!';
    $status_class = 'error';
    $db_details['Status'] = 'Failed';
    $db_details['Error Message'] = htmlspecialchars($e->getMessage());
    $db_details['PHP File'] = htmlspecialchars($e->getFile());
    $db_details['Line Number'] = $e->getLine();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 40px;
            background-color: #f4f7f9;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        h1 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            font-size: 24px;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .success {
            background-color: #e9f7ef;
            color: #2d8654;
            border: 1px solid #bce0c9;
        }
        .error {
            background-color: #fce8e6;
            color: #c9302c;
            border: 1px solid #f7c6c4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
            width: 30%;
        }
        td {
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Connection Test 🧪</h1>

        <div class="status <?php echo htmlspecialchars($status_class); ?>">
            <?php echo htmlspecialchars($status_message); ?>
        </div>

        <?php if (!empty($db_details)): ?>
            <h2>Connection Details</h2>
            <table>
                <?php foreach ($db_details as $key => $value): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($key); ?></th>
                        <td><?php echo $value; // Already escaped in the PHP block ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>