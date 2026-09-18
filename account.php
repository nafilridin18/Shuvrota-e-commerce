<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

// Redirect to login if not logged in
if (empty($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customerId = $_SESSION['customer_id'];
$successMessage = '';
$errorMessage = '';

// Handle Complaint / Comment Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $order_id = (int)$_POST['order_id'];
    $subject = trim($_POST['subject']);
    $desc = trim($_POST['description']);
    if(!empty($order_id) && !empty($subject)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO complaints (customer_id, order_id, subject, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$customerId, $order_id, $subject, $desc]);
            $successMessage = "আপনার মন্তব্য বা অভিযোগটি সফলভাবে পাঠানো হয়েছে।";
        } catch (Exception $e) {
            $errorMessage = "সমস্যা হয়েছে: " . $e->getMessage();
        }
    }
}

// Fetch current customer details
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
} catch (Exception $e) {
    $customer = [];
}

// Handle Account Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');

    if (empty($name) || empty($phone)) {
        $errorMessage = "নাম এবং ফোন নম্বর আবশ্যক। / Name and phone number are required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, address = ?, city = ?, postal_code = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $city, $postalCode, $customerId]);
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_phone'] = $phone;
            $successMessage = "অ্যাকাউন্ট তথ্য সফলভাবে আপডেট করা হয়েছে! / Account details updated successfully!";
            
            // Refresh customer data
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch();
        } catch (Exception $e) {
            $errorMessage = "আপডেট করতে সমস্যা হয়েছে: " . $e->getMessage();
        }
    }
}

// Fetch customer orders (matching by customer_id OR customer phone number)
try {
    $custPhone = $customer['phone'] ?? $_SESSION['customer_phone'] ?? '';
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? OR (guest_phone = ? AND guest_phone != '') OR (shipping_phone = ? AND shipping_phone != '') ORDER BY placed_at DESC");
    $orderStmt->execute([$customerId, $custPhone, $custPhone]);
    $orders = $orderStmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
}

// Fetch Complaints
try {
    $compStmt = $pdo->prepare("SELECT c.*, o.order_number FROM complaints c JOIN orders o ON c.order_id = o.id WHERE c.customer_id = ? ORDER BY c.created_at DESC");
    $compStmt->execute([$customerId]);
    $complaints = $compStmt->fetchAll();
} catch (Exception $e) {
    $complaints = [];
}

$pageTitle = "আমার অ্যাকাউন্ট - Shuvrota";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-size: 20px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($customer['name'] ?? 'User') ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($customer['phone'] ?? '') ?></small>
                    </div>
                </div>
                <div class="nav flex-column nav-pills gap-2" id="accountTab" role="tablist">
                    <button class="nav-link active text-start py-2 px-3 rounded-2" id="details-tab" data-bs-toggle="pill" data-bs-target="#detailsContent" type="button" role="tab">
                        <i class="fa-solid fa-address-card me-2"></i> Account Details
                    </button>
                    <button class="nav-link text-start py-2 px-3 rounded-2" id="orders-tab" data-bs-toggle="pill" data-bs-target="#ordersContent" type="button" role="tab">
                        <i class="fa-solid fa-bag-shopping me-2"></i> My Orders
                    </button>
                    <button class="nav-link text-start py-2 px-3 rounded-2" id="complaints-tab" data-bs-toggle="pill" data-bs-target="#complaintsContent" type="button" role="tab">
                        <i class="fa-solid fa-headset me-2"></i> Support / Comments
                    </button>
                    <a href="logout.php" class="nav-link text-start py-2 px-3 rounded-2 text-danger fw-semibold">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="tab-content" id="accountTabContent">
                <div class="tab-pane fade show active" id="detailsContent" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-3 p-4">
                        <h4 class="fw-bold mb-4"><i class="fa-solid fa-user-pen text-danger me-2"></i> Account Details & Address</h4>
                        <form action="account.php" method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Full Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Email Address</label>
                                    <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($customer['email'] ?? 'Not Provided') ?>" disabled>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Delivery Address</label>
                                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">City</label>
                                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-dark px-4 py-2"><i class="fa-solid fa-floppy-disk me-2"></i> Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="ordersContent" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-3 p-4">
                        <h4 class="fw-bold mb-4"><i class="fa-solid fa-box-open text-danger me-2"></i> My Orders</h4>
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fa-solid fa-basket-shopping fs-1 text-muted mb-3"></i>
                                <p class="text-muted">You haven't placed any orders yet.</p>
                                <a href="index.php" class="btn btn-outline-dark btn-sm">Start Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order ID</th><th>Date</th><th>Total Amount</th><th>Status</th><th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $ord): ?>
                                            <tr>
                                                <td class="fw-bold">#<?= htmlspecialchars($ord['order_number']) ?></td>
                                                <td class="small text-muted"><?= date('d M Y, h:i A', strtotime($ord['placed_at'])) ?></td>
                                                <td class="fw-semibold">৳<?= number_format($ord['total_amount'], 2) ?></td>
                                                <td><span class="badge bg-secondary"><?= ucfirst($ord['status']) ?></span></td>
                                                <td>
                                                    <a href="track.php?order_number=<?= htmlspecialchars($ord['order_number']) ?>" class="btn btn-sm btn-outline-dark" title="Track"><i class="fa-solid fa-truck-fast"></i></a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#complaintModal<?= $ord['id'] ?>" title="Add Comment / Issue"><i class="fa-solid fa-comment-dots"></i></button>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="complaintModal<?= $ord['id'] ?>" tabindex="-1">
                                             <div class="modal-dialog">
                                                <div class="modal-content">
                                                  <form method="POST">
                                                     <div class="modal-header">
                                                        <h5 class="modal-title">Order Comment/Issue #<?= htmlspecialchars($ord['order_number']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                     </div>
                                                     <div class="modal-body">
                                                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Subject / Title</label>
                                                            <input type="text" name="subject" class="form-control" required placeholder="যেমন: সাইজ বা ডেলিভারি নিয়ে মন্তব্য">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Details / Comment</label>
                                                            <textarea name="description" class="form-control" rows="4" required placeholder="আপনার মন্তব্য লিখুন..."></textarea>
                                                        </div>
                                                     </div>
                                                     <div class="modal-footer">
                                                        <button type="submit" name="submit_complaint" class="btn btn-danger">Submit</button>
                                                     </div>
                                                  </form>
                                                </div>
                                             </div>
                                            </div>

                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="complaintsContent" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-3 p-4">
                        <h4 class="fw-bold mb-4"><i class="fa-solid fa-headset text-danger me-2"></i> My Comments / Issues</h4>
                        <?php if (empty($complaints)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted">You have no comments or complaints yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($complaints as $comp): ?>
                                    <div class="list-group-item list-group-item-action flex-column align-items-start p-3 mb-2 border rounded">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($comp['subject']) ?> <small class="text-muted">(Order #<?= $comp['order_number'] ?>)</small></h6>
                                            <span class="badge <?= $comp['status'] == 'resolved' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= ucfirst($comp['status']) ?></span>
                                        </div>
                                        <p class="mb-1 small text-secondary"><?= nl2br(htmlspecialchars($comp['description'])) ?></p>
                                        <?php if(!empty($comp['admin_reply'])): ?>
                                            <div class="mt-2 p-2 bg-light rounded border-start border-4 border-danger">
                                                <strong class="small text-danger">Admin Reply:</strong><br>
                                                <small><?= nl2br(htmlspecialchars($comp['admin_reply'])) ?></small>
                                            </div>
                                        <?php endif; ?>
                                        <small class="text-muted mt-2 d-block">Submitted: <?= date('d M Y, h:i A', strtotime($comp['created_at'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
