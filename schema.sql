-- =====================================================================
-- SHUVROTA (শুভ্রতা) E-COMMERCE DATABASE SCHEMA
-- Stack: MySQL 8.0+ / 5.7+
-- Engine: InnoDB | Charset: utf8mb4
-- =====================================================================

CREATE DATABASE IF NOT EXISTS shuvrota_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE shuvrota_db;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if re-running
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `email_logs`;
DROP TABLE IF EXISTS `sms_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `stock_logs`;
DROP TABLE IF EXISTS `purchase_order_items`;
DROP TABLE IF EXISTS `purchase_orders`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `wishlists`;
DROP TABLE IF EXISTS `banners`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `order_status_history`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `coupon_usages`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `customer_addresses`;
DROP TABLE IF EXISTS `delivery_areas`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `settings`;

-- =====================================================================
-- 1. ADMIN / STAFF
-- =====================================================================

CREATE TABLE roles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50) NOT NULL UNIQUE,
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,
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
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id            INT UNSIGNED NOT NULL,
    name               VARCHAR(100) NOT NULL,
    email              VARCHAR(150) NOT NULL UNIQUE,
    phone              VARCHAR(20) NULL,
    password_hash      VARCHAR(255) NOT NULL,
    two_factor_secret  VARCHAR(255) NULL,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    is_active          TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at      TIMESTAMP NULL,
    last_login_ip      VARCHAR(45) NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier      VARCHAR(150) NOT NULL,
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
    parent_id       INT UNSIGNED NULL,
    name            VARCHAR(100) NOT NULL,
    name_bn         VARCHAR(150) NULL,
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
-- 3. PRODUCTS & VARIANTS
-- =====================================================================

CREATE TABLE products (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         INT UNSIGNED NOT NULL,
    name                VARCHAR(200) NOT NULL,
    name_bn             VARCHAR(255) NULL,
    slug                VARCHAR(220) NOT NULL UNIQUE,
    sku                 VARCHAR(50) NOT NULL UNIQUE,
    short_description   VARCHAR(500) NULL,
    description         TEXT NULL,
    price               DECIMAL(10,2) NOT NULL,
    discount_price      DECIMAL(10,2) NULL,
    cost_price          DECIMAL(10,2) NULL,
    stock_quantity      INT NOT NULL DEFAULT 0,
    is_featured         TINYINT(1) NOT NULL DEFAULT 0,
    is_new_arrival      TINYINT(1) NOT NULL DEFAULT 0,
    status              ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    meta_title          VARCHAR(200) NULL,
    meta_description    VARCHAR(300) NULL,
    views_count         INT UNSIGNED NOT NULL DEFAULT 0,
    created_by          INT UNSIGNED NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status_featured (status, is_featured),
    FULLTEXT INDEX ft_name_desc (name, description)
) ENGINE=InnoDB;

CREATE TABLE product_images (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    image_path      VARCHAR(255) NOT NULL,
    alt_text        VARCHAR(150) NULL,
    is_primary      TINYINT(1) NOT NULL DEFAULT 0,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE product_variants (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    size            VARCHAR(50) NOT NULL,
    color           VARCHAR(50) NOT NULL,
    color_hex       VARCHAR(7) NULL,
    sku_suffix      VARCHAR(30) NULL,
    price_override  DECIMAL(10,2) NULL,
    stock_quantity  INT NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 4. CUSTOMERS & ADDRESSES
-- =====================================================================

CREATE TABLE customers (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    email             VARCHAR(150) NULL UNIQUE,
    phone             VARCHAR(20) NOT NULL UNIQUE,
    password_hash     VARCHAR(255) NULL,
    is_guest          TINYINT(1) NOT NULL DEFAULT 0,
    is_active         TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE delivery_areas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_name       VARCHAR(100) NOT NULL,
    delivery_charge DECIMAL(8,2) NOT NULL DEFAULT 0,
    estimated_days  VARCHAR(30) NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE customer_addresses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED NOT NULL,
    label           VARCHAR(50) NULL,
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20) NOT NULL,
    address_line    VARCHAR(255) NOT NULL,
    area_id         INT UNSIGNED NULL,
    district        VARCHAR(100) NULL,
    is_default      TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES delivery_areas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 5. CART
-- =====================================================================

CREATE TABLE carts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED NULL,
    session_id      VARCHAR(100) NULL,
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
    price_at_add    DECIMAL(10,2) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 6. COUPONS
-- =====================================================================

CREATE TABLE coupons (
    id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code                      VARCHAR(50) NOT NULL UNIQUE,
    type                      ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    value                     DECIMAL(10,2) NOT NULL,
    min_order_amount          DECIMAL(10,2) NULL DEFAULT 0,
    max_discount_amount       DECIMAL(10,2) NULL,
    usage_limit               INT UNSIGNED NULL,
    usage_limit_per_customer  INT UNSIGNED NULL DEFAULT 1,
    used_count                INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at                 DATETIME NULL,
    expires_at                DATETIME NULL,
    is_active                 TINYINT(1) NOT NULL DEFAULT 1,
    created_by                INT UNSIGNED NULL,
    created_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_code (code)
) ENGINE=InnoDB;

-- =====================================================================
-- 7. ORDERS & ITEMS
-- =====================================================================

CREATE TABLE orders (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number        VARCHAR(30) NOT NULL UNIQUE,
    customer_id         INT UNSIGNED NULL,
    guest_name          VARCHAR(100) NULL,
    guest_phone         VARCHAR(20) NULL,
    guest_email         VARCHAR(150) NULL,
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
    payment_method      ENUM('cod') NOT NULL DEFAULT 'cod',
    payment_status      ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    status              ENUM('new','processing','shipped','delivered','cancelled','returned') NOT NULL DEFAULT 'new',
    courier_name        VARCHAR(50) NULL,
    courier_tracking_id VARCHAR(100) NULL,
    customer_note       VARCHAR(500) NULL,
    admin_note          VARCHAR(500) NULL,
    is_flagged_spam     TINYINT(1) NOT NULL DEFAULT 0,
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

CREATE TABLE coupon_usages (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id       INT UNSIGNED NOT NULL,
    customer_id     INT UNSIGNED NULL,
    order_id        BIGINT UNSIGNED NULL,
    used_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NULL,
    variant_id      INT UNSIGNED NULL,
    product_name    VARCHAR(200) NOT NULL,
    size            VARCHAR(30) NULL,
    color           VARCHAR(50) NULL,
    unit_price      DECIMAL(10,2) NOT NULL,
    quantity        INT UNSIGNED NOT NULL DEFAULT 1,
    line_total      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT UNSIGNED NOT NULL,
    old_status      VARCHAR(30) NULL,
    new_status      VARCHAR(30) NOT NULL,
    changed_by      INT UNSIGNED NULL,
    note            VARCHAR(255) NULL,
    changed_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 8. REVIEWS, WISHLISTS, BANNERS & SETTINGS
-- =====================================================================

CREATE TABLE reviews (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    customer_id     INT UNSIGNED NULL,
    order_item_id   BIGINT UNSIGNED NULL,
    rating          TINYINT UNSIGNED NOT NULL,
    comment         TEXT NULL,
    is_approved     TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE SET NULL,
    CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE banners (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150) NULL,
    image_path      VARCHAR(255) NOT NULL,
    link_url        VARCHAR(255) NULL,
    sort_order      INT NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    starts_at       DATETIME NULL,
    ends_at         DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE wishlists (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist (customer_id, product_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE settings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key     VARCHAR(100) NOT NULL UNIQUE,
    setting_value   TEXT NULL,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id        INT UNSIGNED NULL,
    action          VARCHAR(100) NOT NULL,
    entity_type     VARCHAR(50) NULL,
    entity_id       BIGINT UNSIGNED NULL,
    ip_address      VARCHAR(45) NULL,
    details         JSON NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_admin (admin_id),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 9. INVENTORY & SUPPLIER MANAGEMENT
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
    po_number       VARCHAR(30) NOT NULL UNIQUE,
    total_cost      DECIMAL(10,2) NOT NULL DEFAULT 0,
    status          ENUM('draft','ordered','received','cancelled') NOT NULL DEFAULT 'draft',
    notes           VARCHAR(500) NULL,
    created_by      INT UNSIGNED NULL,
    purchased_at    DATETIME NULL,
    received_at     DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_po_number (po_number),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE purchase_order_items (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT UNSIGNED NOT NULL,
    product_id        INT UNSIGNED NOT NULL,
    variant_id        INT UNSIGNED NULL,
    quantity          INT UNSIGNED NOT NULL,
    unit_cost         DECIMAL(10,2) NOT NULL,
    line_total        DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_po (purchase_order_id)
) ENGINE=InnoDB;

CREATE TABLE stock_logs (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id        INT UNSIGNED NOT NULL,
    variant_id        INT UNSIGNED NULL,
    type              ENUM('purchase','sale','return','manual_adjustment') NOT NULL,
    quantity_changed  INT NOT NULL,
    stock_after       INT NULL,
    reference_id      BIGINT UNSIGNED NULL,
    reference_type    ENUM('order','purchase_order','manual') NOT NULL DEFAULT 'manual',
    note              VARCHAR(255) NULL,
    created_by        INT UNSIGNED NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_type (type),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 10. NOTIFICATIONS & LOGS
-- =====================================================================

CREATE TABLE sms_logs (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone            VARCHAR(20) NOT NULL,
    message          VARCHAR(500) NOT NULL,
    gateway          VARCHAR(50) NULL,
    gateway_response TEXT NULL,
    reference_type   ENUM('order','marketing','otp','other') NOT NULL DEFAULT 'other',
    reference_id     BIGINT UNSIGNED NULL,
    status           ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    sent_at          TIMESTAMP NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_status (status),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB;

CREATE TABLE email_logs (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    to_email         VARCHAR(150) NOT NULL,
    subject          VARCHAR(255) NOT NULL,
    reference_type   ENUM('order','account','other') NOT NULL DEFAULT 'other',
    reference_id     BIGINT UNSIGNED NULL,
    status           ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    error_message    VARCHAR(500) NULL,
    sent_at          TIMESTAMP NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_type       ENUM('customer','admin') NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    title           VARCHAR(150) NOT NULL,
    message         VARCHAR(500) NOT NULL,
    link_url        VARCHAR(255) NULL,
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_type, user_id, is_read)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
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

INSERT INTO role_permissions (role_id, permission_id)
    SELECT 1, id FROM permissions;

INSERT INTO role_permissions (role_id, permission_id)
    SELECT 2, id FROM permissions WHERE name != 'admins.manage';

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

INSERT INTO categories (name, slug, is_active) VALUES
    ('Saree', 'saree', 1),
    ('Kurti', 'kurti', 1),
    ('Crafts', 'crafts', 1);

-- =====================================================================
-- VIEW FOR DASHBOARD REPORTING
-- =====================================================================

CREATE OR REPLACE VIEW v_daily_sales AS
SELECT
    DATE(placed_at)                                             AS sale_date,
    COUNT(*)                                                    AS total_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END)       AS delivered_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END)       AS cancelled_orders,
    SUM(total_amount)                                           AS gross_revenue,
    SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) AS confirmed_revenue
FROM orders
GROUP BY DATE(placed_at);