
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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `activity_log` VALUES("10","3","PRINT","aid_distribution","","Printed grouped aid distribution report (0 beneficiaries). Filters: Type: Student","::1","2026-07-09 16:45:01");
INSERT INTO `activity_log` VALUES("11","3","PRINT","residents","","Printed residents report (10 records). Filters: None","::1","2026-07-09 16:45:11");
INSERT INTO `activity_log` VALUES("12","3","PRINT","aid_distribution","","Printed grouped aid distribution report (10 beneficiaries). Filters: Type: Resident","::1","2026-07-09 16:49:53");
INSERT INTO `activity_log` VALUES("13","2","EXPORT","aid_distribution","","Exported 35 aid distribution records as CSV.","::1","2026-07-10 17:11:53");
INSERT INTO `activity_log` VALUES("14","2","PRINT","aid_distribution","","Printed grouped aid distribution report (10 beneficiaries). Filters: Type: Resident","::1","2026-07-10 17:12:17");
INSERT INTO `activity_log` VALUES("15","2","LOCK","aid_distribution","43","Locked aid distribution record #43","::1","2026-07-10 17:12:30");
INSERT INTO `activity_log` VALUES("16","2","UNLOCK","aid_distribution","43","Unlocked aid distribution record #43","::1","2026-07-10 17:12:38");
INSERT INTO `activity_log` VALUES("17","2","PRINT","aid_distribution","","Printed grouped aid distribution report (10 beneficiaries). Filters: Type: Resident","::1","2026-07-10 17:12:50");
INSERT INTO `activity_log` VALUES("18","2","PRINT","aid_distribution","","Printed grouped aid distribution report (10 beneficiaries). Filters: Type: Resident","::1","2026-07-10 17:16:01");
INSERT INTO `activity_log` VALUES("19","2","PRINT","aid_distribution","","Printed grouped aid distribution report (10 beneficiaries). Filters: Type: Resident","::1","2026-07-10 17:33:40");
INSERT INTO `activity_log` VALUES("20","2","DELETE","residents","1","Soft-deleted resident record for ID: 1","::1","2026-07-19 16:14:26");
INSERT INTO `activity_log` VALUES("21","2","UPDATE","residents","10","Updated resident ID: 10 (Category: Student)","::1","2026-07-19 17:34:23");
INSERT INTO `activity_log` VALUES("22","2","PRINT","residents","","Printed residents report (0 records). Filters: None","::1","2026-07-19 17:44:36");
INSERT INTO `activity_log` VALUES("23","2","CREATE","residents","11","Added new resident: John BADIANA (Category: Student)","::1","2026-07-19 17:46:21");
INSERT INTO `activity_log` VALUES("24","2","CREATE","residents","12","Added new resident: Jefferson Amboboyog (Category: Student)","::1","2026-07-19 17:59:47");
INSERT INTO `activity_log` VALUES("25","2","UPDATE","residents","1","Updated resident ID: 1 (Category: Student)","::1","2026-07-19 17:59:56");
INSERT INTO `activity_log` VALUES("26","2","CREATE","residents","13","Added new resident: John Ezekiel Macairan (Category: Student)","::1","2026-07-19 18:01:09");
INSERT INTO `activity_log` VALUES("27","2","CREATE","residents","14","Added new resident: Bryan Oyao (Category: Student)","::1","2026-07-19 18:02:31");
INSERT INTO `activity_log` VALUES("28","2","CREATE","residents","15","Added new resident: Juan Dela Cruz (Category: None)","::1","2026-07-19 18:03:43");
INSERT INTO `activity_log` VALUES("29","2","CREATE","residents","16","Added new resident: Marites Gonzales (Category: Senior)","::1","2026-07-19 18:06:01");
INSERT INTO `activity_log` VALUES("30","2","CREATE","residents","17","Added new resident: Jeffrey Badiana (Category: PWD)","::1","2026-07-19 18:08:32");
INSERT INTO `activity_log` VALUES("31","2","CREATE","aid_distribution","45","Created aid distribution record: aid_type=5, beneficiary_type=PWD, remarks=","::1","2026-07-19 18:09:09");
INSERT INTO `activity_log` VALUES("32","2","UPDATE","aid_distribution","45","Updated status on record #45: \'Received\' → \'Pending\'","::1","2026-07-19 18:09:23");
INSERT INTO `activity_log` VALUES("33","2","UPDATE","aid_distribution","45","Updated status on record #45: \'Pending\' → \'Cancelled\'","::1","2026-07-19 18:29:13");
INSERT INTO `activity_log` VALUES("34","2","UPDATE","aid_distribution","1","Updated status on record #1: \'Cancelled\' → \'Pending\'","::1","2026-07-19 18:30:31");
INSERT INTO `activity_log` VALUES("35","2","CREATE","aid_distribution","46","Created aid distribution record: aid_type=3, beneficiary_type=PWD, remarks=","::1","2026-07-19 18:30:56");
INSERT INTO `activity_log` VALUES("36","2","CREATE","aid_distribution","1","Created aid distribution record: aid_type=2, beneficiary_type=Senior, remarks=","::1","2026-07-19 18:32:37");
INSERT INTO `activity_log` VALUES("37","2","CREATE","aid_distribution","2","Created aid distribution record: aid_type=3, beneficiary_type=PWD, remarks=","::1","2026-07-19 18:33:01");
INSERT INTO `activity_log` VALUES("38","2","PRINT","aid_distribution","","Printed grouped aid distribution report (1 beneficiaries). Filters: None","::1","2026-07-19 18:33:32");
INSERT INTO `activity_log` VALUES("39","2","PRINT","aid_distribution","","Printed grouped aid distribution report (1 beneficiaries). Filters: Type: PWD","::1","2026-07-19 18:33:54");
INSERT INTO `activity_log` VALUES("40","2","CREATE","aid_distribution","3","Created aid distribution record: aid_type=3, beneficiary_type=PWD, remarks=","::1","2026-07-19 18:35:02");
INSERT INTO `activity_log` VALUES("41","2","PRINT","aid_distribution","","Printed grouped aid distribution report (1 beneficiaries). Filters: Type: PWD","::1","2026-07-19 18:35:11");
INSERT INTO `activity_log` VALUES("42","2","CREATE","aid_distribution","4","Created aid distribution record: aid_type=5, beneficiary_type=Resident, remarks=","::1","2026-07-19 18:44:43");
INSERT INTO `activity_log` VALUES("43","2","CREATE","aid_distribution","5","Created aid distribution record: aid_type=1, beneficiary_type=Student, remarks=","::1","2026-07-19 18:44:58");
INSERT INTO `activity_log` VALUES("44","2","CREATE","aid_distribution","6","Created aid distribution record: aid_type=1, beneficiary_type=Student, remarks=","::1","2026-07-19 18:44:58");
INSERT INTO `activity_log` VALUES("45","2","CREATE","aid_distribution","7","Created aid distribution record: aid_type=6, beneficiary_type=Student, remarks=","::1","2026-07-19 18:45:10");
INSERT INTO `activity_log` VALUES("46","2","CREATE","aid_distribution","8","Created aid distribution record: aid_type=6, beneficiary_type=Student, remarks=","::1","2026-07-19 18:45:10");
INSERT INTO `activity_log` VALUES("47","2","PRINT","aid_distribution","","Printed grouped aid distribution report (4 beneficiaries). Filters: Type: Student","::1","2026-07-19 18:45:35");
INSERT INTO `activity_log` VALUES("48","2","UPDATE","aid_distribution","8","Updated status on record #8: \'Pending\' → \'Received\'","::1","2026-07-19 18:45:52");
INSERT INTO `activity_log` VALUES("49","2","CREATE","aid_distribution","9","Created aid distribution record: aid_type=3, beneficiary_type=PWD, remarks=","::1","2026-07-19 19:06:42");
INSERT INTO `activity_log` VALUES("50","2","UPDATE","aid_distribution","9","Updated status on record #9: \'Pending\' → \'Received\'","::1","2026-07-19 19:07:26");
INSERT INTO `activity_log` VALUES("51","2","UPDATE","aid_distribution","1","Updated status on record #1: \'Pending\' → \'Received\'","::1","2026-07-19 20:06:33");
INSERT INTO `activity_log` VALUES("52","2","UPDATE","aid_distribution","4","Updated status on record #4: \'Pending\' → \'Received\'","::1","2026-07-19 20:06:49");
INSERT INTO `activity_log` VALUES("53","2","LOCK","aid_distribution","4","Locked aid distribution record #4","::1","2026-07-19 20:06:53");
INSERT INTO `activity_log` VALUES("54","2","UPDATE","aid_distribution","7","Updated status on record #7: \'Pending\' → \'Received\'","::1","2026-07-19 20:08:07");
INSERT INTO `activity_log` VALUES("55","2","UPDATE","aid_distribution","6","Updated status on record #6: \'Pending\' → \'Received\'","::1","2026-07-19 20:08:11");


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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `aid_distribution`
INSERT INTO `aid_distribution` VALUES("1","2","Senior","16","2026-07-19 18:32:37","Received","","2026-07-19 18:32:37","0","","","");
INSERT INTO `aid_distribution` VALUES("2","3","PWD","17","2026-07-19 18:33:01","Pending","","2026-07-19 18:33:01","0","","","");
INSERT INTO `aid_distribution` VALUES("3","3","PWD","17","2026-07-19 18:35:02","Pending","","2026-07-19 18:35:02","0","","","");
INSERT INTO `aid_distribution` VALUES("4","5","Resident","15","2026-07-19 18:44:43","Received","","2026-07-19 18:44:43","1","2","2026-07-19 14:06:53","2026-07-19 14:06:53");
INSERT INTO `aid_distribution` VALUES("5","1","Student","1","2026-07-19 18:44:58","Pending","","2026-07-19 18:44:58","0","","","");
INSERT INTO `aid_distribution` VALUES("6","1","Student","14","2026-07-19 18:44:58","Received","","2026-07-19 18:44:58","0","","","");
INSERT INTO `aid_distribution` VALUES("7","6","Student","12","2026-07-19 18:45:10","Received","","2026-07-19 18:45:10","0","","","");
INSERT INTO `aid_distribution` VALUES("8","6","Student","13","2026-07-19 18:45:10","Received","","2026-07-19 18:45:10","0","","","");
INSERT INTO `aid_distribution` VALUES("9","3","PWD","17","2026-07-19 19:06:42","Received","","2026-07-19 19:06:42","0","","","");


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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `backup_history`
INSERT INTO `backup_history` VALUES("5","2","backup_2026-04-08_09-25-22.sql","External","16363","2026-04-08 09:25:22","");
INSERT INTO `backup_history` VALUES("11","2","backup_2026-07-09_10-10-23.sql","External","17433","2026-07-09 16:10:23","");


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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `persons_with_disabilities`
INSERT INTO `persons_with_disabilities` VALUES("1","17","Postaxial Polydactyly","PWD-2143-3422","John Lyod Badiana","Nothing","2","","");


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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `residents`
INSERT INTO `residents` VALUES("11","John","Lyod D.","Badiana","","2004-07-17","22","1425 BILL ST","Batasan Hills","1126","09527867823","Male","Single","1","Student","Active","2026-07-19 17:46:21","");
INSERT INTO `residents` VALUES("12","Jefferson","C","Amboboyog","","2005-01-26","21","Payatas","Kalimutan ko","92","09124566943","Male","Married","1","Student","Active","2026-07-19 17:59:47","");
INSERT INTO `residents` VALUES("13","John Ezekiel","L","Macairan","","2004-08-25","21","Pandakaan Manila","842","92","09423832944","Male","Separated","1","Student","Active","2026-07-19 18:01:09","");
INSERT INTO `residents` VALUES("14","Bryan","R","Oyao","","2005-01-30","21","19 Mandaluyong","pinayahan","34","093438247338","Male","Single","1","Student","Active","2026-07-19 18:02:31","");
INSERT INTO `residents` VALUES("15","Juan","Vicente","Dela Cruz","Jr.","2000-01-13","26","Bills St","Batasan Hills","1126","095428523242","Male","Widowed","1","None","Active","2026-07-19 18:03:43","");
INSERT INTO `residents` VALUES("16","Marites","Ohabam","Gonzales","","1960-04-10","66","Ilang Ilang St","Payatas","83","095723857323","Female","Married","1","Senior","Active","2025-07-19 18:06:01","");
INSERT INTO `residents` VALUES("17","Jeffrey","Delera","Badiana","","2000-08-25","25","Bills St","Batasan Hills","1126","09517824351","Male","Single","1","PWD","Active","2026-07-19 18:08:32","");


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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `senior_citizens`
INSERT INTO `senior_citizens` VALUES("1","16","OSCA-242134-2343","2020-04-20","2026-07-19 18:06:01");


-- Table structure for `students`
CREATE TABLE `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `grade_level` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `resident_id` (`resident_id`),
  CONSTRAINT `fk_student_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `students`
INSERT INTO `students` VALUES("1","11","4th Year College","1");
INSERT INTO `students` VALUES("3","12","4th Year College","1");
INSERT INTO `students` VALUES("5","13","4th Year College","1");
INSERT INTO `students` VALUES("6","14","4th Year College","1");


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

