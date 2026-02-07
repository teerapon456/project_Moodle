<?php

/**
 * Dormitory Module - Notification Helper
 * ส่งอีเมลแจ้งเตือนต่างๆ
 */

class NotificationHelper
{
    /**
     * ส่งอีเมล
     */
    public static function sendEmail($to, $subject, $body)
    {
        if (empty($to)) {
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Dormitory System <noreply@dormitory.local>',
            'X-Mailer: PHP/' . phpversion()
        ];

        // Wrap body in HTML template
        $htmlBody = self::getEmailTemplate($subject, $body);

        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    /**
     * แจ้งเตือนเมื่อมีการแจ้งชำระเงิน
     */
    public static function notifyPaymentSubmitted($paymentData)
    {
        $adminEmail = self::getAdminEmail();
        if (empty($adminEmail)) {
            return false;
        }

        $subject = '🔔 แจ้งชำระเงินใหม่ - ระบบหอพัก';

        $body = "
            <h2 style='color:#A21D21;'>มีการแจ้งชำระเงินใหม่</h2>
            <table style='width:100%; border-collapse:collapse;'>
                <tr>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>ยอดเงิน:</strong></td>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>" . number_format($paymentData['total_amount'], 2) . " บาท</td>
                </tr>
                <tr>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>วันที่โอน:</strong></td>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>{$paymentData['payment_date']} {$paymentData['payment_time']}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>จำนวนบิล:</strong></td>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>{$paymentData['invoice_count']} รายการ</td>
                </tr>
            </table>
            <p style='margin-top:20px;'>
                <a href='" . self::getBaseUrl() . "?page=invoices' style='background:#A21D21; color:#fff; padding:10px 20px; text-decoration:none; border-radius:6px;'>
                    ตรวจสอบการชำระเงิน
                </a>
            </p>
        ";

        return self::sendEmail($adminEmail, $subject, $body);
    }

    /**
     * แจ้งเตือนเมื่อมีการแจ้งซ่อม
     */
    public static function notifyMaintenanceRequest($requestData)
    {
        $adminEmail = self::getAdminEmail();
        if (empty($adminEmail)) {
            return false;
        }

        $priorityColors = [
            'critical' => '#dc2626',
            'high' => '#ea580c',
            'medium' => '#f59e0b',
            'low' => '#10b981'
        ];
        $priorityNames = [
            'critical' => 'ฉุกเฉิน',
            'high' => 'สูง',
            'medium' => 'ปานกลาง',
            'low' => 'ต่ำ'
        ];

        $priority = $requestData['priority'] ?? 'medium';
        $priorityColor = $priorityColors[$priority] ?? '#f59e0b';
        $priorityName = $priorityNames[$priority] ?? 'ปานกลาง';

        $subject = "🔧 แจ้งซ่อมใหม่ [{$priorityName}] - ระบบหอพัก";

        $body = "
            <h2 style='color:#A21D21;'>มีการแจ้งซ่อมใหม่</h2>
            <table style='width:100%; border-collapse:collapse;'>
                <tr>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>เลขที่:</strong></td>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>{$requestData['ticket_number']}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>ห้อง:</strong></td>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>{$requestData['room']}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>หัวข้อ:</strong></td>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>{$requestData['title']}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>ความเร่งด่วน:</strong></td>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>
                        <span style='background:{$priorityColor}; color:#fff; padding:4px 10px; border-radius:4px;'>{$priorityName}</span>
                    </td>
                </tr>
                <tr>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'><strong>รายละเอียด:</strong></td>
                    <td style='padding:8px; border-bottom:1px solid #e5e7eb;'>{$requestData['description']}</td>
                </tr>
            </table>
            <p style='margin-top:20px;'>
                <a href='" . self::getBaseUrl() . "?page=maintenance' style='background:#A21D21; color:#fff; padding:10px 20px; text-decoration:none; border-radius:6px;'>
                    ดูรายการแจ้งซ่อม
                </a>
            </p>
        ";

        return self::sendEmail($adminEmail, $subject, $body);
    }

    /**
     * ดึงอีเมลผู้ดูแลระบบจาก settings
     */
    private static function getAdminEmail()
    {
        try {
            require_once __DIR__ . '/../../../core/Database/Database.php';
            $db = new Database();
            $pdo = $db->getConnection();

            $stmt = $pdo->prepare("SELECT setting_value FROM dorm_settings WHERE setting_key = 'admin_email'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['setting_value'] ?? '';
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * ดึง Base URL ของระบบ
     */
    private static function getBaseUrl()
    {
        require_once __DIR__ . '/../../../core/Helpers/UrlHelper.php';
        return \Core\Helpers\UrlHelper::getBaseUrl() . '/Modules/Dormitory/';
    }

    /**
     * Template อีเมล
     */
    private static function getEmailTemplate($title, $content)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$title}</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; background:#f3f4f6; padding:20px;'>
            <div style='max-width:600px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 6px rgba(0,0,0,0.1);'>
                <div style='background:#A21D21; color:#fff; padding:20px; text-align:center;'>
                    <h1 style='margin:0; font-size:20px;'>🏠 ระบบหอพัก</h1>
                </div>
                <div style='padding:24px;'>
                    {$content}
                </div>
                <div style='background:#f9fafb; padding:16px; text-align:center; font-size:12px; color:#6b7280;'>
                    อีเมลนี้ถูกส่งโดยอัตโนมัติจากระบบหอพัก กรุณาอย่าตอบกลับ
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
