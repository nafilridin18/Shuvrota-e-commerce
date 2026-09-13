<?php
require_once __DIR__ . '/config/session.php';
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

    // ডাইনামিক ব্যানার ও কালেকশন ইমেজ ফেচ করা
    $banner_stmt = $pdo->query("SELECT * FROM site_banners");
    $site_banners = [];
    while ($row = $banner_stmt->fetch()) {
        $site_banners[$row['section_key']] = $row;
    }

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
    $site_banners = [];
}

$showingProductGrid = ($selected_category > 0 || !empty($search_keyword) || isset($_GET['show_products']));
$pageTitle = 'শুভ্রতা - Shuvrota E-commerce';
include __DIR__ . '/includes/header.php';
?>

<?php if ($showingProductGrid): ?>
    <!-- ================= PRODUCT LISTING ================= -->
    <div class="container my-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h2 class="fw-bold mb-0">
                <span class="lang-bn"><?= !empty($search_keyword) ? 'অনুসন্ধানের ফলাফল' : 'প্রোডাক্ট তালিকা' ?></span>
                <span class="lang-en"><?= !empty($search_keyword) ? 'Search Results' : 'Product List' ?></span>
            </h2>
            <a href="index.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>
                <span class="lang-bn">হোমে ফিরে যান</span>
                <span class="lang-en">Back to Home</span>
            </a>
        </div>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $prod): ?>
                    <?php $is_in_wishlist = in_array($prod['id'], $user_wishlist_ids); ?>
                    <div class="col">
                        <div class="card product-card h-100 position-relative">
                            <a href="index.php?action=wishlist&id=<?= $prod['id'] ?><?= $selected_category > 0 ? '&category_id='.$selected_category : '' ?>"
                               class="wishlist-toggle position-absolute top-0 end-0 m-2 p-2 rounded-circle text-decoration-none z-3"
                               aria-label="উইশলিস্টে যোগ/বাদ দিন">
                                <i class="<?= $is_in_wishlist ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
                            </a>
                            <a href="product-details.php?id=<?= $prod['id'] ?>">
                                <img src="<?= !empty($prod['img']) ? 'uploads/' . htmlspecialchars($prod['img']) : 'assets/images/default.jpg' ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy">
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
                    <i class="fa-solid fa-box-open fs-1 text-ink-muted mb-3 d-block"></i>
                    <h4 class="text-muted">
                        <span class="lang-bn">কোনো পণ্য পাওয়া যায়নি!</span>
                        <span class="lang-en">No products found!</span>
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
    <!-- ================= HERO ================= -->
    <div class="hero-video-container">
        <?php if (isset($site_banners['hero_banner'])): ?>
            <?php if ($site_banners['hero_banner']['media_type'] === 'video'): ?>
                <video autoplay muted loop playsinline>
                    <source src="<?= htmlspecialchars($site_banners['hero_banner']['media_path']) ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            <?php else: ?>
                <img src="<?= htmlspecialchars($site_banners['hero_banner']['media_path']) ?>" alt="Hero Banner">
            <?php endif; ?>
        <?php else: ?>
            <video autoplay muted loop playsinline>
                <source src="assets/videos/heritage-craft.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        <?php endif; ?>

        <div class="hero-video-overlay">
            <h1 class="display-4 fw-bold mb-3">
                <span class="lang-bn"><?= isset($site_banners['hero_banner']['title']) && !empty($site_banners['hero_banner']['title']) ? htmlspecialchars($site_banners['hero_banner']['title']) : 'হাতে বোনা ঐতিহ্য ও ভালোবাসা' ?></span>
                <span class="lang-en">Weaving Heritage & Stories</span>
            </h1>
            <p class="lead mb-4">
                <span class="lang-bn">কিভাবে আমাদের প্রতিটি অনন্য পণ্য নিখুঁতভাবে তৈরি হয়, তা দেখুন।</span>
                <span class="lang-en">Discover how every single piece is handcrafted by our skilled artisans.</span>
            </p>
            <a href="#collections" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold">
                <span class="lang-bn">কালেকশন দেখুন</span>
                <span class="lang-en">Explore Collections</span>
            </a>
        </div>
    </div>

    <!-- ================= COLLECTIONS ================= -->
    <div class="container my-5" id="collections">
        <div class="text-center mb-5">
            <h2 class="section-heading">
                <span class="lang-bn">আমাদের এক্সক্লুসিভ কালেকশনসমূহ</span>
                <span class="lang-en">Our Exclusive Collections</span>
            </h2>
            <p class="text-muted mt-3">
                <span class="lang-bn">নারী কারিগরদের নিপুণ হাতে তৈরি ঐতিহ্যবাহী পোশাক ও হস্তশিল্প</span>
                <span class="lang-en">Traditional wear and handicrafts crafted by women artisans</span>
            </p>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-3 g-md-4">
            <?php if (!empty($categories)): ?>
                <?php
                    $fallback_images = [
                        'Saree' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?auto=format&fit=crop&w=600&q=80',
                        'Kurti' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?auto=format&fit=crop&w=600&q=80',
                        'Crafts' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80'
                    ];
                    $default_img = 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=600&q=80';

                    foreach($categories as $cat):
                        $c_name = $cat['name'];
                        $s_key = 'cat_img_' . $cat['id'];

                        if (isset($site_banners[$s_key]) && !empty($site_banners[$s_key]['media_path'])) {
                            $img_url = $site_banners[$s_key]['media_path'];
                        } else {
                            $img_url = $default_img;
                            foreach($fallback_images as $key => $img) {
                                if(stripos($c_name, $key) !== false) {
                                    $img_url = $img;
                                    break;
                                }
                            }
                        }
                ?>
                    <div class="col">
                        <div class="collection-card">
                            <img src="<?= htmlspecialchars($img_url) ?>" alt="<?= htmlspecialchars($c_name) ?>" loading="lazy">
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
                    <span class="lang-bn">কোনো ক্যাটাগরি পাওয়া যায়নি।</span>
                    <span class="lang-en">No categories found.</span>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================= TRUST STRIP ================= -->
    <div class="container mb-5">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <i class="fa-solid fa-hand-holding-heart fs-3 text-warning mb-2 d-block"></i>
                <div class="small fw-semibold">
                    <span class="lang-bn">হাতে তৈরি, যত্নে গড়া</span><span class="lang-en">Handcrafted with Care</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <i class="fa-solid fa-truck-fast fs-3 text-warning mb-2 d-block"></i>
                <div class="small fw-semibold">
                    <span class="lang-bn">দ্রুত ডেলিভারি</span><span class="lang-en">Fast Delivery</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <i class="fa-solid fa-money-bill-wave fs-3 text-warning mb-2 d-block"></i>
                <div class="small fw-semibold">
                    <span class="lang-bn">ক্যাশ অন ডেলিভারি</span><span class="lang-en">Cash on Delivery</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <i class="fa-brands fa-whatsapp fs-3 text-warning mb-2 d-block"></i>
                <div class="small fw-semibold">
                    <span class="lang-bn">সরাসরি সাপোর্ট</span><span class="lang-en">Direct Support</span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
