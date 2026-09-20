<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';

$stmt = $pdo->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS total_orders,
           (SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.customer_id = c.id AND o.status = 'delivered') AS total_spent
    FROM customers c
    WHERE c.is_guest = 0
    ORDER BY c.id DESC
");
$customers = $stmt->fetchAll();

$pageTitle     = 'কাস্টমার তালিকা — শুভ্রতা এডমিন';
$pageHeadingBn = 'রেজিস্টার্ড কাস্টমার';
$pageHeadingEn = 'Registered Customers';
$activePage    = 'customers';
include 'includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="stat-card primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small><span class="lang-bn">সর্বমোট কাস্টমার</span><span class="lang-en">Total Customers</span></small>
                    <h3><?= number_format(count($customers)) ?></h3>
                </div>
                <i class="fa-solid fa-users stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <span><i class="fa-solid fa-users me-2"></i>
            <span class="lang-bn">কাস্টমার তালিকা</span><span class="lang-en">Customer List</span>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No customers registered yet.</td></tr>
                <?php else: foreach ($customers as $c): ?>
                    <tr>
                        <td>
                            <span class="badge" style="background: rgba(123,17,19,0.1); color: var(--maroon-700); font-weight:700;">
                                #<?= $c['id'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="admin-user-avatar" style="background: linear-gradient(135deg, var(--maroon-700), var(--maroon-900));">
                                    <?= htmlspecialchars(strtoupper(substr($c['name'], 0, 1))) ?>
                                </span>
                                <span class="fw-bold"><?= htmlspecialchars($c['name']) ?></span>
                            </div>
                        </td>
                        <td><i class="fa-solid fa-phone me-1" style="color: var(--ink-500); font-size: 11px;"></i><?= htmlspecialchars($c['phone']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                        <td>
                            <span class="badge" style="background: rgba(212,175,55,0.15); color: #7b5c12;">
                                <?= (int)$c['total_orders'] ?>
                            </span>
                        </td>
                        <td class="fw-bold" style="color: var(--maroon-700);">
                            ৳<?= number_format($c['total_spent'], 0) ?>
                        </td>
                        <td>
                            <small class="text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></small>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>