<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองรถของคุณถูกเปลี่ยนแปลง</title>
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
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
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
            margin-bottom: 20px;
        }

        .car-details-box {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            border-radius: 0 6px 6px 0;
            padding: 20px;
            margin-bottom: 20px;
        }

        .fleet-details-box {
            background-color: #fdf4ff;
            border-left: 4px solid #c026d3;
            border-radius: 0 6px 6px 0;
            padding: 20px;
            margin-bottom: 20px;
        }

        .changes-box {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            border-radius: 0 6px 6px 0;
            padding: 20px;
            margin-bottom: 20px;
        }

        .instruction-box {
            background-color: #f1f5f9;
            border-radius: 6px;
            padding: 20px;
            margin-top: 30px;
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
            width: 130px;
            display: inline-block;
            vertical-align: top;
        }

        .detail-value {
            color: #1e293b;
            font-weight: 500;
            display: inline-block;
            width: calc(100% - 135px);
        }

        .instructions ul {
            padding-left: 20px;
            margin-top: 10px;
            color: #4b5563;
            font-size: 14px;
        }

        .instructions li {
            margin-bottom: 8px;
        }

        .notice {
            font-size: 13px;
            color: #ef4444;
            margin-top: 15px;
            font-weight: 500;
            background: #fef2f2;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #fee2e2;
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
                <h2>🔄 การจองรถถูกเปลี่ยนแปลง</h2>
            </div>

            <div class="content">
                <p class="greeting" style="margin-top: -10px;">การจองรถของคุณได้รับการปรับปรุงข้อมูลจากผู้ดูแลระบบ โปรดตรวจสอบรายละเอียดใหม่ ดังนี้:</p>

                <!-- Booking Details -->
                <div class="details-box">
                    <div class="box-title" style="color: #1e40af;">📋 ข้อมูลการเดินทางล่าสุด</div>
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
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($booking['start_time'])); ?> - <?php echo date('d/m/Y H:i', strtotime($booking['end_time'])); ?> น.</span>
                    </div>
                    <div class="detail-row" style="margin-bottom: 0;">
                        <span class="detail-label">ระยะเวลา:</span>
                        <span class="detail-value">
                            <?php
                            $start = new DateTime($booking['start_time']);
                            $end = new DateTime($booking['end_time']);
                            $interval = $start->diff($end);
                            echo $interval->format('%h ชั่วโมง %i นาที');
                            ?>
                        </span>
                    </div>
                </div>

                <!-- Car Details -->
                <?php if (!empty($booking['brand']) && !empty($booking['model'])): ?>
                    <div class="car-details-box">
                        <div class="box-title" style="color: #059669;">🚗 ข้อมูลรถที่ได้รับการพิจารณาใหม่</div>
                        <div class="detail-row">
                            <span class="detail-label">ยี่ห้อรถยนต์:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['brand']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">รุ่น/แบบ:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['model']); ?></span>
                        </div>
                        <div class="detail-row" style="margin-bottom: 0;">
                            <span class="detail-label">ทะเบียนรถ:</span>
                            <span class="detail-value" style="font-weight: bold; background: #fff; border: 1px solid #10b981; color: #059669; padding: 2px 8px; border-radius: 4px; display: inline-block; width: auto;"><?php echo htmlspecialchars($booking['license_plate']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Fleet Card Details -->
                <?php if (!empty($booking['fleet_card_id'])): ?>
                    <div class="fleet-details-box">
                        <div class="box-title" style="color: #a21caf;">💳 ข้อมูลบัตรเติมน้ำมันที่ใช้งานได้</div>
                        <div class="detail-row">
                            <span class="detail-label">หมายเลขบัตร:</span>
                            <span class="detail-value" style="font-family: monospace; letter-spacing: 1px; font-size: 16px;"><?php echo htmlspecialchars($booking['fleet_card_number']); ?></span>
                        </div>
                        <?php if (!empty($booking['fleet_amount'])): ?>
                            <div class="detail-row" style="margin-bottom: 0;">
                                <span class="detail-label">วงเงินที่อนุมัติ:</span>
                                <span class="detail-value" style="color: #a21caf; font-weight: bold;"><?php echo number_format($booking['fleet_amount'], 2); ?> บาท</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Changes Highlight -->
                <div class="changes-box">
                    <div class="box-title" style="color: #b45309;">⚠️ สถานะการอัปเดตข้อมูล</div>
                    <p style="color: #78350f; font-size: 14px; margin-bottom: 8px;">การจองของคุณได้รับการปรับปรุงข้อมูลโดยระบบหรือผู้ดูแล กรุณาตรวจสอบรายละเอียดด้านบนอย่างละเอียดอีกครั้ง</p>
                    <div class="detail-row" style="margin-bottom: 0;">
                        <span class="detail-label" style="color: #b45309;">วันที่ปรับปรุง:</span>
                        <span class="detail-value" style="color: #78350f;"><?php echo date('d/m/Y H:i'); ?> น.</span>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="instruction-box instructions">
                    <div class="box-title" style="border-bottom: none; color: #334155; margin-bottom: 0;">📌 ข้อปฏิบัติในการรับรถและใช้งานฉบับล่าสุด</div>
                    <ul>
                        <li>กรุณาไปติดต่อขอรับกุญแจรถและบัตรน้ำมัน ณ จุดบริการที่กำหนด</li>
                        <li>ตรวจสอบสภาพรถรอบคัน และบันทึกเลขไมล์ก่อนออกเดินทางทุกครั้ง</li>
                        <li>เมื่อใช้งานเสร็จสิ้น กรุณานำรถมาจอดที่เดิม แจ้งเลขไมล์ขากลับ และคืนกุญแจพร้อมแฟ้มทันที</li>
                    </ul>
                    <div class="notice">
                        ⚠️ หากต้องการเปลี่ยนแปลงข้อมูลหรือเวลาเดินรถ กรุณาแจ้งล่วงหน้าอย่างน้อย 1 วันทำงาน
                    </div>
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