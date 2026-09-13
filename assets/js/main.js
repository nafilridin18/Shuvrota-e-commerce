// assets/js/main.js
// Shared, site-wide behaviours used across every storefront page.

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ---- Legacy language toggle button (id="langToggleBtn"), kept for
               backward compatibility with older markup that used a single
               toggle button instead of the BN/EN dropdown. ---- */
        const legacyToggle = document.getElementById('langToggleBtn');
        if (legacyToggle && typeof switchLanguage === 'function') {
            legacyToggle.addEventListener('click', function (e) {
                e.preventDefault();
                const activeLang = localStorage.getItem('selectedLang') || 'bn';
                switchLanguage(activeLang === 'bn' ? 'en' : 'bn');
            });
        }

        /* ---- Auto-dismiss dismissible Bootstrap alerts after 4s ---- */
        document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
            setTimeout(function () {
                if (typeof bootstrap !== 'undefined') {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            }, 4000);
        });

        /* ---- Navbar gains a shadow once the page is scrolled ---- */
        const navbar = document.getElementById('siteNavbar');
        if (navbar) {
            const toggleNavShadow = function () {
                navbar.classList.toggle('is-scrolled', window.scrollY > 8);
            };
            toggleNavShadow();
            window.addEventListener('scroll', toggleNavShadow, { passive: true });
        }

        /* ---- Back-to-top button ---- */
        const backToTopBtn = document.getElementById('backToTopBtn');
        if (backToTopBtn) {
            const toggleBackToTop = function () {
                backToTopBtn.classList.toggle('show', window.scrollY > 400);
            };
            toggleBackToTop();
            window.addEventListener('scroll', toggleBackToTop, { passive: true });
            backToTopBtn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        /* ---- Close the mobile offcanvas menu automatically after a link
               inside it is tapped, so users aren't stuck looking at the menu
               after navigating (nice-to-have on slower mobile connections). ---- */
        const offcanvasEl = document.getElementById('menuOffcanvas');
        if (offcanvasEl && window.bootstrap) {
            offcanvasEl.querySelectorAll('a[href]').forEach(function (link) {
                link.addEventListener('click', function () {
                    const instance = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (instance) instance.hide();
                });
            });
        }
    });
})();
