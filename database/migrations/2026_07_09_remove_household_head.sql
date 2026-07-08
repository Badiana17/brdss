-- ============================================================
-- Migration: Remove Household Head Category
-- Date: 2026-07-09
-- ============================================================

-- 1) Update existing residents to 'None'
UPDATE `residents`
SET `beneficiary_category` = 'None'
WHERE `beneficiary_category` = 'Household Head';

-- 2) Alter table to remove 'Household Head' from ENUM
ALTER TABLE `residents`
MODIFY COLUMN `beneficiary_category` enum('Student','Senior','PWD','None') NOT NULL DEFAULT 'None';
