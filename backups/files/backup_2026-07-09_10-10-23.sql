
-- Table structure for `activity_log`
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `activity_log`
INSERT INTO `activity_log` VALUES("1","2","PRINT","residents","","Printed residents report (9 records). Filters: None","127.0.0.1","2026-07-08 21:52:29");
INSERT INTO `activity_log` VALUES("2","2","CREATE","aid_distribution","43","Created aid distribution record: aid_type=1, beneficiary_type=Resident, remarks=","127.0.0.1","2026-07-08 22:01:49");
INSERT INTO `activity_log` VALUES("3","2","CREATE","aid_distribution","44","Created aid distribution record: aid_type=1, beneficiary_type=Resident, remarks=","127.0.0.1","2026-07-08 22:03:46");
INSERT INTO `activity_log` VALUES("4","2","LOCK","aid_distribution","44","Locked aid distribution record #44","127.0.0.1","2026-07-08 23:02:27");
INSERT INTO `activity_log` VALUES("5","2","PRINT","aid_distribution","","Printed grouped aid distribution report (10 beneficiaries). Filters: Type: Resident","127.0.0.1","2026-07-08 23:04:03");
INSERT INTO `activity_log` VALUES("6","3","PRINT","aid_distribution","","Printed grouped aid distribution report (10 beneficiaries). Filters: Type: Resident","::1","2026-07-09 14:53:11");
INSERT INTO `activity_log` VALUES("7","3","PRINT","aid_distribution","","Printed grouped aid distribution report (10 beneficiaries). Filters: Type: Resident","::1","2026-07-09 14:54:21");
INSERT INTO `activity_log` VALUES("8","3","LOCK","aid_distribution","27","Locked aid distribution record #27","::1","2026-07-09 14:54:45");
INSERT INTO `activity_log` VALUES("9","2","UNLOCK","aid_distribution","27","Unlocked aid distribution record #27","::1","2026-07-09 14:55:58");


-- Table structure for `aid_distribution`
CREATE TABLE `aid_distribution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid_type_id` int(11) NOT NULL,
  `beneficiary_type` enum('Resident','Student','Senior','PWD') NOT NULL,
  `beneficiary_id` int(11) NOT NULL,
  `distributed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Received','Cancelled') NOT NULL DEFAULT 'Pending',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `locked_by` int(11) DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aid_distribution_aid` (`aid_type_id`),
  KEY `idx_aid_distribution_beneficiary` (`beneficiary_type`,`beneficiary_id`),
  KEY `idx_aid_distribution_status` (`status`),
  KEY `idx_aid_distribution_date` (`distributed_at`),
  KEY `idx_aid_distribution_locked` (`is_locked`),
  KEY `idx_aid_distribution_locked_by` (`locked_by`),
  CONSTRAINT `fk_aid_distribution_aid_type` FOREIGN KEY (`aid_type_id`) REFERENCES `aid_types` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_aid_distribution_locked_by` FOREIGN KEY (`locked_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `aid_distribution`
INSERT INTO `aid_distribution` VALUES("1","1","Senior","1","2026-03-01 15:46:02","Received","","2026-03-01 23:46:02","0","","","");
INSERT INTO `aid_distribution` VALUES("2","1","Senior","1","2026-03-01 15:46:16","Received","","2026-03-01 23:46:16","0","","","");
INSERT INTO `aid_distribution` VALUES("3","1","Senior","5","2026-03-01 15:46:16","Received","","2026-03-01 23:46:16","0","","","");
INSERT INTO `aid_distribution` VALUES("8","1","Student","8","2026-03-01 15:55:36","Received","","2026-03-01 23:55:36","0","","","");
INSERT INTO `aid_distribution` VALUES("9","1","Student","6","2026-03-01 15:55:36","Received","","2026-03-01 23:55:36","0","","","");
INSERT INTO `aid_distribution` VALUES("10","1","Student","2","2026-03-01 15:55:36","Received","","2026-03-01 23:55:36","0","","","");
INSERT INTO `aid_distribution` VALUES("11","1","Student","8","2026-03-01 15:56:37","Received","","2026-03-01 23:56:37","0","","","");
INSERT INTO `aid_distribution` VALUES("12","1","Student","6","2026-03-01 15:56:37","Received","","2026-03-01 23:56:37","0","","","");
INSERT INTO `aid_distribution` VALUES("13","1","Student","2","2026-03-01 15:56:37","Received","","2026-03-01 23:56:37","0","","","");
INSERT INTO `aid_distribution` VALUES("16","2","PWD","3","2026-03-01 15:57:10","Received","","2026-03-01 23:57:10","0","","","");
INSERT INTO `aid_distribution` VALUES("17","2","PWD","9","2026-03-01 15:57:10","Received","","2026-03-01 23:57:10","0","","","");
INSERT INTO `aid_distribution` VALUES("18","1","Student","8","2026-03-01 15:57:42","Received","","2026-03-01 23:57:42","0","","","");
INSERT INTO `aid_distribution` VALUES("19","1","Student","6","2026-03-01 15:57:42","Received","","2026-03-01 23:57:42","0","","","");
INSERT INTO `aid_distribution` VALUES("20","1","Student","2","2026-03-01 15:57:42","Received","","2026-03-01 23:57:42","0","","","");
INSERT INTO `aid_distribution` VALUES("24","1","Resident","8","2026-03-02 13:17:03","Received","","2026-03-02 21:17:03","0","","","");
INSERT INTO `aid_distribution` VALUES("25","1","Resident","2","2026-03-02 13:17:03","Received","","2026-03-02 21:17:03","0","","","");
INSERT INTO `aid_distribution` VALUES("26","5","Resident","4","2026-04-07 20:11:50","Received","","2026-04-08 04:11:50","0","","","");
INSERT INTO `aid_distribution` VALUES("27","5","Resident","7","2026-04-07 20:11:50","Received","","2026-04-08 04:11:50","0","","","");
INSERT INTO `aid_distribution` VALUES("28","6","Student","8","2026-04-08 09:44:07","Received","","2026-04-08 17:44:07","0","","","");
INSERT INTO `aid_distribution` VALUES("29","6","Student","6","2026-04-08 09:44:07","Received","","2026-04-08 17:44:07","0","","","");
INSERT INTO `aid_distribution` VALUES("30","6","Student","2","2026-04-08 09:44:07","Received","","2026-04-08 17:44:07","0","","","");
INSERT INTO `aid_distribution` VALUES("31","6","Student","8","2026-04-08 09:44:18","Received","","2026-04-08 17:44:18","0","","","");
INSERT INTO `aid_distribution` VALUES("32","6","Student","6","2026-04-08 09:44:18","Received","","2026-04-08 17:44:18","0","","","");
INSERT INTO `aid_distribution` VALUES("33","6","Student","2","2026-04-08 09:44:18","Received","","2026-04-08 17:44:18","0","","","");
INSERT INTO `aid_distribution` VALUES("34","6","Resident","8","2026-04-08 09:45:49","Received","","2026-04-08 17:45:49","0","","","");
INSERT INTO `aid_distribution` VALUES("35","6","Resident","3","2026-04-08 09:45:49","Received","","2026-04-08 17:45:49","0","","","");
INSERT INTO `aid_distribution` VALUES("36","6","Resident","1","2026-04-08 09:45:49","Received","","2026-04-08 17:45:49","0","","","");
INSERT INTO `aid_distribution` VALUES("37","6","Resident","6","2026-04-08 09:45:50","Received","","2026-04-08 17:45:50","0","","","");
INSERT INTO `aid_distribution` VALUES("38","6","Resident","4","2026-04-08 09:45:51","Received","","2026-04-08 17:45:51","0","","","");
INSERT INTO `aid_distribution` VALUES("39","6","Resident","5","2026-04-08 09:45:51","Received","","2026-04-08 17:45:51","0","","","");
INSERT INTO `aid_distribution` VALUES("40","6","Resident","9","2026-04-08 09:45:51","Received","","2026-04-08 17:45:51","0","","","");
INSERT INTO `aid_distribution` VALUES("41","6","Resident","7","2026-04-08 09:45:51","Received","","2026-04-08 17:45:51","0","","","");
INSERT INTO `aid_distribution` VALUES("42","6","Resident","2","2026-04-08 09:45:51","Received","","2026-04-08 17:45:51","0","","","");
INSERT INTO `aid_distribution` VALUES("43","1","Resident","10","2026-07-08 22:01:49","Received","","2026-07-08 22:01:49","0","","","");
INSERT INTO `aid_distribution` VALUES("44","1","Resident","10","2026-07-08 22:03:46","Received","","2026-07-08 22:03:46","1","2","2026-07-08 17:02:27","2026-07-08 17:02:27");


-- Table structure for `aid_types`
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `aid_types`
INSERT INTO `aid_types` VALUES("1","School Supplies 2026","Student","Notebooks, bags, pencils","1","2026-03-01 23:05:20");
INSERT INTO `aid_types` VALUES("2","Senior Cash Assistance Q1","Senior","Quarter 1 cash aid","1","2026-03-01 23:05:20");
INSERT INTO `aid_types` VALUES("3","PWD Medical Kit","PWD","Basic medical supplies","1","2026-03-01 23:05:20");
INSERT INTO `aid_types` VALUES("5","Relief Goods","Resident","Canned Goods, Rice, and etc.","1","2026-04-08 04:11:37");
INSERT INTO `aid_types` VALUES("6","Shoes","Student","Nike","1","2026-04-08 17:42:51");


-- Table structure for `backup_history`
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `backup_history`
INSERT INTO `backup_history` VALUES("5","2","backup_2026-04-08_09-25-22.sql","External","16363","2026-04-08 09:25:22","");
INSERT INTO `backup_history` VALUES("8","2","backup_2026-07-09_15-39-38.sql","Local","18654","2026-07-09 15:39:38","");
INSERT INTO `backup_history` VALUES("9","2","backup_2026-07-09_15-53-24.sql","Local","18747","2026-07-09 15:53:24","");
INSERT INTO `backup_history` VALUES("10","2","backup_2026-07-09_15-57-20.sql","Local","18841","2026-07-09 15:57:20","");


-- Table structure for `persons_with_disabilities`
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

-- Dumping data for table `persons_with_disabilities`


-- Table structure for `residents`
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
  `beneficiary_category` enum('Student','Senior','PWD','None') NOT NULL DEFAULT 'None',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`resident_id`),
  KEY `idx_residents_lastname` (`last_name`),
  KEY `idx_residents_beneficiary_category` (`beneficiary_category`),
  KEY `idx_residents_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `residents`
INSERT INTO `residents` VALUES("1","Juan","Cruz","Dela Cruz","Jr.","1987-12-05","37","Pandakaan Manila","842","92","09171234567","Male","Married","1","Senior","Active","2026-02-27 00:50:47","");
INSERT INTO `residents` VALUES("2","Maria","Santos","Reyes","","2006-03-19","19","Blk 3 Lot 2","842","92","09181234567","Female","Single","1","Student","Active","2026-02-27 00:50:47","");
INSERT INTO `residents` VALUES("3","Jose","M.","Bautista","","1995-08-10","30","Sitio Riverside","842","91","09201234567","Male","Single","0","PWD","Active","2026-02-27 00:50:47","");
INSERT INTO `residents` VALUES("4","Ana","L.","Garcia","","1970-11-22","55","Phase 2 Area 4","842","90","09991234567","Female","Married","1","Senior","Active","2026-02-27 00:50:47","");
INSERT INTO `residents` VALUES("5","Pedro","","Lopez","Sr.","1962-01-14","63","Purok 5","842","90","09061234567","Male","Widowed","1","Senior","Active","2026-02-27 00:50:47","");
INSERT INTO `residents` VALUES("6","Liza","A.","Flores","","2004-06-02","21","Purok 1","842","91","09191112222","Female","Single","1","Student","Active","2026-02-27 00:50:47","");
INSERT INTO `residents` VALUES("7","Mark","B.","Mendoza","","1990-09-09","35","Zone 2 Main Rd","842","93","09170001111","Male","Married","1","None","Active","2026-02-27 00:50:47","");
INSERT INTO `residents` VALUES("8","Jefferson","Celorico","Amboboyogjjj","","2005-01-26","21","12 fatima","Payatas A.","black slash","09991837133","Male","Single","1","Student","Active","2026-03-01 22:00:32","");
INSERT INTO `residents` VALUES("9","Ezkiel","B.","Macairan","","2004-03-01","22","12 fatima","Payatas A.","black slash","09991837133","Male","Married","0","PWD","Active","2026-03-01 22:01:24","");
INSERT INTO `residents` VALUES("10","John","Lyod D.","BADIANA","","2004-07-01","22","Bills St","Batasan Hills","1126","09527867823","Male","Separated","1","Student","Active","2026-07-08 22:00:54","");


-- Table structure for `senior_citizens`
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

-- Dumping data for table `senior_citizens`


-- Table structure for `students`
CREATE TABLE `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `grade_level` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `resident_id` (`resident_id`),
  CONSTRAINT `fk_student_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `students`


-- Table structure for `users`
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES("2","superadmin","$2y$10$du0qxHnlmNIJEobBKSOC0.31q6C2415QU7nNp.LodjgeKOSGbrY8a","System Super Admin","super_admin","1","","","2026-02-27 00:33:01","2026-07-08 19:41:05");
INSERT INTO `users` VALUES("3","macairan","$2y$10$JNzfVc/SplUyUL0ihTExlefz7pqPVIqcjQcAlj/YJ7wU1fP8aEwDC","Kiel Mairan","admin_staff","1","","","2026-04-16 02:42:25","2026-07-09 14:51:48");

