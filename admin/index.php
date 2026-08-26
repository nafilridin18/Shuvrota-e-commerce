<?php
require_once '../config/database.php';

if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
}

$stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>অ্যাডমিন প্যানেল - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5">
    <h2>অ্যাডমিন সেলস ড্যাশবোর্ড</h2>
    <table class="table table-striped table-bordered mt-4 bg-white">
        <thead>
            <tr>
                <th>আইডি</th>
                <th>কাস্টমার</th>
                <th>ফোন</th>
                <th>মোট টাকা</th>
                <th>স্ট্যাটাস</th>
                <th>অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($orders as $o): ?>
                <tr>
                    <td><?= $o['order_code'] ?></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td><?= $o['phone'] ?></td>
                    <td>৳ <?= number_format($o['total_amount'], 2) ?></td>
                    <td><span class="badge bg-info"><?= $o['status'] ?></span></td>
                    <td>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="status" class="form-select form-select-sm">
                                <option value="New">New</option>
                                <option value="Processing">Processing</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm btn-dark">সেভ</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>