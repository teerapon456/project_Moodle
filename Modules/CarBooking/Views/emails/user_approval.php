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
            background: #27ae60;
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
            border-left: 4px solid #27ae60;
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
            <h2>✓ การจองรถได้รับการอนุมัติ</h2>
        </div>

        <div class="content">

            <p>การจองรถของคุณได้รับการอนุมัติแล้ว</p>

            <div class="booking-details">
                <h3>📋 รายละเอียดการจอง</h3>
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

            <?php if (!empty($car)): ?>
                <div class="car-details" style="background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #3498db;">
                    <h3>🚗 รายละเอียดรถที่อนุมัติ</h3>
                    <p><strong>ยี่ห้อ:</strong> <?php echo htmlspecialchars($car['brand'] ?? ''); ?></p>
                    <p><strong>รุ่น:</strong> <?php echo htmlspecialchars($car['model'] ?? ''); ?></p>
                    <p><strong>ทะเบียน:</strong> <?php echo htmlspecialchars($car['license_plate'] ?? ''); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($fleetCard)): ?>
                <div class="fleetcard-details" style="background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #9b59b6;">
                    <h3>💳 รายละเอียด Fleet Card ที่อนุมัติ</h3>
                    <p><strong>หมายเลขบัตร:</strong> <?php echo htmlspecialchars($fleetCard['card_number']); ?></p>
                    <?php if (isset($fleetCard['approved_amount'])): ?>
                        <p><strong>วงเงินที่อนุมัติ:</strong> <?php echo number_format((float)$fleetCard['approved_amount'], 2); ?> บาท</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="approval-details" style="background: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107;">
                <h3>✅ ข้อมูลการอนุมัติ</h3>
                <p><strong>ผู้อนุมัติ:</strong> <?php echo htmlspecialchars($booking['manager_name'] ?? 'HR'); ?></p>
                <p><strong>วันที่อนุมัติ:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['manager_approved_at'])); ?> น.</p>
                <p><strong>สถานะ:</strong> <span style="color: #27ae60; font-weight: bold;">อนุมัติแล้ว ✓</span></p>
            </div>

            <div class="instructions" style="background: #e8f5e8; padding: 15px; margin: 15px 0; border-radius: 5px;">
                <h3>📝 ขั้นตอนถัดไป</h3>
                <ol>
                    <li>กรุณาไปรับรถตามเวลาที่กำหนด</li>
                    <li>ตรวจสอบสภาพรถก่อนรับใช้งาน</li>
                    <li>แจ้งผู้ดูแลรถเมื่อเดินทางกลับ</li>
                </ol>
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