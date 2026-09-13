<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';
$orders = [];
$searched = false;

if (isset($_GET['order_number']) && !empty(trim($_GET['order_number']))) {
    $searched = true;
    $search = trim($_GET['order_number']);
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? OR shipping_phone = ? ORDER BY id DESC");
    $stmt->execute([$search, $search]);
    $orders = $stmt->fetchAll();
}

$pageTitle = 'অর্ডার ট্র্যাকিং - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4 my-md-5" style="max-width: 650px;">
    <h2 class="section-heading text-center mb-4">
        <span class="lang-bn">লাইভ অর্ডার ট্র্যাকিং</span>
        <span class="lang-en">Live Order Tracking</span>
    </h2>

    <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
        <form method="GET">
            <div class="input-group input-group-lg">
                <input type="text" name="order_number" class="form-control" placeholder="অর্ডার নম্বর বা মোবাইল নম্বর দিন" value="<?= isset($_GET['order_number']) ? htmlspecialchars($_GET['order_number']) : '' ?>" required>
                <button class="btn btn-danger" type="submit">
                    <span class="lang-bn">ট্র্যাক করুন</span>
                    <span class="lang-en">Track</span>
                </button>
            </div>
        </form>
    </div>

    <?php if(!empty($orders)): ?>
        <?php foreach($orders as $order): ?>
            <?php
                $status = strtolower(trim($order['status']));
                $badgeBg = 'bg-secondary';
                if ($status === 'new') {
                    $badgeBg = 'bg-warning text-dark';
                } elseif ($status === 'processing') {
                    $badgeBg = 'bg-info text-dark';
                } elseif ($status === 'shipped') {
                    $badgeBg = 'bg-primary text-white';
                } elseif ($status === 'delivered') {
                    $badgeBg = 'bg-success text-white';
                } elseif ($status === 'cancelled') {
                    $badgeBg = 'bg-danger text-white';
                }
            ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-custom-dark text-white p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><span class="lang-bn">অর্ডার নম্বর:</span><span class="lang-en">Order No:</span> <?= htmlspecialchars($order['order_number']) ?></span>
                    <span class="badge <?= $badgeBg ?> text-uppercase px-3 py-2">
                         <?= htmlspecialchars($order['status']) ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <p><strong><span class="lang-bn">নাম:</span><span class="lang-en">Name:</span></strong> <?= htmlspecialchars($order['shipping_name'] ?? $order['name'] ?? 'N/A') ?></p>
                    <p><strong><span class="lang-bn">মোবাইল:</span><span class="lang-en">Phone:</span></strong> <?= htmlspecialchars($order['shipping_phone'] ?? $order['phone'] ?? 'N/A') ?></p>
                    <p><strong><span class="lang-bn">ঠিকানা:</span><span class="lang-en">Address:</span></strong> <?= htmlspecialchars($order['shipping_address'] ?? $order['address'] ?? 'N/A') ?></p>

                    <div class="alert alert-light border mt-3 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span><strong><span class="lang-bn">অর্ডারের বর্তমান অবস্থা:</span><span class="lang-en">Current Order Status:</span></strong></span>
                        <span class="badge <?= $badgeBg ?> text-uppercase px-3 py-2"><?= htmlspecialchars($order['status']) ?></span>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><span class="lang-bn">সর্বমোট বিল:</span><span class="lang-en">Total Bill:</span></span>
                        <span class="fs-4 fw-bold text-danger">৳ <?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php elseif($searched): ?>
        <div class="alert alert-danger text-center">
            <span class="lang-bn">কোনো অর্ডার খুঁজে পাওয়া যায়নি!</span>
            <span class="lang-en">No order found!</span>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
