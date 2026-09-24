-- =====================================================================
-- migration_round2.sql
-- Run this ONCE against your EXISTING database if you already have data
-- in it (customers, orders, products) and don't want to re-import the
-- whole schema.sql from scratch.
--
-- If you're doing a fresh install instead, ignore this file — schema.sql
-- already includes everything below.
-- =====================================================================

USE shuvrota_db;

-- 1) New table needed for track.php's rate limiting (report finding C-4)
CREATE TABLE IF NOT EXISTS `order_track_attempts` (
    `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address`   VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB;

-- 2) Turn on email 2FA for your existing admin account(s).
--    (two_factor_enabled already existed as a column — it just defaulted
--    to 0 and nothing used it before now. This is optional: skip it if you
--    don't want 2FA on yet. You can also toggle this from
--    Admin > Security (2FA) once logged in, instead of running SQL.)
UPDATE `admins` SET `two_factor_enabled` = 1 WHERE `email` = 'admin@shuvrota.com';

-- 3) Optional cleanup: a dead, unused duplicate table (report finding L-3).
--    Safe to drop — nothing in the codebase reads or writes to it.
DROP TABLE IF EXISTS `order_complaints`;
