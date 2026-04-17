

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `table_affected` varchar(80) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `fk_log_user` (`user_id`),
  KEY `idx_log_time` (`timestamp`),
  KEY `idx_log_table` (`table_affected`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `aid_distribution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid_type_id` int(11) NOT NULL,
  `beneficiary_type` enum('Resident','Student','Senior','PWD') NOT NULL,
  `beneficiary_id` int(11) NOT NULL,
  `distributed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Received','Cancelled') NOT NULL DEFAULT 'Pending',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aid_distribution_aid` (`aid_type_id`),
  KEY `idx_aid_distribution_beneficiary` (`beneficiary_type`,`beneficiary_id`),
  KEY `idx_aid_distribution_status` (`status`),
  KEY `idx_aid_distribution_date` (`distributed_at`),
  CONSTRAINT `fk_aid_distribution_aid_type` FOREIGN KEY (`aid_type_id`) REFERENCES `aid_types` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_aid_distribution_aid_type_2026` FOREIGN KEY (`aid_type_id`) REFERENCES `aid_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('1', '1', 'Senior', '1', '2026-03-01 15:46:02', 'Received', '', '2026-03-01 15:46:02');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('2', '1', 'Senior', '1', '2026-03-01 15:46:16', 'Received', '', '2026-03-01 15:46:16');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('3', '1', 'Senior', '5', '2026-03-01 15:46:16', 'Received', '', '2026-03-01 15:46:16');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('8', '1', 'Student', '8', '2026-03-01 15:55:36', 'Received', '', '2026-03-01 15:55:36');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('9', '1', 'Student', '6', '2026-03-01 15:55:36', 'Received', '', '2026-03-01 15:55:36');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('10', '1', 'Student', '2', '2026-03-01 15:55:36', 'Received', '', '2026-03-01 15:55:36');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('11', '1', 'Student', '8', '2026-03-01 15:56:37', 'Received', '', '2026-03-01 15:56:37');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('12', '1', 'Student', '6', '2026-03-01 15:56:37', 'Received', '', '2026-03-01 15:56:37');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('13', '1', 'Student', '2', '2026-03-01 15:56:37', 'Received', '', '2026-03-01 15:56:37');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('16', '2', 'PWD', '3', '2026-03-01 15:57:10', 'Received', '', '2026-03-01 15:57:10');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('17', '2', 'PWD', '9', '2026-03-01 15:57:10', 'Received', '', '2026-03-01 15:57:10');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('18', '1', 'Student', '8', '2026-03-01 15:57:42', 'Received', '', '2026-03-01 15:57:42');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('19', '1', 'Student', '6', '2026-03-01 15:57:42', 'Received', '', '2026-03-01 15:57:42');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('20', '1', 'Student', '2', '2026-03-01 15:57:42', 'Received', '', '2026-03-01 15:57:42');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('24', '1', 'Resident', '8', '2026-03-02 13:17:03', 'Received', '', '2026-03-02 13:17:03');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('25', '1', 'Resident', '2', '2026-03-02 13:17:03', 'Received', '', '2026-03-02 13:17:03');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('26', '5', 'Resident', '4', '2005-04-07 20:11:50', 'Received', '', '2026-04-07 20:11:50');
INSERT INTO `aid_distribution` (`id`, `aid_type_id`, `beneficiary_type`, `beneficiary_id`, `distributed_at`, `status`, `remarks`, `created_at`) VALUES ('27', '5', 'Resident', '7', '2026-04-07 20:11:50', 'Received', '', '2026-04-07 20:11:50');


CREATE TABLE `aid_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid_name` varchar(120) NOT NULL,
  `beneficiary_category` enum('Resident','Student','Senior','PWD') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aid_types_category` (`beneficiary_category`),
  KEY `idx_aid_types_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `aid_types` (`id`, `aid_name`, `beneficiary_category`, `description`, `is_active`, `created_at`) VALUES ('1', 'School Supplies 2026', 'Student', 'Notebooks, bags, pencils', '1', '2026-03-01 15:05:20');
INSERT INTO `aid_types` (`id`, `aid_name`, `beneficiary_category`, `description`, `is_active`, `created_at`) VALUES ('2', 'Senior Cash Assistance Q1', 'Senior', 'Quarter 1 cash aid', '1', '2026-03-01 15:05:20');
INSERT INTO `aid_types` (`id`, `aid_name`, `beneficiary_category`, `description`, `is_active`, `created_at`) VALUES ('3', 'PWD Medical Kit', 'PWD', 'Basic medical supplies', '1', '2026-03-01 15:05:20');
INSERT INTO `aid_types` (`id`, `aid_name`, `beneficiary_category`, `description`, `is_active`, `created_at`) VALUES ('5', 'Relief Goods', 'Resident', 'Canned Goods, Rice, and etc.', '1', '2026-04-07 20:11:37');


CREATE TABLE `backup_history` (
  `backup_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(180) NOT NULL,
  `file_location` varchar(255) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `backup_date` datetime NOT NULL DEFAULT current_timestamp(),
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`backup_id`),
  KEY `fk_backup_user` (`user_id`),
  KEY `idx_backup_date` (`backup_date`),
  CONSTRAINT `fk_backup_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `persons_with_disabilities` (
  `pwd_id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `disability_type` varchar(80) DEFAULT NULL,
  `pwd_id_no` varchar(60) DEFAULT NULL,
  `guardian_name` varchar(120) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `date_validated` date DEFAULT NULL,
  PRIMARY KEY (`pwd_id`),
  UNIQUE KEY `resident_id` (`resident_id`),
  KEY `fk_pwd_created_by` (`created_by`),
  KEY `fk_pwd_validated_by` (`validated_by`),
  CONSTRAINT `fk_pwd_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pwd_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pwd_validated_by` FOREIGN KEY (`validated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `residents` (
  `resident_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `suffix` varchar(15) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `barangay` varchar(80) DEFAULT NULL,
  `zone` varchar(30) DEFAULT NULL,
  `contact_no` varchar(30) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Divorced','Other') DEFAULT NULL,
  `is_voter` tinyint(1) NOT NULL DEFAULT 0,
  `beneficiary_category` enum('Student','Senior Citizen','PWD','Household Head','None') NOT NULL DEFAULT 'None',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`resident_id`),
  KEY `idx_residents_lastname` (`last_name`),
  KEY `idx_residents_beneficiary_category` (`beneficiary_category`),
  KEY `idx_residents_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `address`, `barangay`, `zone`, `contact_no`, `gender`, `civil_status`, `is_voter`, `beneficiary_category`, `status`, `created_at`, `deleted_at`) VALUES ('1', 'Juan', 'Cruz', 'Dela Cruz', 'Jr.', '1987-12-05', '37', 'Pandakaan Manila', '842', '92', '09171234567', 'Male', 'Married', '1', 'Senior Citizen', 'Active', '2026-02-26 16:50:47', NULL);
INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `address`, `barangay`, `zone`, `contact_no`, `gender`, `civil_status`, `is_voter`, `beneficiary_category`, `status`, `created_at`, `deleted_at`) VALUES ('2', 'Maria', 'Santos', 'Reyes', NULL, '2006-03-19', '19', 'Blk 3 Lot 2', '842', '92', '09181234567', 'Female', 'Single', '1', 'Student', 'Active', '2026-02-26 16:50:47', NULL);
INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `address`, `barangay`, `zone`, `contact_no`, `gender`, `civil_status`, `is_voter`, `beneficiary_category`, `status`, `created_at`, `deleted_at`) VALUES ('3', 'Jose', 'M.', 'Bautista', '', '1995-08-10', '30', 'Sitio Riverside', '842', '91', '09201234567', 'Male', 'Single', '0', 'PWD', 'Active', '2026-02-26 16:50:47', NULL);
INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `address`, `barangay`, `zone`, `contact_no`, `gender`, `civil_status`, `is_voter`, `beneficiary_category`, `status`, `created_at`, `deleted_at`) VALUES ('4', 'Ana', 'L.', 'Garcia', NULL, '1970-11-22', '55', 'Phase 2 Area 4', '842', '90', '09991234567', 'Female', 'Married', '1', 'Household Head', 'Active', '2026-02-26 16:50:47', NULL);
INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `address`, `barangay`, `zone`, `contact_no`, `gender`, `civil_status`, `is_voter`, `beneficiary_category`, `status`, `created_at`, `deleted_at`) VALUES ('5', 'Pedro', NULL, 'Lopez', 'Sr.', '1962-01-14', '63', 'Purok 5', '842', '90', '09061234567', 'Male', 'Widowed', '1', 'Senior Citizen', 'Active', '2026-02-26 16:50:47', NULL);
INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `address`, `barangay`, `zone`, `contact_no`, `gender`, `civil_status`, `is_voter`, `beneficiary_category`, `status`, `created_at`, `deleted_at`) VALUES ('6', 'Liza', 'A.', 'Flores', NULL, '2004-06-02', '21', 'Purok 1', '842', '91', '09191112222', 'Female', 'Single', '1', 'Student', 'Active', '2026-02-26 16:50:47', NULL);
INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `address`, `barangay`, `zone`, `contact_no`, `gender`, `civil_status`, `is_voter`, `beneficiary_category`, `status`, `created_at`, `deleted_at`) VALUES ('7', 'Mark', 'B.', 'Mendoza', NULL, '1990-09-09', '35', 'Zone 2 Main Rd', '842', '93', '09170001111', 'Male', 'Married', '1', 'None', 'Active', '2026-02-26 16:50:47', NULL);
INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `address`, `barangay`, `zone`, `contact_no`, `gender`, `civil_status`, `is_voter`, `beneficiary_category`, `status`, `created_at`, `deleted_at`) VALUES ('8', 'Jefferson', 'Celorico', 'Amboboyogjjj', '', '2005-01-26', '21', '12 fatima', 'Payatas A.', 'black slash', '09991837133', 'Male', 'Single', '1', 'Student', 'Active', '2026-03-01 14:00:32', NULL);
INSERT INTO `residents` (`resident_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthday`, `age`, `address`, `barangay`, `zone`, `contact_no`, `gender`, `civil_status`, `is_voter`, `beneficiary_category`, `status`, `created_at`, `deleted_at`) VALUES ('9', 'Ezkiel', 'B.', 'Macairan', '', '2004-03-01', '22', '12 fatima', 'Payatas A.', 'black slash', '09991837133', 'Male', 'Married', '0', 'PWD', 'Active', '2026-03-01 14:01:24', '2026-03-01 14:18:27');


CREATE TABLE `senior_citizens` (
  `senior_id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `osca_id_no` varchar(60) DEFAULT NULL,
  `osca_issued_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`senior_id`),
  UNIQUE KEY `resident_id` (`resident_id`),
  CONSTRAINT `fk_senior_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `grade_level` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `resident_id` (`resident_id`),
  CONSTRAINT `fk_student_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `role` enum('super_admin','admin_staff') NOT NULL DEFAULT 'admin_staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `role`, `is_active`, `last_login`, `locked_until`, `created_at`, `updated_at`) VALUES ('2', 'superadmin', '$2y$10$lXvpOcpAVQrTsdmQS8GI4eoLTEwbbnpPvOpuWMmw8oFSgQBEe0fRa', 'System Super Admin', 'super_admin', '1', NULL, NULL, '2026-02-26 16:33:01', NULL);
