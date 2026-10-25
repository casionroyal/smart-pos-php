-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 27, 2026 at 10:40 AM
-- Server version: 5.7.24
-- PHP Version: 7.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_phone`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Téléphones'),
(2, 'Accessoires'),
(3, 'Pièces de rechange');

-- --------------------------------------------------------

--
-- Table structure for table `phone_imei`
--

CREATE TABLE `phone_imei` (
  `id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `imei_code` varchar(50) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `status` enum('available','sold') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `phone_imei`
--

INSERT INTO `phone_imei` (`id`, `variant_id`, `imei_code`, `serial_number`, `status`, `created_at`) VALUES
(2, 3, 'SM1SN1319584736DSHF', 'SM1SN101019584736DSHF', 'sold', '2026-09-24 10:10:56'),
(3, 3, 'SM2SN2319584736DSHF', 'SM2SN2020584736DSHF', 'sold', '2026-09-24 10:10:56'),
(4, 3, 'SM3SN3319584736DSHF', 'SM3SN3030584736DSHF', 'available', '2026-09-24 10:10:56'),
(5, 4, 'IM123456789100', 'SN12310987654321', 'available', '2026-09-24 15:39:10'),
(6, 4, 'IM212345678910', 'SN210987654321010', 'sold', '2026-09-24 15:39:10'),
(7, 5, 'IM0011010101010', 'SN0011010101010', 'available', '2026-09-24 15:47:02');

-- --------------------------------------------------------

--
-- Table structure for table `phone_models`
--

CREATE TABLE `phone_models` (
  `id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `phone_models`
--

INSERT INTO `phone_models` (`id`, `brand`, `model_name`, `created_at`) VALUES
(1, 'Samsung', 'Galaxy S25', '2026-09-23 15:13:09'),
(2, 'Iphone', '17 ProMax', '2026-09-23 15:14:15');

-- --------------------------------------------------------

--
-- Table structure for table `phone_units`
--

CREATE TABLE `phone_units` (
  `id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `imei1` varchar(15) NOT NULL,
  `imei2` varchar(15) DEFAULT NULL,
  `status` enum('available','sold','returned') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `phone_variants`
--

CREATE TABLE `phone_variants` (
  `id` int(11) NOT NULL,
  `model_id` int(11) NOT NULL,
  `network` varchar(10) DEFAULT '5G',
  `color` varchar(30) NOT NULL,
  `ram` varchar(20) NOT NULL,
  `storage` varchar(20) NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `stock_qty` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `phone_variants`
--

INSERT INTO `phone_variants` (`id`, `model_id`, `network`, `color`, `ram`, `storage`, `purchase_price`, `selling_price`, `stock_qty`) VALUES
(3, 1, '5G', 'Noir', '16GO', '256GO', '240000.00', '290000.00', 1),
(4, 1, '5G', 'Noir', '16GO', '256GO', '240000.00', '290000.00', 1),
(5, 1, '5G', 'Noir', '16GO', '256GO', '240000.00', '290000.00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `model_name` varchar(150) NOT NULL,
  `buy_price` decimal(10,2) NOT NULL,
  `sell_price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT '0',
  `min_stock` int(11) DEFAULT '2',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `product_imeis`
--

CREATE TABLE `product_imeis` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `imei_code` varchar(100) NOT NULL,
  `status` enum('available','sold') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `sale_type` enum('direct','delivery') NOT NULL,
  `delivery_status` enum('none','pending','delivered','returned') DEFAULT 'none',
  `payment_status` enum('paid','pending','refunded') DEFAULT 'pending',
  `client_name` varchar(100) DEFAULT NULL,
  `client_phone` varchar(20) DEFAULT NULL,
  `client_address` text,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_type`, `delivery_status`, `payment_status`, `client_name`, `client_phone`, `client_address`, `total_amount`, `created_at`) VALUES
(1, 'direct', 'none', 'paid', NULL, NULL, NULL, '290000.00', '2026-09-24 13:12:03'),
(2, 'delivery', 'pending', 'pending', 'Abderrahmane Khellafi', '0554544834', 'City les 40 E.P.L.F birtouta N°24', '290000.00', '2026-09-24 13:13:05'),
(3, 'direct', 'none', 'paid', NULL, NULL, NULL, '290000.00', '2026-09-24 15:39:40');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `imei` varchar(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `variant_id`, `imei`, `quantity`, `unit_price`) VALUES
(1, 1, 3, 'SM2SN2319584736DSHF', 1, '290000.00'),
(2, 2, 3, 'SM1SN1319584736DSHF', 1, '290000.00'),
(3, 3, 4, 'IM212345678910', 1, '290000.00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('superadmin','employe') DEFAULT 'employe',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(6) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `phone`, `role`, `created_at`, `reset_token`, `reset_expires`) VALUES
(1, 'admin', 'f6fdffe48c908deb0f4c3bd36c032e72', 'Administrateur Principal', '0554544834', 'superadmin', '2026-09-23 09:01:51', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `phone_imei`
--
ALTER TABLE `phone_imei`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `imei_code` (`imei_code`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `phone_models`
--
ALTER TABLE `phone_models`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `phone_units`
--
ALTER TABLE `phone_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `imei1` (`imei1`),
  ADD UNIQUE KEY `imei2` (`imei2`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `phone_variants`
--
ALTER TABLE `phone_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `model_id` (`model_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_imeis`
--
ALTER TABLE `product_imeis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `imei_code` (`imei_code`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `phone_imei`
--
ALTER TABLE `phone_imei`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `phone_models`
--
ALTER TABLE `phone_models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `phone_units`
--
ALTER TABLE `phone_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phone_variants`
--
ALTER TABLE `phone_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_imeis`
--
ALTER TABLE `product_imeis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `phone_imei`
--
ALTER TABLE `phone_imei`
  ADD CONSTRAINT `phone_imei_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `phone_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `phone_units`
--
ALTER TABLE `phone_units`
  ADD CONSTRAINT `phone_units_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `phone_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `phone_variants`
--
ALTER TABLE `phone_variants`
  ADD CONSTRAINT `phone_variants_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `phone_models` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_imeis`
--
ALTER TABLE `product_imeis`
  ADD CONSTRAINT `product_imeis_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `phone_variants` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
