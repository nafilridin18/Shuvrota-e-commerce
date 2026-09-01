// assets/js/main.js

function applyLanguage(lang) {
    localStorage.setItem('shuvrota_lang', lang);
    const langToggleBtn = document.getElementById('langToggleBtn');

    if (lang === 'en') {
        document.body.classList.remove('lang-bn-mode');
        document.body.classList.add('lang-en-mode');
        if (langToggleBtn) langToggleBtn.innerText = 'বাংলা';
    } else {
        document.body.classList.remove('lang-en-mode');
        document.body.classList.add('lang-bn-mode');
        if (langToggleBtn) langToggleBtn.innerText = 'English';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Set initial language from LocalStorage
    const currentLang = localStorage.getItem('shuvrota_lang') || 'bn';
    applyLanguage(currentLang);

    // Event Listener for Language Toggle Button
    const langToggleBtn = document.getElementById('langToggleBtn');
    if (langToggleBtn) {
        langToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const activeLang = localStorage.getItem('shuvrota_lang') || 'bn';
            const newLang = activeLang === 'bn' ? 'en' : 'bn';
            applyLanguage(newLang);
        });
    }

    // Auto dismissal of Bootstrap Alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (typeof bootstrap !== 'undefined') {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 4000);
    });
});