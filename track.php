<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
$orders = [];
$searched = false;

if (isset($_GET['order_number']) && !empty(trim($_GET['order_number']))) {
    $searched = true;
    $search = trim($_GET['order_number']);
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? OR shipping_phone = ? ORDER BY id DESC");
    $stmt->execute([$search, $search]);
    $orders = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <title>অর্ডার ট্র্যাকিং - শুভ্রতা</title>
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

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-danger" href="index.php">
            <i class="fa-solid fa-gem me-1"></i><span class="lang-bn">শুভ্রতা</span><span class="lang-en">Shuvrota</span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a class="btn btn-outline-dark btn-sm px-3 rounded-pill" href="cart.php">
                <i class="fa-solid fa-cart-shopping me-1"></i> <span class="lang-bn">কার্ট</span><span class="lang-en">Cart</span>
            </a>
            <!-- Language Switcher -->
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

<div class="container my-5" style="max-width: 650px;">
    <h2 class="fw-bold text-center mb-4">
        <span class="lang-bn">লাইভ অর্ডার ট্র্যাকিং</span>
        <span class="lang-en">Live Order Tracking</span>
    </h2>

    <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
        <form method="GET">
            <div class="input-group input-group-lg">
                <input type="text" name="order_number" class="form-control" placeholder="অর্ডার নম্বর বা মোবাইল নম্বর দিন" value="<?= isset($_GET['order_number']) ? htmlspecialchars($_GET['order_number']) : '' ?>" required>
                <button class="btn btn-danger" type="submit">
                    <span class="lang-bn">ট্র্যাক করুন</span>
                    <span class="lang-en">Track</span>
                </button>
            </div>
        </form>
    </div>

    <?php if(!empty($orders)): ?>
        <?php foreach($orders as $order): ?>
            <?php 
                // স্ট্যাটাস অনুযায়ী অ্যাডমিন প্যানেলের সাথে মিলিয়ে কালার কোড নির্ধারণ 
                $status = strtolower(trim($order['status']));
                $badgeBg = 'bg-secondary';
                if ($status === 'new') {
                    $badgeBg = 'bg-warning text-dark';
                } elseif ($status === 'processing') {
                    $badgeBg = 'bg-info text-dark';
                } elseif ($status === 'shipped') {
                    $badgeBg = 'bg-primary text-white';
                } elseif ($status === 'delivered') {
                    $badgeBg = 'bg-success text-white';
                } elseif ($status === 'cancelled') {
                    $badgeBg = 'bg-danger text-white';
                }
            ?>
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                    <span><span class="lang-bn">অর্ডার নম্বর:</span><span class="lang-en">Order No:</span> <?= htmlspecialchars($order['order_number']) ?></span>
                    <span class="badge <?= $badgeBg ?> text-uppercase px-3 py-2 fs-6">
                         স্ট্যাটাস: <?= htmlspecialchars($order['status']) ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <p><strong><span class="lang-bn">নাম:</span><span class="lang-en">Name:</span></strong> <?= htmlspecialchars($order['shipping_name'] ?? $order['name'] ?? 'N/A') ?></p>
                    <p><strong><span class="lang-bn">মোবাইল:</span><span class="lang-en">Phone:</span></strong> <?= htmlspecialchars($order['shipping_phone'] ?? $order['phone'] ?? 'N/A') ?></p>
                    <p><strong><span class="lang-bn">ঠিকানা:</span><span class="lang-en">Address:</span></strong> <?= htmlspecialchars($order['shipping_address'] ?? $order['address'] ?? 'N/A') ?></p>
                    
                    <div class="alert alert-light border mt-3 mb-3 d-flex align-items-center justify-content-between">
                        <span><strong><span class="lang-bn">অর্ডারের বর্তমান অবস্থা:</span><span class="lang-en">Current Order Status:</span></strong></span>
                        <span class="badge <?= $badgeBg ?> text-uppercase px-3 py-2 fs-6"><?= htmlspecialchars($order['status']) ?></span>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><span class="lang-bn">সর্বমোট বিল:</span><span class="lang-en">Total Bill:</span></span>
                        <span class="fs-4 fw-bold text-danger">৳ <?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php elseif($searched): ?>
        <div class="alert alert-danger text-center">
            <span class="lang-bn">কোনো অর্ডার খুঁজে পাওয়া যায়নি!</span>
            <span class="lang-en">No order found!</span>
        </div>
    <?php endif; ?>
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