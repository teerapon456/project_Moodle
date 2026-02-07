<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }

        .container {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            border-top: 5px solid #ff9999;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #d32f2f;
        }

        .details {
            margin: 20px 0;
            padding: 15px;
            background: #fff0f0;
            border-radius: 5px;
        }

        .footer {
            font-size: 12px;
            color: #777;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>ได้รับคำขอของคุณแล้ว</h2>
        <p>เรียนคุณ <?= htmlspecialchars($userName) ?>,</p>
        <p>ระบบได้รับคำขอ <strong><?= $type == 'move_in' ? 'ขอเข้าพัก' : ($type == 'move_out' ? 'ขอย้ายออก' : 'ขอย้ายห้อง') ?></strong> ของคุณเรียบร้อยแล้ว</p>

        <div class="details">
            <p>สถานะปัจจุบัน: <strong>รอการตรวจสอบ (Pending)</strong></p>
            <p>เจ้าหน้าที่ผู้รับผิดชอบกำลังดำเนินการตรวจสอบข้อมูลของคุณ</p>
        </div>

        <p>คุณจะได้รับอีเมลแจ้งเตือนอีกครั้งเมื่อคำขอได้รับการอนุมัติ</p>

        <div class="footer">
            <p>My HR Services - Dormitory Management System</p>
        </div>
    </div>
</body>

</html>