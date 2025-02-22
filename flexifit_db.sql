DROP DATABASE IF EXISTS flexifit_db;
CREATE DATABASE flexifit_db;
USE flexifit_db;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `user_type` ENUM('admin','member','guest') NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: membership_plans (New Table)
-- --------------------------------------------------------
CREATE TABLE `membership_plans` (
  `plan_id` INT(11) NOT NULL AUTO_INCREMENT,
  `plan_name` VARCHAR(50) NOT NULL,
  `duration_days` INT(11) NOT NULL, -- E.g., 7 for 1 week, 30 for 1 month
  `price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: members (Updated to include membership plan)
-- --------------------------------------------------------
CREATE TABLE `members` (
  `member_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL UNIQUE,
  `plan_id` INT(11) NOT NULL, -- References the membership plan
  `membership_status` ENUM('active','expired','pending') DEFAULT 'pending',
  `join_date` DATE DEFAULT CURDATE(),
  PRIMARY KEY (`member_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`plan_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: trainers
-- --------------------------------------------------------
CREATE TABLE `trainers` (
  `trainer_id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `specialty` VARCHAR(100) DEFAULT NULL,
  `availability_status` ENUM('available','unavailable') DEFAULT 'available',
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'disabled') DEFAULT 'active',
  PRIMARY KEY (`trainer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: equipment (Updated to include quantity)
-- --------------------------------------------------------
CREATE TABLE `equipment` (
  `equipment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1, -- New column to track quantity
  `availability_status` ENUM('available','in use','maintenance') DEFAULT 'available',
  `image` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: equipment_inventory (New table for tracking individual units)
-- --------------------------------------------------------
CREATE TABLE `equipment_inventory` (
  `inventory_id` INT(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` INT(11) NOT NULL,
  `unit_number` INT(11) NOT NULL, -- E.g., 1 for treadmill_1, 2 for treadmill_2
  `status` ENUM('available','in use','maintenance') DEFAULT 'available',
  PRIMARY KEY (`inventory_id`),
  FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: schedules (Updated to track individual equipment units)
-- --------------------------------------------------------
CREATE TABLE `schedules` (
  `schedule_id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `inventory_id` INT(11) NOT NULL, -- References a specific equipment unit
  `session_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` ENUM('pending','approved','cancelled','completed') DEFAULT 'pending',
  PRIMARY KEY (`schedule_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  FOREIGN KEY (`inventory_id`) REFERENCES `equipment_inventory` (`inventory_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: schedule_trainer
-- --------------------------------------------------------
CREATE TABLE `schedule_trainer` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` INT(11) NOT NULL,
  `trainer_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE,
  FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: payments (Updated to reference membership plans)
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `plan_id` INT(11) NOT NULL, -- References the membership plan
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `payment_status` ENUM('pending','completed','failed') DEFAULT 'pending',
  `payment_method` ENUM('credit_card','paypal','gcash','bank_transfer') NOT NULL,
  PRIMARY KEY (`payment_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`plan_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: workout_plans (New Feature)
-- --------------------------------------------------------
CREATE TABLE `workout_plans` (
  `plan_id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `plan_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`plan_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: workout_exercises (New Table for storing workout details)
-- --------------------------------------------------------
CREATE TABLE `workout_exercises` (
  `exercise_id` INT(11) NOT NULL AUTO_INCREMENT,
  `plan_id` INT(11) NOT NULL,
  `exercise_name` VARCHAR(100) NOT NULL,
  `sets` INT(11) DEFAULT NULL,
  `reps` INT(11) DEFAULT NULL,
  `equipment_id` INT(11) DEFAULT NULL, -- Optional
  PRIMARY KEY (`exercise_id`),
  FOREIGN KEY (`plan_id`) REFERENCES `workout_plans` (`plan_id`) ON DELETE CASCADE,
  FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
