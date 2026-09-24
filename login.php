<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/login_rate_limit.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phone !== '' && $password !== '') {
        if (login_is_locked_out($pdo, $phone, false)) {
            $error = login_lockout_message();
        } else {
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = ? AND is_active = 1");
            $stmt->execute([$phone]);
            $customer = $stmt->fetch();

            if ($customer && $customer['password_hash'] && password_verify($password, $customer['password_hash'])) {
                login_record_attempt($pdo, $phone, false, true);
                $_SESSION['customer_id']    = $customer['id'];
                $_SESSION['customer_name']  = $customer['name'];
                $_SESSION['customer_phone'] = $customer['phone'];
                header('Location: index.php');
                exit;
            } else {
                login_record_attempt($pdo, $phone, false, false);
                $error = "মোবাইল নম্বর অথবা পাসওয়ার্ড ভুল হয়েছে!";
            }
        }
    } else {
        $error = "সবগুলো ঘর পূরণ করুন।";
    }
}

$pageTitle = 'লগইন - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrap">
    <div class="auth-card-split">

        <!-- Illustration side -->
        <div class="auth-illustration">
            <div class="auth-illustration-content">
                <div class="auth-illustration-icon">
                    <i class="fa-solid fa-gem"></i>
                </div>
                <h2>
                    <span class="lang-bn">স্বাগতম</span>
                    <span class="lang-en">Welcome Back</span>
                </h2>
                <p>
                    <span class="lang-bn">আপনার অ্যাকাউন্টে লগইন করে উইশলিস্ট, অর্ডার এবং ব্যক্তিগত তথ্য সহজে অ্যাক্সেস করুন।</span>
                    <span class="lang-en">Sign in to access your wishlist, orders, and personalized shopping experience.</span>
                </p>
                <div class="auth-illustration-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span class="lang-bn">সুরক্ষিত সেশন</span>
                    <span class="lang-en">Secure Session</span>
                </div>
            </div>
        </div>

        <!-- Form side -->
        <div class="auth-form-side">
            <h3>
                <span class="lang-bn">কাস্টমার লগইন</span>
                <span class="lang-en">Customer Login</span>
            </h3>
            <p class="auth-sub">
                <span class="lang-bn">আপনার মোবাইল নম্বর ও পাসওয়ার্ড দিয়ে প্রবেশ করুন</span>
                <span class="lang-en">Enter your mobile number and password to continue</span>
            </p>

            <?php if ($error): ?>
                <div class="alert alert-danger small py-2">
                    <i class="fa-solid fa-circle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="on">
                <?= csrf_field() ?>
                <div class="auth-field">
                    <input type="text" name="phone" id="loginPhone" placeholder=" " required autocomplete="tel">
                    <label for="loginPhone">
                        <span class="lang-bn">মোবাইল নম্বর</span>
                        <span class="lang-en">Mobile Number</span>
                    </label>
                    <i class="fa-solid fa-phone auth-field-icon"></i>
                </div>

                <div class="auth-field">
                    <input type="password" name="password" id="loginPass" placeholder=" " required autocomplete="current-password">
                    <label for="loginPass">
                        <span class="lang-bn">পাসওয়ার্ড</span>
                        <span class="lang-en">Password</span>
                    </label>
                    <i class="fa-solid fa-lock auth-field-icon"></i>

                    <button type="button" class="auth-field-toggle" id="toggleLoginPass" aria-label="Show password">
                        <i class="fa-regular fa-eye" id="toggleLoginIcon"></i>
                    </button>
                </div>

                <button type="submit" class="auth-submit-btn">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>
                    <span class="lang-bn">লগইন করুন</span>
                    <span class="lang-en">Sign In</span>
                </button>
            </form>

            <p class="auth-switch-link">
                <span class="lang-bn">নতুন কাস্টমার? <a href="register.php">রেজিস্ট্রেশন করুন</a></span>
                <span class="lang-en">New customer? <a href="register.php">Create an account</a></span>
            </p>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn  = document.getElementById('toggleLoginPass');
    const input      = document.getElementById('loginPass');
    const icon       = document.getElementById('toggleLoginIcon');

    if (!toggleBtn || !input || !icon) return;

    toggleBtn.addEventListener('click', function () {
        const isPassword = input.type === 'password';
        input.type       = isPassword ? 'text' : 'password';
        icon.classList.toggle('fa-eye',       !isPassword);
        icon.classList.toggle('fa-eye-slash',  isPassword);
        toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>