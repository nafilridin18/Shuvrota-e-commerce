<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'];

    if (!empty($name) && !empty($phone) && !empty($password)) {
        $chk = $pdo->prepare("SELECT id FROM customers WHERE phone = ?");
        $chk->execute([$phone]);
        if ($chk->fetch()) {
            $error = "এই মোবাইল নম্বরটি দিয়ে ইতোমধ্যে একটি অ্যাকাউন্ট রয়েছে।";
        } else {
            $pass_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, password_hash, is_guest) VALUES (?, ?, ?, ?, 0)");

            if ($stmt->execute([$name, $phone, $email ?: null, $pass_hash])) {
                $customer_id = $pdo->lastInsertId();
                $_SESSION['customer_id']   = $customer_id;
                $_SESSION['customer_name'] = $name;
                $_SESSION['customer_phone']= $phone;
                header('Location: index.php');
                exit;
            } else {
                $error = "অ্যাকাউন্ট তৈরিতে সমস্যা হয়েছে। আবার চেষ্টা করুন।";
            }
        }
    } else {
        $error = "প্রয়োজনীয় ঘরগুলো পূরণ করুন।";
    }
}

$pageTitle = 'রেজিস্ট্রেশন - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-5 min-h-60 d-flex align-items-center justify-content-center" style="max-width: 450px;">
    <div class="card p-4 p-md-5 auth-card rounded-4 w-100">
        <div class="text-center mb-4">
            <i class="fa-solid fa-gem fs-1 text-warning"></i>
        </div>
        <h3 class="fw-bold text-center mb-4">
            <span class="lang-bn">নতুন অ্যাকাউন্ট খুলুন</span><span class="lang-en">Create New Account</span>
        </h3>
        <?php if($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small"><span class="lang-bn">আপনার নাম *</span><span class="lang-en">Your Name *</span></label>
                <input type="text" name="name" class="form-control rounded-pill" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small"><span class="lang-bn">মোবাইল নম্বর *</span><span class="lang-en">Mobile Number *</span></label>
                <input type="text" name="phone" class="form-control rounded-pill" placeholder="017XXXXXXXX" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small"><span class="lang-bn">ইমেইল (ঐচ্ছিক)</span><span class="lang-en">Email (Optional)</span></label>
                <input type="email" name="email" class="form-control rounded-pill">
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold small"><span class="lang-bn">পাসওয়ার্ড *</span><span class="lang-en">Password *</span></label>
                <input type="password" name="password" class="form-control rounded-pill" required>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold rounded-pill py-2">
                <span class="lang-bn">রেজিস্টার করুন</span><span class="lang-en">Register</span>
            </button>
        </form>
        <p class="text-center mt-4 small mb-0">
            <span class="lang-bn">ইতোমধ্যে অ্যাকাউন্ট আছে? <a href="login.php" class="text-danger fw-bold">লগইন করুন</a></span>
            <span class="lang-en">Already have an account? <a href="login.php" class="text-danger fw-bold">Login here</a></span>
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
