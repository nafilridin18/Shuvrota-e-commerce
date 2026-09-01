<?php
session_start();
require_once 'config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 'published'");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

$img_stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$img_stmt->execute([$product_id]);
$images = $img_stmt->fetchAll();

$v_stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1");
$v_stmt->execute([$product_id]);
$variants = $v_stmt->fetchAll();

include 'includes/header.php';
?>

<style>
.product-zoom-box {
    overflow: hidden;
    position: relative;
    cursor: zoom-in;
    border-radius: 12px;
}
.product-zoom-box img {
    transition: transform 0.3s ease;
    width: 100%;
    height: 480px;
    object-fit: cover;
}
.product-zoom-box:hover img {
    transform: scale(1.6);
}
</style>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="product-zoom-box shadow-sm border mb-3" onmousemove="zoomImage(event)" onmouseleave="resetZoom()">
                <?php 
                    $main_img = !empty($images) ? $images[0]['image_path'] : '';
                    $main_src = (!empty($main_img) && file_exists('uploads/' . $main_img)) ? 'uploads/' . $main_img : 'https://via.placeholder.com/500x500?text=No+Image';
                ?>
                <img id="mainImage" src="<?= htmlspecialchars($main_src) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>
            <div class="d-flex gap-2">
                <?php foreach($images as $img): ?>
                    <?php 
                        $path = $img['image_path'];
                        $src = (!empty($path) && file_exists('uploads/' . $path)) ? 'uploads/' . $path : 'https://via.placeholder.com/75x75?text=No+Img';
                    ?>
                    <img src="<?= htmlspecialchars($src) ?>" width="75" height="75" class="rounded border cursor-pointer object-fit-cover" onclick="document.getElementById('mainImage').src='<?= htmlspecialchars($src) ?>'">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-md-6">
            <span class="badge bg-warning text-dark mb-2"><?= htmlspecialchars($product['category_name'] ?? 'General') ?></span>
            <h2 class="fw-bold text-dark"><?= htmlspecialchars($product['name']) ?></h2>
            
            <div class="my-3">
                <span class="fs-3 fw-bold text-danger">৳ <?= number_format($product['discount_price'] ?? $product['price'], 2) ?></span>
                <?php if(!empty($product['discount_price'])): ?>
                    <span class="text-muted text-decoration-line-through fs-5 ms-2">৳ <?= number_format($product['price'], 2) ?></span>
                <?php endif; ?>
            </div>

            <p class="text-muted lh-base"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>
            <hr>

            <form action="index.php" method="POST">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                
                <?php if(!empty($variants)): ?>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Select Variant (Size & Color)</label>
                            <select name="variant_id" id="variantSelect" class="form-select" onchange="updateStockDisplay()" required>
                                <?php foreach($variants as $v): ?>
                                    <option value="<?= $v['id'] ?>" data-stock="<?= $v['stock_quantity'] ?>" data-size="<?= htmlspecialchars($v['size']) ?>" data-color="<?= htmlspecialchars($v['color']) ?>">
                                        Size: <?= htmlspecialchars($v['size']) ?> | Color: <?= htmlspecialchars($v['color']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <span id="stockBadge" class="badge bg-success-subtle text-success border border-success px-3 py-2 fs-6">
                        Available Stock: Select a variant
                    </span>
                </div>

                <button type="submit" name="add_to_cart" id="addToCartBtn" class="btn btn-danger btn-lg rounded-pill px-5 shadow">
                    <i class="fa-solid fa-cart-plus me-2"></i> Add to Cart
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function updateStockDisplay() {
    const select = document.getElementById('variantSelect');
    if (!select) return;
    
    const selectedOption = select.options[select.selectedIndex];
    const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
    const badge = document.getElementById('stockBadge');
    const btn = document.getElementById('addToCartBtn');

    if (stock > 0) {
        badge.className = 'badge bg-success-subtle text-success border border-success px-3 py-2 fs-6';
        badge.innerText = `In Stock: ${stock} items available`;
        btn.disabled = false;
    } else {
        badge.className = 'badge bg-danger-subtle text-danger border border-danger px-3 py-2 fs-6';
        badge.innerText = 'Out of Stock for this variant';
        btn.disabled = true;
    }
}

// Initial Stock Call
document.addEventListener('DOMContentLoaded', updateStockDisplay);

function zoomImage(e) {
    const img = document.querySelector('.product-zoom-box img');
    const rect = e.target.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width * 100;
    const y = (e.clientY - rect.top) / rect.height * 100;
    img.style.transformOrigin = `${x}% ${y}%`;
    img.style.transform = 'scale(1.6)';
}
function resetZoom() {
    const img = document.querySelector('.product-zoom-box img');
    img.style.transform = 'scale(1)';
}
</script>

<?php include 'includes/footer.php'; ?>