<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name']);
    $name_bn         = trim($_POST['name_bn'] ?? '');
    $price           = (float)$_POST['price'];
    $discount        = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : NULL;
    $desc            = trim($_POST['description'] ?? '');
    $sku             = 'SKU-' . rand(10000, 99999);
    $slug            = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . rand(100, 999);

    $is_featured     = isset($_POST['is_featured']) ? 1 : 0;
    $is_best_selling = isset($_POST['is_best_selling']) ? 1 : 0;

    $category_id = (int)$_POST['category_id'];
    if ($category_id === -1 && !empty(trim($_POST['new_category']))) {
        $new_cat_name = trim($_POST['new_category']);
        $new_cat_slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $new_cat_name)) . '-' . rand(10, 99);

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
        if (!file_exists($video_dir)) mkdir($video_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['product_video']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4', 'webm', 'ogg', 'mov'])) {
            $product_video = 'vid_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['product_video']['tmp_name'], $video_dir . $product_video);
        }
    }

    $variant_sizes  = $_POST['variant_size']  ?? [];
    $variant_colors = $_POST['variant_color'] ?? [];
    $variant_stocks = $_POST['variant_stock'] ?? [];

    $total_stock = 0;
    foreach ($variant_stocks as $stk) $total_stock += (int)$stk;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, name_bn, slug, sku, description, price, discount_price, stock_quantity, product_video, is_featured, is_best_selling, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        $stmt->execute([$category_id, $name, $name_bn, $slug, $sku, $desc, $price, $discount, $total_stock, $product_video, $is_featured, $is_best_selling]);
        $product_id = $pdo->lastInsertId();

        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = __DIR__ . '/../uploads/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
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
        $message = "প্রোডাক্ট সফলভাবে সংরক্ষণ করা হয়েছে!";
        $messageType = 'success';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "ত্রুটি: " . $e->getMessage();
        $messageType = 'danger';
    }
}

$cat_stmt = $pdo->query("SELECT c1.id, c1.name, c2.name as parent_name FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id ORDER BY parent_name, c1.name");
$categories = $cat_stmt->fetchAll();

$pageTitle     = 'নতুন প্রোডাক্ট — শুভ্রতা এডমিন';
$pageHeadingBn = 'নতুন প্রোডাক্ট যোগ করুন';
$pageHeadingEn = 'Add New Product';
$activePage    = 'add';
include 'includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="index.php" class="admin-btn admin-btn-outline">
        <i class="fa-solid fa-arrow-left"></i>
        <span class="lang-bn">ড্যাশবোর্ডে ফিরুন</span><span class="lang-en">Back to Dashboard</span>
    </a>
</div>

<div class="admin-card mx-auto" style="max-width: 900px;">
    <div class="admin-card-header">
        <span><i class="fa-solid fa-plus me-2"></i>
            <span class="lang-bn">প্রোডাক্টের তথ্য</span><span class="lang-en">Product Information</span>
        </span>
    </div>
    <div class="admin-card-body">
        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label">Product Name (English) *</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Product Name (বাংলা)</label>
                <input type="text" name="name_bn" class="form-control" placeholder="ঐচ্ছিক">
            </div>

            <div class="mb-3">
                <label class="form-label">Category *</label>
                <select name="category_id" id="categorySelect" class="form-select mb-2" onchange="checkNewCategory(this)" required>
                    <option value="">— Select Category —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= htmlspecialchars($c['parent_name'] ? $c['parent_name'].' > '.$c['name'] : $c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="-1" class="fw-bold" style="color: var(--maroon-700);">+ Add New Category</option>
                </select>
                <input type="text" name="new_category" id="newCategoryInput" class="form-control d-none" placeholder="Enter new category name">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Regular Price (৳) *</label>
                    <input type="number" step="0.01" name="price" id="regularPrice" class="form-control" oninput="calculateDiscount()" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Discount Price (৳)
                        <span id="discountBadge" class="badge ms-1" style="background: var(--maroon-700);"></span>
                    </label>
                    <input type="number" step="0.01" name="discount_price" id="discountPrice" class="form-control" oninput="calculateDiscount()">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-4 mb-4 p-3 rounded" style="background: rgba(244,236,223,0.6); border: 1px solid var(--border-soft);">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="isFeatured" name="is_featured" value="1">
                    <label class="form-check-label fw-bold" for="isFeatured">⭐ Featured Product</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="isBestSelling" name="is_best_selling" value="1">
                    <label class="form-check-label fw-bold" for="isBestSelling" style="color: #2e7d32;">🔥 Best Selling Product</label>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="color: var(--maroon-700);">
                    <i class="fa-solid fa-layer-group me-1"></i> Product Variants (Size, Color, Stock)
                </label>
                <div class="p-3 rounded" style="background: rgba(255,253,250,0.9); border: 1px dashed var(--border-soft);">
                    <div id="variantContainer">
                        <div class="row g-2 mb-2 variant-row align-items-center">
                            <div class="col-4"><input type="text" name="variant_size[]" class="form-control" placeholder="Size (e.g. XL, 32)" required></div>
                            <div class="col-4"><input type="text" name="variant_color[]" class="form-control" placeholder="Color" required></div>
                            <div class="col-3"><input type="number" name="variant_stock[]" class="form-control" placeholder="Stock" value="5" required></div>
                            <div class="col-1"><button type="button" class="admin-btn admin-btn-sm" style="background:#fee; color:#c62828; border:1.5px solid #c62828;" onclick="removeVariant(this)"><i class="fa-solid fa-trash"></i></button></div>
                        </div>
                    </div>
                    <button type="button" class="admin-btn admin-btn-outline admin-btn-sm mt-2" onclick="addVariant()">
                        <i class="fa-solid fa-plus"></i> Add More Variant
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="color: var(--maroon-700);">
                    <i class="fa-solid fa-video me-1"></i> Product Video (Optional)
                </label>
                <input type="file" name="product_video" class="form-control" accept="video/mp4,video/webm,video/ogg">
            </div>

            <div class="mb-4">
                <label class="form-label" style="color: #2e7d32;">
                    <i class="fa-solid fa-images me-1"></i> Product Images (multiple) *
                </label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
                <small class="text-muted">প্রথম ছবিটি Primary ছবি হিসেবে সেট হবে।</small>
            </div>

            <div class="mb-4">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <button type="submit" class="admin-btn admin-btn-primary w-100 justify-content-center" style="padding: 12px;">
                <i class="fa-solid fa-save"></i>
                <span class="lang-bn">প্রোডাক্ট সেভ করুন</span><span class="lang-en">Save Product</span>
            </button>
        </form>
    </div>
</div>

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
        badge.style.display = 'inline-block';
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