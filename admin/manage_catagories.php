<?php
require_once __DIR__ . '/../config/session.php';
require_once 'auth_check.php';
require_once '../config/database.php';

$message = '';
$messageType = 'info';

/* -------- ADD CATEGORY -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . rand(10, 999);

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, parent_id, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$name, $slug, $parent_id]);
        $message = "ক্যাটাগরি সফলভাবে যুক্ত হয়েছে।";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "ত্রুটি: " . $e->getMessage();
        $messageType = 'danger';
    }
}

/* -------- UPDATE CATEGORY -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $cid       = (int)$_POST['category_id'];
    $name      = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;

    if ($cid > 0 && $name !== '') {
        // Prevent a category from being its own parent
        if ($parent_id === $cid) $parent_id = NULL;

        // Regenerate slug from new name
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . $cid;

        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, parent_id = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $parent_id, $cid]);
            $message = "ক্যাটাগরি সফলভাবে আপডেট হয়েছে।";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "ত্রুটি: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

/* -------- TOGGLE ACTIVE -------- */
if (isset($_GET['toggle'])) {
    $cid = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE categories SET is_active = 1 - is_active WHERE id = ?")->execute([$cid]);
    header('Location: manage_catagories.php');
    exit;
}

/* -------- DELETE -------- */
if (isset($_GET['delete'])) {
    $cid = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$cid]);
        $_SESSION['msg'] = "ক্যাটাগরি মুছে ফেলা হয়েছে।";
    } catch (Exception $e) {
        $_SESSION['msg'] = "ক্যাটাগরি মুছতে সমস্যা: " . $e->getMessage();
    }
    header('Location: manage_catagories.php');
    exit;
}

/* -------- FETCH -------- */
$stmt = $pdo->query("SELECT c1.*, c2.name as parent_name,
                     (SELECT COUNT(*) FROM products WHERE category_id = c1.id) as product_count
                     FROM categories c1
                     LEFT JOIN categories c2 ON c1.parent_id = c2.id
                     ORDER BY c1.parent_id, c1.name");
$categories = $stmt->fetchAll();

$parents = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name")->fetchAll();

$pageTitle     = 'ক্যাটাগরি ম্যানেজমেন্ট — শুভ্রতা এডমিন';
$pageHeadingBn = 'ক্যাটাগরি ও সাব-ক্যাটাগরি';
$pageHeadingEn = 'Categories & Sub-categories';
$activePage    = 'categories';
include 'includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- ============ ADD CATEGORY ============ -->
    <div class="col-12 col-xl-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-folder-plus me-2"></i>
                    <span class="lang-bn">নতুন ক্যাটাগরি</span><span class="lang-en">New Category</span>
                </span>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Category (Optional)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">— None (Main Category) —</option>
                            <?php foreach ($parents as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select a main category to create a sub-category.</small>
                    </div>
                    <button type="submit" name="add_category" class="admin-btn admin-btn-primary w-100 justify-content-center" style="padding: 11px;">
                        <i class="fa-solid fa-plus"></i>
                        <span class="lang-bn">যুক্ত করুন</span><span class="lang-en">Add Category</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ CATEGORY LIST ============ -->
    <div class="col-12 col-xl-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fa-solid fa-layer-group me-2"></i>
                    <span class="lang-bn">সকল ক্যাটাগরি</span><span class="lang-en">All Categories</span>
                </span>
                <span class="badge" style="background: var(--gold-500); color:#241b17;"><?= count($categories) ?> Total</span>
            </div>
            <div class="table-responsive">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Parent</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No categories yet.</td></tr>
                        <?php else: foreach ($categories as $c): ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background: rgba(123,17,19,0.1); color: var(--maroon-700); font-weight:700;">
                                        #<?= $c['id'] ?>
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    <span class="cat-name-display" data-id="<?= $c['id'] ?>">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($c['parent_name']): ?>
                                        <span class="badge" style="background: rgba(212,175,55,0.18); color: #7b5c12;">
                                            <?= htmlspecialchars($c['parent_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">— Main —</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge" style="background: rgba(46,125,50,0.15); color:#1b5e20;">
                                        <?= (int)$c['product_count'] ?> items
                                    </span>
                                </td>
                                <td>
                                    <a href="?toggle=<?= $c['id'] ?>" class="text-decoration-none">
                                        <span class="badge" style="background: <?= $c['is_active'] ? 'rgba(46,125,50,0.15)' : 'rgba(198,40,40,0.15)' ?>; color: <?= $c['is_active'] ? '#1b5e20' : '#8e0000' ?>;">
                                            <i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i>
                                            <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </a>
                                </td>
                                <td style="text-align:center; white-space:nowrap;">
                                    <button type="button"
                                            class="admin-btn admin-btn-outline admin-btn-sm"
                                            onclick="openEditModal(
                                                <?= $c['id'] ?>,
                                                '<?= htmlspecialchars(addslashes($c['name'])) ?>',
                                                <?= (int)($c['parent_id'] ?? 0) ?>
                                            )"
                                            title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <a href="?delete=<?= $c['id'] ?>"
                                       class="admin-btn admin-btn-sm"
                                       style="background:#fee; color:#c62828; border:1.5px solid #c62828;"
                                       onclick="return confirm('Delete this category? Products in this category will lose their category link.');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ============ EDIT CATEGORY MODAL ============ -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 18px; border: none; overflow: hidden; box-shadow: 0 30px 80px rgba(0,0,0,0.3);">
            <form method="POST">
                <input type="hidden" name="update_category" value="1">
                <input type="hidden" name="category_id" id="editCatId">

                <div class="admin-card-header" style="background: linear-gradient(135deg, #7b1113, #4a0c0e);">
                    <span><i class="fa-solid fa-pen-to-square me-2"></i>
                        <span class="lang-bn">ক্যাটাগরি এডিট করুন</span><span class="lang-en">Edit Category</span>
                    </span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="admin-card-body" style="padding: 22px;">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" id="editCatName" class="form-control" required>
                        <small class="text-muted">E.g. change "Saree" → "Sharee". Slug is regenerated automatically.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Category (Optional)</label>
                        <select name="parent_id" id="editCatParent" class="form-select">
                            <option value="">— None (Main Category) —</option>
                            <?php foreach ($parents as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end p-3" style="background: rgba(244,236,223,0.5); border-top: 1px solid var(--border-soft);">
                    <button type="button" class="admin-btn admin-btn-outline" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <i class="fa-solid fa-save"></i>
                        <span class="lang-bn">সেভ করুন</span><span class="lang-en">Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(id, name, parentId) {
    document.getElementById('editCatId').value = id;
    document.getElementById('editCatName').value = name;
    document.getElementById('editCatParent').value = parentId || '';

    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>