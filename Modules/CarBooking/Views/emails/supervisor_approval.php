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

        .button {
            display: inline-block;
            padding: 15px 40px;
            margin: 10px 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }

        .button-primary {
            background: #3498db;
            color: white;
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
            <h2>🚗 คำขออนุมัติการจองรถ</h2>
        </div>

        <div class="content">
            <p>มีคำขอจองรถใหม่ที่รอการอนุมัติจากคุณ:</p>

            <div class="booking-details">
                <p><strong>ผู้ขอจอง:</strong> <?php
                                                $userName = $booking['fullname'] ?? $booking['user_fullname'] ?? $booking['username'] ?? 'Unknown User';
                                                echo htmlspecialchars($userName);
                                                ?></p>
                <p><strong>แผนก/ฝ่าย:</strong> <?php echo htmlspecialchars($booking['department'] ?? '-'); ?></p>
                <p><strong>ปลายทาง:</strong> <?php echo htmlspecialchars($booking['destination']); ?></p>
                <p><strong>วัตถุประสงค์:</strong> <?php echo htmlspecialchars($booking['purpose']); ?></p>
                <p><strong>เวลาเริ่มต้น:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['start_time'])); ?> น.</p>
                <p><strong>เวลาสิ้นสุด:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['end_time'])); ?> น.</p>

                <p><strong>คนขับ:</strong>
                    <?php
                    // Ifมี driver_user_id ให้ใช้ชื่อคนขับจากระบบก่อน, ถ้า 0 หรือไม่มีให้ตกไปใช้ชื่อ/อีเมลที่ส่งมา
                    $hasDriverUser = !empty($booking['driver_user_id']) && $booking['driver_user_id'] !== '0';
                    if ($hasDriverUser) {
                        $driverText = $booking['driver_name'] ?? $booking['driver'] ?? $booking['driver_email'] ?? '';
                    } else {
                        $driverText = $booking['driver_name'] ?? $booking['driver_email'] ?? $booking['driver'] ?? '';
                    }
                    echo htmlspecialchars($driverText ?: '-');
                    ?>
                </p>

                <?php
                // Render passengers from passengers_detail (JSON array), fallback to '-'
                $passengerText = '-';
                if (!empty($booking['passengers_detail'])) {
                    $decoded = json_decode($booking['passengers_detail'], true);
                    if (is_array($decoded) && count($decoded) > 0) {
                        $names = array_filter(array_map(function ($p) {
                            return is_array($p) ? ($p['name'] ?? $p['email'] ?? '') : $p;
                        }, $decoded));
                        if (!empty($names)) {
                            $passengerText = implode(', ', $names);
                        }
                    }
                } elseif (!empty($booking['passengers'])) {
                    $passengerText = $booking['passengers'];
                }
                ?>
                <p><strong>ผู้โดยสาร:</strong> <?php echo htmlspecialchars($passengerText); ?></p>
            </div>

            <p style="text-align: center; margin: 30px 0;">
                <a href="<?php echo $review_url; ?>" class="button button-primary">📋 กดเพื่อดำเนินการ</a>
            </p>

            <p style="font-size: 12px; color: #777; text-align: center;">
                กรุณาคลิกปุ่มด้านบนเพื่อดูรายละเอียดและดำเนินการอนุมัติหรือปฏิเสธคำขอ<br>
                ลิงก์นี้จะหมดอายุใน 7 วัน
            </p>
        </div>

        <div class="footer">
            <p>INTEQC Car Booking System</p>
            <p>ระบบจองรถอัตโนมัติ</p>
        </div>
    </div>
</body>

</html>