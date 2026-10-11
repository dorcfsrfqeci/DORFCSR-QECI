-- Restore completion_report_database and ensure payment_record_database has or_num
-- Run this in phpMyAdmin SQL tab for database `clearance_system_db` or import the file.

-- 1) Add `or_num` column if missing
ALTER TABLE payment_record_database
  ADD COLUMN IF NOT EXISTS or_num VARCHAR(100) DEFAULT NULL;

-- 2) Ensure there's an index on or_num (drop existing index with same name first to avoid errors)
DROP INDEX IF EXISTS idx_payment_or_num ON payment_record_database;
ALTER TABLE payment_record_database
  ADD INDEX idx_payment_or_num (or_num);

-- 3) Create completion_report_database table (safe: IF NOT EXISTS)
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
  INDEX idx_or_num (or_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3a) Add foreign key after table creation without specifying a constraint name
-- This avoids duplicate named-constraint errors across the schema
ALTER TABLE completion_report_database
  ADD FOREIGN KEY (or_num) REFERENCES payment_record_database(or_num) ON DELETE SET NULL ON UPDATE CASCADE;

-- 4) Optional: run the setup script in browser to populate the table
-- Open: http://localhost/DORCFSRF/setup_completion_report.php

-- 5) Verify counts
SELECT COUNT(*) AS total FROM completion_report_database;
SELECT COUNT(*) AS payments_linked FROM completion_report_database WHERE or_num IS NOT NULL;
