<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
                // ডাটাবেজের বিভিন্ন সম্ভাব্য কলামের নাম হ্যান্ডেল করা
                $min_order_amt = $coupon['min_order'] ?? ($coupon['min_amount'] ?? 0);
                
                if ($subtotal >= $min_order_amt) {
                    $d_type = $coupon['discount_type'] ?? 'percentage';
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
            $error = "কুপন এপ্লাই করতে সমস্যা হয়েছে।";
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

                $stmt = $pdo->prepare("INSERT INTO orders (order_number, guest_name, guest_phone, guest_email, shipping_name, shipping_phone, shipping_address, shipping_area_id, subtotal, delivery_charge, total_amount, payment_method, payment_status, status, placed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'cod', 'pending', 'new', NOW())");
                $stmt->execute([
                    $order_number, $name, $phone, $email, $name, $phone, $address, 
                    $shipping_area_id, $subtotal, $shipping_cost, $total_amount
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
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>চেকআউট - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .lang-en { display: none; }
        .coupon-toggle-btn { cursor: pointer; color: #0d6efd; font-size: 13px; text-decoration: underline; }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-danger" href="index.php">
            <i class="fa-solid fa-gem me-1"></i><span class="lang-bn">শুভ্রতা</span><span class="lang-en">Shuvrota</span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a class="btn btn-outline-dark btn-sm px-3 rounded-pill" href="cart.php">
                <i class="fa-solid fa-cart-shopping me-1"></i> <span class="lang-bn">কার্ট</span><span class="lang-en">Cart</span>
            </a>
            <div class="dropdown ms-2">
                <a class="btn btn-sm btn-outline-secondary px-3 dropdown-toggle rounded-pill" href="#" role="button" data-bs-toggle="dropdown" id="currentLangText">বাংলা</a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('bn')">বাংলা (BN)</a></li>
                    <li><a class="dropdown-item small" href="#" onclick="switchLanguage('en')">English (EN)</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5" style="max-width: 600px;">
    <?php if (!empty($success_order_number)): ?>
        <div class="card p-5 border-0 shadow-sm rounded-4 text-center bg-white">
            <h3 class="text-success fw-bold mb-3">
                <span class="lang-bn">আপনার অর্ডার সফল হয়েছে!</span>
                <span class="lang-en">Your order has been placed successfully!</span>
            </h3>
            <div class="p-3 bg-light rounded-3 mb-3 border">
                <span class="text-muted small">অর্ডার নম্বর:</span>
                <h4 class="text-danger fw-bold mb-0"><?= htmlspecialchars($success_order_number) ?></h4>
            </div>
            <p class="text-muted small mb-4">এই নম্বর দিয়ে অর্ডার ট্র্যাকিং পেজে স্ট্যাটাস দেখতে পারবেন।</p>
            <a href="index.php" class="btn btn-danger rounded-pill px-4 fw-bold">হোমপেজে ফিরুন</a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h3 class="fw-bold text-dark mb-4 text-center">চেকআউট (ক্যাশ অন ডেলিভারি)</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">আপনার নাম</label>
                    <input type="text" name="name" class="form-control rounded-pill" value="<?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">মোবাইল নম্বর</label>
                    <input type="text" name="phone" class="form-control rounded-pill" value="<?= htmlspecialchars($_SESSION['customer_phone'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">ইমেইল ঠিকানা (ইনভয়েসের জন্য)</label>
                    <input type="email" name="email" class="form-control rounded-pill" placeholder="example@gmail.com">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">ডেলিভারি ঠিকানা</label>
                    <textarea name="address" class="form-control rounded-4" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">ডেলিভারি এরিয়া</label>
                    <select name="area" id="deliveryArea" class="form-select rounded-pill" onchange="updateTotal()">
                        <option value="inside" data-charge="70">Inside Dhaka (চার্জ: ৳70.00)</option>
                        <option value="outside" data-charge="130">Outside Dhaka (চার্জ: ৳130.00)</option>
                    </select>
                </div>

                <!-- কুপন সেকশন -->
                <div class="mb-4 p-3 border rounded-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold small text-dark mb-0">কুপন কোড (যদি থাকে)</label>
                        <span class="coupon-toggle-btn" onclick="toggleAvailableCoupons()"><i class="fa-solid fa-gift me-1"></i>উপলব্ধ কুপন দেখুন</span>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" name="coupon_code" class="form-control rounded-start-pill ps-3" placeholder="কুপন কোড লিখুন" value="<?= htmlspecialchars($applied_coupon_code) ?>">
                        <button class="btn btn-dark rounded-end-pill px-4" type="submit" name="apply_coupon">এপ্লাই</button>
                    </div>

                    <div id="couponListSection" class="mt-3 d-none">
                        <div class="small fw-bold text-danger mb-2">অ্যাভেইলেবল কুপন সমূহ:</div>
                        <?php if (!empty($avail_coupons)): ?>
                            <?php foreach($avail_coupons as $cp): 
                                $min_o = $cp['min_order'] ?? ($cp['min_amount'] ?? 0);
                                $diff = $min_o - $subtotal;
                                $d_type = $cp['discount_type'] ?? 'percentage';
                                $d_val = $cp['discount_value'] ?? ($cp['discount'] ?? ($cp['value'] ?? 0));
                            ?>
                                <div class="p-2 mb-2 bg-white border rounded-2 small d-flex justify-content-between align-items-center">
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
                <div class="card bg-light border-0 p-3 rounded-3 mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">পণ্যের মোট দাম:</span>
                        <span class="fw-bold">৳ <span id="subtotalVal"><?= number_format($subtotal, 2, '.', '') ?></span></span>
                    </div>

                    <?php if ($discount_amount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>কুপন ডিসকাউন্ট:</span>
                            <span class="fw-bold">- ৳ <span id="discountVal"><?= number_format($discount_amount, 2, '.', '') ?></span></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">ডেলিভারি চার্জ:</span>
                        <span class="fw-bold">৳ <span id="shippingVal">70.00</span></span>
                    </div>

                    <div class="d-flex justify-content-between border-top pt-2 mt-1">
                        <span class="fw-bold text-dark">সর্বমোট প্রদেয়:</span>
                        <span class="fw-bold text-danger fs-5">৳ <span id="grandTotalVal"><?= number_format(($subtotal - $discount_amount) + 70, 2, '.', '') ?></span></span>
                    </div>

                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                        <span class="text-muted small">পেমেন্ট পদ্ধতি:</span>
                        <span class="badge bg-success">Cash on Delivery</span>
                    </div>
                </div>

                <button type="submit" name="place_order" class="btn btn-success w-100 py-2 fw-bold rounded-pill shadow-sm">অর্ডার কনফার্ম করুন</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<footer class="bg-dark text-light pt-4 pb-3 mt-auto text-center small">
    <p class="text-muted mb-0">&copy; 2026 Shuvrota. <span class="lang-bn">সর্বস্বত্ব সংরক্ষিত।</span><span class="lang-en">All rights reserved.</span></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleAvailableCoupons() {
    const section = document.getElementById('couponListSection');
    if (section.classList.contains('d-none')) {
        section.classList.remove('d-none');
    } else {
        section.classList.add('d-none');
    }
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

window.onload = function() {
    updateTotal();
    const savedLang = localStorage.getItem('selectedLang') || 'bn';
    switchLanguage(savedLang);
};

function switchLanguage(lang) {
    const bnElements = document.querySelectorAll('.lang-bn');
    const enElements = document.querySelectorAll('.lang-en');
    if (lang === 'en') {
        bnElements.forEach(el => el.style.display = 'none');
        enElements.forEach(el => el.style.display = 'inline');
        document.getElementById('currentLangText').innerText = 'English';
        localStorage.setItem('selectedLang', 'en');
    } else {
        enElements.forEach(el => el.style.display = 'none');
        bnElements.forEach(el => el.style.display = 'inline');
        document.getElementById('currentLangText').innerText = 'বাংলা';
        localStorage.setItem('selectedLang', 'bn');
    }
}
</script>
</body>
</html>