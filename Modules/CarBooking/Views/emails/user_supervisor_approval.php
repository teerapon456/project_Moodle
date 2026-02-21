<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้บังคับบัญชาอนุมัติแล้ว</title>
    <style>
        body,
        table,
        td,
        p,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0;
            padding: 0;
            font-family: 'Sarabun', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f7f6;
            color: #333333;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        table {
            border-spacing: 0;
            width: 100%;
            border-collapse: collapse;
        }

        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f7f6;
            padding-bottom: 40px;
        }

        .webkit {
            max-width: 600px;
            background-color: #ffffff;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }

        .header {
            background-color: #3b82f6;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            padding: 30px 20px;
            text-align: center;
        }

        .header h2 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .content {
            padding: 30px;
        }

        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #4b5563;
        }

        .details-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            border-radius: 0 6px 6px 0;
            padding: 20px;
            margin-bottom: 25px;
        }

        .box-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #1e293b;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding-bottom: 8px;
        }

        .detail-row {
            margin-bottom: 10px;
            font-size: 15px;
        }

        .detail-label {
            font-weight: 600;
            color: #64748b;
            width: 120px;
            display: inline-block;
            vertical-align: top;
        }

        .detail-value {
            color: #1e293b;
            font-weight: 500;
            display: inline-block;
            width: calc(100% - 125px);
        }

        .status-container {
            background-color: #f8fafc;
            padding: 25px 20px;
            border-radius: 6px;
            text-align: center;
            margin: 25px 0;
            border: 1px dashed #cbd5e1;
        }

        .status-badge {
            background-color: #fef08a;
            color: #854d0e;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 15px;
            font-weight: 600;
            display: inline-block;
            border: 1px solid #fde047;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .info-text {
            font-size: 14px;
            color: #64748b;
            text-align: center;
            margin-top: 15px;
        }

        .footer {
            background-color: #f1f5f9;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }

        @media screen and (max-width: 600px) {
            .content {
                padding: 20px;
            }

            .detail-label,
            .detail-value {
                display: block;
                width: 100%;
            }

            .detail-label {
                margin-bottom: 2px;
            }

            .detail-row {
                margin-bottom: 14px;
            }

            .webkit {
                margin-top: 15px;
                border-radius: 0;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="webkit">
            <div class="header">
                <h2>✓ ผู้บังคับบัญชาอนุมัติแล้ว</h2>
            </div>

            <div class="content">
                <p class="greeting">สวัสดี,</p>
                <p class="greeting" style="margin-top: -10px;">การจองรถยนต์ส่วนกลางของคุณได้รับการตรวจสอบและ<strong>ผ่านการอนุมัติเบื้องต้นจากผู้บังคับบัญชาเรียบร้อยแล้ว</strong> ขณะนี้ศูนย์รับเรื่องยานพาหนะ (สายงาน IPCD) ได้รับข้อมูลดังกล่าวแล้ว</p>

                <!-- Booking Details -->
                <div class="details-box">
                    <div class="box-title" style="color: #1d4ed8;">📋 สรุปรายการเดินทางของคุณ</div>
                    <div class="detail-row">
                        <span class="detail-label">ปลายทาง:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['destination']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">วัตถุประสงค์:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['purpose']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">เวลาประสงค์ไป:</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($booking['start_time'])); ?> น.</span>
                    </div>
                    <div class="detail-row" style="margin-bottom: 0;">
                        <span class="detail-label">เวลาประสงค์กลับ:</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($booking['end_time'])); ?> น.</span>
                    </div>
                </div>

                <div class="status-container">
                    <p style="color: #64748b; font-size: 14px; margin-bottom: 12px; font-weight: 500;">สถานะดำเนินการปัจจุบัน</p>
                    <div class="status-badge">⏳ รอสายงาน IPCD จัดสรรรถ</div>
                </div>

                <p class="info-text">
                    กรุณารอการติดต่อกลับ หรือคุณจะได้รับอีเมลแจ้งเตือนอีกครั้งอัตโนมัติ<br>เมื่อสายงาน IPCD ดำเนินการอนุมัติขั้นสุดท้ายและจัดสรรรถให้เสร็จสมบูรณ์
                </p>
            </div>

            <div class="footer">
                <p><strong>INTEQC Group</strong></p>
                <p>ระบบบริหารจัดการรถยนต์ (Car Booking System)</p>
                <p style="margin-top: 10px; font-size: 11px;">อีเมลฉบับนี้เป็นการแจ้งเตือนอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            </div>
        </div>
    </div>
</body>

</html>