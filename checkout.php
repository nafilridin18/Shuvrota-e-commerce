<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';
require_once __DIR__ . '/includes/coupon_helper.php';
require_once __DIR__ . '/includes/csrf.php';

// --- Order Now (Direct Checkout) Logic ---
$is_direct_checkout = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && !isset($_POST['place_order'])) {
    $p_id  = (int)$_POST['product_id'];
    $qty   = (int)($_POST['quantity'] ?? 1);
    $size  = $_POST['size'] ?? 'Free Size';
    $color = $_POST['color'] ?? 'Standard';

    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$p_id]);
        $prod = $stmt->fetch();
        if ($prod) {
            $price = (!empty($prod['discount_price']) && $prod['discount_price'] > 0) ? $prod['discount_price'] : $prod['price'];
            $_SESSION['direct_cart'] = [
                [
                    'product_id' => $prod['id'],
                    'title'      => $prod['name'],
                    'price'      => $price,
                    'qty'        => $qty,
                    'size'       => $size,
                    'color'      => $color
                ]
            ];
            $is_direct_checkout = true;
        }
    } catch (Exception $e) {}
}

if (isset($_SESSION['direct_cart']) && (isset($_POST['place_order']) || $is_direct_checkout)) {
    $cart = $_SESSION['direct_cart'];
    $is_direct_checkout = true;
} else {
    $cart = $_SESSION['cart'] ?? [];
}

$success_order_number = '';

if (empty($cart) && empty($success_order_number)) {
    header('Location: cart.php');
    exit;
}

// M-4: prices were only ever saved into the session once, when an item
// was added to the cart. If an admin changed a product's price (or its
// discount) afterward, the customer's cart kept the old number all the
// way to checkout. Re-fetch current prices from the DB here so the
// displayed total — and the total actually charged — always reflects
// today's price.
if (!empty($cart)) {
    $ids = [];
    foreach ($cart as $item) {
        $pid = (int)($item['product_id'] ?? $item['id'] ?? 0);
        if ($pid > 0) $ids[$pid] = true;
    }
    if (!empty($ids)) {
        $idList = implode(',', array_map('intval', array_keys($ids)));
        $priceRows = $pdo->query("SELECT id, price, discount_price FROM products WHERE id IN ($idList)")->fetchAll(PDO::FETCH_ASSOC);
        $livePrices = [];
        foreach ($priceRows as $row) {
            $livePrices[(int)$row['id']] = (!empty($row['discount_price']) && $row['discount_price'] > 0 && $row['discount_price'] < $row['price'])
                ? (float)$row['discount_price']
                : (float)$row['price'];
        }

        $sessionKey = $is_direct_checkout ? 'direct_cart' : 'cart';
        foreach ($cart as $key => $item) {
            $pid = (int)($item['product_id'] ?? $item['id'] ?? 0);
            if (isset($livePrices[$pid])) {
                $cart[$key]['price'] = $livePrices[$pid];
                if (isset($_SESSION[$sessionKey][$key])) {
                    $_SESSION[$sessionKey][$key]['price'] = $livePrices[$pid];
                }
            }
        }
    }
}

$subtotal = 0;
foreach ($cart as $item) {
    $price = $item['price'] ?? 0;
    $qty   = $item['qty'] ?? 1;
    $subtotal += $price * $qty;
}

$discount_amount = 0;
$applied_coupon_code = '';

if (isset($_SESSION['applied_coupon']['code'])) {
    $preview = validate_and_calculate_coupon($pdo, $_SESSION['applied_coupon']['code'], $subtotal, $_SESSION['customer_id'] ?? null);
    if ($preview['valid']) {
        $discount_amount     = $preview['discount'];
        $applied_coupon_code = $preview['coupon']['code'];
    } else {
        // Coupon no longer applies to the current cart (expired, cart shrank
        // below min_order_amount, etc.) — drop it instead of silently
        // showing a discount that wouldn't survive order placement.
        unset($_SESSION['applied_coupon']);
    }
}

$error = '';

try {
    $avail_coupons = $pdo->query("SELECT * FROM coupons")->fetchAll();
} catch (Exception $e) {
    $avail_coupons = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_coupon']) || isset($_POST['place_order'])) {
        csrf_require();
    }
    /* =====================================================================
       NO-JS FALLBACK — coupon apply via full form submission.
       With JS on, footer.php intercepts the click and calls
       apply_coupon_ajax.php instead, so this block never runs.
       ===================================================================== */
    if (isset($_POST['apply_coupon'])) {
        $coupon_code = trim($_POST['coupon_code'] ?? '');
        try {
            $result = validate_and_calculate_coupon($pdo, $coupon_code, $subtotal, $_SESSION['customer_id'] ?? null);

            if ($result['valid']) {
                $_SESSION['applied_coupon'] = ['code' => $result['coupon']['code']];
                $applied_coupon_code = $result['coupon']['code'];
                $discount_amount     = $result['discount'];
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            $error = "কুপন এপ্লাই করতে সমস্যা হয়েছে।";
        }
    }
    elseif (isset($_POST['place_order'])) {
        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $area    = trim($_POST['area'] ?? 'inside');

        $shipping_cost    = ($area === 'outside') ? 130.00 : 70.00;
        $shipping_area_id = ($area === 'outside') ? 2 : 1;

        // Re-derive the discount from the coupon CODE against the CURRENT
        // cart subtotal — never trust a discount amount saved earlier in
        // the session. This is the fix for C-5: previously a fixed session
        // amount was reused even after the cart shrank, which could push
        // total_amount negative.
        $coupon_id = null;
        if (isset($_SESSION['applied_coupon']['code'])) {
            $result = validate_and_calculate_coupon($pdo, $_SESSION['applied_coupon']['code'], $subtotal, $_SESSION['customer_id'] ?? null);
            if ($result['valid']) {
                $discount_amount = $result['discount'];
                $coupon_id       = (int)$result['coupon']['id'];
            } else {
                // No longer valid for this cart (changed since it was applied) — drop it, don't block the order.
                $discount_amount = 0;
                unset($_SESSION['applied_coupon']);
            }
        }

        // Never let a coupon push the total below zero, no matter what.
        $total_amount = max(0, $subtotal - $discount_amount) + $shipping_cost;

        if (!empty($name) && !empty($phone) && !empty($address)) {
            try {
                $pdo->beginTransaction();

                $order_number = 'SHV-' . date('Ymd') . '-' . rand(1000, 9999);
                $customer_id  = $_SESSION['customer_id'] ?? null;

                $stmt = $pdo->prepare("INSERT INTO orders (order_number, customer_id, guest_name, guest_phone, guest_email, shipping_name, shipping_phone, shipping_address, shipping_area_id, subtotal, discount_amount, delivery_charge, total_amount, coupon_id, payment_method, payment_status, status, placed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'cod', 'pending', 'new', NOW())");
                $stmt->execute([
                    $order_number, $customer_id, $name, $phone, $email, $name, $phone, $address,
                    $shipping_area_id, $subtotal, $discount_amount, $shipping_cost, $total_amount, $coupon_id
                ]);
                $order_id = $pdo->lastInsertId();

                foreach ($cart as $item) {
                    $p_id     = $item['product_id'] ?? ($item['id'] ?? 0);
                    $v_id     = $item['variant_id'] ?? null;
                    $p_title  = $item['title'] ?? 'Product';
                    $p_price  = $item['price'] ?? 0;
                    $p_qty    = $item['qty'] ?? 1;
                    $p_size   = $item['size'] ?? 'Free Size';
                    $p_color  = $item['color'] ?? 'Standard';
                    $line_tot = $p_price * $p_qty;

                    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, color, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $itemStmt->execute([
                        $order_id, $p_id, $v_id, $p_title, $p_size, $p_color, $p_price, $p_qty, $line_tot
                    ]);

                    if (!empty($customer_id)) {
                        $delWish = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
                        $delWish->execute([$customer_id, $p_id]);
                    }
                }

                // Record coupon usage so usage_limit / usage_limit_per_customer
                // are actually enforceable next time (previously used_count
                // was never incremented, making the limit meaningless).
                if ($coupon_id !== null) {
                    $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$coupon_id]);
                    $pdo->prepare("INSERT INTO coupon_usages (coupon_id, customer_id, order_id) VALUES (?, ?, ?)")
                        ->execute([$coupon_id, $customer_id, $order_id]);
                }

                $pdo->commit();
                $success_order_number = $order_number;

                if ($is_direct_checkout) {
                    unset($_SESSION['direct_cart']);
                } else {
                    unset($_SESSION['cart']);
                }
                unset($_SESSION['applied_coupon']);

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('checkout place_order error: ' . $e->getMessage());
                $error = "অর্ডার সম্পন্ন করতে সমস্যা হয়েছে। আবার চেষ্টা করুন।";
            }
        } else {
            $error = "অনুগ্রহ করে সব প্রয়োজনীয় তথ্য পূরণ করুন।";
        }
    }
}

$pageTitle = 'চেকআউট - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4 my-md-5" style="max-width: 1100px;">

    <?php if (!empty($success_order_number)): ?>
        <!-- ================= SUCCESS ================= -->
        <div class="success-card">
            <div class="success-icon">
                <i class="fa-solid fa-check"></i>
            </div>

            <h2 class="fw-bold mb-2" style="font-family: var(--font-display); color: var(--maroon-700);">
                <span class="lang-bn">অর্ডার সফল হয়েছে!</span>
                <span class="lang-en">Order Placed Successfully!</span>
            </h2>
            <p class="text-muted mb-0">
                <span class="lang-bn">আমরা খুব শীঘ্রই আপনার সাথে যোগাযোগ করব।</span>
                <span class="lang-en">We'll contact you shortly to confirm your order.</span>
            </p>

            <div class="success-order-box">
                <small>
                    <span class="lang-bn">অর্ডার নম্বর</span>
                    <span class="lang-en">Order Number</span>
                </small>
                <h4><?= htmlspecialchars($success_order_number) ?></h4>
            </div>

            <p class="text-muted small mb-4">
                <span class="lang-bn">এই নম্বর দিয়ে অর্ডার ট্র্যাকিং পেজে স্ট্যাটাস দেখতে পারবেন।</span>
                <span class="lang-en">Use this number on the tracking page to check your order status.</span>
            </p>

            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="index.php" class="btn btn-outline-dark rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-house me-1"></i>
                    <span class="lang-bn">হোমে ফিরুন</span><span class="lang-en">Back to Home</span>
                </a>
                <a href="track.php" class="btn btn-danger rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-truck-fast me-1"></i>
                    <span class="lang-bn">অর্ডার ট্র্যাক</span><span class="lang-en">Track Order</span>
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- ================= CHECKOUT ================= -->

        <!-- Step indicator -->
        <div class="checkout-steps">
            <div class="checkout-step active">
                <span class="checkout-step-num"><i class="fa-solid fa-bag-shopping"></i></span>
                <span class="lang-bn">কার্ট</span><span class="lang-en">Cart</span>
            </div>
            <div class="checkout-step-line"></div>
            <div class="checkout-step active">
                <span class="checkout-step-num">2</span>
                <span class="lang-bn">ডেলিভারি</span><span class="lang-en">Delivery</span>
            </div>
            <div class="checkout-step-line"></div>
            <div class="checkout-step">
                <span class="checkout-step-num">3</span>
                <span class="lang-bn">কনফার্ম</span><span class="lang-en">Confirm</span>
            </div>
        </div>

        <h2 class="text-center fw-bold mb-4" style="font-family: var(--font-display);">
            <span class="lang-bn">চেকআউট</span>
            <span class="lang-en">Checkout</span>
        </h2>

        <?php if ($error): ?>
            <div class="alert alert-danger small">
                <i class="fa-solid fa-circle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="checkoutForm">
            <?= csrf_field() ?>
            <?php if ($is_direct_checkout): ?>
                <input type="hidden" name="is_direct" value="1">
            <?php endif; ?>

            <div class="checkout-grid">

                <!-- ============ LEFT — FORM ============ -->
                <div>

                    <!-- Contact info -->
                    <div class="checkout-card mb-4">
                        <div class="checkout-card-header">
                            <i class="fa-solid fa-user"></i>
                            <h5>
                                <span class="lang-bn">যোগাযোগের তথ্য</span>
                                <span class="lang-en">Contact Information</span>
                            </h5>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="checkout-field mb-0">
                                    <label>
                                        <span class="lang-bn">আপনার নাম *</span>
                                        <span class="lang-en">Your Name *</span>
                                    </label>
                                    <input type="text" name="name" class="form-control"
                                           value="<?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="checkout-field mb-0">
                                    <label>
                                        <span class="lang-bn">মোবাইল নম্বর *</span>
                                        <span class="lang-en">Mobile Number *</span>
                                    </label>
                                    <input type="text" name="phone" class="form-control"
                                           value="<?= htmlspecialchars($_SESSION['customer_phone'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="checkout-field mb-0">
                                    <label>
                                        <span class="lang-bn">ইমেইল (ঐচ্ছিক)</span>
                                        <span class="lang-en">Email (Optional)</span>
                                    </label>
                                    <input type="email" name="email" class="form-control" placeholder="example@gmail.com">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery info -->
                    <div class="checkout-card mb-4">
                        <div class="checkout-card-header">
                            <i class="fa-solid fa-location-dot"></i>
                            <h5>
                                <span class="lang-bn">ডেলিভারি ঠিকানা</span>
                                <span class="lang-en">Delivery Address</span>
                            </h5>
                        </div>

                        <div class="checkout-field">
                            <label>
                                <span class="lang-bn">সম্পূর্ণ ঠিকানা *</span>
                                <span class="lang-en">Full Address *</span>
                            </label>
                            <textarea name="address" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="checkout-field mb-0">
                            <label>
                                <span class="lang-bn">ডেলিভারি এরিয়া</span>
                                <span class="lang-en">Delivery Area</span>
                            </label>
                            <select name="area" id="deliveryArea" class="form-select" onchange="updateTotal()">
                                <option value="inside"  data-charge="70">Inside Dhaka — ৳70.00</option>
                                <option value="outside" data-charge="130">Outside Dhaka — ৳130.00</option>
                            </select>

                            <small class="delivery-note">
                                <i class="fa-solid fa-circle-info"></i>
                                <span class="lang-bn">*ডেলিভারি চার্জ আপনার লোকেশন অনুযায়ী পরিবর্তিত হতে পারে।</span>
                                <span class="lang-en">*Delivery charges can vary according to your location.</span>
                            </small>
                        </div>
                    </div>

                    <!-- Coupon -->
                    <div class="checkout-card mb-4">
                        <div class="checkout-card-header">
                            <i class="fa-solid fa-ticket"></i>
                            <h5>
                                <span class="lang-bn">কুপন কোড</span>
                                <span class="lang-en">Coupon Code</span>
                            </h5>
                        </div>

                        <div class="coupon-widget">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-1">
                                <small class="fw-bold text-dark mb-0">
                                    <span class="lang-bn">যদি কুপন থাকে</span>
                                    <span class="lang-en">If you have a code</span>
                                </small>
                                <span class="coupon-toggle-btn cursor-pointer text-danger fw-semibold small" onclick="toggleAvailableCoupons()">
                                    <i class="fa-solid fa-gift me-1"></i>
                                    <span class="lang-bn">উপলব্ধ কুপন</span>
                                    <span class="lang-en">View Available Coupons</span>
                                </span>
                            </div>

                            <!-- Custom coupon input row -->
                            <div class="coupon-input-row" id="couponInputRow">
                                <div class="coupon-input-wrap">
                                    <i class="fa-solid fa-ticket coupon-input-icon"></i>
                                    <input type="text"
                                           name="coupon_code"
                                           id="couponCodeInput"
                                           class="coupon-input"
                                           placeholder="Enter code"
                                           autocomplete="off"
                                           value="<?= htmlspecialchars($applied_coupon_code) ?>">
                                    <button type="button"
                                            class="coupon-clear-btn"
                                            id="clearCouponBtn"
                                            aria-label="Remove coupon"
                                            style="<?= $applied_coupon_code ? '' : 'display:none;' ?>">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <button type="submit"
                                        name="apply_coupon"
                                        id="applyCouponBtn"
                                        class="coupon-apply-btn">
                                    <span class="coupon-apply-label">
                                        <span class="lang-bn">এপ্লাই</span><span class="lang-en">Apply</span>
                                    </span>
                                    <span class="coupon-apply-spinner"></span>
                                </button>
                            </div>

                            <!-- Feedback area -->
                            <div id="couponFeedback" class="coupon-feedback" role="status" aria-live="polite"></div>

                            <div id="couponListSection" class="mt-3 d-none">
                                <div class="small fw-bold text-danger mb-2">
                                    <span class="lang-bn">অ্যাভেইলেবল কুপন সমূহ:</span>
                                    <span class="lang-en">Available Coupons:</span>
                                </div>
                                <?php if (!empty($avail_coupons)): ?>
                                    <?php foreach ($avail_coupons as $cp):
                                        $min_o  = $cp['min_order_amount'] ?? 0;
                                        $diff   = $min_o - $subtotal;
                                        $d_type = $cp['type'] ?? 'percentage';
                                        $d_val  = $cp['value'] ?? 0;
                                    ?>
                                        <div class="p-2 mb-2 bg-white border rounded-2 small d-flex justify-content-between align-items-center flex-wrap gap-1">
                                            <div>
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($cp['code']) ?></span>
                                                <span class="text-success ms-1">
                                                    (<?= $d_type === 'percentage' ? $d_val.'%' : '৳'.$d_val ?> OFF)
                                                </span>
                                                <div class="text-muted" style="font-size: 11px;">
                                                    Min order: ৳<?= $min_o ?>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if ($subtotal >= $min_o): ?>
                                                    <span class="badge bg-success">Usable</span>
                                                <?php elseif ($diff <= 500): ?>
                                                    <span class="badge bg-warning text-dark">Add ৳<?= $diff ?> more</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">৳<?= $diff ?> to go</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="small text-muted">No coupons available right now.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ============ RIGHT — ORDER SUMMARY ============ -->
                <div>
                    <div class="order-summary-card">
                        <h5 class="fw-bold mb-3" style="font-family: var(--font-display); color: var(--maroon-700);">
                            <i class="fa-solid fa-receipt me-2"></i>
                            <span class="lang-bn">অর্ডার সামারি</span>
                            <span class="lang-en">Order Summary</span>
                        </h5>

                        <!-- Items -->
                        <div style="max-height: 260px; overflow-y: auto; margin-bottom: 12px;">
                            <?php foreach ($cart as $item): ?>
                                <div class="d-flex justify-content-between align-items-start py-2"
                                     style="border-bottom: 1px dashed var(--border-soft);">
                                    <div class="pe-2">
                                        <div class="fw-semibold" style="font-size: 0.85rem; color: var(--ink-900);">
                                            <?= htmlspecialchars($item['title'] ?? 'Item') ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($item['size'] ?? 'Free Size') ?> ·
                                            <?= htmlspecialchars($item['color'] ?? 'Standard') ?> ·
                                            ×<?= (int)($item['qty'] ?? 1) ?>
                                        </small>
                                    </div>
                                    <span class="fw-bold" style="font-size: 0.85rem; color: var(--maroon-700); white-space: nowrap;">
                                        ৳ <?= number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1), 2) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-summary-item">
                            <span><span class="lang-bn">সাবটোটাল</span><span class="lang-en">Subtotal</span></span>
                            <span>৳ <span id="subtotalVal"><?= number_format($subtotal, 2, '.', '') ?></span></span>
                        </div>

                        <div class="order-summary-item text-success coupon-discount-row"
                             id="discountRow"
                             style="<?= $discount_amount > 0 ? '' : 'display:none;' ?>">
                            <span>
                                <i class="fa-solid fa-tag me-1"></i>
                                <span class="lang-bn">কুপন ডিসকাউন্ট</span>
                                <span class="lang-en">Coupon Discount</span>
                                <span class="coupon-applied-chip"
                                      id="appliedCouponChip"
                                      style="<?= $applied_coupon_code ? '' : 'display:none;' ?>">
                                    <?= htmlspecialchars($applied_coupon_code) ?>
                                </span>
                            </span>
                            <span>- ৳ <span id="discountVal"><?= number_format($discount_amount, 2, '.', '') ?></span></span>
                        </div>

                        <div class="order-summary-item">
                            <span><span class="lang-bn">ডেলিভারি</span><span class="lang-en">Delivery</span></span>
                            <span>৳ <span id="shippingVal">70.00</span></span>
                        </div>

                        <div class="order-summary-item total">
                            <span><span class="lang-bn">সর্বমোট</span><span class="lang-en">Grand Total</span></span>
                            <span class="amount">৳ <span id="grandTotalVal"><?= number_format(($subtotal - $discount_amount) + 70, 2, '.', '') ?></span></span>
                        </div>

                        <div class="order-summary-item" style="padding-top: 12px;">
                            <span class="text-muted small">
                                <span class="lang-bn">পেমেন্ট:</span>
                                <span class="lang-en">Payment:</span>
                            </span>
                            <span class="badge bg-success">Cash on Delivery</span>
                        </div>

                        <button type="submit" name="place_order" class="place-order-btn">
                            <i class="fa-solid fa-shield-check me-1"></i>
                            <span class="lang-bn">অর্ডার কনফার্ম করুন</span>
                            <span class="lang-en">Confirm Order</span>
                        </button>

                        <div class="trust-row">
                            <span><i class="fa-solid fa-shield-halved"></i> COD</span>
                            <span><i class="fa-solid fa-truck-fast"></i> Fast</span>
                            <span><i class="fa-solid fa-hand-holding-heart"></i> Handmade</span>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    <?php endif; ?>
</div>

<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* =====================================================================
   STATE
   ===================================================================== */
window.couponState = {
    subtotal:   <?= (float)$subtotal ?>,
    discount:   <?= (float)$discount_amount ?>,
    appliedCode: <?= json_encode($applied_coupon_code) ?>,
    isDirect:   <?= $is_direct_checkout ? 'true' : 'false' ?>
};

/* =====================================================================
   TOGGLE AVAILABLE COUPONS PANEL
   ===================================================================== */
function toggleAvailableCoupons() {
    const section = document.getElementById('couponListSection');
    if (section) section.classList.toggle('d-none');
}

/* =====================================================================
   GRAND TOTAL RECALCULATION
   ===================================================================== */
function updateTotal() {
    const areaSelect = document.getElementById('deliveryArea');
    if (!areaSelect) return;

    const selectedOption = areaSelect.options[areaSelect.selectedIndex];
    const shippingCharge = parseFloat(selectedOption.getAttribute('data-charge')) || 70;

    const subtotal = parseFloat(document.getElementById('subtotalVal').innerText) || 0;
    const discount = parseFloat(window.couponState.discount) || 0;

    const grandTotal = (subtotal - discount) + shippingCharge;

    document.getElementById('shippingVal').innerText = shippingCharge.toFixed(2);
    document.getElementById('grandTotalVal').innerText = grandTotal.toFixed(2);
}

/* =====================================================================
   COUPON UI HELPERS
   ===================================================================== */
function setCouponLoading(isLoading) {
    const btn = document.getElementById('applyCouponBtn');
    if (!btn) return;
    if (isLoading) {
        btn.classList.add('is-loading');
        btn.disabled = true;
    } else {
        btn.classList.remove('is-loading');
        btn.disabled = false;
    }
}

function shakeCouponRow() {
    const row = document.getElementById('couponInputRow');
    if (!row) return;
    row.classList.remove('is-shaking');
    void row.offsetWidth;
    row.classList.add('is-shaking');
    setTimeout(() => row.classList.remove('is-shaking'), 600);
}

function flashCouponSuccess() {
    const row = document.getElementById('couponInputRow');
    if (!row) return;
    row.classList.add('is-success');
    setTimeout(() => row.classList.remove('is-success'), 1800);
}

function showCouponFeedback(type, message) {
    const el = document.getElementById('couponFeedback');
    if (!el) return;
    el.className = 'coupon-feedback is-visible is-' + type;
    const iconClass = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    el.innerHTML = `<i class="fa-solid ${iconClass}"></i><span>${message}</span>`;
}

function clearCouponFeedback() {
    const el = document.getElementById('couponFeedback');
    if (!el) return;
    el.className = 'coupon-feedback';
    el.innerHTML = '';
}

function showDiscountRow(discount, code) {
    const row = document.getElementById('discountRow');
    const val = document.getElementById('discountVal');
    const chip = document.getElementById('appliedCouponChip');
    const clearBtn = document.getElementById('clearCouponBtn');

    if (discount > 0) {
        row.style.display = '';
        // restart slide-in animation
        row.classList.remove('coupon-discount-row');
        void row.offsetWidth;
        row.classList.add('coupon-discount-row');
        val.innerText = parseFloat(discount).toFixed(2);
        chip.style.display = '';
        chip.innerText = code;
        if (clearBtn) clearBtn.style.display = '';
    } else {
        row.style.display = 'none';
        val.innerText = '0.00';
        chip.style.display = 'none';
        chip.innerText = '';
        if (clearBtn) clearBtn.style.display = 'none';
    }
}

/* =====================================================================
   APPLY COUPON — AJAX
   ===================================================================== */
function applyCouponAjax() {
    const input   = document.getElementById('couponCodeInput');
    const code    = (input.value || '').trim();
    const isDirect = window.couponState.isDirect;

    if (!code) {
        shakeCouponRow();
        showCouponFeedback('error', 'কুপন কোড লিখুন। / Please enter a coupon code.');
        return;
    }

    clearCouponFeedback();
    setCouponLoading(true);

    const fd = new FormData();
    fd.append('action', 'apply');
    fd.append('coupon_code', code);
    if (isDirect) fd.append('is_direct', '1');

    fetch('apply_coupon_ajax.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF_TOKEN }
    })
        .then(r => r.json())
        .then(data => {
            setCouponLoading(false);

            if (!data.success) {
                shakeCouponRow();
                showCouponFeedback('error', data.message || 'Could not apply coupon.');
                return;
            }

            // ---- Success ----
            window.couponState.discount    = parseFloat(data.discount);
            window.couponState.appliedCode = data.code;

            flashCouponSuccess();
            showCouponFeedback('success', data.message || 'Coupon applied!');
            showDiscountRow(data.discount, data.code);
            updateTotal();
        })
        .catch(() => {
            setCouponLoading(false);
            shakeCouponRow();
            showCouponFeedback('error', 'Network error. Please try again.');
        });
}

/* =====================================================================
   REMOVE COUPON — AJAX
   ===================================================================== */
function removeCouponAjax() {
    clearCouponFeedback();
    setCouponLoading(true);

    const fd = new FormData();
    fd.append('action', 'remove');

    fetch('apply_coupon_ajax.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF_TOKEN }
    })
        .then(r => r.json())
        .then(data => {
            setCouponLoading(false);
            if (!data.success) {
                showCouponFeedback('error', data.message || 'Could not remove coupon.');
                return;
            }

            window.couponState.discount    = 0;
            window.couponState.appliedCode = '';

            const input = document.getElementById('couponCodeInput');
            if (input) input.value = '';

            showDiscountRow(0, '');
            updateTotal();
            showCouponFeedback('success', 'কুপন সরানো হয়েছে। / Coupon removed.');
        })
        .catch(() => {
            setCouponLoading(false);
            showCouponFeedback('error', 'Network error. Please try again.');
        });
}

/* =====================================================================
   WIRE UP EVENTS
   ===================================================================== */
document.addEventListener('DOMContentLoaded', function () {
    // Recalculate the total from the current state on load
    updateTotal();

    const applyBtn = document.getElementById('applyCouponBtn');
    const clearBtn = document.getElementById('clearCouponBtn');
    const input    = document.getElementById('couponCodeInput');

    if (applyBtn) {
        // Prevent the whole checkout form from submitting when
        // the user clicks "Apply". Without JS, this button would
        // still submit and hit the PHP fallback in checkout.php.
        applyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            applyCouponAjax();
        });
    }

    if (input) {
        // Pressing Enter inside the coupon field triggers Apply via AJAX
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyCouponAjax();
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
            e.preventDefault();
            removeCouponAjax();
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>