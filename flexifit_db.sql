-- Drop the database if it exists (optional)
DROP DATABASE IF EXISTS flexifit_db;
CREATE DATABASE flexifit_db;
USE flexifit_db;

-- -----------------------------------------
-- 1️⃣ Users Table (No Foreign Keys)
-- -----------------------------------------
CREATE TABLE `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `user_type` ENUM('admin', 'member', 'guest') NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 2️⃣ Equipment Table (No Foreign Keys)
-- -----------------------------------------
CREATE TABLE `equipment` (
  `equipment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1, -- New column for tracking quantity
  `availability_status` ENUM('available', 'in use', 'maintenance') DEFAULT 'available',
  `image` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 3️⃣ Membership Plans Table (No Foreign Keys)
-- -----------------------------------------
CREATE TABLE `membership_plans` (
  `plan_id` INT(11) NOT NULL AUTO_INCREMENT,
  `plan_name` VARCHAR(50) NOT NULL,
  `duration_days` INT(11) NOT NULL, -- Duration in days (e.g., 7 for 1 week, 30 for 1 month)
  `price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 4️⃣ Members Table (Depends on Users & Membership Plans)
-- -----------------------------------------
CREATE TABLE `members` (
  `member_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL UNIQUE,
  `membership_status` ENUM('active', 'expired', 'pending') DEFAULT 'pending',
  `plan_id` INT(11) NOT NULL, -- References membership_plans
  `join_date` DATE DEFAULT CURDATE(),
  PRIMARY KEY (`member_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `membership_plans`(`plan_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 5️⃣ Trainers Table (No Foreign Keys)
-- -----------------------------------------
CREATE TABLE `trainers` (
  `trainer_id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `specialty` VARCHAR(100) DEFAULT NULL,
  `availability_status` ENUM('available', 'unavailable') DEFAULT 'available',
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'disabled') DEFAULT 'active',
  PRIMARY KEY (`trainer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 6️⃣ Workout Plans Table (Depends on Members)
-- -----------------------------------------
CREATE TABLE `workout_plans` (
  `plan_id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`plan_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 7️⃣ Schedules Table (Depends on Members & Equipment)
-- -----------------------------------------
CREATE TABLE `schedules` (
  `schedule_id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `equipment_id` INT(11) NOT NULL,
  `session_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` ENUM('pending', 'approved', 'cancelled', 'completed') DEFAULT 'pending',
  PRIMARY KEY (`schedule_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE,
  FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`equipment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 8️⃣ Schedule Trainer Table (Depends on Schedules & Trainers)
-- -----------------------------------------
CREATE TABLE `schedule_trainer` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` INT(11) NOT NULL,
  `trainer_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`schedule_id`) ON DELETE CASCADE,
  FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`trainer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 9️⃣ Payments Table (Depends on Members)
-- -----------------------------------------
CREATE TABLE `payments` (
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `payment_status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
  `payment_method` ENUM('credit_card', 'paypal', 'gcash', 'bank_transfer') NOT NULL,
  PRIMARY KEY (`payment_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 🔟 Feedback Table (Depends on Members & Trainers)
-- -----------------------------------------
CREATE TABLE `feedback` (
  `feedback_id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `trainer_id` INT(11) DEFAULT NULL,
  `rating` INT(11) DEFAULT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comments` TEXT DEFAULT NULL,
  `feedback_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `update_date` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`feedback_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE,
  FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`trainer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------
-- 1️⃣1️⃣ Content Table (Depends on Admin Users)
-- -----------------------------------------
CREATE TABLE `content` (
  `content_id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `content_type` ENUM('guide', 'tip', 'announcement', 'other') NOT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`content_id`),
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

