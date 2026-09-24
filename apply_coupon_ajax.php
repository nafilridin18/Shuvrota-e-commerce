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
 *
 * IMPORTANT: this only stores the coupon CODE in the session, not a
 * pre-computed discount amount. checkout.php re-derives the discount
 * from this code (via includes/coupon_helper.php) against whatever the
 * cart actually contains at the moment the order is placed — never
 * trusting a number computed earlier. See C-5 in the security report.
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/coupon_helper.php';
require_once __DIR__ . '/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

csrf_require();

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

/* ---- Recompute subtotal from the session — never trust the client ---- */
$useDirect = !empty($_POST['is_direct']) && !empty($_SESSION['direct_cart']);
$cart = $useDirect ? $_SESSION['direct_cart'] : ($_SESSION['cart'] ?? []);

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += (float)($item['price'] ?? 0) * (int)($item['qty'] ?? 1);
}

try {
    $result = validate_and_calculate_coupon($pdo, $coupon_code, $subtotal, $_SESSION['customer_id'] ?? null);

    if (!$result['valid']) {
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }

    // Only the CODE is persisted — the discount is always recalculated
    // fresh, both here and again at order-placement time.
    $_SESSION['applied_coupon'] = [
        'code' => $result['coupon']['code'],
    ];

    echo json_encode([
        'success'  => true,
        'code'     => $result['coupon']['code'],
        'discount' => $result['discount'],
        'subtotal' => round($subtotal, 2),
        'message'  => $result['message'],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('apply_coupon_ajax error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
