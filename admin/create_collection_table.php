<?php
require_once '../config/database.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS home_collections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        category_id INT DEFAULT NULL,
        image_path VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "ডাইনামিক কালেকশন টেবিল সফলভাবে তৈরি হয়েছে! ✅ <a href='manage_collections.php'>ম্যানেজ পেজে যান</a>";
} catch (Exception $e) {
    echo "এরর: " . $e->getMessage();
}
?>