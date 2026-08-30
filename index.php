<?php
session_start();
require_once 'config/database.php';

$message = '';

// কার্ট হ্যান্ডলিং
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $size       = isset($_POST['size']) ? trim($_POST['size']) : 'Free Size';
    $color      = isset($_POST['color']) ? trim($_POST['color']) : 'Default';

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'published'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        // প্রাথমিক ছবি খোঁজা
        $img_stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC LIMIT 1");
        $img_stmt->execute([$product_id]);
        $img = $img_stmt->fetch();
        $image_path = $img ? $img['image_path'] : 'default.jpg';

        $cart_key = $product_id . '_' . $size . '_' . $color;

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['qty'] += 1;
        } else {
            $_SESSION['cart'][$cart_key] = [
                'product_id' => $product['id'],
                'title'      => $product['name'],
                'price'      => $product['discount_price'] ?? $product['price'],
                'image'      => $image_path,
                'size'       => $size,
                'color'      => $color,
                'qty'        => 1
            ];
        }
        $message = "প্রোডাক্ট সফলভাবে কার্টে যোগ করা হয়েছে!";
    }
}

// ক্যাটাগরি ফিল্টারিং
$cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$sql = "SELECT p.*, c.name as category_name, 
       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as primary_image
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'published'";

if ($cat_id > 0) {
    $sql .= " AND p.category_id = $cat_id";
}
$sql .= " ORDER BY p.id DESC";

$products = $pdo->query($sql)->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>শুভ্রতা - Shuvrota E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-custom-dark sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold fs-3 text-warning" href="index.php">🛍️ শুভ্রতা</a>
    <div class="d-flex gap-2">
        <a href="track.php" class="btn btn-outline-warning rounded-pill"><i class="fa-solid fa-truck"></i> ট্র্যাকিং</a>
        <a href="cart.php" class="btn btn-warning rounded-pill position-relative fw-bold">
            <i class="fa-solid fa-bag-shopping"></i> কার্ট
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
            </span>
        </a>
    </div>
  </div>
</nav>

<div class="hero-banner mb-5">
    <div class="container">
        <h1 class="display-4 fw-bold">শুভ্রতা এক্সক্লুসিভ কালেকশন</h1>
        <p class="fs-5 text-light">রেডি-টু-ওয়্যার শাড়ি ও সুতি প্রিমিয়াম কুর্তির বিশ্বস্ত শপ</p>
    </div>
</div>

<div class="container mb-5">
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold border-start border-4 border-danger ps-3">আমাদের কালেকশন</h3>
        <div>
            <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill me-1">সব</a>
            <?php foreach($categories as $cat): ?>
                <a href="index.php?cat=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill"><?= htmlspecialchars($cat['name_bn'] ?? $cat['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="row g-4">
        <?php if(empty($products)): ?>
            <div class="col-12 text-center py-5">
                <h4 class="text-muted">কোনো প্রোডাক্ট পাওয়া যায়নি!</h4>
            </div>
        <?php endif; ?>

        <?php foreach($products as $p): ?>
            <?php 
                $img = $p['primary_image'] ?? 'default.jpg';
                $img_src = (strpos($img, 'http') === 0) ? $img : 'uploads/' . $img;
                
                // ভ্যারিয়েন্ট বের করা
                $v_stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1");
                $v_stmt->execute([$p['id']]);
                $variants = $v_stmt->fetchAll();
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="card card-product h-100 shadow-sm border-0">
                    <img src="<?= htmlspecialchars($img_src) ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($p['name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <span class="badge badge-cat align-self-start mb-2 px-2 py-1 rounded"><?= htmlspecialchars($p['category_name']) ?></span>
                        <h5 class="card-title text-dark fw-bold h6"><?= htmlspecialchars($p['name_bn'] ?? $p['name']) ?></h5>
                        <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($p['short_description']) ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div>
                                <?php if($p['discount_price']): ?>
                                    <span class="fs-5 fw-bold text-danger">৳ <?= number_format($p['discount_price'], 2) ?></span>
                                    <small class="text-muted text-decoration-line-through">৳ <?= number_format($p['price'], 2) ?></small>
                                <?php else: ?>
                                    <span class="fs-5 fw-bold text-danger">৳ <?= number_format($p['price'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" class="mt-3">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            
                            <?php if(!empty($variants)): ?>
                                <div class="row g-1 mb-2">
                                    <div class="col-6">
                                        <select name="size" class="form-select form-select-sm">
                                            <?php foreach(array_unique(array_column($variants, 'size')) as $sz): ?>
                                                <?php if($sz): ?><option value="<?= $sz ?>"><?= $sz ?></option><?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select name="color" class="form-select form-select-sm">
                                            <?php foreach(array_unique(array_column($variants, 'color')) as $cl): ?>
                                                <?php if($cl): ?><option value="<?= $cl ?>"><?= $cl ?></option><?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <button type="submit" name="add_to_cart" class="btn btn-custom w-100 shadow-sm"><i class="fa-solid fa-cart-plus me-1"></i> কার্টে যোগ করুন</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>