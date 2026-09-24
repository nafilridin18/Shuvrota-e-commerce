<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/upload_validator.php';

$message = '';

// Handle uploads (logo + banners)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $section_key = trim($_POST['section_key'] ?? '');
    $title       = trim($_POST['title'] ?? '');

    // ---- SITE LOGO ----
    if ($section_key === 'site_logo') {
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $check = validate_uploaded_file($_FILES['media_file'], UPLOAD_IMAGE_TYPES);

            if ($check['valid']) {
                $upload_dir = __DIR__ . '/../uploads/logo/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

                $filename = 'logo_' . time() . '.' . $check['ext'];
                if (move_uploaded_file($_FILES['media_file']['tmp_name'], $upload_dir . $filename)) {
                    // L-1: delete the previous logo file so old uploads don't pile up forever
                    $oldLogo = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='site_logo'")->fetchColumn();
                    if (!empty($oldLogo)) {
                        $oldPath = __DIR__ . '/../' . $oldLogo;
                        if (is_file($oldPath) && !@unlink($oldPath)) {
                            error_log("Could not delete old logo file: {$oldPath}");
                        }
                    }

                    $logo_path = 'uploads/logo/' . $filename;
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_logo', ?)
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                        ->execute([$logo_path]);
                    $message = "লোগো সফলভাবে আপডেট হয়েছে!";
                }
            } else {
                $message = $check['error'];
            }
        }
    }
    // ---- HERO / CATEGORY BANNERS ----
    else if (!empty($section_key)) {
        $stmt = $pdo->prepare("SELECT * FROM site_banners WHERE section_key = ?");
        $stmt->execute([$section_key]);
        $existing = $stmt->fetch();

        $media_path = $existing['media_path'] ?? '';
        $media_type = $existing['media_type'] ?? 'image';

        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            // Previously this section had NO extension or content check at
            // all — any file type could be uploaded here.
            $allowedTypes = UPLOAD_IMAGE_TYPES + UPLOAD_VIDEO_TYPES;
            $check = validate_uploaded_file($_FILES['media_file'], $allowedTypes, 25 * 1024 * 1024);

            if ($check['valid']) {
                $upload_dir = __DIR__ . '/../uploads/banners/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

                $filename = $section_key . '_' . time() . '.' . $check['ext'];

                if (move_uploaded_file($_FILES['media_file']['tmp_name'], $upload_dir . $filename)) {
                    // L-1: clean up the previous banner file for this section
                    if (!empty($existing['media_path'])) {
                        $oldPath = __DIR__ . '/../' . $existing['media_path'];
                        if (is_file($oldPath) && !@unlink($oldPath)) {
                            error_log("Could not delete old banner file: {$oldPath}");
                        }
                    }

                    $media_path = 'uploads/banners/' . $filename;
                    $media_type = isset(UPLOAD_VIDEO_TYPES[$check['ext']]) ? 'video' : 'image';
                }
            } else {
                $message = $check['error'];
            }
        }

        if ($existing) {
            $pdo->prepare("UPDATE site_banners SET title = ?, media_path = ?, media_type = ? WHERE section_key = ?")
                ->execute([$title, $media_path, $media_type, $section_key]);
        } else {
            $pdo->prepare("INSERT INTO site_banners (section_key, title, media_path, media_type) VALUES (?, ?, ?, ?)")
                ->execute([$section_key, $title, $media_path, $media_type]);
        }
        if (!$message) {
            $message = "সফলভাবে আপডেট করা হয়েছে!";
        }
    }
}

// Load settings
$__settings_raw = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
$__settings = [];
foreach ($__settings_raw as $__s) $__settings[$__s['setting_key']] = $__s['setting_value'];
$current_logo = $__settings['site_logo'] ?? '';

$banners = [];
$res = $pdo->query("SELECT * FROM site_banners")->fetchAll();
foreach ($res as $b) $banners[$b['section_key']] = $b;

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

$pageTitle     = 'ব্যানার ও লোগো — শুভ্রতা এডমিন';
$pageHeadingBn = 'ব্যানার, লোগো ও কালেকশন';
$pageHeadingEn = 'Banners, Logo & Collections';
$activePage    = 'banners';
include 'includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- ============ SITE LOGO ============ -->
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-circle-nodes me-2"></i>
                    <span class="lang-bn">সাইট লোগো</span><span class="lang-en">Site Logo</span>
                </span>
                <span class="badge" style="background: var(--gold-500); color:#241b17;">PNG / SVG / WEBP</span>
            </div>
            <div class="admin-card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-md-3 text-center">
                        <div style="width:130px; height:130px; margin:0 auto; border-radius:50%;
                                    border:3px solid var(--gold-400);
                                    background: linear-gradient(135deg,#7b1113,#4a0c0e);
                                    display:flex; align-items:center; justify-content:center;
                                    overflow:hidden;
                                    box-shadow:0 10px 30px rgba(123,17,19,0.3);">
                            <?php if (!empty($current_logo)): ?>
                                <img src="../<?= htmlspecialchars($current_logo) ?>" style="width:100%; height:100%; object-fit:cover;" alt="Logo">
                            <?php else: ?>
                                <i class="fa-solid fa-gem" style="color:var(--gold-400); font-size:2.4rem;"></i>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mt-2 mb-0">Live Preview</p>
                    </div>
                    <div class="col-md-9">
                        <form method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="section_key" value="site_logo">
                            <label class="form-label">Upload new logo (square recommended, min 200×200 px)</label>
                            <input type="file" name="media_file" class="form-control" accept="image/*" required>
                            <p class="text-muted small mt-2">
                                এই লোগোটি ওয়েবসাইটের navbar, মোবাইল মেনু ও অ্যাডমিন প্যানেলে দেখানো হবে। একটি বৃত্তাকার ফ্রেমের ভেতরে রেন্ডার হবে।
                            </p>
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fa-solid fa-upload"></i>
                                <span class="lang-bn">লোগো আপলোড করুন</span><span class="lang-en">Upload Logo</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ HERO BANNER ============ -->
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-image me-2"></i>
                    <span class="lang-bn">হোমপেজ হিরো ব্যানার / ভিডিও</span><span class="lang-en">Homepage Hero Banner / Video</span>
                </span>
            </div>
            <div class="admin-card-body">
                <form method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                    <input type="hidden" name="section_key" value="hero_banner">
                    <div class="mb-3">
                        <label class="form-label">Banner Title (optional)</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($banners['hero_banner']['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Image / Video (landscape recommended)</label>
                        <input type="file" name="media_file" class="form-control" accept="image/*,video/mp4,video/webm">
                        <?php if (!empty($banners['hero_banner']['media_path'])): ?>
                            <small class="text-muted mt-1 d-block">Current: <?= htmlspecialchars($banners['hero_banner']['media_path']) ?></small>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <i class="fa-solid fa-save"></i>
                        <span class="lang-bn">হিরো আপডেট করুন</span><span class="lang-en">Update Hero</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ CATEGORY COLLECTIONS ============ -->
    <div class="col-12">
        <h4 class="fw-bold mb-3" style="font-family: var(--font-display);">
            <i class="fa-solid fa-layer-group me-2" style="color: var(--maroon-700);"></i>
            <span class="lang-bn">ক্যাটাগরি কালেকশন ইমেজ</span><span class="lang-en">Category Collection Images</span>
        </h4>
        <div class="row g-4">
            <?php foreach ($categories as $cat):
                $s_key = 'cat_img_' . $cat['id'];
            ?>
                <div class="col-md-6 col-xl-4">
                    <div class="admin-card h-100">
                        <div class="admin-card-header" style="background: linear-gradient(135deg, #7b1113, #4a0c0e);">
                            <span><i class="fa-solid fa-tag me-2"></i><?= htmlspecialchars($cat['name']) ?></span>
                        </div>
                        <div class="admin-card-body">
                            <form method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                                <input type="hidden" name="section_key" value="<?= $s_key ?>">
                                <div class="mb-3">
                                    <input type="file" name="media_file" class="form-control" accept="image/*">
                                    <?php if (!empty($banners[$s_key]['media_path'])): ?>
                                        <img src="../<?= htmlspecialchars($banners[$s_key]['media_path']) ?>" class="mt-2 rounded" style="width:100%; max-height:110px; object-fit:cover;">
                                    <?php else: ?>
                                        <small class="text-muted d-block mt-2">Default image in use.</small>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="admin-btn admin-btn-outline admin-btn-sm w-100">
                                    <i class="fa-solid fa-upload"></i> Update
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>