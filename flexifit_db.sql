DROP DATABASE IF EXISTS flexifit_db;
CREATE DATABASE flexifit_db;
USE flexifit_db;

-- Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'member', 'non-member') NOT NULL DEFAULT 'non-member',
    image VARCHAR(255),
    age INT,
    gender ENUM('male', 'female', 'other'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Membership Plans Table
CREATE TABLE membership_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    duration_days INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    free_training_session INT DEFAULT 0 -- New column for free training sessions
);

-- Members Table
CREATE TABLE members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    membership_status ENUM('active','expired','pending') DEFAULT 'pending',
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    renewed_date DATE DEFAULT NULL,
    free_training_session INT DEFAULT 0, -- New column for tracking free training sessions
    workout_plans VARCHAR(255) DEFAULT 'none', -- New column for workout plans
    health_status VARCHAR(255) DEFAULT NULL, -- New column for health conditions
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(plan_id) ON DELETE CASCADE
);

-- Trainers Table
CREATE TABLE trainers (
    trainer_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    age INT,
    gender ENUM('male', 'female', 'other'),
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','disabled') DEFAULT 'active'
);

-- Specialty Table (New)
CREATE TABLE specialty (
    specialty_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Trainer Specialty Relation Table (New)
CREATE TABLE trainer_specialty (
    trainer_id INT NOT NULL,
    specialty_id INT NOT NULL,
    PRIMARY KEY (trainer_id, specialty_id),
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE,
    FOREIGN KEY (specialty_id) REFERENCES specialty(specialty_id) ON DELETE CASCADE
);

-- Equipment Table
CREATE TABLE equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255)
);

-- Equipment Inventory Table
CREATE TABLE equipment_inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    identifier VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('available', 'in_use', 'maintenance') NOT NULL DEFAULT 'available',
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE
);

-- Content Table
CREATE TABLE content (
    content_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    content_type ENUM('guide','tip','announcement','workout_plan','other') NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Feedback Table (Trainer Ratings)
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trainer_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    feedback_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE
);

-- Reviews Table (Content Reviews)
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(content_id) ON DELETE CASCADE
);

-- Payments Table for Membership
CREATE TABLE membership_payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

-- Payments Table for Trainer Sessions
CREATE TABLE trainer_payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    trainer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE
);

-- Workout Plans Table
CREATE TABLE workout_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

-- Schedules Table
CREATE TABLE schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    inventory_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending','approved','cancelled','completed') DEFAULT 'pending',
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES equipment_inventory(inventory_id) ON DELETE CASCADE
);

-- Schedule Trainer Table
CREATE TABLE schedule_trainer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    trainer_id INT NOT NULL,
    FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE
);

-- Notifications Table for Trainer Requests
CREATE TABLE trainer_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    member_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

-- Notifications Table for Admin Membership Requests
CREATE TABLE admin_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Triggers for Membership Date Calculations
DELIMITER $$
CREATE TRIGGER set_end_date_before_insert BEFORE INSERT ON members FOR EACH ROW BEGIN
    DECLARE plan_days INT;
    SELECT duration_days INTO plan_days FROM membership_plans WHERE plan_id = NEW.plan_id;
    SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL plan_days DAY);
END $$

DELIMITER $$

CREATE TRIGGER update_dates_on_renewal BEFORE UPDATE ON members FOR EACH ROW BEGIN
    IF NEW.renewed_date IS NOT NULL THEN
        SET NEW.start_date = NEW.renewed_date;
        SET NEW.end_date = (SELECT DATE_ADD(NEW.renewed_date, INTERVAL duration_days DAY) FROM membership_plans WHERE plan_id = NEW.plan_id);
    END IF;
END $$

DELIMITER $$

DELIMITER $$

-- Trigger to decrease free training session after a member schedules a session
CREATE TRIGGER decrease_free_training_session_after_schedule
AFTER INSERT ON schedule_trainer
FOR EACH ROW
BEGIN
    DECLARE free_sessions_left INT;

    -- Get the member_id from the schedules table using the schedule_id
    SELECT member_id INTO free_sessions_left
    FROM schedules
    WHERE schedule_id = NEW.schedule_id;

    -- Get the current number of free training sessions the member has left
    SELECT free_training_session INTO free_sessions_left
    FROM members
    WHERE member_id = free_sessions_left;

    -- If the member has free sessions left, decrease it by 1
    IF free_sessions_left > 0 THEN
        UPDATE members
        SET free_training_session = free_sessions_left - 1
        WHERE member_id = free_sessions_left;
    END IF;
END $$

DELIMITER ;


ALTER TABLE users 
ADD COLUMN username VARCHAR(255) NOT NULL,
ADD COLUMN birthdate DATE NOT NULL,
ADD COLUMN address VARCHAR(255) NOT NULL,
ADD COLUMN height VARCHAR(255) NOT NULL,
ADD COLUMN weight VARCHAR(255) NOT NULL,
ADD COLUMN weight_goal VARCHAR(255) NOT NULL,
ADD COLUMN medical_condition VARCHAR(255) DEFAULT NULL,
ADD COLUMN medical_conditions TEXT;

ALTER TABLE users ADD COLUMN description TEXT;

ALTER TABLE trainers
ADD COLUMN availability_status ENUM('Available','Unavailable') NOT NULL DEFAULT 'Available';


ALTER TABLE membership_plans
ADD COLUMN description TEXT DEFAULT NULL, 
ADD COLUMN image VARCHAR(255) DEFAULT NULL;
ADD COLUMN status ENUM('active', 'disabled') DEFAULT ACTIVE;

