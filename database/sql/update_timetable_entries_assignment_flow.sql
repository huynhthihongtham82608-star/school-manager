-- Update timetable_entries to use teaching assignments as the source of subject/teacher.
-- Existing subject_id and teacher_id are kept for compatibility and are backfilled from assignment on save.

ALTER TABLE `timetable_entries`
    ADD COLUMN IF NOT EXISTS `assignment_id` VARCHAR(50) NULL AFTER `timetable_id`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `note`,
    ADD COLUMN IF NOT EXISTS `archived_at` TIMESTAMP NULL AFTER `status`;

UPDATE `timetable_entries` te
JOIN `timetables` tt ON tt.id = te.timetable_id
JOIN `teaching_assignments` ta
    ON ta.school_year_id = tt.school_year_id
    AND ta.semester_id = tt.semester_id
    AND ta.class_id = tt.class_id
    AND ta.subject_id = te.subject_id
    AND ta.teacher_id = te.teacher_id
    AND ta.status = 'active'
SET te.assignment_id = ta.id,
    te.status = COALESCE(NULLIF(te.status, ''), 'active')
WHERE te.assignment_id IS NULL;

CREATE INDEX `timetable_entries_assignment_id_index` ON `timetable_entries` (`assignment_id`);
CREATE INDEX `timetable_entries_status_index` ON `timetable_entries` (`status`);
