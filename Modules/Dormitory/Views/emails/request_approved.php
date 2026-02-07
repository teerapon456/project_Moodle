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
            border-top: 5px solid #4CAF50;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2E7D32;
        }

        .details {
            margin: 20px 0;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 5px;
        }

        .note {
            color: #555;
            font-style: italic;
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
        <h2>คำขอของคุณได้รับการอนุมัติ</h2>
        <p>เรียนคุณ <?= htmlspecialchars($userName) ?>,</p>
        <p>คำขอ <strong><?= $type == 'move_in' ? 'ขอเข้าพัก' : ($type == 'move_out' ? 'ขอย้ายออก' : 'ขอย้ายห้อง') ?></strong> ของคุณได้รับการอนุมัติแล้ว</p>

        <div class="details">
            <p><strong>วันนัดรับกุญแจ/ดำเนินการ:</strong> <?= date('d/m/Y H:i', strtotime($keyDate)) ?></p>
            <?php if (!empty($remark)): ?>
                <p><strong>หมายเหตุจากเจ้าหน้าที่:</strong> <?= htmlspecialchars($remark) ?></p>
            <?php endif; ?>
        </div>

        <p>กรุณาติดต่อสำนักงานหอพักตามวันและเวลาดังกล่าว</p>

        <div class="footer">
            <p>My HR Services - Dormitory Management System</p>
        </div>
    </div>
</body>

</html>