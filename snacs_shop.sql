-- ============================================================
-- SNACS SHOP - Hotel & Snacks Center Management System
-- Database: snacs_shop
-- Import this file via phpMyAdmin
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

-- Create Database
CREATE DATABASE IF NOT EXISTS `snacs_shop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `snacs_shop`;

-- ============================================================
-- Table: users (Admin Login)
-- ============================================================
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(200) NOT NULL,
  `role` ENUM('admin','staff') NOT NULL DEFAULT 'admin',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin: username=admin, password=admin123
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@snacsshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- ============================================================
-- Table: tables (Hotel Tables)
-- ============================================================
CREATE TABLE `tables` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `table_number` VARCHAR(20) NOT NULL,
  `capacity` INT(11) NOT NULL DEFAULT 4,
  `status` ENUM('available','occupied','reserved') NOT NULL DEFAULT 'available',
  `floor` VARCHAR(50) DEFAULT 'Ground Floor',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_number` (`table_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tables` (`table_number`, `capacity`, `status`, `floor`) VALUES
('T01', 4, 'available', 'Ground Floor'),
('T02', 4, 'available', 'Ground Floor'),
('T03', 6, 'available', 'Ground Floor'),
('T04', 2, 'available', 'Ground Floor'),
('T05', 4, 'available', 'Ground Floor'),
('T06', 4, 'available', 'First Floor'),
('T07', 6, 'available', 'First Floor'),
('T08', 4, 'available', 'First Floor'),
('T09', 2, 'available', 'First Floor'),
('T10', 8, 'available', 'First Floor');

-- ============================================================
-- Table: menu_categories
-- ============================================================
CREATE TABLE `menu_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT '🍽️',
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menu_categories` (`name`, `icon`, `sort_order`) VALUES
('Starters', '🥗', 1),
('Main Course', '🍛', 2),
('Snacks', '🍟', 3),
('Beverages', '☕', 4),
('Desserts', '🍮', 5),
('Breads', '🫓', 6);

-- ============================================================
-- Table: menu_items
-- ============================================================
CREATE TABLE `menu_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_veg` TINYINT(1) NOT NULL DEFAULT 1,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `is_veg`, `is_available`) VALUES
(1, 'Veg Spring Rolls', 'Crispy rolls filled with mixed vegetables', 80.00, 1, 1),
(1, 'Chicken 65', 'Spicy deep fried chicken starter', 150.00, 0, 1),
(1, 'Paneer Tikka', 'Grilled cottage cheese with spices', 130.00, 1, 1),
(1, 'Gobi Manchurian', 'Crispy cauliflower in Indo-Chinese sauce', 100.00, 1, 1),
(2, 'Chicken Biryani', 'Aromatic basmati rice with tender chicken', 200.00, 0, 1),
(2, 'Veg Biryani', 'Aromatic basmati rice with mixed vegetables', 150.00, 1, 1),
(2, 'Butter Chicken', 'Creamy tomato-based chicken curry', 180.00, 0, 1),
(2, 'Dal Tadka', 'Yellow lentils tempered with garlic and cumin', 100.00, 1, 1),
(2, 'Paneer Butter Masala', 'Cottage cheese in rich tomato gravy', 160.00, 1, 1),
(3, 'Samosa (2 pcs)', 'Fried pastry with spiced potato filling', 30.00, 1, 1),
(3, 'Pav Bhaji', 'Spiced mashed vegetables with buttered buns', 80.00, 1, 1),
(3, 'Masala Dosa', 'Crispy rice crepe with potato filling', 70.00, 1, 1),
(3, 'Idli Sambar (3 pcs)', 'Steamed rice cakes with lentil soup', 60.00, 1, 1),
(3, 'Vada (2 pcs)', 'Crispy lentil donuts', 40.00, 1, 1),
(3, 'French Fries', 'Crispy golden potato fries with dip', 70.00, 1, 1),
(4, 'Masala Chai', 'Spiced Indian tea', 20.00, 1, 1),
(4, 'Filter Coffee', 'South Indian filter coffee', 30.00, 1, 1),
(4, 'Fresh Lime Soda', 'Refreshing lime with soda', 40.00, 1, 1),
(4, 'Mango Lassi', 'Creamy mango yogurt drink', 60.00, 1, 1),
(4, 'Cold Coffee', 'Chilled coffee blended with ice cream', 80.00, 1, 1),
(5, 'Gulab Jamun (2 pcs)', 'Soft milk dumplings in sugar syrup', 50.00, 1, 1),
(5, 'Ice Cream', 'Vanilla / Chocolate / Strawberry scoop', 60.00, 1, 1),
(5, 'Kheer', 'Rice pudding with dry fruits', 70.00, 1, 1),
(6, 'Butter Naan', 'Soft leavened bread with butter', 30.00, 1, 1),
(6, 'Tandoori Roti', 'Whole wheat bread from tandoor', 20.00, 1, 1),
(6, 'Paratha (Plain)', 'Whole wheat flatbread', 25.00, 1, 1);

-- ============================================================
-- Table: orders
-- ============================================================
CREATE TABLE `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `table_id` INT(11) NOT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `customer_name` VARCHAR(200) DEFAULT NULL,
  `customer_mobile` VARCHAR(15) DEFAULT NULL,
  `status` ENUM('placed','preparing','served','cancelled') NOT NULL DEFAULT 'placed',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `table_id` (`table_id`),
  CONSTRAINT `fk_order_table` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: order_items
-- ============================================================
CREATE TABLE `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `menu_item_id` INT(11) NOT NULL,
  `item_name` VARCHAR(200) NOT NULL,
  `item_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending','preparing','served','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orderitem_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: bills
-- ============================================================
CREATE TABLE `bills` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bill_number` VARCHAR(50) NOT NULL,
  `table_id` INT(11) NOT NULL,
  `order_id` INT(11) NOT NULL,
  `customer_name` VARCHAR(200) DEFAULT NULL,
  `customer_mobile` VARCHAR(15) DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('initiated','pending','paid','cancelled') NOT NULL DEFAULT 'initiated',
  `payment_method` ENUM('cash','card','upi','online') DEFAULT 'cash',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_number` (`bill_number`),
  KEY `table_id` (`table_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `fk_bill_table` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bill_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: payments
-- ============================================================
CREATE TABLE `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bill_id` INT(11) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cash','card','upi','online') NOT NULL DEFAULT 'cash',
  `transaction_ref` VARCHAR(100) DEFAULT NULL,
  `paid_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `fk_payment_bill` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
