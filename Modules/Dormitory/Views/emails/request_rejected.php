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
            border-top: 5px solid #f44336;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #c62828;
        }

        .details {
            margin: 20px 0;
            padding: 15px;
            background: #ffebee;
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
        <h2>คำขอของคุณถูกปฏิเสธ</h2>
        <p>เรียนคุณ <?= htmlspecialchars($userName) ?>,</p>
        <p>คำขอ <strong><?= $type == 'move_in' ? 'ขอเข้าพัก' : ($type == 'move_out' ? 'ขอย้ายออก' : 'ขอย้ายห้อง') ?></strong> ของคุณไม่ผ่านการอนุมัติ</p>

        <div class="details">
            <p><strong>เหตุผล:</strong></p>
            <p><?= htmlspecialchars($reason) ?></p>
        </div>

        <p>หากมีข้อสงสัย กรุณาติดต่อฝ่ายบุคคลหรือผู้ดูแลหอพัก</p>

        <div class="footer">
            <p>My HR Services - Dormitory Management System</p>
        </div>
    </div>
</body>

</html>