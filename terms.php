<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

// ইউজার সেশন হ্যান্ডলিং
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

$customer_id = $_SESSION['customer_id'] ?? 0;
$wishlist_count = 0;
$user_wishlist_ids = [];

if ($customer_id > 0) {
    try {
        $w_stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
        $w_stmt->execute([$customer_id]);
        $wishlist_count = $w_stmt->fetchColumn();

        $uw_stmt = $pdo->prepare("SELECT product_id FROM wishlists WHERE customer_id = ?");
        $uw_stmt->execute([$customer_id]);
        $user_wishlist_ids = $uw_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}
}

// ইন-পেজ উইশলিস্ট হ্যান্ডলার
if (isset($_GET['action']) && $_GET['action'] === 'wishlist' && isset($_GET['id'])) {
    if (!isset($_SESSION['customer_id'])) {
        header("Location: login.php");
        exit();
    }
    $product_id = (int)$_GET['id'];
    if ($product_id > 0) {
        try {
            $check = $pdo->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
            $check->execute([$customer_id, $product_id]);
            if ($check->rowCount() > 0) {
                $del = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
                $del->execute([$customer_id, $product_id]);
            } else {
                $ins = $pdo->prepare("INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
                $ins->execute([$customer_id, $product_id]);
            }
        } catch (Exception $e) {}
    }
    header("Location: index.php" . (!empty($_GET['category_id']) ? '?category_id=' . $_GET['category_id'] : ''));
    exit();
}

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$selected_category = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $cat_stmt = $pdo->query("SELECT * FROM categories");
    $categories = $cat_stmt->fetchAll();

    $products = [];
    if ($selected_category > 0 || !empty($search_keyword) || isset($_GET['show_products'])) {
        $query = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as img FROM products p WHERE p.status = 'published'";
        $params = [];

        if ($selected_category > 0) {
            $query .= " AND p.category_id = ?";
            $params[] = $selected_category;
        }

        if (!empty($search_keyword)) {
            $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search_keyword%";
            $params[] = "%$search_keyword%";
        }

        $query .= " ORDER BY p.id DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $products = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>শুভ্রতা - Shuvrota E-commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* মাল্টি-ল্যাঙ্গুয়েজ কন্ট্রোল স্টাইল */
        body.lang-bn-mode .lang-en { display: none !important; }
        body.lang-bn-mode .lang-bn { display: inline-block !important; }
        body.lang-en-mode .lang-bn { display: none !important; }
        body.lang-en-mode .lang-en { display: inline-block !important; }

        .sub-navbar { background-color: #111; padding: 10px 0; }
        .sub-navbar a { color: #fff; text-decoration: none; font-weight: 500; font-size: 14px; margin-right: 25px; transition: color 0.2s; }
        .sub-navbar a:hover { color: #dc3545; }

        .hero-video-container {
            position: relative;
            width: 100%;
            height: 520px;
            overflow: hidden;
            background: #000;
        }
        .hero-video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.75;
        }
        .hero-video-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
            text-align: center;
            padding: 20px;
            background: rgba(0,0,0,0.35);
        }

        .collection-card {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            height: 380px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .collection-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .collection-card:hover img {
            transform: scale(1.06);
        }
        .collection-overlay {
            position: absolute;
            bottom: 0; left: 0; width: 100%;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: #fff;
            padding: 25px 20px;
            text-align: center;
        }
        .product-card img { height: 220px; object-fit: cover; }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100 lang-bn-mode">

<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <button class="btn btn-outline-dark border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas" aria-controls="menuOffcanvas">
            <i class="fa-solid fa-bars fs-5"></i>
        </button>

        <a class="navbar-brand fw-bold text-danger fs-3 tracking-wider" href="index.php">
            <i class="fa-solid fa-gem me-1"></i><span class="lang-bn">শুভ্রতা</span><span class="lang-en">Shuvrota</span>
        </a>

        <form action="index.php" method="GET" class="d-none d-md-flex mx-auto" style="width: 300px;">
            <div class="input-group">
                <input type="text" name="search" class="form-control form-control-sm rounded-start-pill ps-3" placeholder="Search products..." value="<?= htmlspecialchars($search_keyword) ?>">
                <button class="btn btn-dark btn-sm rounded-end-pill px-3" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <a href="wishlist.php" class="text-dark text-decoration-none d-flex align-items-center gap-1 small fw-semibold">
                <i class="fa-regular fa-heart fs-5 text-danger"></i>
                <span class="d-none d-lg-inline"><span class="lang-bn">উইশলিস্ট</span><span class="lang-en">My Wish List</span></span>
                <span class="badge bg-danger rounded-pill"><?= $wishlist_count ?></span>
            </a>

            <a href="cart.php" class="text-dark text-decoration-none d-flex align-items-center gap-1 small fw-semibold">
                <i class="fa-solid fa-bag-shopping fs-5 text-dark"></i>
                <span class="d-none d-lg-inline"><span class="lang-bn">কার্ট</span><span class="lang-en">Shopping Cart</span></span>
                <span class="badge bg-dark rounded-pill"><?= $cart_count ?></span>
            </a>

            <?php if (isset($_SESSION['customer_id'])): ?>
                <div class="dropdown">
                    <a class="dropdown-toggle text-dark text-decoration-none fw-bold small" href="#" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-user-circle text-danger fs-5"></i> <?= htmlspecialchars($_SESSION['customer_name'] ?? 'User') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item small text-danger fw-bold" href="logout.php"><i class="fa-solid fa-right-from-bracket me-1"></i> Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="text-dark text-decoration-none small fw-bold">
                    <span class="lang-bn">লগইন</span><span class="lang-en">Sign In</span>
                </a>
            <?php endif; ?>

            <div class="dropdown">
                <a class="btn btn-sm btn-outline-secondary px-2 py-1 dropdown-toggle rounded-pill small" href="#" data-bs-toggle="dropdown" id="currentLangText">বাংলা</a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('bn')">বাংলা (BN)</a></li>
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('en')">English (EN)</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Sub Navbar -->
<div class="sub-navbar d-none d-lg-block">
    <div class="container text-uppercase">
        <a href="index.php"><span class="lang-bn">হোম</span><span class="lang-en">Home</span></a>
        <?php foreach($categories as $cat): ?>
            <a href="index.php?category_id=<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></a>
        <?php endforeach; ?>
        <a href="track.php" class="float-end text-warning"><i class="fa-solid fa-truck-fast me-1"></i> <span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Order Tracking</span></a>
    </div>
</div>

<!-- Offcanvas Sidebar Menu -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="menuOffcanvas" aria-labelledby="menuOffcanvasLabel">
    <div class="offcanvas-header bg-dark text-white">
        <h5 class="offcanvas-title" id="menuOffcanvasLabel"><i class="fa-solid fa-gem text-danger me-2"></i> Shuvrota Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <h6 class="text-muted text-uppercase fw-bold small mb-3">Categories</h6>
        <ul class="list-unstyled">
            <li class="mb-2"><a href="index.php" class="text-dark text-decoration-none fw-semibold"><i class="fa-solid fa-angle-right text-danger me-2"></i> Home</a></li>
            <?php foreach($categories as $cat): ?>
                <li class="mb-2"><a href="index.php?category_id=<?= $cat['id'] ?>" class="text-dark text-decoration-none fw-semibold"><i class="fa-solid fa-angle-right text-danger me-2"></i> <?= htmlspecialchars($cat['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <hr>
        <ul class="list-unstyled">
            <li class="mb-2"><a href="track.php" class="text-dark text-decoration-none"><i class="fa-solid fa-truck-fast me-2 text-danger"></i> Order Tracking</a></li>
            <li class="mb-2"><a href="cart.php" class="text-dark text-decoration-none"><i class="fa-solid fa-bag-shopping me-2 text-danger"></i> Shopping Cart</a></li>
            <li class="mb-2"><a href="wishlist.php" class="text-dark text-decoration-none"><i class="fa-regular fa-heart me-2 text-danger"></i> My Wish List</a></li>
        </ul>
    </div>
</div>

<?php if ($selected_category > 0 || !empty($search_keyword) || isset($_GET['show_products'])): ?>
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">
                <span class="lang-bn">প্রোডাক্ট তালিকা</span>
                <span class="lang-en">Product List</span>
            </h2>
            <a href="index.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                <span class="lang-bn">হোমে ফিরে যান</span>
                <span class="lang-en">Back to Home</span>
            </a>
        </div>

        <div class="row row-cols-1 row-cols-md-4 g-4">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $prod): ?>
                    <?php $is_in_wishlist = in_array($prod['id'], $user_wishlist_ids); ?>
                    <div class="col">
                        <div class="card product-card h-100 shadow-sm border-0 rounded-3 position-relative">
                            <a href="index.php?action=wishlist&id=<?= $prod['id'] ?><?= $selected_category > 0 ? '&category_id='.$selected_category : '' ?>" class="position-absolute top-0 end-0 m-3 text-danger bg-white p-2 rounded-circle shadow-sm text-decoration-none z-3">
                                <i class="<?= $is_in_wishlist ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
                            </a>
                            <a href="product-details.php?id=<?= $prod['id'] ?>">
                                <img src="<?= !empty($prod['img']) ? 'uploads/' . htmlspecialchars($prod['img']) : 'assets/images/default.jpg' ?>" class="card-img-top" alt="Product">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fs-6 fw-bold">
                                    <a href="product-details.php?id=<?= $prod['id'] ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($prod['name']) ?></a>
                                </h5>
                                <div class="card-text mt-auto mb-3">
                                    <?php if (!empty($prod['discount_price']) && $prod['discount_price'] > 0): ?>
                                        <span class="text-danger fw-bold">৳ <?= number_format($prod['discount_price'], 2) ?></span>
                                        <span class="text-muted text-decoration-line-through small ms-1">৳ <?= number_format($prod['price'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold">৳ <?= number_format($prod['price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="product-details.php?id=<?= $prod['id'] ?>" class="btn btn-outline-danger btn-sm w-100 rounded-pill">
                                    <span class="lang-bn">বিস্তারিত দেখুন</span>
                                    <span class="lang-en">View Details</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h4 class="text-muted">
                        <span class="lang-bn">এই ক্যাটাগরিতে কোনো পণ্য পাওয়া যায়নি!</span>
                        <span class="lang-en">No products found in this category!</span>
                    </h4>
                    <a href="index.php" class="btn btn-danger mt-3 rounded-pill px-4">
                        <span class="lang-bn">হোমে ফিরে যান</span>
                        <span class="lang-en">Back to Home</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- হেরিটেজ ভিডিও সেকশন -->
    <div class="hero-video-container">
        <video autoplay muted loop playsinline>
            <source src="assets/videos/heritage-craft.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="hero-video-overlay">
            <h1 class="display-4 fw-bold mb-3">
                <span class="lang-bn">হাতে বোনা ঐতিহ্য ও ভালোবাসা</span>
                <span class="lang-en">Weaving Heritage & Stories</span>
            </h1>
            <p class="lead mb-4">
                <span class="lang-bn">কিভাবে আমাদের প্রতিটি অনন্য পণ্য নিখুঁতভাবে তৈরি হয়, তা দেখুন।</span>
                <span class="lang-en">Discover how every single piece is handcrafted by our skilled artisans.</span>
            </p>
            <a href="#collections" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold">
                <span class="lang-bn">কালেকশন দেখুন</span>
                <span class="lang-en">Explore Collections</span>
            </a>
        </div>
    </div>

    <!-- কালেকশন সেকশন -->
    <div class="container my-5" id="collections">
        <div class="text-center mb-5">
            <h2 class="fw-bold">
                <span class="lang-bn">আমাদের এক্সক্লুসিভ কালেকশনসমূহ</span>
                <span class="lang-en">Our Exclusive Collections</span>
            </h2>
            <p class="text-muted">
                <span class="lang-bn">নারী কারিগরদের নিপুণ হাতে তৈরি ঐতিহ্যবাহী পোশাক ও হস্তশিল্প</span>
                <span class="lang-en">Traditional wear and handicrafts crafted by women artisans</span>
            </p>
        </div>

        <div class="row row-cols-1 row-cols-md-4 g-4">
            <?php if (!empty($categories)): ?>
                <?php 
                    $cat_images = [
                        'Saree' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?auto=format&fit=crop&w=600&q=80',
                        'Kurti' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?auto=format&fit=crop&w=600&q=80',
                        'Crafts' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80'
                    ];
                    $default_img = 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=600&q=80';

                    foreach($categories as $cat): 
                        $c_name = $cat['name'];
                        $img_url = $default_img;
                        foreach($cat_images as $key => $img) {
                            if(stripos($c_name, $key) !== false) {
                                $img_url = $img;
                                break;
                            }
                        }
                ?>
                    <div class="col">
                        <div class="collection-card">
                            <img src="<?= $img_url ?>" alt="<?= htmlspecialchars($c_name) ?>">
                            <div class="collection-overlay">
                                <h5 class="fw-bold mb-2"><?= htmlspecialchars($c_name) ?> <span class="lang-bn">কালেকশন</span><span class="lang-en">Collection</span></h5>
                                <a href="index.php?category_id=<?= $cat['id'] ?>" class="btn btn-sm btn-light rounded-pill px-3 fw-bold">
                                    <span class="lang-bn">কেনাকাটা করুন</span><span class="lang-en">Shop Now</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">
                    <span class="lang-bn">কোনো ক্যাটাগরি পাওয়া যায়নি।</span>
                    <span class="lang-en">No categories found.</span>
                </p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Footer Section -->
<footer class="bg-dark text-light pt-5 pb-3 mt-auto">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="fw-bold text-warning mb-3">Shuvrota</h5>
                <p class="text-light small">
                    <span class="lang-bn">Shuvrota একটি community-driven social enterprise, যা হরিজন/দলিত সম্প্রদায়ের নারী কারিগরদের ক্ষমতায়নে কাজ করে।</span>
                    <span class="lang-en">Shuvrota is a community-driven social enterprise working to empower women artisans from Harijan/Dalit communities.</span>
                </p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold text-warning mb-3">
                    <span class="lang-bn">জরুরি লিংক</span>
                    <span class="lang-en">Quick Links</span>
                </h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="about.php" class="text-decoration-none text-light"><span class="lang-bn">আমাদের সম্পর্কে</span><span class="lang-en">About Us</span></a></li>
                    <li class="mb-2"><a href="delivery-policy.php" class="text-decoration-none text-light"><span class="lang-bn">ডেলিভারি পলিসি</span><span class="lang-en">Delivery Policy</span></a></li>
                    <li class="mb-2"><a href="refund-policy.php" class="text-decoration-none text-light"><span class="lang-bn">রিটার্ন ও রিফান্ড পলিসি</span><span class="lang-en">Return & Refund Policy</span></a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold text-warning mb-3">
                    <span class="lang-bn">যোগাযোগ করুন</span>
                    <span class="lang-en">Contact Us</span>
                </h6>
                <p class="text-light small mb-2"><i class="fa-solid fa-location-dot me-2 text-warning"></i>Bridge More, Mymensingh, Bangladesh</p>
                <p class="text-light small mb-2"><i class="fa-solid fa-phone me-2 text-warning"></i>01719844226</p>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="text-center text-light small">
            &copy; 2026 Shuvrota. <span class="lang-bn">সর্বস্বত্ব সংরক্ষিত।</span><span class="lang-en">All rights reserved.</span>
        </div>
    </div>
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