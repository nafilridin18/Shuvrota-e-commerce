<?php
require_once '../config/database.php';

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status   = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    // স্ট্যাটাস হিস্ট্রি লগ
    $pdo->prepare("INSERT INTO order_status_history (order_id, new_status) VALUES (?, ?)")->execute([$order_id, $status]);
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>অ্যাডমিন প্যানেল - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">⚙️ শুভ্রতা অ্যাডমিন ড্যাশবোর্ড</a>
        <a href="add_product.php" class="btn btn-danger btn-sm">+ নতুন প্রোডাক্ট আপলোড</a>
    </div>
</nav>

<div class="container my-5">
    <h4 class="fw-bold mb-3">কাস্টমার অর্ডার তালিকা</h4>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>অর্ডার নম্বর</th>
                        <th>কাস্টমার</th>
                        <th>ফোন</th>
                        <th>ঠিকানা</th>
                        <th>বিল</th>
                        <th>স্ট্যাটাস</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $o): ?>
                        <tr>
                            <td class="fw-bold"><?= $o['order_number'] ?></td>
                            <td><?= htmlspecialchars($o['shipping_name']) ?></td>
                            <td><?= htmlspecialchars($o['shipping_phone']) ?></td>
                            <td class="small"><?= htmlspecialchars($o['shipping_address']) ?></td>
                            <td class="fw-bold text-danger">৳ <?= number_format($o['total_amount'], 2) ?></td>
                            <td><span class="badge bg-info text-dark text-uppercase"><?= $o['status'] ?></span></td>
                            <td>
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="new" <?= $o['status'] == 'new' ? 'selected' : '' ?>>New</option>
                                        <option value="processing" <?= $o['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $o['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $o['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="cancelled" <?= $o['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>