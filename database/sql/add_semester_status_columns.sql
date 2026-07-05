-- Bổ sung trạng thái nghiệp vụ cho module Học kỳ.
-- Chỉ chạy các lệnh tương ứng nếu cột chưa tồn tại trong bảng `semesters`.

ALTER TABLE `semesters`
    ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'inactive' AFTER `is_score_input_open`;

ALTER TABLE `semesters`
    ADD COLUMN `locked_at` TIMESTAMP NULL AFTER `status`;

ALTER TABLE `semesters`
    ADD COLUMN `archived_at` TIMESTAMP NULL AFTER `locked_at`;

UPDATE `semesters`
SET `status` = 'inactive'
WHERE `status` IS NULL OR `status` = '';
