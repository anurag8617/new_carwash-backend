-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2025 at 12:53 AM
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
-- Database: `carwash_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `client_subscriptions`
--

CREATE TABLE `client_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `remaining_services` int(11) NOT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_subscriptions`
--

INSERT INTO `client_subscriptions` (`id`, `client_id`, `plan_id`, `vendor_id`, `start_date`, `end_date`, `remaining_services`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 1, '2025-10-16', '2025-10-31', 2, 'active', '2025-10-16 11:32:41', '2025-10-16 11:32:41'),
(2, 2, 4, 1, '2025-10-16', '2025-10-31', 2, 'active', '2025-10-16 11:52:35', '2025-10-16 11:52:35');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2025_10_15_160238_create_vendors_table', 1),
(6, '2025_10_15_171211_add_fields_to_users_table', 1),
(7, '2025_10_15_180901_create_services_table', 1),
(8, '2025_10_15_190226_create_orders_table', 1),
(9, '2025_10_15_195557_create_staffs_table', 1),
(10, '2025_10_15_213902_add_staff_id_to_orders_table', 2),
(11, '2025_10_15_221052_create_ratings_and_reviews_table', 3),
(12, '2025_10_15_231855_create_plans_table', 4),
(13, '2025_10_15_234341_create_client_subscriptions_table', 5),
(14, '2025_10_16_181627_create_payments_table', 6),
(15, '2025_10_16_195426_add_device_token_to_users_table', 7);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED NOT NULL,
  `scheduled_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price` decimal(8,2) NOT NULL,
  `status` enum('pending','assigned','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `staff_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `client_id`, `vendor_id`, `service_id`, `scheduled_time`, `price`, `status`, `payment_status`, `created_at`, `updated_at`, `staff_id`) VALUES
(1, 2, 1, 1, '2025-10-15 22:19:19', 200.00, 'completed', 'paid', '2025-10-15 16:25:27', '2025-10-15 16:25:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `payable_type` varchar(255) DEFAULT NULL,
  `payable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(8,2) NOT NULL,
  `razorpay_payment_id` varchar(255) NOT NULL,
  `razorpay_order_id` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'auth-token', 'f96e16fcec43d97147e6ca8a7775fedfb22f3d79b8a92c5d5e5594d2f6dac84e', '[\"*\"]', '2025-10-15 16:18:12', NULL, '2025-10-15 15:14:40', '2025-10-15 16:18:12'),
(2, 'App\\Models\\User', 1, 'auth-token', 'b6bbecd393d5e074d84bbdb7ade6f03149cbcc6c6f9154e514e30939164e8621', '[\"*\"]', '2025-10-15 16:18:24', NULL, '2025-10-15 15:30:01', '2025-10-15 16:18:24'),
(3, 'App\\Models\\User', 2, 'auth-token', '12ee8f7122e15ded88337c58900f0b4b6f6d2de00badfd1e0f794ecf268bb418', '[\"*\"]', '2025-10-15 16:25:27', NULL, '2025-10-15 16:20:18', '2025-10-15 16:25:27'),
(4, 'App\\Models\\User', 1, 'auth-token', '6bf76634f3519e808f4a5aa0733e90dd95257e0facaaeb5f6258331b9fad0cf2', '[\"*\"]', '2025-10-15 16:33:03', NULL, '2025-10-15 16:25:02', '2025-10-15 16:33:03'),
(5, 'App\\Models\\User', 1, 'auth-token', 'c143a71f98082c38ee7332b4d1670b88a775f9c3ce276bc0d5c77379a354d722', '[\"*\"]', '2025-10-15 16:55:28', NULL, '2025-10-15 16:45:45', '2025-10-15 16:55:28'),
(6, 'App\\Models\\User', 2, 'auth-token', 'c79e232456fa161293e8a5e14dc704bc3a86bd388206e9c3bd999ab9fd0d50cb', '[\"*\"]', '2025-10-15 16:59:51', NULL, '2025-10-15 16:47:47', '2025-10-15 16:59:51'),
(7, 'App\\Models\\User', 4, 'auth-token', 'f037414d62e01d5d6e4181a23b182a5a9e79d67a82e8a8b5d216e3276e91d8d8', '[\"*\"]', NULL, NULL, '2025-10-15 17:28:06', '2025-10-15 17:28:06'),
(8, 'App\\Models\\User', 1, 'auth-token', '774d58213e9b2318aa3b761a934f3c36085de79c43ecdc8f805037d5080a4b7d', '[\"*\"]', '2025-10-15 17:29:02', NULL, '2025-10-15 17:28:53', '2025-10-15 17:29:02'),
(9, 'App\\Models\\User', 1, 'auth-token', '58fdcebbbd707ab4420866338c24406ed25fbff0039267f11c9587d636c33705', '[\"*\"]', '2025-10-15 17:38:54', NULL, '2025-10-15 17:29:56', '2025-10-15 17:38:54'),
(10, 'App\\Models\\User', 1, 'auth-token', '45b628b3e6632e0bc6b6ce3eabf9666cc56c24815813b2cead3c90bea13ee6e0', '[\"*\"]', '2025-10-15 17:45:47', NULL, '2025-10-15 17:39:03', '2025-10-15 17:45:47'),
(11, 'App\\Models\\User', 1, 'auth-token', '5c14defed68347d6b4454b013ce430f090a776c5c96ed1c8a6a5cde1babf1dd5', '[\"*\"]', '2025-10-15 18:11:21', NULL, '2025-10-15 18:03:44', '2025-10-15 18:11:21'),
(12, 'App\\Models\\User', 1, 'auth-token', '008e0c418c0868c8bd45040ed0994508efa9c3ee89f31b26575a4621527e0d40', '[\"*\"]', NULL, NULL, '2025-10-15 18:11:37', '2025-10-15 18:11:37'),
(13, 'App\\Models\\User', 2, 'auth-token', '831d1c9a97d4950723cd7c3382e42412aff43d849b7fcfa60e38399e7b1beeb1', '[\"*\"]', NULL, NULL, '2025-10-15 18:17:40', '2025-10-15 18:17:40'),
(14, 'App\\Models\\User', 2, 'auth-token', 'c4e87a42c725324ae6eb3e767d0607e8bd47d4a66467a11fee09776117485ce5', '[\"*\"]', NULL, NULL, '2025-10-15 18:17:52', '2025-10-15 18:17:52'),
(15, 'App\\Models\\User', 2, 'auth-token', 'e726f0467823cc0b77fc7f4d407c5965189a6360563ec04b3c49f83642b1a02f', '[\"*\"]', '2025-10-15 18:24:42', NULL, '2025-10-15 18:22:56', '2025-10-15 18:24:42'),
(16, 'App\\Models\\User', 2, 'auth-token', '76160672e1de0a25a9965e978445d22a0698a3285aa5817a03d20531399a86db', '[\"*\"]', NULL, NULL, '2025-10-15 18:24:47', '2025-10-15 18:24:47'),
(17, 'App\\Models\\User', 2, 'auth-token', '05455ddf8224a86609cd4d6acb053890bfd44734b9b43250170e4ab9bca80e5e', '[\"*\"]', '2025-10-16 10:15:07', NULL, '2025-10-16 10:14:52', '2025-10-16 10:15:07'),
(18, 'App\\Models\\User', 1, 'auth-token', 'bc9700551ccf520510441593aed37976a90963f3ecf9d3ea9d2ac23c000ca197', '[\"*\"]', '2025-10-16 10:16:06', NULL, '2025-10-16 10:15:40', '2025-10-16 10:16:06'),
(19, 'App\\Models\\User', 1, 'auth-token', '8fd0340b30ab43b72ce5453b74418d35c67ec7f0724c5993f73f3658b8b6566f', '[\"*\"]', '2025-10-16 10:39:00', NULL, '2025-10-16 10:16:11', '2025-10-16 10:39:00'),
(20, 'App\\Models\\User', 3, 'auth-token', '81c53886a5f9f0d2c259b7e1b4e2babb31646615cbfb3b63e06f64a9afcc8e9e', '[\"*\"]', NULL, NULL, '2025-10-16 10:39:11', '2025-10-16 10:39:11'),
(21, 'App\\Models\\User', 1, 'auth-token', 'b568f4647eac73dbdd85fcc883b0982952695a9526b8f85084109abf045c869d', '[\"*\"]', '2025-10-16 10:39:49', NULL, '2025-10-16 10:39:45', '2025-10-16 10:39:49'),
(22, 'App\\Models\\User', 1, 'auth-token', 'd5def1e99b4fa74ea81d9cd5528a4dbf193e7f6cd75d6e57878eeb9f7ec1e574', '[\"*\"]', '2025-10-16 10:40:04', NULL, '2025-10-16 10:39:52', '2025-10-16 10:40:04'),
(23, 'App\\Models\\User', 2, 'auth-token', '7ea6c157442057c8f3ce3ec8b79b62c52b6151c5f8589bf1772dea815d371c80', '[\"*\"]', '2025-10-16 10:41:10', NULL, '2025-10-16 10:40:17', '2025-10-16 10:41:10'),
(24, 'App\\Models\\User', 1, 'auth-token', '9f8e0bbbdd840b9c8b1f44c03e2c48adc4bc095733b460d0118e89196911244f', '[\"*\"]', '2025-10-16 11:09:27', NULL, '2025-10-16 10:47:10', '2025-10-16 11:09:27'),
(25, 'App\\Models\\User', 1, 'auth-token', '94b98037f28676ebff3c177604169271914a4a26d26269c578612d4b720cc88b', '[\"*\"]', '2025-10-16 10:52:00', NULL, '2025-10-16 10:51:18', '2025-10-16 10:52:00'),
(26, 'App\\Models\\User', 1, 'auth-token', '2ebfa07070421ac0bedf7319eb39e52ad5d371f6eac237aaddf0e821dbc71d46', '[\"*\"]', '2025-10-16 10:52:17', NULL, '2025-10-16 10:52:04', '2025-10-16 10:52:17'),
(27, 'App\\Models\\User', 1, 'auth-token', '0d56d65f36ccfa6f0edbc233641e35140606a93750bdd2f9d068b013e7cd0eb2', '[\"*\"]', '2025-10-16 10:52:27', NULL, '2025-10-16 10:52:20', '2025-10-16 10:52:27'),
(28, 'App\\Models\\User', 2, 'auth-token', '6b2e84cffc7395064eceb05610ec2b7d83edf55cb9c5b04f4f984c2baadffd67', '[\"*\"]', '2025-10-16 10:53:32', NULL, '2025-10-16 10:53:26', '2025-10-16 10:53:32'),
(29, 'App\\Models\\User', 1, 'auth-token', 'b31da48128cd0bfa3e21a2849250e88b4dc0de0aa738e7e5f624f8925dbdb9ac', '[\"*\"]', NULL, NULL, '2025-10-16 10:56:14', '2025-10-16 10:56:14'),
(30, 'App\\Models\\User', 1, 'auth-token', '8f63a3b268e7536267056deb0ab16d89941cc29b3fd871677a6558d4f54be842', '[\"*\"]', '2025-10-16 11:09:58', NULL, '2025-10-16 10:56:19', '2025-10-16 11:09:58'),
(31, 'App\\Models\\User', 1, 'auth-token', '135c1bcc47c0050057cf4351d298cad76edba2ff6a81bf95ea7387f0e8c505bb', '[\"*\"]', NULL, NULL, '2025-10-16 11:10:03', '2025-10-16 11:10:03'),
(32, 'App\\Models\\User', 2, 'auth-token', '13d82fa0a8cf8524687caf2a9bdf96ca241fff6eaac237c1cddeebb26a0de34b', '[\"*\"]', '2025-10-16 12:29:08', NULL, '2025-10-16 11:10:30', '2025-10-16 12:29:08'),
(33, 'App\\Models\\User', 2, 'auth-token', 'fd6ace282a4d961bdd2fed5b6f8f2b69b3e9d44bdb4f53f2ec9c0e1de0d6395a', '[\"*\"]', '2025-10-16 12:38:10', NULL, '2025-10-16 12:29:20', '2025-10-16 12:38:10'),
(34, 'App\\Models\\User', 2, 'auth-token', '9c7a096fa220c77d95aea7f6d09157b4db52331fe23c81f3f636f0dd6309af96', '[\"*\"]', '2025-10-16 12:52:06', NULL, '2025-10-16 12:44:14', '2025-10-16 12:52:06'),
(35, 'App\\Models\\User', 2, 'auth-token', '3a4a6adc60cb46e0fbae8e730f179f191d7cbd83934bf097c580478f54f63638', '[\"*\"]', '2025-10-16 12:59:30', NULL, '2025-10-16 12:52:17', '2025-10-16 12:59:30'),
(36, 'App\\Models\\User', 1, 'auth-token', '489dcb108c252f5e42f91fc4e60600b39b6652511e10283149001666b657a378', '[\"*\"]', '2025-10-16 12:59:45', NULL, '2025-10-16 12:59:40', '2025-10-16 12:59:45'),
(37, 'App\\Models\\User', 1, 'auth-token', 'b00fe322cb4ad9fab7722cd03a1a1293d5fee7434527c05f17b4f7bb1b907672', '[\"*\"]', '2025-10-16 13:00:01', NULL, '2025-10-16 12:59:48', '2025-10-16 13:00:01'),
(38, 'App\\Models\\User', 2, 'auth-token', 'e99eeefd4943eb3b821ad56ef9ca516d99a9b0b51759bb4de24ad8845f7d3ce3', '[\"*\"]', '2025-10-16 13:06:57', NULL, '2025-10-16 13:00:43', '2025-10-16 13:06:57'),
(39, 'App\\Models\\User', 1, 'auth-token', '17853e77a1d6c05a2fa8f370b7d88d760a8831619d8d4d6998b907a0420f2449', '[\"*\"]', '2025-10-16 13:08:04', NULL, '2025-10-16 13:07:56', '2025-10-16 13:08:04'),
(40, 'App\\Models\\User', 1, 'auth-token', '3e4bebae833618e84a0b12e8bd2d201e903a40bc24d2269fb6bbf14b4efe9408', '[\"*\"]', '2025-10-16 13:08:16', NULL, '2025-10-16 13:08:07', '2025-10-16 13:08:16'),
(41, 'App\\Models\\User', 5, 'auth-token', '7291e7ce38eb35e8d4607851bd796917e32a3b7527b65008521627497780b8ee', '[\"*\"]', '2025-10-16 14:36:14', NULL, '2025-10-16 13:19:31', '2025-10-16 14:36:14'),
(42, 'App\\Models\\User', 1, 'auth-token', 'af733e4396d91046610e23c637313902807c0048118c86432cd03da8be0c6fbd', '[\"*\"]', '2025-10-16 13:22:56', NULL, '2025-10-16 13:22:40', '2025-10-16 13:22:56'),
(43, 'App\\Models\\User', 5, 'auth-token', '74ea87844bce45725c7379bdf8bdc9374e9c004bf7e9f17068a0a20a54eff7d2', '[\"*\"]', NULL, NULL, '2025-10-16 13:26:48', '2025-10-16 13:26:48'),
(44, 'App\\Models\\User', 5, 'auth-token', '24f064dc721ac2dbeffbb7518b0ceaf938504a3dadc3d7e71a0d6ca147e6a562', '[\"*\"]', NULL, NULL, '2025-10-16 13:27:20', '2025-10-16 13:27:20'),
(45, 'App\\Models\\User', 5, 'auth-token', 'c2542b25e098e9b4aedb81d41c2dfe195cb0abee19e6a8df1d7417e1065ab030', '[\"*\"]', '2025-10-16 13:39:03', NULL, '2025-10-16 13:38:58', '2025-10-16 13:39:03'),
(46, 'App\\Models\\User', 5, 'auth-token', '339215c83598d01a3c6c24c55697ba938f30d20bf40553e680682bbf80e8bf9c', '[\"*\"]', '2025-10-16 13:39:27', NULL, '2025-10-16 13:39:09', '2025-10-16 13:39:27'),
(47, 'App\\Models\\User', 2, 'auth-token', '42969ef84962db935ecf24f021d1c8414038aed5c0b296ec6652b2c0d743f35f', '[\"*\"]', '2025-10-16 13:47:08', NULL, '2025-10-16 13:47:02', '2025-10-16 13:47:08'),
(48, 'App\\Models\\User', 1, 'auth-token', 'b9efd2ac0a780d797e256ed63f8c7115a15f7b550fca0defd8af05d51892ca16', '[\"*\"]', '2025-10-16 13:47:30', NULL, '2025-10-16 13:47:24', '2025-10-16 13:47:30'),
(49, 'App\\Models\\User', 1, 'auth-token', '2aaa3f5ddad2e51a85ea61e5fc5da4708db0d0a2265a512a7161c057fa715ac3', '[\"*\"]', '2025-10-16 13:48:04', NULL, '2025-10-16 13:47:36', '2025-10-16 13:48:04'),
(50, 'App\\Models\\User', 2, 'auth-token', 'd31f1dd32436f326473601010d554ec340c9c3dcf73e35d4d8e996aa802b7704', '[\"*\"]', '2025-10-16 13:50:12', NULL, '2025-10-16 13:48:42', '2025-10-16 13:50:12'),
(51, 'App\\Models\\User', 2, 'auth-token', '40b2e81c3ae8c6cf94bae3ac1af04906b253129c6b8bd7c036ce6b43a0b3f858', '[\"*\"]', '2025-10-16 13:56:11', NULL, '2025-10-16 13:55:46', '2025-10-16 13:56:11'),
(52, 'App\\Models\\User', 2, 'auth-token', 'cacfb5ff7695daf48549a3f8db1b245c8286d6b9e9a30f971d635e9c78b37ac4', '[\"*\"]', '2025-10-16 14:00:49', NULL, '2025-10-16 13:57:33', '2025-10-16 14:00:49'),
(53, 'App\\Models\\User', 1, 'auth-token', '227cb4c43204a5df8eba2e4255d1e5ddbddca2fa38d63d3450474205567b0dc4', '[\"*\"]', '2025-10-16 14:07:29', NULL, '2025-10-16 14:07:25', '2025-10-16 14:07:29'),
(54, 'App\\Models\\User', 1, 'auth-token', '4c31d56f3c9067b21493ccee29ced88d59d1f8b9edb26be445405b6781a0f87a', '[\"*\"]', '2025-10-16 14:09:52', NULL, '2025-10-16 14:09:34', '2025-10-16 14:09:52'),
(55, 'App\\Models\\User', 5, 'auth-token', '9e5fd90121818ab99abcabfb2191f89d1076d160f5bfe1af0d13e23dc8f3ebf4', '[\"*\"]', NULL, NULL, '2025-10-16 14:10:05', '2025-10-16 14:10:05'),
(56, 'App\\Models\\User', 5, 'auth-token', '02f350e44fb95c8724b04f7a30e069bab14c0d13d7fbbafa0e3584e90bbf8cf7', '[\"*\"]', '2025-10-16 14:22:51', NULL, '2025-10-16 14:10:15', '2025-10-16 14:22:51'),
(57, 'App\\Models\\User', 5, 'auth-token', 'cfc541c6b845295ecda4203eb5e5896e03f3183b941bbaa55f59c59e8cdbb5f9', '[\"*\"]', NULL, NULL, '2025-10-16 14:40:14', '2025-10-16 14:40:14'),
(58, 'App\\Models\\User', 1, 'auth-token', 'fef16010d70bd47968612e9362b125c6ee8301d73254a01f9dac8d2b3f3f4b74', '[\"*\"]', '2025-10-16 14:40:43', NULL, '2025-10-16 14:40:35', '2025-10-16 14:40:43'),
(59, 'App\\Models\\User', 1, 'auth-token', '461966cf96fb23430068fa9699762a4498f30b3807999ae47cb84ec1d60e1679', '[\"*\"]', '2025-10-16 14:40:57', NULL, '2025-10-16 14:40:45', '2025-10-16 14:40:57'),
(60, 'App\\Models\\User', 2, 'auth-token', 'b551e8b8ff01be0286cf3ddee415313e5e22558e77bb8e4ecfe3b7fd2471137c', '[\"*\"]', '2025-10-16 14:42:04', NULL, '2025-10-16 14:41:02', '2025-10-16 14:42:04'),
(61, 'App\\Models\\User', 2, 'auth-token', '00788f28bdbdd78f656c830db96fc7d35333274fceb4aaeab12aa939cec708af', '[\"*\"]', '2025-10-16 15:50:51', NULL, '2025-10-16 15:50:38', '2025-10-16 15:50:51'),
(62, 'App\\Models\\User', 5, 'auth-token', 'f515df2bc35742e40acb95ffa53af57f10b7e013ea6aba0154fdf47a61acf1f1', '[\"*\"]', '2025-10-16 15:52:03', NULL, '2025-10-16 15:51:58', '2025-10-16 15:52:03'),
(63, 'App\\Models\\User', 5, 'auth-token', 'd9c179400edcd5a8e25418e33d9bd7b743b56d28f7488f7c6b261953cb712317', '[\"*\"]', NULL, NULL, '2025-10-16 16:03:17', '2025-10-16 16:03:17'),
(64, 'App\\Models\\User', 5, 'auth-token', '84d279aaf3a9c0dd2b9d2e4cd79b518899fbf00db680fe602fd4082f551aaec9', '[\"*\"]', NULL, NULL, '2025-10-16 17:06:32', '2025-10-16 17:06:32'),
(65, 'App\\Models\\User', 5, 'auth-token', '1474e6bfe0a01a1f627f9ef1ac003ea2df0b02e3281dd3306e3e187cd548134c', '[\"*\"]', NULL, NULL, '2025-10-16 17:07:12', '2025-10-16 17:07:12'),
(66, 'App\\Models\\User', 5, 'auth-token', 'cf7c454d4498872de61b6744588504c961a51b6aac56261609c444e645e0c98d', '[\"*\"]', NULL, NULL, '2025-10-16 17:29:58', '2025-10-16 17:29:58'),
(67, 'App\\Models\\User', 5, 'auth-token', '59b6c99295c163a18eb891543e8deb1da49fa8843a29631433f6c92a07c58e12', '[\"*\"]', '2025-10-16 17:47:26', NULL, '2025-10-16 17:31:31', '2025-10-16 17:47:26'),
(68, 'App\\Models\\User', 5, 'auth-token', '03bc8402ca694550ee8118546f4bcdeff6d1cd145c9a85279408ae909f0a7e9a', '[\"*\"]', '2025-10-16 17:47:35', NULL, '2025-10-16 17:47:33', '2025-10-16 17:47:35'),
(69, 'App\\Models\\User', 2, 'auth-token', '2f478cbcac56f2555274b276098850f1cba7258bf0e3ee3e2eab0a7ba8fa4470', '[\"*\"]', '2025-10-16 17:47:50', NULL, '2025-10-16 17:47:48', '2025-10-16 17:47:50'),
(70, 'App\\Models\\User', 5, 'auth-token', '26bffc24cfbe385bde4033bf57dd338ba305d19658ca6c89a8f187ebf20281c6', '[\"*\"]', NULL, NULL, '2025-10-16 17:49:08', '2025-10-16 17:49:08'),
(71, 'App\\Models\\User', 5, 'auth-token', '49bb79bfa571849225e84668d5a7f214e917f7fa0b1722378e4df8d288eb9500', '[\"*\"]', '2025-10-16 17:57:04', NULL, '2025-10-16 17:57:04', '2025-10-16 17:57:04'),
(72, 'App\\Models\\User', 2, 'auth-token', '12dc9d78adf275497e04a172cbb74d19e7ed5913032776d6eee604df33bfc942', '[\"*\"]', '2025-10-16 17:57:56', NULL, '2025-10-16 17:57:24', '2025-10-16 17:57:56'),
(73, 'App\\Models\\User', 5, 'auth-token', '30a6e77b90f35ed4a56df373feff9f2aa778f82e48c79c003fc84b4c50597dd0', '[\"*\"]', '2025-10-16 17:58:41', NULL, '2025-10-16 17:58:32', '2025-10-16 17:58:41'),
(74, 'App\\Models\\User', 1, 'auth-token', 'e91b551671637921790ca655cd3685c7d253f237bfbe472a40205879df032b36', '[\"*\"]', '2025-10-16 17:59:01', NULL, '2025-10-16 17:58:56', '2025-10-16 17:59:01'),
(75, 'App\\Models\\User', 1, 'auth-token', 'ecb22303bb864e3c3ec1dca03f7341dde1e023a2e3c8184d004ca30e78a265da', '[\"*\"]', '2025-10-16 17:59:17', NULL, '2025-10-16 17:59:04', '2025-10-16 17:59:17'),
(76, 'App\\Models\\User', 3, 'auth-token', 'c15370de6087579eb505b0d18b2b80f1510c3a714c80a9aba0eb3ae8d536f0d6', '[\"*\"]', '2025-10-16 17:59:58', NULL, '2025-10-16 17:59:56', '2025-10-16 17:59:58'),
(77, 'App\\Models\\User', 1, 'auth-token', 'bcc163c4e0c06f69bc7016e7a09582056496092f195126bf1fa9a3cd98829e66', '[\"*\"]', NULL, NULL, '2025-10-17 09:12:13', '2025-10-17 09:12:13'),
(78, 'App\\Models\\User', 1, 'auth-token', '640f9fb83efc4a5fac5d5a011e16fb5686266781ad9913493d5e80a2647c537a', '[\"*\"]', '2025-10-17 09:12:27', NULL, '2025-10-17 09:12:15', '2025-10-17 09:12:27'),
(79, 'App\\Models\\User', 1, 'auth-token', 'eb3e8c7f3a1daba18a2ad7f4829eb55b64a884fb8f5cfc2465d2d0174ad763cb', '[\"*\"]', '2025-10-17 09:17:35', NULL, '2025-10-17 09:12:32', '2025-10-17 09:17:35'),
(80, 'App\\Models\\User', 1, 'auth-token', 'f4799850faa6cdd71887030c427675922c2e833b26c21d27644b4ef8408681ea', '[\"*\"]', '2025-10-17 09:24:26', NULL, '2025-10-17 09:18:49', '2025-10-17 09:24:26'),
(81, 'App\\Models\\User', 2, 'auth-token', '587cee8350061a0856fcf37aad57fadf42aaaed469b91bae639515ef2420b103', '[\"*\"]', '2025-10-17 10:17:40', NULL, '2025-10-17 09:25:21', '2025-10-17 10:17:40'),
(82, 'App\\Models\\User', 1, 'auth-token', 'fc46060c797cfe229f93d130ab25d0fa1af762b0b76ee7173a180efec7ac21ec', '[\"*\"]', '2025-10-17 10:19:50', NULL, '2025-10-17 10:19:39', '2025-10-17 10:19:50'),
(83, 'App\\Models\\User', 1, 'auth-token', '3b9b1d7c0bd2443be98c5813e49f4dec8a44d8619c0fa29bcf63c72b97acbdfc', '[\"*\"]', '2025-10-17 10:20:01', NULL, '2025-10-17 10:19:53', '2025-10-17 10:20:01'),
(84, 'App\\Models\\User', 2, 'auth-token', '1d87d2079ad8253ac192d87a1a667ea44022065a4640a21dfb09df426b9f9fb2', '[\"*\"]', '2025-10-17 10:55:23', NULL, '2025-10-17 10:37:22', '2025-10-17 10:55:23'),
(85, 'App\\Models\\User', 1, 'auth-token', '470229c342880ce7c114473ad54bfd0ff13d5f226ab5307fd7df0b38663f6e4c', '[\"*\"]', NULL, NULL, '2025-10-17 10:55:44', '2025-10-17 10:55:44'),
(86, 'App\\Models\\User', 2, 'auth-token', '6d64edd2419a488aa6a0dd0ddf73e21abc4333c9adc83bc3f59601d15258592e', '[\"*\"]', NULL, NULL, '2025-10-17 10:56:00', '2025-10-17 10:56:00'),
(87, 'App\\Models\\User', 5, 'auth-token', '63f524bdafd455bf76a4d67498a5fa313afe9f421f0c45e659f5fe2a4232ca9c', '[\"*\"]', '2025-10-17 10:56:42', NULL, '2025-10-17 10:56:41', '2025-10-17 10:56:42'),
(88, 'App\\Models\\User', 2, 'auth-token', 'b722f6e71d5845cc8ab9bcae04026ac69a6aa104f700fea6d2e81bc1262379e9', '[\"*\"]', '2025-10-17 11:37:15', NULL, '2025-10-17 10:57:33', '2025-10-17 11:37:15'),
(89, 'App\\Models\\User', 1, 'auth-token', '16a0ad7e6f4f2fd8d62155b08ae1a486f6f6793b9807b168c9e7c3b42d6646b5', '[\"*\"]', '2025-10-17 11:46:05', NULL, '2025-10-17 11:45:35', '2025-10-17 11:46:05'),
(90, 'App\\Models\\User', 1, 'auth-token', '59e88e90fa1dad833e8304fbcdbd00d8769102ca5f59a7fa707076c848e27e01', '[\"*\"]', '2025-10-17 11:46:12', NULL, '2025-10-17 11:46:08', '2025-10-17 11:46:12'),
(91, 'App\\Models\\User', 2, 'auth-token', '43bc752505f8e9697c5f33e7b7b5bf112d21e5b92193cc2ac7af477e12f35f7e', '[\"*\"]', '2025-10-17 12:16:18', NULL, '2025-10-17 11:46:31', '2025-10-17 12:16:18'),
(92, 'App\\Models\\User', 1, 'auth-token', 'fe268450450421844d933218299130e4096f48f9d3a4136807dd78ef86c619da', '[\"*\"]', '2025-10-17 12:46:06', NULL, '2025-10-17 12:18:50', '2025-10-17 12:46:06'),
(93, 'App\\Models\\User', 1, 'auth-token', '0a8315113d1c69976c8ed55f52cd787de9f9dc5b0df0efb6d271068abb10c895', '[\"*\"]', NULL, NULL, '2025-10-17 12:47:54', '2025-10-17 12:47:54'),
(94, 'App\\Models\\User', 1, 'auth-token', 'b837fff95dcf6762fa5b8eb68f5ba0ac00c31fca331578a7430b0881372a88aa', '[\"*\"]', '2025-10-17 12:48:13', NULL, '2025-10-17 12:47:54', '2025-10-17 12:48:13'),
(95, 'App\\Models\\User', 1, 'auth-token', 'b6b7f0498e527f211e001e30986d8ffe83b73e17fefa26987e3f68d58683191f', '[\"*\"]', NULL, NULL, '2025-10-17 12:52:11', '2025-10-17 12:52:11'),
(96, 'App\\Models\\User', 1, 'auth-token', '57ad50d2c0a518acb2ff8a352db4e60e10144e1b2ba147ebc5171719b70ebc0b', '[\"*\"]', NULL, NULL, '2025-10-17 13:08:19', '2025-10-17 13:08:19'),
(97, 'App\\Models\\User', 1, 'auth-token', '01e5b0897d27d5c28a5505d5126b5eb6ae2f407c823e28cf8d52e6c29ef5254e', '[\"*\"]', NULL, NULL, '2025-10-17 13:11:35', '2025-10-17 13:11:35'),
(98, 'App\\Models\\User', 2, 'auth-token', '74969c2e564d17098bac485b6936b22c46c03df9bb82c5bd1e11d6650cf5f1af', '[\"*\"]', '2025-10-17 13:12:23', NULL, '2025-10-17 13:11:49', '2025-10-17 13:12:23'),
(99, 'App\\Models\\User', 5, 'auth-token', '3af68aaffa5d6d5d98f05b9c917410a57d5f636e2e543a73838513600cded1cd', '[\"*\"]', '2025-10-17 13:25:04', NULL, '2025-10-17 13:25:03', '2025-10-17 13:25:04'),
(100, 'App\\Models\\User', 5, 'auth-token', '11d12e989de84a53b3ac06e8a8c72b7051ff7a9cfef2847d9983183ab537d458', '[\"*\"]', '2025-10-17 13:25:09', NULL, '2025-10-17 13:25:08', '2025-10-17 13:25:09'),
(101, 'App\\Models\\User', 5, 'auth-token', 'a2462d71a061633418182130c6a1b0978b398fb74a046e4c3291c7852e3900fa', '[\"*\"]', '2025-10-17 13:25:11', NULL, '2025-10-17 13:25:09', '2025-10-17 13:25:11'),
(102, 'App\\Models\\User', 2, 'auth-token', '0b8976c702462dcc6ee5e54a9fe4ad4f498eb36081fdbc8c176e85567d37ae07', '[\"*\"]', '2025-10-17 13:51:58', NULL, '2025-10-17 13:25:30', '2025-10-17 13:51:58'),
(103, 'App\\Models\\User', 2, 'auth-token', 'c9c4360b08216646e4c2870473b74c9a30c1dd974f0259a2286a5d7e7d2a5922', '[\"*\"]', '2025-10-17 14:08:20', NULL, '2025-10-17 13:52:10', '2025-10-17 14:08:20'),
(104, 'App\\Models\\User', 2, 'auth-token', '9f5c39c4cfd916862ba27b0ce860cfa248598e957983b0f5bd0bda58a9bcf591', '[\"*\"]', NULL, NULL, '2025-10-17 14:08:45', '2025-10-17 14:08:45'),
(105, 'App\\Models\\User', 2, 'auth-token', 'ca8ad9af6f043dd7eefcdf9f336c066b3e1b0aaf855278164332e3802d5c0715', '[\"*\"]', NULL, NULL, '2025-10-17 14:15:42', '2025-10-17 14:15:42'),
(106, 'App\\Models\\User', 2, 'auth-token', 'b2422c467d23b9b93dc750180fe676d65e96d3e6b9b2c6ddf825208abe3c9e9a', '[\"*\"]', NULL, NULL, '2025-10-17 14:15:43', '2025-10-17 14:15:43'),
(107, 'App\\Models\\User', 2, 'auth-token', '6851fdb8dc1be00040ab2e4577b2b84fe44f30e57795c62d8f8d72193c90d786', '[\"*\"]', '2025-10-17 14:54:27', NULL, '2025-10-17 14:47:42', '2025-10-17 14:54:27'),
(108, 'App\\Models\\User', 2, 'auth-token', '28ecd42e7af5e09ec8ce8bf735902bb4aa49f83a305d62057bc4586b010814f3', '[\"*\"]', '2025-10-17 14:57:26', NULL, '2025-10-17 14:57:11', '2025-10-17 14:57:26'),
(109, 'App\\Models\\User', 5, 'auth-token', '4b074e58edb155205ecd0de12d68dd43d1ce207eaeee0ae840b64f174c44f4c4', '[\"*\"]', '2025-10-17 14:58:07', NULL, '2025-10-17 14:57:52', '2025-10-17 14:58:07'),
(110, 'App\\Models\\User', 5, 'auth-token', '4811b76f076f944adf7c63903cd7adf68f1168bdb6579462644be8737e2a8464', '[\"*\"]', '2025-10-17 14:58:25', NULL, '2025-10-17 14:58:21', '2025-10-17 14:58:25'),
(111, 'App\\Models\\User', 1, 'auth-token', '5c2ecd34512e6fe44b9f83ade3a9c494383b13213c805231ec171e5bff0c61b5', '[\"*\"]', '2025-10-17 14:58:56', NULL, '2025-10-17 14:58:48', '2025-10-17 14:58:56'),
(112, 'App\\Models\\User', 1, 'auth-token', '78f54d9aad8174ddf93860122c3f2bc23529c13c86a5b48551280a43bfd05fae', '[\"*\"]', '2025-10-17 14:59:44', NULL, '2025-10-17 14:58:59', '2025-10-17 14:59:44'),
(113, 'App\\Models\\User', 2, 'auth-token', '9ae795c08d39a49a1c2e49e608ccdfa5a9576abdae1a358af804c23a1b00cece', '[\"*\"]', '2025-10-17 15:01:39', NULL, '2025-10-17 14:59:57', '2025-10-17 15:01:39'),
(114, 'App\\Models\\User', 5, 'auth-token', '03d62dcceada9c9cb9cfc0ae8735e5739365e20b793c0f711df0b743bebd0f68', '[\"*\"]', '2025-10-17 15:02:30', NULL, '2025-10-17 15:02:29', '2025-10-17 15:02:30'),
(115, 'App\\Models\\User', 3, 'auth-token', '1267f690caabefa82d3fee19913aa644d80866d1689e8593782f9d24bffcb8f7', '[\"*\"]', NULL, NULL, '2025-10-17 15:26:20', '2025-10-17 15:26:20'),
(116, 'App\\Models\\User', 3, 'auth-token', '6ac299d3e58e141d96c93c93aecbb7578509c0c992685a3d6fe2f6405eaad7ef', '[\"*\"]', NULL, NULL, '2025-10-17 15:41:29', '2025-10-17 15:41:29'),
(117, 'App\\Models\\User', 5, 'auth-token', '3642ba711ae72998a49f7790f28562d0b6e5c1a23a98c1107acfe391e7e6d0dc', '[\"*\"]', '2025-10-17 15:46:13', NULL, '2025-10-17 15:45:43', '2025-10-17 15:46:13'),
(118, 'App\\Models\\User', 6, 'auth-token', '983c480209b25b67e87080aa0e3a4dae6d72fc9071352d4edbcc885e139b2fdd', '[\"*\"]', NULL, NULL, '2025-10-17 15:47:01', '2025-10-17 15:47:01'),
(119, 'App\\Models\\User', 6, 'auth-token', 'e1025faa36ed2f7db0efcff1715bbda6a8b8af9ea251fe5c2ed628410f3136c5', '[\"*\"]', NULL, NULL, '2025-10-17 15:52:42', '2025-10-17 15:52:42'),
(121, 'App\\Models\\User', 2, 'auth-token', 'ef94be1f5a0049c208bff1984249ec047196e631c51d5c77436d54f4f017f08a', '[\"*\"]', '2025-12-19 18:00:49', NULL, '2025-12-19 17:59:00', '2025-12-19 18:00:49');

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `service_limit` int(11) NOT NULL,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `vendor_id`, `name`, `description`, `price`, `duration_days`, `service_limit`, `status`, `created_at`, `updated_at`) VALUES
(3, 1, 'Silver Plan', 'Basic car wash package', 299.00, 15, 2, 'active', '2025-10-16 10:55:57', '2025-10-16 10:55:57'),
(4, 1, 'Full wash', 'Basic car wash package', 999.00, 15, 2, 'active', '2025-10-16 11:09:27', '2025-10-16 11:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `ratings_and_reviews`
--

CREATE TABLE `ratings_and_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ratings_and_reviews`
--

INSERT INTO `ratings_and_reviews` (`id`, `order_id`, `client_id`, `vendor_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 5, 'The car wash was amazing!', '2025-10-15 16:59:51', '2025-10-15 16:59:51');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `duration` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `vendor_id`, `name`, `description`, `price`, `duration`, `created_at`, `updated_at`) VALUES
(1, 1, 'full Wash', 'Full exterior and interior wash', 200.00, 1, '2025-10-15 16:25:19', '2025-10-15 17:39:23');

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('available','busy','inactive') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staffs`
--

INSERT INTO `staffs` (`id`, `user_id`, `vendor_id`, `status`, `created_at`, `updated_at`) VALUES
(2, 3, 1, 'available', '2025-10-15 16:18:24', '2025-10-15 16:18:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` enum('admin','vendor','staff','client') NOT NULL DEFAULT 'client',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `device_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `name`, `email`, `phone`, `role`, `email_verified_at`, `password`, `remember_token`, `device_token`, `created_at`, `updated_at`) VALUES
(1, 'Anurag', 'Sharma', NULL, 'vendor@example.com', '0000000', 'vendor', NULL, '$2y$10$6zkdCaB2pMyuWufDQP1xLepAuwXiW6Ls6oGJm48ne13TLxG3LoATu', NULL, NULL, '2025-10-15 15:14:26', '2025-10-15 15:14:26'),
(2, 'Anurag', 'Sharma', NULL, 'anurag@example.com', '00000000', 'client', NULL, '$2y$10$mhVj5K61xnp1G0E4XMPlV.iblOllWVvUalw.PIbOhf.0s6EK1n81e', NULL, NULL, '2025-10-15 15:21:38', '2025-10-15 15:21:38'),
(3, 'ravi', 'kumar', NULL, 'staff@example.com', '0000', 'staff', NULL, '$2y$10$mVWdn3bUmLPv3UBTli0OXeKOBgLoRrIsvEmp/oqNDqI//9AGKS3aS', NULL, NULL, '2025-10-15 15:29:12', '2025-10-15 15:29:12'),
(4, 'shree', 'ram', NULL, 'ram@123.com', '09907947260', 'vendor', NULL, '$2y$10$z1dLfBTiOQQQWuLnQ/HzCeoM23mZ5ukLtT5nifKkQkzRigDBjCh4K', NULL, NULL, '2025-10-15 17:27:13', '2025-10-15 17:27:13'),
(5, 'Anueag', 'Sharma', NULL, 'admin@example.com', '000100000', 'admin', NULL, '$2y$10$q6ZDX9d.SiSW8uOCP3VehO04VP1xhLczVi0KsMea5n8lLTHIh30.u', NULL, 'abc123xyz456', '2025-10-16 13:18:47', '2025-10-16 14:36:14'),
(6, 'babu', 'lal', NULL, 'babu@gmail.com', '121212', 'client', NULL, '$2y$10$Yoxu0NI6tNsm5QiVKV1ndOy1FLZBDBdTgupAgozq8w.g2XhKx0S9G', NULL, NULL, '2025-10-17 15:46:57', '2025-10-17 15:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `location_lat` decimal(10,7) NOT NULL,
  `location_lng` decimal(10,7) NOT NULL,
  `fee_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `admin_id`, `name`, `description`, `address`, `location_lat`, `location_lng`, `fee_percentage`, `created_at`, `updated_at`) VALUES
(1, 1, 'John\'s Car Wash', 'The best car wash in town.', '123 Main St, Anytown, USA', 40.7128000, -74.0060000, 15.00, '2025-10-15 15:16:40', '2025-10-15 15:16:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client_subscriptions`
--
ALTER TABLE `client_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_subscriptions_client_id_foreign` (`client_id`),
  ADD KEY `client_subscriptions_plan_id_foreign` (`plan_id`),
  ADD KEY `client_subscriptions_vendor_id_foreign` (`vendor_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orders_client_id_foreign` (`client_id`),
  ADD KEY `orders_vendor_id_foreign` (`vendor_id`),
  ADD KEY `orders_service_id_foreign` (`service_id`),
  ADD KEY `orders_staff_id_foreign` (`staff_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_razorpay_payment_id_unique` (`razorpay_payment_id`),
  ADD KEY `payments_user_id_foreign` (`user_id`),
  ADD KEY `payments_payable_type_payable_id_index` (`payable_type`,`payable_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plans_vendor_id_foreign` (`vendor_id`);

--
-- Indexes for table `ratings_and_reviews`
--
ALTER TABLE `ratings_and_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ratings_and_reviews_order_id_unique` (`order_id`),
  ADD KEY `ratings_and_reviews_client_id_foreign` (`client_id`),
  ADD KEY `ratings_and_reviews_vendor_id_foreign` (`vendor_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `services_vendor_id_foreign` (`vendor_id`);

--
-- Indexes for table `staffs`
--
ALTER TABLE `staffs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staffs_user_id_vendor_id_unique` (`user_id`,`vendor_id`),
  ADD KEY `staffs_vendor_id_foreign` (`vendor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendors_admin_id_foreign` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client_subscriptions`
--
ALTER TABLE `client_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ratings_and_reviews`
--
ALTER TABLE `ratings_and_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staffs`
--
ALTER TABLE `staffs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `client_subscriptions`
--
ALTER TABLE `client_subscriptions`
  ADD CONSTRAINT `client_subscriptions_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_subscriptions_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staffs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `plans`
--
ALTER TABLE `plans`
  ADD CONSTRAINT `plans_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings_and_reviews`
--
ALTER TABLE `ratings_and_reviews`
  ADD CONSTRAINT `ratings_and_reviews_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_and_reviews_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_and_reviews_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staffs`
--
ALTER TABLE `staffs`
  ADD CONSTRAINT `staffs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staffs_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendors`
--
ALTER TABLE `vendors`
  ADD CONSTRAINT `vendors_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
