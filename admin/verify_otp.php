<?php
require_once __DIR__ . '/../config/session.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/otp.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

if (!otp_has_pending_challenge()) {
    header('Location: login.php');
    exit;
}

$error = '';
$notice = '';
$pending = $_SESSION['admin_2fa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    if (isset($_POST['resend'])) {
        $wait = otp_seconds_until_resend_allowed();
        if ($wait > 0) {
            $error = "আরেকটি কোড চাওয়ার আগে {$wait} সেকেন্ড অপেক্ষা করুন।";
        } else {
            $adminForResend = ['id' => $pending['admin_id'], 'name' => $pending['name'], 'email' => $pending['email']];
            if (otp_start_challenge($adminForResend)) {
                $notice = 'একটি নতুন কোড পাঠানো হয়েছে।';
                $pending = $_SESSION['admin_2fa'];
            } else {
                $error = 'কোড পাঠাতে সমস্যা হয়েছে। আবার চেষ্টা করুন।';
            }
        }
    } elseif (isset($_POST['verify_code'])) {
        $code = trim($_POST['code'] ?? '');
        $result = otp_verify($code);

        switch ($result) {
            case 'ok':
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? AND is_active = 1");
                $stmt->execute([$pending['admin_id']]);
                $admin = $stmt->fetch();

                if ($admin) {
                    otp_clear();
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id']        = $admin['id'];
                    $_SESSION['admin_name']      = $admin['name'];
                    $_SESSION['admin_email']     = $admin['email'];

                    $pdo->prepare("UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?")
                        ->execute([$_SERVER['REMOTE_ADDR'] ?? null, $admin['id']]);

                    header('Location: index.php');
                    exit;
                }
                $error = 'অ্যাকাউন্ট খুঁজে পাওয়া যায়নি।';
                break;

            case 'expired':
                $error = 'কোডের মেয়াদ শেষ হয়ে গেছে। আবার লগইন করুন।';
                break;

            case 'too_many_attempts':
                $error = 'অনেকবার ভুল চেষ্টা করা হয়েছে। আবার লগইন করুন।';
                break;

            default: // invalid
                $error = 'ভুল কোড! আবার চেষ্টা করুন।';
        }
    }
}

$devHint = (defined('MAIL_DEV_MODE') && MAIL_DEV_MODE);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login — Shuvrota Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>

<div class="admin-login-wrap">
    <div class="admin-login-card">
        <div class="admin-login-logo">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h3 class="text-center fw-bold mb-1" style="font-family:'Playfair Display',serif; color:#7b1113;">যাচাইকরণ কোড দিন</h3>
        <p class="text-center text-muted small mb-4">
            <?= htmlspecialchars($pending['email']) ?> ঠিকানায় একটি ৬-সংখ্যার কোড পাঠানো হয়েছে
        </p>

        <?php if ($devHint): ?>
            <div class="alert alert-info py-2 small text-center">
                <i class="fa-solid fa-flask me-1"></i> Dev mode: real email is not configured yet — check <code>storage/dev_mail_log.txt</code> for the code.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small text-center"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($notice): ?>
            <div class="alert alert-success py-2 small text-center"><i class="fa-solid fa-check me-1"></i><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="form-label">৬-সংখ্যার কোড</label>
                <input type="text" name="code" class="form-control form-control-lg text-center" style="letter-spacing:8px; font-size:1.5rem;" maxlength="6" pattern="\d{6}" inputmode="numeric" autofocus required>
            </div>
            <button type="submit" name="verify_code" value="1" class="admin-btn admin-btn-primary w-100 justify-content-center mb-2" style="padding: 12px;">
                <i class="fa-solid fa-check"></i> Verify & Sign In
            </button>
        </form>

        <form method="POST" class="text-center mt-2">
            <?= csrf_field() ?>
            <button type="submit" name="resend" value="1" class="btn btn-link btn-sm">কোড আবার পাঠান / Resend code</button>
        </form>

        <p class="text-center mt-3">
            <a href="login.php" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> লগইনে ফিরে যান</a>
        </p>
    </div>
</div>

</body>
</html>
