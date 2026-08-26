CREATE DATABASE IF NOT EXISTS `shuvrota_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shuvrota_db`;

DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL
);

CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT,
  `title` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255) DEFAULT 'default.jpg',
  `description` TEXT,
  `stock` INT DEFAULT 10,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
);

CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(20) NOT NULL UNIQUE,
  `customer_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `delivery_area` ENUM('inside_dhaka', 'outside_dhaka') DEFAULT 'inside_dhaka',
  `delivery_charge` DECIMAL(10,2) DEFAULT 80.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cod', 'bkash', 'nagad') DEFAULT 'cod',
  `transaction_id` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('New', 'Processing', 'Delivered', 'Cancelled') DEFAULT 'New',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO `categories` (`id`, `name`, `slug`) VALUES
(1, 'রেডি-টু-ওয়্যার শাড়ি', 'ready-saree'),
(2, 'কুর্তি ও সালোয়ার কামিজ', 'kurti-salwar');
UPDATE products 
SET image = 'https://images.unsplash.com/photo-1617627143750-d86bc21e42bb?auto=format&fit=crop&w=600&q=80' 
WHERE id IN (3, 4);