<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id    = $_SESSION['customer_id'];
$successMessage = '';
$errorMessage   = '';

/* =====================================================================
   ADD SINGLE (via ?add=ID)
   ===================================================================== */
if (isset($_GET['add'])) {
    $product_id = (int)$_GET['add'];

    $existsChk = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $existsChk->execute([$product_id]);

    if ($existsChk->fetch()) {
        $chk = $pdo->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
        $chk->execute([$customer_id, $product_id]);

        if (!$chk->fetch()) {
            $ins = $pdo->prepare("INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
            $ins->execute([$customer_id, $product_id]);
        }
    }
    header("Location: wishlist.php");
    exit();
}

/* =====================================================================
   REMOVE SINGLE (via ?remove=ID)
   ===================================================================== */
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    $del = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $del->execute([$customer_id, $remove_id]);
    header("Location: wishlist.php");
    exit();
}

/* =====================================================================
   ADD SINGLE TO CART (via ?add_to_cart=ID) — legacy, no AJAX flow
   ===================================================================== */
if (isset($_GET['add_to_cart'])) {
    $product_id = (int)$_GET['add_to_cart'];
    try {
        $stmt = $pdo->prepare("
            SELECT p.*,
                   (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) AS img
            FROM products p WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product && (int)($product['stock_quantity'] ?? 0) > 0) {
            $price = (!empty($product['discount_price']) && $product['discount_price'] > 0)
                ? $product['discount_price']
                : $product['price'];

            $size = 'Free Size';
            $color = 'Standard';
            $cart_key = $product_id . '_' . md5($size . '_' . $color);

            if (isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key]['qty'] += 1;
            } else {
                $_SESSION['cart'][$cart_key] = [
                    'id'    => $product_id,
                    'title' => $product['name'],
                    'price' => $price,
                    'qty'   => 1,
                    'size'  => $size,
                    'color' => $color,
                    'image' => $product['img'] ?? '',
                ];
            }
            $successMessage = "পণ্যটি কার্টে যোগ করা হয়েছে!";
        } elseif ($product) {
            $errorMessage = "পণ্যটি বর্তমানে স্টকে নেই।";
        }
    } catch (Exception $e) {
        $errorMessage = "কার্টে যোগ করতে সমস্যা হয়েছে।";
    }
}

/* =====================================================================
   BULK ACTIONS
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_products'])) {
    $selected = $_POST['selected_products'];

    if (isset($_POST['bulk_add_cart'])) {
        foreach ($selected as $prod_id) {
            $prod_id = (int)$prod_id;
            $stmt = $pdo->prepare("
                SELECT p.*,
                       (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) AS img
                FROM products p WHERE p.id = ?
            ");
            $stmt->execute([$prod_id]);
            $product = $stmt->fetch();

            // Skip products that are out of stock
            if ($product && (int)($product['stock_quantity'] ?? 0) > 0) {
                $price = (!empty($product['discount_price']) && $product['discount_price'] > 0)
                    ? $product['discount_price']
                    : $product['price'];

                $cart_key = $prod_id . '_' . md5('Free Size_Standard');

                if (isset($_SESSION['cart'][$cart_key])) {
                    $_SESSION['cart'][$cart_key]['qty'] += 1;
                } else {
                    $_SESSION['cart'][$cart_key] = [
                        'id'    => $prod_id,
                        'title' => $product['name'],
                        'price' => $price,
                        'qty'   => 1,
                        'size'  => 'Free Size',
                        'color' => 'Standard',
                        'image' => $product['img'] ?? '',
                    ];
                }
            }
        }
        $successMessage = "নির্বাচিত পণ্যগুলো কার্টে যোগ করা হয়েছে!";
    }
    elseif (isset($_POST['bulk_remove'])) {
        foreach ($selected as $prod_id) {
            $prod_id = (int)$prod_id;
            $del = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
            $del->execute([$customer_id, $prod_id]);
        }
        header("Location: wishlist.php");
        exit();
    }
}

/* =====================================================================
   FETCH WISHLIST
   ===================================================================== */
try {
    $stmt = $pdo->prepare("
        SELECT p.*,
               (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) AS img
        FROM wishlists w
        JOIN products p ON w.product_id = p.id
        WHERE w.customer_id = ?
        ORDER BY w.id DESC
    ");
    $stmt->execute([$customer_id]);
    $wishlist_products = $stmt->fetchAll();
} catch (Exception $e) {
    $wishlist_products = [];
}

$pageTitle = 'আমার উইশলিস্ট - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="wishlist-page">

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($wishlist_products)): ?>

        <!-- ==================== EMPTY STATE ==================== -->
        <div class="wishlist-empty">
            <div class="wishlist-empty-icon">
                <i class="fa-regular fa-heart"></i>
            </div>
            <h3>
                <span class="lang-bn">আপনার উইশলিস্ট খালি!</span>
                <span class="lang-en">Your wishlist is empty</span>
            </h3>
            <p>
                <span class="lang-bn">পছন্দের পণ্যগুলো সংরক্ষণ করুন — পরে সহজে খুঁজে পাবেন।</span>
                <span class="lang-en">Save items you love and find them again easily.</span>
            </p>
            <a href="index.php?show_products=1" class="cart-empty-cta">
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="lang-bn">শপিং শুরু করুন</span>
                <span class="lang-en">Start Shopping</span>
            </a>
        </div>

    <?php else: ?>

        <!-- ==================== HEADER ==================== -->
        <div class="wishlist-page-header">
            <h1 class="wishlist-title">
                <span class="wishlist-title-heart"><i class="fa-solid fa-heart"></i></span>
                <span class="lang-bn">আমার উইশলিস্ট</span>
                <span class="lang-en">My Wishlist</span>
                <span class="cart-title-badge"><?= count($wishlist_products) ?></span>
            </h1>
        </div>

        <form action="wishlist.php" method="POST" id="wishlistForm">

            <!-- ==================== TOOLBAR ==================== -->
            <div class="wishlist-toolbar">
                <label class="wishlist-selectall">
                    <input type="checkbox" id="selectAll">
                    <span class="lang-bn">সব নির্বাচন করুন</span>
                    <span class="lang-en">Select all</span>
                </label>

                <div class="wishlist-actions">
                    <button type="submit" name="bulk_add_cart" class="wishlist-action-btn is-primary">
                        <i class="fa-solid fa-cart-plus"></i>
                        <span class="lang-bn">কার্টে যোগ করুন</span>
                        <span class="lang-en">Add to Cart</span>
                    </button>
                    <button type="submit" name="bulk_remove"
                            class="wishlist-action-btn is-danger"
                            onclick="return confirm('নির্বাচিত পণ্যগুলো মুছে ফেলতে চান?');">
                        <i class="fa-solid fa-trash-can"></i>
                        <span class="lang-bn">মুছুন</span>
                        <span class="lang-en">Remove</span>
                    </button>
                </div>
            </div>

            <!-- ==================== GRID ==================== -->
            <div class="wishlist-grid">
                <?php foreach ($wishlist_products as $prod):
                    $img_src = !empty($prod['img'])
                        ? 'uploads/' . htmlspecialchars($prod['img'])
                        : 'assets/images/default.jpg';
                    $has_discount = (!empty($prod['discount_price']) && $prod['discount_price'] > 0 && $prod['discount_price'] < $prod['price']);
                    $current_price = $has_discount ? $prod['discount_price'] : $prod['price'];

                    $stock_qty = (int)($prod['stock_quantity'] ?? 0);
                    $stock_out = $stock_qty <= 0;
                ?>
                    <div class="wishlist-card <?= $stock_out ? 'is-stock-out' : '' ?>">

                        <div class="wishlist-card-media">
                            <input type="checkbox"
                                   class="wishlist-card-checkbox item-checkbox"
                                   name="selected_products[]"
                                   value="<?= (int)$prod['id'] ?>"
                                   aria-label="Select product">

                            <a href="wishlist.php?remove=<?= (int)$prod['id'] ?>"
                               class="wishlist-card-remove"
                               title="Remove from wishlist"
                               aria-label="Remove from wishlist"
                               onclick="return confirm('উইশলিস্ট থেকে সরাতে চান?');">
                                <i class="fa-solid fa-heart-crack"></i>
                            </a>

                            <a href="product-details.php?id=<?= (int)$prod['id'] ?>">
                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy">
                            </a>

                            <?php if ($stock_out): ?>
                                <div class="stock-out-overlay">
                                    <span class="stock-out-label">
                                        <i class="fa-solid fa-ban"></i>
                                        <span class="lang-bn">স্টক আউট</span>
                                        <span class="lang-en">Stock Out</span>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="wishlist-card-body">
                            <h3 class="wishlist-card-title">
                                <a href="product-details.php?id=<?= (int)$prod['id'] ?>">
                                    <?= htmlspecialchars($prod['name']) ?>
                                </a>
                            </h3>

                            <div class="wishlist-card-price">
                                ৳ <?= number_format($current_price, 2) ?>
                                <?php if ($has_discount): ?>
                                    <span class="was">৳ <?= number_format($prod['price'], 2) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="wishlist-card-actions">
                                <?php if ($stock_out): ?>
                                    <button type="button" class="wishlist-add-cart" disabled>
                                        <i class="fa-solid fa-ban"></i>
                                        <span class="lang-bn">স্টক আউট</span>
                                        <span class="lang-en">Stock Out</span>
                                    </button>
                                <?php else: ?>
                                    <a href="wishlist.php?add_to_cart=<?= (int)$prod['id'] ?>" class="wishlist-add-cart">
                                        <i class="fa-solid fa-cart-plus"></i>
                                        <span class="lang-bn">কার্টে নিন</span>
                                        <span class="lang-en">Add</span>
                                    </a>
                                <?php endif; ?>
                                <a href="product-details.php?id=<?= (int)$prod['id'] ?>"
                                   class="wishlist-view-btn"
                                   title="View details"
                                   aria-label="View details">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </form>

        <script>
            // Select-all toggle
            (function () {
                const selectAll = document.getElementById('selectAll');
                if (!selectAll) return;
                selectAll.addEventListener('change', function () {
                    document.querySelectorAll('.item-checkbox').forEach(cb => {
                        cb.checked = this.checked;
                    });
                });
            })();
        </script>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>