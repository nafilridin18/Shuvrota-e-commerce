<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';
require_once __DIR__ . '/../config/mail.php';

$message = '';
$messageType = 'info';

$adminId = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_2fa'])) {
    csrf_require();
    $enable = $_POST['toggle_2fa'] === '1' ? 1 : 0;
    $pdo->prepare("UPDATE admins SET two_factor_enabled = ? WHERE id = ?")->execute([$enable, $adminId]);
    $message = $enable
        ? 'ইমেইল যাচাইকরণ (2FA) চালু করা হয়েছে। পরবর্তী লগইন থেকে একটি কোড ইমেইলে পাঠানো হবে।'
        : 'ইমেইল যাচাইকরণ (2FA) বন্ধ করা হয়েছে।';
    $messageType = 'success';
}

$stmt = $pdo->prepare("SELECT two_factor_enabled, email FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();
$isEnabled = !empty($admin['two_factor_enabled']);

$pageTitle     = 'Security (2FA) — শুভ্রতা এডমিন';
$pageHeadingBn = 'নিরাপত্তা সেটিংস (2FA)';
$pageHeadingEn = 'Security Settings (2FA)';
$activePage    = 'security';
include 'includes/header.php';
?>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-shield-halved me-2"></i>
                    <span class="lang-bn">দুই-ধাপ যাচাইকরণ (2FA)</span><span class="lang-en">Two-Factor Authentication</span>
                </span>
            </div>
            <div class="admin-card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> py-2 small"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <p class="text-muted small">
                    চালু থাকলে, পাসওয়ার্ড সঠিক দেওয়ার পরেও লগইন সম্পন্ন করতে
                    <strong><?= htmlspecialchars($admin['email']) ?></strong>
                    ঠিকানায় পাঠানো একটি ৬-সংখ্যার কোড দিতে হবে। এটি চালু রাখা জোরালোভাবে সুপারিশ করা হয়, বিশেষত অ্যাডমিন প্যানেলের জন্য।
                </p>

                <?php if (defined('MAIL_DEV_MODE') && MAIL_DEV_MODE): ?>
                    <div class="alert alert-warning py-2 small">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <strong>SMTP এখনো কনফিগার করা হয়নি।</strong> কোড এখন শুধু <code>storage/dev_mail_log.txt</code> ফাইলে লেখা হবে, প্রকৃত ইমেইলে যাবে না।
                        লাইভ সাইটে <code>config/mail.php</code>-তে আপনার আসল মেইল সার্ভারের তথ্য দিন।
                    </div>
                <?php endif; ?>

                <div class="d-flex align-items-center justify-content-between p-3 rounded mt-3"
                     style="background: rgba(244,236,223,0.5); border: 1px solid var(--border-soft);">
                    <div>
                        <div class="fw-bold">ইমেইল OTP লগইন</div>
                        <div class="small text-muted">বর্তমান অবস্থা:
                            <?php if ($isEnabled): ?>
                                <span class="badge" style="background:#2e7d32;">চালু / ON</span>
                            <?php else: ?>
                                <span class="badge" style="background:#c62828;">বন্ধ / OFF</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <form method="POST" class="m-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="toggle_2fa" value="<?= $isEnabled ? '0' : '1' ?>">
                        <?php if ($isEnabled): ?>
                            <button type="submit" class="admin-btn admin-btn-sm" style="background:#fee; color:#c62828; border:1.5px solid #c62828;">
                                <i class="fa-solid fa-toggle-off"></i> বন্ধ করুন
                            </button>
                        <?php else: ?>
                            <button type="submit" class="admin-btn admin-btn-gold admin-btn-sm">
                                <i class="fa-solid fa-toggle-on"></i> চালু করুন
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
