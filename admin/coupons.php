<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$message = '';
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
    } catch (Exception $e) {
        $message = "ত্রুটি: " . $e->getMessage();
    }
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <title>কুপন ম্যানেজমেন্ট - শুভ্রতা এডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.lang-bn-mode .lang-en { display: none !important; }
        body.lang-bn-mode .lang-bn { display: inline-block !important; }
        body.lang-en-mode .lang-bn { display: none !important; }
        body.lang-en-mode .lang-en { display: inline-block !important; }
    </style>
</head>
<body class="bg-light lang-bn-mode">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm px-4 mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-gem text-danger me-1"></i> <span class="lang-bn">শুভ্রতা অ্যাডমিন</span><span class="lang-en">Shuvrota Admin</span></a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="dropdown">
                <a class="btn btn-sm btn-secondary px-3 dropdown-toggle rounded-pill" href="#" role="button" data-bs-toggle="dropdown" id="currentLangText">বাংলা</a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><button type="button" class="dropdown-item small" onclick="switchLanguage('bn')">বাংলা (BN)</button></li>
                    <li><button type="button" class="dropdown-item small" onclick="switchLanguage('en')">English (EN)</button></li>
                </ul>
            </div>
            <a href="index.php" class="btn btn-sm btn-outline-light rounded-pill"><span class="lang-bn">ড্যাশবোর্ড</span><span class="lang-en">Dashboard</span></a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><span class="lang-bn">কুপন ম্যানেজমেন্ট</span><span class="lang-en">Coupon Management</span></h2>
    </div>

    <?php if($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-4 border-0 shadow-sm rounded-4">
                <h4 class="fw-bold mb-3"><span class="lang-bn">নতুন কুপন যোগ করুন</span><span class="lang-en">Add New Coupon</span></h4>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><span class="lang-bn">কুপন কোড</span><span class="lang-en">Coupon Code</span></label>
                        <input type="text" name="code" class="form-control" placeholder="যেমন: SHUVRO10" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><span class="lang-bn">ছাড়ের ধরন</span><span class="lang-en">Discount Type</span></label>
                        <select name="type" class="form-select" required>
                            <option value="percentage"><span class="lang-bn">শতাংশ (%)</span><span class="lang-en">Percentage (%)</span></option>
                            <option value="fixed"><span class="lang-bn">নির্দিষ্ট টাকা (৳)</span><span class="lang-en">Fixed Amount (৳)</span></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><span class="lang-bn">ছাড়ের পরিমাণ</span><span class="lang-en">Discount Value</span></label>
                        <input type="number" step="0.01" name="value" class="form-control" placeholder="১০ বা ১০০" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><span class="lang-bn">ন্যূনতম অর্ডারের পরিমাণ (৳)</span><span class="lang-en">Min Order Amount (৳)</span></label>
                        <input type="number" step="0.01" name="min_order_amount" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><span class="lang-bn">সর্বমোট ব্যবহার সীমা</span><span class="lang-en">Usage Limit</span></label>
                        <input type="number" name="usage_limit" class="form-control" placeholder="খালি রাখলে আনলিমিটেড">
                    </div>
                    <button type="submit" name="add_coupon" class="btn btn-danger w-100 fw-bold"><span class="lang-bn">কুপন সেভ করুন</span><span class="lang-en">Save Coupon</span></button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <h4 class="fw-bold mb-3"><span class="lang-bn">বিদ্যমান কুপন তালিকা</span><span class="lang-en">Existing Coupons List</span></h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th><span class="lang-bn">কোড</span><span class="lang-en">Code</span></th>
                                <th><span class="lang-bn">ছাড়</span><span class="lang-en">Discount</span></th>
                                <th><span class="lang-bn">মিন. অর্ডার</span><span class="lang-en">Min Order</span></th>
                                <th><span class="lang-bn">ব্যবহৃত</span><span class="lang-en">Used</span></th>
                                <th><span class="lang-bn">স্ট্যাটাস</span><span class="lang-en">Status</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($coupons as $c): ?>
                                <tr>
                                    <td class="fw-bold text-danger"><?= htmlspecialchars($c['code']) ?></td>
                                    <td><?= $c['type'] === 'percentage' ? $c['value'].'%' : '৳'.$c['value'] ?></td>
                                    <td>৳ <?= number_format($c['min_order_amount'], 2) ?></td>
                                    <td><?= $c['used_count'] ?> / <?= $c['usage_limit'] ?? '∞' ?></td>
                                    <td><span class="badge bg-<?= $c['is_active'] ? 'success' : 'danger' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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