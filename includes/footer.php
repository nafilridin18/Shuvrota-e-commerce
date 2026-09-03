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