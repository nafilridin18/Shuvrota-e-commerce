<?php
require_once '../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price       = (float)$_POST['price'];
    $discount    = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : NULL;
    $desc        = trim($_POST['description']);
    $sku         = 'SKU-' . rand(10000, 99999);
    $slug        = strtolower(str_replace(' ', '-', $name)) . '-' . rand(100, 999);

    // Calculate total stock from variants
    $variant_sizes  = $_POST['variant_size'] ?? [];
    $variant_colors = $_POST['variant_color'] ?? [];
    $variant_stocks = $_POST['variant_stock'] ?? [];

    $total_stock = 0;
    foreach ($variant_stocks as $stk) {
        $total_stock += (int)$stk;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, sku, description, price, discount_price, stock_quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        $stmt->execute([$category_id, $name, $slug, $sku, $desc, $price, $discount, $total_stock]);
        $product_id = $pdo->lastInsertId();

        // Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed)) {
                $new_filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                    $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)");
                    $img_stmt->execute([$product_id, $new_filename]);
                }
            }
        }

        // Save Variants with Specific Stock
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
        $message = "Product and color/size variants saved successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product with Variant Stock - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Add New Product & Variant Stock</h2>
        <a href="index.php" class="btn btn-secondary">Dashboard</a>
    </div>

    <?php if($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>

    <div class="card p-4 border-0 shadow-sm rounded-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-bold">Product Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Category</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label fw-bold">Regular Price (৳)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label fw-bold">Discount Price (৳)</label>
                    <input type="number" step="0.01" name="discount_price" class="form-control">
                </div>
            </div>

            <!-- Size & Color Variant Section -->
            <div class="mb-4">
                <label class="form-label fw-bold text-danger">Product Variants (Size, Color & Individual Stock)</label>
                <div id="variantContainer">
                    <div class="row g-2 mb-2 variant-row">
                        <div class="col-4">
                            <input type="text" name="variant_size[]" class="form-control" placeholder="Size (e.g. M, L, Free Size)" required>
                        </div>
                        <div class="col-4">
                            <input type="text" name="variant_color[]" class="form-control" placeholder="Color (e.g. Red, Blue)" required>
                        </div>
                        <div class="col-3">
                            <input type="number" name="variant_stock[]" class="form-control" placeholder="Stock Qty" value="5" required>
                        </div>
                        <div class="col-1">
                            <button type="button" class="btn btn-outline-danger w-100" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark mt-2" onclick="addVariant()"><i class="fa-solid fa-plus me-1"></i> Add More Size/Color</button>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Product Image</label>
                <input type="file" name="image" class="form-control" accept="image/*" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold py-2">Save Product & Variants</button>
        </form>
    </div>
</div>

<script>
function addVariant() {
    const container = document.getElementById('variantContainer');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 variant-row';
    row.innerHTML = `
        <div class="col-4">
            <input type="text" name="variant_size[]" class="form-control" placeholder="Size (e.g. XL, M)" required>
        </div>
        <div class="col-4">
            <input type="text" name="variant_color[]" class="form-control" placeholder="Color (e.g. Black, White)" required>
        </div>
        <div class="col-3">
            <input type="number" name="variant_stock[]" class="form-control" placeholder="Stock Qty" value="5" required>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-outline-danger w-100" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button>
        </div>
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