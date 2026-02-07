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
            background: #3498db;
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
            border-left: 4px solid #3498db;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 12px;
        }

        .status-badge {
            background: #f1c40f;
            color: #333;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>✓ ผู้บังคับบัญชาอนุมัติแล้ว</h2>
        </div>

        <div class="content">
            <p>การจองรถของคุณได้รับการอนุมัติจากผู้บังคับบัญชาแล้ว</p>
            <p>สายงานพัฒนาบุคลลากรและวัฒนธรรมองค์กรอินเทคค์ : IPCD ได้รับเรื่องการจองใช้รถเรียบร้อยแล้ว ขณะนี้อยู่ระหว่างการดำเนินการ</p>

            <div class="booking-details">
                <h3>📋 รายละเอียดการจอง</h3>
                <p><strong>ปลายทาง:</strong> <?php echo htmlspecialchars($booking['destination']); ?></p>
                <p><strong>วัตถุประสงค์:</strong> <?php echo htmlspecialchars($booking['purpose']); ?></p>
                <p><strong>เวลาเริ่มต้น:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['start_time'])); ?> น.</p>
                <p><strong>เวลาสิ้นสุด:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['end_time'])); ?> น.</p>
            </div>

            <div class="status-box" style="text-align: center; margin: 20px 0;">
                <p>สถานะปัจจุบัน:</p>
                <span class="status-badge">⏳ รอสายงาน IPCD อนุมัติ</span>
            </div>

            <p>คุณจะได้รับอีเมลแจ้งเตือนอีกครั้งเมื่อสายงาน IPCD ดำเนินการอนุมัติและจัดสรรรถให้เรียบร้อยแล้ว</p>
        </div>

        <div class="footer">
            <p>INTEQC Car Booking System</p>
            <p>ระบบจองรถอัตโนมัติ</p>
        </div>
    </div>
</body>

</html>
