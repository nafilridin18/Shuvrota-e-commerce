<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_check.php';
require_once '../config/database.php';

$message = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$new_orders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();
$total_rev    = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'delivered'")->fetchColumn();
$low_stock    = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 3 AND status = 'published'")->fetchColumn();

$products_sql = "SELECT p.*, c.name as category_name, 
       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as primary_image
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC";
$products = $pdo->query($products_sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>এডমিন ড্যাশবোর্ড - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.lang-bn-mode .lang-en { display: none !important; }
        body.lang-bn-mode .lang-bn { display: inline-block !important; }
        body.lang-en-mode .lang-bn { display: none !important; }
        body.lang-en-mode .lang-en { display: inline-block !important; }

        .order-column { background: #fff; border-radius: 12px; padding: 15px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); min-height: 400px; }
        .order-item-card { background: #fdfdfd; border: 1px solid #eee; border-radius: 8px; padding: 12px; margin-bottom: 12px; transition: 0.2s; }
        .order-item-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light lang-bn-mode">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-danger" href="index.php"><i class="fa-solid fa-gauge me-2"></i><span class="lang-bn">শুভ্রতা এডমিন</span><span class="lang-en">Shuvrota Admin</span></a>
        
        <div class="d-flex align-items-center gap-3 ms-auto">
            <span class="text-light small d-none d-md-inline">
                <span class="lang-bn">স্বাগতম, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong></span>
                <span class="lang-en">Welcome, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong></span>
            </span>

            <div class="dropdown">
                <a class="btn btn-sm btn-secondary px-3 dropdown-toggle rounded-pill" href="#" role="button" data-bs-toggle="dropdown" id="currentLangText">বাংলা</a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><button type="button" class="dropdown-item small" onclick="switchLanguage('bn')">বাংলা (BN)</button></li>
                    <li><button type="button" class="dropdown-item small" onclick="switchLanguage('en')">English (EN)</button></li>
                </ul>
            </div>

            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill"><i class="fa-solid fa-right-from-bracket me-1"></i> <span class="lang-bn">লগআউট</span><span class="lang-en">Logout</span></a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold text-dark mb-0">
            <span class="lang-bn">ড্যাশবোর্ড ওভারভিউ</span><span class="lang-en">Dashboard Overview</span>
        </h2>
        <div class="d-flex gap-2 flex-wrap">
            <a href="add_product.php" class="btn btn-danger"><i class="fa-solid fa-plus me-1"></i> <span class="lang-bn">নতুন প্রোডাক্ট যোগ করুন</span><span class="lang-en">Add New Product</span></a>
            <a href="coupons.php" class="btn btn-secondary"><i class="fa-solid fa-ticket me-1"></i> <span class="lang-bn">কুপন</span><span class="lang-en">Coupons</span></a>
            <a href="banner_settings.php" class="btn btn-warning text-dark fw-bold"><i class="fa-solid fa-image me-1"></i> <span class="lang-bn">ব্যানার ও কালেকশন ম্যানেজ</span><span class="lang-en">Manage Banners</span></a>
            <a href="../index.php" class="btn btn-outline-dark" target="_blank"><i class="fa-solid fa-globe me-1"></i> <span class="lang-bn">ওয়েবসাইট দেখুন</span><span class="lang-en">Visit Website</span></a>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-white-50"><span class="lang-bn">সর্বমোট অর্ডার</span><span class="lang-en">Total Orders</span></small>
                        <h3 class="fw-bold mb-0"><?= $total_orders ?></h3>
                    </div>
                    <i class="fa-solid fa-cart-shopping fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 bg-warning text-dark p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-dark-50"><span class="lang-bn">নতুন অর্ডার</span><span class="lang-en">New Orders</span></small>
                        <h3 class="fw-bold mb-0"><?= $new_orders ?></h3>
                    </div>
                    <i class="fa-solid fa-clock fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 bg-success text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-white-50"><span class="lang-bn">ডেলিভারড আয়</span><span class="lang-en">Delivered Revenue</span></small>
                        <h3 class="fw-bold mb-0">৳ <?= number_format($total_rev, 2) ?></h3>
                    </div>
                    <i class="fa-solid fa-bangladeshi-taka-sign fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 bg-danger text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-white-50"><span class="lang-bn">কম স্টক প্রোডাক্ট</span><span class="lang-en">Low Stock Products</span></small>
                        <h3 class="fw-bold mb-0"><?= $low_stock ?></h3>
                    </div>
                    <i class="fa-solid fa-triangle-exclamation fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <?php
    $statuses = ['new' => 'New', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
    $status_colors = ['new' => 'warning', 'processing' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger'];
    ?>
    <h4 class="fw-bold mb-3"><i class="fa-solid fa-columns me-2"></i><span class="lang-bn">গ্রাহকদের অর্ডারের কলামভিত্তিক তালিকা</span><span class="lang-en">Customer Orders Pipeline</span></h4>
    <div class="row g-3 mb-5">
        <?php foreach($statuses as $st_key => $st_name): ?>
            <?php
                $st_stmt = $pdo->prepare("SELECT o.*, da.area_name FROM orders o LEFT JOIN delivery_areas da ON o.shipping_area_id = da.id WHERE o.status = ? ORDER BY o.id DESC");
                $st_stmt->execute([$st_key]);
                $st_orders = $st_stmt->fetchAll();
            ?>
            <div class="col">
                <div class="order-column border-top border-<?= $status_colors[$st_key] ?> border-4">
                    <h6 class="fw-bold text-<?= $status_colors[$st_key] ?> mb-3 d-flex justify-content-between align-items-center">
                        <span><?= $st_name ?></span>
                        <span class="badge bg-<?= $status_colors[$st_key] ?> <?= in_array($st_key, ['new', 'processing']) ? 'text-dark' : 'text-white' ?>"><?= count($st_orders) ?></span>
                    </h6>
                    <div class="orders-list">
                        <?php if(!empty($st_orders)): ?>
                            <?php foreach($st_orders as $ord): ?>
                                <?php
                                    $item_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                                    $item_stmt->execute([$ord['id']]);
                                    $items = $item_stmt->fetchAll();
                                ?>
                                <div class="order-item-card">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-danger small"><?= htmlspecialchars($ord['order_number']) ?></span>
                                        <span class="text-success fw-bold small">৳ <?= number_format($ord['total_amount'], 2) ?></span>
                                    </div>
                                    <div class="fw-semibold small text-dark mb-1"><?= htmlspecialchars($ord['shipping_name'] ?? $ord['name'] ?? 'N/A') ?></div>
                                    <div class="text-muted small mb-2"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($ord['shipping_phone'] ?? $ord['phone'] ?? 'N/A') ?></div>
                                    
                                    <div class="bg-light p-1 rounded small mb-2 text-muted" style="font-size: 11px;">
                                        <?php foreach($items as $it): ?>
                                            <div>• <?= htmlspecialchars($it['product_name']) ?> (<?= $it['quantity'] ?>x)</div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted" style="font-size: 10px;"><?= date('d M, h:i A', strtotime($ord['placed_at'] ?? $ord['created_at'])) ?></small>
                                        <a href="order_details.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-dark py-0 px-2" style="font-size: 11px;">
                                            <i class="fa-solid fa-eye"></i> <span class="lang-bn">ডিটেইলস</span><span class="lang-en">Details</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted small text-center py-4"><span class="lang-bn">কোনো অর্ডার নেই</span><span class="lang-en">No orders</span></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white p-3 rounded-top-4">
            <h4 class="mb-0 fs-5"><i class="fa-solid fa-boxes-stacked me-2"></i><span class="lang-bn">প্রোডাক্ট তালিকা</span><span class="lang-en">Product List</span></h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th class="ps-3"><span class="lang-bn">ছবি</span><span class="lang-en">Image</span></th>
                            <th><span class="lang-bn">নাম</span><span class="lang-en">Name</span></th>
                            <th><span class="lang-bn">ক্যাটাগরি</span><span class="lang-en">Category</span></th>
                            <th><span class="lang-bn">মূল্য</span><span class="lang-en">Price</span></th>
                            <th><span class="lang-bn">স্টক</span><span class="lang-en">Stock</span></th>
                            <th class="text-center"><span class="lang-bn">অ্যাকশন</span><span class="lang-en">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted"><span class="lang-bn">কোনো প্রোডাক্ট পাওয়া যায়নি।</span><span class="lang-en">No products found.</span></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($products as $p): ?>
                                <?php 
                                    $img = $p['primary_image'] ?? '';
                                    $img_src = (!empty($img) && file_exists('../uploads/' . $img)) ? '../uploads/' . $img : 'https://dummyimage.com/60x60/e0e0e0/000000.png&text=No+Img';
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <img src="<?= htmlspecialchars($img_src) ?>" width="50" height="50" class="rounded object-fit-cover border">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($p['name_bn'] ?? $p['name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($p['category_name'] ?? 'General') ?></span></td>
                                    <td>৳ <?= number_format($p['discount_price'] ?? $p['price'], 2) ?></td>
                                    <td><?= $p['stock_quantity'] ?></td>
                                    <td class="text-center">
                                        <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-dark me-1">
                                            <i class="fa-solid fa-pen-to-square"></i> <span class="lang-bn">এডিট</span><span class="lang-en">Edit</span>
                                        </a>
                                        <a href="delete_product.php?id=<?= $p['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('আপনি কি নিশ্চিত যে এই প্রোডাক্টটি মুছে ফেলতে চান?');">
                                            <i class="fa-solid fa-trash me-1"></i> <span class="lang-bn">ডিলিট</span><span class="lang-en">Delete</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchLanguage(lang) {
    const body = document.body;
    if (lang === 'en') {
        body.classList.remove('lang-bn-mode');
        body.classList.add('lang-en-mode');
        document.getElementById('currentLangText').innerText = 'English';
        localStorage.setItem('adminLang', 'en');
    } else {
        body.classList.remove('lang-en-mode');
        body.classList.add('lang-bn-mode');
        document.getElementById('currentLangText').innerText = 'বাংলা';
        localStorage.setItem('adminLang', 'bn');
    }
}

window.onload = function() {
    const savedLang = localStorage.getItem('adminLang') || 'bn';
    switchLanguage(savedLang);
};
</script>
</body>
</html>