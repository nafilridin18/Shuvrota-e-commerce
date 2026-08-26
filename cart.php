<?php
session_start();
require_once 'config/database.php';

if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
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
                'qty' => 1
            ];
        }
        $msg = "কার্টে যোগ করা হয়েছে!";
    }
}

$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>শুভ্রতা - Shuvrota E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .whatsapp-float {
            position: fixed; bottom: 20px; right: 20px;
            background: #25d366; color: white; border-radius: 50px;
            padding: 10px 20px; font-weight: bold; text-decoration: none; box-shadow: 2px 2px 10px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🛍️ শুভ্রতা (Shuvrota)</a>
    <div>
        <a href="track.php" class="btn btn-outline-info me-2"><i class="fa-solid fa-truck"></i> ট্র্যাকিং</a>
        <a href="cart.php" class="btn btn-outline-light"><i class="fa-solid fa-cart-shopping"></i> কার্ট (<?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>)</a>
    </div>
  </div>
</nav>

<div class="container my-5">
    <?php if(isset($msg)): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>
    
    <h2 class="text-center mb-4">আমাদের কালেকশন</h2>
    <div class="row g-4">
        <?php foreach($products as $product): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <span class="badge bg-secondary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                        <h5 class="card-title"><?= htmlspecialchars($product['title']) ?></h5>
                        <p class="card-text text-muted"><?= htmlspecialchars($product['description']) ?></p>
                        <h6 class="text-primary fw-bold">৳ <?= number_format($product['price'], 2) ?></h6>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-dark w-100"><i class="fa-solid fa-cart-plus"></i> কার্টে যোগ করুন</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<a href="https://wa.me/8801700000000" class="whatsapp-float" target="_blank">
    <i class="fa-brands fa-whatsapp"></i> হোয়াটসঅ্যাপ সাপোর্ট
</a>

</body>
</html>