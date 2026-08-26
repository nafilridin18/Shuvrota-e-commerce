<?php
require_once '../config/database.php';

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status   = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>অ্যাডমিন প্যানেল - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- অ্যাডমিনের জন্য CSS লিঙ্ক (সাব-ফোল্ডার তাই ../ দিয়ে পাথ বের করা) -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">⚙️ শুভ্রতা অ্যাডমিন</a>
        <a href="add_product.php" class="btn btn-danger btn-sm">+ নতুন প্রোডাক্ট আপলোড</a>
    </div>
</nav>

<div class="container my-5">
    <h4 class="fw-bold mb-3">কাস্টমার অর্ডার তালিকা</h4>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="table align-middle mb-0 table-hover">
            <thead class="table-dark">
                <tr>
                    <th>কোড</th>
                    <th>কাস্টমার</th>
                    <th>ফোন</th>
                    <th>পেমেন্ট মেথড</th>
                    <th>TrxID</th>
                    <th>মোট টাকা</th>
                    <th>স্ট্যাটাস</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $o): ?>
                    <tr>
                        <td class="fw-bold"><?= $o['order_code'] ?></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td><?= htmlspecialchars($o['phone']) ?></td>
                        <td><span class="badge bg-secondary"><?= strtoupper($o['payment_method']) ?></span></td>
                        <td><strong class="text-primary"><?= $o['transaction_id'] ? $o['transaction_id'] : 'N/A' ?></strong></td>
                        <td class="fw-bold text-danger">৳ <?= number_format($o['total_amount'], 2) ?></td>
                        <td><span class="badge bg-warning text-dark"><?= $o['status'] ?></span></td>
                        <td>
                            <form method="POST" class="d-flex gap-1">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="New" <?= $o['status'] == 'New' ? 'selected' : '' ?>>New</option>
                                    <option value="Processing" <?= $o['status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="Delivered" <?= $o['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="Cancelled" <?= $o['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-dark">সেভ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>