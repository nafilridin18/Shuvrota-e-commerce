<?php
require_once __DIR__ . '/../config/session.php';
require_once '../config/database.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// Load site logo
$__logo = '';
try {
    $__logo = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_logo'")->fetchColumn() ?: '';
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email !== '' && $password !== '') {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $admin['id'];
            $_SESSION['admin_name']      = $admin['name'];
            $_SESSION['admin_email']     = $admin['email'];

            $pdo->prepare("UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?")
                ->execute([$_SERVER['REMOTE_ADDR'] ?? null, $admin['id']]);

            header('Location: index.php');
            exit;
        } else {
            $error = 'ভুল ইমেইল অথবা পাসওয়ার্ড!';
        }
    } else {
        $error = 'সবগুলো ঘর পূরণ করুন।';
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Shuvrota</title>
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
            <?php if (!empty($__logo)): ?>
                <img src="../<?= htmlspecialchars($__logo) ?>" alt="Logo">
            <?php else: ?>
                <i class="fa-solid fa-gem"></i>
            <?php endif; ?>
        </div>

        <h3 class="text-center fw-bold mb-1" style="font-family:'Playfair Display',serif; color:#7b1113;">Shuvrota Admin</h3>
        <p class="text-center text-muted small mb-4">Sign in to continue to your dashboard</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small text-center">
                <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" id="passwordInput" name="password" class="form-control" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="admin-btn admin-btn-primary w-100 justify-content-center" style="padding: 12px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelector('#togglePassword').addEventListener('click', function () {
    const input = document.querySelector('#passwordInput');
    const icon = document.querySelector('#toggleIcon');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
});
</script>
</body>
</html>