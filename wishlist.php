<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// উইশলিস্টে নতুন প্রোডাক্ট যোগ করার লজিক
if (isset($_GET['add'])) {
    $product_id = (int)$_GET['add'];

    // চেক করা আগে থেকেই লিস্টে আছে কি না
    $chk = $pdo->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $chk->execute([$customer_id, $product_id]);

    if (!$chk->fetch()) {
        $ins = $pdo->prepare("INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
        $ins->execute([$customer_id, $product_id]);
    }
    header("Location: wishlist.php");
    exit();
}

// উইশলিস্ট থেকে রিমুভ করার লজিক
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    $del = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $del->execute([$customer_id, $remove_id]);
    header("Location: wishlist.php");
    exit();
}

// উইশলিস্টের প্রোডাক্টগুলো ফেচ করা
try {
    $stmt = $pdo->prepare("
        SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as img 
        FROM wishlists w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    $wishlist_products = $stmt->fetchAll();
} catch (Exception $e) {
    $wishlist_products = [];
}

$pageTitle = 'আমার উইশলিস্ট - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4 my-md-5" style="max-width: 950px;">
    <h2 class="fw-bold mb-4">
        <i class="fa-solid fa-heart text-danger me-2"></i>
        <span class="lang-bn">আমার উইশলিস্ট</span>
        <span class="lang-en">My Wishlist</span>
    </h2>

    <?php if(empty($wishlist_products)): ?>
        <div class="card p-5 text-center shadow-sm rounded-4 border-0">
            <i class="fa-regular fa-heart fs-1 text-ink-muted mb-3"></i>
            <h4 class="text-muted mb-3">
                <span class="lang-bn">আপনার উইশলিস্ট খালি!</span>
                <span class="lang-en">Your wishlist is empty!</span>
            </h4>
            <a href="index.php" class="btn btn-danger rounded-pill align-self-center px-4">
                <span class="lang-bn">শপিং শুরু করুন</span>
                <span class="lang-en">Start Shopping</span>
            </a>
        </div>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-md-3 g-3 g-md-4">
            <?php foreach($wishlist_products as $prod): ?>
                <div class="col">
                    <div class="card product-card h-100 position-relative">
                        <a href="wishlist.php?remove=<?= $prod['id'] ?>" class="wishlist-toggle position-absolute top-0 end-0 m-2 p-2 rounded-circle text-decoration-none z-2" title="Remove" aria-label="মুছে ফেলুন">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                        <img src="<?= !empty($prod['img']) ? 'uploads/' . htmlspecialchars($prod['img']) : 'assets/images/default.jpg' ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fs-6 fw-bold"><?= htmlspecialchars($prod['name']) ?></h5>
                            <p class="text-danger fw-bold mb-3">৳ <?= number_format($prod['discount_price'] > 0 ? $prod['discount_price'] : $prod['price'], 2) ?></p>
                            <a href="product-details.php?id=<?= $prod['id'] ?>" class="btn btn-outline-danger btn-sm w-100 rounded-pill mt-auto">
                                <span class="lang-bn">বিস্তারিত দেখুন</span>
                                <span class="lang-en">View Details</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
