<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="bn" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return & Refund Policy - শুভ্রতা</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .lang-en { display: none; }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-danger" href="index.php">
            <i class="fa-solid fa-gem me-1"></i><span class="lang-bn">শুভ্রতা</span><span class="lang-en">Shuvrota</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <span class="lang-bn">হোম</span><span class="lang-en">Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="track.php">
                        <span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Order Tracking</span>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item">
                    <a class="btn btn-outline-dark btn-sm px-3 rounded-pill" href="cart.php">
                        <i class="fa-solid fa-cart-shopping me-1"></i> <span class="lang-bn">কার্ট</span><span class="lang-en">Cart</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-danger btn-sm px-3 rounded-pill" href="login.php">
                        <span class="lang-bn">লগইন</span><span class="lang-en">Login</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-danger btn-sm px-3 rounded-pill" href="register.php">
                        <span class="lang-bn">রেজিস্টার</span><span class="lang-en">Register</span>
                    </a>
                </li>

                <!-- Language Switcher -->
                <li class="nav-item dropdown ms-2">
                    <a class="nav-link dropdown-toggle btn btn-sm btn-outline-secondary px-3 text-dark rounded-pill" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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

<!-- Content Section -->
<div class="container my-5 flex-grow-1">
    <div class="card shadow-sm border-0 p-4 rounded-4">
        <h2 class="fw-bold text-danger mb-4">
            <span class="lang-bn">রিটার্ন ও রিফান্ড পলিসি</span>
            <span class="lang-en">Return & Refund Policy</span>
        </h2>
        
        <div class="lang-bn">
            <p>১. প্রোডাক্ট ডেলিভারি নেওয়ার সময় কোনো ত্রুটি বা সমস্যা থাকলে সাথে সাথে ডেলিভারি ম্যানের সামনে চেক করে রিটার্ন করতে পারবেন।</p>
            <p>২. প্রোডাক্ট ব্যবহারের পর সাধারণত রিটার্ন গ্রহণ করা হয় না, যদি না ম্যানুফ্যাকচারিং ত্রুটি থাকে।</p>
            <p>৩. রিফান্ডের ক্ষেত্রে ৩ থেকে ৭ কার্যদিবসের মধ্যে আপনার পেমেন্ট মাধ্যম অনুযায়ী টাকা ফেরত দেওয়া হবে।</p>
        </div>

        <div class="lang-en">
            <p>1. If there is any defect or issue upon delivery, you can check and return the product immediately in front of the delivery man.</p>
            <p>2. Products are generally not returnable after use unless there is a manufacturing defect.</p>
            <p>3. Refunds will be processed within 3 to 7 working days through your original payment method.</p>
        </div>
    </div>
</div>

<!-- Footer Section -->
<footer class="bg-dark text-light pt-5 pb-3 mt-auto">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="fw-bold text-warning mb-3">Shuvrota</h5>
                <p class="text-light small">
                    <span class="lang-bn">Shuvrota একটি community-driven social enterprise, যা হরিজন/দলিত সম্প্রদায়ের নারী কারিগরদের ক্ষমতায়নে কাজ করে।</span>
                    <span class="lang-en">Shuvrota is a community-driven social enterprise working to empower women artisans from Harijan/Dalit communities.</span>
                </p>
                <p class="text-light small">Weaving stories, creating opportunities.</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold text-warning mb-3">
                    <span class="lang-bn">জরুরি লিংক</span><span class="lang-en">Quick Links</span>
                </h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="about.php" class="text-decoration-none text-light">
                        <span class="lang-bn">আমাদের সম্পর্কে</span><span class="lang-en">About Us</span>
                    </a></li>
                    <li class="mb-2"><a href="delivery-policy.php" class="text-decoration-none text-light">
                        <span class="lang-bn">ডেলিভারি পলিসি</span><span class="lang-en">Delivery Policy</span>
                    </a></li>
                    <li class="mb-2"><a href="refund-policy.php" class="text-decoration-none text-light">
                        <span class="lang-bn">রিটার্ন ও রিফান্ড পলিসি</span><span class="lang-en">Return & Refund Policy</span>
                    </a></li>
                    <li class="mb-2"><a href="privacy-policy.php" class="text-decoration-none text-light">
                        <span class="lang-bn">প্রাইভেসি পলিসি</span><span class="lang-en">Privacy Policy</span>
                    </a></li>
                    <li class="mb-2"><a href="terms.php" class="text-decoration-none text-light">
                        <span class="lang-bn">টার্মস অ্যান্ড কন্ডিশন্স</span><span class="lang-en">Terms & Conditions</span>
                    </a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold text-warning mb-3">
                    <span class="lang-bn">যোগাযোগ করুন</span><span class="lang-en">Contact Us</span>
                </h6>
                <p class="text-light small mb-2"><i class="fa-solid fa-location-dot me-2 text-warning"></i>Bridge More, Mymensingh, Bangladesh</p>
                <p class="text-light small mb-2"><i class="fa-solid fa-phone me-2 text-warning"></i>01719844226</p>
                <p class="text-light small mb-2"><i class="fa-brands fa-whatsapp me-2 text-warning"></i>01719844226</p>
                <p class="text-light small mb-2"><i class="fa-solid fa-envelope me-2 text-warning"></i>shuvrata032@gmail.com</p>
                <p class="text-light small mb-0"><i class="fa-solid fa-user-shield me-2 text-warning"></i>Admin: fariaislam1909@gmail.com</p>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="text-center text-light small">
            &copy; 2026 Shuvrota. <span class="lang-bn">সর্বস্বত্ব সংরক্ষিত।</span><span class="lang-en">All rights reserved.</span>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchLanguage(lang) {
    const bnElements = document.querySelectorAll('.lang-bn');
    const enElements = document.querySelectorAll('.lang-en');
    
    if (lang === 'en') {
        bnElements.forEach(el => el.style.display = 'none');
        enElements.forEach(el => el.style.display = 'inline');
        document.getElementById('currentLangText').innerText = 'English';
        localStorage.setItem('selectedLang', 'en');
    } else {
        enElements.forEach(el => el.style.display = 'none');
        bnElements.forEach(el => el.style.display = 'inline');
        document.getElementById('currentLangText').innerText = 'বাংলা';
        localStorage.setItem('selectedLang', 'bn');
    }
}

window.onload = function() {
    const savedLang = localStorage.getItem('selectedLang') || 'bn';
    switchLanguage(savedLang);
};
</script>
</body>
</html>