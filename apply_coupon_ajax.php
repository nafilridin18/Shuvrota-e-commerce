<?php
/**
 * apply_coupon_ajax.php
 * AJAX endpoint for applying / removing a coupon during checkout.
 *
 * POST params:
 *   coupon_code   (string)   — required for action=apply
 *   is_direct     (0|1)      — which cart to read the subtotal from
 *   action        (apply|remove) — defaults to 'apply'
 *
 * JSON response (success):
 *   { success:true, code, discount, subtotal, message }
 * JSON response (failure):
 *   { success:false, message }
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? 'apply';

/* =====================================================================
   REMOVE
   ===================================================================== */
if ($action === 'remove') {
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true, 'removed' => true]);
    exit;
}

/* =====================================================================
   APPLY
   ===================================================================== */
$coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

if ($coupon_code === '') {
    echo json_encode(['success' => false, 'message' => 'কুপন কোড লিখুন। / Please enter a coupon code.']);
    exit;
}

/* ---- Recompute subtotal from the session — never trust the client ---- */
$useDirect = !empty($_POST['is_direct']) && !empty($_SESSION['direct_cart']);
$cart = $useDirect ? $_SESSION['direct_cart'] : ($_SESSION['cart'] ?? []);

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += (float)($item['price'] ?? 0) * (int)($item['qty'] ?? 1);
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'কার্টে কোনো পণ্য নেই। / Your cart is empty.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$coupon_code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'ভুল কুপন কোড! / Invalid coupon code.']);
        exit;
    }

    /* ---- Date window ---- */
    if (!empty($coupon['starts_at']) && strtotime($coupon['starts_at']) > time()) {
        echo json_encode(['success' => false, 'message' => 'এই কুপন এখনো সক্রিয় হয়নি।']);
        exit;
    }
    if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'এই কুপনের মেয়াদ শেষ হয়ে গেছে।']);
        exit;
    }

    /* ---- Usage limit ---- */
    if (!empty($coupon['usage_limit']) && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'এই কুপনটি ইতিমধ্যে ব্যবহৃত হয়েছে।']);
        exit;
    }

    /* ---- Minimum order ---- */
    $min_order = (float)($coupon['min_order_amount'] ?? 0);
    if ($subtotal < $min_order) {
        echo json_encode([
            'success' => false,
            'message' => 'এই কুপনের জন্য সর্বনিম্ন অর্ডার ৳ ' . number_format($min_order, 2) . ' হতে হবে।'
        ]);
        exit;
    }

    /* ---- Compute discount ---- */
    $type  = $coupon['type'] ?? 'percentage';
    $value = (float)($coupon['value'] ?? 0);

    $discount = ($type === 'percentage') ? ($subtotal * $value / 100) : $value;

    if (!empty($coupon['max_discount_amount']) && $discount > (float)$coupon['max_discount_amount']) {
        $discount = (float)$coupon['max_discount_amount'];
    }
    if ($discount > $subtotal) {
        $discount = $subtotal;
    }
    $discount = round($discount, 2);

    /* ---- Persist to session (order placement reads this) ---- */
    $_SESSION['applied_coupon'] = [
        'id'       => (int)$coupon['id'],
        'code'     => $coupon['code'],
        'discount' => $discount,
    ];

    echo json_encode([
        'success'  => true,
        'code'     => $coupon['code'],
        'discount' => $discount,
        'subtotal' => round($subtotal, 2),
        'message'  => 'কুপন সফলভাবে প্রয়োগ হয়েছে! ৳' . number_format($discount, 2) . ' সঞ্চয় হয়েছে।',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}