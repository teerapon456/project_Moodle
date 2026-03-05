<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองรถถูกยกเลิก</title>
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
            background-color: #ef4444;
            background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);
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
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            border-radius: 0 6px 6px 0;
            padding: 20px;
            margin-bottom: 25px;
        }

        .reason-box {
            background-color: #f8fafc;
            border-left: 4px solid #94a3b8;
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

        .info-box {
            background-color: #f0fdf4;
            border-radius: 6px;
            padding: 20px;
            margin-top: 20px;
        }

        .info-box p {
            color: #166534;
            font-size: 15px;
            font-weight: 500;
        }

        .info-box p.note {
            color: #15803d;
            font-size: 14px;
            margin-top: 8px;
            font-weight: normal;
        }

        .contact-notice {
            font-size: 14px;
            text-align: center;
            color: #64748b;
            margin-top: 20px;
            padding: 15px;
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
                <h2>❌ การจองรถถูกยกเลิก</h2>
            </div>

            <div class="content">
                <p class="greeting" style="margin-top: -10px;">การจองรถหมายเลข <strong>#<?php echo $booking['id']; ?></strong> ของคุณได้ถูกยกเลิกเรียบร้อยแล้ว ดังรายละเอียดต่อไปนี้:</p>

                <!-- Booking Details -->
                <div class="details-box">
                    <div class="box-title" style="color: #b91c1c;">📋 รายละเอียดการจองที่ถูกยกเลิก</div>
                    <div class="detail-row">
                        <span class="detail-label">รหัสการจอง:</span>
                        <span class="detail-value">#<?php echo $booking['id']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">ปลายทาง:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['destination']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">วัตถุประสงค์:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['purpose']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">เวลาเดินทาง:</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($booking['start_time'])); ?> น. - <?php echo date('d/m/Y H:i', strtotime($booking['end_time'])); ?> น.</span>
                    </div>
                    <?php if (!empty($booking['brand']) && !empty($booking['model'])): ?>
                        <div class="detail-row" style="margin-bottom: 0;">
                            <span class="detail-label">รถที่จอง:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model'] . ' (' . $booking['license_plate'] . ')'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Rejection Reason -->
                <div class="reason-box">
                    <div class="box-title" style="color: #475569;">📝 เหตุผลการยกเลิก</div>
                    <p style="color: #b91c1c; font-size: 15px; font-weight: 500; padding-top: 5px; margin: 0; white-space: pre-wrap;"><?php echo htmlspecialchars($reason ?: 'ไม่ระบุ'); ?></p>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <div class="box-title" style="color: #166534; border-bottom: none; margin-bottom: 5px;">💡 การจองใหม่</div>
                    <p>หากคุณมีความต้องการใช้รถยนต์ส่วนกลาง สามารถเข้าสู่ระบบและสร้างรายการจองใหม่ได้ทันที</p>
                    <p class="note"><strong>หมายเหตุ:</strong> กรุณาตรวจสอบความพร้อมและตารางการใช้รถก่อนทำการจองทุกครั้ง</p>
                </div>

                <div class="contact-notice">
                    หากมีข้อสงสัยหรือต้องการสอบถามข้อมูลเพิ่มเติม กรุณาติดต่อผู้ดูแลระบบ (ฝ่ายบุคคล) หรือโทร 1064
                </div>
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