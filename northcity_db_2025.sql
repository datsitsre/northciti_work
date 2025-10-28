-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 10.30.252.49
-- Generation Time: Oct 21, 2025 at 02:25 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `northcity_db_2025`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(10) UNSIGNED NOT NULL,
  `key_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `rate_limit_per_minute` int(10) UNSIGNED DEFAULT 60,
  `rate_limit_per_hour` int(10) UNSIGNED DEFAULT 3600,
  `is_active` tinyint(1) DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_logs`
--

CREATE TABLE `api_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` varchar(36) NOT NULL,
  `method` varchar(10) NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `api_key_id` int(10) UNSIGNED DEFAULT NULL,
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_data`)),
  `response_code` int(10) UNSIGNED NOT NULL,
  `response_time_ms` int(10) UNSIGNED NOT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `content_type` enum('news','event') NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `ip_address` varchar(25) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookmarks`
--

INSERT INTO `bookmarks` (`id`, `user_id`, `content_type`, `content_id`, `notes`, `ip_address`, `created_at`, `updated_at`) VALUES
(2, 1, 'news', 4, NULL, NULL, '2025-06-09 06:51:53', '2025-06-09 06:51:53'),
(19, 1, 'news', 10, NULL, '::1', '2025-06-09 09:45:52', '2025-06-09 09:45:52'),
(22, 1, 'news', 14, NULL, '::1', '2025-06-11 08:20:05', '2025-06-11 08:20:05'),
(32, 1, 'news', 12, NULL, '::1', '2025-06-11 20:19:17', '2025-06-11 20:19:17'),
(33, 1, 'news', 9, NULL, '::1', '2025-06-11 20:19:41', '2025-06-11 20:19:41'),
(84, 1, 'news', 2, NULL, '::1', '2025-07-09 18:39:53', '2025-07-09 18:39:53'),
(85, 1, 'event', 43, NULL, '::1', '2025-08-11 08:21:03', '2025-08-11 08:21:03');

-- --------------------------------------------------------

--
-- Table structure for table `cache_entries`
--

CREATE TABLE `cache_entries` (
  `id` varchar(255) NOT NULL,
  `data` longtext NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL DEFAULT uuid(),
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#000000',
  `icon` varchar(50) DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `uuid`, `name`, `slug`, `description`, `color`, `icon`, `parent_id`, `sort_order`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'a07b7092-3cb2-11f0-8122-7e9c40ae708f', 'General News', 'general-news', 'General news and updates', '#2563eb', NULL, NULL, 1, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(2, 'a07b729a-3cb2-11f0-8122-7e9c40ae708f', 'Politics', 'politics', 'Political news and analysis', '#dc2626', NULL, NULL, 2, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(3, 'a07b7326-3cb2-11f0-8122-7e9c40ae708f', 'Technology', 'technology', 'Tech news and innovations', '#059669', NULL, NULL, 3, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(4, 'a07b7380-3cb2-11f0-8122-7e9c40ae708f', 'Sports', 'sports', 'Sports news and events', '#ea580c', NULL, NULL, 4, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(5, 'a07b73da-3cb2-11f0-8122-7e9c40ae708f', 'Entertainment', 'entertainment', 'Entertainment and celebrity news', '#7c3aed', NULL, NULL, 5, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(6, 'a07b742a-3cb2-11f0-8122-7e9c40ae708f', 'Business', 'business', 'Business and economic news', '#0891b2', NULL, NULL, 6, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(7, 'a07b747a-3cb2-11f0-8122-7e9c40ae708f', 'Health', 'health', 'Health and medical news', '#16a34a', NULL, NULL, 7, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(8, 'a07b74d4-3cb2-11f0-8122-7e9c40ae708f', 'Education', 'education', 'Educational news and events', '#ca8a04', NULL, NULL, 8, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(9, 'a07b752e-3cb2-11f0-8122-7e9c40ae708f', 'Community Events', 'community-events', 'Local community events', '#be185d', NULL, NULL, 9, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(10, 'a07ba12a-3cb2-11f0-8122-7e9c40ae708f', 'Conferences', 'conferences', 'Professional conferences and seminars', '#4338ca', NULL, NULL, 10, 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34', NULL),
(11, '1fe5b52d-f3a7-45ef-8552-def11dd992a9', 'Science Fiction', 'science-fiction', 'This is science fiction category', '#801e7d', '', NULL, 8, 1, '2025-06-04 12:04:51', '2025-06-04 12:07:25', '2025-06-04 12:07:25'),
(12, '76bbfab2-d9cf-4e74-8107-2c240ed7b1b9', 'Science Fiction', 'science-fiction-1', 'This is science fiction category', '#801e7d', '', NULL, 11, 1, '2025-06-04 12:05:02', '2025-06-04 12:14:39', NULL),
(13, '62368c0e-1244-4353-8a6f-cd99342fe564', 'Science Fiction', 'science-fiction-2', 'This is science fiction category', '#801e7d', '', NULL, 8, 1, '2025-06-04 12:06:02', '2025-06-04 12:07:20', '2025-06-04 12:07:20'),
(14, 'd8828cf1-078d-4820-ae52-2d7e30c3575f', 'Science Fiction', 'science-fiction-3', 'This is science fiction category', '#801e7d', '', NULL, 8, 1, '2025-06-04 12:07:08', '2025-06-04 12:07:16', '2025-06-04 12:07:16');

--
-- Triggers `categories`
--
DELIMITER $$
CREATE TRIGGER `categories_cache_invalidate` AFTER UPDATE ON `categories` FOR EACH ROW BEGIN
    DELETE FROM cache_entries WHERE id LIKE 'categories_%';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL DEFAULT uuid(),
  `content_type` enum('news','event') NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `author_email` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','rejected','spam','hidden') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `moderation_score` decimal(4,3) DEFAULT 0.000,
  `auto_moderated` tinyint(1) DEFAULT 0,
  `requires_review` tinyint(1) DEFAULT 0,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `moderation_queue_id` int(10) UNSIGNED DEFAULT NULL,
  `edit_count` int(10) UNSIGNED DEFAULT 0,
  `last_edited_at` timestamp NULL DEFAULT NULL,
  `upvotes` int(10) UNSIGNED DEFAULT 0,
  `downvotes` int(10) UNSIGNED DEFAULT 0,
  `is_flagged` tinyint(1) DEFAULT 0,
  `flag_count` int(10) UNSIGNED DEFAULT 0,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `uuid`, `content_type`, `content_id`, `parent_id`, `user_id`, `author_name`, `author_email`, `content`, `status`, `priority`, `moderation_score`, `auto_moderated`, `requires_review`, `reviewed_by`, `reviewed_at`, `moderation_queue_id`, `edit_count`, `last_edited_at`, `upvotes`, `downvotes`, `is_flagged`, `flag_count`, `ip_address`, `user_agent`, `created_at`, `updated_at`, `approved_by`, `approved_at`, `deleted_at`) VALUES
(117, '148a457c-3a8b-4dcb-b628-2fc6d48edeb5', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'Good news', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 22:45:17', '2025-06-10 22:49:12', NULL, NULL, '2025-06-10 22:49:12'),
(118, '935b7ec6-414c-4843-9596-5e8488dc9c1d', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'Lucile news fam', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 22:45:58', '2025-06-10 22:47:16', NULL, NULL, '2025-06-10 22:47:16'),
(119, 'a6b62c9c-828c-41ad-a880-6df07dd90264', 'news', 2, 118, 1, 'System Administrator', 'admin@example.com', 'Valluaable news', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 22:46:25', '2025-06-10 22:46:32', NULL, NULL, '2025-06-10 22:46:32'),
(120, 'b013ec18-b753-4d4a-85fb-9bc2c3838a93', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'Felicity lover', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 22:49:38', '2025-06-10 22:51:44', NULL, NULL, '2025-06-10 22:51:44'),
(121, '754a7444-5383-4f48-bb01-32eff0f76ac0', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'falling in love', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 22:54:25', '2025-06-10 22:54:36', NULL, NULL, '2025-06-10 22:54:36'),
(122, '372cbdad-68c1-4e5b-afe5-340bc752eaa2', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'Love is real', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:04:31', '2025-06-10 23:05:12', NULL, NULL, '2025-06-10 23:05:12'),
(123, '083e3157-eb8e-404a-bf7f-59a9f38caae5', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'loving kindness', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:04:41', '2025-06-10 23:05:09', NULL, NULL, '2025-06-10 23:05:09'),
(124, '6f5d8310-a31e-4c75-899c-8c4067fd8849', 'news', 2, 123, 1, 'System Administrator', 'admin@example.com', 'hope never dies', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:04:53', '2025-06-10 23:05:01', NULL, NULL, '2025-06-10 23:05:01'),
(125, 'f1b16404-564f-422c-b0ba-20636088b8c1', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'True love kind', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 1, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:06:15', '2025-06-11 00:25:27', NULL, NULL, NULL),
(126, '3133b42e-ddf7-479a-9c31-dd28a1f3c7fc', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'Due time soon', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:06:25', '2025-06-10 23:32:36', NULL, NULL, '2025-06-10 23:32:36'),
(127, '3215e515-d601-4f51-9ad2-491932d1217b', 'news', 2, 126, 1, 'System Administrator', 'admin@example.com', 'hahaha we love you', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:06:45', '2025-06-10 23:22:55', NULL, NULL, '2025-06-10 23:22:55'),
(128, '85a14669-b170-4533-b158-ae63481e8bd9', 'news', 2, 126, 1, 'System Administrator', 'admin@example.com', 'Something good', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:06:57', '2025-06-10 23:23:11', NULL, NULL, '2025-06-10 23:23:11'),
(129, 'bb306732-4cce-4be9-9b4a-414a8c98220a', 'news', 2, 125, 1, 'System Administrator', 'admin@example.com', 'Loving jogging', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:07:14', '2025-06-10 23:32:28', NULL, NULL, '2025-06-10 23:32:28'),
(130, '66342f06-a3cd-4786-81e6-0033743a7735', 'news', 20, NULL, 1, 'System Administrator', 'admin@example.com', 'News is great', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:07:35', '2025-06-10 23:33:19', NULL, NULL, '2025-06-10 23:33:19'),
(131, 'dbf11453-cec9-45e5-85f0-59c973c47e67', 'news', 20, NULL, 1, 'System Administrator', 'admin@example.com', 'things are very good', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:07:45', '2025-06-10 23:30:44', NULL, NULL, '2025-06-10 23:30:44'),
(132, '61254470-4d4d-4994-ab9e-1e490001d986', 'news', 20, NULL, 1, 'System Administrator', 'admin@example.com', 'Hiping him', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:07:54', '2025-06-10 23:30:34', NULL, NULL, '2025-06-10 23:30:34'),
(133, 'e4dfbe2b-8da1-45e9-8cff-83fe71328679', 'news', 16, NULL, 1, 'System Administrator', 'admin@example.com', 'The love is great', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 1, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:08:15', '2025-07-15 12:52:10', NULL, NULL, NULL),
(134, '2c2f3ff7-cbbd-41a2-ae76-65567e5756f4', 'news', 20, NULL, 1, 'System Administrator', 'admin@example.com', 'Joe lala is good', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 1, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:31:18', '2025-06-11 01:04:58', NULL, NULL, '2025-06-11 01:04:58'),
(135, 'd256d60a-0ea0-4e20-b9ee-c675254825ad', 'news', 20, NULL, 1, 'System Administrator', 'admin@example.com', 'Loosing i s terrible', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 1, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:33:50', '2025-06-11 02:10:30', NULL, NULL, NULL),
(136, '29954f89-933f-4a44-8ca1-2bc62e97ea43', 'news', 20, 134, 1, 'System Administrator', 'admin@example.com', 'Loosing is not good', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-10 23:34:01', '2025-06-10 23:34:17', NULL, NULL, '2025-06-10 23:34:17'),
(137, '820cee14-4867-4436-b789-d34231cb8488', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'yes lover', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 1, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-11 00:32:49', '2025-06-11 01:29:55', NULL, NULL, NULL),
(138, 'ae623a6a-646d-460a-bd83-11987f7e5159', 'news', 2, 137, 1, 'System Administrator', 'admin@example.com', 'Hava haha', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 1, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-11 01:01:19', '2025-06-11 01:29:51', NULL, NULL, NULL),
(139, 'a88e6731-50c6-4733-8454-d88701df93e9', 'news', 20, 135, 1, 'System Administrator', 'admin@example.com', 'I know this very much', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 1, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-11 01:18:45', '2025-06-11 01:18:57', NULL, NULL, NULL),
(140, '5a83e693-5fe9-483c-a4cb-4bc11b401265', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'Winning mindset', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 0, 0, 0, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-11 01:52:09', '2025-06-11 20:04:08', NULL, NULL, '2025-06-11 20:04:08'),
(141, '2da5f449-0cb1-4d3f-90a6-d6db712d9414', 'news', 2, NULL, 1, 'System Administrator', 'admin@example.com', 'Hello there', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 1, 0, 0, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-11 23:11:41', '2025-06-15 08:20:43', NULL, NULL, NULL),
(142, '1ed2d922-5f60-439d-8b57-874bd4b580d4', 'news', 2, 141, 1, 'System Administrator', 'admin@example.com', 'that works', 'approved', 'medium', 1.000, 1, 0, NULL, NULL, NULL, 0, NULL, 0, 1, 0, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-11 23:11:53', '2025-06-11 23:12:01', NULL, NULL, '2025-06-11 23:12:01');

-- --------------------------------------------------------

--
-- Table structure for table `comment_flags`
--

CREATE TABLE `comment_flags` (
  `id` int(10) UNSIGNED NOT NULL,
  `comment_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `flag_type` enum('spam','inappropriate','harassment','hate_speech','off_topic','other') NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment_votes`
--

CREATE TABLE `comment_votes` (
  `id` int(10) UNSIGNED NOT NULL,
  `comment_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `vote_type` enum('up','down') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_votes`
--

INSERT INTO `comment_votes` (`id`, `comment_id`, `user_id`, `vote_type`, `ip_address`, `created_at`, `updated_at`) VALUES
(3, 134, 1, 'up', '::1', '2025-06-10 23:34:21', '2025-06-10 23:34:21'),
(35, 139, 1, 'down', '::1', '2025-06-11 01:18:57', '2025-06-11 01:18:57'),
(37, 138, 1, 'up', '::1', '2025-06-11 01:29:51', '2025-06-11 01:29:51'),
(38, 137, 1, 'up', '::1', '2025-06-11 01:29:55', '2025-06-11 01:29:55'),
(39, 135, 1, 'up', '::1', '2025-06-11 02:10:30', '2025-06-11 02:10:30'),
(40, 142, 1, 'down', '::1', '2025-06-11 23:11:56', '2025-06-11 23:11:56'),
(41, 141, 1, 'up', '::1', '2025-06-15 08:20:43', '2025-06-15 08:20:43'),
(42, 133, 1, 'up', '::1', '2025-07-15 12:52:10', '2025-07-15 12:52:10');

-- --------------------------------------------------------

--
-- Table structure for table `content_flags`
--

CREATE TABLE `content_flags` (
  `id` int(10) UNSIGNED NOT NULL,
  `content_type` enum('news','event','comment') NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `reporter_id` int(10) UNSIGNED DEFAULT NULL,
  `reporter_email` varchar(255) DEFAULT NULL,
  `flag_type` enum('spam','inappropriate','copyright','misinformation','hate_speech','other') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `ip_address` varchar(45) NOT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_likes`
--

CREATE TABLE `content_likes` (
  `id` int(10) UNSIGNED NOT NULL,
  `content_type` enum('news','event') NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_likes`
--

INSERT INTO `content_likes` (`id`, `content_type`, `content_id`, `user_id`, `ip_address`, `created_at`, `updated_at`) VALUES
(3, 'news', 11, 1, '::1', '2025-06-08 18:46:36', '2025-06-08 18:46:36'),
(5, 'news', 3, 1, '::1', '2025-06-08 18:48:12', '2025-06-08 18:48:12'),
(7, 'news', 4, 1, '::1', '2025-06-08 18:49:22', '2025-06-08 18:49:22'),
(34, 'news', 17, 1, '::1', '2025-06-11 01:04:35', '2025-06-11 01:04:35'),
(37, 'news', 19, 1, '::1', '2025-06-11 08:19:22', '2025-06-11 08:19:22'),
(38, 'news', 13, 1, '::1', '2025-06-11 08:20:11', '2025-06-11 08:20:11'),
(39, 'news', 7, 1, '::1', '2025-06-11 19:35:17', '2025-06-11 19:35:17'),
(40, 'news', 12, 1, '::1', '2025-06-11 20:19:18', '2025-06-11 20:19:18'),
(41, 'news', 18, 1, '::1', '2025-06-11 20:19:29', '2025-06-11 20:19:29'),
(42, 'news', 9, 1, '::1', '2025-06-11 20:19:40', '2025-06-11 20:19:40'),
(49, 'news', 2, 1, '::1', '2025-06-12 07:39:45', '2025-06-12 07:39:45'),
(52, 'news', 20, 1, '::1', '2025-06-12 07:56:50', '2025-06-12 07:56:50'),
(59, 'event', 45, 1, '::1', '2025-07-09 17:27:13', '2025-07-09 17:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `content_media`
--

CREATE TABLE `content_media` (
  `id` int(10) UNSIGNED NOT NULL,
  `content_type` enum('news','event') NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `media_id` int(10) UNSIGNED NOT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_tags`
--

CREATE TABLE `content_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `content_type` enum('news','event') NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_tags`
--

INSERT INTO `content_tags` (`id`, `content_type`, `content_id`, `tag_id`, `created_at`, `updated_at`) VALUES
(112, 'event', 45, 109, '2025-06-06 13:15:50', '2025-06-06 13:15:50'),
(113, 'event', 45, 110, '2025-06-06 13:15:50', '2025-06-06 13:15:50'),
(114, 'event', 42, 111, '2025-06-06 13:20:15', '2025-06-06 13:20:15'),
(115, 'event', 42, 112, '2025-06-06 13:20:15', '2025-06-06 13:20:15'),
(116, 'event', 43, 113, '2025-06-06 13:20:59', '2025-06-06 13:20:59'),
(117, 'event', 43, 114, '2025-06-06 13:20:59', '2025-06-06 13:20:59');

-- --------------------------------------------------------

--
-- Table structure for table `content_views`
--

CREATE TABLE `content_views` (
  `id` int(10) UNSIGNED NOT NULL,
  `content_type` enum('news','event') NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `view_date` date NOT NULL,
  `view_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_views`
--

INSERT INTO `content_views` (`id`, `content_type`, `content_id`, `user_id`, `ip_address`, `user_agent`, `referrer`, `view_date`, `view_time`, `session_id`) VALUES
(19, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:17:13', NULL),
(20, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:17:13', NULL),
(21, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:17:19', NULL),
(22, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:17:19', NULL),
(23, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:17:38', NULL),
(24, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:17:38', NULL),
(25, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:19:02', NULL),
(26, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:19:02', NULL),
(27, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:19:03', NULL),
(28, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:19:03', NULL),
(29, 'news', 8, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:22:32', NULL),
(30, 'news', 8, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:22:32', NULL),
(31, 'news', 1, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:40:24', NULL),
(32, 'news', 1, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 10:40:24', NULL),
(33, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:40:30', NULL),
(34, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:40:30', NULL),
(35, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:40:31', NULL),
(36, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:40:31', NULL),
(37, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:41:46', NULL),
(38, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:41:46', NULL),
(39, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:42:01', NULL),
(40, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:42:01', NULL),
(41, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:42:44', NULL),
(42, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:42:44', NULL),
(43, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:43:46', NULL),
(44, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:43:46', NULL),
(45, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:45:35', NULL),
(46, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:45:35', NULL),
(47, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:46:59', NULL),
(48, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:46:59', NULL),
(49, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:50:26', NULL),
(50, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:50:26', NULL),
(51, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:55:02', NULL),
(52, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:55:02', NULL),
(53, 'news', 1, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:56:44', NULL),
(54, 'news', 1, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:56:44', NULL),
(55, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:56:55', NULL),
(56, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:56:55', NULL),
(57, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:57:01', NULL),
(58, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:57:01', NULL),
(59, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:57:05', NULL),
(60, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:57:05', NULL),
(61, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:57:13', NULL),
(62, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:57:13', NULL),
(63, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:58:34', NULL),
(64, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:58:34', NULL),
(65, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:58:35', NULL),
(66, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:58:35', NULL),
(67, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:58:59', NULL),
(68, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:58:59', NULL),
(69, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:58:59', NULL),
(70, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:58:59', NULL),
(71, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:59:13', NULL),
(72, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:59:13', NULL),
(73, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:59:13', NULL),
(74, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:59:13', NULL),
(75, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:59:30', NULL),
(76, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 12:59:30', NULL),
(77, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:01:07', NULL),
(78, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:01:07', NULL),
(79, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:01:24', NULL),
(80, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:01:24', NULL),
(81, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:01:33', NULL),
(82, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:01:33', NULL),
(83, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:02:20', NULL),
(84, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:02:20', NULL),
(85, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:03:14', NULL),
(86, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:03:14', NULL),
(87, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:03:41', NULL),
(88, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:03:41', NULL),
(89, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:04:00', NULL),
(90, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:04:00', NULL),
(91, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:04:12', NULL),
(92, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:04:13', NULL),
(93, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:04:25', NULL),
(94, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:04:25', NULL),
(95, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:05:14', NULL),
(96, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:05:14', NULL),
(97, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:05:26', NULL),
(98, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:05:26', NULL),
(99, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:15', NULL),
(100, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:15', NULL),
(101, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:16', NULL),
(102, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:16', NULL),
(103, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:46', NULL),
(104, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:47', NULL),
(105, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:47', NULL),
(106, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:47', NULL),
(107, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:58', NULL),
(108, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:58', NULL),
(109, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:58', NULL),
(110, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:06:58', NULL),
(111, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:17', NULL),
(112, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:17', NULL),
(113, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:17', NULL),
(114, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:17', NULL),
(115, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:29', NULL),
(116, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:29', NULL),
(117, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:30', NULL),
(118, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:30', NULL),
(119, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:49', NULL),
(120, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:49', NULL),
(121, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:49', NULL),
(122, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:07:49', NULL),
(123, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:08:50', NULL),
(124, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:08:50', NULL),
(125, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:08:51', NULL),
(126, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:08:51', NULL),
(127, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:09:15', NULL),
(128, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:09:15', NULL),
(129, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:09:16', NULL),
(130, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:09:16', NULL),
(131, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:10:57', NULL),
(132, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:10:57', NULL),
(133, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:10:58', NULL),
(134, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:10:58', NULL),
(135, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:11:17', NULL),
(136, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:11:17', NULL),
(137, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:11:18', NULL),
(138, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:11:19', NULL),
(139, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:11:40', NULL),
(140, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:11:40', NULL),
(141, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:11:41', NULL),
(142, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:11:41', NULL),
(143, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:12:02', NULL),
(144, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:12:02', NULL),
(145, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:12:03', NULL),
(146, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:12:03', NULL),
(147, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:12:54', NULL),
(148, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:12:54', NULL),
(149, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:12:54', NULL),
(150, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:12:54', NULL),
(151, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:25', NULL),
(152, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:25', NULL),
(153, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:27', NULL),
(154, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:27', NULL),
(155, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:29', NULL),
(156, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:29', NULL),
(157, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:32', NULL),
(158, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:32', NULL),
(159, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:47', NULL),
(160, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:47', NULL),
(161, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:59', NULL),
(162, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:14:59', NULL),
(163, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:24', NULL),
(164, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:24', NULL),
(165, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:31', NULL),
(166, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:31', NULL),
(167, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:34', NULL),
(168, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:34', NULL),
(169, 'news', 9, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:40', NULL),
(170, 'news', 9, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:40', NULL),
(171, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:44', NULL),
(172, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:44', NULL),
(173, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:52', NULL),
(174, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:15:52', NULL),
(175, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:16:29', NULL),
(176, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:16:29', NULL),
(177, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:16:34', NULL),
(178, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:16:34', NULL),
(179, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:17:08', NULL),
(180, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:17:08', NULL),
(181, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:17:14', NULL),
(182, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:17:14', NULL),
(183, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:17:28', NULL),
(184, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:17:29', NULL),
(185, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:19:06', NULL),
(186, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:19:06', NULL),
(187, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:19:29', NULL),
(188, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:19:29', NULL),
(189, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:20:02', NULL),
(190, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:20:02', NULL),
(191, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:20:15', NULL),
(192, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:20:15', NULL),
(193, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:20:47', NULL),
(194, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:20:47', NULL),
(195, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:21:00', NULL),
(196, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:21:00', NULL),
(197, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:22:27', NULL),
(198, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:22:27', NULL),
(199, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:22:51', NULL),
(200, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:22:51', NULL),
(201, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:22:52', NULL),
(202, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:22:52', NULL),
(203, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:22:59', NULL),
(204, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:22:59', NULL),
(205, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:23:38', NULL),
(206, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:23:38', NULL),
(207, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:23:58', NULL),
(208, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:23:58', NULL),
(209, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:25:05', NULL),
(210, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:25:05', NULL),
(211, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:25:31', NULL),
(212, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:25:31', NULL),
(213, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:26:07', NULL),
(214, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:26:07', NULL),
(215, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:26:28', NULL),
(216, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:26:28', NULL),
(217, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:26:28', NULL),
(218, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:26:29', NULL),
(219, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:26:48', NULL),
(220, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:26:48', NULL),
(221, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:27:40', NULL),
(222, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:27:40', NULL),
(223, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:27:56', NULL),
(224, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:27:56', NULL),
(225, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:28:14', NULL),
(226, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:28:14', NULL),
(227, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:28:32', NULL),
(228, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:28:32', NULL),
(229, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:28:59', NULL),
(230, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:28:59', NULL),
(231, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:31:33', NULL),
(232, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:31:33', NULL),
(233, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:32:10', NULL),
(234, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:32:10', NULL),
(235, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:34:56', NULL),
(236, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:34:56', NULL),
(237, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:38:05', NULL),
(238, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:38:05', NULL),
(239, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:38:20', NULL),
(240, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:38:20', NULL),
(241, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:38:30', NULL),
(242, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:38:30', NULL),
(243, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:38:52', NULL),
(244, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:38:52', NULL),
(245, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:39:09', NULL),
(246, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:39:09', NULL),
(247, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:39:26', NULL),
(248, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:39:26', NULL),
(249, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:44:39', NULL),
(250, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:44:39', NULL),
(251, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:45:27', NULL),
(252, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:45:28', NULL),
(253, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:48:00', NULL),
(254, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:48:00', NULL),
(255, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:49:01', NULL),
(256, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:49:17', NULL),
(257, 'news', 1, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 13:49:29', NULL),
(258, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 14:37:53', NULL),
(259, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 14:37:53', NULL),
(260, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 15:10:16', NULL),
(261, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 15:10:16', NULL),
(262, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 15:11:10', NULL),
(263, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 15:11:10', NULL),
(264, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 17:50:51', NULL),
(265, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 17:50:51', NULL),
(266, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:34:22', NULL),
(267, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:34:22', NULL),
(268, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:53:57', NULL),
(269, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:53:57', NULL),
(270, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:59:43', NULL),
(271, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:59:43', NULL),
(272, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:59:47', NULL),
(273, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:59:47', NULL),
(274, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:59:49', NULL),
(275, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 18:59:50', NULL),
(276, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:01:59', NULL),
(277, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:01:59', NULL),
(278, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:02:02', NULL),
(279, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:02:02', NULL),
(280, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:02:27', NULL),
(281, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:02:28', NULL),
(282, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:04:27', NULL),
(283, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:04:27', NULL),
(284, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:04:55', NULL),
(285, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:04:55', NULL),
(286, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:05:54', NULL),
(287, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:05:54', NULL),
(288, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:06:06', NULL),
(289, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:06:06', NULL),
(290, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:06:29', NULL),
(291, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:06:29', NULL),
(292, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:07:43', NULL),
(293, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:07:43', NULL),
(294, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:07:57', NULL),
(295, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:07:57', NULL),
(296, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:08:03', NULL),
(297, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:08:03', NULL),
(298, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:09:18', NULL),
(299, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:09:18', NULL),
(300, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:10:15', NULL),
(301, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:10:15', NULL),
(302, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:13:00', NULL),
(303, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:13:00', NULL),
(304, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:13:30', NULL),
(305, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:13:30', NULL),
(306, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:50:06', NULL),
(307, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 19:50:06', NULL),
(308, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:08:10', NULL),
(309, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:08:10', NULL),
(310, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:09:04', NULL),
(311, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:09:04', NULL),
(312, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:10:15', NULL),
(313, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:10:15', NULL),
(314, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:17:10', NULL),
(315, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:17:10', NULL),
(316, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:19:28', NULL),
(317, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:19:28', NULL),
(318, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:20:03', NULL),
(319, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:20:03', NULL),
(320, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:20:17', NULL),
(321, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:20:17', NULL),
(322, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:20:56', NULL),
(323, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:20:56', NULL),
(324, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:23:31', NULL),
(325, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:23:31', NULL),
(326, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:23:40', NULL),
(327, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:23:40', NULL),
(328, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:24:34', NULL),
(329, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:24:34', NULL),
(330, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:24:48', NULL),
(331, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:24:48', NULL),
(332, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:25:58', NULL),
(333, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:25:59', NULL),
(334, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:26:06', NULL),
(335, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:26:06', NULL),
(336, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:26:19', NULL),
(337, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:26:19', NULL),
(338, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:58:02', NULL),
(339, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:58:02', NULL),
(340, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:58:17', NULL),
(341, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:58:17', NULL),
(342, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:58:28', NULL),
(343, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 20:58:28', NULL),
(344, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:00:11', NULL),
(345, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:00:11', NULL),
(346, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:01:36', NULL),
(347, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:01:36', NULL),
(348, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:02:34', NULL),
(349, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:02:34', NULL),
(350, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:03:08', NULL),
(351, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:03:08', NULL),
(352, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:03:54', NULL),
(353, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:03:54', NULL),
(354, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:04:32', NULL),
(355, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:04:32', NULL),
(356, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:04:54', NULL),
(357, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:04:54', NULL),
(358, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:05:03', NULL),
(359, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:05:03', NULL),
(360, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:36:07', NULL),
(361, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:36:07', NULL),
(362, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:37:32', NULL),
(363, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:37:32', NULL),
(364, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:37:43', NULL),
(365, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:37:43', NULL),
(366, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:37:51', NULL),
(367, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:37:51', NULL),
(368, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:37:56', NULL),
(369, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 21:37:57', NULL),
(370, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:10:33', NULL),
(371, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:10:33', NULL),
(372, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:10:53', NULL),
(373, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:10:53', NULL),
(374, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:14:36', NULL),
(375, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:14:36', NULL),
(376, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:19:57', NULL),
(377, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:19:57', NULL),
(378, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:19:57', NULL),
(379, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:19:57', NULL),
(380, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:20:39', NULL),
(381, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:20:39', NULL),
(382, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:20:41', NULL),
(383, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:20:41', NULL),
(384, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:20:41', NULL),
(385, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-08', '2025-06-08 23:20:41', NULL),
(386, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:16:32', NULL),
(387, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:16:33', NULL),
(388, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:16:33', NULL),
(389, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:16:33', NULL),
(390, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:17:07', NULL),
(391, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:17:07', NULL),
(392, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:17:08', NULL),
(393, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:17:08', NULL),
(394, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:17:38', NULL),
(395, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:17:38', NULL),
(396, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:17:38', NULL),
(397, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:17:38', NULL),
(398, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:18:00', NULL),
(399, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:18:01', NULL),
(400, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:18:01', NULL),
(401, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:18:01', NULL),
(402, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:18:36', NULL),
(403, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:18:36', NULL),
(404, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:18:36', NULL),
(405, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:18:36', NULL),
(406, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:21:17', NULL),
(407, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:21:18', NULL),
(408, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:21:18', NULL),
(409, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:21:18', NULL),
(410, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:21:44', NULL),
(411, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:21:44', NULL),
(412, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:21:44', NULL),
(413, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:21:44', NULL),
(414, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:22:06', NULL),
(415, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:22:06', NULL),
(416, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:22:06', NULL),
(417, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:22:06', NULL),
(418, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:22:13', NULL),
(419, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:22:13', NULL),
(420, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:22:13', NULL),
(421, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:22:13', NULL),
(422, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:06', NULL),
(423, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:06', NULL),
(424, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:06', NULL),
(425, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:07', NULL),
(426, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:24', NULL),
(427, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:24', NULL),
(428, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:24', NULL),
(429, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:24', NULL),
(430, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:49', NULL),
(431, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:49', NULL),
(432, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:49', NULL),
(433, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:26:49', NULL),
(434, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:27:01', NULL),
(435, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:27:01', NULL),
(436, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:27:01', NULL),
(437, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:27:01', NULL),
(438, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:28:51', NULL),
(439, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:28:51', NULL),
(440, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:28:51', NULL),
(441, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:28:52', NULL),
(442, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:08', NULL),
(443, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:08', NULL),
(444, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:08', NULL),
(445, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:08', NULL),
(446, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:12', NULL),
(447, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:12', NULL),
(448, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:13', NULL),
(449, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:13', NULL),
(450, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:54', NULL),
(451, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:54', NULL),
(452, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:55', NULL),
(453, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:29:55', NULL),
(454, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:32:17', NULL),
(455, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:32:17', NULL),
(456, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:32:17', NULL),
(457, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:32:18', NULL),
(458, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:32:25', NULL),
(459, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:32:25', NULL),
(460, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:32:25', NULL),
(461, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:32:25', NULL),
(462, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:39:43', NULL),
(463, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:39:43', NULL),
(464, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:39:44', NULL),
(465, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:39:44', NULL),
(466, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:39:55', NULL),
(467, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:39:55', NULL),
(468, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:39:55', NULL),
(469, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:39:55', NULL),
(470, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:40:52', NULL),
(471, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:40:52', NULL),
(472, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:40:52', NULL),
(473, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:40:52', NULL),
(474, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:43:45', NULL),
(475, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:43:45', NULL),
(476, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:43:46', NULL),
(477, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:43:46', NULL),
(478, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:43:57', NULL),
(479, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:43:57', NULL),
(480, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:43:57', NULL),
(481, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:43:57', NULL),
(482, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:48:03', NULL),
(483, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:48:03', NULL),
(484, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:48:03', NULL),
(485, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:48:03', NULL),
(486, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:48:29', NULL),
(487, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:48:29', NULL),
(488, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:48:29', NULL),
(489, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:48:29', NULL),
(490, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:50:32', NULL),
(491, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:50:32', NULL),
(492, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:50:32', NULL),
(493, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:50:32', NULL),
(494, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:50:44', NULL),
(495, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:50:44', NULL),
(496, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:50:44', NULL),
(497, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 04:50:44', NULL),
(498, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 18:58:46', NULL),
(499, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 22:17:59', NULL),
(500, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 22:17:59', NULL),
(501, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 22:18:06', NULL),
(502, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 22:18:06', NULL),
(503, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 22:18:18', NULL),
(504, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 22:18:18', NULL),
(505, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 22:18:34', NULL),
(506, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-09', '2025-06-09 22:18:34', NULL),
(507, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 09:42:19', NULL),
(508, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 09:42:19', NULL),
(509, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 09:43:21', NULL),
(510, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 09:43:22', NULL),
(511, 'news', 9, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 09:44:51', NULL),
(512, 'news', 9, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 09:44:51', NULL),
(513, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:22:40', NULL),
(514, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:22:40', NULL),
(515, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:23:15', NULL),
(516, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:23:16', NULL),
(517, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:23:38', NULL),
(518, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:23:38', NULL),
(519, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:28:11', NULL),
(520, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:28:12', NULL),
(521, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:41:01', NULL),
(522, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:41:01', NULL),
(523, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:52:40', NULL),
(524, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:52:40', NULL),
(525, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:53:49', NULL),
(526, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:53:49', NULL),
(527, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:53:55', NULL),
(528, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 20:53:55', NULL),
(529, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 20:55:50', NULL),
(530, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 20:56:03', NULL),
(531, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 20:56:18', NULL),
(532, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 20:56:35', NULL),
(533, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 20:56:51', NULL),
(534, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 20:57:04', NULL),
(535, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 20:57:05', NULL),
(536, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 20:59:24', NULL),
(537, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 20:59:45', NULL),
(538, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 21:01:06', NULL),
(539, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 21:03:19', NULL),
(540, 'news', 16, NULL, '127.0.0.1', NULL, NULL, '2025-06-10', '2025-06-10 21:03:36', NULL),
(541, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:04:47', NULL),
(542, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:04:47', NULL),
(543, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:04:48', NULL),
(544, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:04:48', NULL),
(545, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:06:44', NULL),
(546, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:06:44', NULL),
(547, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:06:44', NULL),
(548, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:06:44', NULL),
(549, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:07:08', NULL),
(550, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:07:08', NULL),
(551, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:07:08', NULL),
(552, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:07:08', NULL),
(553, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:07:20', NULL),
(554, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:07:20', NULL),
(555, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:07:21', NULL),
(556, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:07:21', NULL),
(557, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:12:42', NULL),
(558, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:12:42', NULL),
(559, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:12:42', NULL),
(560, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:12:42', NULL),
(561, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:14:21', NULL),
(562, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:14:21', NULL),
(563, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:14:22', NULL),
(564, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:14:22', NULL),
(565, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:15:05', NULL),
(566, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:15:05', NULL),
(567, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:15:05', NULL),
(568, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:15:05', NULL),
(569, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:15:53', NULL),
(570, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:15:53', NULL),
(571, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:15:53', NULL),
(572, 'news', 15, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:15:53', NULL),
(573, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:16:49', NULL),
(574, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:16:49', NULL),
(575, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:16:49', NULL),
(576, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:16:49', NULL),
(577, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:20:24', NULL),
(578, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:20:24', NULL),
(579, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:20:24', NULL),
(580, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:20:24', NULL),
(581, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:20:34', NULL),
(582, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:20:34', NULL),
(583, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:20:34', NULL),
(584, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:20:35', NULL),
(585, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:04', NULL),
(586, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:04', NULL),
(587, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:05', NULL),
(588, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:05', NULL),
(589, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:17', NULL),
(590, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:17', NULL),
(591, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:17', NULL),
(592, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:18', NULL),
(593, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:22', NULL),
(594, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:22', NULL),
(595, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:22', NULL),
(596, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:22', NULL),
(597, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:57', NULL),
(598, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:57', NULL),
(599, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:58', NULL),
(600, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:21:58', NULL),
(601, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:23:47', NULL),
(602, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:23:47', NULL),
(603, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:23:48', NULL),
(604, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:23:48', NULL),
(605, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:23:50', NULL),
(606, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:23:50', NULL),
(607, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:23:50', NULL),
(608, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:23:51', NULL),
(609, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:28:41', NULL);
INSERT INTO `content_views` (`id`, `content_type`, `content_id`, `user_id`, `ip_address`, `user_agent`, `referrer`, `view_date`, `view_time`, `session_id`) VALUES
(610, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:28:41', NULL),
(611, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:28:41', NULL),
(612, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:28:41', NULL),
(613, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:29:25', NULL),
(614, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:29:25', NULL),
(615, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:29:25', NULL),
(616, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:29:25', NULL),
(617, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:29:45', NULL),
(618, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:29:45', NULL),
(619, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:29:45', NULL),
(620, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:29:45', NULL),
(621, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:30:12', NULL),
(622, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:30:12', NULL),
(623, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:30:13', NULL),
(624, 'news', 3, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:30:13', NULL),
(625, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:41:18', NULL),
(626, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:41:18', NULL),
(627, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:41:19', NULL),
(628, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:41:19', NULL),
(629, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:47:13', NULL),
(630, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:47:13', NULL),
(631, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:47:14', NULL),
(632, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:47:14', NULL),
(633, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:48:43', NULL),
(634, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:48:43', NULL),
(635, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:48:43', NULL),
(636, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:48:43', NULL),
(637, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:50:16', NULL),
(638, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:50:16', NULL),
(639, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:50:16', NULL),
(640, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:50:17', NULL),
(641, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:54:10', NULL),
(642, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:54:10', NULL),
(643, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:54:10', NULL),
(644, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:54:10', NULL),
(645, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:55:19', NULL),
(646, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:55:19', NULL),
(647, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:55:20', NULL),
(648, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:55:20', NULL),
(649, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:57:12', NULL),
(650, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:57:12', NULL),
(651, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:57:12', NULL),
(652, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:57:12', NULL),
(653, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:57:47', NULL),
(654, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:57:47', NULL),
(655, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:57:47', NULL),
(656, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:57:47', NULL),
(657, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:59:32', NULL),
(658, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:59:32', NULL),
(659, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:59:32', NULL),
(660, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 21:59:32', NULL),
(661, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:01:49', NULL),
(662, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:01:50', NULL),
(663, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:01:50', NULL),
(664, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:01:50', NULL),
(665, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:02:39', NULL),
(666, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:02:39', NULL),
(667, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:02:39', NULL),
(668, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:02:39', NULL),
(669, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:09:27', NULL),
(670, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:09:27', NULL),
(671, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:09:27', NULL),
(672, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:09:27', NULL),
(673, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:10:16', NULL),
(674, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:10:16', NULL),
(675, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:10:16', NULL),
(676, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:10:16', NULL),
(677, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:11:28', NULL),
(678, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:11:28', NULL),
(679, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:11:28', NULL),
(680, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:11:28', NULL),
(681, 'news', 5, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:14:00', NULL),
(682, 'news', 5, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:14:00', NULL),
(683, 'news', 5, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:14:00', NULL),
(684, 'news', 5, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:14:00', NULL),
(685, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:14:03', NULL),
(686, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:14:03', NULL),
(687, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:30:05', NULL),
(688, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:30:05', NULL),
(689, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:30:05', NULL),
(690, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:30:05', NULL),
(691, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:38:18', NULL),
(692, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:38:18', NULL),
(693, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:38:18', NULL),
(694, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 22:38:18', NULL),
(695, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:09:35', NULL),
(696, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:09:35', NULL),
(697, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:09:36', NULL),
(698, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:09:36', NULL),
(699, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:22:34', NULL),
(700, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:22:34', NULL),
(701, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:22:34', NULL),
(702, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:22:34', NULL),
(703, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:23:39', NULL),
(704, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:23:39', NULL),
(705, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:23:39', NULL),
(706, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:23:39', NULL),
(707, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:30:27', NULL),
(708, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:30:27', NULL),
(709, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:30:27', NULL),
(710, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:30:27', NULL),
(711, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:33:15', NULL),
(712, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:33:15', NULL),
(713, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:33:15', NULL),
(714, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:33:15', NULL),
(715, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:34:09', NULL),
(716, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:34:09', NULL),
(717, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:34:09', NULL),
(718, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:34:09', NULL),
(719, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:34:23', NULL),
(720, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:34:23', NULL),
(721, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:34:24', NULL),
(722, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:34:24', NULL),
(723, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:37:41', NULL),
(724, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:37:41', NULL),
(725, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:37:41', NULL),
(726, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:37:41', NULL),
(727, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:37:47', NULL),
(728, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:37:47', NULL),
(729, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:37:47', NULL),
(730, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:37:47', NULL),
(731, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:06', NULL),
(732, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:06', NULL),
(733, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:06', NULL),
(734, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:06', NULL),
(735, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:13', NULL),
(736, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:13', NULL),
(737, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:13', NULL),
(738, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:13', NULL),
(739, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:24', NULL),
(740, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:24', NULL),
(741, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:24', NULL),
(742, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:38:24', NULL),
(743, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:08', NULL),
(744, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:08', NULL),
(745, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:08', NULL),
(746, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:08', NULL),
(747, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:13', NULL),
(748, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:13', NULL),
(749, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:13', NULL),
(750, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:13', NULL),
(751, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:21', NULL),
(752, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:21', NULL),
(753, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:21', NULL),
(754, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:21', NULL),
(755, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:25', NULL),
(756, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:25', NULL),
(757, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:25', NULL),
(758, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:25', NULL),
(759, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:52', NULL),
(760, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:52', NULL),
(761, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:52', NULL),
(762, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:42:52', NULL),
(763, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:06', NULL),
(764, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:06', NULL),
(765, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:06', NULL),
(766, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:06', NULL),
(767, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:14', NULL),
(768, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:14', NULL),
(769, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:15', NULL),
(770, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:15', NULL),
(771, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:19', NULL),
(772, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:19', NULL),
(773, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:19', NULL),
(774, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:19', NULL),
(775, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:32', NULL),
(776, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:32', NULL),
(777, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:33', NULL),
(778, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:33', NULL),
(779, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:36', NULL),
(780, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:36', NULL),
(781, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:36', NULL),
(782, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:43:36', NULL),
(783, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:46:41', NULL),
(784, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:46:42', NULL),
(785, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:46:42', NULL),
(786, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:46:42', NULL),
(787, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:57:24', NULL),
(788, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:57:24', NULL),
(789, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:57:24', NULL),
(790, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-10', '2025-06-10 23:57:24', NULL),
(791, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:00:14', NULL),
(792, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:00:14', NULL),
(793, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:00:14', NULL),
(794, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:00:14', NULL),
(795, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:14:18', NULL),
(796, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:14:18', NULL),
(797, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:14:18', NULL),
(798, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:14:18', NULL),
(799, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:20:37', NULL),
(800, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:20:37', NULL),
(801, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:20:37', NULL),
(802, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:20:37', NULL),
(803, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:23:37', NULL),
(804, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:23:37', NULL),
(805, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:23:37', NULL),
(806, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:23:37', NULL),
(807, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:25:12', NULL),
(808, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:25:12', NULL),
(809, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:25:12', NULL),
(810, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:25:13', NULL),
(811, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:27:49', NULL),
(812, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:27:49', NULL),
(813, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:27:49', NULL),
(814, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:27:49', NULL),
(815, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:28:21', NULL),
(816, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:28:21', NULL),
(817, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:28:21', NULL),
(818, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:28:21', NULL),
(819, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:29:04', NULL),
(820, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:29:04', NULL),
(821, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:29:04', NULL),
(822, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:29:04', NULL),
(823, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:29:31', NULL),
(824, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:29:32', NULL),
(825, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:29:32', NULL),
(826, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:29:32', NULL),
(827, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:30:09', NULL),
(828, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:30:09', NULL),
(829, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:30:10', NULL),
(830, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:30:10', NULL),
(831, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:31:09', NULL),
(832, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:31:09', NULL),
(833, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:31:09', NULL),
(834, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:31:09', NULL),
(835, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:31:52', NULL),
(836, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:31:52', NULL),
(837, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:31:52', NULL),
(838, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:31:52', NULL),
(839, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:14', NULL),
(840, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:14', NULL),
(841, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:14', NULL),
(842, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:14', NULL),
(843, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:34', NULL),
(844, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:34', NULL),
(845, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:35', NULL),
(846, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:35', NULL),
(847, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:42', NULL),
(848, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:42', NULL),
(849, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:42', NULL),
(850, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:32:42', NULL),
(851, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:19', NULL),
(852, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:19', NULL),
(853, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:20', NULL),
(854, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:20', NULL),
(855, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:22', NULL),
(856, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:22', NULL),
(857, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:22', NULL),
(858, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:22', NULL),
(859, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:32', NULL),
(860, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:32', NULL),
(861, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:32', NULL),
(862, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:34:32', NULL),
(863, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:35:22', NULL),
(864, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:35:23', NULL),
(865, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:35:23', NULL),
(866, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:35:23', NULL),
(867, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:41:44', NULL),
(868, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:41:45', NULL),
(869, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:41:45', NULL),
(870, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:41:45', NULL),
(871, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:42:31', NULL),
(872, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:42:31', NULL),
(873, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:42:31', NULL),
(874, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:42:31', NULL),
(875, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:42:37', NULL),
(876, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:42:37', NULL),
(877, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:42:37', NULL),
(878, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:42:37', NULL),
(879, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:43:14', NULL),
(880, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:43:14', NULL),
(881, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:43:14', NULL),
(882, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:43:14', NULL),
(883, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:07', NULL),
(884, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:07', NULL),
(885, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:07', NULL),
(886, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:07', NULL),
(887, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:16', NULL),
(888, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:16', NULL),
(889, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:16', NULL),
(890, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:16', NULL),
(891, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:30', NULL),
(892, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:30', NULL),
(893, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:30', NULL),
(894, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:44:30', NULL),
(895, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:55:43', NULL),
(896, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:55:43', NULL),
(897, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:55:43', NULL),
(898, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:55:43', NULL),
(899, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:58:35', NULL),
(900, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:58:35', NULL),
(901, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:58:35', NULL),
(902, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 00:58:35', NULL),
(903, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:00:50', NULL),
(904, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:00:50', NULL),
(905, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:00:50', NULL),
(906, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:00:50', NULL),
(907, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:00', NULL),
(908, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:00', NULL),
(909, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:00', NULL),
(910, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:00', NULL),
(911, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:09', NULL),
(912, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:09', NULL),
(913, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:09', NULL),
(914, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:09', NULL),
(915, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:54', NULL),
(916, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:54', NULL),
(917, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:54', NULL),
(918, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:01:54', NULL),
(919, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:02:06', NULL),
(920, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:02:06', NULL),
(921, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:02:06', NULL),
(922, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:02:06', NULL),
(923, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:31', NULL),
(924, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:31', NULL),
(925, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:31', NULL),
(926, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:32', NULL),
(927, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:45', NULL),
(928, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:45', NULL),
(929, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:45', NULL),
(930, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:45', NULL),
(931, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:55', NULL),
(932, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:55', NULL),
(933, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:56', NULL),
(934, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:03:56', NULL),
(935, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:04:31', NULL),
(936, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:04:31', NULL),
(937, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:04:31', NULL),
(938, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:04:31', NULL),
(939, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:04:53', NULL),
(940, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:04:54', NULL),
(941, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:04:54', NULL),
(942, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:04:54', NULL),
(943, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:07:17', NULL),
(944, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:07:17', NULL),
(945, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:07:17', NULL),
(946, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:07:17', NULL),
(947, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:07:58', NULL),
(948, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:07:58', NULL),
(949, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:07:58', NULL),
(950, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:07:59', NULL),
(951, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:09:22', NULL),
(952, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:09:22', NULL),
(953, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:09:23', NULL),
(954, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:09:23', NULL),
(955, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:10:04', NULL),
(956, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:10:04', NULL),
(957, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:10:04', NULL),
(958, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:10:04', NULL),
(959, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:10:33', NULL),
(960, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:10:33', NULL),
(961, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:10:33', NULL),
(962, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:10:34', NULL),
(963, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:11:25', NULL),
(964, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:11:25', NULL),
(965, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:11:26', NULL),
(966, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:11:26', NULL),
(967, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:12:02', NULL),
(968, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:12:02', NULL),
(969, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:12:03', NULL),
(970, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:12:03', NULL),
(971, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:12:21', NULL),
(972, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:12:21', NULL),
(973, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:12:21', NULL),
(974, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:12:22', NULL),
(975, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:14:42', NULL),
(976, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:14:42', NULL),
(977, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:14:43', NULL),
(978, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:14:43', NULL),
(979, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:15:14', NULL),
(980, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:15:14', NULL),
(981, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:15:14', NULL),
(982, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:15:15', NULL),
(983, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:15:33', NULL),
(984, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:15:33', NULL),
(985, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:15:33', NULL),
(986, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:15:33', NULL),
(987, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:00', NULL),
(988, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:00', NULL),
(989, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:00', NULL),
(990, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:00', NULL),
(991, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:34', NULL),
(992, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:34', NULL),
(993, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:35', NULL),
(994, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:35', NULL),
(995, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:59', NULL),
(996, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:59', NULL),
(997, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:59', NULL),
(998, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:16:59', NULL),
(999, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:17:15', NULL),
(1000, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:17:15', NULL),
(1001, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:17:15', NULL),
(1002, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:17:15', NULL),
(1003, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:17:21', NULL),
(1004, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:17:21', NULL),
(1005, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:17:21', NULL),
(1006, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:17:21', NULL),
(1007, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:51:59', NULL),
(1008, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:51:59', NULL),
(1009, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:51:59', NULL),
(1010, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:51:59', NULL),
(1011, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:53:12', NULL),
(1012, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:53:12', NULL),
(1013, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:53:12', NULL),
(1014, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:53:12', NULL),
(1015, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:57:20', NULL),
(1016, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:57:20', NULL),
(1017, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:57:20', NULL),
(1018, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 01:57:20', NULL),
(1019, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:00:42', NULL),
(1020, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:00:42', NULL),
(1021, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:00:42', NULL),
(1022, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:00:42', NULL),
(1023, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:00:47', NULL),
(1024, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:00:47', NULL),
(1025, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:00:47', NULL),
(1026, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:00:47', NULL),
(1027, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:02:54', NULL),
(1028, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:02:54', NULL),
(1029, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:02:54', NULL),
(1030, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:02:54', NULL),
(1031, 'news', 19, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:03:05', NULL),
(1032, 'news', 19, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:03:05', NULL),
(1033, 'news', 19, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:03:05', NULL),
(1034, 'news', 19, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:03:05', NULL),
(1035, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:04:20', NULL),
(1036, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:04:20', NULL),
(1037, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:04:20', NULL),
(1038, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:04:20', NULL),
(1039, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:04:56', NULL),
(1040, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:04:57', NULL),
(1041, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:04:57', NULL),
(1042, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:04:57', NULL),
(1043, 'news', 19, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:02', NULL),
(1044, 'news', 19, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:02', NULL),
(1045, 'news', 19, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:02', NULL),
(1046, 'news', 19, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:02', NULL),
(1047, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:06', NULL),
(1048, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:06', NULL),
(1049, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:06', NULL),
(1050, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:06', NULL),
(1051, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:10', NULL),
(1052, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:10', NULL),
(1053, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:10', NULL),
(1054, 'news', 17, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:10', NULL),
(1055, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:14', NULL),
(1056, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:15', NULL),
(1057, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:15', NULL),
(1058, 'news', 11, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:15', NULL),
(1059, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:18', NULL),
(1060, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:18', NULL),
(1061, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:18', NULL),
(1062, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:18', NULL),
(1063, 'news', 13, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:22', NULL),
(1064, 'news', 13, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:22', NULL),
(1065, 'news', 13, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:22', NULL),
(1066, 'news', 13, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:22', NULL),
(1067, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:29', NULL),
(1068, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:29', NULL),
(1069, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:29', NULL),
(1070, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:05:29', NULL),
(1071, 'news', 18, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:10:04', NULL),
(1072, 'news', 18, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:10:04', NULL),
(1073, 'news', 18, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:10:04', NULL),
(1074, 'news', 18, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 02:10:04', NULL),
(1075, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 07:21:04', NULL),
(1076, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 07:21:04', NULL),
(1077, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 07:21:04', NULL),
(1078, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 07:21:04', NULL),
(1079, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 07:21:13', NULL),
(1080, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 07:21:13', NULL),
(1081, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 07:21:13', NULL),
(1082, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 07:21:13', NULL),
(1083, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:06:14', NULL),
(1084, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:06:14', NULL),
(1085, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:06:14', NULL),
(1086, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:06:14', NULL),
(1087, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:20:25', NULL),
(1088, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:20:25', NULL),
(1089, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:20:26', NULL),
(1090, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:20:26', NULL),
(1091, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:22:46', NULL),
(1092, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:22:46', NULL),
(1093, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:22:46', NULL),
(1094, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:22:46', NULL),
(1095, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:22:47', NULL),
(1096, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:22:47', NULL),
(1097, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:22:47', NULL),
(1098, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:22:47', NULL),
(1099, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:17', NULL),
(1100, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:17', NULL),
(1101, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:17', NULL),
(1102, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:18', NULL),
(1103, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:18', NULL),
(1104, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:18', NULL),
(1105, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:18', NULL),
(1106, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:18', NULL),
(1107, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:50', NULL),
(1108, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:50', NULL),
(1109, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:50', NULL),
(1110, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:50', NULL),
(1111, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:51', NULL),
(1112, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:51', NULL),
(1113, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:51', NULL),
(1114, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:24:51', NULL),
(1115, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:26:34', NULL),
(1116, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:26:34', NULL),
(1117, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:26:34', NULL),
(1118, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:26:34', NULL),
(1119, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:26:35', NULL),
(1120, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:26:35', NULL),
(1121, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:26:35', NULL),
(1122, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:26:35', NULL),
(1123, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:29:07', NULL),
(1124, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:29:07', NULL),
(1125, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:29:07', NULL),
(1126, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:29:07', NULL),
(1127, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:29:09', NULL),
(1128, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:29:09', NULL),
(1129, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:29:09', NULL),
(1130, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:29:09', NULL),
(1131, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:30:15', NULL),
(1132, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:30:15', NULL),
(1133, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:30:15', NULL),
(1134, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:30:15', NULL),
(1135, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:30:18', NULL),
(1136, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 08:30:18', NULL),
(1137, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:24:21', NULL),
(1138, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:24:21', NULL),
(1139, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:24:21', NULL),
(1140, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:24:21', NULL),
(1141, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:25:05', NULL),
(1142, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:25:05', NULL),
(1143, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:25:05', NULL),
(1144, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:25:05', NULL),
(1145, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:34:42', NULL),
(1146, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:34:42', NULL),
(1147, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:34:42', NULL),
(1148, 'news', 14, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:34:42', NULL),
(1149, 'news', 7, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:35:06', NULL),
(1150, 'news', 7, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:35:06', NULL),
(1151, 'news', 7, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:35:06', NULL),
(1152, 'news', 7, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:35:06', NULL),
(1153, 'news', 4, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:35:33', NULL),
(1154, 'news', 4, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:35:33', NULL),
(1155, 'news', 4, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:35:33', NULL),
(1156, 'news', 4, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 19:35:33', NULL),
(1157, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:03:58', NULL),
(1158, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:03:58', NULL),
(1159, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:03:58', NULL),
(1160, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:03:58', NULL),
(1161, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:04:49', NULL),
(1162, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:04:49', NULL),
(1163, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:04:49', NULL),
(1164, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:04:49', NULL),
(1165, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:09:07', NULL),
(1166, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:09:07', NULL),
(1167, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:09:07', NULL),
(1168, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:09:07', NULL),
(1169, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:12:24', NULL),
(1170, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:12:24', NULL),
(1171, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:12:24', NULL),
(1172, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:12:24', NULL),
(1173, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:12:56', NULL),
(1174, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:12:56', NULL),
(1175, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:12:56', NULL),
(1176, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:12:56', NULL),
(1177, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:13:37', NULL),
(1178, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:13:37', NULL),
(1179, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:13:37', NULL),
(1180, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:13:37', NULL),
(1181, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:14:45', NULL),
(1182, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:14:45', NULL),
(1183, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:14:45', NULL),
(1184, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:14:45', NULL),
(1185, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:15:33', NULL),
(1186, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:15:33', NULL),
(1187, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:15:33', NULL),
(1188, 'news', 20, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:15:34', NULL),
(1189, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:16:15', NULL),
(1190, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:16:15', NULL),
(1191, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:16:15', NULL),
(1192, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:16:15', NULL),
(1193, 'news', 18, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:29:53', NULL),
(1194, 'news', 18, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:29:53', NULL),
(1195, 'news', 18, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:29:53', NULL),
(1196, 'news', 18, NULL, '::1', NULL, NULL, '2025-06-11', '2025-06-11 20:29:53', NULL),
(1197, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-12', '2025-06-12 00:32:39', NULL);
INSERT INTO `content_views` (`id`, `content_type`, `content_id`, `user_id`, `ip_address`, `user_agent`, `referrer`, `view_date`, `view_time`, `session_id`) VALUES
(1198, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-12', '2025-06-12 00:32:39', NULL),
(1199, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-12', '2025-06-12 00:32:39', NULL),
(1200, 'news', 10, NULL, '::1', NULL, NULL, '2025-06-12', '2025-06-12 00:32:39', NULL),
(1201, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:18:49', NULL),
(1202, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:18:49', NULL),
(1203, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:18:49', NULL),
(1204, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:18:49', NULL),
(1205, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:19:57', NULL),
(1206, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:19:57', NULL),
(1207, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:19:58', NULL),
(1208, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:19:58', NULL),
(1209, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:20:34', NULL),
(1210, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:20:34', NULL),
(1211, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:20:34', NULL),
(1212, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:20:34', NULL),
(1213, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:20:55', NULL),
(1214, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:20:55', NULL),
(1215, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:20:55', NULL),
(1216, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:20:55', NULL),
(1217, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:26:41', NULL),
(1218, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:26:41', NULL),
(1219, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:26:41', NULL),
(1220, 'news', 2, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:26:41', NULL),
(1221, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:26:54', NULL),
(1222, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:26:54', NULL),
(1223, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:26:54', NULL),
(1224, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:26:54', NULL),
(1225, 'news', 13, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:27:14', NULL),
(1226, 'news', 13, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:27:15', NULL),
(1227, 'news', 13, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:27:15', NULL),
(1228, 'news', 13, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:27:15', NULL),
(1229, 'news', 12, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:27:36', NULL),
(1230, 'news', 12, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:27:36', NULL),
(1231, 'news', 12, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:27:36', NULL),
(1232, 'news', 12, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:27:36', NULL),
(1233, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:39:10', NULL),
(1234, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:39:10', NULL),
(1235, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:39:10', NULL),
(1236, 'news', 16, NULL, '::1', NULL, NULL, '2025-06-15', '2025-06-15 08:39:10', NULL),
(1237, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:14:16', NULL),
(1238, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:14:16', NULL),
(1239, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:14:16', NULL),
(1240, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:14:16', NULL),
(1241, 'news', 19, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:32:17', NULL),
(1242, 'news', 19, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:32:17', NULL),
(1243, 'news', 19, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:32:17', NULL),
(1244, 'news', 19, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:32:17', NULL),
(1245, 'news', 10, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:32:32', NULL),
(1246, 'news', 10, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:32:32', NULL),
(1247, 'news', 10, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:32:32', NULL),
(1248, 'news', 10, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:32:32', NULL),
(1249, 'news', 20, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:33:14', NULL),
(1250, 'news', 20, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:33:14', NULL),
(1251, 'news', 20, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:33:14', NULL),
(1252, 'news', 20, NULL, '::1', NULL, NULL, '2025-07-05', '2025-07-05 14:33:14', NULL),
(1253, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:27:29', NULL),
(1254, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:27:29', NULL),
(1255, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:27:29', NULL),
(1256, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:27:29', NULL),
(1257, 'news', 16, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:52:17', NULL),
(1258, 'news', 16, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:52:17', NULL),
(1259, 'news', 16, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:52:17', NULL),
(1260, 'news', 16, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:52:18', NULL),
(1261, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:54:31', NULL),
(1262, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:54:31', NULL),
(1263, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:54:31', NULL),
(1264, 'news', 2, NULL, '::1', NULL, NULL, '2025-07-09', '2025-07-09 09:54:31', NULL),
(1265, 'event', 43, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/events', '2025-07-09', '2025-07-09 18:13:30', NULL),
(1266, 'event', 43, NULL, '127.0.0.1', 'Thunder Client (https://www.thunderclient.com)', '', '2025-07-09', '2025-07-09 18:15:24', NULL),
(1267, 'event', 45, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/events', '2025-07-09', '2025-07-09 18:18:05', NULL),
(1268, 'event', 42, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/events', '2025-07-09', '2025-07-09 18:18:39', NULL),
(1269, 'event', 14, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news/article/ghana-gold-output-could-rise-625percent-to-51-million-ounces-in-2025', '2025-07-09', '2025-07-09 18:22:03', NULL),
(1270, 'event', 2, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news', '2025-07-09', '2025-07-09 18:22:24', NULL),
(1271, 'event', 20, NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news', '2025-07-09', '2025-07-09 18:25:43', NULL),
(1272, 'event', 12, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news', '2025-07-11', '2025-07-11 05:44:45', NULL),
(1273, 'event', 11, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news/article/the-changes-coming-to-trumps-big-beautiful-bill-have-little-to-do-with-elon-musk', '2025-07-12', '2025-07-12 09:12:16', NULL),
(1274, 'event', 16, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news', '2025-07-12', '2025-07-12 12:52:30', NULL),
(1275, 'event', 2, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news', '2025-07-12', '2025-07-12 12:53:46', NULL),
(1276, 'event', 2, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news', '2025-07-13', '2025-07-13 08:26:10', NULL),
(1277, 'event', 16, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news/article/ghana-central-bank-keeps-key-rate-on-hold-as-inflation-eases', '2025-07-15', '2025-07-15 12:51:43', NULL),
(1278, 'event', 19, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news?category=sports', '2025-07-15', '2025-07-15 12:53:43', NULL),
(1279, 'event', 43, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/events', '2025-07-15', '2025-07-15 12:55:56', NULL),
(1280, 'event', 43, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/events', '2025-07-17', '2025-07-17 06:24:22', NULL),
(1281, 'event', 12, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/17.5 Mobile/15A5370a Safari/602.1', 'http://localhost/northcity/news/article/ghana-endorses-moroccos-autonomy-plan-for-western-sahara', '2025-07-17', '2025-07-17 06:28:25', NULL),
(1282, 'event', 12, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/17.5 Mobile/15A5370a Safari/602.1', 'http://localhost/northcity/news/article/ghana-endorses-moroccos-autonomy-plan-for-western-sahara', '2025-07-17', '2025-07-17 06:28:25', NULL),
(1283, 'event', 2, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/17.5 Mobile/15A5370a Safari/602.1', 'http://localhost/northcity/news/article/citroen-2cv-will-return-as-a-retro-city-car-to-beat-the-renault-5', '2025-07-17', '2025-07-17 06:37:51', NULL),
(1284, 'event', 2, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/17.5 Mobile/15A5370a Safari/602.1', 'http://localhost/northcity/news/article/citroen-2cv-will-return-as-a-retro-city-car-to-beat-the-renault-5', '2025-07-17', '2025-07-17 06:37:51', NULL),
(1285, 'event', 16, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news?category=sports', '2025-08-01', '2025-08-01 06:48:47', NULL),
(1286, 'event', 43, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/events', '2025-08-01', '2025-08-01 06:50:24', NULL),
(1287, 'event', 2, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news?category=entertainment', '2025-08-09', '2025-08-09 08:10:59', NULL),
(1288, 'event', 16, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/news?category=entertainment', '2025-08-09', '2025-08-09 08:11:37', NULL),
(1289, 'event', 43, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'http://localhost/northcity/events', '2025-08-11', '2025-08-11 08:18:38', NULL),
(1290, 'event', 2, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'http://localhost/northcity/news/article/citroen-2cv-will-return-as-a-retro-city-car-to-beat-the-renault-5', '2025-09-05', '2025-09-05 13:07:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cron_job_logs`
--

CREATE TABLE `cron_job_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_name` varchar(100) NOT NULL,
  `status` enum('success','error') NOT NULL,
  `execution_time_ms` decimal(10,2) DEFAULT NULL,
  `result_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`result_data`)),
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(10) UNSIGNED NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext DEFAULT NULL,
  `body_text` longtext DEFAULT NULL,
  `priority` enum('low','normal','high') DEFAULT 'normal',
  `status` enum('pending','sending','sent','failed') DEFAULT 'pending',
  `attempts` int(10) UNSIGNED DEFAULT 0,
  `max_attempts` int(10) UNSIGNED DEFAULT 3,
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_queue`
--

INSERT INTO `email_queue` (`id`, `to_email`, `to_name`, `from_email`, `from_name`, `subject`, `body_html`, `body_text`, `priority`, `status`, `attempts`, `max_attempts`, `scheduled_at`, `sent_at`, `error_message`, `created_at`, `updated_at`) VALUES
(18, 'admin@example.com', 'System Administrator', 'noreply@northcity.com', 'News Platform', 'Media Approved', '\n            <html>\n            <body>\n                <h2>Media Moderation Update</h2>\n                <p>Hello System,</p>\n                <p>Your uploaded media file \'AA1Faez7.jpeg\' has been approved and is now available.</p>\n                <p><strong>Reason:</strong> Admin reason</p>\n                <p><strong>File Details:</strong></p>\n                <ul>\n                    <li>File: AA1Faez7.jpeg</li>\n                    <li>Type: image</li>\n                    <li>Size: 64 KB</li>\n                    <li>Uploaded: Jun 5, 2025</li>\n                </ul>\n                <p>Thank you for contributing to our platform.</p>\n                <p>Best regards,<br>The Moderation Team</p>\n            </body>\n            </html>', NULL, 'normal', 'pending', 0, 3, '2025-06-06 01:13:16', NULL, NULL, '2025-06-06 01:13:16', '2025-06-06 01:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL DEFAULT uuid(),
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `organizer_id` int(10) UNSIGNED NOT NULL,
  `actual_organizer_name` varchar(255) NOT NULL,
  `actual_organizer_email` varchar(120) DEFAULT NULL,
  `actual_organizer_address` varchar(255) DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('draft','pending','published','cancelled','completed','archived') DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_online` tinyint(1) DEFAULT 0,
  `is_free` tinyint(1) DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `timezone` varchar(50) DEFAULT 'UTC',
  `venue_name` varchar(255) DEFAULT NULL,
  `venue_address` text DEFAULT NULL,
  `venue_city` varchar(100) DEFAULT NULL,
  `venue_state` varchar(100) DEFAULT NULL,
  `venue_country` varchar(100) DEFAULT NULL,
  `venue_postal_code` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `online_platform` varchar(100) DEFAULT NULL,
  `online_link` varchar(500) DEFAULT NULL,
  `online_password` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'USD',
  `max_capacity` int(10) UNSIGNED DEFAULT NULL,
  `current_attendees` int(10) UNSIGNED DEFAULT 0,
  `registration_required` tinyint(1) DEFAULT 0,
  `registration_deadline` timestamp NULL DEFAULT NULL,
  `registration_link` varchar(500) DEFAULT NULL,
  `view_count` int(10) UNSIGNED DEFAULT 0,
  `like_count` int(10) UNSIGNED DEFAULT 0,
  `bookmark_count` int(10) UNSIGNED DEFAULT 0,
  `comment_count` int(10) UNSIGNED DEFAULT 0,
  `share_count` int(10) UNSIGNED DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_flagged` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `uuid`, `title`, `slug`, `description`, `content`, `featured_image`, `organizer_id`, `actual_organizer_name`, `actual_organizer_email`, `actual_organizer_address`, `category_id`, `status`, `is_featured`, `is_online`, `is_free`, `start_date`, `end_date`, `start_time`, `end_time`, `timezone`, `venue_name`, `venue_address`, `venue_city`, `venue_state`, `venue_country`, `venue_postal_code`, `latitude`, `longitude`, `online_platform`, `online_link`, `online_password`, `price`, `currency`, `max_capacity`, `current_attendees`, `registration_required`, `registration_deadline`, `registration_link`, `view_count`, `like_count`, `bookmark_count`, `comment_count`, `share_count`, `meta_title`, `meta_description`, `approved_by`, `approved_at`, `deleted_at`, `is_flagged`, `created_at`, `updated_at`) VALUES
(42, '278bf322-60b9-494e-9693-993186d488dc', '2025 Global Prayer Works Summit Accra', '2025-global-prayer-works-summit-accra', 'Join the Global Prayer Works Summit Accra, Ghana for 3 days of strategic prayers, breakthroughs, and prophetic impartation. #GPWSAccra', 'What is GPWS Accra?\r\nThe Global Prayer Works Summit Accra is a 3-day strategic prayer conference designed to equip believers with powerful prayer tools that lead to:\r\n\r\nAnswered Prayers – Learn to pray strategically and see results.\r\nBreakthroughs and Deliverance – Break free from limitations and hindrances.\r\nProphetic Impartation – Receive divine direction and alignment for your destiny.\r\nSpiritual Warfare Mastery – You will walk out of this summit fully equipped to overcome every battle.\r\nThis year, it gets even better. The summit will feature the Ghana Faith Pilgrimage Tour (July 14 – 20, 2025), a 7-night spiritual journey through sacred sites in Ghana, designed to prepare participants for an extraordinary encounter at GPWS Accra.\r\n\r\nWho is Hosting the Event?\r\nThis year’s summit is hosted by Archbishop Nicholas Duncan-Williams, a globally recognized spiritual leader and the Presiding Archbishop of Action Chapel International. Called to lead the body of Christ in prayer and intercession, he has pioneered the Global Prayer Works Summits, transforming lives through corporate prayer, prophetic declarations, and deliverance.\r\n\r\nWhy Attend?\r\n· Imagine stepping away from the noise and distractions, connecting with other believers, and completely immersing yourself in prayer and God\'s Word. We have seen God move in mighty ways in previous summits; lives changed, bodies healed and prayers answered time and time again – all proof of His power and faithfulness. If you are tired of the cycles, need healing, breakthrough or divine direction for your next level in life, GPWS Accra is where you need to be.\r\n\r\nWho Should Attend?\r\nThis is not a summit for only individuals, but also for families, churches and intercessors. Whether you are a pastor, ministry leader, or someone who wants to grow in your walk with God, GPWS Accra has something for you.\r\n\r\nWhere and When Is It Happening?\r\nLocation: ACI Prayer Cathedral, Spintex Road, Accra, Ghana\r\nDate: July 17–19, 2025\r\n\r\nHow to Be Part of This Divine Encounter?\r\nRegister Online: Secure your spot now by clicking the [Get Tickets] button.\r\n\r\nChoose Your Package:\r\nInternational Participants: Comprehensive packages including accommodation, guided tours, and exclusive summit access.\r\n\r\nLocal Participants: Special packages for those in Ghana, including all tour experiences and summit access excluding hotel stay.\r\n\r\n[Get Tickets]\r\n\r\nGhana Faith Pilgrimage Tour\r\nDate: July 14 – 20, 2025 (7 Nights)\r\n\r\nAhead of the 2025 Global Prayer Works Summit Accra, participants will embark on a 7-night spiritual journey through the Ghana Faith Pilgrimage Tour.\r\n\r\nWalk through the Land of Faith as you:\r\n· Visit historic landmarks and sacred sites that have shaped the Christian faith in Ghana.\r\n\r\n· Engage in powerful prayer sessions at strategic locations of spiritual significance.\r\n\r\nThis is more than a tour—it’s a faith journey that prepares you for a divine encounter at GPWS Accra.\r\n\r\nA Moment of Prayer, One Word from God Can Change Your Life.\r\n\r\nSee you at GPWS Accra and the Ghana Faith Pilgrimage Tour!', 'events/2025/06/img_6842ca539ab47_1749207635.avif', 1, 'Nicholas Duncan-Williams Ministries', 'email@email.com', '37 Spintex Road Accra, Greater Accra Region', 5, 'published', 0, 1, 1, '2025-08-13', '2025-08-17', '11:59:00', '00:59:00', 'UTC', '', '', '', '', '', '', NULL, NULL, 'zoom', 'https://zoom.com', '', 0.00, 'USD', 0, 1, 0, '2025-08-08 12:00:00', '', 1, 0, 0, 0, 0, '', '', NULL, NULL, NULL, 0, '2025-06-06 11:06:17', '2025-06-06 15:30:08'),
(43, '55d062f5-e174-4ca7-bc48-d632e5abc0b5', '#MekWeVibe @Artopiia Open Mic. Game Night. Intelligent Convo.', 'mekwevibe-artopiia-open-mic-game-night-intelligent-convo', 'Catch of vibe at our unique creative space in East Legon Accra at Artopiia. We bring fine art, a gaming lounge and unique fashion. This unique space encourages creativity. Come for some friendly competition and trash talk every Saturday night at 6:30pm. Enjoy open mic an karaoke as well!', 'Catch of vibe at our unique creative space in East Legon Accra at Artopiia. We bring fine art, a gaming lounge and unique fashion. This unique space encourages creativity. Come for some friendly competition and trash talk every Saturday night at 6:30pm. Enjoy open mic an karaoke as well!\r\n\r\n\r\n\r\n\r\n\r\nArtopiia is a creative wonderland that provides a safe haven for artists and art enthusiasts in Ghana to work together and thrive. Our goal is to empower the creative community, provide them with the necessary tools to be self-sufficient and assist them in monetizing their talents. Ghana is overflowing with undiscovered creative talent. We want to nurture these talents and encourage innovation.\r\n\r\nhttps://www.instagram.com/artopiia_gh/', 'events/2025/06/img_6842e9944cc13_1749215636.avif', 1, 'Artopiia General Hours of Operation', '', 'Artopiia  19 Jungle Avenue Accra, Greater Accra Region', 4, 'published', 1, 1, 1, '2025-08-10', '2025-08-11', '15:13:00', '15:13:00', 'UTC', '', '', '', '', '', '', NULL, NULL, 'meet', 'https://meeets.com', '', 0.00, 'USD', 0, 1, 1, '2025-08-07 12:00:00', '', 6, 0, 2, 0, 0, '', '', NULL, NULL, NULL, 0, '2025-06-06 13:14:33', '2025-06-06 13:26:03'),
(45, '787a2366-8360-462f-887d-f294a116d999', '#MekWeVibe @Artopiia Open Mic. Game Night. Intelligent Convo.', 'mekwevibe-artopiia-open-mic-game-night-intelligent-convo-3', 'Catch of vibe at our unique creative space in East Legon Accra at Artopiia. We bring fine art, a gaming lounge and unique fashion. This unique space encourages creativity. Come for some friendly competition and trash talk every Saturday night at 6:30pm. Enjoy open mic an karaoke as well!', 'Catch of vibe at our unique creative space in East Legon Accra at Artopiia. We bring fine art, a gaming lounge and unique fashion. This unique space encourages creativity. Come for some friendly competition and trash talk every Saturday night at 6:30pm. Enjoy open mic an karaoke as well!\r\n\r\n\r\n\r\n\r\n\r\nArtopiia is a creative wonderland that provides a safe haven for artists and art enthusiasts in Ghana to work together and thrive. Our goal is to empower the creative community, provide them with the necessary tools to be self-sufficient and assist them in monetizing their talents. Ghana is overflowing with undiscovered creative talent. We want to nurture these talents and encourage innovation.\r\n\r\nhttps://www.instagram.com/artopiia_gh/\r\n\r\n', 'events/2025/06/img_6842e9944cc13_1749215636.avif', 1, 'Artopiia General Hours of Operation', '', 'Artopiia  19 Jungle Avenue Accra, Greater Accra Region', 4, 'published', 1, 1, 1, '2025-08-10', '2025-08-11', '15:13:00', '15:13:00', 'UTC', '', '', '', '', '', '', NULL, NULL, 'meet', 'https://meeets.com', '', 0.00, 'USD', 0, 1, 1, '2025-08-07 12:00:00', '', 1, 1, 0, 0, 0, '', '', NULL, NULL, NULL, 0, '2025-06-06 13:15:50', '2025-06-06 14:38:53');

--
-- Triggers `events`
--
DELIMITER $$
CREATE TRIGGER `events_cache_invalidate` AFTER UPDATE ON `events` FOR EACH ROW BEGIN
    DELETE FROM cache_entries WHERE id LIKE CONCAT('events_%', NEW.id, '%') OR id LIKE 'events_list_%';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `event_attendees`
--

CREATE TABLE `event_attendees` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` enum('attending','cancelled') DEFAULT 'attending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_attendees`
--

INSERT INTO `event_attendees` (`id`, `event_id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 43, 1, 'attending', '2025-07-09 16:56:05', '2025-07-09 17:26:21'),
(2, 45, 1, 'attending', '2025-07-09 17:27:12', '2025-07-09 17:27:12'),
(3, 42, 1, 'attending', '2025-07-09 18:33:09', '2025-07-09 18:33:09');

-- --------------------------------------------------------

--
-- Table structure for table `file_cleanup`
--

CREATE TABLE `file_cleanup` (
  `id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `reference_table` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `scheduled_deletion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL DEFAULT uuid(),
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_type` enum('image','video','audio','document','other') NOT NULL,
  `uploader_id` int(10) UNSIGNED NOT NULL,
  `alt_text` text DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `is_approved` tinyint(1) DEFAULT 0,
  `is_rejected` tinyint(1) DEFAULT 0,
  `is_flagged` tinyint(1) DEFAULT 0,
  `flag_count` int(10) UNSIGNED DEFAULT 0,
  `moderated_by` int(10) UNSIGNED DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `download_count` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `uuid`, `filename`, `original_filename`, `file_path`, `file_size`, `mime_type`, `file_type`, `uploader_id`, `alt_text`, `caption`, `is_public`, `is_approved`, `is_rejected`, `is_flagged`, `flag_count`, `moderated_by`, `moderated_at`, `download_count`, `created_at`, `updated_at`, `deleted_at`) VALUES
(51, 'a4d6feed-4093-444d-b495-3e66ad392605', '2025_06_06__08_30_12__6842a7143ce26_ed2029d1167a6071.jpeg', 'AA1FsF41.jpeg', 'general/2025_06_06__08_30_12__6842a7143ce26_ed2029d1167a6071.jpeg', 32768, 'image/jpeg', 'image', 1, 'Test6', 'test6', 1, 1, 0, 0, 0, 1, '2025-06-06 08:30:12', 0, '2025-06-06 08:30:12', '2025-06-06 08:30:12', NULL),
(52, '5dadaf3c-bf42-44b2-99eb-0091e3dc122a', '2025_06_06__10_44_41__6842c69955927_6960668b638eb4ff.jpeg', 'AA1FAJlJ.jpeg', 'general/2025_06_06__10_44_41__6842c69955927_6960668b638eb4ff.jpeg', 32768, 'image/jpeg', 'image', 1, 'Car retro', 'retro car', 1, 1, 0, 0, 0, 1, '2025-06-06 10:44:41', 0, '2025-06-06 10:44:41', '2025-06-06 10:44:41', NULL),
(53, '021efc41-c530-4c8f-a44c-857bd75027d5', '2025_06_06__10_45_02__6842c6aeb47ad_186670076fd06154.jpeg', 'AA1FsF41.jpeg', 'general/2025_06_06__10_45_02__6842c6aeb47ad_186670076fd06154.jpeg', 32768, 'image/jpeg', 'image', 1, 'Vintage car', 'vintage', 1, 1, 0, 0, 0, 1, '2025-06-06 10:45:02', 0, '2025-06-06 10:45:02', '2025-06-06 10:45:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `media_approval_workflow`
--

CREATE TABLE `media_approval_workflow` (
  `id` int(10) UNSIGNED NOT NULL,
  `media_id` int(10) UNSIGNED NOT NULL,
  `workflow_stage` enum('upload','auto_scan','manual_review','approved','rejected') NOT NULL,
  `stage_status` enum('pending','in_progress','completed','failed','skipped') DEFAULT 'pending',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `automated_scan_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`automated_scan_results`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_flags`
--

CREATE TABLE `media_flags` (
  `id` int(10) UNSIGNED NOT NULL,
  `media_id` int(10) UNSIGNED NOT NULL,
  `reporter_id` int(10) UNSIGNED DEFAULT NULL,
  `reporter_email` varchar(255) DEFAULT NULL,
  `flag_type` enum('inappropriate','copyright','spam','misleading','adult_content','violence','hate_speech','other') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `ip_address` varchar(45) NOT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_metadata`
--

CREATE TABLE `media_metadata` (
  `id` int(10) UNSIGNED NOT NULL,
  `media_id` int(10) UNSIGNED NOT NULL,
  `metadata_key` varchar(100) NOT NULL,
  `metadata_value` text DEFAULT NULL,
  `metadata_type` enum('string','integer','float','boolean','json','date') DEFAULT 'string',
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_moderation_log`
--

CREATE TABLE `media_moderation_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `media_id` int(10) UNSIGNED NOT NULL,
  `moderator_id` int(10) UNSIGNED NOT NULL,
  `action` enum('approve','reject','flag','hide','restore','delete') NOT NULL,
  `reason` text DEFAULT NULL,
  `previous_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`previous_status`)),
  `new_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_status`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_reports`
--

CREATE TABLE `media_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `media_id` int(10) UNSIGNED NOT NULL,
  `reporter_id` int(10) UNSIGNED DEFAULT NULL,
  `reporter_name` varchar(255) DEFAULT NULL,
  `reporter_email` varchar(255) DEFAULT NULL,
  `report_type` enum('copyright','inappropriate','spam','violence','harassment','fake_news','other') NOT NULL,
  `description` text NOT NULL,
  `evidence_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_urls`)),
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_review','resolved','dismissed','escalated') DEFAULT 'open',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `resolved_by` int(10) UNSIGNED DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `moderation_logs`
--

CREATE TABLE `moderation_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `comment_id` int(10) UNSIGNED NOT NULL,
  `action` enum('approve','reject','delete','flag','unflag','escalate') NOT NULL,
  `moderator_id` int(10) UNSIGNED DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `previous_status` enum('pending','approved','rejected','spam') DEFAULT NULL,
  `new_status` enum('pending','approved','rejected','spam') DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `moderation_queue`
--

CREATE TABLE `moderation_queue` (
  `id` int(10) UNSIGNED NOT NULL,
  `content_type` enum('news','event','comment') NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `content_title` varchar(255) DEFAULT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `moderation_score` decimal(4,3) DEFAULT 0.000,
  `auto_moderated` tinyint(1) DEFAULT 0,
  `flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flags`)),
  `reasons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reasons`)),
  `status` enum('published','pending','in_review','approved','rejected','draft','cancelled','processed') DEFAULT 'pending',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `reviewer_action` enum('approved','rejected','flagged') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `moderation_queue`
--

INSERT INTO `moderation_queue` (`id`, `content_type`, `content_id`, `content_title`, `author_id`, `priority`, `moderation_score`, `auto_moderated`, `flags`, `reasons`, `status`, `assigned_to`, `reviewer_notes`, `created_at`, `updated_at`, `reviewed_at`, `reviewed_by`, `review_notes`, `reviewer_action`) VALUES
(22, 'news', 1, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-05 09:10:03', '2025-06-06 10:37:05', '2025-06-06 10:37:05', NULL, NULL, NULL),
(34, 'news', 2, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-06 10:44:02', '2025-06-06 13:25:50', '2025-06-06 13:25:50', NULL, NULL, NULL),
(36, 'event', 42, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-06 11:06:17', '2025-06-06 15:30:08', '2025-06-06 15:30:08', NULL, NULL, NULL),
(37, 'event', 43, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-06 13:14:33', '2025-06-06 13:26:03', '2025-06-06 13:26:03', NULL, NULL, NULL),
(39, 'event', 45, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-06 13:15:50', '2025-06-06 14:38:53', '2025-06-06 14:38:53', NULL, NULL, NULL),
(40, 'event', 45, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'published', NULL, 'Great enough', '2025-06-06 13:25:13', '2025-06-06 13:25:13', '2025-06-06 13:25:13', 1, NULL, NULL),
(41, 'news', 3, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-07 16:42:14', '2025-06-08 14:10:22', '2025-06-08 14:10:22', NULL, NULL, NULL),
(42, 'news', 4, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-07 16:46:52', '2025-06-08 14:10:31', '2025-06-08 14:10:31', NULL, NULL, NULL),
(43, 'news', 5, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-07 16:50:00', '2025-06-08 14:10:28', '2025-06-08 14:10:28', NULL, NULL, NULL),
(44, 'news', 6, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-07 16:50:17', '2025-06-08 14:10:45', '2025-06-08 14:10:45', NULL, NULL, NULL),
(45, 'news', 7, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-07 16:54:09', '2025-06-08 14:10:34', '2025-06-08 14:10:34', NULL, NULL, NULL),
(46, 'news', 8, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-07 17:04:13', '2025-06-08 14:10:37', '2025-06-08 14:10:37', NULL, NULL, NULL),
(47, 'news', 9, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-07 17:17:19', '2025-06-08 14:10:03', '2025-06-08 14:10:03', NULL, NULL, NULL),
(48, 'news', 10, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-07 17:18:49', '2025-06-08 14:10:11', '2025-06-08 14:10:11', NULL, NULL, NULL),
(49, 'news', 11, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-07 17:20:41', '2025-06-08 14:10:42', '2025-06-08 14:10:42', NULL, NULL, NULL),
(50, 'comment', 65, 'Love is real this life is good this is something spam negro good', 1, 'low', 0.251, 0, '[\"repetitive\",\"hate_speech\",\"repetitive\"]', '[\"Repetitive or duplicate content\",\"Contains hate speech or discriminatory language\",\"User has posted similar content recently\"]', 'processed', NULL, NULL, '2025-06-08 13:57:42', '2025-06-08 14:09:40', NULL, NULL, NULL, NULL),
(51, 'news', 12, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-09 19:14:22', '2025-06-09 19:37:53', '2025-06-09 19:37:53', NULL, NULL, NULL),
(52, 'news', 13, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-09 19:14:34', '2025-06-09 19:37:58', '2025-06-09 19:37:58', NULL, NULL, NULL),
(53, 'news', 14, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-09 19:14:44', '2025-06-09 19:37:56', '2025-06-09 19:37:56', NULL, NULL, NULL),
(54, 'news', 15, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-09 19:14:55', '2025-06-09 19:38:12', '2025-06-09 19:38:12', NULL, NULL, NULL),
(55, 'news', 16, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-09 19:15:04', '2025-06-09 19:38:00', '2025-06-09 19:38:00', NULL, NULL, NULL),
(56, 'news', 17, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-09 19:15:15', '2025-06-09 19:38:21', '2025-06-09 19:38:21', NULL, NULL, NULL),
(57, 'news', 18, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-09 19:15:30', '2025-06-09 19:38:04', '2025-06-09 19:38:04', NULL, NULL, NULL),
(58, 'news', 19, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-09 19:15:44', '2025-06-09 19:38:15', '2025-06-09 19:38:15', NULL, NULL, NULL),
(59, 'news', 20, NULL, 1, 'medium', 0.000, 0, NULL, NULL, 'approved', 1, NULL, '2025-06-09 19:15:58', '2025-06-09 19:38:07', '2025-06-09 19:38:07', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `moderation_words`
--

CREATE TABLE `moderation_words` (
  `id` int(10) UNSIGNED NOT NULL,
  `word` varchar(255) NOT NULL,
  `type` enum('profanity','spam','hate_speech','harassment') NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `is_regex` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `moderation_words`
--

INSERT INTO `moderation_words` (`id`, `word`, `type`, `severity`, `is_regex`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'spam', 'spam', 'high', 0, 1, NULL, '2025-06-04 19:26:48', '2025-06-04 19:26:48'),
(2, 'scam', 'spam', 'high', 0, 1, NULL, '2025-06-04 19:26:48', '2025-06-04 19:26:48'),
(3, 'fake', 'spam', 'medium', 0, 1, NULL, '2025-06-04 19:26:48', '2025-06-04 19:26:48'),
(4, 'bot', 'spam', 'medium', 0, 1, NULL, '2025-06-04 19:26:48', '2025-06-04 19:26:48'),
(5, 'click here', 'spam', 'high', 0, 1, NULL, '2025-06-04 19:26:48', '2025-06-04 19:26:48'),
(6, 'buy now', 'spam', 'high', 0, 1, NULL, '2025-06-04 19:26:48', '2025-06-04 19:26:48'),
(7, 'limited time', 'spam', 'medium', 0, 1, NULL, '2025-06-04 19:26:48', '2025-06-04 19:26:48'),
(8, 'act now', 'spam', 'high', 0, 1, NULL, '2025-06-04 19:26:48', '2025-06-04 19:26:48'),
(9, 'negro', 'hate_speech', 'critical', 0, 1, NULL, '2025-06-05 15:38:13', '2025-06-05 15:38:13');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL DEFAULT uuid(),
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('draft','pending','published','archived','rejected') DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_breaking` tinyint(1) DEFAULT 0,
  `is_fact_checked` tinyint(1) DEFAULT 0,
  `view_count` int(10) UNSIGNED DEFAULT 0,
  `like_count` int(10) UNSIGNED DEFAULT 0,
  `bookmark_count` int(10) UNSIGNED DEFAULT 0,
  `comment_count` int(10) UNSIGNED DEFAULT 0,
  `share_count` int(10) UNSIGNED DEFAULT 0,
  `reading_time` int(10) UNSIGNED DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_flagged` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `uuid`, `title`, `slug`, `summary`, `content`, `featured_image`, `author_id`, `category_id`, `status`, `is_featured`, `is_breaking`, `is_fact_checked`, `view_count`, `like_count`, `bookmark_count`, `comment_count`, `share_count`, `reading_time`, `meta_title`, `meta_description`, `meta_keywords`, `published_at`, `scheduled_at`, `created_at`, `updated_at`, `approved_by`, `approved_at`, `deleted_at`, `is_flagged`) VALUES
(1, '7c3e2f5c-c610-4ae6-b9e9-5781665fb1df', 'The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts', 'the-2026-lexus-rz-gets-a-tesla-plug-300-miles-of-range-simulated-shifts', 'Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.', '<h3 class=\"article-sub-heading\" style=\"font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 28px; font-weight: 600; margin-top: 32px; margin-bottom: 16px; line-height: 36px; position: relative; padding-top: 16px; padding-bottom: 0px; color: rgb(255, 255, 255); background-color: rgb(36, 36, 36);\">With up to 300 miles of range and Lexus\' first \"virtual manual gear shift system,\" the RZ gets a glow-up.</h3><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.</p><div class=\"intra-article-module\" data-t=\"{&quot;n&quot;:&quot;intraArticle&quot;,&quot;t&quot;:13}\" style=\"position: relative; z-index: 97; width: 768px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-intraArticleModule-0\"></slot></div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">But now, just like its platform-mate the&nbsp;<a href=\"https://insideevs.com/reviews/759938/2026-toyota-bz-first-look/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota bZ</a>, Lexus\' sole electric offering (<a href=\"https://insideevs.com/news/759755/lexus-es-ev-range-charging/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">for now</a>) just got a lot more interesting. Powerful, too. And able to go much further than before.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" class=\"continue-read-break\" style=\"opacity: 1; position: static; margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">The 2026 Lexus RZ gets a big raft of upgrades, including a redesigned battery and motor system, faster charging, and perhaps most interestingly of all, something called M Mode—a fancy Lexus name for simulated eight-speed gear shifts.&nbsp;<slot name=\"cont-read-break\"></slot></p><div class=\"article-image-slot image-slot-placeholder\" data-doc-id=\"cms/api/amp/image/AA1F680i\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\"><div slot=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\" class=\"article-image-slot\"><cp-article-image style=\"color: rgb(245, 245, 245);\"><div class=\"article-image-container polished\" data-t=\"{&quot;n&quot;:&quot;OpenModal&quot;,&quot;t&quot;:13,&quot;b&quot;:8,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" data-test-id=\"AA1F680i\" style=\"position: relative; margin: 0px 0px 8px; width: 768px; height: 100%;\"><button data-customhandled=\"true\" class=\"article-image-height-wrapper expandable article-image-height-wrapper-new\" data-t=\"{&quot;n&quot;:&quot;OpenModalButton&quot;,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" style=\"width: inherit; display: block; appearance: none; border-width: initial; border-style: none; border-color: initial; background-image: initial; background-position: initial; background-size: initial; background-repeat: initial; background-attachment: initial; background-origin: initial; background-clip: initial; position: relative; overflow: hidden; border-radius: 6px; cursor: zoom-in; height: 382px;\"><div style=\"background: url(&quot;https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6&quot;) center center / cover no-repeat; filter: blur(90px); height: 382px;\"></div><img class=\"article-image article-image-ux-impr article-image-new expandable\" src=\"https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6\" alt=\"2026 Lexus RZ\" title=\"2026 Lexus RZ\" loading=\"eager\" style=\"border-radius: 6px; position: absolute; top: 0px; bottom: 0px; left: 384px; transform: translateX(-50%); object-fit: contain; background: rgb(242, 242, 242); transform-origin: 0px 0px; width: 100%;\"></button><div class=\"image-caption-container image-caption-container-ux-impr articlewc-image-caption-container\" style=\"display: flex; font-size: 12px; line-height: 16px; padding: 12px 16px 8px 24px; flex-flow: column; gap: normal; background: transparent !important;\"><span class=\"image-caption\" style=\"color: rgb(255, 255, 255);\">2026 Lexus RZ</span></div></div></cp-article-image></div></slot></div><div class=\"photo-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px;\">2026 Lexus RZ</p></div><div class=\"source-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Photo by: Lexus</div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">It\'s an&nbsp;<em>interesting</em>&nbsp;choice of car to debut a technology that&nbsp;<a href=\"https://insideevs.com/features/693877/toyota-ev-manual-transmission-tested/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota has been working on for years</a>. But on the&nbsp;550e F Sport AWD trim level, you also get&nbsp;402 horsepower to play with. That should be interesting indeed. Even better, it now comes with a Tesla-style North American Charging Standard (NACS) plug for easy fast-charging.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">We first saw&nbsp;<a href=\"https://insideevs.com/news/753063/2025-lexus-rx-specs-features/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">the updated RZ at Toyota\'s Kenshiki Forum event in Brussels</a>&nbsp;this spring, but now its U.S.-market specs have landed as well. Here, it will come in three trim levels: the RZ 350e, the RZ 450e AWD and the aforementioned RZ&nbsp;550e F Sport AWD.&nbsp;</p>', 'news/2025/06/news_68415eeb21855_1749114603.jpeg', 1, 3, 'published', 1, 1, 0, 5, 0, 0, 0, 0, 2, 'range', '', NULL, '2025-06-05 16:57:26', '2025-06-13 09:09:00', '2025-06-05 09:10:03', '2025-06-10 22:41:25', NULL, NULL, NULL, 0),
(2, '3240d4ee-404f-4d57-9df4-8364cd79bee6', 'Citroen 2CV will return as a retro city car to beat the Renault 5', 'citroen-2cv-will-return-as-a-retro-city-car-to-beat-the-renault-5', 'Work has started on a back-to-basics model embodying the look and ethos of the French icon.\r\n©Haymarket Media\r\n\r\n', '<p>Work has started on a back-to-basics model embodying the look and ethos of the French icon. ©Haymarket <br></p><p><br></p><p>Media Citroën is considering reviving the legendary 2CV more than three decades after the no-frills classic went out of production, Autocar can exc</p>', 'news/2025/06/news_6842c671e51b8_1749206641.jpeg', 1, 6, 'published', 1, 1, 0, 729, 1, 8, 4, 0, 1, '', '', NULL, '2025-06-27 10:43:00', '2025-06-27 10:43:00', '2025-06-06 10:44:02', '2025-09-05 13:07:28', NULL, NULL, NULL, 0),
(3, 'd4475b15-a57b-445e-bbf9-8a9455363fbe', 'The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts (Copy)', 'the-2026-lexus-rz-gets-a-tesla-plug-300-miles-of-range-simulated-shifts-copy', 'Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.', '<h3 class=\"article-sub-heading\" style=\"font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 28px; font-weight: 600; margin-top: 32px; margin-bottom: 16px; line-height: 36px; position: relative; padding-top: 16px; padding-bottom: 0px; color: rgb(255, 255, 255); background-color: rgb(36, 36, 36);\">With up to 300 miles of range and Lexus\' first \"virtual manual gear shift system,\" the RZ gets a glow-up.</h3><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.</p><div class=\"intra-article-module\" data-t=\"{&quot;n&quot;:&quot;intraArticle&quot;,&quot;t&quot;:13}\" style=\"position: relative; z-index: 97; width: 768px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-intraArticleModule-0\"></slot></div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">But now, just like its platform-mate the&nbsp;<a href=\"https://insideevs.com/reviews/759938/2026-toyota-bz-first-look/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota bZ</a>, Lexus\' sole electric offering (<a href=\"https://insideevs.com/news/759755/lexus-es-ev-range-charging/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">for now</a>) just got a lot more interesting. Powerful, too. And able to go much further than before.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" class=\"continue-read-break\" style=\"opacity: 1; position: static; margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">The 2026 Lexus RZ gets a big raft of upgrades, including a redesigned battery and motor system, faster charging, and perhaps most interestingly of all, something called M Mode—a fancy Lexus name for simulated eight-speed gear shifts.&nbsp;<slot name=\"cont-read-break\"></slot></p><div class=\"article-image-slot image-slot-placeholder\" data-doc-id=\"cms/api/amp/image/AA1F680i\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\"><div slot=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\" class=\"article-image-slot\"><cp-article-image style=\"color: rgb(245, 245, 245);\"><div class=\"article-image-container polished\" data-t=\"{&quot;n&quot;:&quot;OpenModal&quot;,&quot;t&quot;:13,&quot;b&quot;:8,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" data-test-id=\"AA1F680i\" style=\"position: relative; margin: 0px 0px 8px; width: 768px; height: 100%;\"><button data-customhandled=\"true\" class=\"article-image-height-wrapper expandable article-image-height-wrapper-new\" data-t=\"{&quot;n&quot;:&quot;OpenModalButton&quot;,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" style=\"width: inherit; display: block; appearance: none; border-width: initial; border-style: none; border-color: initial; background-image: initial; background-position: initial; background-size: initial; background-repeat: initial; background-attachment: initial; background-origin: initial; background-clip: initial; position: relative; overflow: hidden; border-radius: 6px; cursor: zoom-in; height: 382px;\"><div style=\"background: url(&quot;https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6&quot;) center center / cover no-repeat; filter: blur(90px); height: 382px;\"></div><img class=\"article-image article-image-ux-impr article-image-new expandable\" src=\"https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6\" alt=\"2026 Lexus RZ\" title=\"2026 Lexus RZ\" loading=\"eager\" style=\"border-radius: 6px; position: absolute; top: 0px; bottom: 0px; left: 384px; transform: translateX(-50%); object-fit: contain; background: rgb(242, 242, 242); transform-origin: 0px 0px; width: 100%;\"></button><div class=\"image-caption-container image-caption-container-ux-impr articlewc-image-caption-container\" style=\"display: flex; font-size: 12px; line-height: 16px; padding: 12px 16px 8px 24px; flex-flow: column; gap: normal; background: transparent !important;\"><span class=\"image-caption\" style=\"color: rgb(255, 255, 255);\">2026 Lexus RZ</span></div></div></cp-article-image></div></slot></div><div class=\"photo-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px;\">2026 Lexus RZ</p></div><div class=\"source-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Photo by: Lexus</div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">It\'s an&nbsp;<em>interesting</em>&nbsp;choice of car to debut a technology that&nbsp;<a href=\"https://insideevs.com/features/693877/toyota-ev-manual-transmission-tested/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota has been working on for years</a>. But on the&nbsp;550e F Sport AWD trim level, you also get&nbsp;402 horsepower to play with. That should be interesting indeed. Even better, it now comes with a Tesla-style North American Charging Standard (NACS) plug for easy fast-charging.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">We first saw&nbsp;<a href=\"https://insideevs.com/news/753063/2025-lexus-rx-specs-features/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">the updated RZ at Toyota\'s Kenshiki Forum event in Brussels</a>&nbsp;this spring, but now its U.S.-market specs have landed as well. Here, it will come in three trim levels: the RZ 350e, the RZ 450e AWD and the aforementioned RZ&nbsp;550e F Sport AWD.&nbsp;</p>', 'news/2025/06/news_68446d4f4014f_1749314895.jpeg', 1, 3, 'published', 1, 1, 0, 101, 1, 0, 0, 0, 2, 'range', '', NULL, '2025-06-07 16:52:02', '2025-06-18 12:05:00', '2025-06-07 16:42:14', '2025-06-10 22:41:31', NULL, NULL, NULL, 0),
(4, '3ce12292-fd39-4bd3-b5eb-99c31b2e421e', 'Thunderstorm warning issued across large parts of England and Wales includes risk of intense downpours and frequent lightning', 'thunderstorm-warning-issued-across-large-parts-of-england-and-wales-includes-risk-of-intense', 'Forecasters predict some areas could also see hail and strong gusty winds, with the potential for flooding, power cuts and travel disruption.', '<p>A yellow warning for thunderstorms has been issued for large parts of England and Wales today.</p><p>The Met Office warning covers most of southern England, parts of the Midlands and most of South Wales between 9am until 6pm.</p><div class=\"sdc-site-outbrain sdc-site-outbrain--AR_6\" data-component-name=\"ui-vendor-outbrain\" data-target=\"\" data-widget-mapping=\"\" data-installation-keys=\"\" data-testid=\"vendor-outbrain\">\r\n    \r\n</div>\r\n<p>People in the affected areas are being warned heavy showers and thunderstorms may lead to some disruption to transport services.</p><p><strong><a href=\"https://news.sky.com/weather\" target=\"_blank\">Find out the forecast for your area</a></strong></p><p>The <a href=\"https://news.sky.com/topic/uk-weather-9424\" target=\"_blank\"><strong>UK\'s weather</strong></a> agency has also warned of frequent lightning, hail and strong gusty winds.</p>\r\n<div class=\"sdc-article-widget sdc-article-image\">\r\n  <figure class=\"sdc-article-image__figure\">\r\n    <div class=\"sdc-article-image__wrapper\" data-aspect-ratio=\"16/9\">\r\n          <img class=\"sdc-article-image__item\" src=\"https://e3.365dm.com/25/06/768x432/skynews-thunderstorm-map_6936211.png?20250607020127\" alt=\"Map showing area of yellow thunderstorm warning in England and Wales\" style=\"width: 100%;\">\r\n    </div>\r\n      <figcaption class=\"ui-media-caption\">\r\n        <span class=\"u-hide-visually\">Image:</span>\r\n        <span class=\"ui-media-caption__caption-text\">A yellow warning covers most of England, as far north as Wolverhampton, and a large part of South Wales. Pic: Met Office\r\n        </span>\r\n      </figcaption>\r\n  </figure>\r\n</div>\r\n<p>Delays to train services are possible and some short-term losses of power are also likely.</p><p>Met Office meteorologist Alex Burkill said Saturday morning will start with \"plenty of showery rain around\".</p>', 'news/2025/06/news_68446cfbf0895_1749314811.jpg', 1, 1, 'published', 1, 1, 1, 4, 1, 0, 0, 0, 1, '', '', NULL, '2025-06-07 16:52:09', '2025-06-19 23:22:00', '2025-06-07 16:46:51', '2025-06-11 19:35:33', NULL, NULL, NULL, 0),
(5, '7a2ebcef-d053-44d4-80ac-4b7e6411331a', 'Body found in search for experienced walker on Isle of Skye', 'body-found-in-search-for-experienced-walker-on-isle-of-skye', 'A body has been found in the search for a missing walker who vanished on the Isle of Skye.\r\nRoddy MacPherson, 67, was reported missing on Wednesday and his family have been informed.\r\nHe was last seen on Monday outside Sligachan Hotel, walking in the direction of the Sligachan Bridge, and has not been heard from since.', '<p>A <a href=\"https://www.independent.co.uk/topic/body\">body</a> has been found in the search for a missing walker who vanished on the <a href=\"https://www.independent.co.uk/topic/isle-of-skye\">Isle of Skye</a>.</p><p>Roddy MacPherson, 67, was reported missing on Wednesday and his family have been informed.</p><p>He\r\n was last seen on Monday outside Sligachan Hotel, walking in the \r\ndirection of the Sligachan Bridge, and has not been heard from since.</p><div id=\"article-im-prompt\"></div><p>Mr MacPherson was described as an “experienced walker” in an appeal by <a href=\"https://www.independent.co.uk/topic/police\">Police</a> Scotland.</p><p>On Friday afternoon, the body of a man was traced by police and local mountain rescue teams on Cuillins.</p><p>There\r\n would appear to be no suspicious circumstances surrounding the death \r\nand a report will be submitted to the procurator fiscal.</p><p>Formal identification has yet to be made.</p><p>A\r\n spokesperson for Skye mountain rescue team (MRT) said: “Extensive \r\nsearches were carried out, mainly focusing on the Red Hills and parts of\r\n the Northern Cuillin. On Thursday, thanks to helpful information from a\r\n local guide, efforts shifted to the area around Bruach na Frithe.</p><p>“Around\r\n midday on Friday, with improved weather and cloudless tops, a body was \r\nsighted on a grassy ledge high on the cliffs between Harta and Lota \r\nCorries. It was immediately clear that lowering the casualty to safety \r\nwould be long and technically demanding.</p><p>“A team member was \r\nlowered to a position above and confirmed there were no signs of life, \r\nand that significantly more equipment would be needed. Fortunately, \r\nStornoway Coastguard helicopter R948 was able to return and carry out a \r\ndirect lift from the ledge — an incredible effort and a huge relief.</p><div class=\"sc-toncsa-0 lfyduH\"></div><p>“The helicopter later returned multiple times to help extract the teams and equipment back to Sligachan.</p><p>“Skye\r\n MRT contributed over 350 hours to this search — not including the time \r\nand effort given by Police Scotland (N division), Royal Air Force \r\nmountain rescue service, Kintail mountain rescue, Glenelg mountain \r\nrescue, Search and Rescue Dog Association Scotland, local Coastguard \r\nteams including <a href=\"https://www.independent.co.uk/topic/hm-coastguard\">HM Coastguard</a> – Portree, Isle of Skye, and helicopter crews of R948 and R951.</p><p>“Our\r\n sincere thanks to all involved. Thanks also to the Sligachan Hotel for \r\nfeeding and supporting us on Wednesday night and Thursday.</p><p>“Skye \r\nMRT extends our deepest condolences to the family and friends of \r\nRoderick MacPherson at this very difficult time. While formal \r\nidentification has yet to be made, we understand this will come as \r\ndeeply sad news to those that knew him.”</p>', 'news/2025/06/news_68446db8c6b48_1749315000.jpeg', 1, 6, 'published', 1, 0, 1, 4, 0, 0, 0, 0, 2, '', '', NULL, '2025-06-07 16:52:00', '2025-06-11 14:12:00', '2025-06-07 16:50:00', '2025-06-10 22:14:00', NULL, NULL, NULL, 0),
(6, '6f22d3d3-aaa6-4cb3-83ae-832cd5496238', 'The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts (Copy)', 'the-2026-lexus-rz-gets-a-tesla-plug-300-miles-of-range-simulated-shifts-copy-1', 'Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.', '<h3 class=\"article-sub-heading\" style=\"font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 28px; font-weight: 600; margin-top: 32px; margin-bottom: 16px; line-height: 36px; position: relative; padding-top: 16px; padding-bottom: 0px; color: rgb(255, 255, 255); background-color: rgb(36, 36, 36);\">With up to 300 miles of range and Lexus\' first \"virtual manual gear shift system,\" the RZ gets a glow-up.</h3><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.</p><div class=\"intra-article-module\" data-t=\"{&quot;n&quot;:&quot;intraArticle&quot;,&quot;t&quot;:13}\" style=\"position: relative; z-index: 97; width: 768px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-intraArticleModule-0\"></slot></div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">But now, just like its platform-mate the&nbsp;<a href=\"https://insideevs.com/reviews/759938/2026-toyota-bz-first-look/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota bZ</a>, Lexus\' sole electric offering (<a href=\"https://insideevs.com/news/759755/lexus-es-ev-range-charging/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">for now</a>) just got a lot more interesting. Powerful, too. And able to go much further than before.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" class=\"continue-read-break\" style=\"opacity: 1; position: static; margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">The 2026 Lexus RZ gets a big raft of upgrades, including a redesigned battery and motor system, faster charging, and perhaps most interestingly of all, something called M Mode—a fancy Lexus name for simulated eight-speed gear shifts.&nbsp;<slot name=\"cont-read-break\"></slot></p><div class=\"article-image-slot image-slot-placeholder\" data-doc-id=\"cms/api/amp/image/AA1F680i\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\"><div slot=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\" class=\"article-image-slot\"><cp-article-image style=\"color: rgb(245, 245, 245);\"><div class=\"article-image-container polished\" data-t=\"{&quot;n&quot;:&quot;OpenModal&quot;,&quot;t&quot;:13,&quot;b&quot;:8,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" data-test-id=\"AA1F680i\" style=\"position: relative; margin: 0px 0px 8px; width: 768px; height: 100%;\"><button data-customhandled=\"true\" class=\"article-image-height-wrapper expandable article-image-height-wrapper-new\" data-t=\"{&quot;n&quot;:&quot;OpenModalButton&quot;,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" style=\"width: inherit; display: block; appearance: none; border-width: initial; border-style: none; border-color: initial; background-image: initial; background-position: initial; background-size: initial; background-repeat: initial; background-attachment: initial; background-origin: initial; background-clip: initial; position: relative; overflow: hidden; border-radius: 6px; cursor: zoom-in; height: 382px;\"><div style=\"background: url(&quot;https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6&quot;) center center / cover no-repeat; filter: blur(90px); height: 382px;\"></div><img class=\"article-image article-image-ux-impr article-image-new expandable\" src=\"https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6\" alt=\"2026 Lexus RZ\" title=\"2026 Lexus RZ\" loading=\"eager\" style=\"border-radius: 6px; position: absolute; top: 0px; bottom: 0px; left: 384px; transform: translateX(-50%); object-fit: contain; background: rgb(242, 242, 242); transform-origin: 0px 0px; width: 100%;\"></button><div class=\"image-caption-container image-caption-container-ux-impr articlewc-image-caption-container\" style=\"display: flex; font-size: 12px; line-height: 16px; padding: 12px 16px 8px 24px; flex-flow: column; gap: normal; background: transparent !important;\"><span class=\"image-caption\" style=\"color: rgb(255, 255, 255);\">2026 Lexus RZ</span></div></div></cp-article-image></div></slot></div><div class=\"photo-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px;\">2026 Lexus RZ</p></div><div class=\"source-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Photo by: Lexus</div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">It\'s an&nbsp;<em>interesting</em>&nbsp;choice of car to debut a technology that&nbsp;<a href=\"https://insideevs.com/features/693877/toyota-ev-manual-transmission-tested/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota has been working on for years</a>. But on the&nbsp;550e F Sport AWD trim level, you also get&nbsp;402 horsepower to play with. That should be interesting indeed. Even better, it now comes with a Tesla-style North American Charging Standard (NACS) plug for easy fast-charging.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">We first saw&nbsp;<a href=\"https://insideevs.com/news/753063/2025-lexus-rx-specs-features/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">the updated RZ at Toyota\'s Kenshiki Forum event in Brussels</a>&nbsp;this spring, but now its U.S.-market specs have landed as well. Here, it will come in three trim levels: the RZ 350e, the RZ 450e AWD and the aforementioned RZ&nbsp;550e F Sport AWD.&nbsp;</p>', 'news/2025/06/news_68446e26f1de1_1749315110.jpg', 1, 3, 'published', 0, 1, 0, 0, 0, 0, 0, 0, 2, 'range', '', NULL, '2025-06-07 16:51:57', '2025-06-09 12:06:00', '2025-06-07 16:50:17', '2025-06-10 22:41:38', NULL, NULL, NULL, 0),
(7, 'f3bdb5e7-72fb-401d-887b-020d4c36c261', 'Five common types of medication you shouldn’t have with coffee', 'five-common-types-of-medication-you-shouldnt-have-with-coffee', 'While your morning brew might feel harmless, it can interact with certain medicines to reduce effectiveness or increase side effects', '<p>For many of us, the day doesn’t start until we’ve had our first cup of <a href=\"https://www.independent.co.uk/topic/coffee\">coffee</a>.\r\n It’s comforting, energising, and one of the most widely consumed \r\nbeverages in the world. But while your morning brew might feel harmless,\r\n it can interact with certain medicines in ways that reduce their \r\neffectiveness – or increase the risk of side effects.</p><p>From common cold tablets to <a href=\"https://www.independent.co.uk/topic/antidepressants\">antidepressants</a>, <a href=\"https://www.independent.co.uk/topic/caffeine\">caffeine</a>’s\r\n impact on the body goes far beyond a quick energy boost. Tea also \r\ncontains caffeine but not in the same concentrations as coffee, and \r\ndoesn’t seem to affect people in the same way. </p><div id=\"article-im-prompt\"></div><p>Here’s what you should know about how coffee can interfere with your medications – and how to stay safe.</p>', 'news/2025/06/news_68446eb1715aa_1749315249.jpeg', 1, 9, 'published', 0, 1, 0, 4, 1, 0, 0, 0, 1, '', '', NULL, '2025-06-07 16:54:18', '2025-06-09 12:04:00', '2025-06-07 16:54:09', '2025-06-11 19:35:17', NULL, NULL, NULL, 0),
(8, 'b9f72d71-c054-4772-8403-a465b64aae26', 'The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts (Copy)', 'the-2026-lexus-rz-gets-a-tesla-plug-300-miles-of-range-simulated-shifts-copy-2', 'Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.', '<h3 class=\"article-sub-heading\" style=\"font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 28px; font-weight: 600; margin-top: 32px; margin-bottom: 16px; line-height: 36px; position: relative; padding-top: 16px; padding-bottom: 0px; color: rgb(255, 255, 255); background-color: rgb(36, 36, 36);\">With up to 300 miles of range and Lexus\' first \"virtual manual gear shift system,\" the RZ gets a glow-up.</h3><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.</p><div class=\"intra-article-module\" data-t=\"{&quot;n&quot;:&quot;intraArticle&quot;,&quot;t&quot;:13}\" style=\"position: relative; z-index: 97; width: 768px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-intraArticleModule-0\"></slot></div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">But now, just like its platform-mate the&nbsp;<a href=\"https://insideevs.com/reviews/759938/2026-toyota-bz-first-look/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota bZ</a>, Lexus\' sole electric offering (<a href=\"https://insideevs.com/news/759755/lexus-es-ev-range-charging/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">for now</a>) just got a lot more interesting. Powerful, too. And able to go much further than before.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" class=\"continue-read-break\" style=\"opacity: 1; position: static; margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">The 2026 Lexus RZ gets a big raft of upgrades, including a redesigned battery and motor system, faster charging, and perhaps most interestingly of all, something called M Mode—a fancy Lexus name for simulated eight-speed gear shifts.&nbsp;<slot name=\"cont-read-break\"></slot></p><div class=\"article-image-slot image-slot-placeholder\" data-doc-id=\"cms/api/amp/image/AA1F680i\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\"><div slot=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\" class=\"article-image-slot\"><cp-article-image style=\"color: rgb(245, 245, 245);\"><div class=\"article-image-container polished\" data-t=\"{&quot;n&quot;:&quot;OpenModal&quot;,&quot;t&quot;:13,&quot;b&quot;:8,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" data-test-id=\"AA1F680i\" style=\"position: relative; margin: 0px 0px 8px; width: 768px; height: 100%;\"><button data-customhandled=\"true\" class=\"article-image-height-wrapper expandable article-image-height-wrapper-new\" data-t=\"{&quot;n&quot;:&quot;OpenModalButton&quot;,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" style=\"width: inherit; display: block; appearance: none; border-width: initial; border-style: none; border-color: initial; background-image: initial; background-position: initial; background-size: initial; background-repeat: initial; background-attachment: initial; background-origin: initial; background-clip: initial; position: relative; overflow: hidden; border-radius: 6px; cursor: zoom-in; height: 382px;\"><div style=\"background: url(&quot;https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6&quot;) center center / cover no-repeat; filter: blur(90px); height: 382px;\"></div><img class=\"article-image article-image-ux-impr article-image-new expandable\" src=\"https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6\" alt=\"2026 Lexus RZ\" title=\"2026 Lexus RZ\" loading=\"eager\" style=\"border-radius: 6px; position: absolute; top: 0px; bottom: 0px; left: 384px; transform: translateX(-50%); object-fit: contain; background: rgb(242, 242, 242); transform-origin: 0px 0px; width: 100%;\"></button><div class=\"image-caption-container image-caption-container-ux-impr articlewc-image-caption-container\" style=\"display: flex; font-size: 12px; line-height: 16px; padding: 12px 16px 8px 24px; flex-flow: column; gap: normal; background: transparent !important;\"><span class=\"image-caption\" style=\"color: rgb(255, 255, 255);\">2026 Lexus RZ</span></div></div></cp-article-image></div></slot></div><div class=\"photo-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px;\">2026 Lexus RZ</p></div><div class=\"source-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Photo by: Lexus</div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">It\'s an&nbsp;<em>interesting</em>&nbsp;choice of car to debut a technology that&nbsp;<a href=\"https://insideevs.com/features/693877/toyota-ev-manual-transmission-tested/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota has been working on for years</a>. But on the&nbsp;550e F Sport AWD trim level, you also get&nbsp;402 horsepower to play with. That should be interesting indeed. Even better, it now comes with a Tesla-style North American Charging Standard (NACS) plug for easy fast-charging.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">We first saw&nbsp;<a href=\"https://insideevs.com/news/753063/2025-lexus-rx-specs-features/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">the updated RZ at Toyota\'s Kenshiki Forum event in Brussels</a>&nbsp;this spring, but now its U.S.-market specs have landed as well. Here, it will come in three trim levels: the RZ 350e, the RZ 450e AWD and the aforementioned RZ&nbsp;550e F Sport AWD.&nbsp;</p>', NULL, 1, 3, 'published', 0, 0, 0, 2, 0, 0, 0, 0, 2, 'range', '', NULL, '2025-06-07 17:07:03', NULL, '2025-06-07 17:04:13', '2025-06-08 14:10:37', NULL, NULL, NULL, 0);
INSERT INTO `news` (`id`, `uuid`, `title`, `slug`, `summary`, `content`, `featured_image`, `author_id`, `category_id`, `status`, `is_featured`, `is_breaking`, `is_fact_checked`, `view_count`, `like_count`, `bookmark_count`, `comment_count`, `share_count`, `reading_time`, `meta_title`, `meta_description`, `meta_keywords`, `published_at`, `scheduled_at`, `created_at`, `updated_at`, `approved_by`, `approved_at`, `deleted_at`, `is_flagged`) VALUES
(9, '3f17df5a-db44-4566-a0c7-ebe21c9a4c28', 'NASCAR to have podium celebration for top three finishers in Cup, Xfinity races in Mexico', 'nascar-to-have-podium-celebration-for-top-three-finishers-in-cup-xfinity-races-in-mexico', 'In between, the Cup Series will hold a one-hour practice at 9:30 a.m., followed by qualifying for Sunday\'s 200-lap race. Tyler Reddick is the defending winner at Michigan.', '<p class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><span style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ;\">The only time the NASCAR Cup Series has had a podium celebration was for the Clash at the LA Memorial Coliseum exhibition races from 2022-24.</span></p><ul class=\"content-list\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 0px; padding-left: 1.538em; list-style-type: none; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-size: 13px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><li style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin-bottom: 0px; font-size: inherit; line-height: inherit; list-style-type: disc;\"><div class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; --ys-page-column-spacing: 0;\"><p class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 0px 0px 0.8em; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; vertical-align: top;\"><a class=\"link \" href=\"https://www.nbcsports.com/author/dustin-long\" rel=\"nofollow noopener\" target=\"_blank\" data-ylk=\"slk:Dustin Long;elm:context_link;itc:0;sec:content-canvas\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; background-color: transparent; color: rgb(0, 99, 235); text-decoration: none;\">Dustin Long</a><span class=\"separator\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ;\">,</span></p></div></li></ul><ul class=\"content-list\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 0px; padding-left: 1.538em; list-style-type: none; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-size: 13px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><li style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin-bottom: 0px; font-size: inherit; line-height: inherit; list-style-type: disc;\"><div class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; --ys-page-column-spacing: 0;\"><p class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 0px 0px 0.8em; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; vertical-align: top;\"><a class=\"link \" href=\"https://www.nbcsports.com/author/dustin-long\" rel=\"nofollow noopener\" target=\"_blank\" data-ylk=\"slk:Dustin Long;elm:context_link;itc:0;sec:content-canvas\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; background-color: transparent; color: rgb(0, 99, 235); text-decoration: none;\">Dustin Long</a><span class=\"separator\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ;\">,</span></p></div></li></ul><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">In between, the Cup Series will hold a one-hour practice at 9:30 a.m., followed by qualifying for Sunday\'s 200-lap race. Tyler Reddick is the defending winner at Michigan.</p><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">The Truck Series will race at Michigan for the 22nd time but the first since Aug. 7, 2020, making the Brooklyn, Michigan, track one of six new tracks in 2025 (and one of four that are returning to the schedule). Zane Smith was the most recent Truck winner at Michigan.</p>', 'news/2025/06/news_6844741f4abe8_1749316639.webp', 1, 4, 'published', 0, 0, 0, 4, 1, 1, 0, 0, 1, '', '', NULL, '2025-06-07 17:20:49', '2025-06-12 22:03:00', '2025-06-07 17:17:19', '2025-06-11 20:19:41', NULL, NULL, NULL, 0),
(10, 'c7f52283-a9f2-436e-9691-daeb6bfa0fac', 'Coco Gauff claims first French Open title after fightback floors Aryna Sabalenka', 'coco-gauff-claims-first-french-open-title-after-fightback-floors-aryna-sabalenka', 'In her half-decade competing at the highest level of her sport, Coco Gauff has built an impermeable reputation for her toughness. No matter the significance of the occasion or the state of her strokes, she will fight with everything at her disposal until the very last point. More often than not, she will find a way through.', '<p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">In her half-decade competing at the highest level of her sport, Coco Gauff has built an impermeable reputation for her toughness. No matter the significance of the occasion or the state of her strokes, she will fight with everything at her disposal until the very last point. More often than not, she will find a way through.</p><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">Across the net from the best player in the world in one of the most important moments of her career, Gauff showed the full breadth of her grit and durability as she somehow plotted a path to victory, holding her own in a gripping match between the two best players in the world to topple Aryna Sabalenka, the world No 1, 6-7 (5), 6-2, 6-4 and win her first French Open title.</p><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><span style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ;\">Related:<span>&nbsp;</span></span><a href=\"https://www.theguardian.com/sport/live/2025/jun/07/aryna-sabalenka-v-coco-gauff-french-open-2025-womens-singles-final-live\" rel=\"nofollow noopener\" target=\"_blank\" data-ylk=\"slk:Aryna Sabalenka v Coco Gauff: French Open women’s singles final goes to deciding set – live;elm:context_link;itc:0;sec:content-canvas\" class=\"link \" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; background-color: transparent; color: rgb(0, 99, 235); text-decoration: none;\">Aryna Sabalenka v Coco Gauff: French Open women’s singles final goes to deciding set – live</a></p><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">Two years after also defeating Sabalenka in three sets<span>&nbsp;</span><a href=\"https://www.theguardian.com/sport/2023/sep/10/coco-gauff-battles-back-to-stun-aryna-sabalenka-and-claim-us-open-title\" rel=\"nofollow noopener\" target=\"_blank\" data-ylk=\"slk:to win her first major title;elm:context_link;itc:0;sec:content-canvas\" class=\"link \" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; background-color: transparent; color: rgb(0, 99, 235); text-decoration: none;\">to win her first major title</a><span>&nbsp;</span>at the 2023 US Open, Gauff, the world No 2, has joined a distinguished group of players as a multiple grand slam champion. She is the first American player to win the French Open title since Serena Williams in 2015 and the youngest to do so since Williams in 2002.</p>', 'news/2025/06/news_6844747958235_1749316729.webp', 1, 4, 'published', 0, 1, 0, 22, 0, 1, 0, 0, 2, '', '', NULL, '2025-06-07 17:20:47', '2025-06-10 13:04:00', '2025-06-07 17:18:49', '2025-07-05 14:32:32', NULL, NULL, NULL, 0),
(11, 'b8ab4dd3-e3bd-4cfc-a649-5fb43888e257', 'The changes coming to Trump\'s \'big beautiful bill\' have little to do with Elon Musk', 'the-changes-coming-to-trumps-big-beautiful-bill-have-little-to-do-with-elon-musk', 'On one front: The nation\'s capital was transfixed by a seismic fight between Elon Musk and President Trump, centered on the cost of the $3 trillion tax and spending bill.', '<p class=\"yf-1090901\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(59 130 246 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: currentcolor; margin: 0px 0px 32px; font-size: 20px; color: rgb(227, 227, 227); font-family: &quot;GT America&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(20, 24, 28); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">Washington was on two parallel tracks this past week when discussing President Trump\'s \"big, beautiful bill.\"</p><p class=\"yf-1090901\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(59 130 246 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: currentcolor; margin: 0px 0px 32px; font-size: 20px; color: rgb(227, 227, 227); font-family: &quot;GT America&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(20, 24, 28); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">On one front: The nation\'s capital was transfixed by<span>&nbsp;</span><a data-i13n=\"cpos:1;pos:1\" href=\"https://finance.yahoo.com/news/its-not-just-the-big-beautiful-bill-elon-musk-is-now-at-war-with-whole-swaths-of-trumps-agenda-131922774.html\" data-ylk=\"slk:a seismic fight between Elon Musk;cpos:1;pos:1;elm:context_link;itc:0;sec:content-canvas;outcm:mb_qualified_link;_E:mb_qualified_link;ct:story;\" class=\"link  yahoo-link\" data-rapid_p=\"226\" data-v9y=\"1\" style=\"color: rgb(159, 215, 255); --tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(59 130 246 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: currentcolor; text-decoration: inherit;\">a seismic fight between Elon Musk</a><span>&nbsp;</span>and President Trump, centered on the cost of the $3 trillion tax and spending bill.</p><p class=\"yf-1090901\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(59 130 246 / .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; box-sizing: border-box; border-width: 0px; border-style: solid; border-color: currentcolor; margin: 0px 0px 32px; font-size: 20px; color: rgb(227, 227, 227); font-family: &quot;GT America&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(20, 24, 28); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">On another front: Republican leaders steadily advanced the pricey package with only a few changes apparently on offer.</p>', 'news/2025/06/news_684474e920772_1749316841.jpeg', 1, 6, 'published', 0, 1, 0, 63, 1, 0, 0, 0, 1, '', '', NULL, '2025-06-07 17:20:52', '2025-06-19 12:33:00', '2025-06-07 17:20:41', '2025-07-12 09:12:16', NULL, NULL, NULL, 0);
INSERT INTO `news` (`id`, `uuid`, `title`, `slug`, `summary`, `content`, `featured_image`, `author_id`, `category_id`, `status`, `is_featured`, `is_breaking`, `is_fact_checked`, `view_count`, `like_count`, `bookmark_count`, `comment_count`, `share_count`, `reading_time`, `meta_title`, `meta_description`, `meta_keywords`, `published_at`, `scheduled_at`, `created_at`, `updated_at`, `approved_by`, `approved_at`, `deleted_at`, `is_flagged`) VALUES
(12, 'fa18dfdd-04a6-4f51-9d38-35ee97dd3bcc', 'Ghana endorses Morocco\'s autonomy plan for Western Sahara', 'ghana-endorses-moroccos-autonomy-plan-for-western-sahara', 'Ghana said on Thursday it views a Moroccan autonomy plan as the sole basis to settle the Western Sahara dispute within the framework of the UN, aligning itself with a growing number of Western, African and Arab countries that back Rabat\'s position on the dispute.', '<div data-testid=\"paragraph-0\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">RABAT,\r\n June 5 (Reuters) - Ghana said on Thursday it views a Moroccan autonomy \r\nplan as the sole basis to settle the Western Sahara dispute within the \r\nframework of the UN, aligning itself with a growing number of <a data-testid=\"Link\" href=\"https://www.reuters.com/world/africa/uk-backs-moroccos-autonomy-plan-western-sahara-lammy-says-2025-06-01/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">Western</a>, <a data-testid=\"Link\" href=\"https://www.reuters.com/world/africa/kenya-backs-moroccos-autonomy-plan-western-sahara-joint-statement-says-2025-05-26/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">African</a> and <a data-testid=\"Link\" href=\"https://www.reuters.com/article/world/bahrain-to-open-consulate-in-western-sahara-morocco-says-idUSL1N2IC1HE/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">Arab</a> countries that back Rabat\'s position on the dispute.</div><div data-testid=\"paragraph-1\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">The <a data-testid=\"Link\" href=\"https://www.reuters.com/article/world/factbox-western-sahara-an-old-conflict-on-the-verge-of-explosion-idUSKBN27T2K2/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">long-frozen conflict</a>\r\n pits Morocco, which considers the desert territory as its own, against \r\nthe Algeria-backed Polisario front, which seeks an independent state \r\nthere.</div><div data-testid=\"element\" class=\"article-body__element__2p5pI\"></div><div data-testid=\"paragraph-2\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Ghana\r\n considers the autonomy plan \"as the only realistic and sustainable \r\nbasis to a mutually agreed solution to the issue,\" said a joint \r\nstatement issued after talks between Ghana\'s foreign minister, Samuel \r\nOkudzeto Ablakwa, and his Moroccan counterpart, Nasser Bourita in Rabat.</div><div data-testid=\"paragraph-3\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">The UN should remain the exclusive framework for finding a solution to the issue, the statement said.</div><div data-testid=\"paragraph-4\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">The position was expressed few days after similar stands by Kenya and the UK, reflecting a diplomatic shift in Morocco\'s favour.</div><div data-testid=\"paragraph-5\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Ghana and Morocco also agreed to promote defense cooperation and work on a visa waiver deal.</div><div data-testid=\"element\" class=\"article-body__element__2p5pI\"></div><div data-testid=\"paragraph-6\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">With\r\n Morocco home to fertilizers and phosphates giant OCP, the two countries\r\n agreed to cooperate on food security, the statement said.</div><div data-testid=\"paragraph-7\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Moroccan\r\n fertilisers will help Ghana develop cocoa farming and reduce dependence\r\n on food imports, worth $3 billion annually, Okudzeto Ablakwa told \r\nreporters.</div><div data-testid=\"paragraph-8\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Ghana, part of the Morocco-Nigeria <a data-testid=\"Link\" href=\"https://www.reuters.com/business/energy/morocco-issues-expression-interest-lng-terminal-2025-04-23/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">pipeline</a> deal, backs Morocco\'s <a data-testid=\"Link\" href=\"https://www.reuters.com/world/africa/landlocked-burkina-mali-niger-back-sea-access-through-morocco-2025-04-28/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">initiative</a> to help landlocked Sahel states access global trade through the Atlantic, he said.</div><br>', 'news/2025/06/news_6847349c21ee5_1749496988.avif', 1, 7, 'published', 1, 1, 1, 7, 1, 1, 0, 0, 2, '', '', NULL, '2025-06-10 09:12:00', '2025-06-11 12:34:00', '2025-06-09 19:14:22', '2025-07-17 06:28:25', NULL, NULL, NULL, 0),
(13, '047f4598-3080-4904-ad42-06f274abe368', 'Ghana consumer inflation slows in May to lowest level since February 2022', 'coco-gauff-claims-first-french-open-title-after-fightback-floors-aryna-sabalenka-copy', 'In her half-decade competing at the highest level of her sport, Coco Gauff has built an impermeable reputation for her toughness. No matter the significance of the occasion or the state of her strokes, she will fight with everything at her disposal until the very last point. More often than not, she will find a way through.', '<div data-testid=\"paragraph-0\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">ACCRA,\r\n June 4 (Reuters) - Ghana\'s consumer inflation slowed in May for the \r\nfifth month in a row to its lowest level since February 2022 as \r\ninflationary pressures ease across the board in the West African state, \r\nthe country\'s statistics agency said on Wednesday.</div><div data-testid=\"paragraph-1\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Consumer\r\n inflation eased to 18.4.% year-on-year in May from 21.2% a month \r\nearlier, government statistician Alhassan Iddrisu told a news \r\nconference, adding that disinflation was expected to continue in the \r\ncoming months.</div><div data-testid=\"element\" class=\"article-body__element__2p5pI\"></div><div data-testid=\"paragraph-2\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">\"The\r\n inflation trend we are witnessing shows sustained deceleration,\" \r\nIddrisu said, adding that food remains a key inflation driver, but the \r\nsharper drop in non-food inflation suggests a broad based easing of \r\ninflation across the economy.</div><div data-testid=\"paragraph-3\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">\"This trend underscores the effectiveness of recent monetary and fiscal measures, the recent appreciation of the <a data-testid=\"Link\" href=\"https://www.reuters.com/world/africa/surge-cedi-currency-eases-ghanas-foreign-debt-burden-2025-05-28/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">Cedi</a> against the major international currencies, favourable external price dynamics and positive market sentiment,\" he added.</div><div data-testid=\"paragraph-4\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">The bellwether producer price inflation reading slowed to 18.5% in April, compared with 24.4% in March.</div><div data-testid=\"paragraph-5\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Last month, the Bank of Ghana <a data-testid=\"Link\" href=\"https://www.reuters.com/world/africa/ghana-central-bank-keeps-rate-unchanged-inflation-eases-2025-05-23/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">held</a> its main interest rate <a data-testid=\"Link\" target=\"_blank\" href=\"https://www.reuters.com/markets/quote/GHCBIR=ECI\" rel=\"noopener\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_ link__with-icon__3x3oD\">(GHCBIR=ECI)<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 32 32\" aria-hidden=\"true\" data-testid=\"NewTabSymbol\" class=\"link__new-tab-symbol__3T19s\"></svg></a></div><div data-testid=\"paragraph-5\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\"><a data-testid=\"Link\" target=\"_blank\" href=\"https://www.reuters.com/markets/quote/GHCBIR=ECI\" rel=\"noopener\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_ link__with-icon__3x3oD\"><span style=\"border: 0px; clip: rect(0px, 0px, 0px, 0px); clip-path: inset(50%); height: 1px; margin: -1px; overflow: hidden; padding: 0px; position: absolute; width: 1px; white-space: nowrap;\">, opens new tab</span></a> steady at 28.0%, maintaining a tight monetary stance and citing sustained inflationary pressures.</div><div data-testid=\"element\" class=\"article-body__element__2p5pI\"></div><div data-testid=\"paragraph-6\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Ghana has struggled to rein in \"<a data-testid=\"Link\" href=\"https://www.reuters.com/world/africa/ghana-central-bank-governor-says-inflation-remains-uncomfortably-high-2025-03-24/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">uncomfortably high</a>\" inflation, which remains well above the central bank\'s target of 8% with a margin of error of 2 percentage points.</div><div data-testid=\"paragraph-7\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">The country is recovering from its worst economic crisis in a generation, marked by disruptions in its cocoa and gold sectors.</div><div data-testid=\"paragraph-8\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Finance\r\n Minister Cassiel Ato Forson said in his March budget speech that sharp \r\nspending cuts will help bring inflation down to 11.9% by year-end.</div><div data-testid=\"element\" class=\"article-body__element__2p5pI\"></div><div class=\"article-body__element__2p5pI\"><p data-testid=\"SignOff\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__extra_small__1Mw6v body__full_width__ekUdw body__extra_small_body__3QTYe sign-off__text__PU3Aj\">Reporting by Christian Akorlie and Emmanuel Bruce;\r\nWriting by Ayen Deng Bior\r\nEditing by Bate Felix and Gareth Jone</p></div>', 'news/2025/06/news_6847357f82abf_1749497215.avif', 1, 4, 'published', 1, 1, 1, 8, 1, 0, 0, 0, 2, '', '', NULL, '2025-06-10 08:12:00', '2025-06-11 12:45:00', '2025-06-09 19:14:34', '2025-06-15 08:27:15', NULL, NULL, NULL, 0),
(14, '85a6dac2-7730-465b-abd6-966668bfc1d6', 'Ghana gold output could rise 6.25% to 5.1 million ounces in 2025', 'ghana-gold-output-could-rise-625percent-to-51-million-ounces-in-2025', 'Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.', '<div data-testid=\"paragraph-0\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">ACCRA,\r\n May 30 (Reuters) - Ghana\'s gold production could increase by around \r\n6.25% to approximately 5.1 million ounces in 2025, up from last year\'s \r\nrecord output of 4.8 million ounces, the Chamber of Mines in Africa\'s \r\ntop gold-producing nation said on Friday.</div><div data-testid=\"paragraph-1\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">High\r\n output has been driven by strong production from artisanal mining and \r\nthe introduction of new large-scale operations, countering a decline at \r\nthe country\'s aging mines.</div><div data-testid=\"element\" class=\"article-body__element__2p5pI\"></div><div data-testid=\"paragraph-2\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">The\r\n 2025 forecast published in the chamber\'s annual report follows Ghana\'s \r\nstronger-than-expected performance in 2024, when total gold output rose \r\n19.3%, cementing its position as Africa\'s top gold producer, ahead of \r\nSouth Africa and Mali.</div><div data-testid=\"paragraph-3\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">The <a data-testid=\"Link\" href=\"https://www.reuters.com/world/india/gold-hits-over-one-week-low-after-us-court-blocks-trumps-tariffs-2025-05-29/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">surging prices of the precious metal</a> have increased Ghana\'s export revenue and <a data-testid=\"Link\" href=\"https://www.reuters.com/world/africa/surge-cedi-currency-eases-ghanas-foreign-debt-burden-2025-05-28/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">strengthened its cedi currency</a>, boosting the country\'s recovery from its <a data-testid=\"Link\" href=\"https://www.reuters.com/world/africa/imf-approves-third-review-ghanas-3-billion-programme-finance-minister-says-2024-12-02/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">worst economic crisis</a> in a generation. Ghana is also a cocoa producer and oil exporter.</div><div data-testid=\"paragraph-4\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">\"We project gold output to range between 4.4 and 5.1 million ounces, buoyed by increased contributions from Newmont\'s <a data-testid=\"Link\" target=\"_blank\" href=\"https://www.reuters.com/markets/companies/NEM.N\" rel=\"noopener\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_ link__with-icon__3x3oD\">(NEM.N)<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 32 32\" aria-hidden=\"true\" data-testid=\"NewTabSymbol\" class=\"link__new-tab-symbol__3T19s\"></svg></a></div><div data-testid=\"paragraph-4\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\"><a data-testid=\"Link\" target=\"_blank\" href=\"https://www.reuters.com/markets/companies/NEM.N\" rel=\"noopener\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_ link__with-icon__3x3oD\"><span style=\"border: 0px; clip: rect(0px, 0px, 0px, 0px); clip-path: inset(50%); height: 1px; margin: -1px; overflow: hidden; padding: 0px; position: absolute; width: 1px; white-space: nowrap;\">, opens new tab</span></a>\r\n Ahafo South Mine and Shandong\'s Namdini Mine,\" Chamber of Mines \r\nPresident Michael Akafia said at an annual gathering in the capital \r\nAccra.</div><div data-testid=\"element\" class=\"article-body__element__2p5pI\"></div><h2 data-testid=\"Heading\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__medium__1kbOh text__heading_6__1qUJ5 heading__base__2T28j heading__heading_6__RtD9P article-body__heading__33EIm\">ARTISANAL BOOM</h2><div data-testid=\"paragraph-5\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Last\r\n year\'s gold output was driven by a record 39.4% contribution from \r\nsmall-scale miners whose operations Akafia said face significant \r\nuncertainty and potential disruption from ongoing regulatory changes.</div><div data-testid=\"paragraph-6\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">Ghana\'s new <a data-testid=\"Link\" href=\"https://www.reuters.com/markets/commodities/ghana-orders-foreigners-exit-gold-market-by-april-30-2025-04-14/\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_\">government has formed the GoldBod</a>\r\n to streamline gold purchases from small-scale miners, increase their \r\nearnings, and reduce the impact of smuggling. It has also removed a \r\nwithholding tax on local gold purchases.</div><figure data-testid=\"image-0\" class=\"article-image__figure__3SBJc article-body__element__2p5pI article-body__img__1AZS2\"><div data-testid=\"Image\" class=\"article-image__image__31ytm\"><div class=\"styles__image-container__3hkY5 styles__fill__22EeH styles__center_center__1CNY5 styles__apply-ratio__1JYnB styles__transition__3hwoa\" style=\"--aspect-ratio: 1.4998636487592036;\"><img src=\"https://www.reuters.com/resizer/v2/WIJ4CNNQBFJW5D6RXDCKTRJR6Q.jpg?auth=e99fb2eb4cb7890c8bcfd8c7b6c074aa2bf3f63bbd2ed2cdda205171e74d5042&amp;width=5500&amp;quality=80\" width=\"5500\" height=\"3667\" alt=\"Ghana\'s wildcat gold mining booms, poisoning people and nature\" style=\"width: 100%;\"></div></div><div data-testid=\"Body\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__ultra_small__37j9j body__full_width__ekUdw body__ultra_small_body__1lUQl article-image__figcaption__ttQIt article-body__figcaption__17dH0\"><span>A\r\n view of gold nuggets aggregated from small-scale mining, inside a \r\nsmelting facility in Accra, Ghana August 22, 2024. REUTERS/Francis \r\nKokoroko/File Photo <a data-testid=\"Link\" target=\"_blank\" href=\"https://www.reutersconnect.com/item/ghanas-wildcat-gold-mining-booms-poisoning-people-and-nature/dGFnOnJldXRlcnMuY29tLDIwMjQ6bmV3c21sX1JDMjFMOUFOQVlWSQ%3D%3D/?utm_medium=rcom-article-media&amp;utm_campaign=rcom-rcp-lead\" rel=\"noopener\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_ link__with-icon__3x3oD collapsible-caption__license__Nbbf3\">Purchase Licensing<span><span class=\"link__suffix__7-NUb\"> Rights</span><svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 32 32\" aria-hidden=\"true\" data-testid=\"NewTabSymbol\" class=\"link__new-tab-symbol__3T19s link__move-left__2zPIr collapsible-caption__symbol__eZoN7\" fill=\"#666\" width=\"0\" height=\"0\"></svg></span></a></span></div></figure><figure data-testid=\"image-0\" class=\"article-image__figure__3SBJc article-body__element__2p5pI article-body__img__1AZS2\"><div data-testid=\"Body\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__ultra_small__37j9j body__full_width__ekUdw body__ultra_small_body__1lUQl article-image__figcaption__ttQIt article-body__figcaption__17dH0\"><span><a data-testid=\"Link\" target=\"_blank\" href=\"https://www.reutersconnect.com/item/ghanas-wildcat-gold-mining-booms-poisoning-people-and-nature/dGFnOnJldXRlcnMuY29tLDIwMjQ6bmV3c21sX1JDMjFMOUFOQVlWSQ%3D%3D/?utm_medium=rcom-article-media&amp;utm_campaign=rcom-rcp-lead\" rel=\"noopener\" class=\"text__text__1FZLe text__inherit-color__3208F text__inherit-font__1Y8w3 text__inherit-size__1DZJi link__link__3Ji6W link__underline_default__2prE_ link__with-icon__3x3oD collapsible-caption__license__Nbbf3\"><span><span style=\"border: 0px; clip: rect(0px, 0px, 0px, 0px); clip-path: inset(50%); height: 1px; margin: -1px; overflow: hidden; padding: 0px; position: absolute; width: 1px; white-space: nowrap;\">, opens new tab</span></span></a></span></div></figure><div data-testid=\"paragraph-7\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">The Ghana National Association of Small-Scale Miners said the new measures will help the sector beat its 2024 gold output.</div><div data-testid=\"paragraph-8\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">\"We\'re\r\n looking at about 30% to 40% more production than the previous year,\" \r\nthe general secretary of the group, Godwin Armah, told Reuters.</div><br>', 'news/2025/06/news_684735e28580e_1749497314.avif', 1, 3, 'published', 1, 1, 1, 13, 0, 1, 0, 0, 2, 'range', '', NULL, '2025-06-10 12:04:00', '2025-06-12 10:05:00', '2025-06-09 19:14:43', '2025-07-09 18:22:03', NULL, NULL, NULL, 0),
(15, '4e66f045-bc56-4f27-9618-7cd3e7d839de', 'Ghana asks Afreximbank to discuss debt treatment', 'ghana-asks-afreximbank-to-discuss-debt-treatment', 'On one front: The nation\'s capital was transfixed by a seismic fight between Elon Musk and President Trump, centered on the cost of the $3 trillion tax and spending bill.', '<div data-testid=\"paragraph-0\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">ACCRA,\r\n May 29 (Reuters) - Ghana asked Afreximbank to sit down for debt \r\ntreatment talks with the gold producing nation and its advisors in a \r\nletter sent last week by the finance minister and seen on Thursday by \r\nReuters.</div><div data-testid=\"paragraph-1\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__small__1kGq2 body__full_width__ekUdw body__small_body__2vQyf article-body__paragraph__2-BtD\">\"The\r\n objective of these discussions is to identify debt treatment solutions \r\nthat are acceptable to Afreximbank, while ensuring that Ghana complies \r\nwith the debt sustainability parameters of the IMF programme and the \r\nComparability of Treatment principle as assessed by the (Official \r\nCreditor Committee),\" Finance Minister Cassiel Ato Forson wrote in the \r\nletter dated May 21.</div><div class=\"article-body__element__2p5pI\"><p data-testid=\"SignOff\" class=\"text__text__1FZLe text__dark-grey__3Ml43 text__regular__2N1Xr text__extra_small__1Mw6v body__full_width__ekUdw body__extra_small_body__3QTYe sign-off__text__PU3Aj\">Reporting by Emmanuel Bruce in Accra, writing by Rodrigo Campos, editing by Chris Reese</p></div><br>', 'news/2025/06/news_684736222559a_1749497378.avif', 1, 6, 'published', 1, 1, 1, 20, 0, 0, 0, 0, 1, '', '', NULL, '2025-06-09 05:07:00', '2025-06-11 12:05:00', '2025-06-09 19:14:55', '2025-06-10 21:15:53', NULL, NULL, NULL, 0),
(16, '63164614-afc1-4ae0-b966-e24d99086eff', 'Ghana central bank keeps key rate on hold as inflation eases', 'ghana-central-bank-keeps-key-rate-on-hold-as-inflation-eases', 'ACCRA, May 23 (Reuters) - The Bank of Ghana held its main interest rate (GHCBIR=ECI), opens new tab steady at 28.0% on Friday, maintaining its tight monetary policy as inflationary pressures continued to ease due to exchange rate stability and fiscal consolidation.', 'ACCRA, May 23 (Reuters) - The Bank of Ghana held its main interest rate (GHCBIR=ECI)<br>, opens new tab steady at 28.0% on Friday, maintaining its tight monetary policy as inflationary pressures continued to ease due to exchange rate stability and fiscal consolidation.<br>Most economists surveyed by Reuters had expected the central bank to leave the rate on hold after a surprise 100 basis points increase in its last monetary policy committee meeting in March.<br>Ghana\'s consumer price inflation slowed for a fourth month in a row in April, to 21.2% year on year from 22.4% in March. It remains well above the Bank of Ghana\'s target of 8% with a margin of error of 2 percentage points.<br>Governor Johnson Asiama said inflation was expected to ease faster towards the medium-term target in the first quarter of next year, as opposed to the second quarter as earlier envisaged, barring unanticipated shocks.<br>\"Despite these positive developments, the committee observed that the current level of inflation remains high relative to the medium-term target and will require maintaining the tight stance to reinforce the disinflation process,\" Asiama said.', 'news/2025/06/news_6847368b83729_1749497483.avif', 1, 4, 'published', 1, 1, 1, 100, 0, 0, 1, 0, 1, '', '', NULL, '2025-06-10 23:05:00', '2025-06-12 12:04:00', '2025-06-09 19:15:04', '2025-08-09 08:11:37', NULL, NULL, NULL, 0),
(17, 'd02fd788-6c5c-4a50-afc5-fc3721c9fcd6', 'Ghana consumer inflation slows for fourth month in April', 'ghana-consumer-inflation-slows-for-fourth-month-in-april', 'BIDJAN, May 13 (Reuters) - Ghana should be able to reduce its debt of $2.5 billion owed to independent power producers and gas suppliers by the end of the year, President John Dramani Mahama said on Tuesday.', '<h3 class=\"article-sub-heading\" style=\"font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 28px; font-weight: 600; margin-top: 32px; margin-bottom: 16px; line-height: 36px; position: relative; padding-top: 16px; padding-bottom: 0px; color: rgb(255, 255, 255); background-color: rgb(36, 36, 36);\">With up to 300 miles of range and Lexus\' first \"virtual manual gear shift system,\" the RZ gets a glow-up.</h3><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Until now, the Lexus RZ has been kind of like a golf cart: often found in fancy California gated communities, and seldom driven far, because it can\'t drive that far anyway. This didn\'t exactly endear it to critics or even many Lexus fans, especially as the luxury brand\'s competitors started packing serious heat in the electric space.</p><div class=\"intra-article-module\" data-t=\"{&quot;n&quot;:&quot;intraArticle&quot;,&quot;t&quot;:13}\" style=\"position: relative; z-index: 97; width: 768px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-intraArticleModule-0\"></slot></div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">But now, just like its platform-mate the&nbsp;<a href=\"https://insideevs.com/reviews/759938/2026-toyota-bz-first-look/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota bZ</a>, Lexus\' sole electric offering (<a href=\"https://insideevs.com/news/759755/lexus-es-ev-range-charging/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">for now</a>) just got a lot more interesting. Powerful, too. And able to go much further than before.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" class=\"continue-read-break\" style=\"opacity: 1; position: static; margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">The 2026 Lexus RZ gets a big raft of upgrades, including a redesigned battery and motor system, faster charging, and perhaps most interestingly of all, something called M Mode—a fancy Lexus name for simulated eight-speed gear shifts.&nbsp;<slot name=\"cont-read-break\"></slot></p><div class=\"article-image-slot image-slot-placeholder\" data-doc-id=\"cms/api/amp/image/AA1F680i\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><slot name=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\"><div slot=\"AA1F65Bd-image-cms/api/amp/image/AA1F680i\" class=\"article-image-slot\"><cp-article-image style=\"color: rgb(245, 245, 245);\"><div class=\"article-image-container polished\" data-t=\"{&quot;n&quot;:&quot;OpenModal&quot;,&quot;t&quot;:13,&quot;b&quot;:8,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" data-test-id=\"AA1F680i\" style=\"position: relative; margin: 0px 0px 8px; width: 768px; height: 100%;\"><button data-customhandled=\"true\" class=\"article-image-height-wrapper expandable article-image-height-wrapper-new\" data-t=\"{&quot;n&quot;:&quot;OpenModalButton&quot;,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.i&quot;:&quot;AA1F65Bd&quot;,&quot;c.l&quot;:false,&quot;c.t&quot;:13,&quot;c.v&quot;:&quot;news&quot;,&quot;c.c&quot;:&quot;others&quot;,&quot;c.b&quot;:&quot;InsideEVs Global&quot;,&quot;c.bi&quot;:&quot;AAvrs6s&quot;,&quot;c.tv&quot;:&quot;autos&quot;,&quot;c.tc&quot;:&quot;electric-cars&quot;,&quot;c.hl&quot;:&quot;The 2026 Lexus RZ Gets A Tesla Plug, 300 Miles Of Range, Simulated Shifts&quot;}\" style=\"width: inherit; display: block; appearance: none; border-width: initial; border-style: none; border-color: initial; background-image: initial; background-position: initial; background-size: initial; background-repeat: initial; background-attachment: initial; background-origin: initial; background-clip: initial; position: relative; overflow: hidden; border-radius: 6px; cursor: zoom-in; height: 382px;\"><div style=\"background: url(&quot;https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6&quot;) center center / cover no-repeat; filter: blur(90px); height: 382px;\"></div><img class=\"article-image article-image-ux-impr article-image-new expandable\" src=\"https://img-s-msn-com.akamaized.net/tenant/amp/entityid/AA1F680i.img?w=768&amp;h=432&amp;m=6\" alt=\"2026 Lexus RZ\" title=\"2026 Lexus RZ\" loading=\"eager\" style=\"border-radius: 6px; position: absolute; top: 0px; bottom: 0px; left: 384px; transform: translateX(-50%); object-fit: contain; background: rgb(242, 242, 242); transform-origin: 0px 0px; width: 100%;\"></button><div class=\"image-caption-container image-caption-container-ux-impr articlewc-image-caption-container\" style=\"display: flex; font-size: 12px; line-height: 16px; padding: 12px 16px 8px 24px; flex-flow: column; gap: normal; background: transparent !important;\"><span class=\"image-caption\" style=\"color: rgb(255, 255, 255);\">2026 Lexus RZ</span></div></div></cp-article-image></div></slot></div><div class=\"photo-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\"><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px;\">2026 Lexus RZ</p></div><div class=\"source-title\" style=\"color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">Photo by: Lexus</div><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">It\'s an&nbsp;<em>interesting</em>&nbsp;choice of car to debut a technology that&nbsp;<a href=\"https://insideevs.com/features/693877/toyota-ev-manual-transmission-tested/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">Toyota has been working on for years</a>. But on the&nbsp;550e F Sport AWD trim level, you also get&nbsp;402 horsepower to play with. That should be interesting indeed. Even better, it now comes with a Tesla-style North American Charging Standard (NACS) plug for easy fast-charging.&nbsp;</p><p data-t=\"{&quot;n&quot;:&quot;blueLinks&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:76}\" style=\"margin-bottom: 16px; color: rgb(255, 255, 255); font-family: &quot;Segoe UI&quot;, &quot;Segoe UI Midlevel&quot;, sans-serif; font-size: 17px; background-color: rgb(36, 36, 36);\">We first saw&nbsp;<a href=\"https://insideevs.com/news/753063/2025-lexus-rx-specs-features/?utm_source=msn.com&amp;utm_medium=referral&amp;utm_campaign=msn-feed\" target=\"_blank\" data-t=\"{&quot;n&quot;:&quot;destination&quot;,&quot;t&quot;:13,&quot;a&quot;:&quot;click&quot;,&quot;b&quot;:1,&quot;c.t&quot;:7}\" style=\"text-decoration: none; color: rgb(48, 145, 220); overflow-wrap: break-word; outline-offset: -3px;\">the updated RZ at Toyota\'s Kenshiki Forum event in Brussels</a>&nbsp;this spring, but now its U.S.-market specs have landed as well. Here, it will come in three trim levels: the RZ 350e, the RZ 450e AWD and the aforementioned RZ&nbsp;550e F Sport AWD.&nbsp;</p>', 'news/2025/06/news_6847370448d03_1749497604.avif', 1, 3, 'published', 0, 0, 1, 10, 1, 0, 0, 0, 2, 'range', '', NULL, '2025-06-10 12:04:00', '2025-06-11 12:04:00', '2025-06-09 19:15:15', '2025-06-11 02:05:10', NULL, NULL, NULL, 0),
(18, 'b266c199-304e-4414-880a-78cacf71415e', 'Ghana secures deal with nine more gold miners to buy 20% of their output', 'ghana-secures-deal-with-nine-more-gold-miners-to-buy-20percent-of-their-output', 'ACCRA, April 30 (Reuters) - Ghana has reached a deal with nine more mining companies to purchase 20% of their gold production, a government body said on Wednesday, aiming to consolidate a gold purchase programme meant to boost the country\'s gold reserves', 'ACCRA, April 30 (Reuters) - Ghana has reached a deal with nine more mining companies to purchase 20% of their gold production, a government body said on Wednesday, aiming to consolidate a gold purchase programme meant to boost the country\'s gold reserves and stabilise its currency.<br>Africa\'s top gold producer signed an agreement with members of an industry group that included Gold Fields (GFIJ.J)<br>opens new tab, Newmont (NEM.N), opens new tab, AngloGold Ashanti (AU.N)<br><br>opens new tab, and Asanko Mining in 2022 to purchase 20% of their annual output for the central Bank of Ghana. Purchases are settled in the Ghanaian cedi currency.<br><br>Bank of Ghana\'s gold holdings rose to 30.8 metric tons in February from 8.77 tons in 2022, helping its gross reserves to hit $9.4 billion this year.<br>The new deal covers mining companies not participating in the central bank\'s arrangement, according to a statement on X from GoldBod, a government body set up to streamline gold purchases from small-scale miners, increase their earnings, and reduce the impact of smuggling.<br>The companies are Golden Team Mining Company Limited, Akroma Gold Limited, Adamus Resources Limited, Cardinal Namdini Mining Limited, Goldstone Akrokeri Limited, Earl International Group (GH) Limited, Xtra Gold Mining Limited, Prestea Sankofa Gold Limited and Gan He Mining Resource Development Limited.<br>Gold mining countries have sought increased value from the precious metal as prices rose 29% this year, boosted by U.S. President Donald Trump\'s tariffs and geopolitical uncertainty.<br>\"Under the agreement, the mining companies will deliver 20% of any gold they seek to export out of the country to the GoldBod in the form of doré bars,\" the GoldBod statement said.<br>\"This agreement represents a significant step toward optimising national benefits from Ghana\'s gold resources.\"<br>The mining companies will receive payment in Ghanaian cedis, discounted at one percent of the London Bullion Market Association (LBMA) spot price.<br>The nine gold miners produce approximately 200 kilograms of gold monthly, GoldBod\'s spokesperson told Reuters.<br><br>Reporting by Christian Akorlie; Writing by Maxwell Akalaare Adombila; Editing by Ayen Deng Bior and Ros Russell<br><br><br>', 'news/2025/06/news_6847377ac91a5_1749497722.avif', 1, 3, 'published', 0, 1, 1, 8, 1, 0, 0, 0, 2, 'range', '', NULL, '2025-06-10 12:04:00', '2025-06-11 23:05:00', '2025-06-09 19:15:30', '2025-06-11 20:29:53', NULL, NULL, NULL, 0);
INSERT INTO `news` (`id`, `uuid`, `title`, `slug`, `summary`, `content`, `featured_image`, `author_id`, `category_id`, `status`, `is_featured`, `is_breaking`, `is_fact_checked`, `view_count`, `like_count`, `bookmark_count`, `comment_count`, `share_count`, `reading_time`, `meta_title`, `meta_description`, `meta_keywords`, `published_at`, `scheduled_at`, `created_at`, `updated_at`, `approved_by`, `approved_at`, `deleted_at`, `is_flagged`) VALUES
(19, 'f708d3e9-96e5-4287-bc1f-49ccae0e68bc', 'Gold bars for top three finishers in Cup Xfinity races in Mexico (Copy)', 'gold-bars-for-top-three-finishers-in-cup-xfinity-races-in-mexico-copy', 'In between, the Cup Series will hold a one-hour practice at 9:30 a.m., followed by qualifying for Sunday\'s 200-lap race. Tyler Reddick is the defending winner at Michigan.', '<p class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><span style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ;\">The only time the NASCAR Cup Series has had a podium celebration was for the Clash at the LA Memorial Coliseum exhibition races from 2022-24.</span></p><ul class=\"content-list\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 0px; padding-left: 1.538em; list-style-type: none; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-size: 13px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><li style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin-bottom: 0px; font-size: inherit; line-height: inherit; list-style-type: disc;\"><div class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; --ys-page-column-spacing: 0;\"><p class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 0px 0px 0.8em; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; vertical-align: top;\"><a class=\"link \" href=\"https://www.nbcsports.com/author/dustin-long\" rel=\"nofollow noopener\" target=\"_blank\" data-ylk=\"slk:Dustin Long;elm:context_link;itc:0;sec:content-canvas\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; background-color: transparent; color: rgb(0, 99, 235); text-decoration: none;\">Dustin Long</a><span class=\"separator\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ;\">,</span></p></div></li></ul><ul class=\"content-list\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 0px; padding-left: 1.538em; list-style-type: none; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-size: 13px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><li style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin-bottom: 0px; font-size: inherit; line-height: inherit; list-style-type: disc;\"><div class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; --ys-page-column-spacing: 0;\"><p class=\"\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 0px 0px 0.8em; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; vertical-align: top;\"><a class=\"link \" href=\"https://www.nbcsports.com/author/dustin-long\" rel=\"nofollow noopener\" target=\"_blank\" data-ylk=\"slk:Dustin Long;elm:context_link;itc:0;sec:content-canvas\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; background-color: transparent; color: rgb(0, 99, 235); text-decoration: none;\">Dustin Long</a><span class=\"separator\" style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ;\">,</span></p></div></li></ul><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">In between, the Cup Series will hold a one-hour practice at 9:30 a.m., followed by qualifying for Sunday\'s 200-lap race. Tyler Reddick is the defending winner at Michigan.</p><p style=\"--tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: #3b82f680; --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; margin: 1.33em 0px; font-size: 1.385em; letter-spacing: normal; line-height: 1.67; grid-column: body-start / body-end; color: rgb(35, 42, 49); font-family: &quot;YahooSans VF&quot;, &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">The Truck Series will race at Michigan for the 22nd time but the first since Aug. 7, 2020, making the Brooklyn, Michigan, track one of six new tracks in 2025 (and one of four that are returning to the schedule). Zane Smith was the most recent Truck winner at Michigan.</p>', 'news/2025/06/news_684737c641c48_1749497798.avif', 1, 4, 'published', 1, 0, 1, 13, 1, 0, 0, 0, 1, '', '', NULL, '2025-06-10 14:05:00', '2025-06-11 12:04:00', '2025-06-09 19:15:44', '2025-07-15 12:53:43', NULL, NULL, NULL, 0),
(20, '8ea1e494-6f43-42d5-9b4d-3d0fc49b6d2b', 'Ghana government takes control of Gold Fields\' Damang mine, lands ministry says', 'ghana-government-takes-control-of-gold-fields-damang-mine-lands-ministry-says', 'Forecasters predict some areas could also see hail and strong gusty winds, with the potential for flooding, power cuts and travel disruption.', '<p>A yellow warning for thunderstorms has been issued for large parts of England and Wales today.</p><p>The Met Office warning covers most of southern England, parts of the Midlands and most of South Wales between 9am until 6pm.</p><div class=\"sdc-site-outbrain sdc-site-outbrain--AR_6\" data-component-name=\"ui-vendor-outbrain\" data-target=\"\" data-widget-mapping=\"\" data-installation-keys=\"\" data-testid=\"vendor-outbrain\">\r\n    \r\n</div>\r\n<p>People in the affected areas are being warned heavy showers and thunderstorms may lead to some disruption to transport services.</p><p><strong><a href=\"https://news.sky.com/weather\" target=\"_blank\">Find out the forecast for your area</a></strong></p><p>The <a href=\"https://news.sky.com/topic/uk-weather-9424\" target=\"_blank\"><strong>UK\'s weather</strong></a> agency has also warned of frequent lightning, hail and strong gusty winds.</p>\r\n<div class=\"sdc-article-widget sdc-article-image\">\r\n  <figure class=\"sdc-article-image__figure\">\r\n    <div class=\"sdc-article-image__wrapper\" data-aspect-ratio=\"16/9\">\r\n          <img class=\"sdc-article-image__item\" src=\"https://e3.365dm.com/25/06/768x432/skynews-thunderstorm-map_6936211.png?20250607020127\" alt=\"Map showing area of yellow thunderstorm warning in England and Wales\" style=\"width: 100%;\">\r\n    </div>\r\n      <figcaption class=\"ui-media-caption\">\r\n        <span class=\"u-hide-visually\">Image:</span>\r\n        <span class=\"ui-media-caption__caption-text\">A yellow warning covers most of England, as far north as Wolverhampton, and a large part of South Wales. Pic: Met Office\r\n        </span>\r\n      </figcaption>\r\n  </figure>\r\n</div>\r\n<p>Delays to train services are possible and some short-term losses of power are also likely.</p><p>Met Office meteorologist Alex Burkill said Saturday morning will start with \"plenty of showery rain around\".</p>', 'news/2025/06/news_68473800f133f_1749497856.avif', 1, 1, 'published', 0, 0, 1, 147, 1, 0, 2, 0, 1, '', '', NULL, '2025-06-10 23:06:00', '2025-06-11 12:56:00', '2025-06-09 19:15:58', '2025-07-09 18:54:37', NULL, NULL, NULL, 0);

--
-- Triggers `news`
--
DELIMITER $$
CREATE TRIGGER `news_cache_invalidate` AFTER UPDATE ON `news` FOR EACH ROW BEGIN
    DELETE FROM cache_entries WHERE id LIKE CONCAT('news_%', NEW.id, '%') OR id LIKE 'news_list_%';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_providers`
--

CREATE TABLE `oauth_providers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `provider` enum('google','facebook','twitter','github') NOT NULL,
  `provider_id` varchar(255) NOT NULL,
  `provider_email` varchar(255) DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_states`
--

CREATE TABLE `oauth_states` (
  `id` int(10) UNSIGNED NOT NULL,
  `state` varchar(255) NOT NULL,
  `redirect_uri` varchar(500) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `content_flags` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(10) UNSIGNED NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `requests_count` int(10) UNSIGNED DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'News & Event Hub', 'string', 'Website name', 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34'),
(2, 'site_description', 'Your trusted source for news and events', 'string', 'Website description', 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34'),
(3, 'site_keywords', 'news, events, community, local', 'string', 'SEO keywords', 0, '2025-05-29 17:30:34', '2025-05-29 17:30:34'),
(4, 'posts_per_page', '10', 'integer', 'Number of posts per page', 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34'),
(5, 'allow_comments', '1', 'boolean', 'Allow comments on content', 1, '2025-05-29 17:30:34', '2025-05-29 17:30:34'),
(6, 'moderate_comments', '1', 'boolean', 'Moderate comments before publishing', 0, '2025-05-29 17:30:34', '2025-05-29 17:30:34'),
(7, 'email_notifications', '1', 'boolean', 'Send email notifications', 0, '2025-05-29 17:30:34', '2025-05-30 19:59:16'),
(8, 'maintenance_mode', '0', 'boolean', 'Enable maintenance mode', 0, '2025-05-29 17:30:34', '2025-05-29 17:30:34'),
(9, 'google_analytics_id', '', 'string', 'Google Analytics tracking ID', 0, '2025-05-29 17:30:34', '2025-05-29 17:30:34'),
(10, 'smtp_host', 'smtp.gmail.com', 'string', 'SMTP server host', 0, '2025-05-29 17:30:34', '2025-05-30 19:59:16'),
(11, 'smtp_port', '587', 'integer', 'SMTP server port', 0, '2025-05-29 17:30:34', '2025-05-30 19:59:16'),
(12, 'smtp_username', 'admin@example.com', 'string', 'SMTP username', 0, '2025-05-29 17:30:34', '2025-05-30 19:59:16'),
(13, 'smtp_password', 'password', 'string', 'SMTP password', 0, '2025-05-29 17:30:34', '2025-05-30 19:59:16'),
(14, 'api_rate_limit_per_minute', '60', 'integer', 'API requests per minute per IP', 0, '2025-05-29 17:30:34', '2025-05-30 20:00:12'),
(15, 'api_rate_limit_per_hour', '3600', 'integer', 'API requests per hour per IP', 0, '2025-05-29 17:30:34', '2025-05-30 20:00:12'),
(16, 'jwt_secret', '', 'string', 'JWT signing secret', 0, '2025-05-29 17:30:34', '2025-05-29 17:30:34'),
(17, 'jwt_access_expire', '3600', 'integer', 'JWT access token expiration (seconds)', 0, '2025-05-29 17:30:34', '2025-05-30 20:00:12'),
(18, 'jwt_refresh_expire', '604800', 'integer', 'JWT refresh token expiration (seconds)', 0, '2025-05-29 17:30:34', '2025-05-30 20:00:12'),
(22, 'mail_from_address', 'noreply@example.com', 'string', NULL, 0, '2025-05-30 19:56:55', '2025-05-30 19:59:16'),
(23, 'mail_from_name', 'NorthCity News', 'string', NULL, 0, '2025-05-30 19:56:55', '2025-05-30 19:59:16'),
(58, 'news_comment_moderation_level', '1', 'integer', 'Moderation level for news comments (0=none, 1=basic, 2=strict, 3=hold all)', 0, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(59, 'event_comment_moderation_level', '1', 'integer', 'Moderation level for event comments (0=none, 1=basic, 2=strict, 3=hold all)', 0, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(60, 'auto_approve_trusted_users', '1', 'boolean', 'Auto-approve comments from trusted users', 0, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(61, 'auto_moderation_enabled', '1', 'boolean', 'Enable automatic moderation', 0, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(62, 'moderation_queue_limit', '100', 'integer', 'Maximum items in moderation queue before auto-processing', 0, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(63, 'comment_edit_time_limit', '300', 'integer', 'Time limit for editing comments (seconds)', 0, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(64, 'max_comment_edits', '3', 'integer', 'Maximum number of edits allowed per comment', 0, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(65, 'comment_voting_enabled', '1', 'boolean', 'Enable comment voting system', 1, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(66, 'nested_comments_depth', '5', 'integer', 'Maximum depth for nested comments', 1, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(67, 'comment_length_min', '3', 'integer', 'Minimum comment length', 1, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(68, 'comment_length_max', '2000', 'integer', 'Maximum comment length', 1, '2025-06-04 16:10:14', '2025-06-04 16:10:14'),
(146, 'reputation_decay_days', '90', 'integer', 'Days after which reputation scores decay', 0, '2025-06-04 19:19:16', '2025-06-04 19:19:16'),
(147, 'auto_moderation_batch_size', '50', 'integer', 'Number of comments to process in each auto-moderation batch', 0, '2025-06-04 19:19:16', '2025-06-04 19:19:16');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `subscription_type` enum('all_news','category_news','events','breaking_news') NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('active','paused','unsubscribed') DEFAULT 'active',
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `verification_token` varchar(255) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL DEFAULT uuid(),
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `usage_count` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `uuid`, `name`, `slug`, `usage_count`, `created_at`, `deleted_at`, `updated_at`) VALUES
(109, '34e203eb-b2eb-4fdd-aed5-61db91c62e8b', 'games', 'games-1', 1, '2025-06-06 13:14:33', NULL, '2025-06-06 13:15:50'),
(110, 'd4be2efe-f4f8-4272-b8fe-bc8c49b59b74', 'gaming', 'gaming-1', 1, '2025-06-06 13:14:33', NULL, '2025-06-06 13:15:50'),
(111, 'ce3ef971-4cbd-4dea-a3eb-491b7052dd90', 'action', 'action', 1, '2025-06-06 13:20:15', NULL, '2025-06-06 13:20:15'),
(112, 'f028fba4-6006-4670-bf84-7a07a1d4ecfd', 'chaple', 'chaple', 1, '2025-06-06 13:20:15', NULL, '2025-06-06 13:20:15'),
(113, '28e26bda-4d49-407b-b273-a53718ea5dc6', 'hoping', 'hoping', 1, '2025-06-06 13:20:59', NULL, '2025-06-06 13:20:59'),
(114, '755aea8c-f1ef-44de-ab40-e77bbc7b9a12', 'loose', 'loose', 1, '2025-06-06 13:20:59', NULL, '2025-06-06 13:20:59');

-- --------------------------------------------------------

--
-- Table structure for table `trusted_domains`
--

CREATE TABLE `trusted_domains` (
  `id` int(10) UNSIGNED NOT NULL,
  `domain` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL DEFAULT uuid(),
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('public','contributor','super_admin') DEFAULT 'public',
  `status` enum('pending','active','suspended','banned') DEFAULT 'pending',
  `remember_me` tinyint(1) DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `social_twitter` varchar(100) DEFAULT NULL,
  `social_facebook` varchar(100) DEFAULT NULL,
  `social_linkedin` varchar(100) DEFAULT NULL,
  `is_verified_contributor` tinyint(1) DEFAULT 0,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`backup_codes`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `uuid`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `status`, `remember_me`, `email_verified`, `profile_image`, `bio`, `phone`, `location`, `website`, `social_twitter`, `social_facebook`, `social_linkedin`, `is_verified_contributor`, `preferences`, `two_factor_enabled`, `two_factor_secret`, `backup_codes`, `created_at`, `updated_at`, `last_login_at`, `deleted_at`) VALUES
(1, 'a07d5e7a-3cb2-11f0-8122-7e9c40ae708f', 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'super_admin', 'active', 0, 1, NULL, 'Something gooding', '+233509212708', NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-29 17:30:34', '2025-09-05 15:19:41', '2025-09-05 15:19:41', NULL),
(2, 'e1e44ea2-3d32-11f0-8122-7e9c40ae708f', 'super', 'super@example.com', '$2y$10$94nPh03j/Zug1GElucBRWOS7oZxL.OAkfRgyNaRwY.pNlo6GUfI8q', 'Super', 'Admin', 'super_admin', 'active', 0, 0, NULL, 'Something good', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-30 08:48:39', '2025-05-31 02:29:56', '2025-05-31 02:29:56', NULL),
(3, '0da707c6-4055-11f0-8122-7e9c40ae708f', 'contributor', 'contributor@example.com', '$2y$10$vfDBzb8faBPej0yzHFxaxO3mLYa.oVjtXJQGimJFY5WUU80/nyQhC', 'contributor', 'Admin', 'contributor', 'active', 0, 0, NULL, 'Something gooding', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-06-03 08:30:49', '2025-06-03 08:32:35', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_moderation_scores`
--

CREATE TABLE `user_moderation_scores` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `total_comments` int(10) UNSIGNED DEFAULT 0,
  `approved_comments` int(10) UNSIGNED DEFAULT 0,
  `rejected_comments` int(10) UNSIGNED DEFAULT 0,
  `flagged_comments` int(10) UNSIGNED DEFAULT 0,
  `spam_comments` int(10) UNSIGNED DEFAULT 0,
  `reputation_score` decimal(5,2) DEFAULT 100.00,
  `trust_level` enum('untrusted','new','trusted','verified') DEFAULT 'new',
  `last_calculated` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_type` enum('access','refresh') DEFAULT 'access',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `payload` text NOT NULL,
  `last_activity` int(10) UNSIGNED NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `token_type`, `ip_address`, `user_agent`, `payload`, `last_activity`, `expires_at`, `created_at`) VALUES
('c6bdf5deb9937abdbfb050568f445e75e015ee24e43022091826e31d954354cb', 1, 'refresh', '127.0.0.1', 'Thunder Client (https://www.thunderclient.com)', '{\"token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L3BoZW1lIiwiYXVkIjoiaHR0cDovL2xvY2FsaG9zdC9waGVtZSIsImlhdCI6MTc1NzA4NTU4MSwiZXhwIjoxNzU3NjkwMzgxLCJ1c2VyX2lkIjoxLCJ0eXBlIjoicmVmcmVzaCJ9.KzXh72Cbua8IW9Jm1WmWpjP2RY4_2EDF6ZUotjzpE3c\"}', 1757085581, '2025-09-12 15:19:41', '2025-09-05 15:19:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_activity_logs_action_created` (`action`,`created_at`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_hash` (`key_hash`),
  ADD KEY `idx_key_hash` (`key_hash`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `api_logs`
--
ALTER TABLE `api_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `api_key_id` (`api_key_id`),
  ADD KEY `idx_request_id` (`request_id`),
  ADD KEY `idx_endpoint` (`endpoint`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_response_code` (`response_code`);

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_content_bookmark` (`user_id`,`content_type`,`content_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_content` (`content_type`,`content_id`);

--
-- Indexes for table `cache_entries`
--
ALTER TABLE `cache_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_moderation_score` (`moderation_score`),
  ADD KEY `idx_auto_moderated` (`auto_moderated`),
  ADD KEY `idx_requires_review` (`requires_review`),
  ADD KEY `idx_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_reviewed_at` (`reviewed_at`),
  ADD KEY `idx_moderation_queue` (`moderation_queue_id`);

--
-- Indexes for table `comment_flags`
--
ALTER TABLE `comment_flags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_comment_flag` (`comment_id`,`user_id`,`flag_type`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_status_pending` (`status`,`created_at`),
  ADD KEY `idx_comment_flags` (`comment_id`,`status`);

--
-- Indexes for table `comment_votes`
--
ALTER TABLE `comment_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_comment_vote` (`comment_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_comment_id` (`comment_id`);

--
-- Indexes for table `content_flags`
--
ALTER TABLE `content_flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_reporter_id` (`reporter_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_content_flags_status_created` (`status`,`created_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `content_likes`
--
ALTER TABLE `content_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_content_like` (`content_type`,`content_id`,`user_id`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `content_media`
--
ALTER TABLE `content_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_media_id` (`media_id`);

--
-- Indexes for table `content_tags`
--
ALTER TABLE `content_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_content_tag` (`content_type`,`content_id`,`tag_id`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_tag_id` (`tag_id`);

--
-- Indexes for table `content_views`
--
ALTER TABLE `content_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_view_date` (`view_date`),
  ADD KEY `idx_ip_date` (`ip_address`,`view_date`);

--
-- Indexes for table `cron_job_logs`
--
ALTER TABLE `cron_job_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_status` (`task_name`,`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled_at` (`scheduled_at`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_organizer_id` (`organizer_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_location` (`venue_city`,`venue_state`,`venue_country`),
  ADD KEY `idx_coordinates` (`latitude`,`longitude`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_deleted_at` (`deleted_at`);
ALTER TABLE `events` ADD FULLTEXT KEY `idx_search` (`title`,`description`,`content`);

--
-- Indexes for table `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_user` (`event_id`,`user_id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `file_cleanup`
--
ALTER TABLE `file_cleanup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scheduled_deletion` (`scheduled_deletion`),
  ADD KEY `idx_reference` (`reference_table`,`reference_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_uploader_id` (`uploader_id`),
  ADD KEY `idx_file_type` (`file_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_approved` (`is_approved`),
  ADD KEY `idx_rejected` (`is_rejected`),
  ADD KEY `idx_flagged` (`is_flagged`),
  ADD KEY `idx_moderated_by` (`moderated_by`);

--
-- Indexes for table `media_approval_workflow`
--
ALTER TABLE `media_approval_workflow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_id` (`media_id`),
  ADD KEY `idx_workflow_stage` (`workflow_stage`),
  ADD KEY `idx_stage_status` (`stage_status`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `media_flags`
--
ALTER TABLE `media_flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_media_id` (`media_id`),
  ADD KEY `idx_reporter_id` (`reporter_id`),
  ADD KEY `idx_flag_type` (`flag_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `media_metadata`
--
ALTER TABLE `media_metadata`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_media_key` (`media_id`,`metadata_key`),
  ADD KEY `idx_metadata_key` (`metadata_key`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `media_moderation_log`
--
ALTER TABLE `media_moderation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_id` (`media_id`),
  ADD KEY `idx_moderator_id` (`moderator_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `media_reports`
--
ALTER TABLE `media_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_media_id` (`media_id`),
  ADD KEY `idx_reporter_id` (`reporter_id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `moderation_logs`
--
ALTER TABLE `moderation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comment_moderation` (`comment_id`,`created_at`),
  ADD KEY `idx_moderator_activity` (`moderator_id`,`created_at`);

--
-- Indexes for table `moderation_queue`
--
ALTER TABLE `moderation_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_moderation_queue_status_priority` (`status`,`priority`);

--
-- Indexes for table `moderation_words`
--
ALTER TABLE `moderation_words`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_type_active` (`type`,`is_active`),
  ADD KEY `idx_word_lookup` (`word`,`is_active`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_author_id` (`author_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_published_at` (`published_at`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_breaking` (`is_breaking`),
  ADD KEY `idx_deleted_at` (`deleted_at`);
ALTER TABLE `news` ADD FULLTEXT KEY `idx_search` (`title`,`summary`,`content`);

--
-- Indexes for table `oauth_providers`
--
ALTER TABLE `oauth_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_provider_user` (`provider`,`provider_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_provider` (`provider`);

--
-- Indexes for table `oauth_states`
--
ALTER TABLE `oauth_states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `state` (`state`),
  ADD KEY `idx_state` (`state`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_identifier_endpoint` (`identifier`,`endpoint`,`window_start`),
  ADD KEY `idx_identifier` (`identifier`),
  ADD KEY `idx_window_start` (`window_start`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`),
  ADD KEY `idx_public` (`is_public`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type_category` (`subscription_type`,`category_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_usage_count` (`usage_count`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `trusted_domains`
--
ALTER TABLE `trusted_domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domain` (`domain`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_domain_active` (`domain`,`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `user_moderation_scores`
--
ALTER TABLE `user_moderation_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_trust_level` (`trust_level`,`reputation_score`),
  ADD KEY `idx_reputation_lookup` (`user_id`,`reputation_score`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_token_type` (`token_type`),
  ADD KEY `idx_last_activity` (`last_activity`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `comment_flags`
--
ALTER TABLE `comment_flags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment_votes`
--
ALTER TABLE `comment_votes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `content_flags`
--
ALTER TABLE `content_flags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_likes`
--
ALTER TABLE `content_likes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `content_media`
--
ALTER TABLE `content_media`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_tags`
--
ALTER TABLE `content_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `content_views`
--
ALTER TABLE `content_views`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1291;

--
-- AUTO_INCREMENT for table `cron_job_logs`
--
ALTER TABLE `cron_job_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `event_attendees`
--
ALTER TABLE `event_attendees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `file_cleanup`
--
ALTER TABLE `file_cleanup`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `media_approval_workflow`
--
ALTER TABLE `media_approval_workflow`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_flags`
--
ALTER TABLE `media_flags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_metadata`
--
ALTER TABLE `media_metadata`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `media_moderation_log`
--
ALTER TABLE `media_moderation_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `media_reports`
--
ALTER TABLE `media_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `moderation_logs`
--
ALTER TABLE `moderation_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `moderation_queue`
--
ALTER TABLE `moderation_queue`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `moderation_words`
--
ALTER TABLE `moderation_words`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `oauth_providers`
--
ALTER TABLE `oauth_providers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `oauth_states`
--
ALTER TABLE `oauth_states`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `trusted_domains`
--
ALTER TABLE `trusted_domains`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_moderation_scores`
--
ALTER TABLE `user_moderation_scores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `api_logs`
--
ALTER TABLE `api_logs`
  ADD CONSTRAINT `api_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `api_logs_ibfk_2` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_comments_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comment_flags`
--
ALTER TABLE `comment_flags`
  ADD CONSTRAINT `comment_flags_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_flags_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `comment_flags_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comment_votes`
--
ALTER TABLE `comment_votes`
  ADD CONSTRAINT `comment_votes_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_flags`
--
ALTER TABLE `content_flags`
  ADD CONSTRAINT `content_flags_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `content_flags_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `content_likes`
--
ALTER TABLE `content_likes`
  ADD CONSTRAINT `content_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_media`
--
ALTER TABLE `content_media`
  ADD CONSTRAINT `content_media_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_tags`
--
ALTER TABLE `content_tags`
  ADD CONSTRAINT `content_tags_ibfk_1` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_views`
--
ALTER TABLE `content_views`
  ADD CONSTRAINT `content_views_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD CONSTRAINT `event_attendees_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `fk_media_moderator` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media_approval_workflow`
--
ALTER TABLE `media_approval_workflow`
  ADD CONSTRAINT `media_approval_workflow_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_approval_workflow_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `media_flags`
--
ALTER TABLE `media_flags`
  ADD CONSTRAINT `media_flags_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_flags_ibfk_2` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `media_flags_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `media_metadata`
--
ALTER TABLE `media_metadata`
  ADD CONSTRAINT `media_metadata_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media_moderation_log`
--
ALTER TABLE `media_moderation_log`
  ADD CONSTRAINT `media_moderation_log_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_moderation_log_ibfk_2` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media_reports`
--
ALTER TABLE `media_reports`
  ADD CONSTRAINT `media_reports_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_reports_ibfk_2` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `media_reports_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `media_reports_ibfk_4` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `moderation_logs`
--
ALTER TABLE `moderation_logs`
  ADD CONSTRAINT `moderation_logs_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `moderation_logs_ibfk_2` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `moderation_queue`
--
ALTER TABLE `moderation_queue`
  ADD CONSTRAINT `moderation_queue_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `moderation_queue_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `moderation_words`
--
ALTER TABLE `moderation_words`
  ADD CONSTRAINT `moderation_words_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `news_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `news_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `oauth_providers`
--
ALTER TABLE `oauth_providers`
  ADD CONSTRAINT `oauth_providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trusted_domains`
--
ALTER TABLE `trusted_domains`
  ADD CONSTRAINT `trusted_domains_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_moderation_scores`
--
ALTER TABLE `user_moderation_scores`
  ADD CONSTRAINT `user_moderation_scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
