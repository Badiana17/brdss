-- BRDSS Database Schema Backup
-- Created: 2025-12-03
-- This file contains the complete database schema for BRDSS
-- To restore: mysql -u root brdss_db < database_schema_backup.sql

-- =====================================================
-- Table: residents
-- =====================================================
CREATE TABLE IF NOT EXISTS residents (
  resident_id int(11) NOT NULL AUTO_INCREMENT,
  first_name varchar(100) NOT NULL,
  middle_name varchar(100),
  last_name varchar(100) NOT NULL,
  age int(3),
  gender enum('Male','Female','Other'),
  address varchar(255),
  contact_number varchar(20),
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (resident_id),
  KEY idx_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: beneficiary_category
-- =====================================================
CREATE TABLE IF NOT EXISTS beneficiary_category (
  category_id int(11) NOT NULL AUTO_INCREMENT,
  category_name varchar(100) NOT NULL,
  description text,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (category_id),
  UNIQUE KEY unique_category (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: resident_beneficiary
-- =====================================================
CREATE TABLE IF NOT EXISTS resident_beneficiary (
  rb_id int(11) NOT NULL AUTO_INCREMENT,
  resident_id int(11) NOT NULL,
  category_id int(11) NOT NULL,
  date_classified date NOT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (rb_id),
  KEY idx_resident (resident_id),
  KEY idx_category (category_id),
  FOREIGN KEY (resident_id) REFERENCES residents(resident_id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES beneficiary_category(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: users
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
  user_id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(50) NOT NULL UNIQUE,
  email varchar(100) NOT NULL UNIQUE,
  password_hash varchar(255) NOT NULL,
  role enum('Admin','Super Admin','Staff') DEFAULT 'Staff',
  is_active tinyint(1) DEFAULT 1,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  KEY idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: assistance_records
-- =====================================================
CREATE TABLE IF NOT EXISTS assistance_records (
  assistance_id int(11) NOT NULL AUTO_INCREMENT,
  resident_id int(11) NOT NULL,
  assistance_type varchar(100),
  amount decimal(10,2),
  assistance_date date,
  remarks text,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (assistance_id),
  KEY idx_resident (resident_id),
  KEY idx_date (assistance_date),
  FOREIGN KEY (resident_id) REFERENCES residents(resident_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: activity_log
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_log (
  log_id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11),
  activity varchar(255) NOT NULL,
  action_type enum('CREATE','UPDATE','DELETE','DOWNLOAD','VIEW','LOGIN','LOGOUT') DEFAULT 'VIEW',
  table_name varchar(100),
  record_id int(11),
  timestamp timestamp DEFAULT CURRENT_TIMESTAMP,
  ip_address varchar(50),
  PRIMARY KEY (log_id),
  KEY idx_user (user_id),
  KEY idx_timestamp (timestamp),
  KEY idx_action (action_type),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: backup_history
-- =====================================================
CREATE TABLE IF NOT EXISTS backup_history (
  backup_id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11),
  file_location varchar(255) NOT NULL,
  file_size bigint(20),
  remarks text,
  backup_date timestamp DEFAULT CURRENT_TIMESTAMP,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (backup_id),
  KEY idx_user (user_id),
  KEY idx_date (backup_date),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Indexes for better query performance
-- =====================================================
CREATE INDEX idx_activity_log_user_date ON activity_log(user_id, timestamp DESC);
CREATE INDEX idx_assistance_records_resident_date ON assistance_records(resident_id, assistance_date DESC);
CREATE INDEX idx_backup_history_date ON backup_history(backup_date DESC);
