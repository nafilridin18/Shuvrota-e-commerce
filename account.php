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
            $errorMessage = "আপডেট করতে সমস্যা হয়েছে: " . $e->getMessage();
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

$pageTitle = "আমার অ্যাকাউন্ট - Shuvrota";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Navigation -->
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
                        <i class="fa-solid fa-address-card me-2"></i> 
                        <span class="lang-bn">অ্যাকাউন্ট বিবরণী</span><span class="lang-en">Account Details</span>
                    </button>
                    <button class="nav-link text-start py-2 px-3 rounded-2" id="orders-tab" data-bs-toggle="pill" data-bs-target="#ordersContent" type="button" role="tab">
                        <i class="fa-solid fa-bag-shopping me-2"></i> 
                        <span class="lang-bn">আমার অর্ডারসমূহ</span><span class="lang-en">My Orders</span>
                    </button>
                    <a href="logout.php" class="nav-link text-start py-2 px-3 rounded-2 text-danger fw-semibold">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> 
                        <span class="lang-bn">লগআউট</span><span class="lang-en">Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
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
                <!-- Account Details Tab -->
                <div class="tab-pane fade show active" id="detailsContent" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-3 p-4">
                        <h4 class="fw-bold mb-4">
                            <i class="fa-solid fa-user-pen text-danger me-2"></i>
                            <span class="lang-bn">অ্যাকাউন্ট বিবরণী ও ঠিকানা</span><span class="lang-en">Account Details & Address</span>
                        </h4>
                        <form action="account.php" method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <span class="lang-bn">পূর্ণ নাম</span><span class="lang-en">Full Name</span>
                                    </label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <span class="lang-bn">ফোন নম্বর</span><span class="lang-en">Phone Number</span>
                                    </label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">
                                        <span class="lang-bn">ইমেইল ঠিকানা</span><span class="lang-en">Email Address</span>
                                    </label>
                                    <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($customer['email'] ?? 'Not Provided') ?>" disabled>
                                    <small class="text-muted">
                                        <span class="lang-bn">ইমেইল পরিবর্তন করা যায় না।</span><span class="lang-en">Email cannot be changed.</span>
                                    </small>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">
                                        <span class="lang-bn">পূর্ণ ঠিকানা</span><span class="lang-en">Delivery Address</span>
                                    </label>
                                    <textarea name="address" class="form-control" rows="2" placeholder="বাসা/হোল্ডিং নম্বর, রোড, এলাকা..."><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <span class="lang-bn">শহর / জেলা</span><span class="lang-en">City / District</span>
                                    </label>
                                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($customer['city'] ?? '') ?>" placeholder="যেমন: ঢাকা">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <span class="lang-bn">পোস্টাল কোড</span><span class="lang-en">Postal Code</span>
                                    </label>
                                    <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($customer['postal_code'] ?? '') ?>" placeholder="যেমন: ১২০০">
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-dark px-4 py-2">
                                        <i class="fa-solid fa-floppy-disk me-2"></i>
                                        <span class="lang-bn">পরিবর্তন সংরক্ষণ করুন</span><span class="lang-en">Save Changes</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- My Orders Tab -->
                <div class="tab-pane fade" id="ordersContent" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-3 p-4">
                        <h4 class="fw-bold mb-4">
                            <i class="fa-solid fa-box-open text-danger me-2"></i>
                            <span class="lang-bn">আমার অর্ডারসমূহ ও স্ট্যাটাস</span><span class="lang-en">My Orders & Status</span>
                        </h4>
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fa-solid fa-basket-shopping fs-1 text-muted mb-3"></i>
                                <p class="text-muted">
                                    <span class="lang-bn">আপনি এখনো কোনো অর্ডার করেননি।</span><span class="lang-en">You haven't placed any orders yet.</span>
                                </p>
                                <a href="index.php?show_products=1" class="btn btn-outline-dark btn-sm">
                                    <span class="lang-bn">কেনাকাটা শুরু করুন</span><span class="lang-en">Start Shopping</span>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th><span class="lang-bn">অর্ডার আইডি</span><span class="lang-en">Order ID</span></th>
                                            <th><span class="lang-bn">তারিখ</span><span class="lang-en">Date</span></th>
                                            <th><span class="lang-bn">মোট মূল্য</span><span class="lang-en">Total Amount</span></th>
                                            <th><span class="lang-bn">পেমেন্ট</span><span class="lang-en">Payment</span></th>
                                            <th><span class="lang-bn">স্ট্যাটাস</span><span class="lang-en">Status</span></th>
                                            <th><span class="lang-bn">কার্যক্রম</span><span class="lang-en">Action</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $ord): ?>
                                            <tr>
                                                <td class="fw-bold">#<?= htmlspecialchars($ord['order_number']) ?></td>
                                                <td class="small text-muted"><?= date('d M Y, h:i A', strtotime($ord['placed_at'])) ?></td>
                                                <td class="fw-semibold">৳<?= number_format($ord['total_amount'], 2) ?></td>
                                                <td>
                                                    <?php if ($ord['payment_status'] === 'paid'): ?>
                                                        <span class="badge bg-success"><span class="lang-bn">পরিশোধিত</span><span class="lang-en">Paid</span></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark"><span class="lang-bn">অপরিশোধিত</span><span class="lang-en">Pending</span></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $statusClass = 'bg-secondary';
                                                        $statusBn = $ord['status'];
                                                        $statusEn = ucfirst($ord['status']);
                                                        switch($ord['status']) {
                                                            case 'new': 
                                                                $statusClass = 'bg-info text-dark'; 
                                                                $statusBn = 'নতুন'; $statusEn = 'New'; break;
                                                            case 'processing': 
                                                                $statusClass = 'bg-primary'; 
                                                                $statusBn = 'প্রক্রিয়াধীন'; $statusEn = 'Processing'; break;
                                                            case 'shipped': 
                                                                $statusClass = 'bg-warning text-dark'; 
                                                                $statusBn = 'শিপ করা হয়েছে'; $statusEn = 'Shipped'; break;
                                                            case 'delivered': 
                                                                $statusClass = 'bg-success'; 
                                                                $statusBn = 'ডেলিভারি সম্পন্ন'; $statusEn = 'Delivered'; break;
                                                            case 'cancelled': 
                                                                $statusClass = 'bg-danger'; 
                                                                $statusBn = 'বাতিল'; $statusEn = 'Cancelled'; break;
                                                        }
                                                    ?>
                                                    <span class="badge <?= $statusClass ?>">
                                                        <span class="lang-bn"><?= $statusBn ?></span>
                                                        <span class="lang-en"><?= $statusEn ?></span>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="track.php?order_number=<?= htmlspecialchars($ord['order_number']) ?>" class="btn btn-sm btn-outline-dark" title="Track">
                                                        <i class="fa-solid fa-truck-fast"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>