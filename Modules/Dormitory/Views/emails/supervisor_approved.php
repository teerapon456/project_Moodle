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
            border-top: 5px solid #10b981;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #059669;
        }

        .details {
            margin: 20px 0;
            padding: 15px;
            background: #ecfdf5;
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
        <h2>หัวหน้างานอนุมัติคำขอของคุณแล้ว</h2>
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
        <p>คำขอ <strong><?= htmlspecialchars($typeLabel) ?></strong> ของคุณได้รับการอนุมัติจากหัวหน้างานแล้ว</p>

        <div class="details">
            <p>สถานะปัจจุบัน: <strong>รอผู้ดูแลหอพักดำเนินการ (Pending Manager)</strong></p>
            <p>คำขอของคุณอยู่ระหว่างการพิจารณาจากฝ่าย IPCD เพื่อจัดสรรห้องพักให้คุณ</p>
        </div>

        <p>คุณจะได้รับอีเมลแจ้งเตือนอีกครั้งเมื่อคำขอได้รับการดำเนินการเสร็จสิ้น</p>

        <div class="footer">
            <p>My HR Services - Dormitory Management System</p>
        </div>
    </div>
</body>

</html>
