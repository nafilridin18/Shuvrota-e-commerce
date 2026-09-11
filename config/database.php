<?php
// config/database.php

// Auto-detect environment: your XAMPP box vs. the cPanel live server.
// This means you do NOT have to remember to hand-edit this file every
// time you push code from local to production, or vice versa.
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || str_ends_with($_SERVER['SERVER_NAME'] ?? '', '.local');

if ($isLocal) {
    $host = '127.0.0.1';
    $port = '3306'; // XAMPP Control Panel অনুযায়ী Port 3307 সেট করা হয়েছে
    $db   = 'shuvrota_db';
    $user = 'root';
    $pass = ''; // সাধারণত ফাঁকা থাকে, কাজ না করলে 'root' ট্রাই করো
} else {
    // ==== FILL THESE IN WITH THE VALUES FROM cPanel > MySQL Databases ====
    // cPanel prefixes both the DB name and DB user with your cPanel
    // username automatically, e.g. 'cpaneluser_shuvrota'.
    $host = 'localhost';   // cPanel MySQL is almost always 'localhost', not an IP
    $port = '3306';        // cPanel's default MySQL port
    $db   = 'CPANELUSER_shuvrota_db';
    $user = 'CPANELUSER_shuvrota';
    $pass = 'PUT_THE_REAL_DB_PASSWORD_HERE';
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Never show real DB error details to a site visitor — it can leak
    // hostnames, table structure, or credentials hints. Log it instead.
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Something went wrong on our end. Please try again shortly.');
}
