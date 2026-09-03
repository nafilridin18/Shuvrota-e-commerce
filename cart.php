<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// যদি 'Add to Cart' ফর্ম থেকে POST রিকোয়েস্ট আসে
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $qty        = intval($_POST['quantity'] ?? 1);
    $size       = trim($_POST['size'] ?? 'Free Size');
    $color      = trim($_POST['color'] ?? 'Standard');

    try {
        // প্রোডাক্টের তথ্য এবং প্রথম ছবি ডাটাবেজ থেকে আনা
        $stmt = $pdo->prepare("
            SELECT p.*, 
            (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as img 
            FROM products p WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            $title = $product['name'];
            // ডিসকাউন্ট প্রাইस থাকলে সেটি ধরবে, নাojle রেগুলার প্রাইস
            $price = (!empty($product['discount_price']) && $product['discount_price'] > 0) ? $product['discount_price'] : $product['price'];
            $image = $product['img'] ?? '';

            // ইউনিক কার্ট আইটেম চাবি (product_id + size + color)
            $cart_key = $product_id . '_' . md5($size . '_' . $color);

            if (isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$cart_key] = [
                    'id'    => $product_id,
                    'title' => $title,
                    'price' => $price,
                    'qty'   => $qty,
                    'size'  => $size,
                    'color' => $color,
                    'image' => $image
                ];
            }
        }
    } catch (Exception $e) {
        // Ignore or handle error
    }

    header('Location: cart.php');
    exit;
}

// কার্ট থেকে আইটেম মুছে ফেলার লজিক
if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $key = $_GET['key'];
    unset($_SESSION['cart'][$key]);
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <title>শপিং কার্ট - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .lang-en { display: none; }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
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

<div class="container my-5" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            <i class="fa-solid fa-bag-shopping me-2"></i>
            <span class="lang-bn">আপনার শপিং কার্ট</span>
            <span class="lang-en">Your Shopping Cart</span>
        </h2>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i> 
            <span class="lang-bn">কেনাকাটা চালিয়ে যান</span>
            <span class="lang-en">Continue Shopping</span>
        </a>
    </div>

    <?php if(empty($cart)): ?>
        <div class="card p-5 text-center shadow-sm rounded-4 border-0">
            <h4 class="text-muted mb-3">
                <span class="lang-bn">আপনার কার্ট খালি!</span>
                <span class="lang-en">Your cart is empty!</span>
            </h4>
            <a href="index.php" class="btn btn-danger rounded-pill align-self-center px-4">
                <span class="lang-bn">শপিং শুরু করুন</span>
                <span class="lang-en">Start Shopping</span>
            </a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">
                                <span class="lang-bn">পণ্য</span><span class="lang-en">Product</span>
                            </th>
                            <th>
                                <span class="lang-bn">সাইজ/কালার</span><span class="lang-en">Size/Color</span>
                            </th>
                            <th>
                                <span class="lang-bn">দাম</span><span class="lang-en">Price</span>
                            </th>
                            <th>
                                <span class="lang-bn">পরিমাণ</span><span class="lang-en">Qty</span>
                            </th>
                            <th>
                                <span class="lang-bn">মোট</span><span class="lang-en">Total</span>
                            </th>
                            <th class="text-center">
                                <span class="lang-bn">মুছুন</span><span class="lang-en">Action</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart as $key => $item): ?>
                            <?php 
                                $img_src = !empty($item['image']) ? 'uploads/' . htmlspecialchars($item['image']) : 'assets/images/default.jpg';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= $img_src ?>" width="50" height="50" class="rounded object-fit-cover">
                                        <span class="fw-semibold"><?= htmlspecialchars($item['title']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['size']) ?> / <?= htmlspecialchars($item['color']) ?></span>
                                </td>
                                <td>৳ <?= number_format($item['price'], 2) ?></td>
                                <td><?= $item['qty'] ?></td>
                                <td class="fw-bold text-danger">৳ <?= number_format($item['price'] * $item['qty'], 2) ?></td>
                                <td class="text-center">
                                    <a href="cart.php?action=remove&key=<?= $key ?>" class="btn btn-sm btn-outline-danger rounded-circle"><i class="fa-solid fa-trash-can"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-4 border-0 shadow-sm rounded-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fs-5 text-muted">
                    <span class="lang-bn">পণ্যের মোট দাম:</span>
                    <span class="lang-en">Subtotal:</span>
                </span>
                <span class="fs-4 fw-bold text-dark">৳ <?= number_format($subtotal, 2) ?></span>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <a href="checkout.php" class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow">
                    <span class="lang-bn">চেকআউট করুন</span>
                    <span class="lang-en">Proceed to Checkout</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer class="bg-dark text-light pt-4 pb-3 mt-auto text-center small">
    <p class="text-muted mb-0">&copy; 2026 Shuvrota. <span class="lang-bn">সর্বস্বত্ব সংরক্ষিত।</span><span class="lang-en">All rights reserved.</span></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchLanguage(lang) {
    const bnElements = document.querySelectorAll('.lang-bn');
    const enElements = document.querySelectorAll('.lang-en');
    if (lang === 'en') {
        bnElements.forEach(el => el.style.display = 'none');
        enElements.forEach(el => el.style.display = 'inline');
        document.getElementById('currentLangText').innerText = 'English';
        localStorage.setItem('selectedLang', 'en');
    } else {
        enElements.forEach(el => el.style.display = 'none');
        bnElements.forEach(el => el.style.display = 'inline');
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