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
            background-color: #ffffff;
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
        <?php
        $typeLabels = [
            'move_in' => 'ขอเข้าพัก',
            'move_out' => 'ขอย้ายออก',
            'change_room' => 'ขอย้ายห้อง',
            'add_relative' => 'ขอเพิ่มญาติ',
            'remove_relative' => 'ขอนำญาติออก'
        ];
        $typeLabel = $typeLabels[$type] ?? 'หอพัก';
        ?>
        <p>คำขอ <strong><?= htmlspecialchars($typeLabel) ?></strong> ของคุณได้รับการอนุมัติแล้ว</p>

        <div class="details">
            <p><strong>รายละเอียดการเข้าพัก:</strong></p>
            <ul>
                <?php if (!empty($building)): ?>
                    <li><strong>อาคาร:</strong> <?= htmlspecialchars($building) ?></li>
                <?php endif; ?>
                <?php if (!empty($floor)): ?>
                    <li><strong>ชั้น:</strong> <?= htmlspecialchars($floor) ?></li>
                <?php endif; ?>
                <?php if (!empty($roomNumber)): ?>
                    <li><strong>เลขห้อง:</strong> <?= htmlspecialchars($roomNumber) ?></li>
                <?php endif; ?>
            </ul>
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