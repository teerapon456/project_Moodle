<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งเตือนคำขอใช้หอพักใหม่</title>
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
            background-color: #4f46e5;
            background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
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
            background-color: #eef2ff;
            border-left: 4px solid #4f46e5;
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
            vertical-align: top;
        }

        .detail-value {
            color: #1e293b;
            font-weight: 500;
            display: inline-block;
            width: calc(100% - 125px);
        }

        .action-container {
            text-align: center;
            margin: 35px 0 20px;
        }

        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            transition: background-color 0.3s;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);
        }

        .button:hover {
            background-color: #4338ca;
        }

        .notice {
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
            margin-top: 20px;
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
                <h2>📋 แจ้งเตือนคำขอหอพักใหม่</h2>
            </div>

            <div class="content">
                <p class="greeting">เรียน ทีมงาน IPCD,</p>
                <p class="greeting" style="margin-top: -10px;">หัวหน้างานได้อนุมัติคำขอหอพักแล้ว มีคำขอหอพักใหม่ที่รอการจัดสรร/พิจารณา ดังรายละเอียดต่อไปนี้:</p>

                <div class="details-box">
                    <div class="detail-row">
                        <span class="detail-label">ผู้ขอจอง:</span>
                        <span class="detail-value">
                            <?php
                            echo htmlspecialchars($booking['requester_name'] ?? $booking['fullname'] ?? $booking['EmpCode'] ?? 'Unknown User');
                            ?>
                        </span>
                    </div>
                    <?php if (!empty($booking['department'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">แผนก/ฝ่าย:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['department']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">ประเภทคำขอ:</span>
                        <span class="detail-value">
                            <?php
                            $typeLabel = match ($booking['request_type'] ?? '') {
                                'move_in' => 'ขอเข้าพัก',
                                'move_out' => 'ขอย้ายออก',
                                'change_room' => 'ขอเปลี่ยนห้อง',
                                'add_relative' => 'ขอเพิ่มญาติ',
                                'remove_relative' => 'ขอนำญาติออก',
                                default => $booking['request_type'] ?? '-'
                            };
                            echo htmlspecialchars($typeLabel);
                            ?>
                        </span>
                    </div>
                    <?php if (!empty($booking['reason'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">เหตุผล:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['reason']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($manageUrl)): ?>
                    <div class="action-container">
                        <a href="<?php echo htmlspecialchars($manageUrl); ?>" class="button">ตรวจสอบและอนุมัติ</a>
                    </div>
                <?php endif; ?>

                <p class="notice">
                    กรุณาคลิกปุ่มด้านบนเพื่อดำเนินการกับคำขอหอพัก
                </p>
            </div>

            <div class="footer">
                <p><strong>INTEQC Group</strong></p>
                <p>ระบบบริหารจัดการหอพัก (Dormitory Management System)</p>
                <p style="margin-top: 10px; font-size: 11px;">อีเมลฉบับนี้เป็นการแจ้งเตือนอัตโนมัติ กรุณาอย่าตอบกลับ</p>
            </div>
        </div>
    </div>
</body>

</html>