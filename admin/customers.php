<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// নিবন্ধিত কাস্টমারদের ডেটা ফেচ করা
$stmt = $pdo->query("SELECT * FROM customers WHERE is_guest = 0 ORDER BY id DESC");
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>রেজিস্টার্ড কাস্টমার তালিকা - শুভ্রতা এডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fa-solid fa-users me-2"></i>রেজিস্টার্ড কাস্টমার তালিকা</h2>
        <a href="index.php" class="btn btn-secondary">ড্যাশবোর্ড</a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">আইডি</th>
                            <th>নাম</th>
                            <th>মোবাইল নম্বর</th>
                            <th>ইমেইল</th>
                            <th>রেজিস্ট্রেশনের তারিখ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($customers)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">এখনো কোনো কাস্টমার অ্যাকাউন্ট খোলেনি।</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($customers as $c): ?>
                                <tr>
                                    <td class="ps-3 fw-bold">#<?= $c['id'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                                    <td><i class="fa-solid fa-phone me-1 text-muted"></i><?= htmlspecialchars($c['phone']) ?></td>
                                    <td><?= htmlspecialchars($c['email'] ?? 'N/A') ?></td>
                                    <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>