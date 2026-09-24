<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';

// Load customer session name/phone if only id is present
if (isset($_SESSION['customer_id']) && empty($_SESSION['customer_name'])) {
    try {
        $stmt = $pdo->prepare("SELECT name, phone FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $custData = $stmt->fetch();
        if ($custData) {
            $_SESSION['customer_name']  = $custData['name'];
            $_SESSION['customer_phone'] = $custData['phone'];
        }
    } catch (Exception $e) {}
}

$__customer_id    = $_SESSION['customer_id'] ?? 0;
$__cart_count     = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$__wishlist_count = 0;

if ($__customer_id > 0) {
    try {
        $w_stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
        $w_stmt->execute([$__customer_id]);
        $__wishlist_count = (int)$w_stmt->fetchColumn();
    } catch (Exception $e) {}
}

try {
    $__nav_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $__nav_categories = [];
}

// Load site settings (logo, site name)
$__settings = [];
try {
    $__s_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    foreach ($__s_stmt->fetchAll() as $__row) {
        $__settings[$__row['setting_key']] = $__row['setting_value'];
    }
} catch (Exception $e) {}

$__site_logo = $__settings['site_logo'] ?? '';
$__site_name = $__settings['site_name'] ?? 'Shuvrota';
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'শুভ্রতা - Shuvrota' ?></title>
    <meta name="description" content="শুভ্রতা — হাতে বোনা শাড়ি, কুর্তি ও হস্তশিল্প। নারী কারিগরদের নিপুণ হাতে তৈরি ঐতিহ্যবাহী পোশাক।">
    <meta name="theme-color" content="#7b1113">

    <!-- ⭐ Mark that JS is available so reveal animations only hide content when JS can reveal it back. -->
    <script>document.documentElement.classList.add('js-reveal-ready');</script>

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

<!-- ==================== TOP NAV ==================== -->
<nav class="navbar navbar-expand-lg site-navbar sticky-top py-2" id="siteNavbar">
    <div class="container">

        <!-- Hamburger (mobile) -->
        <button class="navbar-toggler border-0 shadow-none me-2 d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas" aria-controls="menuOffcanvas" aria-label="Open menu">
            <i class="fa-solid fa-bars fs-5"></i>
        </button>

        <!-- Brand (logo + name) -->
        <a class="navbar-brand fs-3 me-3" href="index.php">
            <span class="brand-logo-circle">
                <?php if (!empty($__site_logo)): ?>
                    <img src="<?= htmlspecialchars($__site_logo) ?>" alt="<?= htmlspecialchars($__site_name) ?>">
                <?php else: ?>
                    <i class="fa-solid fa-gem"></i>
                <?php endif; ?>
            </span>
            <span class="brand-name">
                <span class="lang-bn">শুভ্রতা</span><span class="lang-en">Shuvrota</span>
            </span>
        </a>

        <!-- Desktop search — placeholder swaps with the active language -->
        <form action="index.php" method="GET" class="search-form-inline d-none d-md-flex mx-auto" style="width: 340px;">
            <input type="hidden" name="show_products" value="1">
            <div class="input-group">
                <input type="text" name="search" class="form-control"
                       data-placeholder-bn="শাড়ি, কুর্তি খুঁজুন..."
                       data-placeholder-en="Search Sharees, Kurtis..."
                       placeholder="শাড়ি, কুর্তি খুঁজুন..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button class="btn" type="submit" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </form>

        <!-- Actions -->
        <div class="d-flex align-items-center gap-1 gap-sm-2 ms-auto">

            <!-- Mobile search icon -->
            <a href="#" class="mobile-search-btn d-md-none" data-bs-toggle="modal" data-bs-target="#mobileSearchModal" aria-label="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </a>

            <!-- Wishlist (hidden on mobile — lives in bottom nav) -->
            <a href="wishlist.php" class="nav-icon-link d-none d-md-inline-flex">
                <i class="fa-regular fa-heart fs-5"></i>
                <span class="d-none d-lg-inline"><span class="lang-bn">উইশলিস্ট</span><span class="lang-en">Wishlist</span></span>
                <?php if ($__wishlist_count > 0): ?><span class="icon-badge"><?= $__wishlist_count ?></span><?php endif; ?>
            </a>

            <!-- Cart (hidden on mobile — lives in bottom nav) -->
            <a href="cart.php" class="nav-icon-link d-none d-md-inline-flex">
                <i class="fa-solid fa-bag-shopping fs-5"></i>
                <span class="d-none d-lg-inline"><span class="lang-bn">কার্ট</span><span class="lang-en">Cart</span></span>
                <?php if ($__cart_count > 0): ?><span class="icon-badge"><?= $__cart_count ?></span><?php endif; ?>
            </a>

            <!-- Account / login (hidden on mobile — lives in bottom nav) -->
            <?php if ($__customer_id > 0): ?>
                <div class="dropdown d-none d-md-block">
                    <a class="nav-icon-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-user-circle fs-5"></i>
                        <span class="d-none d-lg-inline"><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Account') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                        <li><a class="dropdown-item small fw-semibold" href="account.php"><i class="fa-solid fa-user-gear me-2"></i><span class="lang-bn">আমার অ্যাকাউন্ট</span><span class="lang-en">My Account</span></a></li>
                        <li><a class="dropdown-item small" href="wishlist.php"><i class="fa-regular fa-heart me-2"></i><span class="lang-bn">উইশলিস্ট</span><span class="lang-en">Wishlist</span></a></li>
                        <li><a class="dropdown-item small" href="track.php"><i class="fa-solid fa-truck-fast me-2"></i><span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Track Order</span></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small fw-bold text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i><span class="lang-bn">লগআউট</span><span class="lang-en">Logout</span></a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-icon-link d-none d-md-inline-flex">
                    <i class="fa-regular fa-user fs-5"></i>
                    <span class="d-none d-lg-inline"><span class="lang-bn">লগইন</span><span class="lang-en">Sign In</span></span>
                </a>
            <?php endif; ?>

            <!-- Language (hidden on mobile — in offcanvas menu) -->
            <div class="dropdown d-none d-md-block">
                <a class="btn btn-sm btn-outline-secondary px-3 py-1 dropdown-toggle rounded-pill small" href="#" data-bs-toggle="dropdown" id="currentLangText">বাংলা</a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3">
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('bn'); return false;">বাংলা (BN)</a></li>
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('en'); return false;">English (EN)</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- ==================== CATEGORY SUB-NAV ==================== -->
<div class="sub-navbar d-none d-lg-block">
    <div class="container text-uppercase">
        <a href="index.php"><span class="lang-bn">হোম</span><span class="lang-en">Home</span></a>
        <a href="index.php?show_products=1"><span class="lang-bn">সকল পণ্য</span><span class="lang-en">Shop All</span></a>
        <?php foreach ($__nav_categories as $__cat): ?>
            <a href="index.php?category_id=<?= (int)$__cat['id'] ?>"><?= htmlspecialchars($__cat['name']) ?></a>
        <?php endforeach; ?>
        <a href="track.php" class="float-end text-warning">
            <i class="fa-solid fa-truck-fast me-1"></i>
            <span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Order Tracking</span>
        </a>
    </div>
</div>

<!-- ==================== MOBILE MENU (OFFCANVAS) ==================== -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="menuOffcanvas" aria-labelledby="menuOffcanvasLabel">
    <div class="offcanvas-header mobile-menu-header">
        <div class="d-flex align-items-center gap-3">
            <span class="mobile-menu-logo">
                <?php if (!empty($__site_logo)): ?>
                    <img src="<?= htmlspecialchars($__site_logo) ?>" alt="Logo">
                <?php else: ?>
                    <i class="fa-solid fa-gem"></i>
                <?php endif; ?>
            </span>
            <div>
                <h5 class="mb-0 fw-bold text-white" style="font-family: var(--font-display);">
                    <span class="lang-bn">শুভ্রতা</span><span class="lang-en">Shuvrota</span>
                </h5>
                <small class="text-white-50" style="font-size: 11px; letter-spacing: 1.5px;">MENU</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <!-- Mobile search field — placeholder swaps with the active language -->
        <form action="index.php" method="GET" class="mb-4 d-md-none">
            <input type="hidden" name="show_products" value="1">
            <div class="input-group">
                <input type="text" name="search" class="form-control"
                       data-placeholder-bn="খুঁজুন..."
                       data-placeholder-en="Search..."
                       placeholder="খুঁজুন..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                       style="background: rgba(255,255,255,0.08); border-color: rgba(212,175,55,0.3); color: #f4ecdf;">
                <button class="btn btn-gold" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <div class="mobile-menu-section-title">
            <span class="lang-bn">ক্যাটাগরি</span><span class="lang-en">Categories</span>
        </div>
        <ul class="mobile-menu-list">
            <li><a href="index.php"><i class="fa-solid fa-house"></i><span class="lang-bn">হোম</span><span class="lang-en">Home</span></a></li>
            <li><a href="index.php?show_products=1"><i class="fa-solid fa-bag-shopping"></i><span class="lang-bn">সকল পণ্য</span><span class="lang-en">Shop All</span></a></li>
            <?php foreach ($__nav_categories as $__cat): ?>
                <li><a href="index.php?category_id=<?= (int)$__cat['id'] ?>"><i class="fa-solid fa-angle-right"></i><?= htmlspecialchars($__cat['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>

        <hr class="mobile-menu-divider">

        <div class="mobile-menu-section-title">
            <span class="lang-bn">অ্যাকাউন্ট</span><span class="lang-en">Account</span>
        </div>
        <ul class="mobile-menu-list">
            <?php if ($__customer_id > 0): ?>
                <li><a href="account.php"><i class="fa-solid fa-user-gear"></i><span class="lang-bn">আমার অ্যাকাউন্ট</span><span class="lang-en">My Account</span></a></li>
            <?php endif; ?>
            <li><a href="track.php"><i class="fa-solid fa-truck-fast"></i><span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Track Order</span></a></li>
            <li><a href="wishlist.php"><i class="fa-regular fa-heart"></i><span class="lang-bn">উইশলিস্ট</span><span class="lang-en">Wishlist</span></a></li>
            <li><a href="cart.php"><i class="fa-solid fa-bag-shopping"></i><span class="lang-bn">শপিং কার্ট</span><span class="lang-en">Shopping Cart</span></a></li>
        </ul>

        <hr class="mobile-menu-divider">

        <div class="mobile-menu-section-title">
            <span class="lang-bn">ভাষা</span><span class="lang-en">Language</span>
        </div>
        <ul class="mobile-menu-list">
            <li><a href="#" onclick="switchLanguage('bn'); return false;"><i class="fa-solid fa-language"></i>বাংলা (BN)</a></li>
            <li><a href="#" onclick="switchLanguage('en'); return false;"><i class="fa-solid fa-language"></i>English (EN)</a></li>
        </ul>

        <?php if ($__customer_id > 0): ?>
            <hr class="mobile-menu-divider">
            <ul class="mobile-menu-list">
                <li><a href="logout.php" style="color: #f4a3a3;"><i class="fa-solid fa-right-from-bracket" style="color: #f4a3a3;"></i><span class="lang-bn">লগআউট</span><span class="lang-en">Logout</span></a></li>
            </ul>
        <?php else: ?>
            <hr class="mobile-menu-divider">
            <ul class="mobile-menu-list">
                <li><a href="login.php"><i class="fa-regular fa-user"></i><span class="lang-bn">লগইন</span><span class="lang-en">Login</span></a></li>
                <li><a href="register.php"><i class="fa-solid fa-user-plus"></i><span class="lang-bn">রেজিস্টার</span><span class="lang-en">Register</span></a></li>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ==================== MOBILE SEARCH MODAL ==================== -->
<div class="modal fade search-modal" id="mobileSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-4">
                <form action="index.php" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="show_products" value="1">
                    <input type="search" name="search" class="form-control search-modal-input flex-grow-1"
                           data-placeholder-bn="খুঁজুন..."
                           data-placeholder-en="Search products..."
                           placeholder="খুঁজুন..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit" class="btn btn-brand rounded-pill px-4">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<main class="flex-grow-1">