DROP DATABASE IF EXISTS flexifit_db;
CREATE DATABASE flexifit_db;
USE flexifit_db;

-- Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'member', 'guest') NOT NULL DEFAULT 'guest',
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
    price DECIMAL(10,2) NOT NULL
);

-- Members Table
CREATE TABLE members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    membership_status ENUM('active','expired','pending') DEFAULT 'pending',
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    renewed_date DATE DEFAULT NULL
);

-- Trainers Table
CREATE TABLE trainers (
    trainer_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    age INT,
    gender ENUM('male', 'female', 'other'),
    specialty VARCHAR(100) DEFAULT NULL,
    availability_status ENUM('available','unavailable') DEFAULT 'available',
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','disabled') DEFAULT 'active'
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
    status ENUM('available', 'in_use', 'maintenance') NOT NULL DEFAULT 'available'
);

-- Content Table
CREATE TABLE content (
    content_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    content_type ENUM('guide','tip','announcement','other') NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Feedback Table
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trainer_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    feedback_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Workout Plans Table
CREATE TABLE workout_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments Table
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Schedules Table
CREATE TABLE schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    inventory_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending','approved','cancelled','completed') DEFAULT 'pending'
);

-- Schedule Trainer Table
CREATE TABLE schedule_trainer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    trainer_id INT NOT NULL
);

-- Notifications Table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Alter Table Commands for Relationships
ALTER TABLE members ADD CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;
ALTER TABLE members ADD CONSTRAINT fk_members_plan FOREIGN KEY (plan_id) REFERENCES membership_plans(plan_id) ON DELETE CASCADE;
ALTER TABLE equipment_inventory ADD CONSTRAINT fk_equipment_inventory_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE;
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;
ALTER TABLE feedback ADD CONSTRAINT fk_feedback_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE;
ALTER TABLE workout_plans ADD CONSTRAINT fk_workout_plans_member FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE;
ALTER TABLE payments ADD CONSTRAINT fk_payments_member FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE;
ALTER TABLE schedules ADD CONSTRAINT fk_schedules_member FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE;
ALTER TABLE schedules ADD CONSTRAINT fk_schedules_inventory FOREIGN KEY (inventory_id) REFERENCES equipment_inventory(inventory_id) ON DELETE CASCADE;
ALTER TABLE schedule_trainer ADD CONSTRAINT fk_schedule_trainer_schedule FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE CASCADE;
ALTER TABLE schedule_trainer ADD CONSTRAINT fk_schedule_trainer_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE;
ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;

-- Triggers for Membership Date Calculations
DELIMITER $$
CREATE TRIGGER set_end_date_before_insert BEFORE INSERT ON members FOR EACH ROW BEGIN
    DECLARE plan_days INT;
    SELECT duration_days INTO plan_days FROM membership_plans WHERE plan_id = NEW.plan_id;
    SET NEW.end_date = DATE_ADD(NEW.start_date, INTERVAL plan_days DAY);
END $$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER update_dates_on_renewal BEFORE UPDATE ON members FOR EACH ROW BEGIN
    IF NEW.renewed_date IS NOT NULL THEN
        SET NEW.start_date = NEW.renewed_date;
        SET NEW.end_date = (SELECT DATE_ADD(NEW.renewed_date, INTERVAL duration_days DAY) FROM membership_plans WHERE plan_id = NEW.plan_id);
    END IF;
END $$
DELIMITER ;
