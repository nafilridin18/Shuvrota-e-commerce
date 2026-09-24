<?php
/**
 * add_to_cart_ajax.php
 * AJAX endpoint — mirrors the cart-add logic from cart.php but returns JSON
 * instead of redirecting, so the storefront can stay on the same page.
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

csrf_require();

$product_id = (int)$_POST['product_id'];
$qty        = max(1, (int)($_POST['quantity'] ?? 1));
$size       = trim($_POST['size'] ?? 'Free Size');
$color      = trim($_POST['color'] ?? 'Standard');

try {
    $stmt = $pdo->prepare("
        SELECT p.*,
               (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) AS img
        FROM products p
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    $price = (!empty($product['discount_price']) && $product['discount_price'] > 0)
        ? $product['discount_price']
        : $product['price'];

    $cart_key = $product_id . '_' . md5($size . '_' . $color);

    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'id'    => $product_id,
            'title' => $product['name'],
            'price' => $price,
            'qty'   => $qty,
            'size'  => $size,
            'color' => $color,
            'image' => $product['img'] ?? '',
        ];
    }

    // Cart badge = total line items (matches existing header logic)
    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

    echo json_encode([
        'success'       => true,
        'message'       => 'Added to cart',
        'cart_count'    => $cart_count,
        'product_title' => $product['name'],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}