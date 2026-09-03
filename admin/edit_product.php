<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $name_bn     = trim($_POST['name_bn']);
    $category_id = (int)$_POST['category_id'];
    $price       = (float)$_POST['price'];
    $discount    = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : NULL;
    $desc        = trim($_POST['description']);

    try {
        $update_stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, name_bn = ?, price = ?, discount_price = ?, description = ? WHERE id = ?");
        $update_stmt->execute([$category_id, $name, $name_bn, $price, $discount, $desc, $product_id]);
        
        $message = "প্রোডাক্ট সফলভাবে আপডেট করা হয়েছে!";
        
        // Refresh product data
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
    } catch (Exception $e) {
        $message = "ত্রুটি: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>প্রোডাক্ট এডিট - শুভ্রতা এডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 700px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>প্রোডাক্ট এডিট করুন</h2>
        <a href="products.php" class="btn btn-secondary">প্রোডাক্ট তালিকায় ফিরুন</a>
    </div>

    <?php if($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>

    <div class="card p-4 border-0 shadow-sm rounded-4">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">প্রোডাক্টের নাম (English)</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">প্রোডাক্টের নাম (বাংলা)</label>
                <input type="text" name="name_bn" class="form-control" value="<?= htmlspecialchars($product['name_bn'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">ক্যাটাগরি</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $product['category_id'] ? 'selected' : '' ?>><?= $c['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label fw-bold">রেগুলার প্রাইস (৳)</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?>" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label fw-bold">ডিসকাউন্ট প্রাইস (৳)</label>
                    <input type="number" step="0.01" name="discount_price" class="form-control" value="<?= $product['discount_price'] ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">বিবরণ (Description)</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold">পরিবর্তন সেভ করুন</button>
        </form>
    </div>
</div>

</body>
</html>