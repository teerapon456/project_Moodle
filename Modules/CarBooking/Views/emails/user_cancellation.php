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
            background: #ef4444;
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
            border-left: 4px solid #ef4444;
        }

        .reason-box {
            background: #fee;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #ef4444;
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
            <h2>❌ การจองรถถูกยกเลิก</h2>
        </div>

        <div class="content">

            <p>การจองรถของคุณได้ถูกยกเลิกเรียบร้อยแล้ว</p>

            <div class="booking-details">
                <h3>📋 รายละเอียดการจอง</h3>
                <p><strong>รหัสการจอง:</strong> #<?php echo $booking['id']; ?></p>
                <p><strong>ปลายทาง:</strong> <?php echo htmlspecialchars($booking['destination']); ?></p>
                <p><strong>วัตถุประสงค์:</strong> <?php echo htmlspecialchars($booking['purpose']); ?></p>
                <p><strong>เวลาเริ่มต้น:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['start_time'])); ?> น.</p>
                <p><strong>เวลาสิ้นสุด:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['end_time'])); ?> น.</p>
                <?php if (!empty($booking['brand']) && !empty($booking['model'])): ?>
                    <p><strong>รถที่จอง:</strong> <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model'] . ' (' . $booking['license_plate'] . ')'); ?></p>
                <?php endif; ?>
            </div>

            <div class="reason-box">
                <h3>📝 เหตุผลการยกเลิก</h3>
                <p style="color: #ef4444; font-weight: bold;"><?php echo htmlspecialchars($reason ?: 'ไม่ระบุ'); ?></p>
            </div>

            <div style="background: #e8f5e8; padding: 15px; margin: 15px 0; border-radius: 5px;">
                <h3>💡 การจองใหม่</h3>
                <p>หากต้องการจองรถสามารถเข้าสู่ระบบและทำการจองใหม่ได้ทันที</p>
                <p><strong>หมายเหตุ:</strong> กรุณาตรวจสอบความพร้อมของรถก่อนทำการจอง</p>
            </div>

            <p>หากมีข้อสงสัยหรือต้องการสอบถามข้อมูลเพิ่มเติม กรุณาติดต่อผู้ดูแลระบบหรือโทร 1064</p>
        </div>

        <div class="footer">
            <p>INTEQC Car Booking System</p>
            <p>ระบบจองรถอัตโนมัติ</p>
        </div>
    </div>
</body>

</html>
