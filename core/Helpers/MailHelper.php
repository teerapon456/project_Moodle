<?php

namespace Core\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper
{
    /**
     * Send an email
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $altBody (Optional) Plain text body
     * @return bool True if sent, false otherwise
     */
    public static function send($to, $subject, $body, $altBody = '')
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = \Env::get('SMTP_HOST', 'localhost');
            $mail->Port       = (int) \Env::get('SMTP_PORT', 25);
            $mail->CharSet    = \Env::get('SMTP_CHARSET', 'UTF-8');

            // Authentication (only if username is provided)
            $smtpUser = \Env::get('SMTP_USERNAME', '');
            if (!empty($smtpUser)) {
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = \Env::get('SMTP_PASSWORD', '');
            } else {
                $mail->SMTPAuth   = false;
            }

            // Encryption
            $smtpSecure = strtolower(\Env::get('SMTP_SECURE', ''));
            if ($smtpSecure === 'tls' || $smtpSecure === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($smtpSecure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            // Recipients
            $mail->setFrom(\Env::get('SMTP_FROM_EMAIL', 'noreply@myhr.com'), \Env::get('SMTP_FROM_NAME', 'MyHR System'));
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log error
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
