-- Database schema for THPT School Management
-- MySQL 5.7/8.0 compatible

CREATE DATABASE IF NOT EXISTS `school_manager` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `school_manager`;

-- Users & roles
CREATE TABLE `users` (
  `id` VARCHAR(50) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','staff','teacher','homeroom','student','parent') NOT NULL DEFAULT 'student',
  `teacher_id` VARCHAR(50) NULL,
  `student_id` VARCHAR(50) NULL,
  `parent_id` VARCHAR(50) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB;

CREATE TABLE `parents` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `email` VARCHAR(255) NULL,
  `address` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `parent_student` (
  `parent_id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `relation` VARCHAR(50) NULL, PRIMARY KEY (`parent_id`,`student_id`)
) ENGINE=InnoDB;

-- Core master data
CREATE TABLE `school_years` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `school_years_name_unique` (`name`)
) ENGINE=InnoDB;

CREATE TABLE `semesters` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `order` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `school_year_id` VARCHAR(50) NOT NULL,
  `is_score_input_open` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  KEY `semesters_school_year_idx` (`school_year_id`)
) ENGINE=InnoDB;

CREATE TABLE `subjects` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `credit` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `is_weighted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_name_unique` (`name`)
) ENGINE=InnoDB;

CREATE TABLE `teachers` (
  `id` VARCHAR(50) NOT NULL,
  `teacher_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `email` VARCHAR(255) NULL,
  `qualification` VARCHAR(255) NULL,
  `main_subject` VARCHAR(255) NULL,
  `is_homeroom` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `teachers_teacher_code_unique` (`teacher_code`)
) ENGINE=InnoDB;

CREATE TABLE `classes` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `grade_level` TINYINT UNSIGNED NOT NULL,
  `school_year_id` VARCHAR(50) NOT NULL,
  `homeroom_teacher_id` VARCHAR(50) NULL,
  `capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 45,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `classes_name_unique` (`name`),
  KEY `classes_school_year_idx` (`school_year_id`)
) ENGINE=InnoDB;

CREATE TABLE `students` (
  `id` VARCHAR(50) NOT NULL,
  `student_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `gender` ENUM('male','female','other') NULL,
  `dob` DATE NULL,
  `address` VARCHAR(255) NULL,
  `parent_phone` VARCHAR(50) NULL,
  `email` VARCHAR(255) NULL,
  `class_id` VARCHAR(50) NOT NULL,
  `school_year_id` VARCHAR(50) NOT NULL,
  `status` ENUM('studying','inactive','graduated') NOT NULL DEFAULT 'studying',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `students_student_code_unique` (`student_code`),
  KEY `students_class_idx` (`class_id`)
) ENGINE=InnoDB;

CREATE TABLE `student_transfers` (
  `id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `from_class_id` VARCHAR(50) NULL,
  `to_class_id` VARCHAR(50) NULL,
  `transfer_date` DATE NOT NULL,
  `note` VARCHAR(255) NULL, PRIMARY KEY (`id`),
  KEY `transfers_student_idx` (`student_id`)
) ENGINE=InnoDB;

CREATE TABLE `teaching_assignments` (
  `id` VARCHAR(50) NOT NULL,
  `teacher_id` VARCHAR(50) NOT NULL,
  `class_id` VARCHAR(50) NOT NULL,
  `subject_id` VARCHAR(50) NOT NULL,
  `school_year_id` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_class_subject_unique` (`teacher_id`,`class_id`,`subject_id`,`school_year_id`)
) ENGINE=InnoDB;

-- Score management
CREATE TABLE `score_headers` (
  `id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `subject_id` VARCHAR(50) NOT NULL,
  `semester_id` VARCHAR(50) NOT NULL,
  `school_year_id` VARCHAR(50) NOT NULL,
  `average` DECIMAL(5,2) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `student_subject_semester_unique` (`student_id`,`subject_id`,`semester_id`,`school_year_id`)
) ENGINE=InnoDB;

CREATE TABLE `score_details` (
  `id` VARCHAR(50) NOT NULL,
  `score_header_id` VARCHAR(50) NOT NULL,
  `type` ENUM('oral','quiz','test','midterm','final') NOT NULL,
  `value` DECIMAL(5,2) NOT NULL,
  `weight_group` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  KEY `score_details_header_idx` (`score_header_id`)
) ENGINE=InnoDB;

CREATE TABLE `conducts` (
  `id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `class_id` VARCHAR(50) NOT NULL,
  `semester_id` VARCHAR(50) NOT NULL,
  `school_year_id` VARCHAR(50) NOT NULL,
  `conduct_level` ENUM('excellent','good','average','weak') NULL,
  `comment` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `student_semester_conduct_unique` (`student_id`,`semester_id`,`school_year_id`)
) ENGINE=InnoDB;

CREATE TABLE `grade_windows` (
  `id` VARCHAR(50) NOT NULL,
  `class_id` VARCHAR(50) NOT NULL,
  `subject_id` VARCHAR(50) NOT NULL,
  `semester_id` VARCHAR(50) NOT NULL,
  `school_year_id` VARCHAR(50) NOT NULL,
  `is_open` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `grade_window_unique` (`class_id`,`subject_id`,`semester_id`,`school_year_id`)
) ENGINE=InnoDB;

-- Timetable
CREATE TABLE `timetables` (
  `id` VARCHAR(50) NOT NULL,
  `school_year_id` VARCHAR(50) NOT NULL,
  `semester_id` VARCHAR(50) NOT NULL,
  `class_id` VARCHAR(50) NOT NULL,
  `week_start` DATE NULL,
  `week_end` DATE NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `timetable_entries` (
  `id` VARCHAR(50) NOT NULL,
  `timetable_id` VARCHAR(50) NOT NULL,
  `day_of_week` TINYINT UNSIGNED NOT NULL, -- 1=Mon..7=Sun
  `period` TINYINT UNSIGNED NOT NULL,      -- tiết 1..n
  `subject_id` VARCHAR(50) NOT NULL,
  `teacher_id` VARCHAR(50) NOT NULL,
  `room` VARCHAR(50) NULL,
  `note` VARCHAR(255) NULL, PRIMARY KEY (`id`),
  UNIQUE KEY `timetable_slot_unique` (`timetable_id`,`day_of_week`,`period`)
) ENGINE=InnoDB;

-- AI analysis & alerts
CREATE TABLE `ai_reports` (
  `id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `semester_id` VARCHAR(50) NOT NULL,
  `summary` TEXT NOT NULL,
  `trend` ENUM('up','down','stable') NULL,
  `created_at` TIMESTAMP NULL, PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `ai_alerts` (
  `id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `teacher_id` VARCHAR(50) NULL,
  `class_id` VARCHAR(50) NULL,
  `semester_id` VARCHAR(50) NULL,
  `risk_level` ENUM('low','medium','high') NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  KEY `ai_alerts_student_idx` (`student_id`)
) ENGINE=InnoDB;

-- Messaging / notifications (teacher-parent-student)
CREATE TABLE `messages` (
  `id` VARCHAR(50) NOT NULL,
  `sender_user_id` VARCHAR(50) NOT NULL,
  `receiver_user_id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NULL,
  `content` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
  KEY `messages_sender_idx` (`sender_user_id`),
  KEY `messages_receiver_idx` (`receiver_user_id`)
) ENGINE=InnoDB;

-- Foreign keys
ALTER TABLE `semesters` ADD CONSTRAINT `semesters_school_year_fk`
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

ALTER TABLE `classes` ADD CONSTRAINT `classes_school_year_fk`
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;
ALTER TABLE `classes` ADD CONSTRAINT `classes_homeroom_fk`
  FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

ALTER TABLE `students` ADD CONSTRAINT `students_class_fk`
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;
ALTER TABLE `students` ADD CONSTRAINT `students_school_year_fk`
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

ALTER TABLE `student_transfers` ADD CONSTRAINT `transfers_student_fk`
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `teaching_assignments` ADD CONSTRAINT `assignments_teacher_fk`
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;
ALTER TABLE `teaching_assignments` ADD CONSTRAINT `assignments_class_fk`
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;
ALTER TABLE `teaching_assignments` ADD CONSTRAINT `assignments_subject_fk`
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
ALTER TABLE `teaching_assignments` ADD CONSTRAINT `assignments_school_year_fk`
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

ALTER TABLE `score_headers` ADD CONSTRAINT `score_headers_student_fk`
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `score_headers` ADD CONSTRAINT `score_headers_subject_fk`
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
ALTER TABLE `score_headers` ADD CONSTRAINT `score_headers_semester_fk`
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;
ALTER TABLE `score_headers` ADD CONSTRAINT `score_headers_school_year_fk`
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

ALTER TABLE `score_details` ADD CONSTRAINT `score_details_header_fk`
  FOREIGN KEY (`score_header_id`) REFERENCES `score_headers` (`id`) ON DELETE CASCADE;

ALTER TABLE `conducts` ADD CONSTRAINT `conducts_student_fk`
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `conducts` ADD CONSTRAINT `conducts_class_fk`
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;
ALTER TABLE `conducts` ADD CONSTRAINT `conducts_semester_fk`
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;
ALTER TABLE `conducts` ADD CONSTRAINT `conducts_school_year_fk`
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

ALTER TABLE `grade_windows` ADD CONSTRAINT `grade_windows_class_fk`
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;
ALTER TABLE `grade_windows` ADD CONSTRAINT `grade_windows_subject_fk`
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
ALTER TABLE `grade_windows` ADD CONSTRAINT `grade_windows_semester_fk`
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;
ALTER TABLE `grade_windows` ADD CONSTRAINT `grade_windows_school_year_fk`
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

ALTER TABLE `timetables` ADD CONSTRAINT `timetables_year_fk`
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;
ALTER TABLE `timetables` ADD CONSTRAINT `timetables_semester_fk`
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;
ALTER TABLE `timetables` ADD CONSTRAINT `timetables_class_fk`
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

ALTER TABLE `timetable_entries` ADD CONSTRAINT `timetable_entries_timetable_fk`
  FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE;
ALTER TABLE `timetable_entries` ADD CONSTRAINT `timetable_entries_subject_fk`
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
ALTER TABLE `timetable_entries` ADD CONSTRAINT `timetable_entries_teacher_fk`
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

ALTER TABLE `ai_reports` ADD CONSTRAINT `ai_reports_student_fk`
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `ai_reports` ADD CONSTRAINT `ai_reports_semester_fk`
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;

ALTER TABLE `ai_alerts` ADD CONSTRAINT `ai_alerts_student_fk`
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
ALTER TABLE `ai_alerts` ADD CONSTRAINT `ai_alerts_teacher_fk`
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;
ALTER TABLE `ai_alerts` ADD CONSTRAINT `ai_alerts_class_fk`
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;
ALTER TABLE `ai_alerts` ADD CONSTRAINT `ai_alerts_semester_fk`
  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL;

ALTER TABLE `messages` ADD CONSTRAINT `messages_sender_fk`
  FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `messages` ADD CONSTRAINT `messages_receiver_fk`
  FOREIGN KEY (`receiver_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `users` ADD CONSTRAINT `users_teacher_fk`
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;
ALTER TABLE `users` ADD CONSTRAINT `users_student_fk`
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;
ALTER TABLE `users` ADD CONSTRAINT `users_parent_fk`
  FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE SET NULL;

ALTER TABLE `parent_student` ADD CONSTRAINT `parent_student_parent_fk`
  FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE;
ALTER TABLE `parent_student` ADD CONSTRAINT `parent_student_student_fk`
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

-- ===================================================================
-- SAMPLE DATA / DỮ LIỆU MẪU
-- ===================================================================
-- LƯU Ý: Các password_hash dưới đây cần được tạo bằng Laravel Hash::make()
-- Để tạo hash chính xác:
--   php artisan tinker
--   > Hash::make('admin123')
--   > Hash::make('gv123')
--   > Hash::make('hs123')
-- Hoặc chạy DatabaseSeeder: php artisan db:seed --class=DatabaseSeeder
-- ===================================================================

-- Năm học / School Year
INSERT INTO `school_years` (`id`, `name`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440001', '2024-2025', '2024-08-01', '2025-05-31', 1, NOW(), NOW());

-- Kỳ học / Semesters
INSERT INTO `semesters` (`id`, `name`, `order`, `school_year_id`, `is_score_input_open`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440011', 'HK1', 1, '550e8400-e29b-41d4-a716-446655440001', 1, NOW(), NOW()),
('550e8400-e29b-41d4-a716-446655440012', 'HK2', 2, '550e8400-e29b-41d4-a716-446655440001', 1, NOW(), NOW());

-- Môn học / Subjects
INSERT INTO `subjects` (`id`, `name`, `credit`, `is_weighted`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440021', 'Toán', 1, 0, NOW(), NOW()),
('550e8400-e29b-41d4-a716-446655440022', 'Ngữ Văn', 1, 0, NOW(), NOW()),
('550e8400-e29b-41d4-a716-446655440023', 'Tiếng Anh', 1, 0, NOW(), NOW()),
('550e8400-e29b-41d4-a716-446655440024', 'Vật Lý', 1, 0, NOW(), NOW());

-- Giáo viên / Teachers
INSERT INTO `teachers` (`id`, `teacher_code`, `name`, `phone`, `email`, `qualification`, `main_subject`, `is_homeroom`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440031', 'GV001', 'Nguyễn Văn Toàn', '0901234567', 'gvtoan@example.com', 'Đại học', 'Toán', 0, NOW(), NOW()),
('550e8400-e29b-41d4-a716-446655440032', 'GV002', 'Trần Thị Chủ Nhiệm', '0908888888', 'gvcn@example.com', 'Đại học', 'Ngữ Văn', 1, NOW(), NOW());

-- Lớp học / Classes
INSERT INTO `classes` (`id`, `name`, `grade_level`, `school_year_id`, `homeroom_teacher_id`, `capacity`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440041', '10A1', 10, '550e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440032', 45, NOW(), NOW()),
('550e8400-e29b-41d4-a716-446655440042', '10A2', 10, '550e8400-e29b-41d4-a716-446655440001', NULL, 45, NOW(), NOW());

-- Học sinh / Students
INSERT INTO `students` (`id`, `student_code`, `name`, `gender`, `dob`, `address`, `parent_phone`, `email`, `class_id`, `school_year_id`, `status`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440051', 'HS001', 'Lê Minh Anh', 'male', '2009-09-20', '123 Nguyễn Trãi', '0911222333', 'leminhanh@example.com', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440001', 'studying', NOW(), NOW()),
('550e8400-e29b-41d4-a716-446655440052', 'HS002', 'Phạm Thu Hà', 'female', '2009-06-15', '456 Lê Lợi', '0944555666', 'phamthuhan@example.com', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440001', 'studying', NOW(), NOW());

-- Phân công dạy học / Teaching Assignments
INSERT INTO `teaching_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `school_year_id`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440061', '550e8400-e29b-41d4-a716-446655440031', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440021', '550e8400-e29b-41d4-a716-446655440001', NOW(), NOW()),
('550e8400-e29b-41d4-a716-446655440062', '550e8400-e29b-41d4-a716-446655440031', '550e8400-e29b-41d4-a716-446655440042', '550e8400-e29b-41d4-a716-446655440021', '550e8400-e29b-41d4-a716-446655440001', NOW(), NOW());

-- TÀI KHOẢN ĐĂNG NHẬP / USER ACCOUNTS
-- ==========================================
-- Hướng dẫn tạo password_hash:
-- Chạy: php artisan tinker
-- Sau đó nhập các lệnh sau:
--   Hash::make('admin123')    -> Để tạo hash cho admin
--   Hash::make('gv123')       -> Để tạo hash cho giáo viên
--   Hash::make('hs123')       -> Để tạo hash cho học sinh
-- Hoặc dùng DatabaseSeeder để tự động tạo tất cả
-- ==========================================

-- Admin account
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `teacher_id`, `student_id`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440091', 'admin', '$2y$12$pZAhesxAO.M8Y0SJhr/RKO89/Dm/xWfs.ueXmHRteqKvyzQVHGHN.', 'admin', NULL, NULL, NULL, 1, NOW(), NOW());

-- Teacher account (Giáo viên Toán)
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `teacher_id`, `student_id`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440092', 'gvtoan', '$2y$12$HtLlgzREIq80u3ejnOuANewbT1bpJefi5AicxRj5iCnFGaQFtADgS', 'teacher', '550e8400-e29b-41d4-a716-446655440031', NULL, NULL, 1, NOW(), NOW());

-- Homeroom teacher account (GVCN 10A1)
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `teacher_id`, `student_id`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440093', 'gvcn10a1', '$2y$12$HtLlgzREIq80u3ejnOuANewbT1bpJefi5AicxRj5iCnFGaQFtADgS', 'homeroom', '550e8400-e29b-41d4-a716-446655440032', NULL, NULL, 1, NOW(), NOW());

-- Student account (Học sinh HS001)
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `teacher_id`, `student_id`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440094', 'hs001', '$2y$12$BVpzWv6c8M8RYRj.TYuK1eB7dm5MaaL2Ww.gtcc59gwVwwgoWjSie', 'student', NULL, '550e8400-e29b-41d4-a716-446655440051', NULL, 1, NOW(), NOW());
