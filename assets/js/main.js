// assets/js/main.js
// Shared behaviours across every storefront page.

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ============================================================
           1. Auto-dismiss Bootstrap alerts
           ============================================================ */
        document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
            setTimeout(function () {
                if (typeof bootstrap !== 'undefined') {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                }
            }, 4500);
        });

        /* ============================================================
           2. Navbar shadow on scroll
           ============================================================ */
        const navbar = document.getElementById('siteNavbar');
        if (navbar) {
            let ticking = false;
            const update = () => {
                navbar.classList.toggle('is-scrolled', window.scrollY > 12);
                ticking = false;
            };
            update();
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(update);
                    ticking = true;
                }
            }, { passive: true });
        }

        /* ============================================================
           3. Back-to-top button
           ============================================================ */
        const backToTopBtn = document.getElementById('backToTopBtn');
        if (backToTopBtn) {
            let ticking = false;
            const update = () => {
                backToTopBtn.classList.toggle('show', window.scrollY > 450);
                ticking = false;
            };
            update();
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(update);
                    ticking = true;
                }
            }, { passive: true });
            backToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        }

        /* ============================================================
           4. Auto-close offcanvas after tapping a link
           ============================================================ */
        const offcanvasEl = document.getElementById('menuOffcanvas');
        if (offcanvasEl && window.bootstrap) {
            offcanvasEl.querySelectorAll('a[href]').forEach(function (link) {
                link.addEventListener('click', function () {
                    const instance = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (instance) instance.hide();
                });
            });
        }

        /* ============================================================
           5. Auto-focus search inside mobile search modal
           ============================================================ */
        const mobileSearch = document.getElementById('mobileSearchModal');
        if (mobileSearch) {
            mobileSearch.addEventListener('shown.bs.modal', function () {
                const input = mobileSearch.querySelector('input[type="search"]');
                if (input) input.focus();
            });
        }

        /* ============================================================
           6. SCROLL-REVEAL
           ------------------------------------------------------------
           Reveals .reveal / .reveal-left / .reveal-right /
           .reveal-scale / .reveal-stagger elements as they enter
           the viewport.

           Multiple failsafes keep content visible even if:
             - IntersectionObserver is unsupported
             - an earlier script errors out
             - the observer never fires (rare layout race conditions)
           ============================================================ */
        const revealSelectors = '.reveal, .reveal-left, .reveal-right, .reveal-scale, .reveal-stagger';
        const revealEls = document.querySelectorAll(revealSelectors);

        // Auto-tag children of .reveal-stagger with --i for the CSS delay
        document.querySelectorAll('.reveal-stagger').forEach(function (group) {
            Array.prototype.forEach.call(group.children, function (child, i) {
                child.style.setProperty('--i', i);
            });
        });

        function revealAll() {
            revealEls.forEach(function (el) { el.classList.add('is-visible'); });
        }

        if (revealEls.length) {
            if ('IntersectionObserver' in window) {
                const io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, {
                    rootMargin: '0px 0px -6% 0px',
                    threshold: 0.05
                });

                revealEls.forEach(function (el) { io.observe(el); });

                // ⭐ Failsafe #1 — if the observer hasn't fired on anything
                // after 1.2s, reveal everything so the page is never blank.
                setTimeout(function () {
                    const anyVisible = document.querySelector('.reveal.is-visible, .reveal-left.is-visible, .reveal-right.is-visible, .reveal-scale.is-visible, .reveal-stagger.is-visible');
                    if (!anyVisible) revealAll();
                }, 1200);

                // ⭐ Failsafe #2 — hard ceiling: after 3s, guarantee visibility.
                setTimeout(revealAll, 3000);
            } else {
                // No IntersectionObserver → just show everything.
                revealAll();
            }
        }

        // Restore visibility when the browser serves a bfcache page
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) revealAll();
        });

        /* ============================================================
           7. 3D Tilt + Cursor Spotlight on product cards (desktop)
           ============================================================ */
        const isDesktop = window.matchMedia('(hover: hover) and (min-width: 992px)').matches;

        if (isDesktop) {
            document.querySelectorAll('.product-card').forEach(function (card) {
                let raf = null;

                card.addEventListener('mousemove', function (e) {
                    if (raf) return;
                    raf = requestAnimationFrame(() => {
                        const rect = card.getBoundingClientRect();
                        const x = (e.clientX - rect.left) / rect.width;
                        const y = (e.clientY - rect.top) / rect.height;
                        const rotX = (0.5 - y) * 4;
                        const rotY = (x - 0.5) * 4;

                        card.style.transform =
                            'translateY(-8px) scale(1.008) rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg)';
                        card.style.setProperty('--mx', (x * 100) + '%');
                        card.style.setProperty('--my', (y * 100) + '%');
                        raf = null;
                    });
                });

                card.addEventListener('mouseleave', function () {
                    card.style.transform = '';
                });
            });
        }

        /* ============================================================
           8. Hero parallax — gentle depth on scroll
           ============================================================ */
        const hero = document.querySelector('.hero-video-container');
        if (hero && window.matchMedia('(min-width: 768px)').matches) {
            let ticking = false;
            window.addEventListener('scroll', function () {
                if (ticking) return;
                ticking = true;
                window.requestAnimationFrame(function () {
                    const rect = hero.getBoundingClientRect();
                    if (rect.bottom > 0 && rect.top < window.innerHeight) {
                        const offset = Math.min(80, Math.max(-80, rect.top * 0.15));
                        const media = hero.querySelector('video, img');
                        if (media) media.style.transform = 'scale(1.06) translateY(' + offset + 'px)';
                    }
                    ticking = false;
                });
            }, { passive: true });
        }

        /* ============================================================
           9. Smooth anchor scrolling for in-page links
           ============================================================ */
        document.querySelectorAll('a[href^="#"]:not([data-bs-toggle])').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                const targetId = this.getAttribute('href');
                if (!targetId || targetId === '#') return;
                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    const top = target.getBoundingClientRect().top + window.scrollY - 90;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                }
            });
        });

    });
})();