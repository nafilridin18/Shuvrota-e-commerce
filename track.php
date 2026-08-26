<?php
require_once 'config/database.php';
$order = null;
$searched = false;

if (isset($_GET['code_or_phone']) && !empty(trim($_GET['code_or_phone']))) {
    $searched = true;
    $search = trim($_GET['code_or_phone']);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 650px;">
    <div class="text-center mb-4">
        <h2 class="fw-bold"><i class="fa-solid fa-magnifying-glass-location text-danger me-2"></i>লাইভ অর্ডার ট্র্যাকিং</h2>
    </div>

    <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
        <form method="GET">
            <div class="input-group input-group-lg">
                <input type="text" name="code_or_phone" class="form-control" placeholder="যেমন: SHV12345 বা 01700..." value="<?= isset($_GET['code_or_phone']) ? htmlspecialchars($_GET['code_or_phone']) : '' ?>" required>
                <button class="btn btn-danger px-4" type="submit">খুঁজুন</button>
            </div>
        </form>
    </div>

    <?php if($order): ?>
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">অর্ডার আইডি: <?= $order['order_code'] ?></span>
                <span class="badge bg-warning text-dark px-3 py-2 fs-6"><?= $order['status'] ?></span>
            </div>
            <div class="card-body p-4">
                <p><strong>কাস্টমারের নাম:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                <p><strong>মোবাইল নম্বর:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                <p><strong>ডেলিভারি ঠিকানা:</strong> <?= htmlspecialchars($order['address']) ?></p>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fs-5">সর্বমোট বিল:</span>
                    <span class="fs-4 fw-bold text-danger">৳ <?= number_format($order['total_amount'], 2) ?></span>
                </div>
            </div>
        </div>
    <?php elseif($searched): ?>
        <div class="alert alert-danger text-center rounded-3 p-4">
            কোনো অর্ডার খুঁজে পাওয়া যায়নি!
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-link text-decoration-none text-muted"><i class="fa-solid fa-arrow-left me-1"></i> হোমপেজে ফিরুন</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>