-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 06, 2025 at 06:30 PM
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
-- Database: `homemade_food_delivery`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `food_id` int(11) DEFAULT NULL,
  `chef_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `customer_id`, `food_id`, `chef_id`, `quantity`, `added_at`) VALUES
(1, 13, 16, 6, 1, '2025-04-29 07:20:26'),
(2, 13, 18, 1, 1, '2025-04-29 07:40:42'),
(3, 13, 14, 7, 1, '2025-04-29 08:23:11'),
(6, 14, 15, 6, 1, '2025-05-01 13:42:26'),
(12, 8, 34, 1, 1, '2025-05-02 14:54:12'),
(13, 8, 14, 7, 1, '2025-05-02 15:09:14'),
(14, 8, 32, 15, 2, '2025-05-02 15:09:17'),
(15, 8, 31, 15, 1, '2025-05-02 15:09:21'),
(16, 8, 28, 15, 2, '2025-05-02 15:43:40'),
(17, 8, 18, 1, 1, '2025-05-02 15:47:06'),
(21, 8, 11, 1, 2, '2025-05-02 17:24:17'),
(22, 8, 20, 15, 1, '2025-05-02 17:24:34'),
(23, 8, 35, 1, 2, '2025-05-02 17:25:20'),
(24, 8, 27, 15, 1, '2025-05-02 17:25:45'),
(48, 16, 34, 1, 1, '2025-05-09 06:52:40'),
(51, 17, 35, 1, 2, '2025-05-09 13:09:37'),
(53, 16, 35, 1, 1, '2025-05-09 13:10:47'),
(54, 17, 11, 1, 3, '2025-05-09 13:20:23'),
(55, 17, 17, 1, 1, '2025-05-09 13:36:48'),
(56, 16, 11, 1, 2, '2025-05-09 15:56:44'),
(57, 16, 17, 1, 1, '2025-05-09 15:57:07'),
(58, 16, 36, 1, 1, '2025-05-10 16:17:32'),
(60, 14, 34, 1, 1, '2025-05-10 16:42:34'),
(61, 14, 36, 1, 1, '2025-05-10 16:42:41');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `chef_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `order_id`, `customer_id`, `chef_id`, `rating`, `comment`, `created_at`) VALUES
(1, 29, 14, 15, 5, 'good food', '2025-05-09 10:41:30'),
(2, 30, 14, 15, 5, 'not so good', '2025-05-09 11:04:22'),
(3, 28, 16, 15, 5, 'hfghfghfgh', '2025-05-09 14:06:05'),
(4, 25, 16, 1, 4, 'gyhfghfghf', '2025-05-09 14:07:32'),
(5, 35, 14, 1, 4, 'Fresh In Quality , Lets give it a try', '2025-05-10 16:43:55'),
(6, 34, 14, 1, 5, 'Top Rated Top Qualty', '2025-05-10 16:44:17');

-- --------------------------------------------------------

--
-- Table structure for table `food_items`
--

CREATE TABLE `food_items` (
  `id` int(11) NOT NULL,
  `chef_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `preparation_time` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_items`
--

INSERT INTO `food_items` (`id`, `chef_id`, `name`, `description`, `price`, `preparation_time`, `image`, `category`, `is_available`, `created_at`, `updated_at`, `quantity`) VALUES
(11, 1, 'Lacchi', 'Juicy', 280.00, 10, '680a07330d315_lacchi.jpg', 'nonveg', 1, '2025-04-24 04:18:29', '2025-04-24 09:41:07', 10),
(12, 1, 'Lemon MInt', 'Juicy', 90.00, 10, '680a07289508a_lemon meant.jfif', 'juicy', 0, '2025-04-24 04:25:22', '2025-04-24 14:33:00', 10),
(13, 7, 'Meat', 'Fresh meat', 500.00, 30, '680a030779e33_meat.jfif', 'nonveg', 0, '2025-04-24 09:23:19', '2025-04-24 09:23:41', 5),
(14, 7, 'lemon meant', 'juice', 100.00, 10, '680a0347aaab3_lemon meant.jfif', 'juicy', 1, '2025-04-24 09:24:23', '2025-04-24 09:24:23', 4),
(15, 6, 'Meat', 'Fresh Meat', 500.00, 25, '680a0661cc180_meat.jfif', 'nonveg', 1, '2025-04-24 09:37:37', '2025-04-24 09:37:37', 5),
(16, 6, 'Kacchi', 'Taste the best', 400.00, 27, '680a06a47acc1_kacchi.jfif', 'nonveg', 1, '2025-04-24 09:38:44', '2025-04-24 09:38:44', 4),
(17, 1, 'Kacchi', 'tasty', 400.00, 27, '680a4b456fc70_kacchi.jfif', 'nonveg', 1, '2025-04-24 14:31:33', '2025-04-24 14:31:33', 4),
(18, 1, 'Meat', 'Fresh Meat', 500.00, 25, '680a4b5fe69a4_meat.jfif', 'veg', 1, '2025-04-24 14:31:59', '2025-04-24 14:31:59', 5),
(19, 15, 'Beaf', 'Mojadar', 200.00, 30, '68134e0301388_bearf.jpg', 'nonveg', 1, '2025-05-01 10:33:39', '2025-05-01 10:33:39', 500),
(20, 15, 'Juice', 'cold', 100.00, 20, '68134e3cd51f2_juice.jpg', 'juicy', 1, '2025-05-01 10:34:36', '2025-05-01 10:34:36', 2),
(21, 15, 'Tehari', 'Mojior', 150.00, 40, '68134e9b22b34_hh.jpg', 'nonveg', 1, '2025-05-01 10:36:11', '2025-05-01 10:36:11', 2),
(22, 15, 'Morgi', 'rosehleo', 190.00, 35, '68134ee803215_morgi.jpg', 'nonveg', 0, '2025-05-01 10:37:28', '2025-05-01 12:15:09', 2),
(23, 15, 'Hot Morgi', 'more hot', 213.00, 34, '6813571eee120_morgi.jpg', 'nonveg', 1, '2025-05-01 11:12:30', '2025-05-01 11:12:30', 2),
(24, 15, 'Juice 2', 'Juice cold', 2133.00, 33, '6813576296bd3_juice.jpg', 'juicy', 1, '2025-05-01 11:13:38', '2025-05-01 11:13:38', 3),
(25, 15, 'Beaf', 'beaf ff', 342.00, 45, '68135780e18de_bearf.jpg', 'nonveg', 1, '2025-05-01 11:14:08', '2025-05-01 11:14:08', 3),
(26, 15, 'Tehari 33', 'therairfi', 225.00, 55, '681357ae7f708_hh.jpg', 'nonveg', 0, '2025-05-01 11:14:54', '2025-05-01 12:01:54', 5),
(27, 15, 'Juice 4', 'jiuciesf', 434.00, 33, '681357d338842_juice.jpg', 'juicy', 1, '2025-05-01 11:15:31', '2025-05-01 11:15:31', 3),
(28, 15, 'New', 'sdjfsf', 333.00, 22, '6813664ae2fc2_bearf.jpg', 'veg', 1, '2025-05-01 12:17:14', '2025-05-01 12:17:14', 3),
(29, 15, 'dfsdsdfs', 'dfsf', 3333.00, 55, '68136984d8e60_juice.jpg', 'veg', 1, '2025-05-01 12:31:00', '2025-05-01 12:31:00', 3),
(30, 15, 'zdefssdf', 'sdf', 444.00, 34, '681369a25f6f1_morgi.jpg', 'veg', 1, '2025-05-01 12:31:30', '2025-05-01 12:31:30', 4),
(31, 15, 'dfgd', 'dgd', 333.00, 34, '681369c35a49b_hh.jpg', 'veg', 1, '2025-05-01 12:32:03', '2025-05-01 12:32:03', 5),
(32, 15, 'fg', 'asd', 22.00, 33, '681369df919b3_juice.jpg', 'veg', 1, '2025-05-01 12:32:31', '2025-05-01 12:32:31', 12),
(33, 15, 'aee', 'dfs', 222.00, 44, '68136a650aa20_bearf.jpg', 'nonveg', 1, '2025-05-01 12:34:45', '2025-05-01 12:34:45', 2),
(34, 1, 'Handi Biriyani', 'Tasty', 180.00, 20, '68139dcc21944_handi.jfif', 'nonveg', 1, '2025-05-01 16:14:04', '2025-05-01 16:14:04', 20),
(35, 1, 'Egg Bhuna', 'Curry tasty', 40.00, 10, '6814e14944a50_egg bhuna.jfif', 'nonveg', 1, '2025-05-01 16:14:46', '2025-05-02 15:14:17', 15),
(36, 1, 'Dry Keema', 'Wowwwwwwwww', 160.00, 40, '68139e200b817_dry keema.jfif', 'nonveg', 1, '2025-05-01 16:15:28', '2025-05-01 16:15:28', 19),
(37, 6, 'Egg Bhuna', 'Curry', 40.00, 20, '6813aa2e24ded_egg bhuna.jfif', 'nonveg', 1, '2025-05-01 17:06:54', '2025-05-01 17:06:54', 5),
(39, 1, 'chuijhal', 'spicy', 180.00, 2, '68938132206de_chuijhal.jfif', 'nonveg', 1, '2025-08-06 16:22:10', '2025-08-06 16:22:10', 5);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `delivery_address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `total_amount`, `status`, `delivery_address`, `created_at`, `updated_at`) VALUES
(1, 13, 1000.00, 'pending', 'Gausia', '2025-04-29 07:41:24', '2025-04-29 07:41:24'),
(2, 13, 1000.00, 'pending', 'Gausia', '2025-04-29 07:43:34', '2025-04-29 07:43:34'),
(3, 13, 1000.00, 'pending', 'Gausia', '2025-04-29 07:46:17', '2025-04-29 07:46:17'),
(4, 13, 1000.00, 'pending', 'Gausia', '2025-04-29 07:46:21', '2025-04-29 07:46:21'),
(5, 13, 1500.00, 'pending', 'Kanchan', '2025-04-29 07:46:58', '2025-04-29 07:46:58'),
(6, 8, 12798.00, 'confirmed', 'Matuail', '2025-05-01 16:48:31', '2025-05-01 16:48:31'),
(7, 8, 480.00, 'confirmed', 'Matuail', '2025-05-01 16:53:11', '2025-05-01 16:53:11'),
(9, 8, 150.00, 'confirmed', 'Matuail', '2025-05-02 16:41:24', '2025-05-02 16:41:24'),
(10, 8, 500.00, 'confirmed', 'Matuail', '2025-05-02 17:10:30', '2025-05-02 17:10:30'),
(11, 8, 434.00, 'confirmed', 'Kusabo', '2025-05-02 17:12:46', '2025-05-02 17:12:46'),
(12, 8, 213.00, 'confirmed', 'Matuail', '2025-05-07 17:56:37', '2025-05-07 17:56:37'),
(13, 16, 160.00, 'confirmed', 'Matuail', '2025-05-08 07:43:28', '2025-05-08 07:43:28'),
(14, 16, 160.00, 'confirmed', 'Matuail', '2025-05-08 07:58:59', '2025-05-08 07:58:59'),
(15, 16, 500.00, 'confirmed', 'Matuail', '2025-05-08 10:44:00', '2025-05-08 10:44:00'),
(16, 16, 280.00, 'confirmed', 'Matuail', '2025-05-08 10:52:58', '2025-05-08 10:52:58'),
(17, 16, 160.00, 'confirmed', 'Matuail', '2025-05-08 10:59:01', '2025-05-08 10:59:01'),
(18, 16, 180.00, 'confirmed', 'Matuail', '2025-05-08 11:03:37', '2025-05-08 11:03:37'),
(19, 16, 400.00, 'confirmed', 'Matuail', '2025-05-09 04:47:49', '2025-05-09 04:47:49'),
(20, 16, 4305.00, 'confirmed', 'Dhanmondi', '2025-05-09 05:41:04', '2025-05-09 05:41:04'),
(21, 16, 213.00, 'confirmed', 'Matuail', '2025-05-09 05:43:36', '2025-05-09 05:43:36'),
(22, 16, 680.00, 'confirmed', 'Matuail', '2025-05-09 06:02:29', '2025-05-09 06:02:29'),
(23, 16, 430.00, 'confirmed', 'Matuail', '2025-05-09 06:04:59', '2025-05-09 06:04:59'),
(24, 16, 100.00, 'confirmed', 'Matuail', '2025-05-09 06:14:17', '2025-05-09 06:14:17'),
(25, 16, 280.00, 'confirmed', 'Matuail', '2025-05-09 06:15:18', '2025-05-09 06:15:18'),
(26, 16, 280.00, 'confirmed', 'Matuail', '2025-05-09 06:39:17', '2025-05-09 06:39:17'),
(27, 16, 213.00, 'confirmed', 'Matuail', '2025-05-09 06:50:04', '2025-05-09 06:50:04'),
(28, 16, 2133.00, 'confirmed', 'Matuail', '2025-05-09 06:52:11', '2025-05-09 06:52:11'),
(29, 14, 434.00, 'confirmed', 'kushabo', '2025-05-09 09:54:53', '2025-05-09 09:54:53'),
(30, 14, 222.00, 'confirmed', 'Matuail', '2025-05-09 11:03:34', '2025-05-09 11:03:34'),
(31, 17, 800.00, 'confirmed', 'Matuail', '2025-05-09 13:49:41', '2025-05-09 13:49:41'),
(32, 16, 640.00, 'confirmed', 'Matuail', '2025-05-09 14:19:38', '2025-05-09 14:19:38'),
(33, 17, 180.00, 'confirmed', 'Matuail', '2025-05-09 14:57:05', '2025-05-09 14:57:05'),
(34, 14, 400.00, 'confirmed', 'Matuail', '2025-05-10 16:41:46', '2025-05-10 16:41:46'),
(35, 14, 500.00, 'confirmed', 'Matuail', '2025-05-10 16:42:58', '2025-05-10 16:42:58');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `food_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `food_item_id`, `quantity`, `price`) VALUES
(6, 6, 24, 6, 2133.00),
(7, 7, 36, 3, 160.00),
(9, 9, 21, 1, 150.00),
(10, 10, 15, 1, 500.00),
(11, 11, 27, 1, 434.00),
(12, 12, 23, 1, 213.00),
(13, 19, 17, 1, 400.00),
(14, 20, 33, 1, 222.00),
(15, 20, 29, 1, 3333.00),
(16, 20, 21, 1, 150.00),
(17, 20, 18, 1, 500.00),
(18, 20, 20, 1, 100.00),
(19, 21, 23, 1, 213.00),
(20, 22, 11, 1, 280.00),
(21, 22, 16, 1, 400.00),
(22, 23, 14, 1, 100.00),
(23, 23, 34, 1, 180.00),
(24, 23, 21, 1, 150.00),
(25, 24, 14, 1, 100.00),
(26, 25, 11, 1, 280.00),
(27, 26, 11, 1, 280.00),
(28, 27, 23, 1, 213.00),
(29, 28, 24, 1, 2133.00),
(30, 29, 27, 1, 434.00),
(31, 30, 33, 1, 222.00),
(32, 31, 36, 5, 160.00),
(33, 32, 36, 4, 160.00),
(34, 33, 34, 1, 180.00),
(35, 34, 17, 1, 400.00),
(36, 35, 18, 1, 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `transaction_id`, `created_at`) VALUES
(1, 6, 'bkash', '123456', '2025-05-01 16:48:31'),
(2, 7, 'bkash', '12345', '2025-05-01 16:53:11'),
(4, 9, 'bkash', '123456', '2025-05-02 16:41:24'),
(5, 10, 'rocket', '123456', '2025-05-02 17:10:30'),
(6, 11, 'nagad', '123456', '2025-05-02 17:12:46'),
(7, 12, 'bkash', '12345', '2025-05-07 17:56:37'),
(8, 13, 'stripe', 'cs_test_a16TLGEwYvwY1nwVM4f8aUiyepzjgS5uweaSdw37osEwgsd1Tjp1IzeAog', '2025-05-08 07:43:28'),
(9, 14, 'stripe', 'cs_test_a1IJc0ZAGlGU5AYl0kudQDLY9FOORKWqjcaKl8sGCLLKtdOvxMJ2IdnvoP', '2025-05-08 07:58:59'),
(10, 15, 'stripe', 'cs_test_b1kmupEmfABPBhJDiFQKo6cNZp7sgavg734ziTUJDOfh2pAzIR5ZXdDDjX', '2025-05-08 10:44:00'),
(11, 16, 'stripe', 'cs_test_a1GMCvboiJQuQT9vuZSQIBnmeY0nTeZZmrXpB4sT5BX0ZcwvHSMJ4WCZUK', '2025-05-08 10:52:58'),
(12, 17, 'stripe', 'cs_test_a1yTzHFRmHOAwgQjuAoPJ3XEIurzscl8qjGBOSC1lYf5OdhR8cU4WnNfaF', '2025-05-08 10:59:01'),
(13, 18, 'stripe', 'cs_test_a1lI1SKPdjePZ8yc82LUkkJuO5CYBCYvCy6nHbvY2X4P2x4HlSjCYO3Lac', '2025-05-08 11:03:37'),
(14, 19, 'rocket', '123456', '2025-05-09 04:47:49'),
(15, 20, 'stripe', 'cs_test_b1HoCDbGSSOXnnU2NACkTZ33kC6vKukXvV8r6jBIBCsGzBAgubaZ5ZtpoE', '2025-05-09 05:41:04'),
(16, 21, 'stripe', 'cs_test_a1DXFdT2DaRcIOtw6YCsaMShAlL4kT2YP5FnrFKINoDV8sIrhfbUEiIQ5z', '2025-05-09 05:43:36'),
(17, 22, 'stripe', 'cs_test_b1ijTD7nkbk0RmugAEWvmQlaS1YieRvmc0JI29Xi0lfF8Og9Qkf3FRBBzk', '2025-05-09 06:02:29'),
(18, 23, 'stripe', 'cs_test_b1BD7vtynxPC009GShWwlaArZmTrCt5Tb7q5h7Dj5aBYveSsfKE2Wj1dGe', '2025-05-09 06:04:59'),
(19, 24, 'rocket', '12345', '2025-05-09 06:14:17'),
(20, 25, 'stripe', 'cs_test_a1InBdSqPZjrEgRISySqJQm9AQVWq26F0DWQBXkAwmzlkRni0i8l1aUSDX', '2025-05-09 06:15:18'),
(21, 26, 'rocket', '12345', '2025-05-09 06:39:17'),
(22, 27, 'stripe', 'cs_test_a1RWlqdzZ8kfeOpIr8ztHoMRB4bhzsXuYfDmpq2GZB2eEw31PGSRY49CtK', '2025-05-09 06:50:04'),
(23, 28, 'stripe', 'cs_test_a1Nli2dMDoxHITt5cCtwCSbobvxGNmcgTFHuTUiP1y3CyD9lC5YiyplLtM', '2025-05-09 06:52:11'),
(24, 29, 'stripe', 'cs_test_a1DCVhUkmCpjMBwJ32AxClWfmzlszJuO7gngH5zs9yuo3ky8JGAHATbdF2', '2025-05-09 09:54:53'),
(25, 30, 'stripe', 'cs_test_a1iFSZL5bnf3VdEKehj7v7FJdz17O1ZK0N8tcNOJb2bUlAQG9aG8rrInKU', '2025-05-09 11:03:34'),
(26, 31, 'stripe', 'cs_test_a135AMIjpjPHKTdmsUZWK75XHKNmJR8SS0ARm0rrbrevdoKKcGOC11yqLC', '2025-05-09 13:49:41'),
(27, 32, 'stripe', 'cs_test_a1OUHBIOyNHiaRStEnRQgJWOczNvjAJSXB4X6EsbZIPCIWk77wZ9HfVssx', '2025-05-09 14:19:38'),
(28, 33, 'stripe', 'cs_test_a1ZAYRF66zY7M0tonwtPCIWECXWUidEc8oGltii8fBoKWzEIOFyjd5rTMd', '2025-05-09 14:57:05'),
(29, 34, 'stripe', 'cs_test_a1YVBjP3qEVjZMT3tvX8zgvzpoZ950PgZMZp8T6Fcpb4OMd2uqPiXJvmCQ', '2025-05-10 16:41:46'),
(30, 35, 'nagad', '12345', '2025-05-10 16:42:58');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `food_item_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('customer','chef','admin') NOT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `token_created_at` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `user_type`, `verification_code`, `is_verified`, `created_at`, `updated_at`, `reset_token`, `token_created_at`, `profile_image`, `location`) VALUES
(1, 'Sayeb', 'sayebahmed1234@gmail.com', '01515215020', 'Matuail', '$2y$10$qU3J161kHNkp/irIAjd.h.x56CBUJPojyxBjcFEjTZcq2kovgkcZO', 'chef', NULL, 1, '2025-04-07 06:43:45', '2025-08-06 16:20:34', NULL, NULL, NULL, 'Matuail'),
(3, 'Samiya', 'samiyamirirya2002@gmail.com', '01892754533', 'Banti', '$2y$10$4MuI3x7Q1F4GaC0cRYUsHOte6PGEolOdv9HxTC25pTsU3M8ICWlAe', 'customer', '799261', 0, '2025-04-07 07:51:45', '2025-04-07 07:51:45', NULL, NULL, NULL, NULL),
(4, 'Abir', 'shantomia069@gmail.com', '01913488929', 'Gawsia', '$2y$10$oMUeLA4D4O1zzWcDjh2rW.nbXM3Tu7dIXtt6hyz/o0BfJ9M0DMZoe', 'chef', NULL, 1, '2025-04-22 08:45:58', '2025-04-24 06:22:18', NULL, NULL, NULL, 'Gawsia'),
(5, 'Sakib Al Hasan', 'shakibhassan486@gmail.com', '01919386711', 'Bikrampur', '$2y$10$x3hLjg12dKwPs7cskeqjn.tHahb54ScrZruw59W2rUfNMUFL5ibu6', 'chef', NULL, 1, '2025-04-24 04:15:12', '2025-04-24 04:16:06', NULL, NULL, NULL, NULL),
(6, 'Jubaer', 'mdsayeb17@gmail.com', '01706535145', 'Dhaka,Matuail', '$2y$10$Oml1JO6qF2Zu6UEYBBa9jOjeX09iq5mKxgC7smRfMexs4QWWGBUpa', 'chef', NULL, 1, '2025-04-24 05:40:51', '2025-05-01 17:06:06', NULL, NULL, NULL, NULL),
(7, 'Ubaida', 'samiyamirriya@gmail.com', '01892754533', 'Banti,Araihazar,Narayanganj', '$2y$10$rS8hTZpwnD8wawG2iy3bgOne3ChTb/VY2Db4AYK.GReRtjKae/bo6', 'chef', NULL, 1, '2025-04-24 06:57:47', '2025-05-02 16:28:44', NULL, NULL, '6809e0eb1e71e_WhatsApp Image 2025-01-30 at 22.03.10.jpeg', NULL),
(8, 'Shipon Miah', 'jahidul123ami@gmail.com', '01984745679', 'Kushabo,Narayanganj', '$2y$10$p7e03sU7UGWwJd3E9V3byOFmWycliDDmex6NP4tnmDG151UfQnOVm', 'customer', NULL, 1, '2025-04-24 09:29:15', '2025-05-08 05:41:28', NULL, NULL, NULL, NULL),
(9, 'Rofiqul Islam', 'sakibkhan123ami@gmail.com', '01984745679', 'Kushabo,Narayanganj', '$2y$10$WiCjiLH4/alO.fyasG30o.TXzwvynjIu0oGTLWerU7tr.brkhgN8q', 'customer', '262985', 0, '2025-04-24 17:41:08', '2025-04-24 17:42:07', NULL, NULL, NULL, NULL),
(10, 'Shanto Mia', 'bpshanto1@gmail.com', '01913488929', 'Gausia', '$2y$10$hyg9G0It8vNaU4XzK0YyK.3Bcpiqz5KjWCkZFoXDFPHZP4XUui.FC', 'customer', NULL, 1, '2025-04-29 04:56:13', '2025-04-29 05:35:22', NULL, NULL, NULL, NULL),
(11, 'Shanto Mia', '213902029@student.green.edu.bd', '01913488929', 'Gausia', '$2y$10$pmt8SS4Tkvw6AurwMVGLye6Rmz4n1ZfhKuwXgbWBhdvtctHsJv4Sa', 'chef', NULL, 1, '2025-04-29 05:00:06', '2025-04-29 05:01:38', NULL, NULL, '68105cd5eae9b_boy.jpg', NULL),
(12, 'Samiya Akter', 's9551545@gmail.com', '01706535145', 'banti', '$2y$10$VDTC25ToHZDNj5ztGWuRTOmxanjjGSVHAQDySgcnU1Zfwvw2HF3l.', 'customer', NULL, 1, '2025-04-29 05:49:18', '2025-04-29 06:10:20', NULL, NULL, NULL, NULL),
(13, 'Joshim', 'spshipon123ami@gmail.com', '01706535145', 'Kanchan', '$2y$10$q3.PU.H4.qpq//.dbk66auFBJGiZT83arFiyfvkdMUaws7yNhZ6t.', 'customer', NULL, 1, '2025-04-29 06:22:34', '2025-04-29 08:22:49', NULL, NULL, '6810702add8cb_WhatsApp Image 2025-04-24 at 23.40.03.jpeg', NULL),
(14, 'Shanto Khan', 'hosay52103@nutrv.com', '01984745603', 'Kanchan', '$2y$10$ig4Il9msL8mYQfsrtSJQFeYSR5YRxCTBySfZ.l8p24JcU8gqp1ZZK', 'customer', NULL, 1, '2025-05-01 10:22:03', '2025-05-10 16:41:17', NULL, NULL, '68134b4be2e7a_240385246_161420049441622_3106164668165083813_n.jpg', NULL),
(15, 'LIpo', 'lipyri@logsmarter.net', '01920147992', 'Narda', '$2y$10$be7aLA5arvSe7gh6SjKS6uYNG5ykNucefKdjjSsE77BaDSyuFMzyW', 'chef', NULL, 1, '2025-05-01 10:31:34', '2025-05-09 11:04:45', NULL, NULL, '68134d86504e1_unnamed.jpg', NULL),
(16, 'Riya', '213902044@student.green.edu.bd', '01892754533', 'Matuail', '$2y$10$EdA8kcL24Yh9KRX/lCryM.Ccq/1Lk0xKiIo88J9rCvqqHHy8wlQny', 'customer', NULL, 1, '2025-05-08 05:47:50', '2025-08-06 16:22:44', NULL, NULL, '681c45861eb8f_samiya.jpeg', NULL),
(17, 'Sayeb', '213902104@student.green.edu.bd', '01515215020', 'Matuail', '$2y$10$eWJouQUzALQPQhlIOMdg6Oks4NkVFuwfUompskZ6VXiPrZxN4rVb.', 'customer', NULL, 1, '2025-05-09 13:08:44', '2025-05-09 14:56:05', NULL, NULL, '681dfe5c274f1_WhatsApp Image 2025-01-30 at 22.03.10.jpeg', NULL),
(18, 'Samiya Mir', 'jarehi7299@neuraxo.com', '01515215020', 'Matuail', '$2y$10$4prBe1BicVxzOCHxaOQRsO3Kf8URFLR4Tk4N55FdgD9toaZDHyrzC', 'chef', NULL, 1, '2025-05-10 16:33:55', '2025-05-10 16:34:28', NULL, NULL, '681f7ff306a24_profile.jpeg', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `chef_id` (`chef_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`,`customer_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `chef_id` (`chef_id`);

--
-- Indexes for table `food_items`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chef_id` (`chef_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `food_item_id` (`food_item_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `food_item_id` (`food_item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `food_items`
--
ALTER TABLE `food_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `food_items` (`id`),
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`chef_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`chef_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `food_items`
--
ALTER TABLE `food_items`
  ADD CONSTRAINT `food_items_ibfk_1` FOREIGN KEY (`chef_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`food_item_id`) REFERENCES `food_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`food_item_id`) REFERENCES `food_items` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
