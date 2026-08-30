<?php
session_start();

if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $key = $_GET['key'];
    unset($_SESSION['cart'][$key]);
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>শপিং কার্ট - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fa-solid fa-bag-shopping me-2"></i>আপনার শপিং কার্ট</h2>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill"><i class="fa-solid fa-arrow-left me-1"></i> কেনাকাটা চালিয়ে যান</a>
    </div>

    <?php if(empty($cart)): ?>
        <div class="card p-5 text-center shadow-sm rounded-4">
            <h4 class="text-muted mb-3">আপনার কার্ট খালি!</h4>
            <a href="index.php" class="btn btn-danger rounded-pill align-self-center px-4">শপিং শুরু করুন</a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">পণ্য</th>
                            <th>সাইজ/কালার</th>
                            <th>দাম</th>
                            <th>পরিমাণ</th>
                            <th>মোট</th>
                            <th class="text-center">মুছুন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart as $key => $item): ?>
                            <?php 
                                $img_src = (strpos($item['image'], 'http') === 0) ? $item['image'] : 'uploads/' . $item['image'];
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= htmlspecialchars($img_src) ?>" width="50" height="50" class="rounded object-fit-cover">
                                        <span class="fw-semibold"><?= htmlspecialchars($item['title']) ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $item['size'] ?> / <?= $item['color'] ?></span></td>
                                <td>৳ <?= number_format($item['price'], 2) ?></td>
                                <td><?= $item['qty'] ?></td>
                                <td class="fw-bold text-danger">৳ <?= number_format($item['price'] * $item['qty'], 2) ?></td>
                                <td class="text-center">
                                    <a href="cart.php?action=remove&key=<?= $key ?>" class="btn btn-sm btn-outline-danger rounded-circle"><i class="fa-solid fa-trash-can"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-4 border-0 shadow-sm rounded-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fs-5 text-muted">পণ্যের মোট দাম:</span>
                <span class="fs-4 fw-bold text-dark">৳ <?= number_format($subtotal, 2) ?></span>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <a href="checkout.php" class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow">চেকআউট করুন</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>