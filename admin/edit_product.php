<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name']);
    $name_bn         = trim($_POST['name_bn'] ?? '');
    $category_id     = (int)$_POST['category_id'];
    $price           = (float)$_POST['price'];
    $discount        = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : NULL;
    $desc            = trim($_POST['description'] ?? '');
    $is_featured     = isset($_POST['is_featured']) ? 1 : 0;
    $is_best_selling = isset($_POST['is_best_selling']) ? 1 : 0;

    $variant_sizes  = $_POST['variant_size']  ?? [];
    $variant_colors = $_POST['variant_color'] ?? [];
    $variant_stocks = $_POST['variant_stock'] ?? [];

    $total_stock = 0;
    foreach ($variant_stocks as $stk) $total_stock += (int)$stk;

    try {
        $pdo->beginTransaction();

        $update_stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, name_bn = ?, price = ?, discount_price = ?, description = ?, is_featured = ?, is_best_selling = ?, stock_quantity = ? WHERE id = ?");
        $update_stmt->execute([$category_id, $name, $name_bn, $price, $discount, $desc, $is_featured, $is_best_selling, $total_stock, $product_id]);

        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = __DIR__ . '/../uploads/';
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $totalFiles = count($_FILES['images']['name']);

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $new_filename = time() . '_' . rand(1000, 9999) . '_' . $i . '.' . $ext;
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_dir . $new_filename)) {
                            $chk = $pdo->prepare("SELECT count(*) FROM product_images WHERE product_id = ? AND is_primary = 1");
                            $chk->execute([$product_id]);
                            $is_primary = ($chk->fetchColumn() == 0) ? 1 : 0;

                            $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                            $img_stmt->execute([$product_id, $new_filename, $is_primary]);
                        }
                    }
                }
            }
        }

        $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product_id]);
        if (!empty($variant_sizes)) {
            for ($i = 0; $i < count($variant_sizes); $i++) {
                $sz = trim($variant_sizes[$i]);
                $cl = trim($variant_colors[$i]);
                $stk = (int)($variant_stocks[$i] ?? 0);
                if ($sz !== '' && $cl !== '') {
                    $v_stmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, color, stock_quantity) VALUES (?, ?, ?, ?)");
                    $v_stmt->execute([$product_id, $sz, $cl, $stk]);
                }
            }
        }

        $pdo->commit();
        $message = "প্রোডাক্ট সফলভাবে আপডেট করা হয়েছে!";
        $messageType = 'success';

        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "ত্রুটি: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle image delete
if (isset($_GET['delete_image'])) {
    $img_id = (int)$_GET['delete_image'];
    $del = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
    $del->execute([$img_id, $product_id]);
    $img_row = $del->fetch();
    if ($img_row) {
        $path = __DIR__ . '/../uploads/' . $img_row['image_path'];
        if (file_exists($path)) @unlink($path);
        $pdo->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?")->execute([$img_id, $product_id]);
    }
    header("Location: edit_product.php?id=" . $product_id);
    exit;
}

$cat_stmt = $pdo->query("SELECT c1.id, c1.name, c2.name as parent_name FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id ORDER BY parent_name, c1.name");
$categories = $cat_stmt->fetchAll();

$var_stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$var_stmt->execute([$product_id]);
$variants = $var_stmt->fetchAll();

$img_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
$img_stmt->execute([$product_id]);
$images = $img_stmt->fetchAll();

$pageTitle     = 'প্রোডাক্ট এডিট — শুভ্রতা এডমিন';
$pageHeadingBn = 'প্রোডাক্ট এডিট করুন';
$pageHeadingEn = 'Edit Product';
$activePage    = 'products';
include 'includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-4 justify-content-center">
    <a href="index.php" class="admin-btn admin-btn-outline">
        <i class="fa-solid fa-arrow-left"></i>
        <span class="lang-bn">ড্যাশবোর্ডে ফিরুন</span><span class="lang-en">Back to Dashboard</span>
    </a>
    <a href="index.php#products" class="admin-btn admin-btn-outline">
        <i class="fa-solid fa-list"></i>
        <span class="lang-bn">প্রোডাক্ট তালিকা</span><span class="lang-en">Product List</span>
    </a>
</div>

<div class="admin-card mx-auto" style="max-width: 900px;">
    <div class="admin-card-header">
        <span><i class="fa-solid fa-pen-to-square me-2"></i>
            <span class="lang-bn">প্রোডাক্ট আপডেট</span><span class="lang-en">Update Product</span>
        </span>
        <span class="badge" style="background: var(--gold-500); color:#241b17;">ID #<?= $product_id ?></span>
    </div>
    <div class="admin-card-body">
        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label">Product Name (English) *</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Product Name (বাংলা)</label>
                <input type="text" name="name_bn" class="form-control" value="<?= htmlspecialchars($product['name_bn'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Category *</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $product['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['parent_name'] ? $c['parent_name'].' > '.$c['name'] : $c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Regular Price (৳) *</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Discount Price (৳)</label>
                    <input type="number" step="0.01" name="discount_price" class="form-control" value="<?= $product['discount_price'] ?>">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-4 mb-4 p-3 rounded" style="background: rgba(244,236,223,0.6); border: 1px solid var(--border-soft);">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="isFeatured" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="isFeatured">⭐ Featured Product</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="isBestSelling" name="is_best_selling" value="1" <?= $product['is_best_selling'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="isBestSelling" style="color: #2e7d32;">🔥 Best Selling Product</label>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="color: var(--maroon-700);">
                    <i class="fa-solid fa-layer-group me-1"></i> Product Variants
                </label>
                <div class="p-3 rounded" style="background: rgba(255,253,250,0.9); border: 1px dashed var(--border-soft);">
                    <div id="variantContainer">
                        <?php if (empty($variants)): ?>
                            <div class="row g-2 mb-2 variant-row align-items-center">
                                <div class="col-4"><input type="text" name="variant_size[]" class="form-control" placeholder="Size" required></div>
                                <div class="col-4"><input type="text" name="variant_color[]" class="form-control" placeholder="Color" required></div>
                                <div class="col-3"><input type="number" name="variant_stock[]" class="form-control" placeholder="Stock" value="5" required></div>
                                <div class="col-1"><button type="button" class="admin-btn admin-btn-sm" style="background:#fee; color:#c62828; border:1.5px solid #c62828;" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button></div>
                            </div>
                        <?php else: foreach ($variants as $v): ?>
                            <div class="row g-2 mb-2 variant-row align-items-center">
                                <div class="col-4"><input type="text" name="variant_size[]" class="form-control" value="<?= htmlspecialchars($v['size']) ?>" required></div>
                                <div class="col-4"><input type="text" name="variant_color[]" class="form-control" value="<?= htmlspecialchars($v['color']) ?>" required></div>
                                <div class="col-3"><input type="number" name="variant_stock[]" class="form-control" value="<?= $v['stock_quantity'] ?>" required></div>
                                <div class="col-1"><button type="button" class="admin-btn admin-btn-sm" style="background:#fee; color:#c62828; border:1.5px solid #c62828;" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <button type="button" class="admin-btn admin-btn-outline admin-btn-sm mt-2" onclick="addVariant()">
                        <i class="fa-solid fa-plus"></i> Add More Variant
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Current Images</label>
                <?php if (empty($images)): ?>
                    <p class="text-muted small">No images uploaded yet.</p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($images as $img): ?>
                            <div class="position-relative" style="border: 1px solid var(--border-soft); border-radius: 10px; padding: 5px;">
                                <img src="../uploads/<?= htmlspecialchars($img['image_path']) ?>" style="width:80px; height:80px; object-fit:cover; border-radius: 8px;">
                                <?php if ($img['is_primary']): ?>
                                    <span class="badge position-absolute" style="top:5px; left:5px; background: var(--gold-500); color:#241b17; font-size:9px;">Primary</span>
                                <?php endif; ?>
                                <a href="edit_product.php?id=<?= $product_id ?>&delete_image=<?= $img['id'] ?>"
                                   class="position-absolute" style="top:-8px; right:-8px; background:#c62828; color:#fff; width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; text-decoration:none; font-size:11px;"
                                   onclick="return confirm('Delete this image?');">×</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="form-label" style="color: #2e7d32;">
                    <i class="fa-solid fa-images me-1"></i> Add New Images
                </label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
            </div>

            <div class="mb-4">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="admin-btn admin-btn-primary w-100 justify-content-center" style="padding: 12px;">
                <i class="fa-solid fa-save"></i>
                <span class="lang-bn">পরিবর্তন সেভ করুন</span><span class="lang-en">Save Changes</span>
            </button>
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
        <div class="col-1"><button type="button" class="admin-btn admin-btn-sm" style="background:#fee; color:#c62828; border:1.5px solid #c62828;" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button></div>
    `;
    container.appendChild(row);
}
function removeVariant(btn) {
    const rows = document.querySelectorAll('.variant-row');
    if (rows.length > 1) btn.closest('.variant-row').remove();
}
</script>

<?php include 'includes/footer.php'; ?>