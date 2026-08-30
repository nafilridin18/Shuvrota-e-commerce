<?php
require_once 'config/database.php';
$order = null;
$searched = false;

if (isset($_GET['order_number']) && !empty(trim($_GET['order_number']))) {
    $searched = true;
    $search = trim($_GET['order_number']);
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? OR shipping_phone = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$search, $search]);
    $order = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>অর্ডার ট্র্যাকিং - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 650px;">
    <h2 class="fw-bold text-center mb-4">লাইভ অর্ডার ট্র্যাকিং</h2>

    <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
        <form method="GET">
            <div class="input-group input-group-lg">
                <input type="text" name="order_number" class="form-control" placeholder="অর্ডার নম্বর বা মোবাইল নম্বর দিন" value="<?= isset($_GET['order_number']) ? htmlspecialchars($_GET['order_number']) : '' ?>" required>
                <button class="btn btn-danger" type="submit">ট্র্যাক করুন</button>
            </div>
        </form>
    </div>

    <?php if($order): ?>
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header bg-dark text-white p-3 d-flex justify-content-between">
                <span>অর্ডার নম্বর: <?= $order['order_number'] ?></span>
                <span class="badge bg-warning text-dark text-uppercase"><?= $order['status'] ?></span>
            </div>
            <div class="card-body p-4">
                <p><strong>নাম:</strong> <?= htmlspecialchars($order['shipping_name']) ?></p>
                <p><strong>মোবাইল:</strong> <?= htmlspecialchars($order['shipping_phone']) ?></p>
                <p><strong>ঠিকানা:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>সর্বমোট বিল:</span>
                    <span class="fs-4 fw-bold text-danger">৳ <?= number_format($order['total_amount'], 2) ?></span>
                </div>
            </div>
        </div>
    <?php elseif($searched): ?>
        <div class="alert alert-danger text-center">কোনো অর্ডার খুঁজে পাওয়া যায়নি!</div>
    <?php endif; ?>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>