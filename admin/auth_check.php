<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// অ্যাডমিন লগইন করা আছে কিনা পরীক্ষা করা
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>