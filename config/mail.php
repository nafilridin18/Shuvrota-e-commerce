<?php
// config/mail.php
//
// SMTP settings used to send the admin 2FA login code.
//
// ==== FILL THESE IN WITH A REAL MAILBOX BEFORE GOING LIVE ====
// Any of these work fine:
//   - Your cPanel email account (cPanel > Email Accounts > Connect Devices
//     shows the exact host/port for that mailbox — usually mail.yourdomain.com,
//     port 587, username = the full email address, password = its mailbox password).
//   - A transactional email service's SMTP (Brevo, SendGrid, Mailgun, etc.) —
//     these are more reliable for deliverability than a shared-hosting mailbox.
//   - Gmail SMTP with an "App Password" (not your normal Gmail password).
//
// DEV_MODE: while MAIL_SMTP_HOST is still the placeholder below, or on your
// local XAMPP box, no real email is sent. Instead the OTP code is written to
// storage/dev_mail_log.txt and shown directly on the verification page, so
// you can test the whole 2FA flow without any mail server configured.

define('MAIL_SMTP_HOST', 'smtp.example.com');       // <-- e.g. mail.shuvrota.com or smtp.brevo.com
define('MAIL_SMTP_PORT', 587);                       // 587 = STARTTLS (recommended), 465 = SSL
define('MAIL_SMTP_ENCRYPTION', 'tls');               // 'tls' for port 587, 'ssl' for port 465
define('MAIL_SMTP_USERNAME', 'you@example.com');     // <-- your real mailbox address
define('MAIL_SMTP_PASSWORD', 'PUT_THE_REAL_MAILBOX_PASSWORD_HERE');
define('MAIL_FROM_ADDRESS', 'no-reply@shuvrota.com'); // <-- shown as the "From" address
define('MAIL_FROM_NAME', 'Shuvrota Admin Security');

define('MAIL_DEV_MODE', MAIL_SMTP_HOST === 'smtp.example.com');
