<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';

/* =====================================================================
   ADD TO CART (POST) — kept as a no-JS fallback.
   In normal use, AJAX interception in footer.php sends the POST to
   add_to_cart_ajax.php so this block never runs.
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $qty        = max(1, (int)($_POST['quantity'] ?? 1));
    $size       = trim($_POST['size'] ?? 'Free Size');
    $color      = trim($_POST['color'] ?? 'Standard');

    try {
        $stmt = $pdo->prepare("
            SELECT p.*,
                   (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) AS img
            FROM products p WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            $price = (!empty($product['discount_price']) && $product['discount_price'] > 0)
                ? $product['discount_price']
                : $product['price'];

            $cart_key = $product_id . '_' . md5($size . '_' . $color);

            if (isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$cart_key] = [
                    'id'    => $product_id,
                    'title' => $product['name'],
                    'price' => $price,
                    'qty'   => $qty,
                    'size'  => $size,
                    'color' => $color,
                    'image' => $product['img'] ?? '',
                ];
            }
        }
    } catch (Exception $e) {}

    header('Location: cart.php');
    exit;
}

/* =====================================================================
   REMOVE ITEM
   ===================================================================== */
if (isset($_GET['action']) && $_GET['action'] === 'remove') {
    $key = $_GET['key'] ?? '';
    if ($key !== '') unset($_SESSION['cart'][$key]);
    header('Location: cart.php');
    exit;
}

/* =====================================================================
   UPDATE QUANTITY
   ===================================================================== */
if (isset($_GET['action']) && $_GET['action'] === 'update_qty') {
    $key = $_GET['key'] ?? '';
    $qty = (int)($_GET['qty'] ?? 1);

    if ($key !== '' && isset($_SESSION['cart'][$key])) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$key]);
        } else {
            $_SESSION['cart'][$key]['qty'] = $qty;
        }
    }
    header('Location: cart.php');
    exit;
}

/* =====================================================================
   FETCH CART
   ===================================================================== */
$cart         = $_SESSION['cart'] ?? [];
$subtotal     = 0;
$total_items  = 0;

foreach ($cart as $item) {
    $subtotal    += $item['price'] * $item['qty'];
    $total_items += $item['qty'];
}

$pageTitle = 'শপিং কার্ট - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="cart-page">

    <?php if (empty($cart)): ?>
        <!-- ==================== EMPTY STATE ==================== -->
        <div class="cart-empty">
            <div class="cart-empty-icon">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <h3>
                <span class="lang-bn">আপনার কার্ট খালি!</span>
                <span class="lang-en">Your cart is empty</span>
            </h3>
            <p>
                <span class="lang-bn">আমাদের হাতে তৈরি পণ্যগুলো ঘুরে দেখুন এবং আপনার পছন্দের কিছু যোগ করুন।</span>
                <span class="lang-en">Browse our handcrafted collections and add something you love.</span>
            </p>
            <a href="index.php?show_products=1" class="cart-empty-cta">
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="lang-bn">শপিং শুরু করুন</span>
                <span class="lang-en">Start Shopping</span>
            </a>
        </div>

    <?php else: ?>

        <!-- ==================== HEADER ==================== -->
        <div class="cart-page-header">
            <h1 class="cart-title">
                <span class="lang-bn">আপনার কার্ট</span>
                <span class="lang-en">Shopping Cart</span>
                <span class="cart-title-badge">
                    <?= (int)$total_items ?> <span class="lang-bn">আইটেম</span><span class="lang-en">items</span>
                </span>
            </h1>
            <a href="index.php?show_products=1" class="cart-continue-btn">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="lang-bn">কেনাকাটা চালিয়ে যান</span>
                <span class="lang-en">Continue Shopping</span>
            </a>
        </div>

        <!-- ==================== LAYOUT ==================== -->
        <div class="cart-layout">

            <!-- ITEMS -->
            <div class="cart-items-list">
                <?php foreach ($cart as $key => $item):
                    $img_src = !empty($item['image'])
                        ? 'uploads/' . htmlspecialchars($item['image'])
                        : 'assets/images/default.jpg';
                    $line_total = $item['price'] * $item['qty'];
                    $encoded_key = urlencode($key);
                ?>
                    <div class="cart-item">

                        <a href="product-details.php?id=<?= (int)$item['id'] ?>" class="cart-item-image">
                            <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                        </a>

                        <div class="cart-item-details">

                            <div class="cart-item-header">
                                <h3 class="cart-item-title">
                                    <a href="product-details.php?id=<?= (int)$item['id'] ?>">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </h3>
                                <a href="cart.php?action=remove&key=<?= $encoded_key ?>"
                                   class="cart-item-remove"
                                   aria-label="Remove item">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            </div>

                            <div class="cart-item-meta">
                                <span class="cart-chip">
                                    <i class="fa-solid fa-ruler"></i>
                                    <?= htmlspecialchars($item['size']) ?>
                                </span>
                                <span class="cart-chip">
                                    <i class="fa-solid fa-palette"></i>
                                    <?= htmlspecialchars($item['color']) ?>
                                </span>
                            </div>

                            <div class="cart-item-footer">

                                <div class="qty-control">
                                    <a href="cart.php?action=update_qty&key=<?= $encoded_key ?>&qty=<?= $item['qty'] - 1 ?>"
                                       class="qty-btn <?= $item['qty'] <= 1 ? 'is-disabled' : '' ?>"
                                       aria-label="Decrease">
                                        <i class="fa-solid fa-minus"></i>
                                    </a>
                                    <span class="qty-value"><?= (int)$item['qty'] ?></span>
                                    <a href="cart.php?action=update_qty&key=<?= $encoded_key ?>&qty=<?= $item['qty'] + 1 ?>"
                                       class="qty-btn"
                                       aria-label="Increase">
                                        <i class="fa-solid fa-plus"></i>
                                    </a>
                                </div>

                                <div class="cart-item-price">
                                    <span class="unit-price">৳ <?= number_format($item['price'], 2) ?> × <?= (int)$item['qty'] ?></span>
                                    <span class="line-total">৳ <?= number_format($line_total, 2) ?></span>
                                </div>

                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- SUMMARY -->
            <aside class="cart-summary">
                <div class="summary-card">
                    <h3 class="summary-title">
                        <span class="lang-bn">অর্ডার সামারি</span>
                        <span class="lang-en">Order Summary</span>
                    </h3>

                    <div class="summary-row">
                        <span><span class="lang-bn">সাবটোটাল</span><span class="lang-en">Subtotal</span></span>
                        <span>৳ <?= number_format($subtotal, 2) ?></span>
                    </div>

                    <div class="summary-row is-muted">
                        <span><span class="lang-bn">ডেলিভারি চার্জ</span><span class="lang-en">Delivery</span></span>
                        <span><span class="lang-bn">চেকআউটে হিসাব</span><span class="lang-en">Calculated at checkout</span></span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row summary-total">
                        <span><span class="lang-bn">সর্বমোট</span><span class="lang-en">Total</span></span>
                        <span>৳ <?= number_format($subtotal, 2) ?></span>
                    </div>

                    <a href="checkout.php" class="checkout-cta">
                        <i class="fa-solid fa-lock"></i>
                        <span class="lang-bn">চেকআউট করুন</span>
                        <span class="lang-en">Proceed to Checkout</span>
                    </a>

                    <div class="summary-trust">
                        <span><i class="fa-solid fa-truck-fast"></i> <span class="lang-bn">দ্রুত ডেলিভারি</span><span class="lang-en">Fast Delivery</span></span>
                        <span><i class="fa-solid fa-shield-halved"></i> <span class="lang-bn">নিরাপদ</span><span class="lang-en">Secure</span></span>
                    </div>
                </div>
            </aside>

        </div>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>