<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_url ?? ''); ?>">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #3b82f6;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }

        .content {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }

        .booking-details {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
        }

        .car-details {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }

        .changes-highlight {
            background: #fff3cd;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>🔄 การจองรถของคุณถูกเปลี่ยนแปลง</h2>
        </div>

        <div class="content">

            <p>การจองรถของคุณได้รับการปรับปรุงข้อมูลแล้ว โปรดตรวจสอบรายละเอียดใหม่</p>

            <div class="booking-details">
                <h3>📋 รายละเอียดการจอง</h3>
                <p><strong>รหัสการจอง:</strong> #<?php echo $booking['id']; ?></p>
                <p><strong>ปลายทาง:</strong> <?php echo htmlspecialchars($booking['destination']); ?></p>
                <p><strong>วัตถุประสงค์:</strong> <?php echo htmlspecialchars($booking['purpose']); ?></p>
                <p><strong>เวลาเริ่มต้น:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['start_time'])); ?> น.</p>
                <p><strong>เวลาสิ้นสุด:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['end_time'])); ?> น.</p>
                <p><strong>ระยะเวลา:</strong> <?php
                                                $start = new DateTime($booking['start_time']);
                                                $end = new DateTime($booking['end_time']);
                                                $interval = $start->diff($end);
                                                echo $interval->format('%h ชั่วโมง %i นาที');
                                                ?></p>
            </div>

            <?php if (!empty($booking['brand']) && !empty($booking['model'])): ?>
                <div class="car-details">
                    <h3>🚗 รายละเอียดรถ</h3>
                    <p><strong>ยี่ห้อ:</strong> <?php echo htmlspecialchars($booking['brand']); ?></p>
                    <p><strong>รุ่น:</strong> <?php echo htmlspecialchars($booking['model']); ?></p>
                    <p><strong>ทะเบียน:</strong> <?php echo htmlspecialchars($booking['license_plate']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($booking['fleet_card_id'])): ?>
                <div class="car-details" style="border-left-color: #f59e0b;">
                    <h3>💳 รายละเอียดบัตร Fleet Card</h3>
                    <p><strong>หมายเลขบัตร:</strong> <?php echo htmlspecialchars($booking['fleet_card_number']); ?></p>
                    <?php if (!empty($booking['fleet_amount'])): ?>
                        <p><strong>วงเงินที่อนุมัติ:</strong> <?php echo number_format($booking['fleet_amount'], 2); ?> บาท</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="changes-highlight">
                <h3>⚠️ สิ่งที่เปลี่ยนแปลง</h3>
                <p>การจองของคุณได้รับการปรับปรุงข้อมูลแล้ว กรุณาตรวจสอบรายละเอียดด้านบนอีกครั้ง</p>
                <p><strong>วันที่ปรับปรุง:</strong> <?php echo date('d/m/Y H:i'); ?> น.</p>
            </div>

            <div style="background: #e8f5e8; padding: 15px; margin: 15px 0; border-radius: 5px;">
                <h3>📝 หมายเหตุสำคัญ</h3>
                <ul>
                    <li>กรุณาไปรับรถตามเวลาที่กำหนด</li>
                    <li>ตรวจสอบสภาพรถก่อนรับใช้งาน</li>
                    <li>แจ้งผู้ดูแลรถเมื่อเดินทางกลับ</li>
                </ul>
                <p><strong>หมายเหตุ:</strong> หากต้องการเปลี่ยนแปลงกรุณาแจ้งล่วงหน้าอย่างน้อย 1 วัน</p>
            </div>
        </div>

        <div class="footer">
            <p>INTEQC Car Booking System</p>
            <p>ระบบจองรถอัตโนมัติ</p>
        </div>
    </div>
</body>

</html>