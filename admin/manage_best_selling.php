<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    csrf_require();
    $p_id = (int)$_POST['toggle_id'];
    $current_val = (int)$_POST['current_val'];
    $new_val = $current_val ? 0 : 1;

    $pdo->prepare("UPDATE products SET is_best_selling = ? WHERE id = ?")->execute([$new_val, $p_id]);
    header("Location: manage_best_selling.php");
    exit;
}

$suggest_stmt = $pdo->query("
    SELECT p.id, p.name, p.price, p.stock_quantity, p.is_best_selling, IFNULL(SUM(oi.quantity), 0) as total_sold
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('delivered', 'shipped')
    WHERE p.status = 'published' AND p.is_best_selling = 0
    GROUP BY p.id
    HAVING total_sold > 0
    ORDER BY total_sold DESC
    LIMIT 10
");
$suggestions = $suggest_stmt->fetchAll();

$current_best = $pdo->query("
    SELECT p.id, p.name, p.price, p.stock_quantity,
           (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as img
    FROM products p
    WHERE p.is_best_selling = 1
    ORDER BY p.id DESC
")->fetchAll();

$pageTitle     = 'Best Selling — শুভ্রতা এডমিন';
$pageHeadingBn = 'বেস্ট সেলিং ম্যানেজমেন্ট';
$pageHeadingEn = 'Best Selling Management';
$activePage    = 'best';
include 'includes/header.php';
?>

<div class="row g-4">

    <!-- System Suggestions -->
    <div class="col-12 col-xl-6">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-brain me-2"></i>
                    <span class="lang-bn">সিস্টেম সাজেশন</span><span class="lang-en">System Suggestions</span>
                </span>
            </div>
            <div class="admin-card-body">
                <p class="text-muted small mb-3">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    সর্বাধিক বিক্রি হওয়া (Delivered / Shipped) প্রোডাক্ট সাজেস্ট করা হচ্ছে।
                </p>

                <?php if (empty($suggestions)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-check-circle fs-1" style="color: #2e7d32; opacity:0.4;"></i>
                        <p class="text-muted small mt-2 mb-0">No new suggestions available.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($suggestions as $s): ?>
                            <div class="d-flex align-items-center gap-3 p-3 rounded"
                                 style="background: rgba(244,236,223,0.5); border: 1px solid var(--border-soft);">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-truncate" style="font-size: 0.92rem;"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="small mt-1">
                                        <span class="badge" style="background: rgba(46,125,50,0.15); color:#1b5e20;">
                                            <i class="fa-solid fa-fire me-1"></i><?= $s['total_sold'] ?> sold
                                        </span>
                                        <span class="text-muted ms-2">Stock: <?= $s['stock_quantity'] ?></span>
                                    </div>
                                </div>
                                <form method="POST" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="toggle_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="current_val" value="0">
                                    <button type="submit" class="admin-btn admin-btn-gold admin-btn-sm">
                                        <i class="fa-solid fa-plus"></i> Add
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Currently Live -->
    <div class="col-12 col-xl-6">
        <div class="admin-card h-100">
            <div class="admin-card-header" style="background: linear-gradient(135deg, #7b1113, #4a0c0e);">
                <span><i class="fa-solid fa-fire me-2"></i>
                    <span class="lang-bn">হোমপেজে লাইভ</span><span class="lang-en">Live on Homepage</span>
                </span>
                <span class="badge" style="background: var(--gold-500); color:#241b17;"><?= count($current_best) ?></span>
            </div>
            <div class="admin-card-body">
                <?php if (empty($current_best)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-fire fs-1 text-muted" style="opacity:0.3;"></i>
                        <p class="text-muted small mt-2 mb-0">No best-selling products set yet.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($current_best as $c):
                            $img = $c['img'] ?? '';
                            $img_src = $img ? '../uploads/' . htmlspecialchars($img) : 'https://dummyimage.com/50x50/e0e0e0/000000.png&text=No+Img';
                        ?>
                            <div class="d-flex align-items-center gap-3 p-3 rounded"
                                 style="background: rgba(244,236,223,0.5); border: 1px solid var(--border-soft);">
                                <img src="<?= $img_src ?>" width="46" height="46" class="rounded" style="object-fit:cover;">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-truncate" style="font-size: 0.92rem;"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="small text-muted mt-1">Stock: <?= $c['stock_quantity'] ?> · ৳<?= number_format($c['price'], 0) ?></div>
                                </div>
                                <form method="POST" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="toggle_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="current_val" value="1">
                                    <button type="submit" class="admin-btn admin-btn-sm" style="background:#fee; color:#c62828; border:1.5px solid #c62828;">
                                        <i class="fa-solid fa-xmark"></i> Remove
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>