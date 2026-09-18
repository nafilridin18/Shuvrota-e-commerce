<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$successMessage = '';
$errorMessage = '';

// উইশলিস্টে নতুন প্রোডাক্ট যোগ করার লজিক
if (isset($_GET['add'])) {
    $product_id = (int)$_GET['add'];
    $chk = $pdo->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $chk->execute([$customer_id, $product_id]);

    if (!$chk->fetch()) {
        $ins = $pdo->prepare("INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
        $ins->execute([$customer_id, $product_id]);
    }
    header("Location: wishlist.php");
    exit();
}

// উইশলিস্ট থেকে একটি রিমুভ করার লজিক
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    $del = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $del->execute([$customer_id, $remove_id]);
    header("Location: wishlist.php");
    exit();
}

// উইশলিস্ট থেকে সরাসরি কার্টে অ্যাড করার লজিক
if (isset($_GET['add_to_cart'])) {
    $product_id = (int)$_GET['add_to_cart'];
    try {
        $stmt = $pdo->prepare("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as img FROM products p WHERE p.id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            $title = $product['name'];
            $price = (!empty($product['discount_price']) && $product['discount_price'] > 0) ? $product['discount_price'] : $product['price'];
            $image = $product['img'] ?? '';
            $size = 'Free Size';
            $color = 'Standard';
            
            $cart_key = $product_id . '_' . md5($size . '_' . $color);

            if (isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key]['qty'] += 1;
            } else {
                $_SESSION['cart'][$cart_key] = [
                    'id'    => $product_id,
                    'title' => $title,
                    'price' => $price,
                    'qty'   => 1,
                    'size'  => $size,
                    'color' => $color,
                    'image' => $image
                ];
            }
            $successMessage = "পণ্যটি কার্টে যোগ করা হয়েছে!";
        }
    } catch (Exception $e) {
        $errorMessage = "কার্টে যোগ করতে সমস্যা হয়েছে।";
    }
}

// হ্যান্ডেল পোস্ট (Bulk Action: Add Selected to Cart / Remove Selected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_products'])) {
    $selected = $_POST['selected_products'];
    if (isset($_POST['bulk_add_cart'])) {
        foreach ($selected as $prod_id) {
            $prod_id = (int)$prod_id;
            $stmt = $pdo->prepare("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as img FROM products p WHERE p.id = ?");
            $stmt->execute([$prod_id]);
            $product = $stmt->fetch();
            if ($product) {
                $title = $product['name'];
                $price = (!empty($product['discount_price']) && $product['discount_price'] > 0) ? $product['discount_price'] : $product['price'];
                $image = $product['img'] ?? '';
                $cart_key = $prod_id . '_' . md5('Free Size_Standard');
                if (isset($_SESSION['cart'][$cart_key])) {
                    $_SESSION['cart'][$cart_key]['qty'] += 1;
                } else {
                    $_SESSION['cart'][$cart_key] = [
                        'id' => $prod_id, 'title' => $title, 'price' => $price, 'qty' => 1, 'size' => 'Free Size', 'color' => 'Standard', 'image' => $image
                    ];
                }
            }
        }
        $successMessage = "নির্বাচিত পণ্যগুলো কার্টে যোগ করা হয়েছে!";
    } elseif (isset($_POST['bulk_remove'])) {
        foreach ($selected as $prod_id) {
            $prod_id = (int)$prod_id;
            $del = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
            $del->execute([$customer_id, $prod_id]);
        }
        header("Location: wishlist.php");
        exit();
    }
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

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

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
        <form action="wishlist.php" method="POST" id="wishlistForm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label fw-semibold" for="selectAll">
                        <span class="lang-bn">সব নির্বাচন করুন</span><span class="lang-en">Select All</span>
                    </label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="bulk_add_cart" class="btn btn-sm btn-dark rounded-pill px-3">
                        <i class="fa-solid fa-cart-plus me-1"></i> <span class="lang-bn">নির্বাচিত কার্টে যোগ করুন</span><span class="lang-en">Add Selected to Cart</span>
                    </button>
                    <button type="submit" name="bulk_remove" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('মুছে ফেলতে চান?');">
                        <i class="fa-solid fa-trash-can me-1"></i> <span class="lang-bn">মুছে ফেলুন</span><span class="lang-en">Remove Selected</span>
                    </button>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-3 g-md-4">
                <?php foreach($wishlist_products as $prod): ?>
                    <div class="col">
                        <div class="card product-card h-100 position-relative shadow-sm border-0 rounded-4 overflow-hidden">
                            <div class="position-absolute top-0 start-0 m-2 z-2">
                                <input class="form-check-input item-checkbox" type="checkbox" name="selected_products[]" value="<?= $prod['id'] ?>" style="transform: scale(1.2);">
                            </div>
                            <a href="wishlist.php?remove=<?= $prod['id'] ?>" class="wishlist-toggle position-absolute top-0 end-0 m-2 p-2 rounded-circle text-decoration-none z-2 bg-white shadow-sm text-danger" title="Remove" aria-label="মুছে ফেলুন">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                            <img src="<?= !empty($prod['img']) ? 'uploads/' . htmlspecialchars($prod['img']) : 'assets/images/default.jpg' ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy" style="height: 220px; object-fit: cover;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fs-6 fw-bold"><?= htmlspecialchars($prod['name']) ?></h5>
                                <p class="text-danger fw-bold mb-3">৳ <?= number_format($prod['discount_price'] > 0 ? $prod['discount_price'] : $prod['price'], 2) ?></p>
                                <div class="mt-auto d-flex gap-2">
                                    <a href="wishlist.php?add_to_cart=<?= $prod['id'] ?>" class="btn btn-dark btn-sm w-50 rounded-pill">
                                        <i class="fa-solid fa-cart-shopping me-1"></i> <span class="lang-bn">কার্টে নিন</span><span class="lang-en">Add to Cart</span>
                                    </a>
                                    <a href="product-details.php?id=<?= $prod['id'] ?>" class="btn btn-outline-secondary btn-sm w-50 rounded-pill">
                                        <span class="lang-bn">বিস্তারিত</span><span class="lang-en">Details</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>

        <script>
            document.getElementById('selectAll').addEventListener('change', function() {
                let checkboxes = document.querySelectorAll('.item-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        </script>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>