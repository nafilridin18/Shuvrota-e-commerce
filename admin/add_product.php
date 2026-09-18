<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name']);
    $price         = (float)$_POST['price'];
    $discount      = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : NULL;
    $desc          = trim($_POST['description']);
    $sku           = 'SKU-' . rand(10000, 99999);
    $slug          = strtolower(str_replace(' ', '-', $name)) . '-' . rand(100, 999);
    
    $is_featured     = isset($_POST['is_featured']) ? 1 : 0;
    $is_best_selling = isset($_POST['is_best_selling']) ? 1 : 0;

    $category_id = (int)$_POST['category_id'];
    if ($category_id === -1 && !empty(trim($_POST['new_category']))) {
        $new_cat_name = trim($_POST['new_category']);
        $new_cat_slug = strtolower(str_replace(' ', '-', $new_cat_name)) . '-' . rand(10, 99);
        
        $cat_chk = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $cat_chk->execute([$new_cat_name]);
        $existing_cat = $cat_chk->fetch();

        if ($existing_cat) {
            $category_id = $existing_cat['id'];
        } else {
            $ins_cat = $pdo->prepare("INSERT INTO categories (name, slug, is_active) VALUES (?, ?, 1)");
            $ins_cat->execute([$new_cat_name, $new_cat_slug]);
            $category_id = $pdo->lastInsertId();
        }
    }

    $product_video = NULL;
    if (isset($_FILES['product_video']) && $_FILES['product_video']['error'] === UPLOAD_ERR_OK) {
        $video_dir = __DIR__ . '/../uploads/videos/';
        if (!file_exists($video_dir)) {
            mkdir($video_dir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['product_video']['name'], PATHINFO_EXTENSION));
        $allowed_video = ['mp4', 'webm', 'ogg', 'mov'];
        if (in_array($ext, $allowed_video)) {
            $product_video = 'vid_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['product_video']['tmp_name'], $video_dir . $product_video);
        }
    }

    $variant_sizes  = $_POST['variant_size'] ?? [];
    $variant_colors = $_POST['variant_color'] ?? [];
    $variant_stocks = $_POST['variant_stock'] ?? [];

    $total_stock = 0;
    foreach ($variant_stocks as $stk) {
        $total_stock += (int)$stk;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, sku, description, price, discount_price, stock_quantity, product_video, is_featured, is_best_selling, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        $stmt->execute([$category_id, $name, $slug, $sku, $desc, $price, $discount, $total_stock, $product_video, $is_featured, $is_best_selling]);
        $product_id = $pdo->lastInsertId();

        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = __DIR__ . '/../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $totalFiles = count($_FILES['images']['name']);

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $new_filename = time() . '_' . rand(1000, 9999) . '_' . $i . '.' . $ext;
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_dir . $new_filename)) {
                            $is_primary = ($i === 0) ? 1 : 0;
                            $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                            $img_stmt->execute([$product_id, $new_filename, $is_primary]);
                        }
                    }
                }
            }
        }

        if (!empty($variant_sizes)) {
            for ($i = 0; $i < count($variant_sizes); $i++) {
                $sz  = trim($variant_sizes[$i]);
                $cl  = trim($variant_colors[$i]);
                $stk = (int)($variant_stocks[$i] ?? 0);
                if (!empty($sz) && !empty($cl)) {
                    $v_stmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, color, stock_quantity) VALUES (?, ?, ?, ?)");
                    $v_stmt->execute([$product_id, $sz, $cl, $stk]);
                }
            }
        }

        $pdo->commit();
        $message = "প্রোডাক্ট সফলভাবে সংরক্ষণ করা হয়েছে!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "ত্রুটি: " . $e->getMessage();
    }
}

$cat_stmt = $pdo->query("SELECT c1.id, c1.name, c2.name as parent_name FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id ORDER BY parent_name, c1.name");
$categories = $cat_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <title>প্রোডাক্ট যোগ করুন - শুভ্রতা এডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.lang-bn-mode .lang-en { display: none !important; }
        body.lang-bn-mode .lang-bn { display: inline-block !important; }
        body.lang-en-mode .lang-bn { display: none !important; }
        body.lang-en-mode .lang-en { display: inline-block !important; }
        .variant-box { background: #fff; border: 1px dashed #ced4da; border-radius: 8px; padding: 15px; }
        .custom-file-upload { border: 2px dashed #0d6efd; padding: 15px; border-radius: 8px; background: #f8f9fa; text-align: center; }
    </style>
</head>
<body class="bg-light lang-bn-mode">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm px-4 mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-gem text-danger me-1"></i> <span class="lang-bn">শুভ্রতা অ্যাডমিন</span><span class="lang-en">Shuvrota Admin</span></a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <a href="index.php" class="btn btn-sm btn-outline-light rounded-pill"><span class="lang-bn">ড্যাশবোর্ড</span><span class="lang-en">Dashboard</span></a>
        </div>
    </div>
</nav>

<div class="container my-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><span class="lang-bn">নতুন প্রোডাক্ট যোগ করুন</span><span class="lang-en">Add New Product</span></h2>
    </div>

    <?php if($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>

    <div class="card p-4 border-0 shadow-sm rounded-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-bold"><span class="lang-bn">প্রোডাক্টের নাম</span><span class="lang-en">Product Name</span></label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><span class="lang-bn">ক্যাটাগরি</span><span class="lang-en">Category</span></label>
                <select name="category_id" id="categorySelect" class="form-select mb-2" onchange="checkNewCategory(this)" required>
                    <option value=""><span class="lang-bn">ক্যাটাগরি সিলেক্ট করুন</span><span class="lang-en">Select Category</span></option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= htmlspecialchars($c['parent_name'] ? $c['parent_name'].' > '.$c['name'] : $c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="-1" class="fw-bold text-danger">+ <span class="lang-bn">নতুন ক্যাটাগরি যোগ করুন</span><span class="lang-en">Add New Category</span></option>
                </select>
                <input type="text" name="new_category" id="newCategoryInput" class="form-control d-none mt-2" placeholder="Enter new category name">
            </div>

            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label fw-bold"><span class="lang-bn">রেগুলার মূল্য (৳)</span><span class="lang-en">Regular Price (৳)</span></label>
                    <input type="number" step="0.01" name="price" id="regularPrice" class="form-control" oninput="calculateDiscount()" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label fw-bold"><span class="lang-bn">ডিসকাউন্ট মূল্য (৳)</span><span class="lang-en">Discount Price (৳)</span> <span id="discountBadge" class="badge bg-danger ms-1"></span></label>
                    <input type="number" step="0.01" name="discount_price" id="discountPrice" class="form-control" oninput="calculateDiscount()">
                </div>
            </div>

            <div class="d-flex gap-4 mb-4 p-3 bg-light rounded border">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="isFeatured" name="is_featured" value="1">
                    <label class="form-check-label fw-bold" for="isFeatured">Featured Product</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="isBestSelling" name="is_best_selling" value="1">
                    <label class="form-check-label fw-bold text-success" for="isBestSelling">Best Selling Product</label>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-danger"><span class="lang-bn">প্রোডাক্ট ভ্যারিয়েন্ট (কাস্টম সাইজ, কালার ও স্টক)</span><span class="lang-en">Product Variants</span></label>
                <div class="variant-box mb-2">
                    <div id="variantContainer">
                        <div class="row g-2 mb-2 variant-row align-items-center">
                            <div class="col-4">
                                <input type="text" name="variant_size[]" class="form-control" placeholder="Size (e.g. XL, 32, Free Size)" required>
                            </div>
                            <div class="col-4">
                                <input type="text" name="variant_color[]" class="form-control" placeholder="Color (e.g. Red, Black)" required>
                            </div>
                            <div class="col-3">
                                <input type="number" name="variant_stock[]" class="form-control" placeholder="Stock" value="5" required>
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-dark mt-2" onclick="addVariant()"><i class="fa-solid fa-plus me-1"></i> <span class="lang-bn">আরও যোগ করুন</span><span class="lang-en">Add More</span></button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-primary"><i class="fa-solid fa-video me-1"></i> <span class="lang-bn">প্রোডাক্ট ভিডিও (ঐচ্ছিক)</span><span class="lang-en">Product Video (Optional)</span></label>
                <div class="custom-file-upload">
                    <input type="file" name="product_video" class="form-control" accept="video/mp4,video/webm,video/ogg">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-success"><i class="fa-solid fa-images me-1"></i> <span class="lang-bn">প্রোডাক্ট ছবি গ্যালারি</span><span class="lang-en">Product Images Gallery</span></label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><span class="lang-bn">বিবরণ</span><span class="lang-en">Description</span></label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold py-2"><span class="lang-bn">প্রোডাক্ট সেভ করুন</span><span class="lang-en">Save Product</span></button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function checkNewCategory(select) {
    const input = document.getElementById('newCategoryInput');
    if (select.value === '-1') {
        input.classList.remove('d-none');
        input.required = true;
    } else {
        input.classList.add('d-none');
        input.required = false;
        input.value = '';
    }
}

function calculateDiscount() {
    const price = parseFloat(document.getElementById('regularPrice').value) || 0;
    const discount = parseFloat(document.getElementById('discountPrice').value) || 0;
    const badge = document.getElementById('discountBadge');

    if (price > 0 && discount > 0 && discount < price) {
        const percent = Math.round(((price - discount) / price) * 100);
        badge.innerText = `${percent}% OFF`;
    } else {
        badge.innerText = '';
    }
}

function addVariant() {
    const container = document.getElementById('variantContainer');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 variant-row align-items-center';
    row.innerHTML = `
        <div class="col-4"><input type="text" name="variant_size[]" class="form-control" placeholder="Size" required></div>
        <div class="col-4"><input type="text" name="variant_color[]" class="form-control" placeholder="Color" required></div>
        <div class="col-3"><input type="number" name="variant_stock[]" class="form-control" placeholder="Stock" value="5" required></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button></div>
    `;
    container.appendChild(row);
}
function removeVariant(btn) {
    const rows = document.querySelectorAll('.variant-row');
    if (rows.length > 1) btn.closest('.variant-row').remove();
}
</script>
</body>
</html>