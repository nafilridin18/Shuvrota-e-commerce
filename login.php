<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone    = trim($_POST['phone']);
    $password = $_POST['password'];

    if (!empty($phone) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = ? AND is_active = 1");
        $stmt->execute([$phone]);
        $customer = $stmt->fetch();

        if ($customer && $customer['password_hash'] && password_verify($password, $customer['password_hash'])) {
            $_SESSION['customer_id']    = $customer['id'];
            $_SESSION['customer_name']  = $customer['name'];
            $_SESSION['customer_phone'] = $customer['phone'];

            header('Location: index.php');
            exit;
        } else {
            $error = "মোবাইল নম্বর অথবা পাসওয়ার্ড ভুল হয়েছে!";
        }
    } else {
        $error = "সবগুলো ঘর পূরণ করুন।";
    }
}

$pageTitle = 'লগইন - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-5 min-h-60 d-flex align-items-center justify-content-center" style="max-width: 420px;">
    <div class="card p-4 p-md-5 auth-card rounded-4 w-100">
        <div class="text-center mb-4">
            <i class="fa-solid fa-gem fs-1 text-warning"></i>
        </div>
        <h3 class="fw-bold text-center mb-4">
            <span class="lang-bn">কাস্টমার লগইন</span><span class="lang-en">Customer Login</span>
        </h3>
        <?php if($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small"><span class="lang-bn">মোবাইল নম্বর</span><span class="lang-en">Mobile Number</span></label>
                <input type="text" name="phone" class="form-control rounded-pill" placeholder="017XXXXXXXX" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold small"><span class="lang-bn">পাসওয়ার্ড</span><span class="lang-en">Password</span></label>
                <input type="password" name="password" class="form-control rounded-pill" required>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold rounded-pill py-2">
                <span class="lang-bn">লগইন</span><span class="lang-en">Login</span>
            </button>
        </form>
        <p class="text-center mt-4 small mb-0">
            <span class="lang-bn">নতুন কাস্টমার? <a href="register.php" class="text-danger fw-bold">রেজিস্ট্রেশন করুন</a></span>
            <span class="lang-en">New Customer? <a href="register.php" class="text-danger fw-bold">Register here</a></span>
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
