<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code       = strtoupper(trim($_POST['code']));
    $type       = $_POST['type'];
    $value      = (float)$_POST['value'];
    $min_amount = !empty($_POST['min_order_amount']) ? (float)$_POST['min_order_amount'] : 0;
    $limit      = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : NULL;

    try {
        $stmt = $pdo->prepare("INSERT INTO coupons (code, type, value, min_order_amount, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$code, $type, $value, $min_amount, $limit]);
        $message = "কুপন সফলভাবে তৈরি হয়েছে!";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "ত্রুটি: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $cid = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE coupons SET is_active = 1 - is_active WHERE id = ?")->execute([$cid]);
    header('Location: coupons.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $cid = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$cid]);
    header('Location: coupons.php');
    exit;
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();

$pageTitle     = 'কুপন ম্যানেজমেন্ট — শুভ্রতা এডমিন';
$pageHeadingBn = 'কুপন ম্যানেজমেন্ট';
$pageHeadingEn = 'Coupon Management';
$activePage    = 'coupons';
include 'includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- Add Coupon Form -->
    <div class="col-12 col-xl-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-ticket me-2"></i>
                    <span class="lang-bn">নতুন কুপন</span><span class="lang-en">New Coupon</span>
                </span>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Coupon Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. SHUVRO10" required style="text-transform: uppercase;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discount Type *</label>
                        <select name="type" class="form-select" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (৳)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discount Value *</label>
                        <input type="number" step="0.01" name="value" class="form-control" placeholder="10 or 100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Minimum Order Amount (৳)</label>
                        <input type="number" step="0.01" name="min_order_amount" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usage Limit</label>
                        <input type="number" name="usage_limit" class="form-control" placeholder="Leave empty for unlimited">
                    </div>
                    <button type="submit" name="add_coupon" class="admin-btn admin-btn-primary w-100 justify-content-center" style="padding: 11px;">
                        <i class="fa-solid fa-plus"></i>
                        <span class="lang-bn">কুপন সেভ করুন</span><span class="lang-en">Save Coupon</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Coupons List -->
    <div class="col-12 col-xl-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-list me-2"></i>
                    <span class="lang-bn">বিদ্যমান কুপন তালিকা</span><span class="lang-en">Existing Coupons</span>
                </span>
                <span class="badge" style="background: var(--gold-500); color:#241b17;"><?= count($coupons) ?> Total</span>
            </div>
            <div class="table-responsive">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Min Order</th>
                            <th>Used</th>
                            <th>Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No coupons yet.</td></tr>
                        <?php else: foreach ($coupons as $c): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold" style="color: var(--maroon-700); font-family: monospace; font-size: 14px;">
                                        <?= htmlspecialchars($c['code']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    <?= $c['type'] === 'percentage' ? $c['value'].'%' : '৳'.$c['value'] ?>
                                </td>
                                <td>৳<?= number_format($c['min_order_amount'], 2) ?></td>
                                <td>
                                    <span class="badge" style="background: rgba(123,17,19,0.1); color: var(--maroon-700);">
                                        <?= $c['used_count'] ?> / <?= $c['usage_limit'] ?? '∞' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?toggle=<?= $c['id'] ?>" class="text-decoration-none">
                                        <span class="badge" style="background: <?= $c['is_active'] ? 'rgba(46,125,50,0.15)' : 'rgba(198,40,40,0.15)' ?>; color: <?= $c['is_active'] ? '#1b5e20' : '#8e0000' ?>;">
                                            <i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i>
                                            <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </a>
                                </td>
                                <td style="text-align:center;">
                                    <a href="?delete=<?= $c['id'] ?>"
                                       class="admin-btn admin-btn-sm"
                                       style="background:#fee; color:#c62828; border:1.5px solid #c62828;"
                                       onclick="return confirm('Delete this coupon?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>