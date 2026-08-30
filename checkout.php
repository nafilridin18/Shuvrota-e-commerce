<?php
session_start();
require_once 'config/database.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: index.php');
    exit;
}

$subtotal = 0;
foreach($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
}

$delivery_areas = $pdo->query("SELECT * FROM delivery_areas WHERE is_active = 1")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $area_id  = (int)$_POST['area_id'];

    // ডেলিভারি এরিয়া চার্জ বের করা
    $area_stmt = $pdo->prepare("SELECT delivery_charge FROM delivery_areas WHERE id = ?");
    $area_stmt->execute([$area_id]);
    $area_info = $area_stmt->fetch();
    $charge = $area_info ? $area_info['delivery_charge'] : 130.00;

    $total_amount = $subtotal + $charge;
    $order_number = 'SHV-' . date('Ymd') . '-' . rand(1000, 9999);

    try {
        $pdo->beginTransaction();

        // ১. অর্ডার টেবিল ইনসার্ট
        $stmt = $pdo->prepare("INSERT INTO orders 
            (order_number, shipping_name, shipping_phone, shipping_address, shipping_area_id, subtotal, delivery_charge, total_amount, payment_method, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cod', 'new')");
        $stmt->execute([$order_number, $name, $phone, $address, $area_id, $subtotal, $charge, $total_amount]);
        $order_id = $pdo->lastInsertId();

        // ২. অর্ডার আইটেমস ও স্টক লগ ইনসার্ট
        foreach ($cart as $item) {
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, size, color, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $line_total = $item['price'] * $item['qty'];
            $item_stmt->execute([$order_id, $item['product_id'], $item['title'], $item['size'], $item['color'], $item['price'], $item['qty'], $line_total]);

            // স্টক কমানো ও stock_logs আপডেট
            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$item['qty'], $item['product_id']]);
            
            $log_stmt = $pdo->prepare("INSERT INTO stock_logs (product_id, type, quantity_changed, reference_id, reference_type, note) VALUES (?, 'sale', ?, ?, 'order', 'Customer Purchase')");
            $log_stmt->execute([$item['product_id'], -$item['qty'], $order_id]);
        }

        $pdo->commit();
        unset($_SESSION['cart']);
        $success = $order_number;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "অর্ডার প্রক্রিয়াজাতকরণে ত্রুটি ঘটেছে: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>চেকআউট - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 700px;">
    <?php if(isset($success)): ?>
        <div class="card p-5 border-0 shadow-lg text-center rounded-4">
            <h2 class="fw-bold text-success mb-3">আপনার অর্ডার সফল হয়েছে!</h2>
            <div class="alert alert-warning py-3">
                <span>অর্ডার নম্বর: </span><span class="fs-3 fw-bold text-danger"><?= $success ?></span>
            </div>
            <a href="index.php" class="btn btn-danger rounded-pill px-4 mt-3 align-self-center">হোমপেজে ফিরুন</a>
        </div>
    <?php else: ?>
        <h2 class="fw-bold mb-4 text-center">চেকআউট (ক্যাশ অন ডেলিভারি)</h2>
        <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        
        <div class="card p-4 border-0 shadow-sm rounded-4">
            <form method="POST" id="checkoutForm">
                <div class="mb-3">
                    <label class="form-label">আপনার নাম</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">মোবাইল নম্বর</label>
                    <input type="text" name="phone" class="form-control" placeholder="017XXXXXXXX" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ডেলিভারি ঠিকানা</label>
                    <textarea name="address" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label">ডেলিভারি এরিয়া</label>
                    <select name="area_id" class="form-select" required>
                        <?php foreach($delivery_areas as $area): ?>
                            <option value="<?= $area['id'] ?>"><?= $area['area_name'] ?> (ডেলিভারি চার্জ: ৳<?= number_format($area['delivery_charge'], 2) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bg-light p-3 rounded mb-4 border">
                    <div class="d-flex justify-content-between mb-2">
                        <span>পণ্যের মোট দাম:</span>
                        <span>৳ <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between font-bold">
                        <span>পেমেন্ট পদ্ধতি:</span>
                        <span class="badge bg-success">Cash on Delivery</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill fw-bold">অর্ডার কনফার্ম করুন</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>