<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// উইশলিস্টে নতুন প্রোডাক্ট যোগ করার লজিক
if (isset($_GET['add'])) {
    $product_id = (int)$_GET['add'];
    
    // চেক করা আগে থেকেই লিস্টে আছে কি না
    $chk = $pdo->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $chk->execute([$customer_id, $product_id]);
    
    if (!$chk->fetch()) {
        $ins = $pdo->prepare("INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
        $ins->execute([$customer_id, $product_id]);
    }
    header("Location: wishlist.php");
    exit();
}

// উইশলিস্ট থেকে রিমুভ করার লজিক
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    $del = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $del->execute([$customer_id, $remove_id]);
    header("Location: wishlist.php");
    exit();
}

// উইশলিস্টের প্রোডাক্টগুলো ফেচ করা
try {
    $stmt = $pdo->prepare("
        SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as img 
        FROM wishlists w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    $wishlist_products = $stmt->fetchAll();
} catch (Exception $e) {
    $wishlist_products = [];
}
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <title>আমার উইশলিস্ট - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-danger" href="index.php">
            <i class="fa-solid fa-gem me-1"></i> শুভ্রতা
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill">হোমে ফিরে যান</a>
    </div>
</nav>

<div class="container my-5" style="max-width: 900px;">
    <h2 class="fw-bold mb-4"><i class="fa-solid fa-heart text-danger me-2"></i> আমার উইশলিস্ট</h2>

    <?php if(empty($wishlist_products)): ?>
        <div class="card p-5 text-center shadow-sm rounded-4 border-0">
            <h4 class="text-muted mb-3">আপনার উইশলিস্ট খালি!</h4>
            <a href="index.php" class="btn btn-danger rounded-pill align-self-center px-4">শপিং শুরু করুন</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach($wishlist_products as $prod): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 rounded-3 position-relative">
                        <a href="wishlist.php?remove=<?= $prod['id'] ?>" class="position-absolute top-0 end-0 m-2 text-danger bg-white p-2 rounded-circle shadow-sm text-decoration-none z-2" title="Remove">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                        <img src="<?= !empty($prod['img']) ? 'uploads/' . htmlspecialchars($prod['img']) : 'assets/images/default.jpg' ?>" class="card-img-top object-fit-cover" style="height: 200px;" alt="Product">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fs-6 fw-bold"><?= htmlspecialchars($prod['name']) ?></h5>
                            <p class="text-danger fw-bold mb-3">৳ <?= number_format($prod['discount_price'] > 0 ? $prod['discount_price'] : $prod['price'], 2) ?></p>
                            <a href="product-details.php?id=<?= $prod['id'] ?>" class="btn btn-outline-danger btn-sm w-100 rounded-pill mt-auto">বিস্তারিত দেখুন</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="bg-dark text-light pt-4 pb-3 mt-auto text-center small">
    <p class="text-muted mb-0">&copy; 2026 Shuvrota. সর্বস্বত্ব সংরক্ষিত।</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>