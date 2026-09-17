<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';

// যদি 'Add to Cart' ফর্ম থেকে POST রিকোয়েস্ট আসে
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $qty        = intval($_POST['quantity'] ?? 1);
    $size       = trim($_POST['size'] ?? 'Free Size');
    $color      = trim($_POST['color'] ?? 'Standard');

    try {
        // প্রোডাক্টের তথ্য এবং প্রথম ছবি ডাটাবেজ থেকে আনা
        $stmt = $pdo->prepare("
            SELECT p.*, 
            (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as img 
            FROM products p WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            $title = $product['name'];
            $price = (!empty($product['discount_price']) && $product['discount_price'] > 0) ? $product['discount_price'] : $product['price'];
            $image = $product['img'] ?? '';

            $cart_key = $product_id . '_' . md5($size . '_' . $color);

            if (isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$cart_key] = [
                    'id'    => $product_id,
                    'title' => $title,
                    'price' => $price,
                    'qty'   => $qty,
                    'size'  => $size,
                    'color' => $color,
                    'image' => $image
                ];
            }
        }
    } catch (Exception $e) {
        // Ignore or handle error
    }

    header('Location: cart.php');
    exit;
}

// কার্ট থেকে আইটেম মুছে ফেলার লজিক
if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $key = $_GET['key'];
    unset($_SESSION['cart'][$key]);
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
}

$pageTitle = 'শপিং কার্ট - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4 my-md-5" style="max-width: 950px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="fw-bold mb-0">
            <i class="fa-solid fa-bag-shopping me-2 text-danger"></i>
            <span class="lang-bn">আপনার শপিং কার্ট</span>
            <span class="lang-en">Your Shopping Cart</span>
        </h2>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>
            <span class="lang-bn">কেনাকাটা চালিয়ে যান</span>
            <span class="lang-en">Continue Shopping</span>
        </a>
    </div>

    <?php if(empty($cart)): ?>
        <div class="card p-5 text-center border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-cart-shopping fs-1 text-ink-muted mb-3"></i>
            <h4 class="text-muted mb-3">
                <span class="lang-bn">আপনার কার্ট খালি!</span>
                <span class="lang-en">Your cart is empty!</span>
            </h4>
            <a href="index.php" class="btn btn-danger rounded-pill align-self-center px-4">
                <span class="lang-bn">শপিং শুরু করুন</span>
                <span class="lang-en">Start Shopping</span>
            </a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">
                                <span class="lang-bn">পণ্য</span><span class="lang-en">Product</span>
                            </th>
                            <th>
                                <span class="lang-bn">সাইজ/কালার</span><span class="lang-en">Size/Color</span>
                            </th>
                            <th>
                                <span class="lang-bn">দাম</span><span class="lang-en">Price</span>
                            </th>
                            <th>
                                <span class="lang-bn">পরিমাণ</span><span class="lang-en">Qty</span>
                            </th>
                            <th>
                                <span class="lang-bn">মোট</span><span class="lang-en">Total</span>
                            </th>
                            <th class="text-center">
                                <span class="lang-bn">মুছুন</span><span class="lang-en">Action</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart as $key => $item): ?>
                            <?php
                                $img_src = !empty($item['image']) ? 'uploads/' . htmlspecialchars($item['image']) : 'assets/images/default.jpg';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= $img_src ?>" width="50" height="50" class="rounded object-fit-cover" alt="<?= htmlspecialchars($item['title']) ?>">
                                        <span class="fw-semibold"><?= htmlspecialchars($item['title']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['size']) ?> / <?= htmlspecialchars($item['color']) ?></span>
                                </td>
                                <td>৳ <?= number_format($item['price'], 2) ?></td>
                                <td><?= $item['qty'] ?></td>
                                <td class="fw-bold text-danger">৳ <?= number_format($item['price'] * $item['qty'], 2) ?></td>
                                <td class="text-center">
                                    <a href="cart.php?action=remove&key=<?= $key ?>" class="btn btn-sm btn-outline-danger rounded-circle" aria-label="মুছুন"><i class="fa-solid fa-trash-can"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-4 border-0 shadow-sm rounded-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fs-5 text-muted">
                    <span class="lang-bn">পণ্যের মোট দাম:</span>
                    <span class="lang-en">Subtotal:</span>
                </span>
                <span class="fs-4 fw-bold text-dark">৳ <?= number_format($subtotal, 2) ?></span>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <a href="checkout.php" class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow-sm w-100 w-md-auto">
                    <span class="lang-bn">চেকআউট করুন</span>
                    <span class="lang-en">Proceed to Checkout</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
