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

    echo "<h3>সফলভাবে 'site_banners' টেবিল তৈরি হয়ে গেছে!</h3>";
    echo "<a href='banner_settings.php'>ব্যানার সেটিংস পেজে যান</a>";
} catch (Exception $e) {
    echo "সরাসরি টেবিল তৈরি করতে সমস্যা হচ্ছে: " . $e->getMessage();
}
?>