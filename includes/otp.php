<?php
/**
 * includes/otp.php
 * Email-based 2FA for admin login.
 *
 * The pending OTP lives only in the session between "password correct"
 * and "code verified" — it's short-lived (10 minutes), single-use, and
 * never touches the database, so no schema change was needed beyond the
 * `two_factor_enabled` column that already existed on `admins`.
 */

require_once __DIR__ . '/mailer.php';

const OTP_LENGTH           = 6;
const OTP_TTL_SECONDS       = 600; // 10 minutes
const OTP_MAX_ATTEMPTS      = 5;
const OTP_RESEND_COOLDOWN   = 45;  // seconds between resend requests

function otp_generate(): string
{
    $max = 10 ** OTP_LENGTH - 1;
    return str_pad((string) random_int(0, $max), OTP_LENGTH, '0', STR_PAD_LEFT);
}

/**
 * Starts (or restarts) a pending 2FA challenge for this admin and emails
 * the code. Called right after the password check succeeds.
 */
function otp_start_challenge(array $admin): bool
{
    $code = otp_generate();

    $_SESSION['admin_2fa'] = [
        'admin_id'    => (int)$admin['id'],
        'name'        => $admin['name'],
        'email'       => $admin['email'],
        'code_hash'   => password_hash($code, PASSWORD_BCRYPT),
        'expires_at'  => time() + OTP_TTL_SECONDS,
        'attempts'    => 0,
        'last_sent_at' => time(),
    ];

    return otp_send_email($admin['name'], $admin['email'], $code);
}

function otp_send_email(string $name, string $email, string $code): bool
{
    $subject = "আপনার Shuvrota Admin লগইন কোড: {$code}";
    $bodyText = "প্রিয় {$name},\n\nআপনার অ্যাডমিন লগইন যাচাইকরণ কোড: {$code}\n\nএই কোডটি " . (OTP_TTL_SECONDS / 60) . " মিনিটের জন্য বৈধ থাকবে। আপনি যদি লগইন করার চেষ্টা না করে থাকেন, এই ইমেইলটি উপেক্ষা করুন এবং আপনার পাসওয়ার্ড পরিবর্তন করুন।\n\n— Shuvrota Security";
    $bodyHtml = "<div style='font-family:sans-serif;max-width:480px;margin:auto'>"
        . "<h2 style='color:#7b1113'>Shuvrota Admin Login Code</h2>"
        . "<p>প্রিয় " . htmlspecialchars($name) . ",</p>"
        . "<p>আপনার লগইন যাচাইকরণ কোড:</p>"
        . "<p style='font-size:32px;font-weight:bold;letter-spacing:6px;background:#f5f5f5;padding:16px;text-align:center;border-radius:8px'>{$code}</p>"
        . "<p>এই কোডটি <strong>" . (OTP_TTL_SECONDS / 60) . " মিনিট</strong> বৈধ থাকবে।</p>"
        . "<p style='color:#888;font-size:13px'>আপনি যদি এই লগইন অনুরোধ না করে থাকেন, এই ইমেইলটি উপেক্ষা করুন এবং অবিলম্বে আপনার পাসওয়ার্ড পরিবর্তন করুন।</p>"
        . "</div>";

    return send_email($email, $name, $subject, $bodyHtml, $bodyText);
}

function otp_has_pending_challenge(): bool
{
    return !empty($_SESSION['admin_2fa']['admin_id']);
}

function otp_seconds_until_resend_allowed(): int
{
    $last = $_SESSION['admin_2fa']['last_sent_at'] ?? 0;
    return max(0, OTP_RESEND_COOLDOWN - (time() - $last));
}

/**
 * Returns: 'ok' | 'expired' | 'too_many_attempts' | 'invalid'
 */
function otp_verify(string $submittedCode): string
{
    $pending = $_SESSION['admin_2fa'] ?? null;
    if (!$pending) {
        return 'expired';
    }
    if (time() > $pending['expires_at']) {
        unset($_SESSION['admin_2fa']);
        return 'expired';
    }
    if ($pending['attempts'] >= OTP_MAX_ATTEMPTS) {
        unset($_SESSION['admin_2fa']);
        return 'too_many_attempts';
    }

    if (password_verify($submittedCode, $pending['code_hash'])) {
        return 'ok';
    }

    $_SESSION['admin_2fa']['attempts']++;
    return 'invalid';
}

function otp_clear(): void
{
    unset($_SESSION['admin_2fa']);
}
