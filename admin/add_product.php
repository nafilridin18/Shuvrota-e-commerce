<?php
require_once '../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price       = (float)$_POST['price'];
    $discount    = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : NULL;
    $stock       = (int)$_POST['stock'];
    $desc        = trim($_POST['description']);
    $sku         = 'SKU-' . rand(10000, 99999);
    $slug        = strtolower(str_replace(' ', '-', $name)) . '-' . rand(100, 999);

    try {
        $pdo->beginTransaction();

        // ১. প্রোডাক্ট ইনসার্ট
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, sku, description, price, discount_price, stock_quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        $stmt->execute([$category_id, $name, $slug, $sku, $desc, $price, $discount, $stock]);
        $product_id = $pdo->lastInsertId();

        // ২. ফটো আপলোড
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename);

            $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)");
            $img_stmt->execute([$product_id, $filename]);
        }

        // ৩. স্টক লগ
        $log_stmt = $pdo->prepare("INSERT INTO stock_logs (product_id, type, quantity_changed, reference_type, note) VALUES (?, 'purchase', ?, 'manual', 'Initial Stock Upload')");
        $log_stmt->execute([$product_id, $stock]);

        $pdo->commit();
        $message = "প্রোডাক্ট সফলভাবে আপলোড হয়েছে!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "এরর: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>প্রোডাক্ট আপলোড - অ্যাডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 650px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>নতুন প্রোডাক্ট আপলোড</h2>
        <a href="index.php" class="btn btn-secondary">ড্যাশবোর্ড</a>
    </div>

    <?php if($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>

    <div class="card p-4 border-0 shadow-sm rounded-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">প্রোডাক্টের নাম</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">ক্যাটাগরি</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">রেগুলার প্রাইস (৳)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">ডিসকাউন্ট প্রাইস (৳)</label>
                    <input type="number" step="0.01" name="discount_price" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">স্টক পরিমাণ</label>
                <input type="number" name="stock" class="form-control" value="10" required>
            </div>
            <div class="mb-3">
                <label class="form-label">প্রোডাক্টের ছবি</label>
                <input type="file" name="image" class="form-control" accept="image/*" required>
            </div>
            <div class="mb-3">
                <label class="form-label">বিবরণ</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold">সেভ করুন</button>
        </form>
    </div>
</div>

</body>
</html>