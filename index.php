<?php
session_start();
require_once 'config/database.php';

$message = '';

if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $size       = isset($_POST['size']) ? trim($_POST['size']) : 'Free Size';
    $color      = isset($_POST['color']) ? trim($_POST['color']) : 'Default';

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'published'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
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
        $message = "Product added to cart!";
    }
}

$products = $pdo->query("SELECT p.*, c.name as category_name, 
       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as primary_image
        FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published' ORDER BY p.id DESC")->fetchAll();

include 'includes/header.php';
?>

<div class="hero-banner mb-5">
    <div class="container">
        <h1 class="display-5 fw-bold">Shubhrata</h1>
        <p class="fs-5 text-light">Weaving stories, creating opportunities.</p>
    </div>
</div>

<div class="container mb-5">
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <h3 class="fw-bold border-start border-4 border-danger ps-3 mb-4">
        <span class="lang-bn">আমাদের হস্তশিল্প কালেকশন</span>
        <span class="lang-en">Our Artisan Collection</span>
    </h3>

    <div class="row g-4">
        <?php foreach($products as $p): ?>
            <?php 
                $img = $p['primary_image'] ?? 'default.jpg';
                $img_src = (strpos($img, 'http') === 0) ? $img : 'uploads/' . $img;
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="card card-product h-100 shadow-sm border-0">
                    <img src="<?= htmlspecialchars($img_src) ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($p['name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <span class="badge badge-cat align-self-start mb-2 px-2 py-1 rounded"><?= htmlspecialchars($p['category_name']) ?></span>
                        <h5 class="card-title text-dark fw-bold h6"><?= htmlspecialchars($p['name']) ?></h5>
                        <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($p['short_description']) ?></p>
                        
                        <div class="mt-2 mb-3">
                            <span class="fs-5 fw-bold text-danger">৳ <?= number_format($p['discount_price'] ?? $p['price'], 2) ?></span>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-custom w-100 shadow-sm">
                                <span class="lang-bn">কার্টে যোগ করুন</span>
                                <span class="lang-en">Add to Cart</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>