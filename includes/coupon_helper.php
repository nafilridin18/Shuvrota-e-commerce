<?php
/**
 * includes/coupon_helper.php
 *
 * Single source of truth for "is this coupon valid for this subtotal,
 * and what discount does it give". Used by:
 *   - apply_coupon_ajax.php   (live preview while the customer is on checkout)
 *   - checkout.php            (no-JS fallback apply, AND the final
 *                              recalculation done right before an order
 *                              is actually saved)
 *
 * Re-deriving the discount from the DB at every one of these points —
 * instead of trusting a number saved earlier in the session — is what
 * closes the negative-total exploit (report finding C-5): the discount
 * can never be "stale" relative to whatever is actually in the cart
 * right now.
 */

function validate_and_calculate_coupon(PDO $pdo, string $code, float $subtotal, ?int $customerId = null): array
{
    $code = strtoupper(trim($code));

    if ($code === '') {
        return ['valid' => false, 'message' => 'কুপন কোড লিখুন। / Please enter a coupon code.'];
    }
    if ($subtotal <= 0) {
        return ['valid' => false, 'message' => 'কার্টে কোনো পণ্য নেই। / Your cart is empty.'];
    }

    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return ['valid' => false, 'message' => 'ভুল কুপন কোড! / Invalid coupon code.'];
    }

    if (!empty($coupon['starts_at']) && strtotime($coupon['starts_at']) > time()) {
        return ['valid' => false, 'message' => 'এই কুপন এখনো সক্রিয় হয়নি।'];
    }
    if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
        return ['valid' => false, 'message' => 'এই কুপনের মেয়াদ শেষ হয়ে গেছে।'];
    }

    if (!empty($coupon['usage_limit']) && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'এই কুপনটি ইতিমধ্যে ব্যবহৃত হয়েছে।'];
    }

    if ($customerId !== null && !empty($coupon['usage_limit_per_customer'])) {
        $usedStmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = ? AND customer_id = ?");
        $usedStmt->execute([$coupon['id'], $customerId]);
        if ((int)$usedStmt->fetchColumn() >= (int)$coupon['usage_limit_per_customer']) {
            return ['valid' => false, 'message' => 'আপনি এই কুপনটি ইতিমধ্যে ব্যবহার করেছেন।'];
        }
    }

    $minOrder = (float)($coupon['min_order_amount'] ?? 0);
    if ($subtotal < $minOrder) {
        return [
            'valid'   => false,
            'message' => 'এই কুপনের জন্য সর্বনিম্ন অর্ডার ৳ ' . number_format($minOrder, 2) . ' হতে হবে।',
        ];
    }

    $type  = $coupon['type'] ?? 'percentage';
    $value = (float)($coupon['value'] ?? 0);
    $discount = ($type === 'percentage') ? ($subtotal * $value / 100) : $value;

    if (!empty($coupon['max_discount_amount']) && $discount > (float)$coupon['max_discount_amount']) {
        $discount = (float)$coupon['max_discount_amount'];
    }
    // A coupon can never discount more than the subtotal itself — this is
    // the guard that keeps total_amount from ever going negative.
    if ($discount > $subtotal) {
        $discount = $subtotal;
    }
    $discount = round(max(0, $discount), 2);

    return [
        'valid'    => true,
        'coupon'   => $coupon,
        'discount' => $discount,
        'message'  => 'কুপন সফলভাবে প্রয়োগ হয়েছে! ৳' . number_format($discount, 2) . ' সঞ্চয় হয়েছে।',
    ];
}
