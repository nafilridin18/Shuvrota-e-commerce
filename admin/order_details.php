<?php
require_once __DIR__ . '/../config/session.php';
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

$items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

$history_stmt = $pdo->prepare("SELECT h.*, a.name as admin_name FROM order_status_history h LEFT JOIN admins a ON h.changed_by = a.id WHERE h.order_id = ? ORDER BY h.id DESC");
$history_stmt->execute([$order_id]);
$history = $history_stmt->fetchAll();

$complaints_stmt = $pdo->prepare("SELECT c.*, cu.name as customer_name FROM complaints c LEFT JOIN customers cu ON c.customer_id = cu.id WHERE c.order_id = ? ORDER BY c.created_at DESC");
$complaints_stmt->execute([$order_id]);
$order_complaints = $complaints_stmt->fetchAll();

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status         = $_POST['status'];
    $new_payment_status = $_POST['payment_status'];
    $note               = trim($_POST['note'] ?? '');
    $admin_id           = $_SESSION['admin_id'] ?? null;

    if ($new_status !== $order['status'] || $new_payment_status !== $order['payment_status']) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?")
                ->execute([$new_status, $new_payment_status, $order_id]);

            if ($new_status !== $order['status']) {
                $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$order_id, $order['status'], $new_status, $admin_id, $note]);

                if ($new_status === 'delivered' && $order['status'] !== 'delivered') {
                    foreach ($items as $item) {
                        if (!empty($item['product_id'])) {
                            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")
                                ->execute([$item['quantity'], $item['product_id']]);
                        }
                    }
                }
            }
            $pdo->commit();
            $order['status'] = $new_status;
            $order['payment_status'] = $new_payment_status;
            $message = "অর্ডার এবং পেমেন্ট স্ট্যাটাস সফলভাবে আপডেট হয়েছে!";
            $messageType = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "আপডেট করতে সমস্যা হয়েছে: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Order status badge colors
$status_colors = [
    'new'        => ['warning', 'text-dark'],
    'processing' => ['info',    'text-dark'],
    'shipped'    => ['primary', 'text-white'],
    'delivered'  => ['success', 'text-white'],
    'cancelled'  => ['danger',  'text-white'],
    'returned'   => ['secondary','text-white'],
];
$sc = $status_colors[$order['status']] ?? ['secondary', 'text-white'];

$payment_colors = [
    'pending' => ['warning', 'text-dark'],
    'paid'    => ['success', 'text-white'],
    'failed'  => ['danger',  'text-white'],
];
$pc = $payment_colors[$order['payment_status']] ?? ['secondary', 'text-white'];

$pageTitle     = 'অর্ডার বিবরণ #' . $order['order_number'] . ' — শুভ্রতা এডমিন';
$pageHeadingBn = 'অর্ডার বিবরণ';
$pageHeadingEn = 'Order Details';
$activePage    = 'dashboard';
include 'includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 align-items-center mb-4">
    <a href="index.php" class="admin-btn admin-btn-outline">
        <i class="fa-solid fa-arrow-left"></i>
        <span class="lang-bn">ড্যাশবোর্ডে ফিরুন</span><span class="lang-en">Back to Dashboard</span>
    </a>
    <div class="ms-auto d-flex flex-wrap gap-2">
        <span class="badge bg-<?= $sc[0] ?> <?= $sc[1] ?>" style="padding: 8px 14px; font-size: 12px;">
            <i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 5px;"></i>
            <?= strtoupper($order['status']) ?>
        </span>
        <span class="badge bg-<?= $pc[0] ?> <?= $pc[1] ?>" style="padding: 8px 14px; font-size: 12px;">
            <i class="fa-solid fa-money-bill me-1"></i>
            Payment: <?= strtoupper($order['payment_status']) ?>
        </span>
    </div>
</div>

<!-- Order Number Banner -->
<div class="admin-card mb-4" style="background: linear-gradient(135deg, #241b17, #1c1210); color: #f4ecdf;">
    <div class="admin-card-body" style="padding: 22px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <small style="color: #a89a8d; letter-spacing: 1.5px; text-transform: uppercase; font-size: 11px;">Order Number</small>
                <h3 class="mb-0 fw-bold" style="font-family: var(--font-display); color: var(--gold-400);">
                    #<?= htmlspecialchars($order['order_number']) ?>
                </h3>
            </div>
            <div class="text-end">
                <small style="color: #a89a8d; letter-spacing: 1.5px; text-transform: uppercase; font-size: 11px;">Placed On</small>
                <div class="fw-bold"><?= date('d M Y, h:i A', strtotime($order['placed_at'] ?? $order['created_at'])) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">

        <!-- Items -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-box me-2"></i>
                    <span class="lang-bn">অর্ডারকৃত আইটেম</span><span class="lang-en">Ordered Items</span>
                </span>
                <span class="badge" style="background: var(--gold-500); color:#241b17;"><?= count($items) ?> Items</span>
            </div>
            <div class="table-responsive">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Size / Color</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></td>
                                <td>
                                    <span class="badge" style="background: rgba(212,175,55,0.15); color: #7b5c12;">
                                        <?= htmlspecialchars($item['size'] ?? '—') ?> / <?= htmlspecialchars($item['color'] ?? '—') ?>
                                    </span>
                                </td>
                                <td>৳<?= number_format($item['unit_price'], 2) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td class="fw-bold" style="color: var(--maroon-700);">৳<?= number_format($item['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Complaints / Feedback -->
        <div class="admin-card mb-4" style="border: 1.5px solid rgba(198,40,40,0.35);">
            <div class="admin-card-header" style="background: linear-gradient(135deg, #c62828, #8e0000);">
                <span><i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <span class="lang-bn">কাস্টমার কমেন্ট / অভিযোগ</span><span class="lang-en">Customer Feedback</span>
                </span>
                <span class="badge" style="background: #fff; color: #8e0000;"><?= count($order_complaints) ?></span>
            </div>
            <div class="admin-card-body">
                <?php if (empty($order_complaints)): ?>
                    <p class="text-muted small mb-0"><i class="fa-solid fa-check-circle me-1" style="color:#2e7d32;"></i> No complaints or comments for this order.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($order_complaints as $oc): ?>
                            <div class="p-3 rounded" style="background: rgba(198,40,40,0.05); border-left: 4px solid #c62828;">
                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                    <h6 class="fw-bold mb-1" style="color: #8e0000;"><?= htmlspecialchars($oc['subject']) ?></h6>
                                    <small class="text-muted"><?= date('d M Y, h:i A', strtotime($oc['created_at'])) ?></small>
                                </div>
                                <p class="mb-2 small" style="color: var(--ink-700);"><?= nl2br(htmlspecialchars($oc['description'])) ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="fw-bold"><i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($oc['customer_name'] ?? 'Guest') ?></small>
                                    <span class="badge" style="background: <?= $oc['status'] === 'resolved' ? 'rgba(46,125,50,0.15)' : 'rgba(212,175,55,0.18)' ?>; color: <?= $oc['status'] === 'resolved' ? '#1b5e20' : '#7b5c12' ?>;">
                                        <?= ucfirst($oc['status']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status History -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-clock-rotate-left me-2"></i>
                    <span class="lang-bn">স্ট্যাটাস পরিবর্তনের ইতিহাস</span><span class="lang-en">Status History</span>
                </span>
            </div>
            <div class="admin-card-body">
                <?php if (empty($history)): ?>
                    <p class="text-muted small mb-0">No history yet.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($history as $h): ?>
                            <div class="d-flex gap-3">
                                <div style="width: 3px; background: var(--maroon-700); border-radius: 3px; flex-shrink: 0;"></div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between flex-wrap gap-1">
                                        <span class="badge bg-primary text-white"><?= htmlspecialchars($h['new_status']) ?></span>
                                        <small class="text-muted"><?= date('d M Y, h:i A', strtotime($h['changed_at'])) ?></small>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        By: <strong><?= htmlspecialchars($h['admin_name'] ?? 'System') ?></strong>
                                    </div>
                                    <?php if (!empty($h['note'])): ?>
                                        <div class="small fst-italic mt-1" style="color: var(--ink-500);">"<?= htmlspecialchars($h['note']) ?>"</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right Sidebar -->
    <div class="col-12 col-xl-4">

        <!-- Customer Info -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-user me-2"></i>
                    <span class="lang-bn">গ্রাহকের তথ্য</span><span class="lang-en">Customer Info</span>
                </span>
            </div>
            <div class="admin-card-body">
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Name</small>
                    <div class="fw-bold"><?= htmlspecialchars($order['shipping_name']) ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Phone</small>
                    <div><i class="fa-solid fa-phone me-1" style="color: var(--maroon-700);"></i><?= htmlspecialchars($order['shipping_phone']) ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Address</small>
                    <div class="small"><?= htmlspecialchars($order['shipping_address']) ?></div>
                </div>
                <div class="mb-0">
                    <small class="text-muted d-block mb-1">Area</small>
                    <span class="badge" style="background: rgba(123,17,19,0.1); color: var(--maroon-700);">
                        <?= htmlspecialchars($order['area_name'] ?? 'General') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-receipt me-2"></i>
                    <span class="lang-bn">পেমেন্ট সারসংক্ষেপ</span><span class="lang-en">Payment Summary</span>
                </span>
            </div>
            <div class="admin-card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Subtotal</span>
                    <span class="fw-semibold">৳<?= number_format($order['subtotal'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Discount</span>
                    <span class="fw-semibold" style="color: #2e7d32;">- ৳<?= number_format($order['discount_amount'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted small">Delivery Charge</span>
                    <span class="fw-semibold">৳<?= number_format($order['delivery_charge'], 2) ?></span>
                </div>
                <hr style="border-color: var(--border-soft);">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Grand Total</span>
                    <span class="fw-bold fs-5" style="color: var(--maroon-700); font-family: var(--font-display);">
                        ৳<?= number_format($order['total_amount'], 2) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Update Status -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-pen-to-square me-2"></i>
                    <span class="lang-bn">স্ট্যাটাস আপডেট</span><span class="lang-en">Update Status</span>
                </span>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--maroon-700);">
                            <i class="fa-solid fa-money-bill me-1"></i> Payment Status
                        </label>
                        <select name="payment_status" class="form-select" required>
                            <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid"    <?= $order['payment_status'] === 'paid'    ? 'selected' : '' ?>>Paid</option>
                            <option value="failed"  <?= $order['payment_status'] === 'failed'  ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fa-solid fa-cube me-1"></i> Order Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="new"        <?= $order['status'] == 'new'        ? 'selected' : '' ?>>New</option>
                            <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="shipped"    <?= $order['status'] == 'shipped'    ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered"  <?= $order['status'] == 'delivered'  ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled"  <?= $order['status'] == 'cancelled'  ? 'selected' : '' ?>>Cancelled</option>
                            <option value="returned"   <?= $order['status'] == 'returned'   ? 'selected' : '' ?>>Returned</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Internal Note (optional)</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="e.g. Handed to courier..."></textarea>
                    </div>

                    <button type="submit" name="update_status" class="admin-btn admin-btn-primary w-100 justify-content-center" style="padding: 11px;">
                        <i class="fa-solid fa-save"></i>
                        <span class="lang-bn">আপডেট করুন</span><span class="lang-en">Update Status</span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>