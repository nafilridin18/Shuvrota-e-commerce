<?php
require_once '../config/database.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_key VARCHAR(50) NOT NULL UNIQUE,
        title VARCHAR(255) DEFAULT NULL,
        media_type VARCHAR(20) DEFAULT 'image',
        media_path VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    echo "<h2 style='color: green; text-align: center; margin-top: 50px;'>সফলভাবে 'site_banners' টেবিল তৈরি হয়ে গেছে! ✅</h2>";
    echo "<p style='text-align: center;'><a href='banner_settings.php'>ব্যানার সেটিংস পেজে ফিরে যান</a></p>";
} catch (Exception $e) {
    echo "<h2 style='color: red; text-align: center; margin-top: 50px;'>এরর: " . $e->getMessage() . "</h2>";
}
?>