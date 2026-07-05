-- Update teaching_assignments for semester-based assignment management.
-- Keeps existing IDs and existing teacher/class/subject/year references.

ALTER TABLE `teaching_assignments`
    ADD COLUMN IF NOT EXISTS `semester_id` VARCHAR(50) NULL AFTER `school_year_id`,
    ADD COLUMN IF NOT EXISTS `role` VARCHAR(50) NOT NULL DEFAULT 'primary' AFTER `semester_id`,
    ADD COLUMN IF NOT EXISTS `custom_role` VARCHAR(255) NULL AFTER `role`,
    ADD COLUMN IF NOT EXISTS `note` TEXT NULL AFTER `custom_role`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `note`,
    ADD COLUMN IF NOT EXISTS `archived_at` TIMESTAMP NULL AFTER `status`;

UPDATE `teaching_assignments` ta
LEFT JOIN `semesters` s_active
    ON s_active.school_year_id = ta.school_year_id
    AND s_active.status = 'active'
LEFT JOIN `semesters` s_first
    ON s_first.id = (
        SELECT s2.id
        FROM `semesters` s2
        WHERE s2.school_year_id = ta.school_year_id
        ORDER BY s2.`order`, s2.`name`
        LIMIT 1
    )
SET ta.semester_id = COALESCE(s_active.id, s_first.id),
    ta.role = COALESCE(NULLIF(ta.role, ''), 'primary'),
    ta.status = COALESCE(NULLIF(ta.status, ''), 'active')
WHERE ta.semester_id IS NULL;

ALTER TABLE `teaching_assignments`
    DROP INDEX `teacher_class_subject_unique`;

CREATE UNIQUE INDEX `assignment_unique_with_role`
    ON `teaching_assignments` (`teacher_id`, `class_id`, `subject_id`, `school_year_id`, `semester_id`, `role`, `custom_role`);

CREATE INDEX `teaching_assignments_semester_id_index` ON `teaching_assignments` (`semester_id`);
CREATE INDEX `teaching_assignments_role_index` ON `teaching_assignments` (`role`);
CREATE INDEX `teaching_assignments_status_index` ON `teaching_assignments` (`status`);
