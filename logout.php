<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// সেশনের সব ডেটা মুছে ফেলা
$_SESSION = array();

// কুকিজ ডিলিট করা (যদি থাকে)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// সেশন ধ্বংস করা
session_destroy();

// সরাসরি লগইন পেজে রিডাইরেক্ট করা
header('Location: login.php');
exit;