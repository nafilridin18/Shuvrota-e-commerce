<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT o.*, da.area_name FROM orders o LEFT JOIN delivery_areas da ON o.shipping_area_id = da.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Fetch Items
$items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

// Fetch Status History
$history_stmt = $pdo->prepare("SELECT h.*, a.name as admin_name FROM order_status_history h LEFT JOIN admins a ON h.changed_by = a.id WHERE h.order_id = ? ORDER BY h.id DESC");
$history_stmt->execute([$order_id]);
$history = $history_stmt->fetchAll();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $note       = trim($_POST['note'] ?? '');
    $admin_id   = $_SESSION['admin_id'] ?? null;

    if ($new_status !== $order['status']) {
        try {
            $pdo->beginTransaction();

            // অর্ডার স্ট্যাটাস আপডেট
            $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
            
            // স্ট্যাটাস হিস্ট্রি ইনসার্ট
            $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?, ?, ?, ?, ?)")
                ->execute([$order_id, $order['status'], $new_status, $admin_id, $note]);

            // যদি স্ট্যাটাস পরিবর্তন করে 'delivered' করা হয় এবং আগের স্ট্যাটাস delivered না থাকে, তবে স্টক অটো কমবে
            if ($new_status === 'delivered' && $order['status'] !== 'delivered') {
                foreach ($items as $item) {
                    if (!empty($item['product_id'])) {
                        $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                        $stockStmt->execute([$item['quantity'], $item['product_id']]);
                    }
                }
            }

            $pdo->commit();
            $order['status'] = $new_status;
            $message = "অর্ডার স্ট্যাটাস সফলভাবে আপডেট হয়েছে!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "স্ট্যাটাস আপডেট করতে সমস্যা হয়েছে: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>অর্ডার বিবরণ #<?= htmlspecialchars($order['order_number']) ?> - শুভ্রতা এডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>অর্ডার ডিটেইলস: <span class="text-danger"><?= htmlspecialchars($order['order_number']) ?></span></h2>
        <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> ড্যাশবোর্ডে ফিরুন</a>
    </div>

    <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-dark text-white fw-bold">অর্ডারকৃত আইটেমসমূহ</div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>পণ্য</th>
                                <th>সাইজ/কালার</th>
                                <th>একক মূল্য</th>
                                <th>পরিমাণ</th>
                                <th>মোট</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= htmlspecialchars($item['size'] ?? '-') ?> / <?= htmlspecialchars($item['color'] ?? '-') ?></td>
                                    <td>৳ <?= number_format($item['unit_price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td class="fw-bold text-danger">৳ <?= number_format($item['line_total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white fw-bold">স্ট্যাটাস পরিবর্তনের ইতিহাস</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach($history as $h): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary"><?= htmlspecialchars($h['new_status']) ?></span>
                                    <small class="text-muted ms-2">পরিবর্তনকারী: <?= htmlspecialchars($h['admin_name'] ?? 'System') ?></small>
                                    <?php if($h['note']): ?><br><small class="fst-italic text-secondary">নোট: <?= htmlspecialchars($h['note']) ?></small><?php endif; ?>
                                </div>
                                <small class="text-muted"><?= date('d M Y, h:i A', strtotime($h['changed_at'])) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-dark text-white fw-bold">গ্রাহকের তথ্য</div>
                <div class="card-body">
                    <p class="mb-1"><strong>নাম:</strong> <?= htmlspecialchars($order['shipping_name']) ?></p>
                    <p class="mb-1"><strong>ফোন:</strong> <?= htmlspecialchars($order['shipping_phone']) ?></p>
                    <p class="mb-1"><strong>ঠিকানা:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                    <p class="mb-1"><strong>এরিয়া:</strong> <?= htmlspecialchars($order['area_name'] ?? 'General') ?></p>
                    <hr>
                    <p class="mb-1"><strong>সাবটোটাল:</strong> ৳ <?= number_format($order['subtotal'], 2) ?></p>
                    <p class="mb-1"><strong>ছাড়:</strong> ৳ <?= number_format($order['discount_amount'], 2) ?></p>
                    <p class="mb-1"><strong>ডেলিভারি চার্জ:</strong> ৳ <?= number_format($order['delivery_charge'], 2) ?></p>
                    <h5 class="fw-bold text-danger mt-2">সর্বমোট: ৳ <?= number_format($order['total_amount'], 2) ?></h5>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white fw-bold">স্ট্যাটাস আপডেট করুন</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">বর্তমান স্ট্যাটাস</label>
                            <select name="status" class="form-select" required>
                                <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>New</option>
                                <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">নোট (ঐচ্ছিক)</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="যেমন: পার্সেল কুরিয়ারে দেওয়া হয়েছে"></textarea>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-danger w-100 fw-bold">আপডেট করুন</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>