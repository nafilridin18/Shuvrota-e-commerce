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
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $area = $_POST['area'];
    
    $charge = ($area === 'inside_dhaka') ? 80.00 : 150.00;
    $total_amount = $subtotal + $charge;
    $order_code = 'SHV' . rand(1000, 9999);

    $stmt = $pdo->prepare("INSERT INTO orders (order_code, customer_name, phone, address, delivery_area, delivery_charge, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$order_code, $name, $phone, $address, $area, $charge, $total_amount]);

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
</head>
<body class="bg-light">

<div class="container my-5">
    <?php if(isset($success)): ?>
        <div class="alert alert-success">
            <h3>ধন্যবাদ! আপনার অর্ডার সফল হয়েছে।</h3>
            <p>আপনার অর্ডার আইডি: <strong><?= $success ?></strong> (স্ট্যাটাস ট্র্যাক করতে এটি সংরক্ষণ করুন)।</p>
            <a href="index.php" class="btn btn-primary">হোমপেজে ফিরুন</a>
        </div>
    <?php else: ?>
        <h2>অর্ডার ও শিপিং তথ্য (ক্যাশ অন ডেলিভারি)</h2>
        <form method="POST" class="bg-white p-4 shadow-sm rounded">
            <div class="mb-3">
                <label>নাম</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>ফোন নম্বর</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>পূর্ণাঙ্গ ঠিকানা</label>
                <textarea name="address" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label>ডেলিভারি এলাকা</label>
                <select name="area" class="form-select">
                    <option value="inside_dhaka">ঢাকার ভেতরে (ডেলিভারি চার্জ ৳৮০)</option>
                    <option value="outside_dhaka">ঢাকার বাইরে (ডেলিভারি চার্জ ৳১৫০)</option>
                </select>
            </div>
            <h4>সর্বমোট বিল: ৳ <?= number_format($subtotal, 2) ?> + ডেলিভারি চার্জ</h4>
            <button type="submit" class="btn btn-success mt-3">অর্ডার নিশ্চিত করুন</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>