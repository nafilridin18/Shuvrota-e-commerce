<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Handle Toggle Best Selling Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $p_id = (int)$_POST['toggle_id'];
    $current_val = (int)$_POST['current_val'];
    $new_val = $current_val ? 0 : 1;
    
    $pdo->prepare("UPDATE products SET is_best_selling = ? WHERE id = ?")->execute([$new_val, $p_id]);
    header("Location: manage_best_selling.php");
    exit;
}

// ALGORITHM: Find Most Sold Products that are NOT YET marked as Best Selling
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

// Currently Best Selling Products
$current_best = $pdo->query("SELECT id, name, price, stock_quantity, is_best_selling FROM products WHERE is_best_selling = 1 ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Best Selling Management - Shuvrota Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <h2>Best Selling Algorithm & Management</h2>
    <a href="index.php" class="btn btn-secondary mb-4">ড্যাশবোর্ডে ফিরুন</a>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-dark text-white fw-bold">
                    System Suggestions (Top Selling Products)
                </div>
                <div class="card-body">
                    <p class="text-muted small">সিস্টেম সবচেয়ে বেশি বিক্রি হওয়া (Delivered/Shipped) প্রোডাক্টগুলো সাজেস্ট করছে।</p>
                    <table class="table align-middle">
                        <thead class="table-light"><tr><th>Product Name</th><th>Sold Qty</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if(empty($suggestions)): ?>
                                <tr><td colspan="3" class="text-center text-muted">কোনো নতুন সাজেশন নেই।</td></tr>
                            <?php else: foreach($suggestions as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['name']) ?></td>
                                    <td class="fw-bold text-success"><?= $s['total_sold'] ?> Pcs</td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="toggle_id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="current_val" value="0">
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">+ Add to Best Selling</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-danger text-white fw-bold">
                    Currently Live on Homepage
                </div>
                <div class="card-body">
                    <table class="table align-middle">
                        <thead class="table-light"><tr><th>Product Name</th><th>Stock</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if(empty($current_best)): ?>
                                <tr><td colspan="3" class="text-center text-muted">কোনো বেস্ট সেলিং প্রোডাক্ট সেট করা নেই।</td></tr>
                            <?php else: foreach($current_best as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['name']) ?></td>
                                    <td><?= $c['stock_quantity'] ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="toggle_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="current_val" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>