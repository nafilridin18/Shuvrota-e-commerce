<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
    $slug = strtolower(str_replace(' ', '-', $name)) . '-' . rand(10, 999);

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, parent_id, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$name, $slug, $parent_id]);
        $message = "ক্যাটাগরি সফলভাবে যুক্ত হয়েছে।";
    } catch (Exception $e) {
        $message = "ত্রুটি: " . $e->getMessage();
    }
}

// Fetch Categories for hierarchy
$stmt = $pdo->query("SELECT c1.*, c2.name as parent_name FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id ORDER BY parent_id, name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>ক্যাটাগরি ম্যানেজমেন্ট - শুভ্রতা এডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <h2>ক্যাটাগরি এবং সাব-ক্যাটাগরি</h2>
    <a href="index.php" class="btn btn-secondary mb-3">ড্যাশবোর্ডে ফিরুন</a>
    
    <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0">
                <form method="POST">
                    <h5>নতুন যোগ করুন</h5>
                    <div class="mb-3">
                        <label class="form-label">ক্যাটাগরির নাম</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">প্যারেন্ট ক্যাটাগরি (ঐচ্ছিক)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- কোনোটি নয় (মেইন ক্যাটাগরি) --</option>
                            <?php foreach($categories as $c): if(empty($c['parent_id'])): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                        <small class="text-muted">সাব-ক্যাটাগরি বানাতে চাইলে মেইন ক্যাটাগরি সিলেক্ট করুন।</small>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary w-100">যুক্ত করুন</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-3 shadow-sm border-0">
                <table class="table">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>নাম</th><th>প্যারেন্ট ক্যাটাগরি</th><th>স্ট্যাটাস</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $c): ?>
                            <tr>
                                <td><?= $c['id'] ?></td>
                                <td><?= htmlspecialchars($c['name']) ?></td>
                                <td><?= $c['parent_name'] ? '<span class="badge bg-secondary">'.htmlspecialchars($c['parent_name']).'</span>' : '-' ?></td>
                                <td><?= $c['is_active'] ? 'Active' : 'Inactive' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>