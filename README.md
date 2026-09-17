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
- Product search and category filtering
- Product details page with image gallery, hover-zoom, and click-to-enlarge lightbox
- Size/color variant selection and quantity picker
- Wishlist (requires login) and shopping cart (works for guests, stored in session)
- Checkout with coupon codes, Inside/Outside Dhaka delivery charges, and Cash on Delivery
- Order tracking by order number or phone number
- Customer login/register (phone + password)
- Bilingual UI (Bangla ⇄ English) with a persistent language toggle
- Floating WhatsApp contact button
- Fully responsive — phone, tablet, and desktop

### Admin Panel (`/admin`)
- Sales dashboard (orders, pending orders, delivered revenue)
- Product catalog management (add/edit/delete, images, variants)
- Order management with status updates (New → Processing → Shipped → Delivered / Cancelled)
- Coupon management
- Site banner management (hero video/image, category images)

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
├── index.php                 # Homepage (hero, collections, product grid)
├── product-details.php       # Single product page
├── cart.php                  # Shopping cart (session-based)
├── checkout.php              # Checkout + coupons + COD order placement
├── track.php                 # Order tracking by order number / phone
├── login.php / register.php  # Customer authentication
├── logout.php
├── wishlist.php               # Customer wishlist (requires login)
├── about.php
├── delivery-policy.php
├── privacy-policy.php
├── refund-policy.php
├── terms.php
├── config/
│   ├── database.php          # PDO connection (auto-detects local vs. live server)
│   └── session.php            # Secure session bootstrap
├── includes/
│   ├── header.php             # Shared nav, search, cart/wishlist icons, mobile menu
│   └── footer.php             # Shared footer, WhatsApp button, back-to-top, scripts
├── assets/
│   ├── css/style.css          # Design system (colors, type, components, responsive rules)
│   └── js/main.js             # Shared behaviours (scroll shadow, back-to-top, alerts)
├── uploads/                    # Product images/videos uploaded via admin panel
├── admin/                       # Admin panel (dashboard, products, orders, coupons, banners)
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

`includes/header.php` opens `<html>`, the nav, and `<main>`; `includes/footer.php` closes `</main>`, prints the footer, and closes `</body></html>`. This means every page automatically gets the same navigation, cart/wishlist counts, and styling — you never need to touch the nav in more than one place again.

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

This creates all tables (`products`, `categories`, `orders`, `customers`, `coupons`, `admins`, etc.) and seeds some starter data, including a default admin account.

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

### Step 5 — Open the site
Visit:
```
http://localhost/shuvrota/
```
(replace `shuvrota` with whatever folder name you used in Step 1)

You should see the homepage with the hero banner and category collections. If the product grid is empty, that's expected until you add products from the admin panel.

### Step 6 — Log into the admin panel
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

---

## 6. Configuration Notes

- **`config/database.php`** auto-detects whether it's running locally (`localhost`/`127.0.0.1`) or on a live server, and switches connection settings automatically — you shouldn't need to maintain two copies of this file when moving between local and production.
- **`config/session.php`** hardens PHP's session cookie settings (HttpOnly, SameSite, etc.) — don't remove this include from any page that touches `$_SESSION`.
- **Uploads:** product images/videos uploaded through the admin panel are saved to `/uploads`. Make sure this folder is writable by your web server (`chmod 755` or higher on Linux/macOS; not usually an issue on Windows/XAMPP).
- **Hero video:** the homepage falls back to `assets/videos/heritage-craft.mp4` if no hero banner is set in the `site_banners` table via the admin panel. That folder is currently empty in this repo — either upload a hero video/image through the admin panel's banner manager, or drop an `.mp4` file at that path.

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

---

## 8. What Changed in the Redesign

The storefront was rebuilt on top of the existing PHP/MySQL logic — **no backend logic, database queries, or form handling was changed**, only presentation:

- **New design system** (`assets/css/style.css`): warm ivory background, deep maroon (`#7b1113`) + antique gold accents, Playfair Display + Hind Siliguri typography, refined cards/buttons/forms, and mobile-first responsive rules.
- **Unified header/footer**: every page previously had its own copy-pasted `<nav>` and `<footer>` HTML (with small inconsistencies between pages). These are now centralized in `includes/header.php` / `includes/footer.php`, so the nav, cart count, wishlist count, and language switcher are consistent everywhere and only need to be maintained in one place.
- **`assets/css/style.css` is now actually linked.** In the original code, this file existed but wasn't referenced from any page — the custom styles were dead code. It's now loaded on every page.
- **Mobile navigation**: a proper offcanvas menu with categories, search, and account links, plus a sticky nav that gains a shadow on scroll and a back-to-top button.
- **`terms.php` now has real content.** The original file was an accidental duplicate of the homepage (same hero/collections markup, wrong title) — it's been replaced with genuine bilingual Terms & Conditions content, in the same style as the other policy pages.
- **`refund-policy.php`** was moved onto the shared header/footer include (it previously had its own hand-written nav/footer copy); its actual policy content is unchanged.
- The **admin panel was left untouched** — it doesn't currently link `assets/css/style.css` at all (it's plain Bootstrap), so none of these changes affect it either visually or functionally.

If you add new customer-facing pages later, follow the pattern in section 3 (`$pageTitle` + `include header.php` … `include footer.php`) to automatically inherit the shared nav and styling.

---

## 9. Development Team

| Name | Role | Responsibilities |
|---|---|---|
| Nafil Ardul Ridin | Team Lead & DB Architect | Architecture, database schema, security, deployment |
| Ashikur Rahman | Frontend Developer | Responsive UI/UX, styling, storefront design |
| Hridoy Khan | Backend Developer | Backend logic, order processing, admin CRUD features |
| Shafin Khan | Design & Documentation | Brand assets, image optimization, user manual |
| Shahriar | Python Specialist | Database seeding automation, scripting/tests |

---

## License

Internal project — no license file included. Add one if you plan to open-source this.
