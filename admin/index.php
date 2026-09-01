<?php
session_start();
require_once '../config/database.php';

$message = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

// Fetch All Orders with Items & Customer Details
$orders_sql = "SELECT o.*, da.area_name 
               FROM orders o 
               LEFT JOIN delivery_areas da ON o.shipping_area_id = da.id 
               ORDER BY o.id DESC";
$orders = $pdo->query($orders_sql)->fetchAll();

// Fetch All Products
$products_sql = "SELECT p.*, c.name as category_name, 
       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as primary_image
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC";
$products = $pdo->query($products_sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Shubhrata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="fa-solid fa-gauge me-2"></i>Admin Dashboard</h2>
        <div>
            <a href="add_product.php" class="btn btn-danger"><i class="fa-solid fa-plus me-1"></i> Add New Product</a>
            <a href="../index.php" class="btn btn-outline-dark ms-2" target="_blank"><i class="fa-solid fa-globe me-1"></i> View Website</a>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Section 1: Customer Orders Record -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-dark text-white p-3 rounded-top-4 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fs-5"><i class="fa-solid fa-cart-shopping me-2"></i>Customer Orders Record</h4>
            <span class="badge bg-danger fs-6"><?= count($orders) ?> Total Orders</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th class="ps-3">Order Number</th>
                            <th>Customer Info</th>
                            <th>Items Ordered</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No orders placed yet. Test checkout to generate records.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($orders as $ord): ?>
                                <?php
                                    // Fetch items for this order
                                    $item_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                                    $item_stmt->execute([$ord['id']]);
                                    $items = $item_stmt->fetchAll();
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-danger"><?= htmlspecialchars($ord['order_number']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($ord['shipping_name']) ?></strong><br>
                                        <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($ord['shipping_phone']) ?></small><br>
                                        <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($ord['shipping_address']) ?> (<?= htmlspecialchars($ord['area_name'] ?? 'General') ?>)</small>
                                    </td>
                                    <td>
                                        <ul class="list-unstyled mb-0 small">
                                            <?php foreach($items as $it): ?>
                                                <li>• <?= htmlspecialchars($it['product_name']) ?> (<?= htmlspecialchars($it['size']) ?>/<?= htmlspecialchars($it['color']) ?>) x <?= $it['quantity'] ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td class="fw-bold text-success">৳ <?= number_format($ord['total_amount'], 2) ?></td>
                                    <td>
                                        <?php if($ord['status'] === 'new'): ?>
                                            <span class="badge bg-warning text-dark">New</span>
                                        <?php elseif($ord['status'] === 'delivered'): ?>
                                            <span class="badge bg-success">Delivered</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($ord['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: Product Management -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-dark text-white p-3 rounded-top-4">
            <h4 class="mb-0 fs-5"><i class="fa-solid fa-boxes-stacked me-2"></i>Product List</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th class="ps-3">Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No products found.</td>
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
                                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($p['category_name'] ?? 'General') ?></span></td>
                                    <td>৳ <?= number_format($p['discount_price'] ?? $p['price'], 2) ?></td>
                                    <td><?= $p['stock_quantity'] ?></td>
                                    <td class="text-center">
                                        <a href="delete_product.php?id=<?= $p['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this product?');">
                                            <i class="fa-solid fa-trash me-1"></i> Delete
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
</body>
</html>