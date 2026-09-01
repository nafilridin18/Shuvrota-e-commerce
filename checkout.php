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
    $email    = trim($_POST['email']);
    $address  = trim($_POST['address']);
    $area_id  = (int)$_POST['area_id'];

    $area_stmt = $pdo->prepare("SELECT delivery_charge FROM delivery_areas WHERE id = ?");
    $area_stmt->execute([$area_id]);
    $area_info = $area_stmt->fetch();
    $charge = $area_info ? $area_info['delivery_charge'] : 150.00;

    $total_amount = $subtotal + $charge;
    $order_number = 'SHV-' . date('Ymd') . '-' . rand(1000, 9999);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO orders 
            (order_number, shipping_name, shipping_phone, shipping_address, shipping_area_id, subtotal, delivery_charge, total_amount, payment_method, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cod', 'new')");
        $stmt->execute([$order_number, $name, $phone, $address, $area_id, $subtotal, $charge, $total_amount]);
        $order_id = $pdo->lastInsertId();

        foreach ($cart as $item) {
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, size, color, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $line_total = $item['price'] * $item['qty'];
            $item_stmt->execute([$order_id, $item['product_id'], $item['title'], $item['size'], $item['color'], $item['price'], $item['qty'], $line_total]);

            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$item['qty'], $item['product_id']]);
        }

        $pdo->commit();

        // Send Email Confirmation
        if(!empty($email)) {
            $subject = "Order Confirmation - Shubhrata ($order_number)";
            $message_body = "Dear $name,\n\nThank you for shopping at Shubhrata!\nYour Order Number: $order_number\nTotal Amount: BDT $total_amount\n\nWe will contact you shortly before delivery.";
            $headers = "From: shubhrata032@gmail.com\r\nReply-To: shubhrata032@gmail.com";
            @mail($email, $subject, $message_body, $headers);
        }

        // Send SMS Confirmation via Gateway API
        $sms_text = "Dear $name, Order #$order_number confirmed at Shubhrata! Total BDT $total_amount. Delivery soon.";
        $api_url = "http://api.greenweb.com.bd/api.php";
        $sms_data = [
            'token' => 'YOUR_SMS_GATEWAY_API_KEY', // Replace with active API key
            'to' => $phone,
            'message' => $sms_text
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sms_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_exec($ch);
        curl_close($ch);

        unset($_SESSION['cart']);
        $success = $order_number;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Order Processing Failed: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container my-5" style="max-width: 700px;">
    <?php if(isset($success)): ?>
        <div class="card p-5 border-0 shadow-lg text-center rounded-4">
            <h2 class="fw-bold text-success mb-3">Order Placed Successfully!</h2>
            <p>An email and SMS confirmation have been sent to your contact details.</p>
            <div class="alert alert-warning py-3">
                <span>Order Reference: </span><span class="fs-3 fw-bold text-danger"><?= $success ?></span>
            </div>
            <a href="index.php" class="btn btn-danger rounded-pill px-4 mt-3 align-self-center">Return to Home</a>
        </div>
    <?php else: ?>
        <h2 class="fw-bold mb-4 text-center">Checkout Details</h2>
        <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        
        <div class="card p-4 border-0 shadow-sm rounded-4">
            <form method="POST" id="checkoutForm">
                <div class="mb-3">
                    <label class="form-label fw-bold">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Mobile Number (For SMS Notification)</label>
                    <input type="text" name="phone" class="form-control" placeholder="017XXXXXXXX" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Email Address (For Email Invoice)</label>
                    <input type="email" name="email" class="form-control" placeholder="example@mail.com">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Delivery Address</label>
                    <textarea name="address" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Delivery Region</label>
                    <select name="area_id" class="form-select" required>
                        <?php foreach($delivery_areas as $area): ?>
                            <option value="<?= $area['id'] ?>"><?= $area['area_name'] ?> (Charge: ৳<?= number_format($area['delivery_charge'], 2) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bg-light p-3 rounded mb-4 border">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Items Subtotal:</span>
                        <span>৳ <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between font-bold">
                        <span>Payment Method:</span>
                        <span class="badge bg-success">Cash on Delivery</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill fw-bold">Confirm Order</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>