<?php // admin/includes/footer.php ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openSidebar() {
    document.getElementById('adminSidebar').classList.add('show');
    document.getElementById('adminBackdrop').classList.add('show');
}
function closeSidebar() {
    document.getElementById('adminSidebar').classList.remove('show');
    document.getElementById('adminBackdrop').classList.remove('show');
}
function switchAdminLang(lang) {
    document.body.classList.remove('lang-bn-mode', 'lang-en-mode');
    document.body.classList.add(lang === 'en' ? 'lang-en-mode' : 'lang-bn-mode');
    const t = document.getElementById('currentLangText');
    if (t) t.innerText = lang === 'en' ? 'English' : 'বাংলা';
    localStorage.setItem('adminLang', lang);
}
document.addEventListener('DOMContentLoaded', function () {
    switchAdminLang(localStorage.getItem('adminLang') || 'bn');
    // auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(a => {
        setTimeout(() => { if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(a).close(); }, 4000);
    });
});
</script>
</body>
</html>