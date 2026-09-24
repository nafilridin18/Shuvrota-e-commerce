<?php
/**
 * includes/mailer.php
 * Thin wrapper around PHPMailer for sending the admin 2FA code.
 *
 * send_email() returns true/false and never throws — a mail failure
 * should show a friendly retry message, not a fatal error.
 */

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function send_email(string $toEmail, string $toName, string $subject, string $bodyHtml, string $bodyText): bool
{
    // ---- DEV MODE: no real SMTP configured yet, or running locally ----
    // Log the email instead of sending it, so the 2FA flow is fully
    // testable before real mailbox credentials are filled in.
    if (MAIL_DEV_MODE) {
        $logDir = __DIR__ . '/../storage';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $entry = "==== " . date('Y-m-d H:i:s') . " ====\nTo: {$toName} <{$toEmail}>\nSubject: {$subject}\n\n{$bodyText}\n\n";
        @file_put_contents($logDir . '/dev_mail_log.txt', $entry, FILE_APPEND);
        error_log("[DEV MODE] Email to {$toEmail}: {$subject} — see storage/dev_mail_log.txt (configure config/mail.php to send real emails)");
        return true;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_SMTP_USERNAME;
        $mail->Password   = MAIL_SMTP_PASSWORD;
        $mail->SMTPSecure = MAIL_SMTP_ENCRYPTION;
        $mail->Port       = MAIL_SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyText;

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('send_email failed: ' . $e->getMessage());
        return false;
    }
}
