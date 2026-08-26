<?php
session_start();
require_once 'config/database.php';

$message = '';
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['qty'] += 1;
        } else {
            $_SESSION['cart'][$product_id] = [
                'title' => $product['title'],
                'price' => $product['price'],
                'image' => $product['image'],
                'qty'   => 1
            ];
        }
        $message = "প্রোডাক্ট সফলভাবে কার্টে যোগ করা হয়েছে!";
    }
}

$category_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
if ($category_filter > 0) {
    $query .= " WHERE p.category_id = $category_filter";
}
$query .= " ORDER BY p.id DESC";

$products = $pdo->query($query)->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>শুভ্রতা - Shuvrota E-Commerce</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS Link -->
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

<div class="container my-5">
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <h3 class="fw-bold mb-4">আমাদের প্রিমিয়াম কালেকশন</h3>

    <div class="row g-4">
        <?php foreach($products as $product): ?>
            <?php 
                $img_path = (strpos($product['image'], 'http') === 0) ? $product['image'] : 'uploads/' . $product['image'];
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="card card-product h-100 shadow-sm border-0">
                    <img src="<?= htmlspecialchars($img_path) ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($product['title']) ?>">
                    <div class="card-body d-flex flex-column">
                        <span class="badge badge-cat align-self-start mb-2 px-2 py-1 rounded"><?= htmlspecialchars($product['category_name']) ?></span>
                        <h5 class="card-title text-dark fw-bold h6"><?= htmlspecialchars($product['title']) ?></h5>
                        <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($product['description']) ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="fs-5 fw-bold text-danger">৳ <?= number_format($product['price'], 2) ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 pb-3">
                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-custom w-100 shadow-sm"><i class="fa-solid fa-cart-plus me-1"></i> কার্টে যোগ করুন</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- JS Links -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>