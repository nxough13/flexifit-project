-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 23, 2025 at 03:01 AM
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
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `content_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content_type` enum('guide','tip','announcement','other') NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content`
--

INSERT INTO `content` (`content_id`, `admin_id`, `title`, `description`, `content_type`, `file_path`, `image`, `created_at`) VALUES
(1, 1, 'How to Start Strength Training', 'A beginner-friendly guide to lifting weights.', 'guide', NULL, NULL, '2025-02-23 01:21:40'),
(2, 1, 'Cardio Tips', 'Best practices for effective cardio workouts.', 'tip', NULL, NULL, '2025-02-23 01:21:40');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `availability_status` enum('available','in use','maintenance') DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `name`, `quantity`, `availability_status`, `image`) VALUES
(1, 'Treadmill', 5, 'available', NULL),
(2, 'Dumbbells', 10, 'available', NULL),
(3, 'Bench Press', 3, 'available', NULL),
(4, 'Cycling Machine', 4, 'available', NULL),
(5, 'Rowing Machine', 2, 'available', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `feedback_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `member_id`, `trainer_id`, `rating`, `comments`, `feedback_date`, `update_date`) VALUES
(1, 1, 1, 5, 'Great trainer, very helpful!', '2025-02-23 01:21:40', NULL),
(2, 2, 2, 4, 'Good training session.', '2025-02-23 01:21:40', NULL);

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
  `end_date` date DEFAULT NULL,
  `renewed_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `user_id`, `membership_status`, `plan_id`, `start_date`, `end_date`, `renewed_date`) VALUES
(1, 2, 'active', 2, '2025-02-01', '2025-03-01', NULL),
(2, 3, 'active', 3, '2025-02-10', NULL, NULL),
(3, 4, 'pending', 1, '2025-02-15', NULL, NULL);

--
-- Triggers `members`
--
DELIMITER $$
CREATE TRIGGER `set_end_date_before_insert` BEFORE INSERT ON `members` FOR EACH ROW BEGIN
    DECLARE plan_days INT;
    
    
    SELECT duration_days INTO plan_days 
    FROM membership_plans 
    WHERE plan_id = NEW.plan_id;

    
    SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL plan_days DAY);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_dates_on_renewal` BEFORE UPDATE ON `members` FOR EACH ROW BEGIN
    
    IF NEW.renewed_date IS NOT NULL THEN
        
        SET NEW.start_date = NEW.renewed_date;

        
        SET NEW.end_date = (SELECT DATE_ADD(NEW.renewed_date, INTERVAL duration_days DAY) 
                            FROM membership_plans 
                            WHERE plan_id = NEW.plan_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `membership_plans`
--

CREATE TABLE `membership_plans` (
  `plan_id` int(11) NOT NULL,
  `plan_name` varchar(50) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_plans`
--

INSERT INTO `membership_plans` (`plan_id`, `plan_name`, `duration_days`, `price`) VALUES
(1, '1 Week Plan', 7, 10.00),
(2, '1 Month Plan', 30, 30.00),
(3, '3 Months Plan', 90, 75.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_method` enum('credit_card','paypal','gcash','bank_transfer') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `member_id`, `amount`, `payment_date`, `payment_status`, `payment_method`) VALUES
(1, 1, 30.00, '2025-02-23 01:21:39', 'completed', 'paypal'),
(2, 2, 75.00, '2025-02-23 01:21:39', 'completed', 'credit_card');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','approved','cancelled','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `member_id`, `equipment_id`, `session_date`, `start_time`, `end_time`, `status`) VALUES
(1, 1, 1, '2025-02-25', '08:00:00', '09:00:00', 'approved'),
(2, 2, 3, '2025-02-26', '10:00:00', '11:00:00', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_trainer`
--

CREATE TABLE `schedule_trainer` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_trainer`
--

INSERT INTO `schedule_trainer` (`id`, `schedule_id`, `trainer_id`) VALUES
(1, 1, 1),
(2, 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `trainer_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `availability_status` enum('available','unavailable') DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`trainer_id`, `first_name`, `last_name`, `email`, `specialty`, `availability_status`, `image`, `status`) VALUES
(1, 'Jake', 'Williams', 'jake.trainer@example.com', 'Strength Training', 'available', NULL, 'active'),
(2, 'Emily', 'Davis', 'emily.trainer@example.com', 'Cardio Workouts', 'available', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','member','guest') NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `user_type`, `image`, `created_at`) VALUES
(1, 'Admin', 'User', 'admin@example.com', 'admin123', 'admin', NULL, '2025-02-23 01:21:38'),
(2, 'John', 'Doe', 'john@example.com', 'password123', 'member', NULL, '2025-02-23 01:21:38'),
(3, 'Jane', 'Smith', 'jane@example.com', 'password123', 'member', NULL, '2025-02-23 01:21:38'),
(4, 'Mike', 'Brown', 'mike@example.com', 'password123', 'member', NULL, '2025-02-23 01:21:38'),
(5, 'Guest', 'User', 'guest@example.com', 'guest123', 'guest', NULL, '2025-02-23 01:21:38'),
(6, 'John Neo', 'Bagon', 'johnb4@gmail.com', '$2y$10$fpI0xLJ4rxVHl/vUt4Wl1eNtV3V01JLLglCUEFwBAjALz4yVWx.yC', 'member', NULL, '2025-02-23 01:27:49');

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
-- Dumping data for table `workout_plans`
--

INSERT INTO `workout_plans` (`plan_id`, `member_id`, `title`, `description`, `created_at`) VALUES
(1, 1, 'Basic Cardio Plan', 'Treadmill + Cycling for 30 min each.', '2025-02-23 01:21:39'),
(2, 2, 'Strength Training Plan', 'Bench Press, Dumbbells, and Core exercises.', '2025-02-23 01:21:39');

--
-- Indexes for dumped tables
--

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
  ADD PRIMARY KEY (`equipment_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `schedule_trainer`
--
ALTER TABLE `schedule_trainer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`trainer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

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
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `schedule_trainer`
--
ALTER TABLE `schedule_trainer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `trainer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `workout_plans`
--
ALTER TABLE `workout_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `content_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE SET NULL;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`plan_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule_trainer`
--
ALTER TABLE `schedule_trainer`
  ADD CONSTRAINT `schedule_trainer_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_trainer_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD CONSTRAINT `workout_plans_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
