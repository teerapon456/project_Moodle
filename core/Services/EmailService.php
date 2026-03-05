<?php
require_once __DIR__ . '/../Config/EmailConfig.php';
require_once __DIR__ . '/../Database/Database.php';

/**
 * Email Service for sending notifications
 * Uses PHPMailer with SMTP configuration
 */
class EmailService
{

    /**
     * Send test email (public wrapper for sendMail)
     */
    public static function sendTestEmail($to, $subject, $body)
    {
        return self::sendMail($to, $subject, $body);
    }

    /**
     * Send email with auto CC from module settings
     * Generic method that any module can use by passing its module_id
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body HTML body
     * @param int $moduleId Module ID for fetching CC settings (2=CarBooking, 20=Dormitory, etc)
     * @return bool
     */
    public static function sendModuleEmail($to, $subject, $body, $moduleId)
    {
        $settings = self::getModuleSettings($moduleId);
        return self::sendMail($to, $subject, $body, null, $settings['cc_emails']);
    }

    /**
     * Get module settings (admin_emails, cc_emails) from system_settings
     * @param int $moduleId Module ID (2 = Car Booking, 20 = Dormitory)
     * @return array ['admin_emails' => string, 'cc_emails' => string]
     */
    public static function getModuleSettings($moduleId)
    {
        $settings = ['admin_emails' => '', 'cc_emails' => ''];
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE module_id = ?");
            $stmt->execute([$moduleId]);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = $row['setting_key'];
                $val = $row['setting_value'];

                if ($key === 'admin_emails' || $key === 'admin_email') {
                    // Prefer plural, but accept singular. detailed logic: join if multiple? usually just one key exists.
                    // overwrite if found, assuming one valid key is used.
                    if (!empty($val)) $settings['admin_emails'] = $val;
                } elseif ($key === 'cc_emails' || $key === 'cc_email') {
                    if (!empty($val)) $settings['cc_emails'] = $val;
                }
            }
        } catch (\Exception $e) {
            error_log("EmailService::getModuleSettings error: " . $e->getMessage());
        }
        return $settings;
    }

    /**
     * Build CC list from booking data (driver email + passengers emails)
     * @param array $booking Booking data with driver_email and passengers_detail
     * @param string|null $excludeEmail Email to exclude from CC (usually the main recipient)
     * @return array List of CC emails
     */
    public static function buildBookingCcList($booking, $excludeEmail = null)
    {
        $ccEmails = [];
        $userEmail = $booking['user_email'] ?? $booking['email'] ?? '';

        // Add driver email if different from user and recipient
        if (!empty($booking['driver_email'])) {
            $driverEmail = strtolower(trim($booking['driver_email']));
            if (
                $driverEmail !== strtolower($userEmail) &&
                ($excludeEmail === null || $driverEmail !== strtolower($excludeEmail))
            ) {
                $ccEmails[] = $booking['driver_email'];
            }
        }

        // Add passenger emails
        $passengersDetail = $booking['passengers_detail'] ?? null;
        if (!empty($passengersDetail)) {
            $passengers = is_string($passengersDetail) ? json_decode($passengersDetail, true) : $passengersDetail;
            if (is_array($passengers)) {
                foreach ($passengers as $p) {
                    if (!empty($p['email'])) {
                        $pEmail = strtolower(trim($p['email']));
                        if (
                            $pEmail !== strtolower($userEmail) &&
                            ($excludeEmail === null || $pEmail !== strtolower($excludeEmail)) &&
                            !in_array($p['email'], $ccEmails)
                        ) {
                            $ccEmails[] = $p['email'];
                        }
                    }
                }
            }
        }

        return $ccEmails;
    }

    /**
     * Send email using PHPMailer with SMTP
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $htmlBody HTML body
     * @param string|null $textBody Plain text body
     * @param array|string|null $ccEmails CC email(s) - can be array or comma-separated string
     */
    public static function sendMail($to, $subject, $htmlBody, $textBody = null, $ccEmails = null)
    {
        $enabledEmails = EmailConfig::getEnableEmails();
        // Check recipient
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Email skipped: Invalid or empty recipient '$to' for subject: $subject");
            self::logEmail($to, $subject, $htmlBody, 'skipped', 'Invalid or empty recipient');
            return false;
        }

        if (!$enabledEmails) {
            error_log("Email sending disabled. Would have sent: $subject to $to");
            self::logEmail($to, $subject, $htmlBody, 'disabled', 'Email sending is disabled in config');
            return true;
        }

        // Load Composer autoloader - this properly registers PHPMailer namespaces
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            $errorMsg = "Composer autoloader not found. Please run 'composer install' in the project root.";
            error_log("CRITICAL ERROR: $errorMsg");
            self::logEmail($to, $subject, $htmlBody, 'failed', $errorMsg);
            return false;
        }
        require_once $autoloadPath;

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            // [DISABLED FOR PRODUCTION] error_log("PHPMailer instance created successfully");
        } catch (\Exception $e) {
            $errorMsg = "Failed to create PHPMailer instance: " . $e->getMessage();
            error_log("CRITICAL ERROR: $errorMsg");
            self::logEmail($to, $subject, $htmlBody, 'failed', $errorMsg);
            return false;
        }

        try {
            // Load environment configuration
            $smtpHost = Env::get('SMTP_HOST', '');
            $smtpPort = Env::get('SMTP_PORT', 587);
            $smtpUsername = Env::get('SMTP_USERNAME', '');
            $smtpPassword = Env::get('SMTP_PASSWORD', '');
            $smtpFromEmail = Env::get('SMTP_FROM_EMAIL', $smtpUsername);
            $smtpFromName = Env::get('SMTP_FROM_NAME', 'MyHR Portal');
            $smtpDebug = 0; // Disable debug for production (use 2 for troubleshooting)

            // Reduce logging for performance
            // error_log("SMTP Config: Host=$smtpHost, Port=$smtpPort, User=$smtpUsername, FromEmail=$smtpFromEmail");

            // Validate SMTP configuration (Host is required, Auth is optional)
            if (empty($smtpHost)) {
                $errorMsg = "SMTP configuration is invalid. Host is missing.";
                error_log("CRITICAL ERROR: $errorMsg");
                self::logEmail($to, $subject, $htmlBody, 'failed', $errorMsg);
                return false;
            }

            // Server settings
            if ($smtpDebug > 0) {
                $mail->SMTPDebug = $smtpDebug;
                $mail->Debugoutput = function ($str, $level) {
                    error_log("SMTP Debug [$level]: $str");
                };
            }

            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->Port       = (int)$smtpPort;

            // Authentication
            if (!empty($smtpUsername)) {
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUsername;
                $mail->Password = $smtpPassword;
            } else {
                $mail->SMTPAuth = false;
            }

            // Encryption
            $smtpSecure = Env::get('SMTP_SECURE', ''); // tls, ssl, or empty
            $mail->SMTPSecure = $smtpSecure; // If empty, PHPMailer disables encryption logic unless AutoTLS
            $mail->CharSet = 'UTF-8';

            // Performance optimization
            $mail->Timeout = 10;
            $mail->SMTPKeepAlive = false;

            // AutoTLS: Enable only if not explicitly disabled or if Secure is set
            // If Port 25 and Secure is empty, we often want to disable AutoTLS for internal relays
            if (empty($smtpSecure) && $smtpPort == 25) {
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPAutoTLS = true;
            }
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Recipients
            $mail->setFrom($smtpFromEmail, $smtpFromName);
            $mail->addAddress($to);

            // Add CC emails - use passed parameter if provided, otherwise fall back to EmailConfig
            $ccList = [];
            if ($ccEmails !== null) {
                if (is_array($ccEmails)) {
                    $ccList = $ccEmails;
                } elseif (is_string($ccEmails) && !empty($ccEmails)) {
                    $ccList = array_map('trim', explode(',', $ccEmails));
                }
            } else {
                // Fall back to EmailConfig for backward compatibility
                $configCc = EmailConfig::CC_EMAIL();
                if (!empty($configCc)) {
                    $ccList = [$configCc];
                }
            }

            foreach ($ccList as $cc) {
                if (!empty($cc) && filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($cc);
                    // [DISABLED FOR PRODUCTION] error_log("Adding CC: $cc");
                }
            }

            // error_log("Email recipients configured: From=$smtpFromEmail, To=$to");

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            if ($textBody) {
                $mail->AltBody = $textBody;
            }

            // error_log("Attempting to send email...");
            $mail->send();

            // [DISABLED FOR PRODUCTION] error_log("=== EMAIL SENT SUCCESSFULLY === To: $to, Subject: $subject");
            self::logEmail($to, $subject, $htmlBody, 'sent', null);
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $errorMsg = "PHPMailer Error: {$mail->ErrorInfo} | Exception: {$e->getMessage()}";
            error_log("=== EMAIL SEND FAILED (PHPMailer) === $errorMsg");
            self::logEmail($to, $subject, $htmlBody, 'failed', $errorMsg);
            return false;
        } catch (\Exception $e) {
            $errorMsg = "General Error: {$e->getMessage()} | Trace: " . $e->getTraceAsString();
            error_log("=== EMAIL SEND FAILED (General) === $errorMsg");
            self::logEmail($to, $subject, $htmlBody, 'failed', $errorMsg);
            return false;
        }
    }

    /**
     * Send raw email (simple wrapper for scheduled reports etc.)
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $htmlBody HTML body
     * @param string|null $attachmentPath Optional file path to attach
     * @param string|null $attachmentName Optional name for the attachment
     * @return bool
     */
    public static function sendRawEmail($to, $subject, $htmlBody, $attachmentPath = null, $attachmentName = null)
    {
        // If no attachment, just use regular send
        if (!$attachmentPath) {
            return self::sendMail($to, $subject, $htmlBody);
        }

        // Load Composer autoloader
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            error_log("Composer autoloader not found");
            return false;
        }
        require_once $autoloadPath;

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = Env::get('SMTP_HOST', '');
            $mail->Port = Env::get('SMTP_PORT', 587);
            $mail->SMTPAuth = true;
            $mail->Username = Env::get('SMTP_USERNAME', '');
            $mail->Password = Env::get('SMTP_PASSWORD', '');
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(Env::get('SMTP_FROM_EMAIL', $mail->Username), Env::get('SMTP_FROM_NAME', 'MyHR Portal'));
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            // Add attachment
            if (file_exists($attachmentPath)) {
                $mail->addAttachment($attachmentPath, $attachmentName ?: basename($attachmentPath));
            }

            $result = $mail->send();
            self::logEmail($to, $subject, $htmlBody, $result ? 'sent' : 'failed');

            return $result;
        } catch (\Exception $e) {
            error_log("Send email with attachment failed: " . $e->getMessage());
            self::logEmail($to, $subject, $htmlBody, 'failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Log email to database and file
     */
    private static function logEmail($to, $subject, $body, $status, $error = null)
    {
        try {
            // Log to database
            $db = new Database();
            $conn = $db->getConnection();

            // Ensure connection uses utf8mb4 to support emoji characters in email templates
            $conn->exec("SET NAMES utf8mb4");

            $bodyPreview = mb_substr(strip_tags($body), 0, 150);

            $query = "INSERT INTO email_logs (recipient_email, subject, body_preview, body_html, status, error_message) 
                      VALUES (:recipient, :subject, :body_preview, :body, :status, :error)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':recipient', $to);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':body_preview', $bodyPreview);
            $stmt->bindParam(':body', $body);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':error', $error);

            try {
                $stmt->execute();
            } catch (\PDOException $utf8Err) {
                // Fallback: strip 4-byte UTF-8 characters (emoji) and retry
                if (strpos($utf8Err->getMessage(), '1366') !== false || strpos($utf8Err->getMessage(), 'Incorrect string value') !== false) {
                    $cleanBody = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $body);
                    $cleanSubject = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $subject);
                    $cleanPreview = mb_substr(strip_tags($cleanBody), 0, 150);

                    $stmt2 = $conn->prepare($query);
                    $stmt2->bindParam(':recipient', $to);
                    $stmt2->bindParam(':subject', $cleanSubject);
                    $stmt2->bindParam(':body_preview', $cleanPreview);
                    $stmt2->bindParam(':body', $cleanBody);
                    $stmt2->bindParam(':status', $status);
                    $stmt2->bindParam(':error', $error);
                    $stmt2->execute();
                } else {
                    throw $utf8Err;
                }
            }
        } catch (Exception $e) {
            error_log("Failed to log email to database: " . $e->getMessage());
        }

        // Also log to file
        $logFile = __DIR__ . '/../../logs/email.log';
        $logDir = dirname($logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$status] To: $to | Subject: $subject";
        if ($error) {
            $logMessage .= " | Error: $error";
        }
        $logMessage .= PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Send booking approval request to supervisor
     * @param array $booking Booking data
     * @param string $supervisorEmail Supervisor's email
     * @param string $token Approval token
     * @param array|null $additionalCc Additional CC emails (driver, passengers)
     */
    public static function sendSupervisorApprovalEmail($booking, $supervisorEmail, $token, $additionalCc = null)
    {
        $baseUrl = rtrim(Env::getBaseUrl(), '/');
        $reviewUrl = $baseUrl . "/Modules/CarBooking/index.php?page=manage&id=" . $booking['id'];

        $userName = $booking['user_fullname'] ?? $booking['fullname'] ?? $booking['username'] ?? 'Unknown User';
        $department = !empty($booking['user_department']) ? " - {$booking['user_department']}" : "";
        $subject = "การขออนุมัติการจองรถ - {$userName}{$department}";

        $html = self::renderTemplate('supervisor_approval', [
            'booking' => $booking,
            'review_url' => $reviewUrl
        ]);

        // Get CC emails from Car Booking module settings (module_id = 2)
        $settings = self::getModuleSettings(EmailConfig::getModuleId());

        // Merge system CC with additional CC (driver, passengers)
        $allCc = [];
        if (!empty($settings['cc_emails'])) {
            $allCc = array_merge($allCc, array_map('trim', explode(',', $settings['cc_emails'])));
        }
        if (!empty($additionalCc) && is_array($additionalCc)) {
            $allCc = array_merge($allCc, $additionalCc);
        }

        // Remove duplicates and empty values, also don't CC the supervisor (they're the main recipient)
        $allCc = array_unique(array_filter($allCc, function ($email) use ($supervisorEmail) {
            return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && strtolower($email) !== strtolower($supervisorEmail);
        }));

        return self::sendMail($supervisorEmail, $subject, $html, null, $allCc);
    }

    /**
     * Send notification to manager after supervisor approval
     */
    public static function sendManagerNotificationEmail($booking, $managerEmail = null)
    {
        // Get Car Booking module settings (module_id = 2)
        $settings = self::getModuleSettings(EmailConfig::getModuleId());

        // Use provided email or fall back to admin_emails from Car Booking settings
        if (!$managerEmail) {
            $managerEmail = $settings['admin_emails'];
        }

        if (empty($managerEmail)) {
            error_log("sendManagerNotificationEmail: No manager email configured");
            return false;
        }

        $userName = $booking['user_fullname'] ?? $booking['fullname'] ?? $booking['username'] ?? 'Unknown User';
        $department = !empty($booking['user_department']) ? " - {$booking['user_department']}" : "";
        $subject = "คำขอจองรถใหม่ - {$userName}{$department}";

        $baseUrl = rtrim(Env::getBaseUrl(), '/');
        // Point to new Module structure
        $manageUrl = $baseUrl . "/Modules/CarBooking/index.php?page=manage&id=" . $booking['id'];

        $html = self::renderTemplate('manager_notification', [
            'booking' => $booking,
            'manage_url' => $manageUrl
        ]);

        // Send to all admin emails (comma-separated) with CC
        $sent = false;
        $adminList = array_map('trim', explode(',', $managerEmail));
        foreach ($adminList as $email) {
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $sent = self::sendMail($email, $subject, $html, null, $settings['cc_emails']) || $sent;
            }
        }
        return $sent;
    }

    /**
     * Send rejection notification to user
     */
    public static function sendUserRejectionEmail($booking, $userEmail, $reason, $rejectedBy = 'supervisor')
    {
        $subject = "การจองรถของคุณถูกปฏิเสธ";

        $html = self::renderTemplate('user_rejection', [
            'booking' => $booking,
            'reason' => $reason,
            'rejectedBy' => $rejectedBy
        ]);

        // Get CC emails: system settings + driver + passengers
        $settings = self::getModuleSettings(EmailConfig::getModuleId());
        $bookingCc = self::buildBookingCcList($booking, $userEmail);
        $allCc = array_merge(
            !empty($settings['cc_emails']) ? array_map('trim', explode(',', $settings['cc_emails'])) : [],
            $bookingCc
        );
        return self::sendMail($userEmail, $subject, $html, null, array_unique(array_filter($allCc)));
    }

    /**
     * Send cancellation notification to user
     */
    public static function sendCancellationEmail($booking, $userEmail, $reason)
    {
        $subject = "การจองรถของคุณถูกยกเลิก - #{$booking['id']}";

        $html = self::renderTemplate('user_cancellation', [
            'booking' => $booking,
            'reason' => $reason
        ]);

        // Get CC emails: system settings + driver + passengers
        $settings = self::getModuleSettings(EmailConfig::getModuleId());
        $bookingCc = self::buildBookingCcList($booking, $userEmail);
        $allCc = array_merge(
            !empty($settings['cc_emails']) ? array_map('trim', explode(',', $settings['cc_emails'])) : [],
            $bookingCc
        );
        return self::sendMail($userEmail, $subject, $html, null, array_unique(array_filter($allCc)));
    }

    /**
     * Send update notification to user
     */
    public static function sendBookingUpdateEmail($booking, $userEmail)
    {
        $subject = "การจองรถของคุณถูกปรับปรุง - #{$booking['id']}";

        $html = self::renderTemplate('user_booking_update', [
            'booking' => $booking
        ]);

        // Get CC emails: system settings + driver + passengers
        $settings = self::getModuleSettings(EmailConfig::getModuleId());
        $bookingCc = self::buildBookingCcList($booking, $userEmail);
        $allCc = array_merge(
            !empty($settings['cc_emails']) ? array_map('trim', explode(',', $settings['cc_emails'])) : [],
            $bookingCc
        );
        return self::sendMail($userEmail, $subject, $html, null, array_unique(array_filter($allCc)));
    }

    /**
     * Send approval confirmation to user
     */
    public static function sendUserApprovalEmail($booking, $userEmail)
    {
        $subject = "การจองรถของคุณได้รับการอนุมัติ";

        // Extract car details if car_id is assigned
        $car = null;
        if (!empty($booking['assigned_car_id']) || !empty($booking['brand']) || !empty($booking['license_plate']) || !empty($booking['assigned_car'])) {
            $car = [
                'brand' => $booking['brand'] ?? $booking['assigned_car_brand'] ?? null,
                'model' => $booking['model'] ?? $booking['assigned_car_model'] ?? null,
                'license_plate' => $booking['license_plate'] ?? $booking['assigned_car_plate'] ?? null,
            ];
        }

        // Extract fleet card details if fleet_card_id is assigned
        $fleetCard = null;
        if (!empty($booking['fleet_card_id']) && !empty($booking['fleet_card_number'])) {
            $fleetCard = [
                'card_number' => $booking['fleet_card_number'],
                'approved_amount' => $booking['fleet_amount'] ?? null
            ];
        }

        $html = self::renderTemplate('user_approval', [
            'booking' => $booking,
            'car' => $car,
            'fleetCard' => $fleetCard
        ]);

        // Get CC emails: system settings + driver + passengers
        $settings = self::getModuleSettings(EmailConfig::getModuleId());
        $bookingCc = self::buildBookingCcList($booking, $userEmail);
        $allCc = array_merge(
            !empty($settings['cc_emails']) ? array_map('trim', explode(',', $settings['cc_emails'])) : [],
            $bookingCc
        );
        return self::sendMail($userEmail, $subject, $html, null, array_unique(array_filter($allCc)));
    }

    /**
     * Send supervisor approval notification to user
     * Simple notification that supervisor approved, waiting for manager
     */
    public static function sendUserSupervisorApprovalEmail($booking, $userEmail)
    {
        $subject = "ผู้บังคับบัญชาอนุมัติแล้ว - รอสายงาน IPCD พิจารณา";

        $html = self::renderTemplate('user_supervisor_approval', [
            'booking' => $booking
        ]);

        // Get CC emails: system settings + driver + passengers
        $settings = self::getModuleSettings(EmailConfig::getModuleId());
        $bookingCc = self::buildBookingCcList($booking, $userEmail);
        $allCc = array_merge(
            !empty($settings['cc_emails']) ? array_map('trim', explode(',', $settings['cc_emails'])) : [],
            $bookingCc
        );
        return self::sendMail($userEmail, $subject, $html, null, array_unique(array_filter($allCc)));
    }

    /**
     * Send Dorm Request Received Email
     */
    public static function sendDormRequestReceived($userEmail, $userName, $type)
    {
        $subject = "ได้รับคำขอเข้าพัก/ย้ายหอพักเรียบร้อยแล้ว";
        $html = self::renderTemplate('request_received', [
            'userName' => $userName,
            'type' => $type
        ], 'Dormitory');
        $settings = self::getModuleSettings(20); // Dormitory Module ID
        return self::sendMail($userEmail, $subject, $html, null, $settings['cc_emails']);
    }

    /**
     * Send Dorm Request Approved Email
     */
    public static function sendDormRequestApproved($userEmail, $userName, $type, $keyDate, $remark, $roomNumber = null, $floor = null, $building = null)
    {
        $subject = "คำขอหอพักของคุณได้รับการอนุมัติ";
        $html = self::renderTemplate('request_approved', [
            'userName' => $userName,
            'type' => $type,
            'keyDate' => $keyDate,
            'remark' => $remark,
            'roomNumber' => $roomNumber,
            'floor' => $floor,
            'building' => $building
        ], 'Dormitory');
        $settings = self::getModuleSettings(20);
        return self::sendMail($userEmail, $subject, $html, null, $settings['cc_emails']);
    }

    /**
     * Send Dorm Request Rejected Email
     */
    public static function sendDormRequestRejected($userEmail, $userName, $type, $reason, $rejectedBy = 'ผู้ดูแลระบบ')
    {
        $subject = "คำขอหอพักของคุณถูกปฏิเสธ";
        $html = self::renderTemplate('request_rejected', [
            'userName' => $userName,
            'type' => $type,
            'reason' => $reason,
            'rejectedBy' => $rejectedBy
        ], 'Dormitory');
        $settings = self::getModuleSettings(20);
        return self::sendMail($userEmail, $subject, $html, null, $settings['cc_emails']);
    }

    /**
     * Send supervisor approved notification to requester (Dormitory)
     * Notify user that supervisor has approved, now waiting for manager/IPCD
     */
    public static function sendDormSupervisorApprovedEmail($userEmail, $userName, $type)
    {
        $subject = "หัวหน้างานอนุมัติคำขอหอพักของคุณแล้ว - รอผู้ดูแลดำเนินการ";
        $html = self::renderTemplate('supervisor_approved', [
            'userName' => $userName,
            'type' => $type
        ], 'Dormitory');
        $settings = self::getModuleSettings(20);
        return self::sendMail($userEmail, $subject, $html, null, $settings['cc_emails']);
    }

    /**
     * Send supervisor approval request for Dormitory
     */
    public static function sendDormSupervisorApprovalEmail($booking, $supervisorEmail, $token)
    {
        $baseUrl = Env::getBaseUrl();
        $userName = $booking['fullname'] ?? $booking['requester_name'] ?? 'ผู้ใช้งาน';
        $subject = "การขออนุมัติบริการหอพัก - {$userName}";

        $reviewUrl = rtrim($baseUrl, '/') . "/Modules/Dormitory/index.php?page=booking_manage&id=" . urlencode($booking['id'] ?? '');

        $html = self::renderTemplate('supervisor_approval', [
            'booking' => $booking,
            'token' => $token,
            'baseUrl' => $baseUrl,
            'review_url' => $reviewUrl
        ], 'Dormitory');

        $settings = self::getModuleSettings(20);
        return self::sendMail($supervisorEmail, $subject, $html, null, $settings['cc_emails']);
    }

    /**
     * Send Notification to Admin when new request received
     */
    public static function sendDormRequestNotificationToAdmin($bookingData)
    {
        $userName = $bookingData['fullname'];
        $requestType = $bookingData['request_type'];

        $subject = "มีคำขอหอพักใหม่: $userName ($requestType)";

        $baseUrl = Env::getBaseUrl();
        $manageUrl = rtrim($baseUrl, '/') . "/Modules/Dormitory/index.php?page=booking_manage";

        $html = self::renderTemplate('admin_notification', [
            'booking' => $bookingData,
            'manageUrl' => $manageUrl
        ], 'Dormitory');

        $settings = self::getModuleSettings(20);
        $adminEmails = $settings['admin_emails'];

        if (empty($adminEmails)) {
            error_log("No admin emails configured for Dormitory Module (ID 20)");
            return false;
        }

        $sent = false;
        $adminList = array_map('trim', explode(',', $adminEmails));
        foreach ($adminList as $email) {
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $sent = self::sendMail($email, $subject, $html) || $sent;
            }
        }
        return $sent;
    }

    /**
     * Render email template
     */
    private static function renderTemplate($templateName, $data, $module = 'CarBooking')
    {
        $templatePath = __DIR__ . "/../../Modules/$module/Views/emails/$templateName.php";

        // Fallback to other module if not found (legacy support)
        if (!file_exists($templatePath)) {
            $otherModule = ($module === 'CarBooking') ? 'Dormitory' : 'CarBooking';
            $templatePath = __DIR__ . "/../../Modules/$otherModule/Views/emails/$templateName.php";
        }

        if (!file_exists($templatePath)) {
            error_log("Email template not found: $templatePath");
            return "<p>Template error</p>";
        }

        // Default favicon for all email templates
        if (empty($data['favicon_url'])) {
            $baseUrl = Env::getBaseUrl();
            // Check if we're in Docker (DocumentRoot = public/) or XAMPP (DocumentRoot = htdocs/)
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            $hasPublicAssets = $docRoot && is_dir($docRoot . '/assets');
            $assetPath = $hasPublicAssets ? '/assets/' : '/public/assets/';
            $data['favicon_url'] = rtrim($baseUrl, '/') . $assetPath . 'images/brand/inteqc-logo.png';
        }

        // Extract variables for template
        extract($data);

        // Start output buffering
        ob_start();
        include $templatePath;
        $html = ob_get_clean();

        return $html;
    }
}
