<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';

$message = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$new_orders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();
$total_rev    = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'delivered' AND payment_status = 'Paid'")->fetchColumn();
$low_stock    = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 3 AND status = 'published'")->fetchColumn();

$products_sql = "SELECT p.*, c.name as category_name,
       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as primary_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC";
$products = $pdo->query($products_sql)->fetchAll();

$pageTitle     = 'ড্যাশবোর্ড — শুভ্রতা এডমিন';
$pageHeadingBn = 'ড্যাশবোর্ড ওভারভিউ';
$pageHeadingEn = 'Dashboard Overview';
$activePage    = 'dashboard';
include 'includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="add_product.php" class="admin-btn admin-btn-primary">
        <i class="fa-solid fa-plus"></i>
        <span class="lang-bn">নতুন প্রোডাক্ট</span><span class="lang-en">Add Product</span>
    </a>
    <a href="coupons.php" class="admin-btn admin-btn-gold">
        <i class="fa-solid fa-ticket"></i>
        <span class="lang-bn">কুপন</span><span class="lang-en">Coupons</span>
    </a>
    <a href="banner_settings.php" class="admin-btn admin-btn-outline">
        <i class="fa-solid fa-image"></i>
        <span class="lang-bn">ব্যানার ও লোগো</span><span class="lang-en">Banners & Logo</span>
    </a>
    <a href="../index.php" target="_blank" class="admin-btn admin-btn-outline">
        <i class="fa-solid fa-globe"></i>
        <span class="lang-bn">ওয়েবসাইট দেখুন</span><span class="lang-en">Visit Website</span>
    </a>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small><span class="lang-bn">সর্বমোট অর্ডার</span><span class="lang-en">Total Orders</span></small>
                    <h3><?= number_format($total_orders) ?></h3>
                </div>
                <i class="fa-solid fa-cart-shopping stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small><span class="lang-bn">নতুন অর্ডার</span><span class="lang-en">New Orders</span></small>
                    <h3><?= number_format($new_orders) ?></h3>
                </div>
                <i class="fa-solid fa-clock stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small><span class="lang-bn">ডেলিভারড আয়</span><span class="lang-en">Delivered Revenue</span></small>
                    <h3>৳<?= number_format($total_rev, 0) ?></h3>
                </div>
                <i class="fa-solid fa-coins stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card danger">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small><span class="lang-bn">কম স্টক</span><span class="lang-en">Low Stock</span></small>
                    <h3><?= number_format($low_stock) ?></h3>
                </div>
                <i class="fa-solid fa-triangle-exclamation stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Order Pipeline -->
<?php
$statuses = [
    'new'        => ['New',        'warning'],
    'processing' => ['Processing', 'info'],
    'shipped'    => ['Shipped',    'primary'],
    'delivered'  => ['Delivered',  'success'],
    'cancelled'  => ['Cancelled',  'danger'],
    'returned'   => ['Returned',   'secondary'],
];
?>
<h4 class="fw-bold mb-3" style="font-family: var(--font-display);">
    <i class="fa-solid fa-diagram-project me-2" style="color: var(--maroon-700);"></i>
    <span class="lang-bn">অর্ডার পাইপলাইন</span><span class="lang-en">Order Pipeline</span>
</h4>

<div class="pipeline-scroll row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-6 g-2 mb-5">
    <?php foreach ($statuses as $st_key => [$st_name, $st_color]):
        $st_stmt = $pdo->prepare("SELECT o.* FROM orders o WHERE o.status = ? ORDER BY o.id DESC LIMIT 30");
        $st_stmt->execute([$st_key]);
        $st_orders = $st_stmt->fetchAll();
    ?>
        <div class="col">
            <div class="pipeline-col">
                <div class="pipeline-col-header text-<?= $st_color ?>">
                    <span><?= $st_name ?></span>
                    <span class="badge bg-<?= $st_color ?> <?= in_array($st_key, ['new','processing']) ? 'text-dark' : 'text-white' ?>"><?= count($st_orders) ?></span>
                </div>

                <?php if (!empty($st_orders)): ?>
                    <?php foreach ($st_orders as $ord):
                        $item_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                        $item_stmt->execute([$ord['id']]);
                        $items = $item_stmt->fetchAll();

                        $p_status = strtolower($ord['payment_status'] ?? 'pending');
                        $p_badge  = $p_status === 'paid' ? 'bg-success' : ($p_status === 'failed' ? 'bg-danger' : 'bg-warning text-dark');
                    ?>
                        <div class="order-card">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="order-num">#<?= htmlspecialchars($ord['order_number']) ?></span>
                                <span class="order-total">৳<?= number_format($ord['total_amount'], 0) ?></span>
                            </div>
                            <span class="badge <?= $p_badge ?>" style="font-size: 9px; padding: 3px 7px;">
                                <?= htmlspecialchars(ucfirst($ord['payment_status'] ?? 'Pending')) ?>
                            </span>
                            <div class="customer-name mt-2"><?= htmlspecialchars($ord['shipping_name'] ?? $ord['guest_name'] ?? 'N/A') ?></div>
                            <div class="customer-phone"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($ord['shipping_phone'] ?? $ord['guest_phone'] ?? 'N/A') ?></div>

                            <?php if (!empty($items)): ?>
                                <div class="items-preview">
                                    <?php foreach (array_slice($items, 0, 2) as $it): ?>
                                        <div>• <?= htmlspecialchars($it['product_name']) ?> (<?= $it['quantity'] ?>x)</div>
                                    <?php endforeach; ?>
                                    <?php if (count($items) > 2): ?>
                                        <div class="fst-italic">+<?= count($items) - 2 ?> more</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="meta-row">
                                <small><?= date('d M, h:i A', strtotime($ord['placed_at'] ?? $ord['created_at'])) ?></small>
                                <a href="order_details.php?id=<?= $ord['id'] ?>" class="admin-btn admin-btn-outline admin-btn-sm">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small text-center py-4 mb-0">
                        <span class="lang-bn">কোনো অর্ডার নেই</span><span class="lang-en">No orders</span>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Product Table -->
<div class="admin-card" id="products">
    <div class="admin-card-header">
        <span><i class="fa-solid fa-boxes-stacked me-2"></i>
            <span class="lang-bn">প্রোডাক্ট তালিকা</span><span class="lang-en">Product List</span>
        </span>
        <a href="add_product.php" class="admin-btn admin-btn-gold admin-btn-sm">
            <i class="fa-solid fa-plus"></i>
            <span class="lang-bn">নতুন</span><span class="lang-en">New</span>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No products found.</td></tr>
                <?php else: foreach ($products as $p):
                    $img = $p['primary_image'] ?? '';
                    $img_src = (!empty($img) && file_exists('../uploads/' . $img))
                        ? '../uploads/' . $img
                        : 'https://dummyimage.com/60x60/e0e0e0/000000.png&text=No+Img';
                ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($img_src) ?>" width="48" height="48" class="rounded" style="object-fit:cover; border:1px solid var(--border-soft);"></td>
                        <td class="fw-bold"><?= htmlspecialchars($p['name_bn'] ?? $p['name']) ?></td>
                        <td><span class="badge" style="background: rgba(123,17,19,0.1); color: var(--maroon-700); font-weight:600;"><?= htmlspecialchars($p['category_name'] ?? 'General') ?></span></td>
                        <td class="fw-bold">৳<?= number_format($p['discount_price'] ?? $p['price'], 0) ?></td>
                        <td>
                            <span class="badge" style="background: <?= $p['stock_quantity'] > 3 ? 'rgba(46,125,50,0.14)' : 'rgba(198,40,40,0.14)' ?>;
                                color: <?= $p['stock_quantity'] > 3 ? '#1b5e20' : '#8e0000' ?>;">
                                <?= $p['stock_quantity'] ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <a href="edit_product.php?id=<?= $p['id'] ?>" class="admin-btn admin-btn-outline admin-btn-sm">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="delete_product.php?id=<?= $p['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>"
                               class="admin-btn admin-btn-sm" style="background:#fee; color:#c62828; border:1.5px solid #c62828;"
                               onclick="return confirm('মুছে ফেলতে চান?');">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>