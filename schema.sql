-- =====================================================================
-- SHUVROTA (শুভ্রতা) E-COMMERCE DATABASE SCHEMA
-- Stack: MySQL 8.0+ (works with MySQL 5.7+ too, minor tweaks noted)
-- Engine: InnoDB (for FK + transaction support)
-- Charset: utf8mb4 (needed for Bangla text, emoji, etc.)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS shuvrota_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE shuvrota_db;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1. ADMIN / STAFF (role-based access)
-- =====================================================================

CREATE TABLE roles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50) NOT NULL UNIQUE,   -- e.g. 'super_admin', 'manager', 'staff'
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,   -- e.g. 'products.create', 'orders.update_status'
    description     VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id         INT UNSIGNED NOT NULL,
    permission_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE admins (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id         INT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    phone           VARCHAR(20) NULL,
    password_hash   VARCHAR(255) NOT NULL,          -- store bcrypt/argon2 hash, never plain text
    two_factor_secret VARCHAR(255) NULL,             -- for optional OTP/2FA
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at   TIMESTAMP NULL,
    last_login_ip   VARCHAR(45) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- Track failed login attempts for rate limiting / bot protection
CREATE TABLE login_attempts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier      VARCHAR(150) NOT NULL,   -- email or IP
    ip_address      VARCHAR(45) NOT NULL,
    is_admin_login  TINYINT(1) NOT NULL DEFAULT 0,
    success         TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, attempted_at),
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB;

-- =====================================================================
-- 2. CATEGORIES
-- =====================================================================

CREATE TABLE categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id       INT UNSIGNED NULL,             -- for subcategories (e.g. Saree > Cotton Saree)
    name            VARCHAR(100) NOT NULL,
    name_bn         VARCHAR(150) NULL,             -- Bangla name for display
    slug            VARCHAR(120) NOT NULL UNIQUE,
    image           VARCHAR(255) NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- =====================================================================
-- 3. PRODUCTS
-- =====================================================================

CREATE TABLE products (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         INT UNSIGNED NOT NULL,
    name                VARCHAR(200) NOT NULL,
    name_bn             VARCHAR(255) NULL,
    slug                VARCHAR(220) NOT NULL UNIQUE,
    sku                 VARCHAR(50) NOT NULL UNIQUE,      -- Stock Keeping Unit
    short_description   VARCHAR(500) NULL,
    description         TEXT NULL,
    price               DECIMAL(10,2) NOT NULL,           -- regular price
    discount_price      DECIMAL(10,2) NULL,               -- sale price (if any)
    cost_price          DECIMAL(10,2) NULL,               -- internal cost (for profit tracking, not shown to customer)
    stock_quantity      INT NOT NULL DEFAULT 0,           -- total stock (sum of variants, or use directly if no variants)
    is_featured         TINYINT(1) NOT NULL DEFAULT 0,
    is_new_arrival      TINYINT(1) NOT NULL DEFAULT 0,
    status              ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    meta_title          VARCHAR(200) NULL,                -- SEO
    meta_description    VARCHAR(300) NULL,                -- SEO
    views_count         INT UNSIGNED NOT NULL DEFAULT 0,
    created_by          INT UNSIGNED NULL,                -- admin who created it
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status_featured (status, is_featured),
    FULLTEXT INDEX ft_name_desc (name, description)      -- for search feature
) ENGINE=InnoDB;

CREATE TABLE product_images (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    image_path      VARCHAR(255) NOT NULL,
    alt_text        VARCHAR(150) NULL,
    is_primary      TINYINT(1) NOT NULL DEFAULT 0,       -- main thumbnail
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- Size / Color / Stock combinations. Handles e.g. Saree (free size, multi-color)
-- and Kurti (S/M/L/XL x color) cleanly.
CREATE TABLE product_variants (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    size            VARCHAR(30) NULL,        -- e.g. 'S','M','L','XL','Free Size'
    color           VARCHAR(50) NULL,        -- e.g. 'Red','Maroon'
    color_hex       VARCHAR(7) NULL,         -- e.g. '#800000' for swatch UI
    sku_suffix      VARCHAR(30) NULL,        -- appended to parent SKU
    price_override  DECIMAL(10,2) NULL,      -- if this variant costs differently, else use product.price
    stock_quantity  INT NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uq_variant (product_id, size, color),
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 4. CUSTOMERS
-- =====================================================================

CREATE TABLE customers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NULL UNIQUE,
    phone           VARCHAR(20) NOT NULL UNIQUE,         -- primary identifier for BD customers (COD via phone)
    password_hash   VARCHAR(255) NULL,                    -- nullable: guest checkout allowed
    is_guest        TINYINT(1) NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE customer_addresses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED NOT NULL,
    label           VARCHAR(50) NULL,           -- 'Home','Office'
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20) NOT NULL,
    address_line    VARCHAR(255) NOT NULL,
    area_id         INT UNSIGNED NULL,          -- FK to delivery_areas
    district        VARCHAR(100) NULL,
    is_default      TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 5. DELIVERY AREAS (area-based delivery charge)
-- =====================================================================

CREATE TABLE delivery_areas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_name       VARCHAR(100) NOT NULL,      -- e.g. 'Dhaka City', 'Outside Dhaka'
    delivery_charge DECIMAL(8,2) NOT NULL DEFAULT 0,
    estimated_days  VARCHAR(30) NULL,           -- e.g. '1-2 days'
    is_active       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

ALTER TABLE customer_addresses
    ADD CONSTRAINT fk_addr_area FOREIGN KEY (area_id) REFERENCES delivery_areas(id) ON DELETE SET NULL;

-- =====================================================================
-- 6. CART (persisted cart, works for logged-in + guest via session_id)
-- =====================================================================

CREATE TABLE carts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED NULL,          -- NULL for guest
    session_id      VARCHAR(100) NULL,          -- for guest cart tracking
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id         BIGINT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NOT NULL,
    variant_id      INT UNSIGNED NULL,
    quantity        INT UNSIGNED NOT NULL DEFAULT 1,
    price_at_add    DECIMAL(10,2) NOT NULL,     -- snapshot price when added
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 7. COUPONS
-- =====================================================================

CREATE TABLE coupons (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(50) NOT NULL UNIQUE,
    type            ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    value           DECIMAL(10,2) NOT NULL,        -- 10 (%) or 100 (taka)
    min_order_amount DECIMAL(10,2) NULL DEFAULT 0,
    max_discount_amount DECIMAL(10,2) NULL,        -- cap for percentage coupons
    usage_limit     INT UNSIGNED NULL,              -- total times usable, NULL = unlimited
    usage_limit_per_customer INT UNSIGNED NULL DEFAULT 1,
    used_count      INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at       DATETIME NULL,
    expires_at      DATETIME NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_by      INT UNSIGNED NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_code (code)
) ENGINE=InnoDB;

CREATE TABLE coupon_usages (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id       INT UNSIGNED NOT NULL,
    customer_id     INT UNSIGNED NULL,
    order_id        BIGINT UNSIGNED NULL,          -- set after order table defined below (FK added later)
    used_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 8. ORDERS
-- =====================================================================

CREATE TABLE orders (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number        VARCHAR(30) NOT NULL UNIQUE,   -- human-readable e.g. 'SHV-20260901-0001'
    customer_id         INT UNSIGNED NULL,             -- NULL if guest checkout w/o account
    guest_name          VARCHAR(100) NULL,
    guest_phone         VARCHAR(20) NULL,
    guest_email         VARCHAR(150) NULL,

    -- shipping snapshot (never rely only on customer_addresses since address can change later)
    shipping_name       VARCHAR(100) NOT NULL,
    shipping_phone      VARCHAR(20) NOT NULL,
    shipping_address    VARCHAR(255) NOT NULL,
    shipping_area_id    INT UNSIGNED NULL,
    shipping_district   VARCHAR(100) NULL,

    subtotal            DECIMAL(10,2) NOT NULL,
    discount_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    delivery_charge     DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount        DECIMAL(10,2) NOT NULL,

    coupon_id           INT UNSIGNED NULL,
    payment_method      ENUM('cod') NOT NULL DEFAULT 'cod',   -- extensible later for bKash/Nagad
    payment_status      ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',

    status              ENUM('new','processing','shipped','delivered','cancelled','returned')
                            NOT NULL DEFAULT 'new',

    courier_name        VARCHAR(50) NULL,          -- 'Pathao','Steadfast','RedX'
    courier_tracking_id VARCHAR(100) NULL,

    customer_note       VARCHAR(500) NULL,
    admin_note          VARCHAR(500) NULL,

    is_flagged_spam     TINYINT(1) NOT NULL DEFAULT 0,  -- bot/fake COD protection flag
    ip_address          VARCHAR(45) NULL,

    placed_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (shipping_area_id) REFERENCES delivery_areas(id),
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_customer (customer_id),
    INDEX idx_placed_at (placed_at)
) ENGINE=InnoDB;

ALTER TABLE coupon_usages
    ADD CONSTRAINT fk_coupon_usage_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;

CREATE TABLE order_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NULL,           -- NULL-safe: keep order history even if product later deleted
    variant_id      INT UNSIGNED NULL,
    product_name    VARCHAR(200) NOT NULL,        -- snapshot (name may change later)
    size            VARCHAR(30) NULL,             -- snapshot
    color           VARCHAR(50) NULL,             -- snapshot
    unit_price      DECIMAL(10,2) NOT NULL,       -- snapshot price at purchase time
    quantity        INT UNSIGNED NOT NULL DEFAULT 1,
    line_total      DECIMAL(10,2) NOT NULL,       -- unit_price * quantity
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- Full audit trail of status changes (New -> Processing -> Delivered, etc.)
CREATE TABLE order_status_history (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT UNSIGNED NOT NULL,
    old_status      VARCHAR(30) NULL,
    new_status      VARCHAR(30) NOT NULL,
    changed_by      INT UNSIGNED NULL,           -- admin id
    note            VARCHAR(255) NULL,
    changed_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 9. REVIEWS & RATINGS (optional feature)
-- =====================================================================

CREATE TABLE reviews (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    customer_id     INT UNSIGNED NULL,
    order_item_id   BIGINT UNSIGNED NULL,       -- link to verify "verified purchase"
    rating          TINYINT UNSIGNED NOT NULL,  -- 1-5
    comment         TEXT NULL,
    is_approved     TINYINT(1) NOT NULL DEFAULT 0,  -- admin moderation before public display
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE SET NULL,
    CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 10. HOMEPAGE BANNERS / SLIDER
-- =====================================================================

CREATE TABLE banners (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150) NULL,
    image_path      VARCHAR(255) NOT NULL,
    link_url        VARCHAR(255) NULL,          -- e.g. link to a category or product
    sort_order      INT NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    starts_at       DATETIME NULL,
    ends_at         DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 11. WISHLIST (nice-to-have, low cost to include now)
-- =====================================================================

CREATE TABLE wishlists (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist (customer_id, product_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 12. SITE SETTINGS (key-value store: admin email, WhatsApp number, etc.)
-- =====================================================================

CREATE TABLE settings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key     VARCHAR(100) NOT NULL UNIQUE,   -- e.g. 'admin_notification_email', 'whatsapp_number'
    setting_value   TEXT NULL,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 13. ACTIVITY / AUDIT LOG (security requirement: track admin actions)
-- =====================================================================

CREATE TABLE activity_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id        INT UNSIGNED NULL,
    action          VARCHAR(100) NOT NULL,     -- e.g. 'product.create', 'order.status_update'
    entity_type     VARCHAR(50) NULL,           -- e.g. 'Product', 'Order'
    entity_id       BIGINT UNSIGNED NULL,
    ip_address      VARCHAR(45) NULL,
    details         JSON NULL,                  -- old/new values snapshot
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_admin (admin_id),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 14. INVENTORY & SUPPLIER MANAGEMENT (Purchase Orders)
-- =====================================================================

CREATE TABLE suppliers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name    VARCHAR(150) NOT NULL,
    contact_person  VARCHAR(100) NULL,
    phone           VARCHAR(20) NULL,
    email           VARCHAR(150) NULL,
    address         VARCHAR(255) NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company (company_name)
) ENGINE=InnoDB;

CREATE TABLE purchase_orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id     INT UNSIGNED NOT NULL,
    po_number       VARCHAR(30) NOT NULL UNIQUE,       -- e.g. 'PO-20260901-0001'
    total_cost      DECIMAL(10,2) NOT NULL DEFAULT 0,  -- can be auto-summed from purchase_order_items
    status          ENUM('draft','ordered','received','cancelled') NOT NULL DEFAULT 'draft',
    notes           VARCHAR(500) NULL,
    created_by      INT UNSIGNED NULL,                 -- admin who created the PO
    purchased_at    DATETIME NULL,                      -- when order was placed with supplier
    received_at     DATETIME NULL,                      -- when stock actually arrived
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_po_number (po_number),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Line items of a purchase order — added on top of your spec so total_cost
-- has a real, itemized breakdown (product, variant, qty, unit cost) instead
-- of being a single lump number. Also lets stock_logs reference exact items.
CREATE TABLE purchase_order_items (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id   INT UNSIGNED NOT NULL,
    product_id          INT UNSIGNED NOT NULL,
    variant_id          INT UNSIGNED NULL,
    quantity            INT UNSIGNED NOT NULL,
    unit_cost           DECIMAL(10,2) NOT NULL,
    line_total          DECIMAL(10,2) NOT NULL,        -- quantity * unit_cost
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_po (purchase_order_id)
) ENGINE=InnoDB;

-- Full audit trail of every stock movement — sale, purchase, return, manual fix.
-- This is the single source of truth for "why is stock X right now".
CREATE TABLE stock_logs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id          INT UNSIGNED NOT NULL,
    variant_id          INT UNSIGNED NULL,
    type                ENUM('purchase','sale','return','manual_adjustment') NOT NULL,
    quantity_changed    INT NOT NULL,                  -- positive = stock in, negative = stock out
    stock_after         INT NULL,                       -- resulting stock level (optional snapshot, handy for audits)
    reference_id        BIGINT UNSIGNED NULL,            -- order_id (sale/return) or purchase_order_id (purchase)
    reference_type      ENUM('order','purchase_order','manual') NOT NULL DEFAULT 'manual',
    note                VARCHAR(255) NULL,               -- e.g. reason for manual_adjustment
    created_by          INT UNSIGNED NULL,               -- admin who made a manual adjustment
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_type (type),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 15. NOTIFICATIONS (SMS & Email Logs, in-app notifications)
-- =====================================================================

CREATE TABLE sms_logs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone               VARCHAR(20) NOT NULL,
    message             VARCHAR(500) NOT NULL,
    gateway             VARCHAR(50) NULL,               -- e.g. 'GreenwebSMS', 'BulkSMSBD'
    gateway_response    TEXT NULL,                       -- raw response/JSON from SMS API for debugging
    reference_type      ENUM('order','marketing','otp','other') NOT NULL DEFAULT 'other',
    reference_id        BIGINT UNSIGNED NULL,             -- e.g. order_id if this SMS was an order confirmation
    status              ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    sent_at             TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_status (status),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB;

-- Email delivery log (order confirmation to admin, etc.) — mirrors sms_logs
-- so both channels can be tracked/debugged the same way.
CREATE TABLE email_logs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    to_email            VARCHAR(150) NOT NULL,
    subject             VARCHAR(255) NOT NULL,
    reference_type      ENUM('order','account','other') NOT NULL DEFAULT 'other',
    reference_id        BIGINT UNSIGNED NULL,
    status              ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    error_message       VARCHAR(500) NULL,
    sent_at             TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_type       ENUM('customer','admin') NOT NULL,
    user_id         INT UNSIGNED NOT NULL,             -- customer.id or admin.id depending on user_type
    title           VARCHAR(150) NOT NULL,
    message         VARCHAR(500) NOT NULL,
    link_url        VARCHAR(255) NULL,                  -- e.g. deep link to the related order in admin panel
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_type, user_id, is_read)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA (starter roles, permissions, delivery areas)
-- =====================================================================

INSERT INTO roles (name, description) VALUES
    ('super_admin', 'Full access to everything'),
    ('manager', 'Manage products, orders, coupons - no staff management'),
    ('staff', 'Order processing and product entry only');

INSERT INTO permissions (name, description) VALUES
    ('products.manage', 'Add/edit/delete products'),
    ('categories.manage', 'Add/edit/delete categories'),
    ('orders.manage', 'View and update order status'),
    ('coupons.manage', 'Create/edit discount coupons'),
    ('customers.view', 'View customer list and details'),
    ('reports.view', 'View sales dashboard/reports'),
    ('admins.manage', 'Add/edit/remove admin/staff accounts'),
    ('settings.manage', 'Update site-wide settings, banners'),
    ('inventory.manage', 'Manage suppliers, purchase orders and stock logs');

-- super_admin gets everything
INSERT INTO role_permissions (role_id, permission_id)
    SELECT 1, id FROM permissions;

-- manager gets all except admin management
INSERT INTO role_permissions (role_id, permission_id)
    SELECT 2, id FROM permissions WHERE name != 'admins.manage';

-- staff gets only products + orders
INSERT INTO role_permissions (role_id, permission_id)
    SELECT 3, id FROM permissions WHERE name IN ('products.manage','orders.manage');

INSERT INTO delivery_areas (area_name, delivery_charge, estimated_days) VALUES
    ('Inside Dhaka', 70.00, '1-2 days'),
    ('Outside Dhaka', 130.00, '2-4 days');

INSERT INTO settings (setting_key, setting_value) VALUES
    ('site_name', 'Shuvrota'),
    ('admin_notification_email', ''),
    ('whatsapp_number', ''),
    ('currency_symbol', '৳');

-- =====================================================================
-- USEFUL VIEW: quick sales dashboard base (daily/monthly report feature)
-- =====================================================================

CREATE OR REPLACE VIEW v_daily_sales AS
SELECT
    DATE(placed_at)            AS sale_date,
    COUNT(*)                   AS total_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
    SUM(total_amount)          AS gross_revenue,
    SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) AS confirmed_revenue
FROM orders
GROUP BY DATE(placed_at);

-- =====================================================================
-- NOTES FOR THE TEAM (Abdur Rahim Hridoy / backend):
-- 1. stock_quantity on `products` should be treated as the SUM of all
--    active `product_variants.stock_quantity` when variants exist.
--    If a product has NO variants (e.g. simple item), use products.stock_quantity directly.
-- 2. On order placement: decrement stock inside a DB transaction to avoid
--    overselling during concurrent COD orders (SELECT ... FOR UPDATE).
-- 3. order_number should be generated app-side (e.g. SHV-YYYYMMDD-XXXX)
--    and checked for uniqueness before insert.
-- 4. Always snapshot product_name/price/size/color into order_items —
--    never JOIN live product data for historical orders.
-- 5. Passwords: use PHP password_hash() (bcrypt) or Laravel's built-in
--    Hash facade — never store plaintext, never use md5/sha1.
-- 6. For bot/fake-COD protection: use login_attempts + orders.is_flagged_spam
--    combined with app-level rate limiting (e.g. max N orders per phone/IP per hour).
-- 7. Every stock change MUST create a `stock_logs` row (app-level, inside the
--    same DB transaction as the stock update) — never update products/variants
--    stock_quantity without also inserting the matching log. This keeps
--    stock_logs as the single source of truth for "why is stock X right now".
-- 8. Receiving a purchase order (`purchase_orders.status` -> 'received'):
--    a) increment product/variant stock_quantity for each purchase_order_item
--    b) insert one stock_logs row per item with type='purchase',
--       reference_type='purchase_order', reference_id = purchase_orders.id
--    c) optionally update products.cost_price from unit_cost for margin tracking
-- 9. On order placement/cancellation/return, insert matching stock_logs rows
--    (type='sale' / 'return') with reference_type='order', reference_id = orders.id.
-- 10. sms_logs/email_logs are write-once-then-update: insert with status='pending'
--     right before calling the SMS/email API, then update status/gateway_response
--     after the API responds — this way a crashed request still leaves a trace.
-- =====================================================================
