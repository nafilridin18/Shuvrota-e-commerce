<?php
require_once '../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $category_id = (int)$_POST['category_id'];
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $description = trim($_POST['description']);

    // ইমেজের ফোল্ডার তৈরি ও আপলোড হ্যান্ডলিং
    $image_name = 'default.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
    }

    $stmt = $pdo->prepare("INSERT INTO products (category_id, title, price, image, description, stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$category_id, $title, $price, $image_name, $description, $stock]);
    $message = "প্রোডাক্ট সফলভাবে যুক্ত হয়েছে!";
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>নতুন প্রোডাক্ট যোগ করুন - অ্যাডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>নতুন প্রোডাক্ট যোগ করুন</h2>
        <a href="index.php" class="btn btn-secondary">ড্যাশবোর্ডে ফিরুন</a>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <div class="card p-4 shadow-sm border-0 rounded-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">প্রোডাক্টের নাম</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">ক্যাটাগরি</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">দাম (৳)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">স্টক পরিমাণ</label>
                    <input type="number" name="stock" class="form-control" value="10" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">প্রোডাক্টের ছবি সিলেক্ট করুন</label>
                <input type="file" name="image" class="form-control" accept="image/*" required>
            </div>
            <div class="mb-3">
                <label class="form-label">বিস্তারিত বিবরণ</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold">প্রোডাক্ট সেভ করুন</button>
        </form>
    </div>
</div>

</body>
</html>