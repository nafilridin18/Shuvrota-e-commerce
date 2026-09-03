<?php
if (session_status() === PHP_SESSION_NONE) session_start();
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
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <title>রেজিস্ট্রেশন - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.lang-bn-mode .lang-en { display: none !important; }
        body.lang-bn-mode .lang-bn { display: inline-block !important; }
        body.lang-en-mode .lang-bn { display: none !important; }
        body.lang-en-mode .lang-en { display: inline-block !important; }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100 lang-bn-mode">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-danger" href="index.php">
            <i class="fa-solid fa-gem me-1"></i><span class="lang-bn">শুভ্রতা</span><span class="lang-en">Shuvrota</span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a class="btn btn-outline-dark btn-sm px-3 rounded-pill" href="cart.php">
                <i class="fa-solid fa-cart-shopping me-1"></i> <span class="lang-bn">কার্ট</span><span class="lang-en">Cart</span>
            </a>
            <div class="dropdown ms-2">
                <a class="btn btn-sm btn-outline-secondary px-3 dropdown-toggle rounded-pill" href="#" role="button" data-bs-toggle="dropdown" id="currentLangText">বাংলা</a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('bn')">বাংলা (BN)</a></li>
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('en')">English (EN)</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5" style="max-width: 450px;">
    <div class="card p-4 border-0 shadow-sm rounded-4">
        <h3 class="fw-bold text-center mb-4 text-danger">
            <span class="lang-bn">নতুন অ্যাকাউন্ট খুলুন</span><span class="lang-en">Create New Account</span>
        </h3>
        <?php if($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold"><span class="lang-bn">আপনার নাম *</span><span class="lang-en">Your Name *</span></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold"><span class="lang-bn">মোবাইল নম্বর *</span><span class="lang-en">Mobile Number *</span></label>
                <input type="text" name="phone" class="form-control" placeholder="017XXXXXXXX" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold"><span class="lang-bn">ইমেইল (ঐচ্ছিক)</span><span class="lang-en">Email (Optional)</span></label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold"><span class="lang-bn">পাসওয়ার্ড *</span><span class="lang-en">Password *</span></label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold rounded-pill">
                <span class="lang-bn">রেজিস্টার করুন</span><span class="lang-en">Register</span>
            </button>
        </form>
        <p class="text-center mt-3 small">
            <span class="lang-bn">ইতোমধ্যে অ্যাকাউন্ট আছে? <a href="login.php" class="text-danger fw-bold">লগইন করুন</a></span>
            <span class="lang-en">Already have an account? <a href="login.php" class="text-danger fw-bold">Login here</a></span>
        </p>
    </div>
</div>

<footer class="bg-dark text-light pt-4 pb-3 mt-auto text-center small">
    <p class="text-muted mb-0">&copy; 2026 Shuvrota. <span class="lang-bn">সর্বস্বত্ব সংরক্ষিত।</span><span class="lang-en">All rights reserved.</span></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchLanguage(lang) {
    const body = document.body;
    if (lang === 'en') {
        body.classList.remove('lang-bn-mode');
        body.classList.add('lang-en-mode');
        document.getElementById('currentLangText').innerText = 'English';
        localStorage.setItem('selectedLang', 'en');
    } else {
        body.classList.remove('lang-en-mode');
        body.classList.add('lang-bn-mode');
        document.getElementById('currentLangText').innerText = 'বাংলা';
        localStorage.setItem('selectedLang', 'bn');
    }
}

window.onload = function() {
    const savedLang = localStorage.getItem('selectedLang') || 'bn';
    switchLanguage(savedLang);
};
</script>
</body>
</html>