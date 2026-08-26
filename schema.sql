<?php
require_once 'config/database.php';
$order = null;
if (isset($_GET['code_or_phone'])) {
    $search = $_GET['code_or_phone'];
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? OR phone = ? ORDER BY id DESC LIMIT 1");
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
</head>
<body class="bg-light">

<div class="container my-5">
    <h2>অর্ডার ট্র্যাকিং</h2>
    <form method="GET" class="my-4">
        <div class="input-group">
            <input type="text" name="code_or_phone" class="form-control" placeholder="অর্ডার আইডি (যেমন: SHV1234) অথবা ফোন নম্বর দিন" required>
            <button class="btn btn-primary" type="submit">খুঁজুন</button>
        </div>
    </form>

    <?php if($order): ?>
        <div class="card p-4 border-info">
            <h5>অর্ডার কোড: <?= $order['order_code'] ?></h5>
            <p>কাস্টমার নাম: <?= htmlspecialchars($order['customer_name']) ?></p>
            <p>মোট বিল: ৳ <?= number_format($order['total_amount'], 2) ?></p>
            <p>বর্তমান স্ট্যাটাস: <span class="badge bg-warning text-dark"><?= $order['status'] ?></span></p>
        </div>
    <?php elseif(isset($_GET['code_or_phone'])): ?>
        <div class="alert alert-danger">কোনো অর্ডার খুঁজে পাওয়া যায়নি।</div>
    <?php endif; ?>
</div>

</body>
</html>