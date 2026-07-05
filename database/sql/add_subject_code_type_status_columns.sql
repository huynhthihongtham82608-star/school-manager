-- Add only the columns required by the new Subject management flow.
-- This script keeps existing subject IDs and all subject_id references intact.

ALTER TABLE `subjects`
    ADD COLUMN IF NOT EXISTS `code` VARCHAR(50) NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `type` VARCHAR(20) NOT NULL DEFAULT 'required' AFTER `credit`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `type`;

UPDATE `subjects`
SET
    `code` = CONCAT('MH_', LEFT(REPLACE(CAST(`id` AS CHAR), '-', ''), 20)),
    `type` = COALESCE(NULLIF(`type`, ''), 'required'),
    `status` = COALESCE(NULLIF(`status`, ''), 'active')
WHERE `code` IS NULL OR `code` = '';

CREATE UNIQUE INDEX `subjects_code_unique` ON `subjects` (`code`);
