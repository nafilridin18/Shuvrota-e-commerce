<?php
session_start();
require_once '../config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    try {
        $pdo->beginTransaction();

        // ১. সার্ভারের uploads/ ফোল্ডার থেকে প্রজেক্টের সব ছবি মুছে ফেলা
        $img_stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $img_stmt->execute([$product_id]);
        $images = $img_stmt->fetchAll();

        foreach ($images as $img) {
            $file_path = __DIR__ . '/../uploads/' . $img['image_path'];
            if (!empty($img['image_path']) && file_exists($file_path)) {
                @unlink($file_path);
            }
        }

        // ২. ডাটাবেজ থেকে প্রোডাক্টের ইমেজ রেকর্ড মুছে ফেলা
        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$product_id]);

        // ৩. ডাটাবেজ থেকে প্রোডাক্টের ভ্যারিয়েন্ট রেকর্ড মুছে ফেলা
        $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product_id]);

        // ৪. মূল প্রোডাক্টটি মুছে ফেলা
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);

        $pdo->commit();
        $_SESSION['msg'] = "Product deleted successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['msg'] = "Error deleting product: " . $e->getMessage();
    }
}

header('Location: index.php');
exit;