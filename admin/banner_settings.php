<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_check.php';
require_once '../config/database.php';

$message = '';

// ব্যানার ও ক্যাটাগরি কালেকশন ইমেজ আপডেট লজিক
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_key = trim($_POST['section_key']);
    $title = trim($_POST['title'] ?? '');
    
    $stmt = $pdo->prepare("SELECT * FROM site_banners WHERE section_key = ?");
    $stmt->execute([$section_key]);
    $existing = $stmt->fetch();

    $media_path = $existing['media_path'] ?? '';
    $media_type = $existing['media_type'] ?? 'image';

    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/banners/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        $filename = $section_key . '_' . time() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['media_file']['tmp_name'], $upload_dir . $filename)) {
            $media_path = 'uploads/banners/' . $filename;
            $media_type = in_array($ext, ['mp4', 'webm', 'ogg']) ? 'video' : 'image';
        }
    }

    if ($existing) {
        $update = $pdo->prepare("UPDATE site_banners SET title = ?, media_path = ?, media_type = ? WHERE section_key = ?");
        $update->execute([$title, $media_path, $media_type, $section_key]);
    } else {
        $insert = $pdo->prepare("INSERT INTO site_banners (section_key, title, media_path, media_type) VALUES (?, ?, ?, ?)");
        $insert->execute([$section_key, $title, $media_path, $media_type]);
    }
    $message = "সফলভাবে আপডেট করা হয়েছে!";
}

$banners = [];
$res = $pdo->query("SELECT * FROM site_banners")->fetchAll();
foreach($res as $b) {
    $banners[$b['section_key']] = $b;
}

// ডাটাবেজ থেকে সকল ক্যাটাগরি ফেচ করা
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>ব্যানার ও কালেকশন ম্যানেজ - শুভ্রতা এডমিন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container my-5" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-image me-2"></i> ব্যানার ও কালেকশন ইমেজ ম্যানেজ</h2>
        <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> ড্যাশবোর্ড</a>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- হিরো ব্যানার ফর্ম -->
    <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
        <h4 class="fw-bold mb-3 text-danger">১. হোমপেজ হিরো ব্যানার/ভিডিও</h4>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="section_key" value="hero_banner">
            <div class="mb-3">
                <label class="form-label fw-bold small">ব্যানার শিরোনাম (Title)</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($banners['hero_banner']['title'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">ছবি বা ভিডিও আপলোড (Landscape)</label>
                <input type="file" name="media_file" class="form-control">
                <?php if(!empty($banners['hero_banner']['media_path'])): ?>
                    <small class="text-muted mt-1 d-block">বর্তমান ফাইল: <?= $banners['hero_banner']['media_path'] ?></small>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-dark w-100 fw-bold">হিরো ব্যানার আপডেট করুন</button>
        </form>
    </div>

    <h4 class="fw-bold mb-3 text-dark">২. ক্যাটাগরি কালেকশন কার্ডসমূহ</h4>
    
    <!-- ডাইনামিক ক্যাটাগরি লুপ -->
    <?php foreach($categories as $cat): 
        $s_key = 'cat_img_' . $cat['id'];
        $cat_name = $cat['name'];
    ?>
        <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
            <h5 class="fw-bold mb-3 text-secondary"><?= htmlspecialchars($cat_name) ?> কালেকশন</h5>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="section_key" value="<?= $s_key ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold small">ব্যানার/ছবির ল্যান্ডস্কেপ ফাইল</label>
                    <input type="file" name="media_file" class="form-control">
                    <?php if(!empty($banners[$s_key]['media_path'])): ?>
                        <div class="mt-2">
                            <img src="../<?= htmlspecialchars($banners[$s_key]['media_path']) ?>" width="120" class="rounded border">
                        </div>
                    <?php else: ?>
                        <small class="text-muted d-block mt-1">বর্তমানে ডিফল্ট ছবি ব্যবহার হচ্ছে। নতুন ছবি আপলোড করে পরিবর্তন করতে পারেন।</small>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-outline-danger w-100 fw-bold"><?= htmlspecialchars($cat_name) ?> ছবি আপডেট করুন</button>
            </form>
        </div>
    <?php endforeach; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>