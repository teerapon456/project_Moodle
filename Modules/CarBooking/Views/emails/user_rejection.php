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
            background: #e74c3c;
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
            border-left: 4px solid #e74c3c;
        }

        .reason-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
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
            <h2>❌ การจองรถไม่ผ่านการอนุมัติ</h2>
        </div>

        <div class="content">

            <?php
            $rejectorName = 'หัวหน้างาน';
            if (isset($rejectedBy) && $rejectedBy === 'manager') {
                $rejectorName = 'สายงาน IPCD';
            }
            ?>
            <p>ขออภัยค่ะ/ครับ การจองรถของคุณไม่ผ่านการอนุมัติโดย<?php echo $rejectorName; ?></p>

            <div class="booking-details">
                <p><strong>ปลายทาง:</strong> <?php echo htmlspecialchars($booking['destination']); ?></p>
                <p><strong>วัตถุประสงค์:</strong> <?php echo htmlspecialchars($booking['purpose']); ?></p>
                <p><strong>เวลาที่ขอจอง:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['start_time'])); ?> - <?php echo date('d/m/Y H:i', strtotime($booking['end_time'])); ?> น.</p>
            </div>

            <div class="reason-box">
                <p><strong>เหตุผลที่ไม่ผ่านการอนุมัติ:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($reason)); ?></p>
            </div>

            <p>หากมีข้อสงสัย กรุณาติดต่อผู้บังคับบัญชาของคุณโดยตรง</p>
        </div>

        <div class="footer">
            <p>INTEQC Car Booking System</p>
            <p>ระบบจองรถอัตโนมัติ</p>
        </div>
    </div>
</body>

</html>
