<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';

$product_id = $_GET['id'] ?? 1;

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    $imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $imgStmt->execute([$product_id]);
    $images = $imgStmt->fetchAll();

    $varStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
    $varStmt->execute([$product_id]);
    $variants = $varStmt->fetchAll();

} catch (PDOException $e) {
    $product = null;
    $images = [];
    $variants = [];
}

$pageTitle = htmlspecialchars($product['name'] ?? 'Product Details') . ' - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4 my-md-5">
    <?php if ($product): ?>
        <?php
            $has_discount = (!empty($product['discount_price']) && $product['discount_price'] > 0 && $product['discount_price'] < $product['price']);
            $current_price = $has_discount ? $product['discount_price'] : $product['price'];
            $discount_percent = $has_discount ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
            $default_img = !empty($images[0]['image_path']) ? 'uploads/' . htmlspecialchars($images[0]['image_path']) : 'assets/images/default.jpg';
        ?>

        <nav aria-label="breadcrumb" class="mb-3 d-none d-md-block">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width:300px;"><?= htmlspecialchars($product['name']) ?></li>
            </ol>
        </nav>

        <div class="row g-4 g-lg-5">
            <div class="col-12 col-md-6 position-relative">
                <div class="media-display-container mb-3" id="displayBox"
                     onmousemove="imageZoom(event)"
                     onmouseleave="closeZoom()"
                     onclick="openLightbox()">

                    <?php if (!empty($product['product_video'])): ?>
                        <video id="mainVideo" class="main-product-media" controls autoplay muted loop>
                            <source src="uploads/videos/<?= htmlspecialchars($product['product_video']) ?>" type="video/mp4">
                        </video>
                        <img id="mainImage" class="main-product-media d-none" src="<?= $default_img ?>" alt="Product Image">
                    <?php else: ?>
                        <img id="mainImage" class="main-product-media" src="<?= $default_img ?>" alt="Product Image">
                    <?php endif; ?>

                    <div id="imgZoomContainer"></div>
                </div>

                <div class="text-muted small mb-2 text-center d-none d-md-block">
                    <i class="fa-solid fa-search-plus me-1"></i> <span class="lang-bn">ছবি জুম করতে মাউস নিন বা বড় দেখতে ক্লিক করুন</span><span class="lang-en">Hover to zoom or click to view large image</span>
                </div>

                <div class="d-flex gap-2 flex-wrap justify-content-center justify-content-md-start">
                    <?php if (!empty($product['product_video'])): ?>
                        <div class="thumbnail-item active d-flex align-items-center justify-content-center text-white bg-dark rounded" onclick="showVideo()">
                            <i class="fa-solid fa-play fs-5 text-danger"></i>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($images as $index => $img): ?>
                        <img src="<?= 'uploads/' . htmlspecialchars($img['image_path']) ?>"
                               class="thumbnail-item <?= (empty($product['product_video']) && $index === 0) ? 'active' : '' ?>"
                               onclick="showImage(this, '<?= 'uploads/' . htmlspecialchars($img['image_path']) ?>')"
                               alt="Thumbnail">
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <h2 class="fw-bold mb-3"><?= htmlspecialchars($product['name']) ?></h2>

                <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
                    <h3 class="price-current fw-bold mb-0">৳ <?= number_format($current_price, 2) ?></h3>
                    <?php if ($has_discount): ?>
                        <span class="text-muted text-decoration-line-through fs-5">৳ <?= number_format($product['price'], 2) ?></span>
                        <span class="badge bg-danger fs-6 px-3 py-2"><?= $discount_percent ?>% OFF</span>
                    <?php endif; ?>
                </div>

                <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($product['description'] ?? 'কোনো বিবরণ দেওয়া হয়নি।')) ?></p>

                <div class="mb-3">
                    <span class="badge bg-success p-2">
                        <span class="lang-bn">স্টকে আছে: <?= $product['stock_quantity'] ?? 10 ?> টি</span>
                        <span class="lang-en">In Stock: <?= $product['stock_quantity'] ?? 10 ?> pcs</span>
                    </span>
                </div>

                <form action="cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold small"><span class="lang-bn">সাইজ নির্বাচন করুন</span><span class="lang-en">Select Size</span></label>
                        <select name="size" class="form-select rounded-pill" required>
                            <?php if (!empty($variants)): ?>
                                <?php $unique_sizes = array_unique(array_column($variants, 'size')); foreach($unique_sizes as $sz): ?>
                                    <option value="<?= htmlspecialchars($sz) ?>"><?= htmlspecialchars($sz) ?></option>
                                <?php endforeach; else: ?>
                                <option value="Free Size">Free Size</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small"><span class="lang-bn">কালার নির্বাচন করুন</span><span class="lang-en">Select Color</span></label>
                        <select name="color" class="form-select rounded-pill" required>
                            <?php if (!empty($variants)): ?>
                                <?php $unique_colors = array_unique(array_column($variants, 'color')); foreach($unique_colors as $cl): ?>
                                    <option value="<?= htmlspecialchars($cl) ?>"><?= htmlspecialchars($cl) ?></option>
                                <?php endforeach; else: ?>
                                <option value="Standard">Standard / Original</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3" style="max-width: 150px;">
                        <label class="form-label fw-bold small"><span class="lang-bn">পরিমাণ</span><span class="lang-en">Quantity</span></label>
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?? 10 ?>" class="form-control rounded-pill text-center">
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-danger px-4 py-2 fw-bold rounded-pill shadow-sm">
                            <i class="fa-solid fa-cart-plus me-2"></i> <span class="lang-bn">কার্টে যোগ করুন</span><span class="lang-en">Add to Cart</span>
                        </button>
                        <a href="wishlist.php?add=<?= $product['id'] ?>" class="btn btn-outline-danger px-4 py-2 fw-bold rounded-pill shadow-sm">
                            <i class="fa-solid fa-heart me-2"></i> <span class="lang-bn">উইশলিস্টে যোগ করুন</span><span class="lang-en">Add to Wishlist</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5 min-h-60 d-flex flex-column justify-content-center">
            <i class="fa-solid fa-box-open fs-1 text-ink-muted mb-3"></i>
            <h3 class="text-muted"><span class="lang-bn">প্রোডাক্টটি পাওয়া যায়নি!</span><span class="lang-en">Product not found!</span></h3>
            <a href="index.php" class="btn btn-danger mt-3 rounded-pill px-4 align-self-center"><span class="lang-bn">হোমে ফিরে যান</span><span class="lang-en">Back to Home</span></a>
        </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body text-center position-relative p-0">
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3 bg-dark p-2 rounded-circle shadow" data-bs-dismiss="modal" aria-label="Close"></button>
        <img id="modalImage" src="" class="img-fluid rounded shadow-lg" style="max-height: 85vh; background: #000;">
      </div>
    </div>
  </div>
</div>

<script>
function showVideo() {
    const video = document.getElementById('mainVideo');
    const img = document.getElementById('mainImage');
    const zoomContainer = document.getElementById('imgZoomContainer');
    if(zoomContainer) zoomContainer.style.display = 'none';
    if(video) {
        video.classList.remove('d-none');
        img.classList.add('d-none');
        video.play();
    }
    document.querySelectorAll('.thumbnail-item').forEach(el => el.classList.remove('active'));
    event.currentTarget.classList.add('active');
}

function showImage(element, src) {
    const video = document.getElementById('mainVideo');
    const img = document.getElementById('mainImage');
    if(video) {
        video.pause();
        video.classList.add('d-none');
    }
    img.src = src;
    img.classList.remove('d-none');

    document.querySelectorAll('.thumbnail-item').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
}

function imageZoom(e) {
    const img = document.getElementById('mainImage');
    const zoomer = document.getElementById('imgZoomContainer');

    if (!img || img.classList.contains('d-none') || window.innerWidth < 992) return;

    const rect = e.currentTarget.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const xPercent = (x / rect.width) * 100;
    const yPercent = (y / rect.height) * 100;

    zoomer.style.backgroundImage = `url('${img.src}')`;
    zoomer.style.backgroundSize = `${rect.width * 2.5}px ${rect.height * 2.5}px`;
    zoomer.style.backgroundPosition = `${xPercent}% ${yPercent}%`;
    zoomer.style.display = 'block';
}

function closeZoom() {
    const zoomer = document.getElementById('imgZoomContainer');
    if(zoomer) zoomer.style.display = 'none';
}

function openLightbox() {
    const img = document.getElementById('mainImage');
    if (!img || img.classList.contains('d-none')) return;

    const modalImg = document.getElementById('modalImage');
    modalImg.src = img.src;

    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
    imageModal.show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
