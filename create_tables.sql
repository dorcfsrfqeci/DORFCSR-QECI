-- SQL script to create missing tables for clearance_system_db

-- Create user_database table
CREATE TABLE IF NOT EXISTS user_database (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('Student', 'Staff', 'Admin') NOT NULL,
    email VARCHAR(255) UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create student_information_database table
CREATE TABLE IF NOT EXISTS student_information_database (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    student_lastname VARCHAR(100),
    student_firstname VARCHAR(100),
    student_middlename VARCHAR(100),
    student_year VARCHAR(10),
    student_course VARCHAR(100),
    student_section VARCHAR(50),
    student_sem VARCHAR(20),
    contact_num VARCHAR(20),
    permanent_address TEXT,
    date_of_birth DATE,
    year_graduated VARCHAR(10),
    elementary_school VARCHAR(255),
    jhs_school VARCHAR(255),
    shs_school VARCHAR(255),
    college_community VARCHAR(255),
    reason_clearance TEXT,
    scholastic_document VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES user_database(user_id) ON DELETE CASCADE
);

-- Create student_clearance_database table
CREATE TABLE IF NOT EXISTS student_clearance_database (
    clearance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    clearance_status ENUM('Pending', 'Cleared', 'Rejected') DEFAULT 'Pending',
    unit_stats VARCHAR(50) DEFAULT 'Not Submitted',
    records_officer_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    computer_lab_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    librarian_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    finance_officer_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    cbrc_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES user_database(user_id) ON DELETE CASCADE
);

-- Create payment_record_database table
CREATE TABLE IF NOT EXISTS payment_record_database (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    or_num VARCHAR(100) DEFAULT NULL,
    student_id VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) DEFAULT 150.00,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    reference_number VARCHAR(100),
    status ENUM('Pending', 'Completed', 'Failed') DEFAULT 'Completed',
    FOREIGN KEY (student_id) REFERENCES user_database(user_id) ON DELETE CASCADE
);

-- Ensure or_num is indexed/unique for FK lookups (if present)
ALTER TABLE payment_record_database
    ADD UNIQUE KEY uq_payment_or_num (or_num(100));

-- Create staff_notifications table (if not already created by code)
CREATE TABLE IF NOT EXISTS staff_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    notification_type VARCHAR(100) DEFAULT 'form_submission',
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES user_database(user_id),
    FOREIGN KEY (student_id) REFERENCES user_database(user_id)
);

-- Insert sample admin user (if not exists)
INSERT IGNORE INTO user_database (user_id, full_name, role, email, username, password) 
VALUES ('ADMIN001', 'Administrator', 'Admin', 'admin@qeci.edu.ph', 'admin', '$2y$10$examplehashedpassword');  -- Replace with actual hash for 'password'

-- Insert sample staff users
INSERT IGNORE INTO user_database (user_id, full_name, role, email, username, password) 
VALUES 
('STF001', 'Mrs. Janie Ruth Barenno', 'Staff', 'ms.janierc@gmail.com', 'janie_barenno', '$2y$10$examplehashedpassword'),
('STF002', 'Mr. Reyniel Centeno', 'Staff', 'mr.reynielc@gmail.com', 'reyniel_centeno', '$2y$10$examplehashedpassword'),
('STF003', 'Mrs. Maritess Panal', 'Staff', 'mrs.panalib@gmail.com', 'maritess_panal', '$2y$10$examplehashedpassword'),
('STF004', 'Ms. Chin-Chin Devilles', 'Staff', 'mrs.devillesfo@gmail.com', 'chinchin_devilles', '$2y$10$examplehashedpassword'),
('STF005', 'Ms. Kristine Dela Cruz', 'Staff', 'ms.delacruzcbcr@gmail.com', 'kristine_delacruz', '$2y$10$examplehashedpassword');

-- Insert sample student clearances (one per student, adjust as needed)
-- This assumes students exist in user_database
INSERT IGNORE INTO student_clearance_database (student_id) 
SELECT user_id FROM user_database WHERE role = 'Student';

-- Create completion_report_database table (used by completion report pages)
CREATE TABLE IF NOT EXISTS completion_report_database (
    studentrecord_id VARCHAR(64) PRIMARY KEY,
    or_num VARCHAR(100),
    student_id VARCHAR(50),
    student_lastname VARCHAR(100),
    student_firstname VARCHAR(100),
    student_middlename VARCHAR(100),
    student_year VARCHAR(10),
    student_course VARCHAR(100),
    student_section VARCHAR(50),
    student_address TEXT,
    contact_num VARCHAR(50),
    unit_con VARCHAR(50),
    unit_stats VARCHAR(50) DEFAULT 'Pending',
    release_date DATETIME DEFAULT NULL,
    remarks TEXT,
    received_by VARCHAR(255),
    scholasticrec_stat VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_or_num (or_num(100)),
    CONSTRAINT fk_completion_payment FOREIGN KEY (or_num) REFERENCES payment_record_database(or_num) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Create requestor_database table to mirror student submissions for external/requestor views
CREATE TABLE IF NOT EXISTS requestor_database (
    requestor_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    student_lastname VARCHAR(100),
    student_firstname VARCHAR(100),
    student_middlename VARCHAR(100),
    student_year VARCHAR(10),
    student_course VARCHAR(100),
    student_section VARCHAR(50),
    contact_num VARCHAR(20),
    permanent_address TEXT,
    request_type VARCHAR(100) DEFAULT 'FormSubmission',
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_requestor_student (student_id),
    FOREIGN KEY (student_id) REFERENCES user_database(user_id) ON DELETE CASCADE
);