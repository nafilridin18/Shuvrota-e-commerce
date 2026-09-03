<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// ইতোমধ্যে লগইন থাকলে সরাসরি ড্যাশবোর্ডে রিডাইরেক্ট
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        // পাসওয়ার্ড যাচাই (এখানে প্লেইন টেক্সট অথবা হ্যাশ উভয়ই কাজ করবে)
        if ($admin && ($password === $admin['password_hash'] || password_verify($password, $admin['password_hash']))) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $admin['id'];
            $_SESSION['admin_name']      = $admin['name'];
            $_SESSION['admin_email']     = $admin['email'];

            // লাস্ট লগইন আপডেট
            $pdo->prepare("UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?")
                ->execute([$_SERVER['REMOTE_ADDR'] ?? null, $admin['id']]);

            header('Location: index.php');
            exit;
        } else {
            $error = 'ভুল ইমেইল অথবা পাসওয়ার্ড দেওয়া হয়েছে!';
        }
    } else {
        $error = 'অনুগ্রহ করে সবগুলো ঘর পূরণ করুন।';
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>এডমিন লগইন - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

<div class="card p-4 border-0 shadow-lg rounded-4" style="max-width: 400px; width: 100%;">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-danger"><i class="fa-solid fa-lock me-2"></i>শুভ্রতা এডমিন</h3>
        <p class="text-muted small">ড্যাশবোর্ডে প্রবেশ করতে লগইন করুন</p>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger py-2 small text-center" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label class="form-label fw-bold">ইমেইল ঠিকানা</label>
            <input type="email" name="email" class="form-control" value="admin@shuvrota.com" required>
        </div>
        
        <div class="mb-4">
            <label class="form-label fw-bold">পাসওয়ার্ড</label>
            <div class="input-group">
                <input type="password" id="passwordInput" name="password" class="form-control" placeholder="123456" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="fa-solid fa-eye" id="toggleIcon"></i>
                </button>
            </div>
            <div class="form-text small text-muted mt-1">পাসওয়ার্ড দিন: <strong>123456</strong></div>
        </div>

        <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">প্রবেশ করুন</button>
    </form>
</div>

<script>
const togglePassword = document.querySelector('#togglePassword');
const passwordInput = document.querySelector('#passwordInput');
const toggleIcon = document.querySelector('#toggleIcon');

togglePassword.addEventListener('click', function () {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    toggleIcon.classList.toggle('fa-eye');
    toggleIcon.classList.toggle('fa-eye-slash');
});
</script>

</body>
</html>