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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $area     = $_POST['area'];
    $method   = $_POST['payment_method'];
    $trx_id   = isset($_POST['transaction_id']) ? trim($_POST['transaction_id']) : NULL;
    
    $charge = ($area === 'inside_dhaka') ? 80.00 : 150.00;
    $total_amount = $subtotal + $charge;
    $order_code = 'SHV' . rand(10000, 99999);

    $stmt = $pdo->prepare("INSERT INTO orders (order_code, customer_name, phone, address, delivery_area, delivery_charge, total_amount, payment_method, transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$order_code, $name, $phone, $address, $area, $charge, $total_amount, $method, $trx_id]);

    unset($_SESSION['cart']);
    $success = $order_code;
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
            <h2 class="fw-bold text-success">অর্ডার সফল হয়েছে!</h2>
            <div class="alert alert-warning py-3 my-3">
                <span>অর্ডার কোড: </span><span class="fs-2 fw-bold text-danger"><?= $success ?></span>
            </div>
            <a href="index.php" class="btn btn-danger rounded-pill px-4">হোমপেজে ফিরুন</a>
        </div>
    <?php else: ?>
        <h2 class="fw-bold mb-4 text-center">চেকআউট ও পেমেন্ট</h2>
        <div class="card p-4 border-0 shadow-sm rounded-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">নাম</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">মোবাইল নম্বর</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ঠিকানা</label>
                    <textarea name="address" class="form-control" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">ডেলিভারি এলাকা</label>
                    <select name="area" class="form-select" required>
                        <option value="inside_dhaka">ঢাকার ভেতরে (৳৮০)</option>
                        <option value="outside_dhaka">ঢাকার বাইরে (৳১৫০)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">পেমেন্ট পদ্ধতি নির্বাচন করুন</label>
                    <select name="payment_method" id="payMethod" class="form-select" onchange="togglePaymentBox()" required>
                        <option value="cod">Cash on Delivery (ক্যাশ অন ডেলিভারি)</option>
                        <option value="bkash">bKash (বিকাশ সেন্ড মানি)</option>
                        <option value="nagad">Nagad (নগদ সেন্ড মানি)</option>
                    </select>
                </div>

                <div id="onlinePayBox" class="alert alert-info d-none">
                    <p class="mb-1"><strong>bKash / Nagad Personal Number:</strong> 01700000000</p>
                    <small>উপরে উল্লেখিত নম্বরে টাকা পাঠিয়ে আপনার Transaction ID নিচে দিন:</small>
                    <input type="text" name="transaction_id" class="form-control mt-2" placeholder="TrxID (যেমন: 9J7A6K8L)">
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill fw-bold mt-3">অর্ডার নিশ্চিত করুন</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
function togglePaymentBox() {
    var method = document.getElementById('payMethod').value;
    var box = document.getElementById('onlinePayBox');
    if(method === 'bkash' || method === 'nagad') {
        box.classList.remove('d-none');
    } else {
        box.classList.add('d-none');
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>