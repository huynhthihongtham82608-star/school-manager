-- Bổ sung liên kết Học kỳ và trạng thái nghiệp vụ cho module Lớp học.
-- Chỉ chạy các lệnh ADD COLUMN nếu cột chưa tồn tại trong bảng `classes`.

ALTER TABLE `classes`
    ADD COLUMN `semester_id` VARCHAR(50) NULL AFTER `school_year_id`,
    ADD INDEX `classes_semester_id_index` (`semester_id`);

ALTER TABLE `classes`
    ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'draft' AFTER `capacity`,
    ADD INDEX `classes_status_index` (`status`);

ALTER TABLE `classes`
    ADD COLUMN `locked_at` TIMESTAMP NULL AFTER `status`;

ALTER TABLE `classes`
    ADD COLUMN `archived_at` TIMESTAMP NULL AFTER `locked_at`;

UPDATE `classes` c
JOIN (
    SELECT s.school_year_id, MIN(s.id) AS semester_id
    FROM `semesters` s
    GROUP BY s.school_year_id
) first_semester ON first_semester.school_year_id = c.school_year_id
SET c.semester_id = first_semester.semester_id
WHERE c.semester_id IS NULL;
