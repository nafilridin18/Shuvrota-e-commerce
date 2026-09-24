# 🛍️ শুভ্রতা (Shuvrota) — E-Commerce Platform

A responsive, bilingual (Bangla/English) e-commerce storefront for handmade sarees, kurtis, and handicrafts made by women artisans — built with plain PHP (PDO), MySQL, and Bootstrap 5, with a full admin panel for managing products, orders, and coupons.

This README covers everything a developer needs: what the project is, how it's organized, how to run it locally, and what changed in the latest storefront redesign.

---

## Table of Contents
1. [Features](#1-features)
2. [Tech Stack](#2-tech-stack)
3. [Project Structure](#3-project-structure)
4. [Local Setup (Step-by-Step)](#4-local-setup-step-by-step)
5. [Default Admin Login](#5-default-admin-login)
6. [Configuration Notes](#6-configuration-notes)
7. [Troubleshooting](#7-troubleshooting)
8. [What Changed in the Redesign](#8-what-changed-in-the-redesign)
9. [Development Team](#9-development-team)

---

## 1. Features

### Customer Storefront
- Dynamic homepage with hero banner (image/video, admin-configurable), category collections, and a live product grid
- Product search and category filtering, with **bilingual search placeholders** that swap with the language toggle
- Product details page with image gallery, hover-zoom, and click-to-enlarge lightbox
- Size/color variant selection and quantity picker
- **Stock-out handling**: out-of-stock products display a "Stock Out" overlay on cards, greyscale imagery, disabled Order/Cart buttons, and a warning panel on the details page
- **Wishlist** (requires login) with **AJAX fly-to-heart animation**, rose-colored toast, and live badge updates — no page refresh
- **Shopping cart** with **AJAX fly-to-cart animation**, green toast, and live badge updates — no page refresh
- **Checkout** with **AJAX coupon apply/remove**, animated spinner, shake-on-error, and slide-in discount row — no page refresh
- Coupon codes, Inside/Outside Dhaka delivery charges, and Cash on Delivery
- Order tracking by order number or phone number
- Customer login/register (phone + password)
- **Editable account details** including name, phone, email, address, city, and postal code
- Bilingual UI (Bangla ⇄ English) with a persistent language toggle and instant placeholder swaps
- Floating WhatsApp contact button
- **Modern mobile experience**:
  - Floating **frosted-glass pill header** with brand + search
  - Floating **frosted-glass bottom navigation** (Home · Shop · Wishlist · Cart · Account) with maroon/gold active indicator
  - Safe-area-aware positioning (works on notched iPhones and gesture-navigation Android)
- **Redesigned Cart & Wishlist pages** with glass cards, real quantity steppers, size/color chips, and responsive grids
- Fully responsive — phone, tablet, and desktop

### Admin Panel (`/admin`)
- Sales dashboard (orders, pending orders, delivered revenue, low-stock count)
- Order pipeline (New → Processing → Shipped → Delivered / Cancelled / Returned)
- Product catalog management (add/edit/delete, images, video, variants, featured/best-selling flags)
- Order management with status updates and internal notes
- Customer list with total orders and total spent
- Coupon management
- Site banner management (hero video/image, category images, site logo)
- Best-selling product manager with auto-suggestions based on sales

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x (PDO, native sessions, no framework) |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3 (custom design system), vanilla JavaScript |
| UI Framework | Bootstrap 5.3 (via CDN) |
| Icons | Font Awesome 6 (via CDN) |
| Fonts | Google Fonts — Playfair Display (headings) + Hind Siliguri (Bangla/English body text) |

No build step, no `npm install`, no Composer dependencies — it's plain PHP you can drop into any Apache+MySQL stack (XAMPP, WAMP, MAMP, Laragon, or a cPanel host).

---

## 3. Project Structure

```
Shuvrota-e-commerce-main/
├── index.php                    # Homepage (hero, collections, product grid)
├── product-details.php          # Single product page
├── cart.php                     # Shopping cart (session-based)
├── wishlist.php                 # Customer wishlist (requires login)
├── checkout.php                 # Checkout + coupons + COD order placement
├── track.php                    # Order tracking by order number / phone
├── account.php                  # Customer account & order history
├── login.php / register.php     # Customer authentication
├── logout.php
│
├── add_to_cart_ajax.php         # ⭐ AJAX: add to cart (returns JSON)
├── toggle_wishlist_ajax.php     # ⭐ AJAX: wishlist toggle (returns JSON)
├── apply_coupon_ajax.php        # ⭐ AJAX: apply/remove coupon (returns JSON)
│
├── about.php
├── delivery-policy.php
├── privacy-policy.php
├── refund-policy.php
├── terms.php
│
├── config/
│   ├── database.php             # PDO connection (auto-detects local vs. live server)
│   └── session.php              # Secure session bootstrap
│
├── includes/
│   ├── header.php               # Shared nav, search, cart/wishlist icons, offcanvas menu
│   └── footer.php               # Shared footer, mobile bottom nav, all AJAX + animation scripts
│
├── assets/
│   ├── css/style.css            # Design system (colors, type, components, responsive rules)
│   ├── js/main.js               # Shared behaviours (scroll shadow, back-to-top, tilt)
│   ├── images/default.jpg       # Fallback product image
│   └── videos/heritage-craft.mp4 # Fallback hero video
│
├── uploads/                     # Product images/videos/logo/banners (writable by web server)
│   ├── logo/                    # Site logo (admin-uploaded)
│   ├── banners/                 # Hero + category images/videos
│   └── videos/                  # Product videos
│
├── admin/                       # Admin panel
│   ├── index.php                # Dashboard
│   ├── add_product.php
│   ├── edit_product.php
│   ├── delete_product.php
│   ├── manage_best_selling.php
│   ├── manage_catagories.php
│   ├── coupons.php
│   ├── customers.php
│   ├── banner_settings.php
│   ├── order_details.php
│   ├── login.php / logout.php
│   ├── auth_check.php
│   ├── assets/admin.css         # Admin panel stylesheet
│   └── includes/
│       ├── header.php
│       └── footer.php
│
├── schema.sql                   # Full database schema + seed data
└── .htaccess
```

**Key pattern:** every customer-facing page follows the same shape —

```php
<?php
// ...page-specific PHP logic (DB queries, form handling)...
$pageTitle = 'পেজের টাইটেল - শুভ্রতা';
include __DIR__ . '/includes/header.php';
?>
<!-- page HTML -->
<?php include __DIR__ . '/includes/footer.php'; ?>
```

`includes/header.php` opens `<html>`, the nav, and `<main>`; `includes/footer.php` closes `</main>`, prints the footer, the mobile bottom nav, and **all shared JavaScript** (including AJAX interception for cart/wishlist/coupons and the toast/animation engine). This means every page automatically gets the same navigation, cart/wishlist counts, and animations — you never need to touch the nav in more than one place again.

---

## 4. Local Setup (Step-by-Step)

### What you need
- [XAMPP](https://www.apachefriends.org/) (or WAMP/MAMP/Laragon) — gives you PHP + Apache + MySQL + phpMyAdmin
- A code editor (VS Code recommended)
- Git (optional, if cloning from GitHub)

### Step 1 — Get the files onto your machine
Unzip this project (or clone your repo) directly into your server's web root:

- **XAMPP (Windows):** `C:\xampp\htdocs\shuvrota`
- **XAMPP (macOS/Linux):** `/Applications/XAMPP/htdocs/shuvrota` or `/opt/lampp/htdocs/shuvrota`

So the path `C:\xampp\htdocs\shuvrota\index.php` should exist.

### Step 2 — Start Apache and MySQL
Open the **XAMPP Control Panel** and click **Start** next to both **Apache** and **MySQL**.

> ⚠️ If MySQL fails to start because port `3306` is already in use (common on Windows if you have another MySQL install, e.g. from WAMP or a previous install), open XAMPP's MySQL **Config → my.ini**, change the port to `3307`, and restart. This project's `config/database.php` already expects port **3307** for local development (see [Configuration Notes](#6-configuration-notes)) — if your MySQL runs on the default `3306` instead, update that file to match.

### Step 3 — Create the database
1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **New** in the left sidebar → name the database **`shuvrota_db`** → click **Create**
3. Select the new `shuvrota_db` database, click the **Import** tab
4. Choose the `schema.sql` file from the project folder → click **Go**

This creates all tables (`products`, `categories`, `orders`, `customers`, `coupons`, `admins`, `complaints`, `site_banners`, etc.) and seeds some starter data, including a default admin account.

### Step 4 — Check the database config
Open `config/database.php`. For local development it auto-selects these values when `SERVER_NAME` is `localhost` or `127.0.0.1`:

```php
$host = '127.0.0.1';
$port = '3307';          // ⚠️ change to '3306' if your MySQL uses the default port
$db   = 'shuvrota_db';
$user = 'root';
$pass = '';               // try 'root' if blank doesn't work
```

You normally don't need to change anything here for local use — just confirm the `$port` matches whatever your MySQL is actually running on (check the XAMPP Control Panel, it lists the port next to MySQL).

### Step 5 — Make `uploads/` writable
Product images, videos, the site logo, and banners are saved under `/uploads`. On Linux/macOS, run:

```bash
chmod -R 755 uploads
```

On Windows/XAMPP this is usually fine by default.

### Step 6 — Open the site
Visit:
```
http://localhost/shuvrota/
```
(replace `shuvrota` with whatever folder name you used in Step 1)

You should see the homepage with the hero banner and category collections. If the product grid is empty, that's expected until you add products from the admin panel.

### Step 7 — Log into the admin panel
Go to:
```
http://localhost/shuvrota/admin/login.php
```
Use the credentials in [Default Admin Login](#5-default-admin-login) below to add categories, products, images, and coupons — the storefront pulls everything live from the database, so once you add a product there it will immediately appear on the homepage/shop page.

---

## 5. Default Admin Login

The seed data in `schema.sql` creates one admin account:

| Field | Value |
|---|---|
| URL | `/admin/login.php` |
| Email | `admin@shuvrota.com` |
| Password | `ChangeMeNow!2026` |

**Change this password immediately after your first login**, especially before deploying anywhere public — it's a well-known default sitting in a public-facing schema file.

**Two-factor login (email OTP) is ON by default** for this account. After you enter the password above, a 6-digit code is required to finish logging in.
- Until you configure a real mailbox in `config/mail.php`, the app runs in **dev mode**: the code is written to `storage/dev_mail_log.txt` instead of being emailed, so you can log in and test locally without setting up SMTP first.
- Fill in `config/mail.php` with a real mailbox (a cPanel email account, or a transactional service like Brevo/SendGrid) before deploying anywhere public — dev mode should never run on a live server.
- You can turn 2FA on/off per admin account from **Admin > Security (2FA)** once logged in.

---

## 6. Configuration Notes

- **`config/database.php`** auto-detects whether it's running locally (`localhost`/`127.0.0.1`) or on a live server, and switches connection settings automatically — you shouldn't need to maintain two copies of this file when moving between local and production.
- **`config/session.php`** hardens PHP's session cookie settings (HttpOnly, SameSite, auto-detects HTTPS) — don't remove this include from any page that touches `$_SESSION`.
- **Uploads:** product images/videos/logo/banners are saved under `/uploads`. Make sure this folder is writable by your web server (`chmod 755` or higher on Linux/macOS; not usually an issue on Windows/XAMPP).
- **Hero video:** the homepage falls back to `assets/videos/heritage-craft.mp4` if no hero banner is set in the `site_banners` table via the admin panel. That folder is currently empty in the repo — either upload a hero video/image through the admin panel's banner manager, or drop an `.mp4` file at that path.
- **AJAX endpoints:** the three new files (`add_to_cart_ajax.php`, `toggle_wishlist_ajax.php`, `apply_coupon_ajax.php`) must live in the project root alongside `index.php`. If you move them, update the fetch URLs in `includes/footer.php` and `checkout.php` accordingly.
- **`.htaccess` depends on Apache (`mod_rewrite` + `mod_headers`).** The HTTPS redirect, security headers, and the block on executing PHP inside `uploads/` all rely on it. **Before going live, confirm your host actually runs Apache** (true for virtually all cPanel shared hosting; not true for Nginx or some LiteSpeed setups without `.htaccess` translation). If unsure, ask your host, or test by trying to download `schema.sql` directly from the live URL after deploying — it should return a 403, not the file itself. As a backstop, file uploads are also validated by their real content type in PHP itself (`includes/upload_validator.php`), not just by `.htaccess`, so a misconfigured server doesn't remove that layer of protection.
- **`config/mail.php`** must be filled in with real SMTP credentials before going live — see the 2FA note in section 5. Without it, admin 2FA codes only get written to `storage/dev_mail_log.txt`, which is fine for local testing but not for production.

---

## 7. Troubleshooting

| Problem | Likely Cause / Fix |
|---|---|
| Blank white page | Turn on PHP error display temporarily: add `ini_set('display_errors', 1); error_reporting(E_ALL);` to the top of `config/database.php`, reload, read the error, then remove it again. |
| "Something went wrong on our end" | Database connection failed — check `config/database.php`'s port/credentials against your actual MySQL setup, and confirm `shuvrota_db` was imported. |
| Homepage shows no categories/products | You haven't added any yet — log into `/admin`, add a category and a product with at least one image. |
| Styles look unstyled / plain Bootstrap | Hard-refresh (Ctrl+Shift+R) to clear a cached copy of `assets/css/style.css`, and confirm the file wasn't blocked by an ad-blocker or a strict `Content-Security-Policy`. |
| MySQL won't start in XAMPP | Port conflict — see Step 2 above. |
| Login/register says "wrong password" for the admin | Password is case-sensitive: `ChangeMeNow!2026` exactly. |
| **Add to Cart / Wishlist / Apply Coupon does nothing** | Open DevTools → Network tab. If the `*_ajax.php` request is 404, the file is missing from the project root. If 500, check `error_log`. If it redirects to login, the user isn't authenticated for wishlist. |
| **Flying animation goes off-screen** | The fly-to-cart target is `.site-navbar .nav-icon-link[href="cart.php"]` on desktop and `.mobile-bottom-nav .mbn-item[data-nav="cart"]` on mobile. If you renamed those classes, update `flyToCart()` in `includes/footer.php`. |
| **Toast doesn't appear** | Confirm `<div class="cart-toast-container" id="cartToastContainer">` exists in `includes/footer.php` and hasn't been removed by a template override. |

---

## 8. What Changed in the Redesign

The storefront was rebuilt on top of the existing PHP/MySQL logic — **no database schema or business logic was changed**, only presentation and UX. Key improvements across recent versions:

### Design System (v3.3)
- **New design system** (`assets/css/style.css`): warm ivory background, deep maroon (`#7b1113`) + antique gold accents, Playfair Display + Hind Siliguri typography, refined cards/buttons/forms, and mobile-first responsive rules.
- **Unified header/footer**: every page previously had its own copy-pasted `<nav>` and `<footer>`. These are now centralized in `includes/header.php` / `includes/footer.php`.
- **`assets/css/style.css` is now actually linked.** In the original code, this file existed but wasn't referenced from any page — the custom styles were dead code. It's now loaded on every page.
- **Mobile navigation**: a proper offcanvas menu with categories, search, and account links, plus a sticky nav that gains a shadow on scroll.
- **`terms.php` now has real content.** The original file was an accidental duplicate of the homepage — it's been replaced with genuine bilingual Terms & Conditions.
- **`refund-policy.php`** was moved onto the shared header/footer include; its policy content is unchanged.

### Mobile UX Overhaul (v3.4 – v3.5)
- **Floating glass-morphic bottom navigation** (mobile only) with five tabs: Home · Shop · Wishlist · Cart · Account. Includes maroon/gold active indicator, badge counts, safe-area-aware positioning, and — where relevant — is the fly-to target for cart/wishlist animations.
- **Floating glass-morphic top header** (mobile only) matching the bottom nav's design language: frosted glass, gold hairline on the inner edge, subtle white reflection, and hover lift on the search button.
- **Back-to-top button hidden on mobile** (the bottom nav makes it redundant there).
- **Redesigned Cart & Wishlist pages** — glass cards, real `−`/`+` quantity steppers, size/color chips, per-item line totals, sticky order summary, per-card checkbox, hover-remove heart, and price with strikethrough.

### AJAX + Animation (v3.6 – v3.7)
- **Add to Cart is fully AJAX** via `add_to_cart_ajax.php`. Clicking Add to Cart:
  1. A maroon dot flies from the button to the cart icon (bottom nav on mobile, top nav on desktop).
  2. The cart icon pulses.
  3. A green toast slides in from the top-right (desktop) or above the bottom nav (mobile) with the product name and a "View" link.
  4. Cart badge counts update instantly — no page reload.
- **Wishlist toggle is fully AJAX** via `toggle_wishlist_ajax.php`. Same animation pattern but with a **rose-colored dot and rose toast**, plus the heart icon flips between outline and solid-red in place.
- **Coupon apply/remove is fully AJAX** via `apply_coupon_ajax.php`. Includes:
  - Rotating spinner inside the Apply button
  - Shake animation on invalid code / min-order-not-met
  - Slide-in discount row in the order summary with a coupon-code chip
  - Instant grand-total recalculation
  - One-tap remove (`X` inside the input) with a confirmation toast
- **All three endpoints gracefully fall back** to their original server-rendered equivalents if JavaScript is disabled.

### Account & Checkout Polish (v3.7)
- **Email is now editable** from `account.php` — previously it was a disabled read-only field. The form validates format with `filter_var` and rejects duplicates with a bilingual error message.
- **Stock-out display** (v3.8): out-of-stock products now show a bold "Stock Out" overlay on the product image (with a soft greyscale + dark gradient), disabled Order/Cart buttons, and a red warning panel on the product-details page with size/color/quantity inputs greyed out. Wishlist still works on stock-out items.
- **Bilingual search placeholders**: the desktop search, offcanvas search, and mobile-search modal now swap between Bangla and English placeholders the instant the user switches language — no page reload.

### Admin
- The admin panel was left untouched functionally — it doesn't currently link `assets/css/style.css` at all (it's plain Bootstrap). The one small fix was centering the "Update Product" card on `edit_product.php` (added `mx-auto`) so it matches `add_product.php`.

If you add new customer-facing pages later, follow the pattern in section 3 (`$pageTitle` + `include header.php` … `include footer.php`) to automatically inherit the shared nav, mobile bottom nav, styling, and AJAX animations.

---

## 9. Development Team

| Name | Role | Responsibilities |
|---|---|---|
| Nafil Ardul Ridin | Team Lead & DB Architect | Architecture, database schema, security, deployment |
| Ashikur Rahman | Frontend Developer | Responsive UI/UX, Mobile View Interface Design, styling, storefront design, Some Backend Logics |
| Hridoy Khan | Backend Developer | Backend logic, order processing, admin CRUD features |
| Shafin Khan | Design & Documentation | Brand assets, image optimization, user manual |
| Shahriar | Python Specialist | Database seeding automation, scripting/tests |

---

## License

Internal project — no license file included. Add one if you plan to open-source this.