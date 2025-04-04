-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2025 at 02:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `flexifit_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `notification_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `content_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content_type` enum('guide','tip','announcement','workout_plan','other') NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content`
--

INSERT INTO `content` (`content_id`, `admin_id`, `title`, `description`, `content_type`, `file_path`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 'Tipt #1', 'Tip#1', 'tip', 'uploads/1742744032_legpress.jpg', NULL, '2025-03-27 19:58:44', '2025-03-27 22:12:58'),
(3, 1, 'neo', 'test', 'guide', '', 'uploads/dumbell.jpg', '2025-03-28 01:11:25', '2025-03-28 01:11:25'),
(4, 1, 'asdada', 'adsada', 'guide', '', '../admin/uploads/67e5f909eee83_cirrus.jpg', '2025-03-28 01:19:05', '2025-03-28 01:19:05'),
(5, 1, 'test', 'test', 'guide', '', 'uploads/gym-bg.jpg', '2025-03-28 03:15:35', '2025-03-28 03:15:35');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `name`, `description`, `image`) VALUES
(1, 'TreadMill', 'A Treadmill for you to run on!', 'offers1 (1).jpg'),
(2, 'Dumb Bell', 'Dumbbell', 'dumbell.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_inventory`
--

CREATE TABLE `equipment_inventory` (
  `inventory_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `identifier` varchar(50) NOT NULL,
  `status` enum('available','in_use','maintenance') DEFAULT 'available',
  `active_status` enum('active','disabled') DEFAULT 'active',
  `added_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_maintenance_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_inventory`
--

INSERT INTO `equipment_inventory` (`inventory_id`, `equipment_id`, `identifier`, `status`, `active_status`, `added_date`, `last_maintenance_date`) VALUES
(1, 1, 'TreadMill-01', 'in_use', 'active', '2025-03-27 17:55:26', NULL),
(2, 1, 'TreadMill-02', 'in_use', 'active', '2025-03-27 17:55:26', NULL),
(3, 1, 'TreadMill-03', 'in_use', 'active', '2025-03-27 17:55:26', NULL),
(4, 2, 'Dumbell-01', 'available', 'active', '2025-03-28 02:43:44', NULL),
(5, 2, 'Dumbell-02', 'available', 'active', '2025-03-28 02:43:44', NULL),
(6, 2, 'Dumbell-03', 'in_use', 'active', '2025-03-28 02:43:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `equipment_usage`
--

CREATE TABLE `equipment_usage` (
  `usage_id` int(11) NOT NULL,
  `inventory_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('in_use','completed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `feedback_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `membership_status` enum('active','expired','pending') DEFAULT 'pending',
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `renewed_date` date DEFAULT NULL,
  `free_training_session` int(11) DEFAULT 0,
  `workout_plans` varchar(255) DEFAULT 'none',
  `health_status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `user_id`, `membership_status`, `plan_id`, `start_date`, `end_date`, `renewed_date`, `free_training_session`, `workout_plans`, `health_status`) VALUES
(1, 2, 'active', 1, '2025-03-28', '2025-04-04', NULL, 0, 'none', NULL),
(2, 5, 'pending', 2, '2025-03-29', '2025-04-01', NULL, 1, 'none', NULL),
(3, 6, 'active', 1, '2025-03-28', '2025-04-04', NULL, 2, 'none', NULL),
(4, 9, 'pending', 3, '2025-03-29', '2025-04-28', NULL, 4, 'none', NULL),
(5, 11, 'active', 3, '2025-04-03', '2025-05-03', NULL, 4, 'none', NULL),
(6, 10, 'pending', 4, '2025-04-03', '2025-05-23', NULL, 10, 'none', NULL),
(7, 12, 'active', 2, '2025-04-03', '2025-04-06', NULL, 1, 'none', NULL),
(8, 13, 'pending', 2, '2025-04-03', '2025-04-06', NULL, 1, 'none', NULL);

--
-- Triggers `members`
--
DELIMITER $$
CREATE TRIGGER `set_end_date_before_insert` BEFORE INSERT ON `members` FOR EACH ROW BEGIN
    DECLARE plan_days INT;
    SELECT duration_days INTO plan_days FROM membership_plans WHERE plan_id = NEW.plan_id;
    SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL plan_days DAY);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_dates_on_renewal` BEFORE UPDATE ON `members` FOR EACH ROW BEGIN
    IF NEW.renewed_date IS NOT NULL THEN
        SET NEW.start_date = NEW.renewed_date;
        SET NEW.end_date = (SELECT DATE_ADD(NEW.renewed_date, INTERVAL duration_days DAY) FROM membership_plans WHERE plan_id = NEW.plan_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `membership_payments`
--

CREATE TABLE `membership_payments` (
  `payment_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gmail` varchar(255) NOT NULL,
  `payment_mode` enum('cash','credit_card','gcash','bank_transfer') NOT NULL,
  `gcash_reference_number` varchar(255) DEFAULT NULL,
  `gcash_phone_number` varchar(20) DEFAULT NULL,
  `card_type` enum('Visa','Mastercard','Amex','Other') DEFAULT NULL,
  `card_id_number` varchar(20) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `proof_of_payment` varchar(255) DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_payments`
--

INSERT INTO `membership_payments` (`payment_id`, `member_id`, `plan_id`, `amount`, `gmail`, `payment_mode`, `gcash_reference_number`, `gcash_phone_number`, `card_type`, `card_id_number`, `payment_status`, `payment_date`, `proof_of_payment`, `email_sent`) VALUES
(1, 1, 1, 770.00, 'princenatsu07@gmail.com', 'gcash', '1234567890', '09611676764', '', '', '', '2025-03-27 20:16:42', NULL, 0),
(2, 2, 2, 330.00, 'johnbagon4@gmail.com', 'cash', '', '', '', '', 'pending', '2025-03-28 00:02:25', '67e5e710c272a_Magnetic Rowing.jpg', 0),
(3, 3, 1, 770.00, 'johnbagon4@gmail.com', 'gcash', '0987654321', '', '', '', '', '2025-03-28 02:49:06', '67e60e21ec450_1742211401_right-image.jpg', 0),
(4, 4, 3, 2950.00, 'johnbagon4@gmail.com', 'cash', '', '', '', '', 'pending', '2025-03-28 03:33:26', NULL, 0),
(5, 5, 3, 2950.00, 'mari@gmail.com', 'cash', '', '', '', '', 'pending', '2025-04-03 05:53:29', NULL, 1),
(6, 6, 4, 5000.00, 'erpemem.pascua@gmail.com', 'cash', '1234567890', '09611676764', '', '', 'pending', '2025-04-03 05:58:19', NULL, 1),
(7, 7, 2, 330.00, 'debayn@gmail.com', 'cash', '', '', '', '', 'pending', '2025-04-03 06:19:40', NULL, 1),
(8, 8, 2, 330.00, 'mel@gmail.com', 'gcash', '987546371920123', '09611676764', '', '', 'pending', '2025-04-03 23:58:46', 'proof_67ef20b60941c.png', 1);

-- --------------------------------------------------------

--
-- Table structure for table `membership_plans`
--

CREATE TABLE `membership_plans` (
  `plan_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `free_training_session` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_plans`
--

INSERT INTO `membership_plans` (`plan_id`, `name`, `duration_days`, `price`, `free_training_session`, `description`, `image`, `status`) VALUES
(1, '7-Day Plan', 7, 770.00, 2, 'A 7-Day Plan for you to work your best out!', 'contacts.jpg', 'active'),
(2, '3-Day Plan', 3, 330.00, 1, 'A 3-Day Working out for your muscles!', 'plan_2_1743102090.jpg', 'active'),
(3, '30-Day Plan', 30, 2950.00, 4, 'Enjoy our 30-Day Plan!', 'plan_3_1743129897.jpg', 'active'),
(4, '50-Days Plan', 50, 5000.00, 10, '50 days plan', 'background.jpg', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','approved','cancelled','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `member_id`, `inventory_id`, `date`, `start_time`, `end_time`, `status`) VALUES
(1, 1, 1, '2025-03-28', '05:01:00', '05:07:00', 'cancelled'),
(2, 1, 1, '2025-04-01', '05:20:00', '05:26:00', 'approved'),
(3, 1, 2, '2025-04-01', '05:25:00', '05:35:00', 'approved'),
(4, 1, 1, '2025-03-30', '05:21:00', '05:27:00', 'approved'),
(5, 1, 1, '2025-03-30', '05:21:00', '05:27:00', 'cancelled'),
(6, 1, 1, '2025-04-02', '05:22:00', '05:28:00', 'approved'),
(7, 3, 1, '2025-03-28', '11:49:00', '11:54:00', 'approved'),
(8, 7, 6, '2025-04-06', '07:16:00', '07:22:00', 'approved'),
(9, 7, 3, '2025-04-05', '07:16:00', '07:22:00', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_trainer`
--

CREATE TABLE `schedule_trainer` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `trainer_status` enum('approved','pending','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_trainer`
--

INSERT INTO `schedule_trainer` (`id`, `schedule_id`, `trainer_id`, `trainer_status`) VALUES
(1, 6, 1, 'approved'),
(2, 7, 2, 'approved'),
(3, 8, 6, 'approved');

--
-- Triggers `schedule_trainer`
--
DELIMITER $$
CREATE TRIGGER `decrease_free_training_session_after_schedule` AFTER INSERT ON `schedule_trainer` FOR EACH ROW BEGIN
    DECLARE free_sessions_left INT;

    
    SELECT member_id INTO free_sessions_left
    FROM schedules
    WHERE schedule_id = NEW.schedule_id;

    
    SELECT free_training_session INTO free_sessions_left
    FROM members
    WHERE member_id = free_sessions_left;

    
    IF free_sessions_left > 0 THEN
        UPDATE members
        SET free_training_session = free_sessions_left - 1
        WHERE member_id = free_sessions_left;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `specialty`
--

CREATE TABLE `specialty` (
  `specialty_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialty`
--

INSERT INTO `specialty` (`specialty_id`, `name`) VALUES
(6, 'Calisthenics'),
(1, 'Cycling Workouts'),
(8, 'Exercise'),
(5, 'Jogging'),
(7, 'Swimming'),
(4, 'Swimming Exercises'),
(9, 'Workout Yoga'),
(3, 'Yoga Workout'),
(2, 'Yoga Workouts');

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `trainer_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `availability_status` enum('Available','Unavailable') NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`trainer_id`, `first_name`, `last_name`, `email`, `password`, `age`, `gender`, `image`, `status`, `availability_status`, `created_at`, `updated_at`) VALUES
(1, 'Richelle', 'Cat', 'meow@gmail.com', '', 40, 'female', 'trainer_67e613f819afd.jpg', 'disabled', 'Unavailable', '2025-03-27 19:05:16', '2025-03-28 03:37:40'),
(2, 'Doja', 'Cat', 'princenatsu07@gmail.com', '', 32, 'female', '1741712346', 'disabled', 'Available', '2025-03-27 20:43:56', '2025-04-03 23:27:56'),
(3, 'Haru', 'Natsu', 'johnbagon4@gmail.com', '$2y$10$KKkpi1SK.eWJQ388oHnocO.Taeopw5aj07h95Bu386uefPGcV0CyS', 23, 'male', 'default.png', 'active', 'Available', '2025-03-28 00:09:44', '2025-03-28 00:09:44'),
(4, 'Chi', 'Moreno', 'chi@gmail.com', '$2y$10$/kYIc7RPDwY1DOOek3CYV.NCc/r6jPD9D.an.c6BwKRmThgt5qozi', 21, 'female', 'default.png', 'active', 'Available', '2025-03-28 03:07:12', '2025-03-28 03:07:12'),
(5, 'Chi', 'Moreno', 'chi@gmail.com', '$2y$10$EMfuWKvLZ2/Kb/ScJJ.M9.ZYcpT15BbsMr3NuBcEujURypVuhvpwG', 21, 'female', 'default.png', 'active', 'Available', '2025-03-28 03:07:36', '2025-03-28 03:07:36'),
(6, 'Chi', 'Moreno', 'johnbagon4@gmail.com', '$2y$10$DRJ3hj2vPJl6ggGmO.V3NeCY1pFLuXIg8ozTeygPvkU0Xd0nOPLs.', 21, 'female', 'trainer_67e612941d7b3.jpg', 'active', 'Available', '2025-03-28 03:08:04', '2025-03-28 03:10:24');

-- --------------------------------------------------------

--
-- Table structure for table `trainer_notifications`
--

CREATE TABLE `trainer_notifications` (
  `notification_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainer_payments`
--

CREATE TABLE `trainer_payments` (
  `payment_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainer_reviews`
--

CREATE TABLE `trainer_reviews` (
  `review_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainer_schedule_approval`
--

CREATE TABLE `trainer_schedule_approval` (
  `approval_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `status` enum('approved','pending','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainer_specialty`
--

CREATE TABLE `trainer_specialty` (
  `trainer_id` int(11) NOT NULL,
  `specialty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainer_specialty`
--

INSERT INTO `trainer_specialty` (`trainer_id`, `specialty_id`) VALUES
(1, 8),
(2, 1),
(2, 5),
(2, 6),
(3, 7),
(4, 9),
(5, 9),
(6, 9);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','member','non-member','trainer') NOT NULL DEFAULT 'non-member',
  `image` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `address` varchar(255) NOT NULL,
  `height` varchar(255) NOT NULL,
  `weight` varchar(255) NOT NULL,
  `weight_goal` varchar(255) NOT NULL,
  `medical_condition` varchar(255) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `medical_certificate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `phone_number`, `password`, `user_type`, `image`, `age`, `gender`, `created_at`, `username`, `birthdate`, `address`, `height`, `weight`, `weight_goal`, `medical_condition`, `medical_conditions`, `description`, `updated_at`, `medical_certificate`) VALUES
(1, 'Neo', 'Bagon', 'johnbagon4@gmail.com', '09611676764', '$2y$10$3mg/zZo/GomjPXS4JeSdTuY16Yj5RL9OYAYfumCBI90xVoFgsrQIu', 'admin', '1743096102_1741615978_cirrus.jpg', 19, 'male', '2025-03-27 14:18:42', 'neobagon', '2005-07-13', 'Taguig City', '5\'6', '56', '50', 'no', '', 'Admin #1', '2025-03-27 17:21:42', NULL),
(2, 'Edilyn', 'Mallo', 'princenatsu07@gmail.com', '09611676762', '$2y$10$HHm5vua8Zbu.D.G5HSv6h.APpH.aCree4PkUt5hs4lAg3awlo6w/q', 'member', '1743114164_about1.jpg', 21, 'male', '2025-03-27 14:35:47', 'edilynmallo', '2004-03-21', 'Pae', '5\'2', '55', '50', 'no', '', '', '2025-03-27 22:22:44', NULL),
(3, 'Emma', 'Pascua', 'emmarose@gmail.com', '09611676764', '$2y$10$kbV//V4n6jf3nF0kCGs7c.WgPeMZQh43dQsagQVdH4yYHA8UDlTzW', 'member', NULL, 24, 'female', '2025-03-27 23:16:19', 'emmapascua', '2001-01-01', 'Paranaque', '54', '55', '51', 'no', '', NULL, '2025-03-28 03:40:00', NULL),
(4, 'Marie', 'Yumang', 'nella@gmail.com', '09611676769', '$2y$10$dgxCq6v1fo/TIZzRSUoNtOtMe5GUG42hDcEeN6PJJ7hXa1j3OiRvu', 'trainer', '1743117919_offers3.jpg', 23, 'female', '2025-03-27 23:18:28', 'marieyumang', '2002-02-02', 'taguig', '52', '54', '54', 'no', '', '', '2025-04-03 22:24:23', NULL),
(5, 'Renz', 'Mark', 'renz@gmail.com', '09611676764', '$2y$10$JUIEJ0hZAcCbACavqhwB6OdEnqCHw7Jg1vN3QcboleEfB2yTYK3Yu', 'member', NULL, 22, 'male', '2025-03-27 23:57:56', 'renzmark', '2003-03-03', 'Taguig City', '58', '56', '61', 'yes', 'Asthma, Diabetes, Heart Disease, Hypertension, Other', NULL, '2025-03-28 00:02:25', NULL),
(6, 'Aia', 'Garcia', 'aia@gmail.com', '09611676760', '$2y$10$YEN6gLhujEG.6UVuuVlGbeuybdSq191GVVvB0FiK6UR/sv8Byd0.W', 'member', NULL, 20, 'male', '2025-03-28 02:48:06', 'aiagarcia', '2005-03-07', 'Taguig City', '52', '63', '55', 'no', '', '', '2025-03-28 03:03:50', NULL),
(9, 'renz', 'mark', 'edilyn123@gmail.com', '09611676764', '$2y$10$yYrMlNAk.fHPn0VfBdyEIOCcNE0MvaaxmtnpwLdNSQtQmQx6t2csG', 'member', NULL, 22, 'male', '2025-03-28 03:31:13', 'renzmark', '2003-03-03', 'Taguig City', '56', '56', '56', 'no', '', NULL, '2025-03-28 03:33:25', NULL),
(10, 'Billie', 'Eilish', 'billie@gmail.com', '09611676764', '$2y$10$1QG5I5.WHHw3kbLCJf7UC.x6Aq5jQnvaYevvLBtTLOUYub3IEvmDO', 'member', NULL, 24, 'female', '2025-04-03 04:00:36', 'billieeilish', '2001-01-01', 'USA', '167', '70', '70', 'no', 'SAKIT SA ULO SI EDILYN', '', '2025-04-03 05:58:19', 'medical_certificates/billieeilish_certificate_1743652836.png'),
(11, 'Mari', 'Leano', 'mari@gmail.com', '09611676764', '$2y$10$fFUEG7zFLqc3Lprf/Xn.O.mZmih7MJBiTJJ0/m2tyMGN4E9MEwdkO', 'member', NULL, 21, '', '2025-04-03 05:50:34', 'marileano', '2003-07-07', 'japan', '141', '50', '45', 'yes', 'Asthma, Diabetes, Heart Disease, Hypertension, PWD, RACIST', NULL, '2025-04-03 05:53:29', 'medical_certificates/marileano_certificate_1743659434.png'),
(12, 'Debayn', 'Untalan', 'debayn@gmail.com', '09611676764', '$2y$10$u33df2uY.AXgJhspumoJ0OP72jUxSlwg8sy/DOg/7kVR/VOFOBfru', 'member', NULL, 24, 'male', '2025-04-03 06:19:22', 'debaynuntalan', '2001-01-01', 'Taguig', '157', '54', '54', 'yes', 'Diabetes', NULL, '2025-04-03 06:19:40', 'medical_certificates/debaynuntalan_certificate_1743661162.png'),
(13, 'Mel', 'Mar', 'mel@gmail.com', '09611676764', '$2y$10$rE9U0L/lOXDr1nwriP/ei.2SSrc6eao8NRjMpU8Sl4b5RaYn.wPMC', 'member', NULL, 23, 'female', '2025-04-03 23:48:12', 'melmar', '2001-07-07', 'Somewhere', '172', '56', '55', 'yes', 'Diabetes', NULL, '2025-04-03 23:58:46', 'medical_certificates/melmar_certificate_1743724092.png');

-- --------------------------------------------------------

--
-- Table structure for table `usertypeupdate`
--

CREATE TABLE `usertypeupdate` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `user_type` varchar(50) NOT NULL,
  `change_reason` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usertypeupdate`
--

INSERT INTO `usertypeupdate` (`id`, `user_id`, `name`, `user_type`, `change_reason`, `updated_at`) VALUES
(1, 4, 'Marie Yumang', 'trainer', 'You are now registered as a trainer', '2025-04-03 22:24:23');

-- --------------------------------------------------------

--
-- Table structure for table `workout_plans`
--

CREATE TABLE `workout_plans` (
  `plan_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `equipment_inventory`
--
ALTER TABLE `equipment_inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD UNIQUE KEY `identifier` (`identifier`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `equipment_usage`
--
ALTER TABLE `equipment_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `membership_payments`
--
ALTER TABLE `membership_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `content_id` (`content_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `schedule_trainer`
--
ALTER TABLE `schedule_trainer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `specialty`
--
ALTER TABLE `specialty`
  ADD PRIMARY KEY (`specialty_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`trainer_id`);

--
-- Indexes for table `trainer_notifications`
--
ALTER TABLE `trainer_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `trainer_payments`
--
ALTER TABLE `trainer_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `trainer_reviews`
--
ALTER TABLE `trainer_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trainer_schedule_approval`
--
ALTER TABLE `trainer_schedule_approval`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `trainer_specialty`
--
ALTER TABLE `trainer_specialty`
  ADD PRIMARY KEY (`trainer_id`,`specialty_id`),
  ADD KEY `specialty_id` (`specialty_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `usertypeupdate`
--
ALTER TABLE `usertypeupdate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user` (`user_id`);

--
-- Indexes for table `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `member_id` (`member_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `equipment_inventory`
--
ALTER TABLE `equipment_inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `equipment_usage`
--
ALTER TABLE `equipment_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `membership_payments`
--
ALTER TABLE `membership_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `schedule_trainer`
--
ALTER TABLE `schedule_trainer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `specialty`
--
ALTER TABLE `specialty`
  MODIFY `specialty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `trainer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `trainer_notifications`
--
ALTER TABLE `trainer_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainer_payments`
--
ALTER TABLE `trainer_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainer_reviews`
--
ALTER TABLE `trainer_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainer_schedule_approval`
--
ALTER TABLE `trainer_schedule_approval`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `usertypeupdate`
--
ALTER TABLE `usertypeupdate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `workout_plans`
--
ALTER TABLE `workout_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `admin_notifications_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `content_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment_inventory`
--
ALTER TABLE `equipment_inventory`
  ADD CONSTRAINT `equipment_inventory_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment_usage`
--
ALTER TABLE `equipment_usage`
  ADD CONSTRAINT `equipment_usage_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `equipment_inventory` (`inventory_id`),
  ADD CONSTRAINT `equipment_usage_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`plan_id`) ON DELETE CASCADE;

--
-- Constraints for table `membership_payments`
--
ALTER TABLE `membership_payments`
  ADD CONSTRAINT `membership_payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `membership_payments_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`plan_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `equipment_inventory` (`inventory_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule_trainer`
--
ALTER TABLE `schedule_trainer`
  ADD CONSTRAINT `schedule_trainer_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_trainer_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE;

--
-- Constraints for table `trainer_notifications`
--
ALTER TABLE `trainer_notifications`
  ADD CONSTRAINT `trainer_notifications_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainer_notifications_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `trainer_payments`
--
ALTER TABLE `trainer_payments`
  ADD CONSTRAINT `trainer_payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainer_payments_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE;

--
-- Constraints for table `trainer_reviews`
--
ALTER TABLE `trainer_reviews`
  ADD CONSTRAINT `trainer_reviews_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainer_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `trainer_schedule_approval`
--
ALTER TABLE `trainer_schedule_approval`
  ADD CONSTRAINT `trainer_schedule_approval_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainer_schedule_approval_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE;

--
-- Constraints for table `trainer_specialty`
--
ALTER TABLE `trainer_specialty`
  ADD CONSTRAINT `trainer_specialty_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainer_specialty_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialty` (`specialty_id`) ON DELETE CASCADE;

--
-- Constraints for table `usertypeupdate`
--
ALTER TABLE `usertypeupdate`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD CONSTRAINT `workout_plans_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
