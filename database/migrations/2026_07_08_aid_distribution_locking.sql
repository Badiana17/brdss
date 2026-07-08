-- ============================================================
-- Migration: Aid Distribution Locking Feature
-- Date: 2026-07-08
-- Idempotent: safe to run multiple times
-- Target: MariaDB 10.4+ / MySQL 5.7+
-- ============================================================

-- 1) Add locking columns if they don't exist
-- MariaDB supports IF NOT EXISTS on ALTER TABLE ADD COLUMN

ALTER TABLE `aid_distribution`
  ADD COLUMN IF NOT EXISTS `is_locked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `created_at`;

ALTER TABLE `aid_distribution`
  ADD COLUMN IF NOT EXISTS `locked_by` INT(11) DEFAULT NULL AFTER `is_locked`;

ALTER TABLE `aid_distribution`
  ADD COLUMN IF NOT EXISTS `locked_at` DATETIME DEFAULT NULL AFTER `locked_by`;

ALTER TABLE `aid_distribution`
  ADD COLUMN IF NOT EXISTS `finalized_at` DATETIME DEFAULT NULL AFTER `locked_at`;

-- 2) Standardize beneficiary naming: ensure "Senior" is used consistently
--    (no "Senior Citizen" variants in aid_distribution or aid_types)
UPDATE `aid_distribution`
  SET `beneficiary_type` = 'Senior'
  WHERE `beneficiary_type` = 'Senior Citizen';

UPDATE `aid_types`
  SET `beneficiary_category` = 'Senior'
  WHERE `beneficiary_category` = 'Senior Citizen';

-- 3) Add FK on locked_by if not already present
--    Drop-and-recreate pattern to ensure correctness
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'aid_distribution'
    AND CONSTRAINT_NAME = 'fk_aid_distribution_locked_by'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

-- Only add if missing (MariaDB doesn't support IF NOT EXISTS on constraints)
-- We use a prepared statement workaround
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE `aid_distribution` ADD CONSTRAINT `fk_aid_distribution_locked_by` FOREIGN KEY (`locked_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) Remove duplicate FK if it exists (e.g. fk_aid_distribution_aid_type_2026)
SET @dup_fk = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'aid_distribution'
    AND CONSTRAINT_NAME = 'fk_aid_distribution_aid_type_2026'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @drop_sql = IF(@dup_fk > 0,
  'ALTER TABLE `aid_distribution` DROP FOREIGN KEY `fk_aid_distribution_aid_type_2026`',
  'SELECT 1'
);
PREPARE stmt2 FROM @drop_sql;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 5) Ensure indexes exist (ADD INDEX IF NOT EXISTS — MariaDB 10.5+)
--    Using safe pattern: check then add

ALTER TABLE `aid_distribution`
  ADD INDEX IF NOT EXISTS `idx_aid_distribution_aid` (`aid_type_id`);

ALTER TABLE `aid_distribution`
  ADD INDEX IF NOT EXISTS `idx_aid_distribution_beneficiary` (`beneficiary_type`, `beneficiary_id`);

ALTER TABLE `aid_distribution`
  ADD INDEX IF NOT EXISTS `idx_aid_distribution_status` (`status`);

ALTER TABLE `aid_distribution`
  ADD INDEX IF NOT EXISTS `idx_aid_distribution_date` (`distributed_at`);

ALTER TABLE `aid_distribution`
  ADD INDEX IF NOT EXISTS `idx_aid_distribution_locked` (`is_locked`);

ALTER TABLE `aid_distribution`
  ADD INDEX IF NOT EXISTS `idx_aid_distribution_locked_by` (`locked_by`);

-- Done. All changes are idempotent.
