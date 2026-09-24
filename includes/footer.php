</main>

<!-- ==================== FOOTER ==================== -->
<footer class="site-footer">
    <div class="footer-wrap">

        <div class="footer-grid">

            <div class="footer-brand-block">
                <a href="index.php" class="footer-logo">
                    <span class="footer-logo-mark">
                        <?php if (!empty($__site_logo ?? '')): ?>
                            <img src="<?= htmlspecialchars($__site_logo) ?>" alt="Shuvrota">
                        <?php else: ?>
                            <i class="fa-solid fa-gem"></i>
                        <?php endif; ?>
                    </span>
                    <span class="footer-logo-text">
                        <span class="lang-bn">শুভ্রতা</span>
                        <span class="lang-en">Shuvrota</span>
                    </span>
                </a>

                <p class="footer-brand-desc">
                    <span class="lang-bn">হাতে বোনা ঐতিহ্য, ভালোবাসায় গড়া — নারী কারিগরদের নিপুণ হস্তশিল্প।</span>
                    <span class="lang-en">Handwoven heritage, crafted with love by women artisans.</span>
                </p>

                <div class="footer-socials">
                    <a href="https://www.facebook.com/share/1HfkGH7G6f/"
                       target="_blank" rel="noopener"
                       class="footer-social" aria-label="Facebook" title="Facebook">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor" aria-hidden="true">
                            <path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"/>
                        </svg>
                    </a>
                    <a href="https://wa.me/8801719844226"
                       target="_blank" rel="noopener"
                       class="footer-social" aria-label="WhatsApp" title="WhatsApp">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                    </a>
                    <a href="mailto:shuvrota032@gmail.com"
                       class="footer-social" aria-label="Email" title="Email">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="5.5" width="18" height="13" rx="2"/>
                            <path d="M3.5 7.5l8.5 6 8.5-6"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h6 class="footer-label">
                    <span class="lang-bn">শপ</span>
                    <span class="lang-en">Shop</span>
                </h6>
                <ul class="footer-nav">
                    <li><a href="index.php?show_products=1"><span class="lang-bn">সকল পণ্য</span><span class="lang-en">All Products</span></a></li>
                    <li><a href="index.php#best-selling"><span class="lang-bn">বেস্ট সেলিং</span><span class="lang-en">Best Sellers</span></a></li>
                    <li><a href="index.php#collections"><span class="lang-bn">কালেকশন</span><span class="lang-en">Collections</span></a></li>
                    <li><a href="wishlist.php"><span class="lang-bn">উইশলিস্ট</span><span class="lang-en">Wishlist</span></a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h6 class="footer-label">
                    <span class="lang-bn">সহায়তা</span>
                    <span class="lang-en">Support</span>
                </h6>
                <ul class="footer-nav">
                    <li><a href="track.php"><span class="lang-bn">অর্ডার ট্র্যাক</span><span class="lang-en">Track Order</span></a></li>
                    <li><a href="delivery-policy.php"><span class="lang-bn">ডেলিভারি</span><span class="lang-en">Delivery</span></a></li>
                    <li><a href="refund-policy.php"><span class="lang-bn">রিটার্ন ও রিফান্ড</span><span class="lang-en">Return & Refund</span></a></li>
                    <li><a href="privacy-policy.php"><span class="lang-bn">প্রাইভেসি</span><span class="lang-en">Privacy Policy</span></a></li>
                    <li><a href="terms.php"><span class="lang-bn">শর্তাবলী</span><span class="lang-en">Terms</span></a></li>
                </ul>
            </div>

            <div class="footer-col footer-col-contact">
                <h6 class="footer-label">
                    <span class="lang-bn">যোগাযোগ</span>
                    <span class="lang-en">Contact</span>
                </h6>
                <ul class="footer-contact">
                    <li>
                        <i class="fa-solid fa-location-dot"></i>
                        <span>
                            <span class="lang-bn">ব্রিজ মোড়, ময়মনসিংহ</span>
                            <span class="lang-en">Bridge More, Mymensingh</span>
                        </span>
                    </li>
                    <li>
                        <i class="fa-solid fa-phone"></i>
                        <a href="tel:+8801719844226">+8801719844226</a>
                    </li>
                    <li>
                        <i class="fa-solid fa-envelope"></i>
                        <a href="mailto:shuvrota032@gmail.com">shuvrota032@gmail.com</a>
                    </li>
                </ul>
            </div>

        </div>

        <div class="footer-bar">
            <p class="footer-copy">
                &copy; <?= date('Y') ?> <span class="footer-copy-brand">Shuvrota</span>
                <span class="footer-copy-sep">·</span>
                <span class="lang-bn">সর্বস্বত্ব সংরক্ষিত</span>
                <span class="lang-en">All rights reserved</span>
            </p>
        </div>

    </div>
</footer>

<!-- ==================== MOBILE BOTTOM NAV ==================== -->
<nav class="mobile-bottom-nav d-md-none" aria-label="Mobile primary navigation">

    <a href="index.php" class="mbn-item" data-nav="home">
        <span class="mbn-icon"><i class="fa-solid fa-house"></i></span>
        <span class="mbn-label">
            <span class="lang-bn">হোম</span><span class="lang-en">Home</span>
        </span>
    </a>

    <a href="index.php?show_products=1" class="mbn-item" data-nav="shop">
        <span class="mbn-icon"><i class="fa-solid fa-store"></i></span>
        <span class="mbn-label">
            <span class="lang-bn">শপ</span><span class="lang-en">Shop</span>
        </span>
    </a>

    <a href="wishlist.php" class="mbn-item" data-nav="wishlist">
        <span class="mbn-icon">
            <i class="fa-regular fa-heart"></i>
            <?php if (($__wishlist_count ?? 0) > 0): ?>
                <span class="mbn-badge"><?= $__wishlist_count > 99 ? '99+' : (int)$__wishlist_count ?></span>
            <?php endif; ?>
        </span>
        <span class="mbn-label">
            <span class="lang-bn">উইশলিস্ট</span><span class="lang-en">Wishlist</span>
        </span>
    </a>

    <a href="cart.php" class="mbn-item" data-nav="cart">
        <span class="mbn-icon">
            <i class="fa-solid fa-bag-shopping"></i>
            <?php if (($__cart_count ?? 0) > 0): ?>
                <span class="mbn-badge"><?= $__cart_count > 99 ? '99+' : (int)$__cart_count ?></span>
            <?php endif; ?>
        </span>
        <span class="mbn-label">
            <span class="lang-bn">কার্ট</span><span class="lang-en">Cart</span>
        </span>
    </a>

    <?php if (($__customer_id ?? 0) > 0): ?>
        <a href="account.php" class="mbn-item" data-nav="account">
            <span class="mbn-icon"><i class="fa-regular fa-user"></i></span>
            <span class="mbn-label">
                <span class="lang-bn">অ্যাকাউন্ট</span><span class="lang-en">Account</span>
            </span>
        </a>
    <?php else: ?>
        <a href="login.php" class="mbn-item" data-nav="login">
            <span class="mbn-icon"><i class="fa-regular fa-user"></i></span>
            <span class="mbn-label">
                <span class="lang-bn">লগইন</span><span class="lang-en">Login</span>
            </span>
        </a>
    <?php endif; ?>

</nav>

<!-- ==================== FLOATING HELPERS ==================== -->
<a href="https://wa.me/8801719844226"
   target="_blank" rel="noopener"
   class="whatsapp-float"
   aria-label="WhatsApp এ যোগাযোগ করুন"
   title="WhatsApp এ যোগাযোগ করুন">
    <i class="fa-brands fa-whatsapp"></i>
</a>

<button type="button"
        class="back-to-top"
        id="backToTopBtn"
        aria-label="উপরে যান"
        title="উপরে যান">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<!-- ==================== TOAST CONTAINER ==================== -->
<div class="cart-toast-container" id="cartToastContainer" aria-live="polite"></div>

<!-- ==================== SCRIPTS ==================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
/* =====================================================================
   CSRF — read the token every AJAX POST below must send
   ===================================================================== */
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* =====================================================================
   LANGUAGE — text toggle + placeholder swap
   ===================================================================== */

/* Swap placeholder of every input that declares both language variants */
function updatePlaceholders(lang) {
    document.querySelectorAll('[data-placeholder-bn][data-placeholder-en]').forEach(function (el) {
        el.placeholder = (lang === 'en')
            ? el.getAttribute('data-placeholder-en')
            : el.getAttribute('data-placeholder-bn');
    });
}

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
    updatePlaceholders(lang);
}

/* =====================================================================
   FLY ANIMATION — shared engine
   ===================================================================== */
function flyTo(sourceEl, targetSelector, dotClass, pulseClass) {
    if (!sourceEl) return;

    const target = document.querySelector(targetSelector);

    const srcRect = sourceEl.getBoundingClientRect();
    const startX  = srcRect.left + srcRect.width / 2;
    const startY  = srcRect.top + srcRect.height / 2;

    const dot = document.createElement('div');
    dot.className = 'fly-dot' + (dotClass ? ' ' + dotClass : '');
    dot.style.left = startX + 'px';
    dot.style.top  = startY + 'px';
    document.body.appendChild(dot);

    let endX = startX, endY = startY;
    if (target) {
        const tgtRect = target.getBoundingClientRect();
        endX = tgtRect.left + tgtRect.width / 2;
        endY = tgtRect.top + tgtRect.height / 2;
    } else {
        endY = startY - 80;
    }

    const dx = endX - startX;
    const dy = endY - startY;

    requestAnimationFrame(() => {
        dot.style.transform = `translate(${dx}px, ${dy}px) scale(0.25)`;
        dot.style.opacity   = '0.25';
    });

    setTimeout(() => {
        dot.remove();
        if (target) {
            const icon = target.querySelector('i');
            if (icon && pulseClass) {
                icon.classList.remove('cart-pulse', 'wishlist-pulse');
                void icon.offsetWidth;
                icon.classList.add(pulseClass);
                setTimeout(() => icon.classList.remove('cart-pulse', 'wishlist-pulse'), 750);
            }
        }
    }, 880);
}

function isMobileViewport() {
    return window.matchMedia('(max-width: 767.98px)').matches;
}

function flyToCart(sourceEl) {
    const target = isMobileViewport()
        ? '.mobile-bottom-nav .mbn-item[data-nav="cart"]'
        : '.site-navbar .nav-icon-link[href="cart.php"]';
    flyTo(sourceEl, target, '', 'cart-pulse');
}

function flyToWishlist(sourceEl) {
    const target = isMobileViewport()
        ? '.mobile-bottom-nav .mbn-item[data-nav="wishlist"]'
        : '.site-navbar .nav-icon-link[href="wishlist.php"]';
    flyTo(sourceEl, target, 'is-wishlist', 'wishlist-pulse');
}

/* =====================================================================
   TOAST — cart
   ===================================================================== */
function showCartToast(productTitle, isError) {
    const container = document.getElementById('cartToastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'cart-toast' + (isError ? ' is-error' : '');

    const iconClass = isError ? 'fa-triangle-exclamation' : 'fa-check';
    const title     = isError ? 'Could not add' : 'Added to cart';

    toast.innerHTML = `
        <span class="cart-toast-icon"><i class="fa-solid ${iconClass}"></i></span>
        <div class="cart-toast-body">
            <p class="cart-toast-title">${title}</p>
            <p class="cart-toast-sub">${productTitle || ''}</p>
        </div>
        <a href="cart.php" class="cart-toast-view">View</a>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('is-leaving');
        setTimeout(() => toast.remove(), 400);
    }, 2600);
}

/* =====================================================================
   TOAST — wishlist
   ===================================================================== */
function showWishlistToast(state, message) {
    const container = document.getElementById('cartToastContainer');
    if (!container) return;

    let iconClass = 'fa-heart';
    let title     = 'Added to wishlist';
    let isError   = false;

    if (state === 'removed') {
        iconClass = 'fa-heart-crack';
        title     = 'Removed from wishlist';
    } else if (state === 'error') {
        iconClass = 'fa-triangle-exclamation';
        title     = 'Wishlist error';
        isError   = true;
    }

    const toast = document.createElement('div');
    toast.className = 'cart-toast is-wishlist' + (isError ? ' is-error' : '');
    toast.innerHTML = `
        <span class="cart-toast-icon"><i class="fa-solid ${iconClass}"></i></span>
        <div class="cart-toast-body">
            <p class="cart-toast-title">${title}</p>
            <p class="cart-toast-sub">${message || ''}</p>
        </div>
        <a href="wishlist.php" class="cart-toast-view">View</a>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('is-leaving');
        setTimeout(() => toast.remove(), 400);
    }, 2600);
}

/* =====================================================================
   BADGE UPDATES
   ===================================================================== */
function updateCartBadges(count) {
    const mbnCart = document.querySelector('.mobile-bottom-nav .mbn-item[data-nav="cart"] .mbn-icon');
    if (mbnCart) {
        let badge = mbnCart.querySelector('.mbn-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'mbn-badge';
                mbnCart.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
        } else if (badge) { badge.remove(); }
    }

    const headerCart = document.querySelector('.site-navbar .nav-icon-link[href="cart.php"]');
    if (headerCart) {
        let badge = headerCart.querySelector('.icon-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'icon-badge';
                headerCart.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) { badge.remove(); }
    }
}

function updateWishlistBadges(count) {
    const mbnWish = document.querySelector('.mobile-bottom-nav .mbn-item[data-nav="wishlist"] .mbn-icon');
    if (mbnWish) {
        let badge = mbnWish.querySelector('.mbn-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'mbn-badge';
                mbnWish.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
        } else if (badge) { badge.remove(); }
    }

    const headerWish = document.querySelector('.site-navbar .nav-icon-link[href="wishlist.php"]');
    if (headerWish) {
        let badge = headerWish.querySelector('.icon-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'icon-badge';
                headerWish.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) { badge.remove(); }
    }
}

/* =====================================================================
   AJAX — ADD TO CART
   ===================================================================== */
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || form.tagName !== 'FORM') return;

    const submitter  = e.submitter;
    const actionAttr = (submitter && submitter.getAttribute('formaction'))
        || form.getAttribute('action')
        || '';

    if (!actionAttr.includes('cart.php')) return;

    const productIdInput = form.querySelector('input[name="product_id"]');
    if (!productIdInput) return;

    e.preventDefault();

    const formData = new FormData(form);
    const btn = submitter || form.querySelector('button[type="submit"]');

    flyToCart(btn);

    fetch('add_to_cart_ajax.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF_TOKEN }
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateCartBadges(data.cart_count);
                showCartToast(data.product_title || 'Item added', false);
            } else {
                showCartToast(data.message || 'Could not add to cart', true);
            }
        })
        .catch(() => showCartToast('Network error. Please try again.', true));
});

/* =====================================================================
   AJAX — WISHLIST TOGGLE
   ===================================================================== */
document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href]');
    if (!link) return;

    const href = link.getAttribute('href') || '';
    if (!href.includes('wishlist')) return;

    let productId = null;
    try {
        const url = new URL(link.href, location.origin);

        if (url.searchParams.get('action') === 'wishlist' && url.searchParams.get('id')) {
            productId = url.searchParams.get('id');
        } else if (url.pathname.endsWith('wishlist.php') && url.searchParams.get('add')) {
            productId = url.searchParams.get('add');
        }
    } catch (err) { return; }

    if (!productId) return;

    e.preventDefault();

    link.classList.remove('is-activating');
    void link.offsetWidth;
    link.classList.add('is-activating');
    setTimeout(() => link.classList.remove('is-activating'), 550);

    flyToWishlist(link);

    const fd = new FormData();
    fd.append('product_id', productId);

    fetch('toggle_wishlist_ajax.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF_TOKEN }
    })
        .then(r => r.json())
        .then(data => {
            if (data.requires_login) {
                window.location.href = 'login.php';
                return;
            }

            if (!data.success) {
                showWishlistToast('error', data.message || 'Could not update wishlist');
                return;
            }

            const icon = link.querySelector('i.fa-heart, i.fa-heart-crack, i.fa-regular.fa-heart, i.fa-solid.fa-heart');
            if (icon) {
                if (data.state === 'added') {
                    icon.classList.remove('fa-regular', 'text-dark');
                    icon.classList.add('fa-solid', 'text-danger');
                } else {
                    icon.classList.remove('fa-solid', 'text-danger');
                    icon.classList.add('fa-regular', 'text-dark');
                }
            }

            updateWishlistBadges(data.wishlist_count);
            showWishlistToast(data.state === 'added' ? 'added' : 'removed', '');
        })
        .catch(() => showWishlistToast('error', 'Network error. Please try again.'));
});

/* =====================================================================
   INIT ON DOM READY
   ===================================================================== */
document.addEventListener('DOMContentLoaded', function () {
    const savedLang = localStorage.getItem('selectedLang') || 'bn';
    switchLanguage(savedLang);

    /* ---- Mobile bottom nav active-state detection ---- */
    const mbnItems = document.querySelectorAll('.mobile-bottom-nav .mbn-item');
    if (mbnItems.length) {
        const path      = (location.pathname.split('/').pop() || 'index.php').toLowerCase();
        const params    = new URLSearchParams(location.search);
        const isListing = params.has('show_products') || params.has('category_id') || params.has('search');

        mbnItems.forEach(item => {
            const nav = item.getAttribute('data-nav');
            let active = false;

            if (nav === 'home'     && (path === 'index.php' || path === '') && !isListing) active = true;
            else if (nav === 'shop'     && path === 'index.php' && isListing)               active = true;
            else if (nav === 'wishlist' && path === 'wishlist.php')                         active = true;
            else if (nav === 'cart'     && path === 'cart.php')                             active = true;
            else if (nav === 'account'  && path === 'account.php')                          active = true;
            else if (nav === 'login'    && (path === 'login.php' || path === 'register.php')) active = true;

            if (active) item.classList.add('active');
        });
    }
});
</script>
</body>
</html>