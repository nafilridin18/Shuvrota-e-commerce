<?php
/**
 * toggle_wishlist_ajax.php
 * AJAX endpoint — toggles a product in/out of the logged-in customer's
 * wishlist and returns JSON so the storefront can animate and stay put.
 *
 * Response shape:
 *   { success: true,  state: 'added'|'removed', wishlist_count: N }
 *   { success: false, requires_login: true }        ← front-end redirects
 *   { success: false, message: '…' }                ← generic error
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

/* ---- Method / input guards ---- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

csrf_require();

/* ---- Auth guard — the front-end redirects to login on this flag ---- */
if (empty($_SESSION['customer_id'])) {
    echo json_encode([
        'success'        => false,
        'requires_login' => true,
        'message'        => 'Please log in to use your wishlist',
    ]);
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];
$product_id  = (int)$_POST['product_id'];

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

try {
    // Make sure the product actually exists (guards against stale cache)
    $pStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $pStmt->execute([$product_id]);
    if (!$pStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Check current state
    $check = $pdo->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $check->execute([$customer_id, $product_id]);

    if ($check->fetch()) {
        // ---- Currently in wishlist → remove it ----
        $del = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
        $del->execute([$customer_id, $product_id]);
        $state = 'removed';
    } else {
        // ---- Not in wishlist → add it ----
        $ins = $pdo->prepare("INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
        $ins->execute([$customer_id, $product_id]);
        $state = 'added';
    }

    // Fresh total count for badge updates
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
    $cnt->execute([$customer_id]);
    $wishlist_count = (int)$cnt->fetchColumn();

    echo json_encode([
        'success'        => true,
        'state'          => $state,
        'wishlist_count' => $wishlist_count,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}