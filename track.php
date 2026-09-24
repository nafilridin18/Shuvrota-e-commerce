<?php
require_once __DIR__ . '/config/session.php';
require_once 'config/database.php';

$orders = [];
$searched = false;
$rateLimited = false;
$errorMsg = '';

const TRACK_MAX_ATTEMPTS_PER_WINDOW = 5;
const TRACK_WINDOW_SECONDS = 60;

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['order_number']) && isset($_GET['phone'])) {
    $orderNumber = trim($_GET['order_number']);
    $phone       = trim($_GET['phone']);

    if ($orderNumber !== '' && $phone !== '') {
        $searched = true;
        $ip = client_ip();

        // ---- Rate limit: max TRACK_MAX_ATTEMPTS_PER_WINDOW lookups per IP per minute ----
        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM order_track_attempts WHERE ip_address = ? AND attempted_at > (NOW() - INTERVAL ? SECOND)"
        );
        $countStmt->execute([$ip, TRACK_WINDOW_SECONDS]);
        $recentAttempts = (int) $countStmt->fetchColumn();

        if ($recentAttempts >= TRACK_MAX_ATTEMPTS_PER_WINDOW) {
            $rateLimited = true;
        } else {
            // Log this attempt regardless of outcome (so brute-forcing pairs is also throttled)
            $pdo->prepare("INSERT INTO order_track_attempts (ip_address) VALUES (?)")->execute([$ip]);

            // Occasionally prune old rows so this table doesn't grow forever
            if (random_int(1, 50) === 1) {
                $pdo->exec("DELETE FROM order_track_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
            }

            // Both order_number AND shipping_phone must match the SAME order.
            // (Previously this was OR'd on phone alone, which let anyone page
            // through BD mobile numbers and scrape every customer's name,
            // phone and full address with no login at all.)
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND shipping_phone = ? ORDER BY id DESC");
            $stmt->execute([$orderNumber, $phone]);
            $orders = $stmt->fetchAll();
        }
    } else {
        $errorMsg = 'অর্ডার নম্বর এবং মোবাইল নম্বর উভয়ই দিতে হবে।';
    }
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
            <div class="mb-3">
                <label class="form-label small text-muted">
                    <span class="lang-bn">অর্ডার নম্বর</span><span class="lang-en">Order Number</span>
                </label>
                <input type="text" name="order_number" class="form-control form-control-lg" placeholder="SHV-20260901-0001" value="<?= isset($_GET['order_number']) ? htmlspecialchars($_GET['order_number']) : '' ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted">
                    <span class="lang-bn">মোবাইল নম্বর</span><span class="lang-en">Mobile Number</span>
                </label>
                <input type="text" name="phone" class="form-control form-control-lg" placeholder="01XXXXXXXXX" value="<?= isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : '' ?>" required>
            </div>
            <div class="small text-muted mb-3">
                <span class="lang-bn">উভয় তথ্য আপনার অর্ডার কনফার্মেশন এসএমএস/ইমেইলে পাবেন।</span>
                <span class="lang-en">You'll find both in your order confirmation SMS/email.</span>
            </div>
            <button class="btn btn-danger w-100 btn-lg" type="submit">
                <span class="lang-bn">ট্র্যাক করুন</span>
                <span class="lang-en">Track</span>
            </button>
        </form>
    </div>

    <?php if ($rateLimited): ?>
        <div class="alert alert-warning text-center">
            <span class="lang-bn">অনেকবার চেষ্টা করা হয়েছে। এক মিনিট পর আবার চেষ্টা করুন।</span>
            <span class="lang-en">Too many attempts. Please wait a minute and try again.</span>
        </div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php elseif (!empty($orders)): ?>
        <?php foreach ($orders as $order): ?>
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
    <?php elseif ($searched): ?>
        <div class="alert alert-danger text-center">
            <span class="lang-bn">কোনো অর্ডার খুঁজে পাওয়া যায়নি! অর্ডার নম্বর ও মোবাইল নম্বর ঠিক আছে কিনা যাচাই করুন।</span>
            <span class="lang-en">No matching order found! Please check both the order number and mobile number.</span>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
