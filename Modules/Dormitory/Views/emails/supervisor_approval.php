<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำขออนุมัติการใช้หอพัก</title>
    <style>
        body,
        table,
        td,
        p,
        a,
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
            margin: 30px auto 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            padding: 30px 20px;
            text-align: center;
        }

        .header h2 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
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
            background-color: #f8fafc;
            border-left: 4px solid #3b82f6;
            border-radius: 0 6px 6px 0;
            padding: 20px;
            margin-bottom: 25px;
        }

        .detail-row {
            margin-bottom: 12px;
            font-size: 15px;
        }

        .detail-label {
            font-weight: 600;
            color: #64748b;
            width: 120px;
            display: inline-block;
        }

        .detail-value {
            color: #1e293b;
            font-weight: 500;
        }

        .action-container {
            text-align: center;
            margin: 35px 0 20px;
        }

        .button {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 600;
            margin: 0 5px;
        }

        .button-reject {
            background-color: #ef4444;
        }

        .description-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 14px;
            color: #92400e;
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
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="webkit">
            <div class="header">
                <h2>🏢 คำขออนุมัติบริการหอพัก</h2>
            </div>
            <div class="content">
                <p class="greeting">เรียน ผู้จัดการ/หัวหน้างาน,</p>
                <p class="greeting" style="margin-top: -10px;">มีคำขอเกี่ยวกับบริการหอพักรอการพิจารณาอนุมัติจากคุณ ดังนี้:</p>

                <div class="details-box">
                    <div class="detail-row">
                        <span class="detail-label">ผู้ขอ:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['fullname'] ?? $booking['requester_name'] ?? 'ผู้ใช้งาน'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">ประเภทคำขอ:</span>
                        <span class="detail-value">
                            <?php
                            echo match ($booking['request_type']) {
                                'move_in' => 'ขอเข้าพัก',
                                'move_out' => 'ขอย้ายออก',
                                'change_room' => 'ขอเปลี่ยนห้อง',
                                'add_relative' => 'ขอเพิ่มญาติ',
                                'remove_relative' => 'ขอนำญาติออก',
                                default => $booking['request_type']
                            };
                            ?>
                        </span>
                    </div>
                    <?php if (!empty($booking['reason'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">เหตุผล:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['reason']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">วันที่ดำเนินการ:</span>
                        <span class="detail-value"><?php echo date('d/m/Y', strtotime($booking['check_in'])); ?></span>
                    </div>
                </div>

                <div class="action-container">
                    <a href="<?php echo htmlspecialchars($review_url ?? ($baseUrl . '/Modules/Dormitory/index.php?page=booking_manage')); ?>" class="button">ตรวจสอบและดำเนินการ</a>
                </div>

                <div class="description-box">
                    <strong>หมายเหตุ:</strong> การอนุมัติในขั้นตอนนี้เป็นการรับทราบและเห็นชอบจากหัวหน้างาน
                    หลังจากนี้คำขอจะถูกส่งให้แผนก IPCD ดำเนินการจัดสรรห้องพักและอนุมัติในลำดับสุดท้ายต่อไป
                </div>
            </div>
            <div class="footer">
                <p><strong>INTEQC Group - Dormitory System</strong></p>
                <p>อีเมลฉบับนี้เป็นการแจ้งเตือนอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            </div>
        </div>
    </div>
</body>

</html>