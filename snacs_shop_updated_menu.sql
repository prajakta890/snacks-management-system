-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 21, 2026 at 05:34 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `snacs_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `bill_number` varchar(50) NOT NULL,
  `table_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_name` varchar(200) DEFAULT NULL,
  `customer_mobile` varchar(15) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 5.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('initiated','pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','card','upi','online') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_categories`
--

CREATE TABLE `menu_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT '?️',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_categories`
--

INSERT INTO `menu_categories` (`id`, `name`, `icon`, `sort_order`, `is_active`) VALUES
(1, 'Starters', '🥗', 1, 0),
(2, 'Snacks', '🍛', 2, 1),
(3, 'Main Course', '🍟', 3, 0),
(4, 'साऊथ इंडीयन', '☕', 4, 1),
(5, 'Desserts', '🍮', 5, 1),
(6, 'चायनिज', '🫓', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_veg` tinyint(1) NOT NULL DEFAULT 1,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `image`, `is_veg`, `is_available`, `is_deleted`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Veg Spring Rolls', 'Crispy rolls filled with mixed vegetables', 80.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(2, 1, 'Chicken 65', 'Spicy deep fried chicken starter. Kolhapuri taste', 130.00, NULL, 0, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 09:55:28'),
(3, 1, 'Paneer Tikka', 'Grilled cottage cheese with spices', 130.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(4, 1, 'Gobi Manchurian', 'Crispy cauliflower in Indo-Chinese sauce', 100.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(5, 2, 'मिसळपाव', '', 50.00, 'item_6999d32d22902.jpg', 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:45:49'),
(6, 2, 'स्पेशल मिसळ', '', 70.00, 'item_6999d4283ad0e.jpg', 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:50:00'),
(7, 2, 'राईस प्लेट', 'Chapati, Bhaji, Dal Baht', 100.00, 'item_6999d25632274.png', 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:42:14'),
(8, 2, 'पुरीभाजी', '', 50.00, 'item_6999d4f5dc71b.jpg', 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:53:25'),
(9, 2, 'मिसळ चपाती', '', 60.00, 'item_6999d3ddf220c.jpg', 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:48:45'),
(10, 3, 'Samosa (2 pcs)', 'Fried pastry with spiced potato filling', 30.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(11, 3, 'Pav Bhaji', 'Spiced mashed vegetables with buttered buns', 80.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(12, 3, 'Masala Dosa', 'Crispy rice crepe with potato filling', 70.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(13, 3, 'Idli Sambar (3 pcs)', 'Steamed rice cakes with lentil soup', 60.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(14, 3, 'Vada (2 pcs)', 'Crispy lentil donuts', 40.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(15, 3, 'French Fries', 'Crispy golden potato fries with dip', 70.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 13:24:04'),
(16, 4, 'कांदा उतप्पा', '', 60.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:59:40'),
(17, 4, 'स्पंच डोसा', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:58:41'),
(18, 4, 'पेपर डोसा', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:59:00'),
(19, 4, 'साधा डोसा', '', 30.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:59:20'),
(20, 4, 'मसाला डोसा', '', 40.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 15:58:17'),
(21, 5, 'Gulab Jamun (2 pcs)', 'Soft milk dumplings in sugar syrup', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(22, 5, 'Ice Cream', 'Vanilla / Chocolate / Strawberry scoop', 60.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(23, 5, 'Kheer', 'Rice pudding with dry fruits', 70.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 08:38:02'),
(24, 6, 'ट्रिपस राईस', '', 100.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 16:02:16'),
(25, 6, 'ट्रिपस राईस हाफ', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 16:02:37'),
(26, 6, 'शेजवान राईस', '', 100.00, NULL, 1, 1, 0, 0, '2026-02-21 08:38:02', '2026-02-21 16:03:02'),
(27, 3, 'Vadapav', 'Taste of Vadapav', 20.00, 'item_6999b17d8a1dc.jpeg', 1, 1, 0, 0, '2026-02-21 09:55:59', '2026-02-21 13:22:05'),
(28, 2, 'पावभाजी', '', 40.00, NULL, 1, 1, 0, 0, '2026-02-21 15:53:57', '2026-02-21 15:53:57'),
(29, 2, 'वडा सांबर', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 15:54:15', '2026-02-21 15:54:15'),
(30, 2, 'छोले बटुरे', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 15:54:34', '2026-02-21 15:54:34'),
(31, 2, 'वडापाव', '', 15.00, NULL, 1, 1, 0, 0, '2026-02-21 15:54:52', '2026-02-21 15:54:52'),
(32, 2, 'समोसा', '', 15.00, NULL, 1, 1, 0, 0, '2026-02-21 15:55:07', '2026-02-21 15:55:07'),
(33, 2, 'मंचुरीयन भजी', '', 30.00, NULL, 1, 1, 0, 0, '2026-02-21 15:55:21', '2026-02-21 15:55:21'),
(34, 2, 'कांदा भजी', '', 30.00, NULL, 1, 1, 0, 0, '2026-02-21 15:55:41', '2026-02-21 15:55:41'),
(35, 2, 'पालक भजी', '', 30.00, NULL, 1, 1, 0, 0, '2026-02-21 15:55:56', '2026-02-21 15:55:56'),
(36, 2, 'बटाटा भजी', '', 30.00, NULL, 1, 1, 0, 0, '2026-02-21 15:56:15', '2026-02-21 15:56:15'),
(37, 2, 'पॅटीस', '', 20.00, NULL, 1, 1, 0, 0, '2026-02-21 15:56:29', '2026-02-21 15:56:29'),
(38, 2, 'पोहे', '', 20.00, NULL, 1, 1, 0, 0, '2026-02-21 15:56:46', '2026-02-21 15:56:46'),
(39, 2, 'चहा / ब्लॅक टी', '', 10.00, NULL, 1, 1, 0, 0, '2026-02-21 15:57:02', '2026-02-21 15:57:02'),
(40, 2, 'कॉफी', '', 15.00, NULL, 1, 1, 0, 0, '2026-02-21 15:57:15', '2026-02-21 15:57:15'),
(41, 4, 'ईडली सांबर', '', 40.00, NULL, 1, 1, 0, 0, '2026-02-21 15:59:54', '2026-02-21 16:04:53'),
(42, 4, 'उडीद वडा', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 16:00:15', '2026-02-21 16:05:00'),
(43, 2, 'शेजवान राईस हाफ', '', 50.00, NULL, 1, 0, 1, 0, '2026-02-21 16:03:18', '2026-02-21 16:04:07'),
(44, 6, 'शेजवान राईस हाफ', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 16:03:54', '2026-02-21 16:03:54'),
(45, 6, 'फ्राय राईस', '', 100.00, NULL, 1, 1, 0, 0, '2026-02-21 16:05:29', '2026-02-21 16:05:29'),
(46, 6, 'फ्राय राईस हाफ', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 16:05:50', '2026-02-21 16:05:50'),
(47, 6, 'मंचुरीयन राईस', '', 120.00, NULL, 1, 1, 0, 0, '2026-02-21 16:06:13', '2026-02-21 16:07:42'),
(48, 6, 'मंचुरीयन राईस हाफ', '', 60.00, NULL, 1, 1, 0, 0, '2026-02-21 16:06:34', '2026-02-21 16:07:35'),
(49, 6, 'व्हेज नुडल्स', '', 120.00, NULL, 1, 1, 0, 0, '2026-02-21 16:07:03', '2026-02-21 16:07:03'),
(50, 6, 'व्हेज नुडल्स हाफ', '', 60.00, NULL, 1, 1, 0, 0, '2026-02-21 16:07:19', '2026-02-21 16:07:19'),
(51, 6, 'आख्खा नुडल्स', '', 80.00, NULL, 1, 1, 0, 0, '2026-02-21 16:08:04', '2026-02-21 16:08:04'),
(52, 6, 'आख्खा नुडल्स हाफ', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 16:08:22', '2026-02-21 16:08:22'),
(53, 6, 'व्हेज मंचा सुप', '', 40.00, NULL, 1, 1, 0, 0, '2026-02-21 16:08:42', '2026-02-21 16:08:42'),
(54, 6, 'ग्रेवी मंचुरीयन', '', 50.00, NULL, 1, 1, 0, 0, '2026-02-21 16:09:01', '2026-02-21 16:09:01');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_name` varchar(200) DEFAULT NULL,
  `customer_mobile` varchar(15) DEFAULT NULL,
  `status` enum('placed','preparing','served','cancelled') NOT NULL DEFAULT 'placed',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `status` enum('pending','preparing','served','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','upi','online') NOT NULL DEFAULT 'cash',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_number` varchar(20) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `status` enum('available','occupied','reserved') NOT NULL DEFAULT 'available',
  `floor` varchar(50) DEFAULT 'Ground Floor',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `capacity`, `status`, `floor`, `is_active`, `created_at`) VALUES
(1, 'T01', 4, 'available', 'Ground Floor', 1, '2026-02-21 08:38:02'),
(2, 'T02', 4, 'available', 'Ground Floor', 1, '2026-02-21 08:38:02'),
(3, 'T03', 6, 'available', 'Ground Floor', 1, '2026-02-21 08:38:02'),
(4, 'T04', 2, 'available', 'Ground Floor', 1, '2026-02-21 08:38:02'),
(5, 'T05', 4, 'available', 'Ground Floor', 1, '2026-02-21 08:38:02'),
(6, 'T06', 4, 'available', 'First Floor', 1, '2026-02-21 08:38:02'),
(7, 'T07', 6, 'available', 'First Floor', 1, '2026-02-21 08:38:02'),
(8, 'T08', 4, 'available', 'First Floor', 1, '2026-02-21 08:38:02'),
(9, 'T09', 2, 'available', 'First Floor', 1, '2026-02-21 08:38:02'),
(10, 'T10', 8, 'available', 'First Floor', 1, '2026-02-21 08:38:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `is_active`, `created_at`) VALUES
(1, 'admin', 'admin@snacsshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 1, '2026-02-21 08:38:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bill_number` (`bill_number`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_number` (`table_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `menu_categories`
--
ALTER TABLE `menu_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `fk_bill_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bill_table` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_table` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitem_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_bill` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
