<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shubhrata - Weaving stories, creating opportunities</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="lang-bn-mode">

<nav class="navbar navbar-expand-lg navbar-dark bg-custom-dark sticky-top shadow-sm">
  <div class="container">
    <a class="brand-logo" href="index.php">
        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="45" stroke="#d4af37" stroke-width="4"/>
            <path d="M30 65 C 40 40, 60 40, 70 65" stroke="#ffffff" stroke-width="5" stroke-linecap="round"/>
            <circle cx="50" cy="35" r="8" fill="#d4af37"/>
        </svg>
        <span>Shubhrata</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav me-auto ms-3">
            <li class="nav-item"><a class="nav-link" href="index.php"><span class="lang-bn">হোম</span><span class="lang-en">Home</span></a></li>
            <li class="nav-item"><a class="nav-link" href="about.php"><span class="lang-bn">আমাদের কথা</span><span class="lang-en">About Us</span></a></li>
        </ul>

        <div class="d-flex gap-2 align-items-center mt-2 mt-lg-0">
            <button id="langToggleBtn" type="button" class="btn btn-sm btn-outline-light rounded-pill px-3">English</button>
            <a href="track.php" class="btn btn-outline-warning rounded-pill btn-sm">
                <i class="fa-solid fa-truck me-1"></i>
                <span class="lang-bn">ট্র্যাকিং</span><span class="lang-en">Tracking</span>
            </a>
            <a href="cart.php" class="btn btn-warning rounded-pill position-relative fw-bold btn-sm ms-1">
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                </span>
            </a>
        </div>
    </div>
  </div>
</nav>
<main>