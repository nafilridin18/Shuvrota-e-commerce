<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// সেশনে আইডি থাকলে কিন্তু নাম/ফোন না থাকলে ডাটাবেজ থেকে টেনে সেশনে বসিয়ে দেওয়া
if (isset($_SESSION['customer_id']) && empty($_SESSION['customer_name'])) {
    try {
        $stmt = $pdo->prepare("SELECT name, phone FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $custData = $stmt->fetch();
        if ($custData) {
            $_SESSION['customer_name']  = $custData['name'];
            $_SESSION['customer_phone'] = $custData['phone'];
        }
    } catch (Exception $e) {
        // Ignore
    }
}

// নেভিগেশনের জন্য নিজস্ব ভ্যারিয়েবল (পেজ-লেভেল ভ্যারিয়েবলের সাথে যেন সংঘর্ষ না হয়)
$__customer_id    = $_SESSION['customer_id'] ?? 0;
$__cart_count     = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$__wishlist_count = 0;

if ($__customer_id > 0) {
    try {
        $w_stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
        $w_stmt->execute([$__customer_id]);
        $__wishlist_count = (int)$w_stmt->fetchColumn();
    } catch (Exception $e) {
        $__wishlist_count = 0;
    }
}

try {
    $__nav_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $__nav_categories = [];
}
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'শুভ্রতা - Shuvrota' ?></title>
    <meta name="description" content="শুভ্রতা — হাতে বোনা শাড়ি, কুর্তি ও হস্তশিল্প। নারী কারিগরদের নিপুণ হাতে তৈরি ঐতিহ্যবাহী পোশাক।">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,600&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (!empty($extraStyles)): ?>
        <style><?= $extraStyles ?></style>
    <?php endif; ?>
</head>
<body class="lang-bn-mode d-flex flex-column min-vh-100">

<!-- Top Navigation -->
<nav class="navbar navbar-expand-lg site-navbar sticky-top py-2" id="siteNavbar">
    <div class="container">
        <button class="navbar-toggler border-0 shadow-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas" aria-controls="menuOffcanvas" aria-label="মেনু খুলুন">
            <i class="fa-solid fa-bars fs-5"></i>
        </button>

        <a class="navbar-brand fs-3 me-3" href="index.php">
            <i class="fa-solid fa-gem me-1"></i><span class="lang-bn">শুভ্রতা</span><span class="lang-en">Shuvrota</span>
        </a>

        <form action="index.php" method="GET" class="search-form-inline d-none d-md-flex mx-auto" style="width: 320px;">
            <input type="hidden" name="show_products" value="1">
            <div class="input-group">
                <input type="text" name="search" class="form-control ps-3" placeholder="শাড়ি, কুর্তি খুঁজুন..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button class="btn" type="submit" aria-label="খুঁজুন"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <a href="wishlist.php" class="nav-icon-link d-none d-sm-inline-flex">
                <i class="fa-regular fa-heart fs-5"></i>
                <span class="d-none d-lg-inline"><span class="lang-bn">উইশলিস্ট</span><span class="lang-en">Wishlist</span></span>
                <?php if ($__wishlist_count > 0): ?><span class="icon-badge"><?= $__wishlist_count ?></span><?php endif; ?>
            </a>

            <a href="cart.php" class="nav-icon-link">
                <i class="fa-solid fa-bag-shopping fs-5"></i>
                <span class="d-none d-lg-inline"><span class="lang-bn">কার্ট</span><span class="lang-en">Cart</span></span>
                <?php if ($__cart_count > 0): ?><span class="icon-badge"><?= $__cart_count ?></span><?php endif; ?>
            </a>

            <?php if ($__customer_id > 0): ?>
                <div class="dropdown">
                    <a class="nav-icon-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-user-circle fs-5"></i>
                        <span class="d-none d-lg-inline"><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Account') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2">
                        <li><a class="dropdown-item small" href="wishlist.php"><i class="fa-regular fa-heart me-2"></i><span class="lang-bn">উইশলিস্ট</span><span class="lang-en">Wishlist</span></a></li>
                        <li><a class="dropdown-item small" href="track.php"><i class="fa-solid fa-truck-fast me-2"></i><span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Track Order</span></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small fw-bold text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i><span class="lang-bn">লগআউট</span><span class="lang-en">Logout</span></a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-icon-link d-none d-sm-inline-flex">
                    <i class="fa-regular fa-user fs-5"></i>
                    <span class="d-none d-lg-inline"><span class="lang-bn">লগইন</span><span class="lang-en">Sign In</span></span>
                </a>
            <?php endif; ?>

            <div class="dropdown">
                <a class="btn btn-sm btn-outline-secondary px-2 py-1 dropdown-toggle rounded-pill small" href="#" data-bs-toggle="dropdown" id="currentLangText">বাংলা</a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('bn'); return false;">বাংলা (BN)</a></li>
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('en'); return false;">English (EN)</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Category Sub-navbar (desktop) -->
<div class="sub-navbar d-none d-lg-block">
    <div class="container text-uppercase">
        <a href="index.php"><span class="lang-bn">হোম</span><span class="lang-en">Home</span></a>
        <a href="index.php?show_products=1"><span class="lang-bn">সকল পণ্য</span><span class="lang-en">Shop All</span></a>
        <?php foreach ($__nav_categories as $__cat): ?>
            <a href="index.php?category_id=<?= (int)$__cat['id'] ?>"><?= htmlspecialchars($__cat['name']) ?></a>
        <?php endforeach; ?>
        <a href="track.php" class="float-end text-warning"><i class="fa-solid fa-truck-fast me-1"></i> <span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Order Tracking</span></a>
    </div>
</div>

<!-- Mobile Offcanvas Menu -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="menuOffcanvas" aria-labelledby="menuOffcanvasLabel">
    <div class="offcanvas-header bg-custom-dark text-white">
        <h5 class="offcanvas-title" id="menuOffcanvasLabel">
            <i class="fa-solid fa-gem text-warning me-2"></i><span class="lang-bn">শুভ্রতা মেনু</span><span class="lang-en">Shuvrota Menu</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form action="index.php" method="GET" class="mb-4 d-md-none">
            <input type="hidden" name="show_products" value="1">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="খুঁজুন..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button class="btn btn-brand" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <h6 class="text-uppercase fw-bold small mb-3 text-ink-muted"><span class="lang-bn">ক্যাটাগরি</span><span class="lang-en">Categories</span></h6>
        <ul class="list-unstyled">
            <li class="mb-2"><a href="index.php" class="text-dark text-decoration-none fw-semibold"><i class="fa-solid fa-angle-right text-danger me-2"></i> <span class="lang-bn">হোম</span><span class="lang-en">Home</span></a></li>
            <li class="mb-2"><a href="index.php?show_products=1" class="text-dark text-decoration-none fw-semibold"><i class="fa-solid fa-angle-right text-danger me-2"></i> <span class="lang-bn">সকল পণ্য</span><span class="lang-en">Shop All</span></a></li>
            <?php foreach ($__nav_categories as $__cat): ?>
                <li class="mb-2"><a href="index.php?category_id=<?= (int)$__cat['id'] ?>" class="text-dark text-decoration-none fw-semibold"><i class="fa-solid fa-angle-right text-danger me-2"></i> <?= htmlspecialchars($__cat['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <hr>
        <ul class="list-unstyled">
            <li class="mb-2"><a href="track.php" class="text-dark text-decoration-none"><i class="fa-solid fa-truck-fast me-2 text-danger"></i> <span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Order Tracking</span></a></li>
            <li class="mb-2"><a href="cart.php" class="text-dark text-decoration-none"><i class="fa-solid fa-bag-shopping me-2 text-danger"></i> <span class="lang-bn">শপিং কার্ট</span><span class="lang-en">Shopping Cart</span></a></li>
            <li class="mb-2"><a href="wishlist.php" class="text-dark text-decoration-none"><i class="fa-regular fa-heart me-2 text-danger"></i> <span class="lang-bn">উইশলিস্ট</span><span class="lang-en">My Wish List</span></a></li>
            <?php if ($__customer_id > 0): ?>
                <li class="mb-2"><a href="logout.php" class="text-danger text-decoration-none fw-bold"><i class="fa-solid fa-right-from-bracket me-2"></i> <span class="lang-bn">লগআউট</span><span class="lang-en">Logout</span></a></li>
            <?php else: ?>
                <li class="mb-2"><a href="login.php" class="text-dark text-decoration-none"><i class="fa-regular fa-user me-2 text-danger"></i> <span class="lang-bn">লগইন</span><span class="lang-en">Login</span></a></li>
                <li class="mb-2"><a href="register.php" class="text-dark text-decoration-none"><i class="fa-solid fa-user-plus me-2 text-danger"></i> <span class="lang-bn">রেজিস্টার</span><span class="lang-en">Register</span></a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<main class="flex-grow-1">
