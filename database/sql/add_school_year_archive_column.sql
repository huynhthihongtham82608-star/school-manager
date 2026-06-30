-- Add archive status for school_years.
-- Import this file only if the column does not exist yet.

ALTER TABLE `school_years`
  ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_active`;
