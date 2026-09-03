<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// যদি সেশনে আইডি থাকে কিন্তু নাম বা ফোন না থাকে, তবে ডাটাবেজ থেকে নিয়ে সেশনে সেট করে দেব
if (isset($_SESSION['customer_id']) && empty($_SESSION['customer_name'])) {
    try {
        $stmt = $pdo->prepare("SELECT name, phone FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $custData = $stmt->fetch();
        if ($custData) {
            $_SESSION['customer_name']  = $custData['name'];
            $_SESSION['customer_phone'] = $custData['phone'];
        }
    } catch (Exception $e) {
        // Ignore
    }
}
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'শুভ্রতা - Shuvrota' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .lang-en { display: none; }

        /* মডার্ন ডায়নামিক বাটন ও হোভার ইফেক্ট */
        .btn-modern {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .btn-modern:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .btn-dynamic-danger {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            border: none;
            color: #fff;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-dynamic-danger:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.5);
            color: #fff;
        }

        .navbar-brand {
            letter-spacing: 0.5px;
            transition: 0.3s;
        }
        .navbar-brand:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-danger fs-4" href="index.php">
            <i class="fa-solid fa-gem me-1 text-danger"></i><span class="lang-bn">শুভ্রতা</span><span class="lang-en">Shuvrota</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3" href="index.php">
                        <span class="lang-bn">হোম</span><span class="lang-en">Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3" href="track.php">
                        <span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Order Tracking</span>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item">
                    <a class="btn btn-outline-dark btn-sm px-3 rounded-pill btn-modern fw-semibold" href="cart.php">
                        <i class="fa-solid fa-cart-shopping me-1"></i> <span class="lang-bn">কার্ট</span><span class="lang-en">Cart</span>
                    </a>
                </li>

                <!-- ইউজার লগইন করা থাকলে নাম এবং লগআউট ড্রপডাউন দেখাবে -->
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold text-dark d-flex align-items-center gap-1 px-2 py-1 rounded-pill border bg-light" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user-circle text-danger fs-5"></i> 
                            <span><?= htmlspecialchars($_SESSION['customer_name'] ?? 'User') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item py-2 px-3 small text-danger fw-bold" href="logout.php">
                                    <i class="fa-solid fa-right-from-bracket me-1"></i> 
                                    <span class="lang-bn">লগআউট</span><span class="lang-en">Logout</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                <!-- লগইন করা না থাকলে লগইন ও রেজিস্টার বাটন দেখাবে -->
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger btn-sm px-3 rounded-pill btn-modern fw-semibold" href="login.php">
                            <span class="lang-bn">লগইন</span><span class="lang-en">Login</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-danger btn-sm px-3 rounded-pill btn-dynamic-danger fw-semibold" href="register.php">
                            <span class="lang-bn">রেজিস্টার</span><span class="lang-en">Register</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Language Switcher -->
                <li class="nav-item dropdown ms-lg-2">
                    <a class="nav-link dropdown-toggle btn btn-sm btn-outline-secondary px-3 text-dark rounded-pill btn-modern" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-globe me-1 text-muted"></i> <span id="currentLangText">বাংলা</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2" aria-labelledby="langDropdown">
                        <li><a class="dropdown-item py-2 px-3 small" href="#" onclick="switchLanguage('bn')">বাংলা (BN)</a></li>
                        <li><a class="dropdown-item py-2 px-3 small" href="#" onclick="switchLanguage('en')">English (EN)</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>