-- SQL tạo database + dữ liệu mẫu cho Quản lý trường THPT
-- Có thể dùng thay cho migrate/seed nếu cần khôi phục nhanh

CREATE DATABASE IF NOT EXISTS `school_manager` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `school_manager`;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NULL,
  `role` varchar(50) NOT NULL DEFAULT 'student',
  `teacher_id` bigint unsigned NULL,
  `student_id` bigint unsigned NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` text NULL,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `school_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `start_date` date NULL,
  `end_date` date NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_years_name_unique` (`name`)
) ENGINE=InnoDB;

CREATE TABLE `teachers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) NULL,
  `email` varchar(255) NULL,
  `main_subject` varchar(255) NULL,
  `is_homeroom` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teachers_teacher_code_unique` (`teacher_code`)
) ENGINE=InnoDB;

CREATE TABLE `subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `credit` tinyint unsigned NOT NULL DEFAULT 1,
  `is_weighted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_name_unique` (`name`)
) ENGINE=InnoDB;

CREATE TABLE `classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `grade_level` tinyint unsigned NOT NULL,
  `school_year_id` bigint unsigned NOT NULL,
  `homeroom_teacher_id` bigint unsigned NULL,
  `capacity` smallint unsigned NOT NULL DEFAULT 45,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classes_name_unique` (`name`)
) ENGINE=InnoDB;

CREATE TABLE `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gender` enum('male','female','other') NULL,
  `dob` date NULL,
  `address` varchar(255) NULL,
  `parent_phone` varchar(50) NULL,
  `email` varchar(255) NULL,
  `class_id` bigint unsigned NOT NULL,
  `school_year_id` bigint unsigned NOT NULL,
  `status` enum('studying','inactive') NOT NULL DEFAULT 'studying',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_student_code_unique` (`student_code`)
) ENGINE=InnoDB;

CREATE TABLE `semesters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` tinyint unsigned NOT NULL DEFAULT 1,
  `school_year_id` bigint unsigned NOT NULL,
  `is_score_input_open` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `teaching_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `class_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `school_year_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_class_subject_unique` (`teacher_id`,`class_id`,`subject_id`,`school_year_id`)
) ENGINE=InnoDB;

CREATE TABLE `score_headers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `semester_id` bigint unsigned NOT NULL,
  `school_year_id` bigint unsigned NOT NULL,
  `average` decimal(5,2) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_subject_semester_unique` (`student_id`,`subject_id`,`semester_id`,`school_year_id`)
) ENGINE=InnoDB;

CREATE TABLE `score_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `score_header_id` bigint unsigned NOT NULL,
  `type` enum('oral','quiz','test','midterm','final') NOT NULL,
  `value` decimal(5,2) NOT NULL,
  `weight_group` tinyint unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `conducts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_id` bigint unsigned NOT NULL,
  `semester_id` bigint unsigned NOT NULL,
  `school_year_id` bigint unsigned NOT NULL,
  `conduct_level` enum('excellent','good','average','weak') NULL,
  `comment` text NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_semester_conduct_unique` (`student_id`,`semester_id`,`school_year_id`)
) ENGINE=InnoDB;

CREATE TABLE `grade_windows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `semester_id` bigint unsigned NOT NULL,
  `school_year_id` bigint unsigned NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade_window_unique` (`class_id`,`subject_id`,`semester_id`,`school_year_id`)
) ENGINE=InnoDB;

-- Dữ liệu mẫu
INSERT INTO `school_years` (`id`,`name`,`start_date`,`end_date`,`is_active`) VALUES
(1,'2024-2025','2024-08-01','2025-05-31',1);

INSERT INTO `semesters` (`id`,`name`,`order`,`school_year_id`,`is_score_input_open`) VALUES
(1,'HK1',1,1,1),
(2,'HK2',2,1,1);

INSERT INTO `teachers` (`id`,`teacher_code`,`name`,`phone`,`email`,`main_subject`,`is_homeroom`) VALUES
(1,'GV001','Nguyễn Văn Toàn','0901234567','gvtoan@example.com','Toán',0),
(2,'GV002','Trần Thị Chủ Nhiệm','0908888888','gvcn@example.com','Văn',1);

INSERT INTO `subjects` (`id`,`name`,`credit`,`is_weighted`) VALUES
(1,'Toán',1,0),
(2,'Ngữ văn',1,0),
(3,'Tiếng Anh',1,0),
(4,'Vật lý',1,0);

INSERT INTO `classes` (`id`,`name`,`grade_level`,`school_year_id`,`homeroom_teacher_id`,`capacity`) VALUES
(1,'10A1',10,1,2,45),
(2,'10A2',10,1,NULL,45);

INSERT INTO `students` (`id`,`student_code`,`name`,`gender`,`dob`,`parent_phone`,`class_id`,`school_year_id`,`status`) VALUES
(1,'HS001','Lê Minh Anh','male','2009-09-20','0911222333',1,1,'studying'),
(2,'HS002','Phạm Thu Hà','female','2009-06-15','0944555666',1,1,'studying');

INSERT INTO `users` (`id`,`username`,`name`,`email`,`role`,`teacher_id`,`student_id`,`password`) VALUES
(1,'admin','Admin',NULL,'admin',NULL,NULL,'$2y$12$TbJnJaampzc4qBKKgKwAvenP9MuzFYTNECorsHYpoemVQFxr1kIGO'),
(2,'gvtoan','Nguyễn Văn Toàn','gvtoan@example.com','teacher',1,NULL,'$2y$12$x1ByqD1/2KSerSyVn9AdL.sAohz1pweGOlzTsDGZOdge1pXnDbdY2'),
(3,'gvcn10a1','Trần Thị Chủ Nhiệm','gvcn@example.com','homeroom',2,NULL,'$2y$12$x1ByqD1/2KSerSyVn9AdL.sAohz1pweGOlzTsDGZOdge1pXnDbdY2'),
(4,'hs001','Lê Minh Anh',NULL,'student',NULL,1,'$2y$12$tAUw7/rHX/tR5xzGA16XFOWQf2blAIeL6zZ57Mpt8kh..RcVFudBy');

INSERT INTO `teaching_assignments` (`id`,`teacher_id`,`class_id`,`subject_id`,`school_year_id`) VALUES
(1,1,1,1,1),
(2,2,1,2,1);

INSERT INTO `grade_windows` (`id`,`class_id`,`subject_id`,`semester_id`,`school_year_id`,`is_open`) VALUES
(1,1,1,1,1,1);

INSERT INTO `conducts` (`id`,`student_id`,`class_id`,`semester_id`,`school_year_id`,`conduct_level`,`comment`) VALUES
(1,1,1,1,1,'excellent','Chăm ngoan');

INSERT INTO `score_headers` (`id`,`student_id`,`subject_id`,`semester_id`,`school_year_id`,`average`) VALUES
(1,1,1,1,1,8.50);

INSERT INTO `score_details` (`id`,`score_header_id`,`type`,`value`,`weight_group`) VALUES
(1,1,'final',9.00,3);
