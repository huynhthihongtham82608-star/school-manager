-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 10, 2026 lúc 06:04 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `school_manager`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_alerts`
--

CREATE TABLE `ai_alerts` (
  `id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `class_id` varchar(50) DEFAULT NULL,
  `semester_id` varchar(50) DEFAULT NULL,
  `risk_level` enum('low','medium','high') NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ai_alerts`
--

INSERT INTO `ai_alerts` (`id`, `student_id`, `teacher_id`, `class_id`, `semester_id`, `risk_level`, `message`, `is_read`, `created_at`) VALUES
('019e9926-20d1-7069-9cba-cf855377468a', '550e8400-e29b-41d4-a716-446655440051', '550e8400-e29b-41d4-a716-446655440032', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440011', 'medium', '[AI] Lê Minh Anh: TB=N/A (truoc=N/A), rui ro=medium. Can theo doi them du lieu diem.', 0, '2026-06-05 11:57:53'),
('019e9926-20d5-7064-bdae-309ec7318cf0', '550e8400-e29b-41d4-a716-446655440052', '550e8400-e29b-41d4-a716-446655440032', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440011', 'medium', '[AI] Phạm Thu Hà: TB=N/A (truoc=N/A), rui ro=medium. Can theo doi them du lieu diem.', 0, '2026-06-05 11:57:53');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_reports`
--

CREATE TABLE `ai_reports` (
  `id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `semester_id` varchar(50) NOT NULL,
  `summary` text NOT NULL,
  `trend` enum('up','down','stable') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ai_reports`
--

INSERT INTO `ai_reports` (`id`, `student_id`, `semester_id`, `summary`, `trend`, `created_at`) VALUES
('019e9926-20cb-72ae-a95d-2babb8b790c5', '550e8400-e29b-41d4-a716-446655440051', '550e8400-e29b-41d4-a716-446655440011', 'Hoc sinh: Lê Minh Anh. TB hoc ky hien tai: N/A. TB hoc ky truoc: N/A. Xu huong: stable. Muc rui ro: medium. Chua du du lieu de goi y mon can cai thien.', 'stable', '2026-06-05 11:57:53'),
('019e9926-20d3-716b-a49b-0369d54d1c7f', '550e8400-e29b-41d4-a716-446655440052', '550e8400-e29b-41d4-a716-446655440011', 'Hoc sinh: Phạm Thu Hà. TB hoc ky hien tai: N/A. TB hoc ky truoc: N/A. Xu huong: stable. Muc rui ro: medium. Chua du du lieu de goi y mon can cai thien.', 'stable', '2026-06-05 11:57:53');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `classes`
--

CREATE TABLE `classes` (
  `id` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `homeroom_teacher_id` varchar(50) DEFAULT NULL,
  `capacity` smallint(5) UNSIGNED NOT NULL DEFAULT 45,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `classes`
--

INSERT INTO `classes` (`id`, `name`, `grade_level`, `school_year_id`, `homeroom_teacher_id`, `capacity`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440041', '10A1', 10, '550e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440032', 45, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440042', '10A2', 10, '550e8400-e29b-41d4-a716-446655440001', NULL, 45, '2026-06-05 15:55:17', '2026-06-05 15:55:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `conducts`
--

CREATE TABLE `conducts` (
  `id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `class_id` varchar(50) NOT NULL,
  `semester_id` varchar(50) NOT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `conduct_level` enum('excellent','good','average','weak') DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `conducts`
--

INSERT INTO `conducts` (`id`, `student_id`, `class_id`, `semester_id`, `school_year_id`, `conduct_level`, `comment`, `created_at`, `updated_at`) VALUES
('019e9905-b4eb-732d-bccf-8bc9f77b5d5e', '550e8400-e29b-41d4-a716-446655440051', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440011', '550e8400-e29b-41d4-a716-446655440001', 'excellent', 'Chăm ngoan, học giỏi', '2026-06-05 11:22:28', '2026-06-05 11:22:28'),
('019e9905-b4f1-70ae-83e0-4e1fa4d9a164', '550e8400-e29b-41d4-a716-446655440052', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440011', '550e8400-e29b-41d4-a716-446655440001', 'good', 'Tốt', '2026-06-05 11:22:28', '2026-06-05 11:22:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `grade_windows`
--

CREATE TABLE `grade_windows` (
  `id` varchar(50) NOT NULL,
  `class_id` varchar(50) NOT NULL,
  `subject_id` varchar(50) NOT NULL,
  `semester_id` varchar(50) NOT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id` varchar(50) NOT NULL,
  `sender_user_id` varchar(50) NOT NULL,
  `receiver_user_id` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `parents`
--

CREATE TABLE `parents` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `parents`
--

INSERT INTO `parents` (`id`, `name`, `phone`, `email`, `address`, `created_at`, `updated_at`) VALUES
('019e9900-c04a-7057-94ba-d3b2a1f726e2', 'Lê Minh Trung', '0345387247', 'Leminhtrungph@gmail.com', 'Tân Thanh, Cái Bè, Tiền Giang', '2026-06-05 11:17:03', '2026-06-05 11:17:03');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `parent_student`
--

CREATE TABLE `parent_student` (
  `parent_id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `relation` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `parent_student`
--

INSERT INTO `parent_student` (`parent_id`, `student_id`, `relation`) VALUES
('019e9900-c04a-7057-94ba-d3b2a1f726e2', '550e8400-e29b-41d4-a716-446655440051', 'PH');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `school_years`
--

CREATE TABLE `school_years` (
  `id` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `school_years`
--

INSERT INTO `school_years` (`id`, `name`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440001', '2024-2025', '2024-08-01', '2025-05-31', 1, '2026-06-05 15:55:17', '2026-06-05 15:55:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `score_details`
--

CREATE TABLE `score_details` (
  `id` varchar(50) NOT NULL,
  `score_header_id` varchar(50) NOT NULL,
  `type` enum('oral','quiz','test','midterm','final') NOT NULL,
  `value` decimal(5,2) NOT NULL,
  `weight_group` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `score_headers`
--

CREATE TABLE `score_headers` (
  `id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `subject_id` varchar(50) NOT NULL,
  `semester_id` varchar(50) NOT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `average` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `semesters`
--

CREATE TABLE `semesters` (
  `id` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `school_year_id` varchar(50) NOT NULL,
  `is_score_input_open` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `semesters`
--

INSERT INTO `semesters` (`id`, `name`, `order`, `school_year_id`, `is_score_input_open`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440011', 'HK1', 1, '550e8400-e29b-41d4-a716-446655440001', 1, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440012', 'HK2', 2, '550e8400-e29b-41d4-a716-446655440001', 1, '2026-06-05 15:55:17', '2026-06-05 15:55:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `students`
--

CREATE TABLE `students` (
  `id` varchar(50) NOT NULL,
  `student_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `parent_phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `class_id` varchar(50) NOT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `status` enum('studying','inactive','graduated') NOT NULL DEFAULT 'studying',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `students`
--

INSERT INTO `students` (`id`, `student_code`, `name`, `gender`, `dob`, `address`, `parent_phone`, `email`, `class_id`, `school_year_id`, `status`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440051', 'HS001', 'Lê Minh Anh', 'male', '2009-09-20', '123 Nguyễn Trãi', '0911222333', 'leminhanh@example.com', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440001', 'studying', '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440052', 'HS002', 'Phạm Thu Hà', 'female', '2009-06-15', '456 Lê Lợi', '0944555666', 'phamthuhan@example.com', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440001', 'studying', '2026-06-05 15:55:17', '2026-06-05 15:55:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `student_transfers`
--

CREATE TABLE `student_transfers` (
  `id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `from_class_id` varchar(50) DEFAULT NULL,
  `to_class_id` varchar(50) DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `subjects`
--

CREATE TABLE `subjects` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `credit` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `is_weighted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `credit`, `is_weighted`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440021', 'Toán', 1, 0, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440022', 'Ngữ Văn', 1, 0, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440023', 'Tiếng Anh', 1, 0, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440024', 'Vật Lý', 1, 0, '2026-06-05 15:55:17', '2026-06-05 15:55:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `teachers`
--

CREATE TABLE `teachers` (
  `id` varchar(50) NOT NULL,
  `teacher_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `main_subject` varchar(255) DEFAULT NULL,
  `is_homeroom` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_code`, `name`, `phone`, `email`, `qualification`, `main_subject`, `is_homeroom`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440031', 'GV001', 'Nguyễn Văn Toàn', '0901234567', 'gvtoan@example.com', 'Đại học', 'Toán', 0, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440032', 'GV002', 'Trần Thị Chủ Nhiệm', '0908888888', 'gvcn@example.com', 'Đại học', 'Ngữ Văn', 1, '2026-06-05 15:55:17', '2026-06-05 11:48:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `teaching_assignments`
--

CREATE TABLE `teaching_assignments` (
  `id` varchar(50) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `class_id` varchar(50) NOT NULL,
  `subject_id` varchar(50) NOT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `teaching_assignments`
--

INSERT INTO `teaching_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `school_year_id`, `created_at`, `updated_at`) VALUES
('550e8400-e29b-41d4-a716-446655440061', '550e8400-e29b-41d4-a716-446655440031', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440021', '550e8400-e29b-41d4-a716-446655440001', '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440062', '550e8400-e29b-41d4-a716-446655440031', '550e8400-e29b-41d4-a716-446655440042', '550e8400-e29b-41d4-a716-446655440021', '550e8400-e29b-41d4-a716-446655440001', '2026-06-05 15:55:17', '2026-06-05 15:55:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `timetables`
--

CREATE TABLE `timetables` (
  `id` varchar(50) NOT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `semester_id` varchar(50) NOT NULL,
  `class_id` varchar(50) NOT NULL,
  `week_start` date DEFAULT NULL,
  `week_end` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `timetables`
--

INSERT INTO `timetables` (`id`, `school_year_id`, `semester_id`, `class_id`, `week_start`, `week_end`, `created_at`, `updated_at`) VALUES
('019e98fa-f9b4-7015-895c-bb0c4062b8d6', '550e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440011', '550e8400-e29b-41d4-a716-446655440041', NULL, NULL, '2026-06-05 11:10:45', '2026-06-05 11:10:45');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `timetable_entries`
--

CREATE TABLE `timetable_entries` (
  `id` varchar(50) NOT NULL,
  `timetable_id` varchar(50) NOT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL,
  `period` tinyint(3) UNSIGNED NOT NULL,
  `subject_id` varchar(50) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff','teacher','homeroom','student','parent') NOT NULL DEFAULT 'student',
  `teacher_id` varchar(50) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `parent_id` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `teacher_id`, `student_id`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES
('019e9900-c151-70b5-914b-0b7dbf8daaa6', 'ph001', '$2y$12$vnYiSJ.VzFzvPmJuc0Lln.4yKzeiSHBLkqqsWPCz7k2UdXQicx7vW', 'parent', NULL, NULL, '019e9900-c04a-7057-94ba-d3b2a1f726e2', 1, '2026-06-05 11:17:04', '2026-06-05 11:17:04'),
('550e8400-e29b-41d4-a716-446655440091', 'admin', '$2y$12$pZAhesxAO.M8Y0SJhr/RKO89/Dm/xWfs.ueXmHRteqKvyzQVHGHN.', 'admin', NULL, NULL, NULL, 1, '2026-06-05 15:55:17', '2026-06-05 11:33:52'),
('550e8400-e29b-41d4-a716-446655440092', 'gvtoan', '$2y$12$HtLlgzREIq80u3ejnOuANewbT1bpJefi5AicxRj5iCnFGaQFtADgS', 'teacher', '550e8400-e29b-41d4-a716-446655440031', NULL, NULL, 1, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440093', 'gvcn10a1', '$2y$12$HtLlgzREIq80u3ejnOuANewbT1bpJefi5AicxRj5iCnFGaQFtADgS', 'homeroom', '550e8400-e29b-41d4-a716-446655440032', NULL, NULL, 1, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440094', 'hs001', '$2y$12$BVpzWv6c8M8RYRj.TYuK1eB7dm5MaaL2Ww.gtcc59gwVwwgoWjSie', 'student', NULL, '550e8400-e29b-41d4-a716-446655440051', NULL, 1, '2026-06-05 15:55:17', '2026-06-05 15:55:17');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `ai_alerts`
--
ALTER TABLE `ai_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_alerts_student_idx` (`student_id`),
  ADD KEY `ai_alerts_teacher_fk` (`teacher_id`),
  ADD KEY `ai_alerts_class_fk` (`class_id`),
  ADD KEY `ai_alerts_semester_fk` (`semester_id`);

--
-- Chỉ mục cho bảng `ai_reports`
--
ALTER TABLE `ai_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_reports_student_fk` (`student_id`),
  ADD KEY `ai_reports_semester_fk` (`semester_id`);

--
-- Chỉ mục cho bảng `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `classes_name_unique` (`name`),
  ADD KEY `classes_school_year_idx` (`school_year_id`),
  ADD KEY `classes_homeroom_fk` (`homeroom_teacher_id`);

--
-- Chỉ mục cho bảng `conducts`
--
ALTER TABLE `conducts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_semester_conduct_unique` (`student_id`,`semester_id`,`school_year_id`),
  ADD KEY `conducts_class_fk` (`class_id`),
  ADD KEY `conducts_semester_fk` (`semester_id`),
  ADD KEY `conducts_school_year_fk` (`school_year_id`);

--
-- Chỉ mục cho bảng `grade_windows`
--
ALTER TABLE `grade_windows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grade_window_unique` (`class_id`,`subject_id`,`semester_id`,`school_year_id`),
  ADD KEY `grade_windows_subject_fk` (`subject_id`),
  ADD KEY `grade_windows_semester_fk` (`semester_id`),
  ADD KEY `grade_windows_school_year_fk` (`school_year_id`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_sender_idx` (`sender_user_id`),
  ADD KEY `messages_receiver_idx` (`receiver_user_id`);

--
-- Chỉ mục cho bảng `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `parent_student`
--
ALTER TABLE `parent_student`
  ADD PRIMARY KEY (`parent_id`,`student_id`),
  ADD KEY `parent_student_student_fk` (`student_id`);

--
-- Chỉ mục cho bảng `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_years_name_unique` (`name`);

--
-- Chỉ mục cho bảng `score_details`
--
ALTER TABLE `score_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `score_details_header_idx` (`score_header_id`);

--
-- Chỉ mục cho bảng `score_headers`
--
ALTER TABLE `score_headers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_subject_semester_unique` (`student_id`,`subject_id`,`semester_id`,`school_year_id`),
  ADD KEY `score_headers_subject_fk` (`subject_id`),
  ADD KEY `score_headers_semester_fk` (`semester_id`),
  ADD KEY `score_headers_school_year_fk` (`school_year_id`);

--
-- Chỉ mục cho bảng `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semesters_school_year_idx` (`school_year_id`);

--
-- Chỉ mục cho bảng `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_student_code_unique` (`student_code`),
  ADD KEY `students_class_idx` (`class_id`),
  ADD KEY `students_school_year_fk` (`school_year_id`);

--
-- Chỉ mục cho bảng `student_transfers`
--
ALTER TABLE `student_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transfers_student_idx` (`student_id`);

--
-- Chỉ mục cho bảng `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subjects_name_unique` (`name`);

--
-- Chỉ mục cho bảng `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teachers_teacher_code_unique` (`teacher_code`);

--
-- Chỉ mục cho bảng `teaching_assignments`
--
ALTER TABLE `teaching_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_class_subject_unique` (`teacher_id`,`class_id`,`subject_id`,`school_year_id`),
  ADD KEY `assignments_class_fk` (`class_id`),
  ADD KEY `assignments_subject_fk` (`subject_id`),
  ADD KEY `assignments_school_year_fk` (`school_year_id`);

--
-- Chỉ mục cho bảng `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timetables_year_fk` (`school_year_id`),
  ADD KEY `timetables_semester_fk` (`semester_id`),
  ADD KEY `timetables_class_fk` (`class_id`);

--
-- Chỉ mục cho bảng `timetable_entries`
--
ALTER TABLE `timetable_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `timetable_slot_unique` (`timetable_id`,`day_of_week`,`period`),
  ADD KEY `timetable_entries_subject_fk` (`subject_id`),
  ADD KEY `timetable_entries_teacher_fk` (`teacher_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD KEY `users_teacher_fk` (`teacher_id`),
  ADD KEY `users_student_fk` (`student_id`),
  ADD KEY `users_parent_fk` (`parent_id`);

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `ai_alerts`
--
ALTER TABLE `ai_alerts`
  ADD CONSTRAINT `ai_alerts_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ai_alerts_semester_fk` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ai_alerts_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_alerts_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `ai_reports`
--
ALTER TABLE `ai_reports`
  ADD CONSTRAINT `ai_reports_semester_fk` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_reports_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_homeroom_fk` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `classes_school_year_fk` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `conducts`
--
ALTER TABLE `conducts`
  ADD CONSTRAINT `conducts_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conducts_school_year_fk` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conducts_semester_fk` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conducts_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `grade_windows`
--
ALTER TABLE `grade_windows`
  ADD CONSTRAINT `grade_windows_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_windows_school_year_fk` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_windows_semester_fk` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_windows_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_receiver_fk` FOREIGN KEY (`receiver_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_sender_fk` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `parent_student`
--
ALTER TABLE `parent_student`
  ADD CONSTRAINT `parent_student_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_student_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `score_details`
--
ALTER TABLE `score_details`
  ADD CONSTRAINT `score_details_header_fk` FOREIGN KEY (`score_header_id`) REFERENCES `score_headers` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `score_headers`
--
ALTER TABLE `score_headers`
  ADD CONSTRAINT `score_headers_school_year_fk` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `score_headers_semester_fk` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `score_headers_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `score_headers_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_school_year_fk` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_school_year_fk` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `student_transfers`
--
ALTER TABLE `student_transfers`
  ADD CONSTRAINT `transfers_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `teaching_assignments`
--
ALTER TABLE `teaching_assignments`
  ADD CONSTRAINT `assignments_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_school_year_fk` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetables_semester_fk` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetables_year_fk` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `timetable_entries`
--
ALTER TABLE `timetable_entries`
  ADD CONSTRAINT `timetable_entries_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_timetable_fk` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
