</main>

<!-- Footer -->
<footer class="site-footer pt-5 pb-3 mt-auto">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="fw-bold mb-3">Shuvrota</h5>
                <p class="small mb-2">
                    <span class="lang-bn">শুভ্রতা একটি community-driven social enterprise, যা হরিজন/দলিত সম্প্রদায়ের নারী কারিগরদের ক্ষমতায়নে কাজ করে।</span>
                    <span class="lang-en">Shuvrota is a community-driven social enterprise working to empower women artisans from Harijan/Dalit communities.</span>
                </p>
                <p class="small mb-0 fst-italic">
                    <span class="lang-bn">গল্প বুনি, সুযোগ তৈরি করি।</span>
                    <span class="lang-en">Weaving stories, creating opportunities.</span>
                </p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold mb-3"><span class="lang-bn">জরুরি লিংক</span><span class="lang-en">Quick Links</span></h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="about.php"><span class="lang-bn">আমাদের সম্পর্কে</span><span class="lang-en">About Us</span></a></li>
                    <li class="mb-2"><a href="delivery-policy.php"><span class="lang-bn">ডেলিভারি পলিসি</span><span class="lang-en">Delivery Policy</span></a></li>
                    <li class="mb-2"><a href="refund-policy.php"><span class="lang-bn">রিটার্ন ও রিফান্ড পলিসি</span><span class="lang-en">Return & Refund Policy</span></a></li>
                    <li class="mb-2"><a href="privacy-policy.php"><span class="lang-bn">প্রাইভেসি পলিসি</span><span class="lang-en">Privacy Policy</span></a></li>
                    <li class="mb-2"><a href="terms.php"><span class="lang-bn">টার্মস অ্যান্ড কন্ডিশন্স</span><span class="lang-en">Terms & Conditions</span></a></li>
                    <li class="mb-2"><a href="track.php"><span class="lang-bn">অর্ডার ট্র্যাকিং</span><span class="lang-en">Order Tracking</span></a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold mb-3"><span class="lang-bn">যোগাযোগ করুন</span><span class="lang-en">Contact Us</span></h6>
                <p class="small mb-2"><i class="fa-solid fa-location-dot me-2 text-warning"></i><span class="lang-bn">ব্রিজ মোড়, ময়মনসিংহ, বাংলাদেশ</span><span class="lang-en">Bridge More, Mymensingh, Bangladesh</span></p>
                <p class="small mb-2"><i class="fa-solid fa-phone me-2 text-warning"></i><a href="tel:01719844226">01719844226</a></p>
                <p class="small mb-2"><i class="fa-brands fa-whatsapp me-2 text-warning"></i><a href="https://wa.me/8801719844226" target="_blank" rel="noopener">01719844226</a></p>
                <p class="small mb-2"><i class="fa-solid fa-envelope me-2 text-warning"></i><a href="mailto:shuvrota032@gmail.com">shuvrota032@gmail.com</a></p>
                <p class="small mb-0"><i class="fa-solid fa-user-shield me-2 text-warning"></i><span class="lang-bn">অ্যাডমিন:</span><span class="lang-en">Admin:</span> <a href="mailto:fariaislam1909@gmail.com">fariaislam1909@gmail.com</a></p>
            </div>
        </div>
        <hr class="my-4">
        <div class="text-center small">
            &copy; <?= date('Y') ?> Shuvrota. <span class="lang-bn">সর্বস্বত্ব সংরক্ষিত।</span><span class="lang-en">All rights reserved.</span>
        </div>
    </div>
</footer>

<!-- Floating helpers -->
<a href="https://wa.me/8801719844226" target="_blank" rel="noopener" class="whatsapp-float" aria-label="WhatsApp এ যোগাযোগ করুন" title="WhatsApp এ যোগাযোগ করুন">
    <i class="fa-brands fa-whatsapp"></i>
</a>
<button type="button" class="back-to-top" id="backToTopBtn" aria-label="উপরে যান" title="উপরে যান">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
function switchLanguage(lang) {
    const body = document.body;
    if (lang === 'en') {
        body.classList.remove('lang-bn-mode');
        body.classList.add('lang-en-mode');
        const t = document.getElementById('currentLangText');
        if (t) t.innerText = 'English';
        localStorage.setItem('selectedLang', 'en');
    } else {
        body.classList.remove('lang-en-mode');
        body.classList.add('lang-bn-mode');
        const t = document.getElementById('currentLangText');
        if (t) t.innerText = 'বাংলা';
        localStorage.setItem('selectedLang', 'bn');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const savedLang = localStorage.getItem('selectedLang') || 'bn';
    switchLanguage(savedLang);
});
</script>
</body>
</html>
