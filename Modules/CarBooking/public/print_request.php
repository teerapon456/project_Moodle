<?php
// Session management
require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
if (function_exists('startOptimizedSession')) {
    startOptimizedSession();
} else {
    // Fallback if function not found (should not happen)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$user = $_SESSION['user'] ?? null;
if (!$user) {
    http_response_code(401);
    die('Unauthorized');
}

$bookingId = (int)($_GET['id'] ?? 0);
if (!$bookingId) {
    http_response_code(400);
    die('Invalid booking ID');
}

// Fetch booking data
try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = "SELECT
cb.*,
u.fullname as user_fullname,
u.username as user_name,
u.Level3Name as user_department,
u.email as user_email,
c.name as car_name,
c.brand,
c.model,
c.type as car_type,
c.license_plate,
c.capacity,
fc.card_number as fleet_card_number,
fc.department as fleet_card_department,
fc.credit_limit,
supervisor.fullname as supervisor_fullname,
supervisor.username as supervisor_name,
super_email_match.fullname as supervisor_fullname_by_email,
manager.fullname as manager_fullname,
manager.username as manager_name,
driver_u.fullname as driver_fullname
FROM cb_bookings cb
LEFT JOIN users u ON cb.user_id = u.id
LEFT JOIN cb_cars c ON cb.assigned_car_id = c.id
LEFT JOIN cb_fleet_cards fc ON cb.fleet_card_id = fc.id
LEFT JOIN users supervisor ON cb.supervisor_approved_user_id = supervisor.id
LEFT JOIN users super_email_match ON cb.supervisor_approved_by COLLATE utf8mb4_unicode_ci = super_email_match.email
LEFT JOIN users manager ON cb.manager_approved_user_id = manager.id
LEFT JOIN users driver_u ON cb.driver_email COLLATE utf8mb4_unicode_ci = driver_u.email
WHERE cb.id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        http_response_code(404);
        die('Booking not found');
    }

    // Parse passengers
    $passengers = [];
    if (!empty($booking['passengers_detail'])) {
        $passengerData = json_decode($booking['passengers_detail'], true);
        if (is_array($passengerData)) {
            foreach ($passengerData as $p) {
                // Determine email or identifier
                $val = $p['name'] ?? $p['email'] ?? '';
                if ($val) {
                    $passengers[] = ['raw' => $val, 'is_email' => filter_var($val, FILTER_VALIDATE_EMAIL)];
                }
            }

            // Optimize: Fetch passenger names if they are emails
            $emailsToFetch = array_column(array_filter($passengers, fn($p) => $p['is_email']), 'raw');
            $userMap = [];
            if (!empty($emailsToFetch)) {
                $placeholders = implode(',', array_fill(0, count($emailsToFetch), '?'));
                $uStmt = $conn->prepare("SELECT email, fullname FROM users WHERE email IN ($placeholders)");
                $uStmt->execute($emailsToFetch);
                $userMap = $uStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }

            // Rebuild final passenger list
            $finalPassengers = [];
            foreach ($passengers as $p) {
                if ($p['is_email'] && isset($userMap[$p['raw']])) {
                    $finalPassengers[] = $userMap[$p['raw']];
                } else {
                    $finalPassengers[] = $p['raw'];
                }
            }
            $passengers = $finalPassengers;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    die('Database error: ' . $e->getMessage());
}

// Start output buffering for PDF rendering
ob_start();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'TH Sarabun New', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
        }

        .header .subtitle {
            font-size: 10pt;
            margin: 5px 0 0 0;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            padding: 5px 0;
            margin: 14px 0 8px 0;
            border-bottom: 2px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        td {
            padding: 6px 10px;
            vertical-align: top;
            font-size: 10pt;
            word-wrap: break-word;
        }

        .label {
            font-weight: bold;
            width: 22%;
        }

        .value {
            width: 28%;
        }

        .guard-box {
            border: 2px solid #000;
            padding: 18px;
            margin-top: 20px;
        }

        .guard-box h3 {
            text-align: center;
            margin: 0 0 18px 0;
            font-size: 11pt;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }

        .guard-box table {
            margin-bottom: 14px;
            table-layout: fixed;
        }

        .guard-box td {
            border: 1px solid #000;
            padding: 16px 14px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .line-field {
            display: block;
            border-bottom: 1px dotted #000;
            height: 14px;
            margin-top: 6px;
        }

        .line-field.tall {
            height: 24px;
        }

        .guard-label {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 12px;
            line-height: 1.8;
            display: block;
        }

        .footer {
            text-align: center;
            font-size: 10pt;
            margin-top: 18px;
            padding-top: 10px;
            border-top: 2px solid #000;
        }
    </style>
</head>

<body>

    <div class="header">
        <div style="font-size: 14pt; font-weight: bold;">CAR REQUEST FORM</div>
        <div class="subtitle">เลขที่ : <strong>CAR-<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></strong> &nbsp;|&nbsp; วันที่ออกเอกสาร (Date): <strong><?php echo date('d/m/Y H:i', strtotime($booking['manager_approved_at'] ?? $booking['created_at'])) . ' น.'; ?></strong></div>
    </div>

    <div class="section-title">ส่วนที่ 1: ข้อมูลผู้ขอใช้บริการและยานพาหนะ/Fleet Card (Requester &amp; Vehicle/Fleet Card Information)</div>
    <table>
        <tr>
            <td class="label">ชื่อผู้ขอ :</td>
            <td class="value"><?php echo htmlspecialchars($booking['user_fullname'] ?: $booking['user_name']); ?></td>
            <td class="label">แผนก :</td>
            <td class="value"><?php echo htmlspecialchars($booking['user_department'] ?: '-'); ?></td>
        </tr>
        <?php if (!empty($booking['assigned_car_id']) || !empty($booking['brand']) || !empty($booking['model']) || !empty($booking['license_plate'])): ?>
            <tr>
                <td class="label">ยี่ห้อ/รุ่น :</td>
                <td class="value">
                    <?php
                    $brandModel = trim(($booking['brand'] ?? '') . ' ' . ($booking['model'] ?? ''));
                    echo htmlspecialchars($brandModel ?: '-');
                    ?>
                </td>
                <td class="label">ทะเบียน :</td>
                <td class="value"><?php echo htmlspecialchars($booking['license_plate'] ?? '-'); ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($booking['fleet_card_id'])): ?>
            <tr>
                <td class="label">Fleet Card :</td>
                <td class="value"><?php echo htmlspecialchars($booking['fleet_card_number']); ?></td>
                <td class="label">วงเงินอนุมัติ :</td>
                <td class="value">
                    <?php
                    $amount = $booking['fleet_amount'] ?? null;
                    if ($amount !== null && $amount !== '') {
                        echo number_format((float)$amount, 2) . ' บาท';
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="section-title">ส่วนที่ 2: รายละเอียดการเดินทาง (Trip Details)</div>
    <table>
        <tr>
            <td class="label">ปลายทาง :</td>
            <td colspan="3" class="value"><?php echo htmlspecialchars($booking['destination']); ?></td>
        </tr>
        <tr>
            <td class="label">วัตถุประสงค์ :</td>
            <td colspan="3" class="value"><?php echo htmlspecialchars($booking['purpose']); ?></td>
        </tr>
        <tr>
            <td class="label">วันเวลาออก :</td>
            <td class="value"><?php echo date('d/m/Y H:i', strtotime($booking['start_time'])) . ' น.'; ?></td>
            <td class="label">วันเวลากลับ :</td>
            <td class="value"><?php echo date('d/m/Y H:i', strtotime($booking['end_time'])) . ' น.'; ?></td>
        </tr>
    </table>

    <div class="section-title">ส่วนที่ 3: ข้อมูลการอนุมัติ (Approval Information)</div>
    <table>
        <?php if (!empty($booking['supervisor_approved_at'])): ?>
            <tr>
                <td class="label">หัวหน้างาน :</td>
                <td class="value">
                    <?php
                    $supName = $booking['supervisor_fullname'] ?: $booking['supervisor_name'];
                    if (empty($supName)) {
                        $supName = $booking['supervisor_fullname_by_email'] ?: $booking['supervisor_approved_by'] ?: '-';
                    }
                    echo htmlspecialchars($supName);
                    ?>
                </td>
                <td class="label">วันที่อนุมัติ :</td>
                <td class="value"><?php echo date('d/m/Y H:i', strtotime($booking['supervisor_approved_at'])) . ' น.'; ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($booking['manager_approved_at'])): ?>
            <tr>
                <td class="label">สายงาน IPCD :</td>
                <td class="value"><?php echo htmlspecialchars($booking['manager_fullname'] ?: $booking['manager_name'] ?: '-'); ?></td>
                <td class="label">วันที่อนุมัติ :</td>
                <td class="value"><?php echo date('d/m/Y H:i', strtotime($booking['manager_approved_at'])) . ' น.'; ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="guard-box">
        <h3>ส่วนที่ 4: สำหรับเจ้าหน้าที่รักษาความปลอดภัย (For Security Guard)</h3>
        <table>
            <tr>
                <td style="width: 50%;">
                    <div style="text-align: center; font-weight: bold; margin-bottom: 14px; border-bottom: 1px solid #000; padding-bottom: 6px;">รถออก (CHECK-OUT)</div><br />
                    <div class="guard-label">วันที่/เวลา :</div>
                    <span class="line-field"></span><br />
                    <div class="guard-label">เลขไมล์ :</div>
                    <span class="line-field"></span><br />
                    <div class="guard-label">ลายเซ็น รปภ. :</div>
                    <span class="line-field tall"></span><br />
                </td>
                <td style="width: 50%;">
                    <div style="text-align: center; font-weight: bold; margin-bottom: 14px; border-bottom: 1px solid #000; padding-bottom: 6px;">รถเข้า (CHECK-IN)</div><br />
                    <div class="guard-label">วันที่/เวลา :</div>
                    <span class="line-field"></span><br />
                    <div class="guard-label">เลขไมล์ :</div>
                    <span class="line-field"></span><br />
                    <div class="guard-label">ลายเซ็น รปภ. :</div>
                    <span class="line-field tall"></span><br />
                </td>
            </tr>
        </table>
        <div style="margin-top: 12px; border-top: 1px solid #000; padding-top: 10px;">
            <div class="guard-label">หมายเหตุ/ความเสียหาย (Remarks/Damage):</div>
            <div class="line-field tall"></div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 15px;">
        <div style="font-weight: bold; margin-bottom: 5px;">สแกนเพื่อติดต่อเจ้าหน้าที่ / Scan for Contact</div>
        <img src="<?= __DIR__ ?>/assets/images/line_qr.jpg" style="width: 90px; height: 90px;" alt="LINE QR Code">
        <div style="font-size: 9pt; margin-top: 3px;">LINE Official Account</div>
    </div>

    <div class="footer">
        <strong>INTEQC Car Booking System</strong><br>
        กรุณาส่งคืนเอกสารฉบับนี้หลังใช้รถเสร็จสิ้น / Please return this form after use
    </div>

</body>

</html>

<?php
// Render buffered HTML to PDF with mPDF
$html = ob_get_clean();
$mpdf = new \Mpdf\Mpdf([
    'autoLangToFont' => true,
    'tempDir' => sys_get_temp_dir(),
    'margin_top' => 12,
    'margin_bottom' => 12,
    'margin_left' => 12,
    'margin_right' => 12
]);
$mpdf->WriteHTML($html);
$mpdf->Output('car_request_' . $booking['id'] . '.pdf', 'I');
exit;
