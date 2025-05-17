-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 12, 2024 at 04:24 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kiyaraplants_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admintb`
--

CREATE TABLE `admintb` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL DEFAULT 'Add a Number',
  `address` varchar(300) NOT NULL DEFAULT 'Add a address',
  `user_image` varchar(10000) NOT NULL DEFAULT 'Add a Photo',
  `age` varchar(20) NOT NULL DEFAULT 'Add age',
  `postal_code` varchar(20) NOT NULL DEFAULT 'Add a Postal Code'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admintb`
--

INSERT INTO `admintb` (`admin_id`, `name`, `email`, `password`, `phone`, `address`, `user_image`, `age`, `postal_code`) VALUES
(1, 'Deshan Dulmina', 'test@gmail.com', '2', '0715634082', '62/B, Kaluwelgoda, Makawita, Ja-ela', 'uploads/dog-feel-music-cute.gif', '30', '10002'),
(2, 'Jane Smith', 'jane.smith@example.com', 'hashed_password_2', '1234567892', '456 Oak St', 'uploads/image-removebg-preview - 2023-11-27T022453.521.png', '30', '10002'),
(3, 'Bob Johnson', 'bob.johnson@example.com', 'hashed_password_3', '1234567893', '789 Elm St', 'image_3.jpg', '35', '10003'),
(4, 'John Doe', 'john.doe@example.com', 'hashed_password_1', '1234567891', '123 Main St', 'image_1.jpg', '25', '10001'),
(55, 'Alice Brown', 'alice.brown@example.com', 'hashed_password_55', '1234567945', '987 Pine St', 'image_55.jpg', '22', '10055');

-- --------------------------------------------------------

--
-- Table structure for table `allorders`
--

CREATE TABLE `allorders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `plant_id` int(11) NOT NULL,
  `plant_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) DEFAULT NULL,
  `shipping_status` int(11) DEFAULT NULL,
  `update_id` int(11) DEFAULT NULL,
  `update_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_description` varchar(255) DEFAULT NULL,
  `updated_by_admin` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `plant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`) VALUES
(1, 'Indoor Plants'),
(2, 'Outdoor Plants'),
(3, 'Succulents'),
(4, 'Herbs'),
(5, 'Flowering Plants');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `age` varchar(11) DEFAULT 'Add age',
  `postal_code` varchar(20) DEFAULT 'Add Postal Code'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `email`, `password`, `address`, `phone`, `profile_image`, `age`, `postal_code`) VALUES
(23, 'Deshan Dulmina', 'deshandulmina30840@gmail.com', '1', '62/B,kaluwelgoda,makawita,ja-ela', '+94713618282', 'uploads/dog-feel-music-cute.gif', '26', '356'),
(24, 'Randi Wasana', 'kiyaraplants@gmail.com', '5', 'No.5/4\'Jayani\'Dinugardens', '0719112495', 'uploads/image-removebg-preview - 2023-11-27T022453.521.png', '30', 'Add Postal Code'),
(25, 'KC', 'kc@gamil.com', '3', 'Kelani Waththa, Thumpane', '119', '', 'Add age', 'Add Postal Code');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_status` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `detail_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `plant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_status` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plants`
--

CREATE TABLE `plants` (
  `plant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `pot_type` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `img_url` varchar(500) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `watering` varchar(100) DEFAULT NULL,
  `light` varchar(100) DEFAULT NULL,
  `benefits` varchar(100) DEFAULT NULL,
  `animal_friendly` varchar(100) DEFAULT NULL,
  `wanna_know_more` varchar(100) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 CHECK (`status` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plants`
--

INSERT INTO `plants` (`plant_id`, `name`, `pot_type`, `description`, `img_url`, `price`, `stock`, `watering`, `light`, `benefits`, `animal_friendly`, `wanna_know_more`, `origin`, `status`) VALUES
(1, 'Snake Plant', 'Ceramic Pot', 'Known for its striking appearance and air-purifying qualities, the Snake Plant is an ideal houseplant.', 'contecnt/img/Prodcuts/image-removebg-preview - 2023-10-25T092329.340.png', 1400.00, 50, 'Low', 'Indirect sunlight', 'Improves air quality, Low maintenance', 'Pet-friendly', 'Prefers dry conditions, occasional watering', 'West Africa', 1),
(2, 'Peace Lily', 'Ceramic Pot', 'A beautiful flowering plant that thrives in shade, producing white blooms and filtering toxins from the air.', 'contecnt/img/Prodcuts/image-removebg-preview - 2023-10-25T092328.056.png', 1200.00, 35, 'Moderate', 'Indirect sunlight', 'Air purification, Peaceful appearance', 'Toxic to pets if ingested', 'Prefers high humidity', 'South America', 1),
(3, 'Fiddle Leaf Fig', 'Ceramic Pot', 'Featuring large, violin-shaped leaves, the Fiddle Leaf Fig is a trendy indoor plant that requires bright, indirect light.', 'contecnt/img/Prodcuts/image-removebg-preview - 2023-10-25T092324.999.png', 1600.00, 20, 'Moderate', 'Bright, indirect light', 'Attractive foliage, Stylish addition to spaces', 'Toxic to pets if ingested', 'Regular watering, avoid overwatering', 'West Africa', 1),
(4, 'Monstera Deliciosa', 'Ceramic Pot', 'Recognizable by its unique leaf splits, the Monstera Deliciosa is a tropical plant that adds a lush vibe to any room.', 'contecnt/img/Prodcuts/image-removebg-preview - 2023-10-25T092729.204.png', 1150.00, 25, 'Low', 'Indirect light', 'Unique appearance, Easy to care for', 'Toxic to pets if ingested', 'Requires occasional pruning', 'Central America', 1),
(5, 'Rose', 'Terracotta', 'Beautiful flowering plant', 'contecnt/img/Prodcuts/image-removebg-preview - 2023-10-25T091921.623.png', 1500.00, 25, 'Regular', 'Full sun to partial shade', 'Aesthetic appeal and fragrance', 'Yes', 'For more details...', 'Europe', 1),
(6, 'Spider Plant', 'Plastic', 'Air-purifying plant with spider-like leaves', 'contecnt/img/Prodcuts/image-removebg-preview - 2023-10-25T092324.999.png', 1280.00, 30, 'Low to moderate', 'Indirect light', 'Improves indoor air quality', 'Yes', 'Check out more info', 'South Africa', 1),
(7, 'Fiddle Leaf Fig', 'Ceramic', 'Tall indoor plant with violin-shaped leaves', 'contecnt/img/Prodcuts/image-removebg-preview - 2023-10-25T092328.056.png', 900.00, 10, 'Moderate', 'Bright indirect light', 'Adds a statement to interiors', 'Yes', 'Explore further', 'Western Africa', 0),
(8, 'Money Plant', 'Clay', 'Believed to bring good luck and prosperity', 'contecnt/img/Prodcuts/image-removebg-preview - 2023-10-25T092729.204.png', 1200.00, 40, 'Minimal', 'Indirect light', 'Symbol of wealth and luck', 'Yes', 'Learn more about benefits', 'Asia', 1),
(9, 'Snake Plant', 'Metal', 'Tall plant with long, sword-shaped leaves', 'contecnt/img/Prodcuts/image-removebg-preview-2023-10-25T092328.056.png', 1100.00, 20, 'Minimal', 'Low to bright indirect light', 'Improves air quality', 'Yes', 'Discover more about care', 'Africa', 0);

-- --------------------------------------------------------

--
-- Table structure for table `plant_category`
--

CREATE TABLE `plant_category` (
  `plant_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plant_category`
--

INSERT INTO `plant_category` (`plant_id`, `category_id`) VALUES
(1, 2),
(2, 3),
(3, 1),
(4, 5);

-- --------------------------------------------------------

--
-- Table structure for table `plant_images`
--

CREATE TABLE `plant_images` (
  `image_id` int(11) NOT NULL,
  `plant_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plant_images`
--

INSERT INTO `plant_images` (`image_id`, `plant_id`, `image_url`) VALUES
(1, 5, 'Prodcuts/image-removebg-preview-2023-10-25T091921.623.png'),
(2, 6, 'Prodcuts/image-removebg-preview-2023-10-25T092324.999.png'),
(3, 7, 'contecnt/img/Prodcuts/image-removebg-preview-2023-10-25T092328.056.png'),
(4, 8, 'contecnt/img/Prodcuts/image-removebg-preview-2023-10-25T092329.340.png');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_updates`
--

CREATE TABLE `shipping_updates` (
  `update_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `update_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_description` varchar(255) DEFAULT NULL,
  `updated_by_admin` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admintb`
--
ALTER TABLE `admintb`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `allorders`
--
ALTER TABLE `allorders`
  ADD PRIMARY KEY (`order_id`,`plant_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `plant_id` (`plant_id`),
  ADD KEY `update_id` (`update_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `plant_id` (`plant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `plant_id` (`plant_id`);

--
-- Indexes for table `plants`
--
ALTER TABLE `plants`
  ADD PRIMARY KEY (`plant_id`);

--
-- Indexes for table `plant_category`
--
ALTER TABLE `plant_category`
  ADD PRIMARY KEY (`plant_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `plant_images`
--
ALTER TABLE `plant_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `plant_id` (`plant_id`);

--
-- Indexes for table `shipping_updates`
--
ALTER TABLE `shipping_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `order_id` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admintb`
--
ALTER TABLE `admintb`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plants`
--
ALTER TABLE `plants`
  MODIFY `plant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `plant_images`
--
ALTER TABLE `plant_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shipping_updates`
--
ALTER TABLE `shipping_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `allorders`
--
ALTER TABLE `allorders`
  ADD CONSTRAINT `allorders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `allorders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `allorders_ibfk_3` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`),
  ADD CONSTRAINT `allorders_ibfk_4` FOREIGN KEY (`update_id`) REFERENCES `shipping_updates` (`update_id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`);

--
-- Constraints for table `plant_category`
--
ALTER TABLE `plant_category`
  ADD CONSTRAINT `plant_category_ibfk_1` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`),
  ADD CONSTRAINT `plant_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `plant_images`
--
ALTER TABLE `plant_images`
  ADD CONSTRAINT `plant_images_ibfk_1` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`);

--
-- Constraints for table `shipping_updates`
--
ALTER TABLE `shipping_updates`
  ADD CONSTRAINT `shipping_updates_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
