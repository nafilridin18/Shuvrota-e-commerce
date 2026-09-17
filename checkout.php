<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$subtotal = 0;
foreach ($cart as $item) {
    $price = $item['price'] ?? 0;
    $qty   = $item['qty'] ?? 1;
    $subtotal += $price * $qty;
}

$discount_amount = 0;
$applied_coupon_code = '';

if (isset($_SESSION['applied_coupon'])) {
    $discount_amount = $_SESSION['applied_coupon']['discount'];
    $applied_coupon_code = $_SESSION['applied_coupon']['code'];
}

$success_order_number = '';
$error = '';

// ডাটাবেজ থেকে কুপনগুলো ফেচ করা
try {
    $avail_coupons = $pdo->query("SELECT * FROM coupons")->fetchAll();
} catch (Exception $e) {
    $avail_coupons = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_coupon'])) {
        $coupon_code = trim($_POST['coupon_code'] ?? '');
        try {
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
            $stmt->execute([$coupon_code]);
            $coupon = $stmt->fetch();

            if ($coupon) {
                $min_order_amt = $coupon['min_order'] ?? ($coupon['min_amount'] ?? ($coupon['min_order_amount'] ?? 0));

                if ($subtotal >= $min_order_amt) {
                    $d_type = $coupon['discount_type'] ?? ($coupon['type'] ?? 'percentage');
                    $d_val  = $coupon['discount_value'] ?? ($coupon['discount'] ?? ($coupon['value'] ?? 0));

                    if ($d_type === 'percentage') {
                        $discount_amount = ($subtotal * $d_val) / 100;
                    } else {
                        $discount_amount = $d_val;
                    }
                    $_SESSION['applied_coupon'] = [
                        'code' => $coupon['code'],
                        'discount' => $discount_amount
                    ];
                    $applied_coupon_code = $coupon['code'];
                } else {
                    $error = "এই কুপনের জন্য সর্বনিম্ন অর্ডার ৳ " . $min_order_amt . " হতে হবে।";
                }
            } else {
                $error = "ভুল কুপন কোড!";
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

        $shipping_cost     = ($area === 'outside') ? 130.00 : 70.00;
        $shipping_area_id = ($area === 'outside') ? 2 : 1;
        $total_amount      = ($subtotal - $discount_amount) + $shipping_cost;

        if (!empty($name) && !empty($phone) && !empty($address)) {
            try {
                $pdo->beginTransaction();

                $order_number = 'SHV-' . date('Ymd') . '-' . rand(1000, 9999);
                $customer_id = $_SESSION['customer_id'] ?? null;

                $stmt = $pdo->prepare("INSERT INTO orders (order_number, customer_id, guest_name, guest_phone, guest_email, shipping_name, shipping_phone, shipping_address, shipping_area_id, subtotal, discount_amount, delivery_charge, total_amount, payment_method, payment_status, status, placed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'cod', 'pending', 'new', NOW())");
                $stmt->execute([
                    $order_number, $customer_id, $name, $phone, $email, $name, $phone, $address,
                    $shipping_area_id, $subtotal, $discount_amount, $shipping_cost, $total_amount
                ]);
                $order_id = $pdo->lastInsertId();

                foreach ($cart as $item) {
                    $p_id    = $item['product_id'] ?? ($item['id'] ?? 0);
                    $v_id    = $item['variant_id'] ?? null;
                    $p_title = $item['title'] ?? 'Product';
                    $p_price = $item['price'] ?? 0;
                    $p_qty   = $item['qty'] ?? 1;
                    $p_size  = $item['size'] ?? 'Free Size';
                    $p_color = $item['color'] ?? 'Standard';
                    $line_tot = $p_price * $p_qty;

                    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, color, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $itemStmt->execute([
                        $order_id, $p_id, $v_id, $p_title, $p_size, $p_color, $p_price, $p_qty, $line_tot
                    ]);

                    // Automatically remove ordered items from wishlist if customer is logged in
                    if (!empty($customer_id)) {
                        $delWish = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
                        $delWish->execute([$customer_id, $p_id]);
                    }
                }

                $pdo->commit();
                $success_order_number = $order_number;

                unset($_SESSION['cart']);
                unset($_SESSION['applied_coupon']);

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "অর্ডার সম্পন্ন করতে সমস্যা হয়েছে: " . $e->getMessage();
            }
        } else {
            $error = "অনুগ্রহ করে সব প্রয়োজনীয় তথ্য পূরণ করুন।";
        }
    }
}

$pageTitle = 'চেকআউট - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4 my-md-5" style="max-width: 650px;">
    <?php if (!empty($success_order_number)): ?>
        <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 text-center">
            <i class="fa-solid fa-circle-check fs-1 text-success mb-3"></i>
            <h3 class="text-success fw-bold mb-3">
                <span class="lang-bn">আপনার অর্ডার সফল হয়েছে!</span>
                <span class="lang-en">Your order has been placed successfully!</span>
            </h3>
            <div class="p-3 bg-ivory rounded-3 mb-3 border">
                <span class="text-muted small"><span class="lang-bn">অর্ডার নম্বর:</span><span class="lang-en">Order Number:</span></span>
                <h4 class="text-danger fw-bold mb-0"><?= htmlspecialchars($success_order_number) ?></h4>
            </div>
            <p class="text-muted small mb-4">
                <span class="lang-bn">এই নম্বর দিয়ে অর্ডার ট্র্যাকিং পেজে স্ট্যাটাস দেখতে পারবেন।</span>
                <span class="lang-en">Use this number on the order tracking page to check your order status.</span>
            </p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="index.php" class="btn btn-outline-dark rounded-pill px-4 fw-bold"><span class="lang-bn">হোমপেজে ফিরুন</span><span class="lang-en">Back to Home</span></a>
                <a href="track.php" class="btn btn-danger rounded-pill px-4 fw-bold"><span class="lang-bn">অর্ডার ট্র্যাক করুন</span><span class="lang-en">Track Order</span></a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <h3 class="fw-bold text-center mb-4">
                <span class="lang-bn">চেকআউট (ক্যাশ অন ডেলিভারি)</span>
                <span class="lang-en">Checkout (Cash on Delivery)</span>
            </h3>

            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold"><span class="lang-bn">আপনার নাম</span><span class="lang-en">Your Name</span></label>
                    <input type="text" name="name" class="form-control rounded-pill" value="<?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold"><span class="lang-bn">মোবাইল নম্বর</span><span class="lang-en">Mobile Number</span></label>
                    <input type="text" name="phone" class="form-control rounded-pill" value="<?= htmlspecialchars($_SESSION['customer_phone'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold"><span class="lang-bn">ইমেইল ঠিকানা (ইনভয়েসের জন্য)</span><span class="lang-en">Email Address (for invoice)</span></label>
                    <input type="email" name="email" class="form-control rounded-pill" placeholder="example@gmail.com">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold"><span class="lang-bn">ডেলিভারি ঠিকানা</span><span class="lang-en">Delivery Address</span></label>
                    <textarea name="address" class="form-control rounded-4" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold"><span class="lang-bn">ডেলিভারি এরিয়া</span><span class="lang-en">Delivery Area</span></label>
                    <select name="area" id="deliveryArea" class="form-select rounded-pill" onchange="updateTotal()">
                        <option value="inside" data-charge="70">Inside Dhaka (চার্জ: ৳70.00)</option>
                        <option value="outside" data-charge="130">Outside Dhaka (চার্জ: ৳130.00)</option>
                    </select>
                </div>

                <!-- কুপন সেকশন -->
                <div class="mb-4 p-3 border rounded-3 bg-ivory">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                        <label class="form-label fw-bold small text-dark mb-0"><span class="lang-bn">কুপন কোড (যদি থাকে)</span><span class="lang-en">Coupon Code (if any)</span></label>
                        <span class="coupon-toggle-btn cursor-pointer text-danger fw-semibold small" onclick="toggleAvailableCoupons()"><i class="fa-solid fa-gift me-1"></i><span class="lang-bn">উপলব্ধ কুপন দেখুন</span><span class="lang-en">View Available Coupons</span></span>
                    </div>

                    <div class="input-group">
                        <input type="text" name="coupon_code" class="form-control rounded-start-pill ps-3" placeholder="কুপন কোড লিখুন" value="<?= htmlspecialchars($applied_coupon_code) ?>">
                        <button class="btn btn-dark rounded-end-pill px-4" type="submit" name="apply_coupon"><span class="lang-bn">এপ্লাই</span><span class="lang-en">Apply</span></button>
                    </div>

                    <div id="couponListSection" class="mt-3 d-none">
                        <div class="small fw-bold text-danger mb-2"><span class="lang-bn">অ্যাভেইলেবল কুপন সমূহ:</span><span class="lang-en">Available Coupons:</span></div>
                        <?php if (!empty($avail_coupons)): ?>
                            <?php foreach($avail_coupons as $cp):
                                $min_o = $cp['min_order'] ?? ($cp['min_amount'] ?? ($cp['min_order_amount'] ?? 0));
                                $diff = $min_o - $subtotal;
                                $d_type = $cp['discount_type'] ?? ($cp['type'] ?? 'percentage');
                                $d_val = $cp['discount_value'] ?? ($cp['discount'] ?? ($cp['value'] ?? 0));
                            ?>
                                <div class="p-2 mb-2 bg-white border rounded-2 small d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <div>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($cp['code']) ?></span>
                                        <span class="text-success ms-1">(<?= $d_type === 'percentage' ? $d_val.'%' : '৳'.$d_val ?> OFF)</span>
                                        <div class="text-muted" style="font-size: 11px;">ন্যূনতম অর্ডার: ৳<?= $min_o ?></div>
                                    </div>
                                    <div>
                                        <?php if ($subtotal >= $min_o): ?>
                                            <span class="badge bg-success">ব্যবহারযোগ্য</span>
                                        <?php elseif ($diff <= 500): ?>
                                            <span class="badge bg-warning text-dark">আর ৳<?= $diff ?> শপিং করুন</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">আরও ৳<?= $diff ?> বাকি</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="small text-muted">বর্তমানে কোনো কুপন উপলব্ধ নেই।</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ইনভয়েস স্টাইলের টোটাল সামারি -->
                <div class="card bg-ivory border-0 p-3 rounded-3 mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><span class="lang-bn">পণ্যের মোট দাম:</span><span class="lang-en">Subtotal:</span></span>
                        <span class="fw-bold">৳ <span id="subtotalVal"><?= number_format($subtotal, 2, '.', '') ?></span></span>
                    </div>

                    <?php if ($discount_amount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span><span class="lang-bn">কুপন ডিসকাউন্ট:</span><span class="lang-en">Coupon Discount:</span></span>
                            <span class="fw-bold">- ৳ <span id="discountVal"><?= number_format($discount_amount, 2, '.', '') ?></span></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><span class="lang-bn">ডেলিভারি চার্জ:</span><span class="lang-en">Delivery Charge:</span></span>
                        <span class="fw-bold">৳ <span id="shippingVal">70.00</span></span>
                    </div>

                    <div class="d-flex justify-content-between border-top pt-2 mt-1">
                        <span class="fw-bold text-dark"><span class="lang-bn">সর্বমোট প্রদেয়:</span><span class="lang-en">Grand Total:</span></span>
                        <span class="fw-bold text-danger fs-5">৳ <span id="grandTotalVal"><?= number_format(($subtotal - $discount_amount) + 70, 2, '.', '') ?></span></span>
                    </div>

                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                        <span class="text-muted small"><span class="lang-bn">পেমেন্ট পদ্ধতি:</span><span class="lang-en">Payment Method:</span></span>
                        <span class="badge bg-success">Cash on Delivery</span>
                    </div>
                </div>

                <button type="submit" name="place_order" class="btn btn-success w-100 py-2 fw-bold rounded-pill shadow-sm">
                    <span class="lang-bn">অর্ডার কনফার্ম করুন</span><span class="lang-en">Confirm Order</span>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleAvailableCoupons() {
    const section = document.getElementById('couponListSection');
    section.classList.toggle('d-none');
}

function updateTotal() {
    const areaSelect = document.getElementById('deliveryArea');
    const selectedOption = areaSelect.options[areaSelect.selectedIndex];
    const shippingCharge = parseFloat(selectedOption.getAttribute('data-charge')) || 70;

    const subtotal = parseFloat(document.getElementById('subtotalVal').innerText) || 0;
    const discount = <?= $discount_amount ?>;

    const grandTotal = (subtotal - discount) + shippingCharge;

    document.getElementById('shippingVal').innerText = shippingCharge.toFixed(2);
    document.getElementById('grandTotalVal').innerText = grandTotal.toFixed(2);
}

document.addEventListener('DOMContentLoaded', updateTotal);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>