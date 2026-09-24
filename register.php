<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';
require_once __DIR__ . '/includes/csrf.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name !== '' && $phone !== '' && $password !== '') {
        // H-6: minimum password strength — previously a single-character
        // password like "1" was accepted outright.
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = "পাসওয়ার্ড কমপক্ষে ৮ ক্যারেক্টার হতে হবে এবং অক্ষর ও সংখ্যা উভয়ই থাকতে হবে। / Password must be at least 8 characters and include both letters and numbers.";
        } else {
            $chk = $pdo->prepare("SELECT id FROM customers WHERE phone = ?");
            $chk->execute([$phone]);

            // M-2: email uniqueness was never checked before insert, even
            // though the schema has a UNIQUE constraint on email — a
            // duplicate email crashed with an uncaught PDOException (HTTP 500).
            $emailTaken = false;
            if ($email !== '') {
                $emailChk = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
                $emailChk->execute([$email]);
                $emailTaken = (bool) $emailChk->fetch();
            }

            if ($chk->fetch()) {
                $error = "এই মোবাইল নম্বরটি দিয়ে ইতোমধ্যে একটি অ্যাকাউন্ট রয়েছে।";
            } elseif ($emailTaken) {
                $error = "এই ইমেইলটি দিয়ে ইতোমধ্যে একটি অ্যাকাউন্ট রয়েছে। / This email is already registered.";
            } else {
                try {
                    $pass_hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, password_hash, is_guest) VALUES (?, ?, ?, ?, 0)");
                    $stmt->execute([$name, $phone, $email ?: null, $pass_hash]);

                    $customer_id = $pdo->lastInsertId();
                    $_SESSION['customer_id']    = $customer_id;
                    $_SESSION['customer_name']  = $name;
                    $_SESSION['customer_phone'] = $phone;
                    header('Location: index.php');
                    exit;
                } catch (Exception $e) {
                    error_log('register error: ' . $e->getMessage());
                    $error = "অ্যাকাউন্ট তৈরিতে সমস্যা হয়েছে। আবার চেষ্টা করুন।";
                }
            }
        }
    } else {
        $error = "প্রয়োজনীয় ঘরগুলো পূরণ করুন।";
    }
}

$pageTitle = 'রেজিস্ট্রেশন - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrap">
    <div class="auth-card-split">

        <div class="auth-illustration">
            <div class="auth-illustration-content">
                <div class="auth-illustration-icon">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h2>
                    <span class="lang-bn">আমাদের সাথে যোগ দিন</span>
                    <span class="lang-en">Join Our Community</span>
                </h2>
                <p>
                    <span class="lang-bn">রেজিস্ট্রেশন করে দ্রুত অর্ডার, উইশলিস্ট এবং ব্যক্তিগত ড্যাশবোর্ডের সুবিধা নিন।</span>
                    <span class="lang-en">Register to enjoy faster checkout, wishlist and a personalized dashboard.</span>
                </p>
                <div class="auth-illustration-badge">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    <span class="lang-bn">হাতে তৈরি, ভালোবাসায় গড়া</span>
                    <span class="lang-en">Handcrafted with Love</span>
                </div>
            </div>
        </div>

        <div class="auth-form-side">
            <h3>
                <span class="lang-bn">নতুন অ্যাকাউন্ট</span>
                <span class="lang-en">Create Account</span>
            </h3>
            <p class="auth-sub">
                <span class="lang-bn">মাত্র কয়েক সেকেন্ডে অ্যাকাউন্ট খুলুন</span>
                <span class="lang-en">Sign up in just a few seconds</span>
            </p>

            <?php if ($error): ?>
                <div class="alert alert-danger small py-2">
                    <i class="fa-solid fa-circle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="on">
                <?= csrf_field() ?>
                <div class="auth-field">
                    <input type="text" name="name" id="regName" placeholder=" " required autocomplete="name">
                    <label for="regName">
                        <span class="lang-bn">আপনার নাম *</span>
                        <span class="lang-en">Your Name *</span>
                    </label>
                    <i class="fa-solid fa-user auth-field-icon"></i>
                </div>

                <div class="auth-field">
                    <input type="text" name="phone" id="regPhone" placeholder=" " required autocomplete="tel">
                    <label for="regPhone">
                        <span class="lang-bn">মোবাইল নম্বর *</span>
                        <span class="lang-en">Mobile Number *</span>
                    </label>
                    <i class="fa-solid fa-phone auth-field-icon"></i>
                </div>

                <div class="auth-field">
                    <input type="email" name="email" id="regEmail" placeholder=" " autocomplete="email">
                    <label for="regEmail">
                        <span class="lang-bn">ইমেইল (ঐচ্ছিক)</span>
                        <span class="lang-en">Email (Optional)</span>
                    </label>
                    <i class="fa-solid fa-envelope auth-field-icon"></i>
                </div>

                <div class="auth-field">
                    <input type="password" name="password" id="regPass" placeholder=" " required autocomplete="new-password" minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" title="At least 8 characters, including letters and numbers">
                    <label for="regPass">
                        <span class="lang-bn">পাসওয়ার্ড *</span>
                        <span class="lang-en">Password *</span>
                    </label>
                    <i class="fa-solid fa-lock auth-field-icon"></i>

                    <button type="button" class="auth-field-toggle" id="toggleRegPass" aria-label="Show password">
                        <i class="fa-regular fa-eye" id="toggleRegIcon"></i>
                    </button>
                </div>
                <p class="small text-muted mt-n2 mb-3">
                    <span class="lang-bn">কমপক্ষে ৮ ক্যারেক্টার, অক্ষর ও সংখ্যা উভয়ই থাকতে হবে</span>
                    <span class="lang-en">At least 8 characters, with both letters and numbers</span>
                </p>

                <button type="submit" class="auth-submit-btn">
                    <i class="fa-solid fa-user-plus me-2"></i>
                    <span class="lang-bn">রেজিস্টার করুন</span>
                    <span class="lang-en">Register</span>
                </button>
            </form>

            <p class="auth-switch-link">
                <span class="lang-bn">ইতোমধ্যে অ্যাকাউন্ট আছে? <a href="login.php">লগইন করুন</a></span>
                <span class="lang-en">Already have an account? <a href="login.php">Sign in</a></span>
            </p>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleRegPass');
    const input     = document.getElementById('regPass');
    const icon      = document.getElementById('toggleRegIcon');

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