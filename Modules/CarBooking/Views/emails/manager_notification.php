<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งเตือนคำขอจองรถใหม่</title>
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
            background-color: #f59e0b;
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
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
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
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
            background-color: #f59e0b;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            transition: background-color 0.3s;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }

        .button:hover {
            background-color: #d97706;
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
                <h2>📋 แจ้งเตือนคำขอจองรถใหม่</h2>
            </div>

            <div class="content">
                <p class="greeting">เรียน ทีมงาน IPCD,</p>
                <p class="greeting" style="margin-top: -10px;">มีคำขอใช้รถยนต์ส่วนกลางใหม่ที่รอการจัดสรรรถยนต์และบัตรเติมน้ำมัน ดังรายละเอียดต่อไปนี้:</p>

                <div class="details-box">
                    <div class="detail-row">
                        <span class="detail-label">ผู้ขอจอง:</span>
                        <span class="detail-value">
                            <?php
                            echo htmlspecialchars($booking['user_fullname'] ?? $booking['fullname'] ?? $booking['username'] ?? 'Unknown User');
                            ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">แผนก/ฝ่าย:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['user_department'] ?? '-'); ?></span>
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
                        <span class="detail-label">เวลาไป:</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($booking['start_time'])); ?> น.</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">เวลากลับ:</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($booking['end_time'])); ?> น.</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">คนขับ:</span>
                        <span class="detail-value">
                            <?php
                            $hasDriverUser = !empty($booking['driver_user_id']) && $booking['driver_user_id'] !== '0';
                            if ($hasDriverUser) {
                                $driverText = $booking['driver_name'] ?? $booking['driver'] ?? $booking['driver_email'] ?? '';
                            } else {
                                $driverText = $booking['driver_name'] ?? $booking['driver_email'] ?? $booking['driver'] ?? '';
                            }
                            echo htmlspecialchars($driverText ?: '-');
                            ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">ผู้โดยสาร:</span>
                        <span class="detail-value">
                            <?php
                            $passengerText = '-';
                            if (!empty($booking['passengers_detail'])) {
                                $decoded = json_decode($booking['passengers_detail'], true);
                                if (is_array($decoded) && count($decoded) > 0) {
                                    $names = [];
                                    foreach ($decoded as $p) {
                                        if (is_array($p)) {
                                            $names[] = $p['name'] ?? $p['email'] ?? '';
                                        } else {
                                            $names[] = $p;
                                        }
                                    }
                                    $names = array_filter($names);
                                    if (!empty($names)) {
                                        $passengerText = implode(', ', $names);
                                    }
                                }
                            } elseif (!empty($booking['passengers'])) {
                                $passengerText = $booking['passengers'];
                            }
                            echo htmlspecialchars($passengerText);
                            ?>
                        </span>
                    </div>

                    <?php if (!empty($booking['fleet_card_number'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Fleet Card:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['fleet_card_number']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="detail-row" style="margin-bottom: 0;">
                        <span class="detail-label">สถานะปัจจุบัน:</span>
                        <span class="detail-value" style="color: #059669; font-weight: bold;">รอการจัดรถ</span>
                    </div>
                </div>

                <?php if (!empty($manage_url)): ?>
                    <div class="action-container">
                        <a href="<?php echo htmlspecialchars($manage_url); ?>" class="button">เข้าสู่ระบบเพื่อจัดการคำขอ</a>
                    </div>
                <?php endif; ?>

                <p class="notice">
                    กรุณาคลิกปุ่มด้านบนเพื่อดำเนินการจัดสรรรถยนต์ส่วนกลางและบัตรเติมน้ำมันให้กับผู้ขอใช้งาน
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