<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt =$pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product =$stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {$name            = trim($_POST['name']);$name_bn         = trim($_POST['name_bn']);$category_id     = (int)$_POST['category_id'];$price           = (float)$_POST['price'];$discount        = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : NULL;
    $desc            = trim($_POST['description']);
    $is_featured     = isset($_POST['is_featured']) ? 1 : 0;
    $is_best_selling = isset($_POST['is_best_selling']) ? 1 : 0;

    $variant_sizes  =$_POST['variant_size'] ?? [];
    $variant_colors =$_POST['variant_color'] ?? [];
    $variant_stocks =$_POST['variant_stock'] ?? [];

    $total_stock = 0;
    foreach ($variant_stocks as$stk) {
        $total_stock += (int)$stk;
    }

    try {
        $pdo->beginTransaction();

        $update_stmt =$pdo->prepare("UPDATE products SET category_id = ?, name = ?, name_bn = ?, price = ?, discount_price = ?, description = ?, is_featured = ?, is_best_selling = ?, stock_quantity = ? WHERE id = ?");
        $update_stmt->execute([$category_id,$name, $name_bn,$price, $discount,$desc, $is_featured,$is_best_selling, $total_stock,$product_id]);
        
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = __DIR__ . '/../uploads/';$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $totalFiles = count($_FILES['images']['name']);

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {$ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {$new_filename = time() . '_' . rand(1000, 9999) . '_' . $i . '.' .$ext;
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_dir .$new_filename)) {
                            $chk =$pdo->prepare("SELECT count(*) FROM product_images WHERE product_id = ? AND is_primary = 1");
                            $chk->execute([$product_id]);
                            $is_primary = ($chk->fetchColumn() == 0) ? 1 : 0;

                            $img_stmt =$pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                            $img_stmt->execute([$product_id, $new_filename,$is_primary]);
                        }
                    }
                }
            }
        }

        $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product_id]);
        if (!empty($variant_sizes)) {
            for ($i = 0; $i < count($variant_sizes); $i++) {$sz  = trim($variant_sizes[$i]);
                $cl  = trim($variant_colors[$i]);$stk = (int)($variant_stocks[$i] ?? 0);
                if (!empty($sz) && !empty($cl)) {
                    $v_stmt =$pdo->prepare("INSERT INTO product_variants (product_id, size, color, stock_quantity) VALUES (?, ?, ?, ?)");
                    $v_stmt->execute([$product_id,$sz, $cl,$stk]);
                }
            }
        }

        $pdo->commit();$message = "প্রোডাক্ট সফলভাবে আপডেট করা হয়েছে!";
        
        $stmt->execute([$product_id]);
        $product =$stmt->fetch();
    } catch (Exception $e) {$pdo->rollBack();
        $message = "ত্রুটি: " . $e->getMessage();
    }
}

$cat_stmt =$pdo->query("SELECT c1.id, c1.name, c2.name as parent_name FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id ORDER BY parent_name, c1.name");
$categories =$cat_stmt->fetchAll();

$var_stmt =$pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$var_stmt->execute([$product_id]);
$variants =$var_stmt->fetchAll();

$img_stmt =$pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$img_stmt->execute([$product_id]);
$images =$img_stmt->fetchAll();

if (isset($_GET['delete_image'])) {
    $img_id = (int)$_GET['delete_image'];
    $del =$pdo->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?");
    $del->execute([$img_id,$product_id]);
    header("Location: edit_product.php?id=" . $product_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>প্রোডাক্ট এডিট - শুভ্রতা এডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.variant-box { background: #fff; border: 1px dashed #ced4da; border-radius: 8px; padding: 15px; }</style>
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>প্রোডাক্ট এডিট করুন</h2>
        <a href="products.php" class="btn btn-secondary">প্রোডাক্ট তালিকায় ফিরুন</a>
    </div>

    <?php if($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>

    <div class="card p-4 border-0 shadow-sm rounded-4">
        <form method="POST" enctype="multipart/form-data">
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
                    <?php foreach($categories as$c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] ==$product['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['parent_name'] ?$c['parent_name'].' > '.$c['name'] :$c['name']) ?>
                        </option>
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

            <div class="d-flex gap-4 mb-4 p-3 bg-light rounded border">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="isFeatured" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="isFeatured">Featured Product</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="isBestSelling" name="is_best_selling" value="1" <?= $product['is_best_selling'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold text-success" for="isBestSelling">Best Selling Product</label>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-danger">প্রোডাক্ট ভ্যারিয়েন্ট (কাস্টম সাইজ, কালার ও স্টক)</label>
                <div class="variant-box mb-2">
                    <div id="variantContainer">
                        <?php if(empty($variants)): ?>
                            <div class="row g-2 mb-2 variant-row align-items-center">
                                <div class="col-4"><input type="text" name="variant_size[]" class="form-control" placeholder="Size" required></div>
                                <div class="col-4"><input type="text" name="variant_color[]" class="form-control" placeholder="Color" required></div>
                                <div class="col-3"><input type="number" name="variant_stock[]" class="form-control" placeholder="Stock" value="5" required></div>
                                <div class="col-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button></div>
                            </div>
                        <?php else: foreach($variants as$v): ?>
                            <div class="row g-2 mb-2 variant-row align-items-center">
                                <div class="col-4"><input type="text" name="variant_size[]" class="form-control" value="<?= htmlspecialchars($v['size']) ?>" required></div>
                                <div class="col-4"><input type="text" name="variant_color[]" class="form-control" value="<?= htmlspecialchars($v['color']) ?>" required></div>
                                <div class="col-3"><input type="number" name="variant_stock[]" class="form-control" value="<?= $v['stock_quantity'] ?>" required></div>
                                <div class="col-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-dark mt-2" onclick="addVariant()"><i class="fa-solid fa-plus me-1"></i> আরও যোগ করুন</button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">বিদ্যমান ছবিসমূহ</label>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <?php foreach($images as$img): ?>
                        <div class="position-relative border p-1 rounded">
                            <img src="../uploads/<?= htmlspecialchars($img['image_path']) ?>" style="height: 60px; width: 60px; object-fit: cover;">
                            <a href="edit_product.php?id=<?= $product_id ?>&delete_image=<?= $img['id'] ?>" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" style="padding: 0 4px; font-size: 10px;" onclick="return confirm('মুছে ফেলতে চান?')">X</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-success">নতুন ছবি যোগ করুন (একাধিক)</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">বিবরণ (Description)</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold">পরিবর্তন সেভ করুন</button>
        </form>
    </div>
</div>
<script>
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
    if (rows.length > 1) {
        btn.closest('.variant-row').remove();
    }
}
</script>
</body>
</html>