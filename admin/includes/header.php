<?php
// admin/includes/header.php
if (!isset($pageTitle)) $pageTitle = 'Admin — Shuvrota';
if (!isset($pageHeadingBn)) $pageHeadingBn = $pageTitle;
if (!isset($pageHeadingEn)) $pageHeadingEn = $pageTitle;
if (!isset($activePage)) $activePage = '';

$__admin_name  = $_SESSION['admin_name']  ?? 'Admin';
$__admin_email = $_SESSION['admin_email'] ?? '';

$__admin_logo = '';
try {
    if (isset($pdo)) {
        $__stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_logo'");
        $__admin_logo = $__stmt->fetchColumn() ?: '';
    }
} catch (Exception $e) {}

$__nav_items = [
    'dashboard'  => ['index.php',              'fa-gauge-high',   'Dashboard',  'ড্যাশবোর্ড'],
    'products'   => ['index.php#products',     'fa-box',          'Products',   'প্রোডাক্ট'],
    'add'        => ['add_product.php',        'fa-plus',         'Add Product','নতুন প্রোডাক্ট'],
    'best'       => ['manage_best_selling.php','fa-fire',         'Best Selling','বেস্ট সেলিং'],
    'coupons'    => ['coupons.php',            'fa-ticket',       'Coupons',    'কুপন'],
    'customers'  => ['customers.php',          'fa-users',        'Customers',  'কাস্টমার'],
    'categories' => ['manage_catagories.php',  'fa-layer-group',  'Categories', 'ক্যাটাগরি'],
    'banners'    => ['banner_settings.php',    'fa-image',        'Banners & Logo','ব্যানার ও লোগো'],
    'security'   => ['two_factor_settings.php','fa-shield-halved','Security (2FA)','নিরাপত্তা (2FA)'],
];
?>
<!DOCTYPE html>
<html lang="bn" id="adminHtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body lang-bn-mode">

<div class="admin-shell">

    <!-- ============ SIDEBAR ============ -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-brand">
            <a href="index.php" class="admin-brand-link">
                <span class="admin-brand-logo">
                    <?php if (!empty($__admin_logo)): ?>
                        <img src="../<?= htmlspecialchars($__admin_logo) ?>" alt="Logo">
                    <?php else: ?>
                        <i class="fa-solid fa-gem"></i>
                    <?php endif; ?>
                </span>
                <span class="admin-brand-text">
                    <strong>Shuvrota</strong>
                    <small>Admin Panel</small>
                </span>
            </a>
            <button class="admin-sidebar-close d-lg-none" type="button" onclick="closeSidebar()" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="admin-nav">
            <?php foreach ($__nav_items as $key => $item): ?>
                <a href="<?= $item[0] ?>" class="admin-nav-link <?= $activePage === $key ? 'active' : '' ?>">
                    <i class="fa-solid <?= $item[1] ?>"></i>
                    <span class="lang-en"><?= $item[2] ?></span>
                    <span class="lang-bn"><?= $item[3] ?></span>
                </a>
            <?php endforeach; ?>

            <hr class="admin-nav-divider">

            <a href="../index.php" target="_blank" class="admin-nav-link">
                <i class="fa-solid fa-globe"></i>
                <span class="lang-en">View Website</span>
                <span class="lang-bn">ওয়েবসাইট দেখুন</span>
            </a>
            <a href="logout.php" class="admin-nav-link" style="color:#f4a3a3;">
                <i class="fa-solid fa-right-from-bracket" style="color:#f4a3a3;"></i>
                <span class="lang-en">Logout</span>
                <span class="lang-bn">লগআউট</span>
            </a>
        </nav>
    </aside>

    <div class="admin-sidebar-backdrop" id="adminBackdrop" onclick="closeSidebar()"></div>

    <!-- ============ MAIN ============ -->
    <div class="admin-main">

        <header class="admin-topbar">
            <button class="admin-menu-toggle d-lg-none" onclick="openSidebar()" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <h1 class="admin-page-title">
                <span class="lang-en"><?= htmlspecialchars($pageHeadingEn) ?></span>
                <span class="lang-bn"><?= htmlspecialchars($pageHeadingBn) ?></span>
            </h1>

            <div class="admin-topbar-actions">
                <div class="dropdown">
                    <button class="admin-lang-btn dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-globe"></i>
                        <span id="currentLangText">বাংলা</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end admin-dropdown">
                        <li><button class="dropdown-item" onclick="switchAdminLang('bn')">বাংলা (BN)</button></li>
                        <li><button class="dropdown-item" onclick="switchAdminLang('en')">English (EN)</button></li>
                    </ul>
                </div>

                <div class="dropdown">
                    <button class="admin-user-btn dropdown-toggle" data-bs-toggle="dropdown">
                        <span class="admin-user-avatar"><?= htmlspecialchars(strtoupper(substr($__admin_name, 0, 1))) ?></span>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($__admin_name) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end admin-dropdown">
                        <li><a class="dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="admin-content">