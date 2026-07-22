-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th7 20, 2026 lúc 06:56 PM
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
-- Cấu trúc bảng cho bảng `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` char(36) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `class_id` varchar(50) NOT NULL,
  `semester_id` varchar(50) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `session_type` varchar(20) NOT NULL DEFAULT 'daily',
  `timetable_entry_id` varchar(50) DEFAULT NULL,
  `session_label` varchar(255) DEFAULT NULL,
  `session_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `session_key` varchar(80) NOT NULL DEFAULT 'daily',
  `status` varchar(255) NOT NULL DEFAULT 'present',
  `note` text DEFAULT NULL,
  `recorded_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `attendance_records`
--

INSERT INTO `attendance_records` (`id`, `student_id`, `class_id`, `semester_id`, `attendance_date`, `session_type`, `timetable_entry_id`, `session_label`, `session_order`, `session_key`, `status`, `note`, `recorded_by`, `created_at`, `updated_at`) VALUES
('019f5ceb-79ae-7322-ac6d-422e52d0e18a', '550e8400-e29b-41d4-a716-446655440051', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79b3-7096-b1ed-d90c34fff8a5', '550e8400-e29b-41d4-a716-446655440052', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79b7-70f6-aae8-87b1f34b8ea6', '019f2465-c5d6-7215-a117-3229fdf89d17', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79b8-7190-bbea-9b07d80648a5', '019f2465-c6a4-7275-8cb5-8317df8a2301', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79ba-7087-b456-8e29eca23f90', '019f2468-5982-7096-a622-a21e0470f5b2', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79bc-721d-9f94-6c0cf35de69d', '019f246d-7c81-7324-a077-22a7b4d1c6b9', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79bd-734b-91f9-e55ad63f81a1', '019f2483-c309-73ab-ace6-efc291cd2279', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79bf-70f9-9404-11efd22216a2', '019f2483-c3df-722c-a54e-4e00c18ea3e4', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79c1-7253-9ae7-b51db064abe2', '019f47e8-040e-7143-a8c5-13c56caab718', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79c2-7034-b609-69aff6e8efe7', '019f5cd8-807f-7063-92ef-45fe7adfa46a', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23'),
('019f5ceb-79c4-7146-ad71-c0d194c155e9', '019f237f-316a-71d9-97bb-765763ae00b6', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-13', 'daily', NULL, 'Điểm danh theo ngày', 0, 'daily', 'present', NULL, '550e8400-e29b-41d4-a716-446655440091', '2026-07-13 12:19:23', '2026-07-13 12:19:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `classes`
--

CREATE TABLE `classes` (
  `id` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `semester_id` varchar(50) DEFAULT NULL,
  `homeroom_teacher_id` varchar(50) DEFAULT NULL,
  `capacity` smallint(5) UNSIGNED NOT NULL DEFAULT 45,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `locked_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `classes`
--

INSERT INTO `classes` (`id`, `name`, `grade_level`, `school_year_id`, `semester_id`, `homeroom_teacher_id`, `capacity`, `status`, `locked_at`, `archived_at`, `created_at`, `updated_at`) VALUES
('019f149a-fbfe-728f-baa7-748afe885ca6', '11A1', 11, '019f149a-fbe8-71f3-9491-7f037342cdfa', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '550e8400-e29b-41d4-a716-446655440032', 45, 'active', NULL, NULL, '2026-06-29 11:18:49', '2026-07-03 02:27:04'),
('019f24be-00f8-7346-8809-42615afddc37', '11A2', 11, '019f149a-fbe8-71f3-9491-7f037342cdfa', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '550e8400-e29b-41d4-a716-446655440031', 45, 'active', NULL, NULL, '2026-07-02 14:30:59', '2026-07-02 14:33:52'),
('019f24c3-298a-73c2-abef-7a05dc300580', '10A1', 10, '019f149a-fbe8-71f3-9491-7f037342cdfa', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', NULL, 45, 'active', '2026-07-02 14:36:40', NULL, '2026-07-02 14:36:37', '2026-07-02 14:36:45'),
('550e8400-e29b-41d4-a716-446655440041', '10A1', 10, '550e8400-e29b-41d4-a716-446655440001', NULL, '550e8400-e29b-41d4-a716-446655440032', 45, 'draft', NULL, NULL, '2026-06-05 15:55:17', '2026-06-05 15:55:17');

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
('019f6226-39e2-7228-b2cc-512bf4563d39', '019f237f-316a-71d9-97bb-765763ae00b6', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39e8-71e8-9b60-4ec3cdd911d3', '019f2465-c5d6-7215-a117-3229fdf89d17', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39eb-73c9-9c4d-c690a3b565a5', '019f2465-c6a4-7275-8cb5-8317df8a2301', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39ec-720a-ab5e-fe3310cbaf48', '019f2468-5982-7096-a622-a21e0470f5b2', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39ee-71ef-9f84-4e37384d2ab7', '019f246d-7c81-7324-a077-22a7b4d1c6b9', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39ef-7345-8293-eba463dd3203', '019f2483-c309-73ab-ace6-efc291cd2279', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39f1-72f4-9fb0-4b6b25ba1d1e', '019f2483-c3df-722c-a54e-4e00c18ea3e4', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39f2-720b-b734-a46be725ed54', '019f47e8-040e-7143-a8c5-13c56caab718', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39f3-7256-a48f-40f21abd0f7e', '019f5cd8-807f-7063-92ef-45fe7adfa46a', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39f5-70cf-8861-28a4cf216687', '550e8400-e29b-41d4-a716-446655440051', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'good', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40'),
('019f6226-39f6-706b-88f3-476e71652e24', '550e8400-e29b-41d4-a716-446655440052', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'excellent', NULL, '2026-07-14 12:41:40', '2026-07-14 12:41:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_schedules`
--

CREATE TABLE `exam_schedules` (
  `id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `class_id` varchar(50) DEFAULT NULL,
  `subject_id` varchar(50) DEFAULT NULL,
  `semester_id` varchar(50) DEFAULT NULL,
  `exam_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(255) DEFAULT NULL,
  `score_input_opens_at` date DEFAULT NULL,
  `score_input_closes_at` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `exam_schedules`
--

INSERT INTO `exam_schedules` (`id`, `title`, `type`, `display_name`, `class_id`, `subject_id`, `semester_id`, `exam_date`, `start_time`, `end_time`, `room`, `score_input_opens_at`, `score_input_closes_at`, `note`, `created_at`, `updated_at`) VALUES
('019f5cab-8b47-72f0-a551-e4f6dbb05e77', 'Kiểm tra giữa kỳ', 'midterm', 'Kiểm tra giữa kỳ', '019f24c3-298a-73c2-abef-7a05dc300580', '019f24c7-5682-70cc-a045-e23cd365e3f4', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '2026-07-14', '07:00:00', '08:00:00', '1', '2026-07-15', '2026-07-21', '<!--school_manager_meta:{\"school_year_id\":\"019f149a-fbe8-71f3-9491-7f037342cdfa\",\"status\":\"published\"}-->', '2026-07-13 11:09:34', '2026-07-13 11:10:07');

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
-- Cấu trúc bảng cho bảng `home_page_contents`
--

CREATE TABLE `home_page_contents` (
  `id` char(36) NOT NULL,
  `key` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `extra` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `home_page_contents`
--

INSERT INTO `home_page_contents` (`id`, `key`, `title`, `content`, `image_url`, `extra`, `created_at`, `updated_at`) VALUES
('019f0873-11eb-7058-b8ed-b8f828de545e', 'banner', 'Chào mừng đến với Trường Trung học Phổ thông', 'Hệ thống quản lý trường THPT giúp học sinh, giáo viên và phụ huynh dễ dàng theo dõi kết quả học tập, thời khóa biểu, thông báo và các hoạt động của nhà trường.', NULL, '{\"subtitle\":\"M\\u00f4i tr\\u01b0\\u1eddng h\\u1ecdc t\\u1eadp hi\\u1ec7n \\u0111\\u1ea1i - K\\u1ef7 c\\u01b0\\u01a1ng - S\\u00e1ng t\\u1ea1o - Ph\\u00e1t tri\\u1ec3n to\\u00e0n di\\u1ec7n\"}', '2026-06-27 02:39:46', '2026-06-27 02:39:46'),
('019f0873-120f-7157-9684-18cbedc7ab27', 'about', 'Giới thiệu nhà trường', 'Trường Trung học Phổ thông hướng đến xây dựng môi trường giáo dục hiện đại, lấy học sinh làm trung tâm, chú trọng phát triển kiến thức, kỹ năng và phẩm chất. Nhà trường không ngừng đổi mới phương pháp giảng dạy, ứng dụng công nghệ thông tin trong quản lý và học tập nhằm nâng cao chất lượng giáo dục.', NULL, NULL, '2026-06-27 02:39:46', '2026-06-27 02:40:11'),
('019f0873-1213-73a9-8b53-fb643b02f55d', 'contact', 'Thông tin liên hệ', 'Nhà trường luôn sẵn sàng hỗ trợ học sinh, phụ huynh và giáo viên. Mọi thắc mắc vui lòng liên hệ qua điện thoại, email hoặc đến trực tiếp văn phòng trong giờ hành chính.', NULL, '{\"phone\":\"038 608 2608\",\"email\":\"hththam-cntt17@tdu.edu.vn\",\"address\":\"L\\u00ea B\\u00ecnh, qu\\u1eadn C\\u00e1i R\\u0103ng, tp C\\u1ea7n Th\\u01a1\"}', '2026-06-27 02:39:46', '2026-06-27 02:39:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `learning_documents`
--

CREATE TABLE `learning_documents` (
  `id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `subject_id` varchar(50) DEFAULT NULL,
  `class_id` varchar(50) DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `learning_documents`
--

INSERT INTO `learning_documents` (`id`, `title`, `description`, `category`, `file_url`, `subject_id`, `class_id`, `uploaded_by`, `is_published`, `created_at`, `updated_at`) VALUES
('019f5cda-5918-72ac-9d6f-8c86d19ba154', 'Test', 'Không có mô tả\n<!--school_manager_meta:{\"target_roles\":[\"all\"]}-->', 'tài liệu mật hệ thống', 'http://localhost/storage/learning-documents/bao-cao-so-bo-20260713190041-wYcaJ6aE.docx', NULL, NULL, '550e8400-e29b-41d4-a716-446655440091', 1, '2026-07-13 12:00:41', '2026-07-13 12:00:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id` varchar(50) NOT NULL,
  `conversation_id` varchar(50) DEFAULT NULL,
  `parent_message_id` varchar(50) DEFAULT NULL,
  `sender_user_id` varchar(50) NOT NULL,
  `receiver_user_id` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `target_type` varchar(50) NOT NULL DEFAULT 'individual',
  `recipient_summary` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `sender_deleted_at` timestamp NULL DEFAULT NULL,
  `sender_permanently_deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `parent_message_id`, `sender_user_id`, `receiver_user_id`, `title`, `content`, `target_type`, `recipient_summary`, `is_read`, `created_at`, `sender_deleted_at`, `sender_permanently_deleted_at`) VALUES
('019ecc81-1862-73ed-9318-0802997aeea2', '019ecc81-1862-73ed-9318-0802997aeea2', NULL, '550e8400-e29b-41d4-a716-446655440094', '550e8400-e29b-41d4-a716-446655440091', 'Phản ánh hạnh kiểm', 'Tôi cảm thấy hạnh kiểm không đúng.', 'individual', NULL, 1, '2026-06-15 11:17:52', NULL, NULL),
('019f0915-9b9d-7084-a8f6-b5bddc4ae58a', '019f0915-9b9d-7084-a8f6-b5bddc4ae58a', NULL, '550e8400-e29b-41d4-a716-446655440091', '550e8400-e29b-41d4-a716-446655440094', 'Trả lời về việc phản ánh hạnh kiểm', 'Hạnh kiểm của học sinh hs001 không có vấn đề gì cả.', 'individual', NULL, 1, '2026-06-27 05:37:18', NULL, NULL),
('019f4c9c-073d-73aa-8535-0129f3bccb19', '019ecc81-1862-73ed-9318-0802997aeea2', '019ecc81-1862-73ed-9318-0802997aeea2', '550e8400-e29b-41d4-a716-446655440091', '550e8400-e29b-41d4-a716-446655440094', 'Re: Phản ánh hạnh kiểm', 'ờ', 'individual', 'HS001 - Lê Minh Anh - 11A1', 0, '2026-07-10 08:18:41', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `message_attachments`
--

CREATE TABLE `message_attachments` (
  `id` varchar(50) NOT NULL,
  `message_id` varchar(50) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `message_recipients`
--

CREATE TABLE `message_recipients` (
  `id` varchar(50) NOT NULL,
  `message_id` varchar(50) NOT NULL,
  `receiver_user_id` varchar(50) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `permanently_deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `message_recipients`
--

INSERT INTO `message_recipients` (`id`, `message_id`, `receiver_user_id`, `is_read`, `read_at`, `deleted_at`, `permanently_deleted_at`, `created_at`, `updated_at`) VALUES
('019f4c9c-075c-72ca-8e7d-8c3fa9abb135', '019f4c9c-073d-73aa-8535-0129f3bccb19', '550e8400-e29b-41d4-a716-446655440094', 1, '2026-07-12 14:52:44', NULL, NULL, '2026-07-10 08:18:41', '2026-07-12 14:52:44'),
('1c9d7944-7c6e-11f1-857e-04421ad60579', '019ecc81-1862-73ed-9318-0802997aeea2', '550e8400-e29b-41d4-a716-446655440091', 1, '2026-06-15 11:17:52', NULL, NULL, '2026-06-15 11:17:52', '2026-06-15 11:17:52'),
('1c9d8aa8-7c6e-11f1-857e-04421ad60579', '019f0915-9b9d-7084-a8f6-b5bddc4ae58a', '550e8400-e29b-41d4-a716-446655440094', 1, '2026-06-27 05:37:18', NULL, NULL, '2026-06-27 05:37:18', '2026-06-27 05:37:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_06_29_103331_create_personal_access_tokens_table', 1),
(2, '2026_06_30_000001_add_archived_at_to_school_years_table', 2),
(3, '2026_07_01_000001_add_status_to_semesters_table', 3),
(4, '2026_07_01_000002_add_semester_and_status_to_classes_table', 4),
(5, '2026_07_01_000003_add_code_type_status_to_subjects_table', 5),
(6, '2026_07_02_000001_update_teaching_assignments_for_semester_flow', 6),
(7, '2026_07_02_000002_update_timetable_entries_for_assignment_flow', 7),
(8, '2026_07_02_000003_create_subject_period_norms_table', 8),
(9, '2026_07_02_000004_create_rooms_table_and_link_timetable_entries', 9),
(10, '2026_07_02_000005_add_missing_foreign_keys_to_timetable_entries', 10),
(11, '2026_07_02_000006_update_students_management_fields', 11),
(12, '2026_07_02_000007_standardize_student_gender_values', 12),
(13, '2026_07_02_000008_add_admission_fields_to_students_table', 13),
(14, '2026_07_02_000009_add_religion_to_students_table', 14),
(15, '2026_07_03_000001_update_classes_unique_index_for_semester_scope', 15),
(16, '2026_07_03_000002_create_student_class_assignments_table', 16),
(17, '2026_07_03_000003_add_force_change_password_to_users_table', 17),
(18, '2026_07_03_000004_add_profile_fields_to_teachers_table', 18),
(19, '2026_07_09_000001_normalize_subject_codes_to_mh_format', 19),
(20, '2026_07_09_000002_add_parent_code_to_parents_table', 20),
(21, '2026_07_09_000003_sync_parent_usernames_to_phone', 21),
(22, '2026_07_10_000004_create_system_settings_table', 22),
(23, '2026_07_10_000005_upgrade_internal_messages_module', 23),
(24, '2026_07_10_000006_add_conversation_fields_to_messages_table', 24),
(25, '2026_07_13_000001_add_ai_encouragements_to_system_settings_table', 25),
(26, '2026_07_13_000002_normalize_current_academic_context', 26),
(27, '2026_07_13_000003_add_teacher_primary_subject_and_assignment_flags', 27),
(28, '2026_07_13_000004_move_homeroom_management_to_classes', 28),
(29, '2026_07_13_000010_normalize_subject_academic_types', 29),
(30, '2026_07_13_000011_add_type_display_name_to_exam_schedules', 30),
(31, '2026_06_16_000006_create_exam_schedules_table', 31),
(32, '2026_07_14_000001_upgrade_exam_schedules_score_windows', 32),
(33, '2026_07_14_000002_upgrade_attendance_records_for_sessions', 33),
(34, '2026_07_20_000001_remove_ai_learning_analysis_artifacts', 34),
(35, '2026_07_21_000001_create_teacher_departments_table', 35),
(36, '2026_07_21_000002_create_teacher_department_subject_table', 36),
(37, '2026_07_21_000003_detach_non_official_subjects_from_teacher_departments', 37),
(38, '2026_07_21_000004_sync_teachers_to_departments_by_department_subjects', 38);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `parents`
--

CREATE TABLE `parents` (
  `id` varchar(50) NOT NULL,
  `parent_code` varchar(20) DEFAULT NULL,
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

INSERT INTO `parents` (`id`, `parent_code`, `name`, `phone`, `email`, `address`, `created_at`, `updated_at`) VALUES
('019e9900-c04a-7057-94ba-d3b2a1f726e2', 'PH0001', 'Lê Minh Trung', '0345387247', 'Leminhtrungph@gmail.com', 'Tân Thanh, Cái Bè, Tiền Giang', '2026-06-05 11:17:03', '2026-06-05 11:17:03'),
('019f099f-b83e-73f6-8574-0b054fdd6d4d', 'PH0002', 'Phạm Nhựt Nhân', '0123798356', 'nhan@gmail.com', 'Tân Thanh, Cái Bè, Tiền Giang', '2026-06-27 08:08:10', '2026-06-27 08:08:10'),
('019f47e8-04d4-729f-b0d9-11a741f1fe33', 'PH0003', 'Phan văn Kiệt', '032456789', NULL, 'Ổ 1', '2026-07-09 10:23:35', '2026-07-09 10:23:35'),
('019f5cd8-8146-71aa-b20c-b86b11a9ae2a', 'PH0004', 'Phan văn Kiệt', '0345117288', NULL, NULL, '2026-07-13 11:58:40', '2026-07-13 11:58:40');

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
('019e9900-c04a-7057-94ba-d3b2a1f726e2', '550e8400-e29b-41d4-a716-446655440051', 'father'),
('019f099f-b83e-73f6-8574-0b054fdd6d4d', '550e8400-e29b-41d4-a716-446655440052', 'PH'),
('019f47e8-04d4-729f-b0d9-11a741f1fe33', '019f2465-c6a4-7275-8cb5-8317df8a2301', 'father'),
('019f47e8-04d4-729f-b0d9-11a741f1fe33', '019f2483-c309-73ab-ace6-efc291cd2279', 'father'),
('019f47e8-04d4-729f-b0d9-11a741f1fe33', '019f2483-c3df-722c-a54e-4e00c18ea3e4', 'father'),
('019f47e8-04d4-729f-b0d9-11a741f1fe33', '019f47e8-040e-7143-a8c5-13c56caab718', 'father'),
('019f5cd8-8146-71aa-b20c-b86b11a9ae2a', '019f5cd8-807f-7063-92ef-45fe7adfa46a', 'guardian');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` char(36) NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(2, 'App\\Models\\User', '550e8400-e29b-41d4-a716-446655440091', 'android', 'b2bfd23ffec4fd7898252752456d46e0f3f85fb154cd5a380d89db913844805a', '[\"*\"]', NULL, NULL, '2026-06-29 09:15:43', '2026-06-29 09:15:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rooms`
--

CREATE TABLE `rooms` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'standard',
  `custom_type` varchar(255) DEFAULT NULL,
  `capacity` smallint(5) UNSIGNED NOT NULL DEFAULT 45,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `type`, `custom_type`, `capacity`, `status`, `note`, `created_at`, `updated_at`) VALUES
('019f1f0b-cc11-7329-8663-41700385ddac', 'T01', 'standard', NULL, 60, 'active', NULL, '2026-07-01 11:58:14', '2026-07-01 11:58:14'),
('5f453e48-11c3-4fb1-9c89-8f9840ecfc3f', '1', 'standard', NULL, 45, 'active', NULL, '2026-07-01 11:56:18', '2026-07-01 11:56:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `school_events`
--

CREATE TABLE `school_events` (
  `id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `school_events`
--

INSERT INTO `school_events` (`id`, `title`, `description`, `location`, `starts_at`, `ends_at`, `is_published`, `created_at`, `updated_at`) VALUES
('019f12b1-32aa-714a-9712-8fbbe6cec05f', 'aaa', 'dgdsgsgsdg\n<!--school_manager_meta:{\"target_roles\":[\"all\"]}-->', 'fdgg', '2026-06-30 17:24:00', '2026-07-04 17:24:00', 1, '2026-06-29 02:23:50', '2026-06-29 02:23:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `school_posts`
--

CREATE TABLE `school_posts` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `school_posts`
--

INSERT INTO `school_posts` (`id`, `type`, `title`, `summary`, `content`, `published_at`, `is_published`, `created_at`, `updated_at`) VALUES
('019f0873-ff2e-71e6-9586-b4d4c5005362', 'news', 'Thông báo kế hoạch ôn tập và kiểm tra học kỳ II', 'Nhà trường thông báo kế hoạch ôn tập và lịch kiểm tra học kỳ II dành cho toàn thể học sinh.', 'Nhằm chuẩn bị cho kỳ kiểm tra học kỳ II, nhà trường triển khai kế hoạch ôn tập theo từng môn học. Học sinh cần theo dõi thời khóa biểu, hoàn thành đầy đủ bài tập và tham gia các buổi ôn tập theo lịch của giáo viên bộ môn.', '2026-04-02 17:00:00', 1, '2026-06-27 02:40:47', '2026-06-27 02:40:47');

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
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `school_years`
--

INSERT INTO `school_years` (`id`, `name`, `start_date`, `end_date`, `is_active`, `archived_at`, `created_at`, `updated_at`) VALUES
('019f08c6-f17a-7251-b397-22f3a317dd4e', '2025 - 2026', '0002-08-01', '2026-05-31', 0, '2026-06-29 10:55:22', '2026-06-27 04:11:23', '2026-06-30 11:52:45'),
('019f12b3-8c59-7100-97a1-d4d48b07a82a', '2026-2027', '2026-09-01', '2027-05-30', 0, '2026-06-29 11:19:32', '2026-06-29 02:26:24', '2026-06-29 11:19:32'),
('019f149a-fbe8-71f3-9491-7f037342cdfa', '2026 - 2027', '2026-08-02', '2027-05-31', 1, NULL, '2026-06-29 11:18:49', '2026-06-30 12:52:40'),
('550e8400-e29b-41d4-a716-446655440001', '2024-2025', '2024-08-01', '2025-05-31', 0, '2026-06-29 10:55:14', '2026-06-05 15:55:17', '2026-06-29 10:57:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `score_details`
--

CREATE TABLE `score_details` (
  `id` varchar(50) NOT NULL,
  `score_header_id` varchar(50) NOT NULL,
  `exam_schedule_id` varchar(50) DEFAULT NULL,
  `type` enum('oral','quiz','test','midterm','final') NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` decimal(5,2) NOT NULL,
  `weight_group` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `score_details`
--

INSERT INTO `score_details` (`id`, `score_header_id`, `exam_schedule_id`, `type`, `name`, `value`, `weight_group`, `created_at`, `updated_at`) VALUES
('019f6211-4577-71f6-8f42-9e181dfa85be', '019f6211-4576-72ab-b34a-1a3446cc7424', NULL, 'oral', NULL, 8.00, 1, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-4579-714c-8c35-dee39cf35e33', '019f6211-4576-72ab-b34a-1a3446cc7424', NULL, 'quiz', NULL, 9.00, 1, '2026-07-14 12:18:46', '2026-07-14 12:18:46');

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

--
-- Đang đổ dữ liệu cho bảng `score_headers`
--

INSERT INTO `score_headers` (`id`, `student_id`, `subject_id`, `semester_id`, `school_year_id`, `average`, `created_at`, `updated_at`) VALUES
('019f6211-4556-70a0-90b5-24cab230ebd7', '019f237f-316a-71d9-97bb-765763ae00b6', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-4560-713b-9e91-f217956d6cf3', '019f2465-c5d6-7215-a117-3229fdf89d17', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-4564-709e-ade5-09f0693aedd1', '019f2465-c6a4-7275-8cb5-8317df8a2301', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-4567-7040-ac8c-f4557b50ca57', '019f2468-5982-7096-a622-a21e0470f5b2', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-4569-7133-8cbb-e7d68c0c2691', '019f246d-7c81-7324-a077-22a7b4d1c6b9', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-456c-73cf-8c9a-e774b363cc50', '019f2483-c309-73ab-ace6-efc291cd2279', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-456e-701c-8f0d-fca6403bd761', '019f2483-c3df-722c-a54e-4e00c18ea3e4', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-4570-7151-b98f-10fb1308108f', '019f47e8-040e-7143-a8c5-13c56caab718', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-4573-729e-941b-0c44ddcb3d51', '019f5cd8-807f-7063-92ef-45fe7adfa46a', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-4576-72ab-b34a-1a3446cc7424', '550e8400-e29b-41d4-a716-446655440051', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 8.50, '2026-07-14 12:18:46', '2026-07-14 12:18:46'),
('019f6211-457c-72bf-9638-94d184e414a0', '550e8400-e29b-41d4-a716-446655440052', '550e8400-e29b-41d4-a716-446655440021', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbe8-71f3-9491-7f037342cdfa', NULL, '2026-07-14 12:18:46', '2026-07-14 12:18:46');

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
  `status` varchar(20) NOT NULL DEFAULT 'inactive',
  `locked_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `semesters`
--

INSERT INTO `semesters` (`id`, `name`, `order`, `school_year_id`, `is_score_input_open`, `status`, `locked_at`, `archived_at`, `created_at`, `updated_at`) VALUES
('019f12b3-ddb7-7376-bc47-75803a7ac02d', 'Học kỳ 2', 2, '019f149a-fbe8-71f3-9491-7f037342cdfa', 0, 'inactive', NULL, NULL, '2026-06-29 02:26:45', '2026-07-14 11:53:55'),
('019f1d58-7ca7-73e9-b531-9f27f624f5d6', 'Học kỳ 1', 1, '019f149a-fbe8-71f3-9491-7f037342cdfa', 1, 'active', NULL, NULL, '2026-07-01 04:02:46', '2026-07-14 11:53:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `students`
--

CREATE TABLE `students` (
  `id` varchar(50) NOT NULL,
  `student_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gender` enum('nam','nu') NOT NULL,
  `dob` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `place_of_birth` varchar(255) DEFAULT NULL,
  `ethnicity` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `admission_type` varchar(20) NOT NULL DEFAULT 'new',
  `previous_school` varchar(255) DEFAULT NULL,
  `transfer_grade_level` tinyint(3) UNSIGNED DEFAULT NULL,
  `previous_class` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `class_id` varchar(50) DEFAULT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'studying',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `students`
--

INSERT INTO `students` (`id`, `student_code`, `name`, `gender`, `dob`, `address`, `place_of_birth`, `ethnicity`, `religion`, `parent_phone`, `email`, `enrollment_date`, `admission_type`, `previous_school`, `transfer_grade_level`, `previous_class`, `avatar`, `note`, `class_id`, `school_year_id`, `status`, `created_at`, `updated_at`) VALUES
('019f237f-316a-71d9-97bb-765763ae00b6', 'HS202611A1001', 'Huỳnh Thị Hồng Thắm', 'nu', '2009-11-30', 'Lê Bình, Cái Răng, Cần Thơ', 'TG', 'Kinh', 'Không', '0345117288', 'huynhthihongtham82608@gmail.com', '2026-07-02', 'new', NULL, NULL, NULL, NULL, NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-07-02 08:42:46', '2026-07-02 08:42:46'),
('019f2465-c5d6-7215-a117-3229fdf89d17', 'HS20260001', 'Nguyễn Văn An', 'nam', '2010-09-15', 'Phường 1, Quận 1', NULL, 'Kinh', 'Không', '0901234567', NULL, '2026-07-02', 'new', NULL, NULL, NULL, NULL, NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-07-02 12:54:37', '2026-07-02 12:54:37'),
('019f2465-c6a4-7275-8cb5-8317df8a2301', 'HS20260002', 'Trần Thị Bình', 'nu', '2009-04-20', 'Phường 2, Quận 3', NULL, 'Kinh', 'Không', '0912345678', NULL, '2026-07-02', 'transfer', 'THCS Nguyễn Du', 11, NULL, NULL, NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-07-02 12:54:37', '2026-07-02 12:54:37'),
('019f2468-5982-7096-a622-a21e0470f5b2', 'HS20260003', 'Nguyễn Văn Anh', 'nam', '2010-09-15', 'Phường 1, Quận 1', NULL, 'Kinh', 'Không', NULL, NULL, '2026-07-02', 'new', NULL, NULL, NULL, NULL, NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-07-02 12:57:26', '2026-07-02 12:57:26'),
('019f246d-7c81-7324-a077-22a7b4d1c6b9', 'HS20260004', 'Nguyễn Văn Anh Tuấn', 'nam', '2010-09-15', 'Phường 1, Quận 1', NULL, 'Kinh', 'Không', NULL, NULL, '2026-07-02', 'new', NULL, NULL, NULL, NULL, NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-07-02 13:03:02', '2026-07-02 13:03:02'),
('019f2483-c309-73ab-ace6-efc291cd2279', 'HS20260005', 'Nguyễn Văn Anh Kỳ', 'nam', '2010-09-15', 'Phường 1, Quận 1', 'TP Hồ Chí Minh', 'Kinh', 'Không', '901234567', 'ph_an@example.com', '2026-07-02', 'new', NULL, NULL, NULL, NULL, NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-07-02 13:27:22', '2026-07-02 13:27:22'),
('019f2483-c3df-722c-a54e-4e00c18ea3e4', 'HS20260006', 'Trần Thị Bình Thường', 'nu', '2009-04-20', 'Phường 2, Quận 3', 'Đồng Nai', 'Kinh', 'Không', '912345678', 'ph_binh@example.com', '2026-07-02', 'transfer', 'THCS Nguyễn Du', 11, NULL, NULL, 'Học sinh chuyển trường', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-07-02 13:27:22', '2026-07-02 13:27:22'),
('019f47e8-040e-7143-a8c5-13c56caab718', 'HS20260007', 'Phan Trung Hiếu', 'nam', '2009-05-17', 'Lê Bình, Cái Răng, Cần Thơ', 'TG', 'Kinh', 'Không', '032456789', NULL, '2026-07-09', 'new', NULL, NULL, NULL, NULL, 'không', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-07-09 10:23:35', '2026-07-09 10:23:35'),
('019f5cd8-807f-7063-92ef-45fe7adfa46a', 'HS20260008', 'Lê Minh Anh', 'nam', '2009-09-20', 'Tân Thanh, Cái Bè, Tiền Giang', 'TG', 'Kinh', 'Không', '0911222334', NULL, '2026-07-13', 'new', NULL, NULL, NULL, NULL, NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-07-13 11:58:40', '2026-07-13 11:59:21'),
('550e8400-e29b-41d4-a716-446655440051', 'HS001', 'Lê Minh Anh', 'nam', '2009-09-20', '123 Nguyễn Trãi', NULL, 'Kinh', 'Không', '0911222334', 'leminhanh@example.com', '2026-07-09', 'new', NULL, NULL, NULL, NULL, NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-06-05 15:55:17', '2026-07-10 07:27:46'),
('550e8400-e29b-41d4-a716-446655440052', 'HS002', 'Phạm Thu Hà', 'nu', '2009-06-15', '456 Lê Lợi', NULL, NULL, 'Không', '0944555666', 'phamthuhan@example.com', NULL, 'new', NULL, NULL, NULL, NULL, NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'studying', '2026-06-05 15:55:17', '2026-06-29 11:18:49');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `student_class_assignments`
--

CREATE TABLE `student_class_assignments` (
  `id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `class_id` varchar(50) NOT NULL,
  `academic_year_id` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `student_class_assignments`
--

INSERT INTO `student_class_assignments` (`id`, `student_id`, `class_id`, `academic_year_id`, `status`, `created_at`, `updated_at`) VALUES
('0137f0b1-ba4c-4822-95f9-9ce4119fc087', '019f237f-316a-71d9-97bb-765763ae00b6', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-07-02 08:42:46', '2026-07-02 08:42:46'),
('019f61fc-1c54-71e8-953b-c7f5580b9035', '019f47e8-040e-7143-a8c5-13c56caab718', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-07-14 11:55:40', '2026-07-14 11:55:40'),
('01e5e84a-e691-4730-aecc-f5f8e269fa19', '019f2465-c5d6-7215-a117-3229fdf89d17', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-07-02 12:54:37', '2026-07-02 12:54:37'),
('25317c83-af03-4d1d-8669-0e89534a651a', '019f2483-c309-73ab-ace6-efc291cd2279', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-07-02 13:27:22', '2026-07-02 13:27:22'),
('4615589c-b8cb-49e1-8b54-50c4df62c56a', '019f2465-c6a4-7275-8cb5-8317df8a2301', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-07-02 12:54:37', '2026-07-02 12:54:37'),
('67b7a026-d00e-4a20-80e7-087f8e57c5c9', '019f2483-c3df-722c-a54e-4e00c18ea3e4', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-07-02 13:27:22', '2026-07-02 13:27:22'),
('67f6f07e-dc7f-4067-b43f-1380e723b828', '019f2468-5982-7096-a622-a21e0470f5b2', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-07-02 12:57:26', '2026-07-02 12:57:26'),
('af9aa5ee-0699-46f1-9d97-c20f18a135ea', '019f246d-7c81-7324-a077-22a7b4d1c6b9', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-07-02 13:03:02', '2026-07-02 13:03:02'),
('affa3a2b-5844-4f3d-964d-87d3f630a4d6', '550e8400-e29b-41d4-a716-446655440052', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-06-05 15:55:17', '2026-06-29 11:18:49'),
('b6370915-f272-4b0f-b028-1b76f3ed711b', '550e8400-e29b-41d4-a716-446655440051', '019f149a-fbfe-728f-baa7-748afe885ca6', '019f149a-fbe8-71f3-9491-7f037342cdfa', 'active', '2026-06-05 15:55:17', '2026-06-29 11:18:49');

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

--
-- Đang đổ dữ liệu cho bảng `student_transfers`
--

INSERT INTO `student_transfers` (`id`, `student_id`, `from_class_id`, `to_class_id`, `transfer_date`, `note`) VALUES
('195b88e3-628a-4968-8a6d-9b2a5b97fa87', '019f2468-5982-7096-a622-a21e0470f5b2', NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '2026-07-02', 'Import học sinh'),
('1bcdd680-0d3e-4a1a-8461-1c3c53cf2c77', '019f47e8-040e-7143-a8c5-13c56caab718', NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '2026-07-09', 'Nhập học'),
('436b2b18-5be6-4933-b2bb-a1085750a211', '019f2483-c309-73ab-ace6-efc291cd2279', NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '2026-07-02', 'Import học sinh'),
('782615c0-1989-4b87-b03b-51497ff5b617', '019f246d-7c81-7324-a077-22a7b4d1c6b9', NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '2026-07-02', 'Import học sinh'),
('8fe90e9d-9cf8-4932-b93f-adf54c995ecd', '019f2465-c5d6-7215-a117-3229fdf89d17', NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '2026-07-02', 'Import học sinh'),
('98fceb9d-49ce-48fe-946c-4f71dc05a9a7', '019f2465-c6a4-7275-8cb5-8317df8a2301', NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '2026-07-02', 'Import học sinh'),
('b59d38b6-31c7-47d7-9a30-a533d7ef808a', '019f237f-316a-71d9-97bb-765763ae00b6', NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '2026-07-02', 'Nhập học'),
('de285bed-5562-4c2f-8925-54e4caecb274', '019f2483-c3df-722c-a54e-4e00c18ea3e4', NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '2026-07-02', 'Import học sinh'),
('df8c6c43-5b9a-4632-8c9c-9d1e7580d1ce', '019f5cd8-807f-7063-92ef-45fe7adfa46a', NULL, '019f149a-fbfe-728f-baa7-748afe885ca6', '2026-07-13', 'Nhập học');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `subjects`
--

CREATE TABLE `subjects` (
  `id` varchar(50) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `credit` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `type` varchar(20) NOT NULL DEFAULT 'required',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `is_weighted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `name`, `credit`, `type`, `status`, `is_weighted`, `created_at`, `updated_at`) VALUES
('019f24c5-5e93-7237-805c-cd74e13745dc', 'MH005', 'Hình học', 1, 'official', 'active', 0, '2026-07-02 14:39:02', '2026-07-02 14:39:34'),
('019f24c6-5c9f-7318-8245-7aa173faefb4', 'MH006', 'Hóa học', 1, 'official', 'active', 0, '2026-07-02 14:40:07', '2026-07-09 02:51:07'),
('019f24c6-8c31-735c-92a8-860852e6f6ec', 'MH007', 'Sinh học', 1, 'official', 'active', 0, '2026-07-02 14:40:19', '2026-07-02 14:40:19'),
('019f24c6-aecf-7373-a1e9-1ccb38edc09c', 'MH008', 'Lịch sử', 1, 'official', 'active', 0, '2026-07-02 14:40:28', '2026-07-02 14:40:28'),
('019f24c6-deec-73ff-9c20-40d721ebfd90', 'MH009', 'Địa Lý', 1, 'official', 'active', 0, '2026-07-02 14:40:40', '2026-07-09 02:50:50'),
('019f24c7-213e-72c5-b045-22a4c88d09a2', 'MH010', 'Tin học', 1, 'official', 'active', 0, '2026-07-02 14:40:57', '2026-07-02 14:40:57'),
('019f24c7-5682-70cc-a045-e23cd365e3f4', 'MH011', 'Công nghệ', 1, 'official', 'active', 0, '2026-07-02 14:41:11', '2026-07-09 02:50:42'),
('019f24c7-98aa-734e-bfc9-60f006acf281', 'MH012', 'Giáo dục thể chất', 1, 'official', 'active', 0, '2026-07-02 14:41:28', '2026-07-09 02:51:00'),
('019f24c7-ffb1-7173-ad2f-75aacc5effb1', 'MH013', 'GDQP-AN', 1, 'official', 'active', 0, '2026-07-02 14:41:54', '2026-07-02 14:41:54'),
('019f5cdf-10d4-7249-a152-ee35ce45776a', 'MH014', 'Chào cờ', 1, 'activity', 'active', 0, '2026-07-13 12:05:50', '2026-07-13 12:05:50'),
('550e8400-e29b-41d4-a716-446655440021', 'MH001', 'Đại số', 1, 'official', 'active', 0, '2026-06-05 15:55:17', '2026-07-02 14:38:40'),
('550e8400-e29b-41d4-a716-446655440022', 'MH002', 'Ngữ Văn', 1, 'official', 'active', 0, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440023', 'MH003', 'Tiếng Anh', 1, 'official', 'active', 0, '2026-06-05 15:55:17', '2026-06-05 15:55:17'),
('550e8400-e29b-41d4-a716-446655440024', 'MH004', 'Vật Lý', 1, 'official', 'active', 0, '2026-06-05 15:55:17', '2026-06-05 15:55:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `subject_period_norms`
--

CREATE TABLE `subject_period_norms` (
  `id` char(36) NOT NULL,
  `subject_id` varchar(50) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `periods_per_week` tinyint(3) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `subject_period_norms`
--

INSERT INTO `subject_period_norms` (`id`, `subject_id`, `grade_level`, `periods_per_week`, `created_at`, `updated_at`) VALUES
('019f1ef3-7e08-7059-91f2-80b3911c7f51', '550e8400-e29b-41d4-a716-446655440022', 10, 3, '2026-07-01 11:31:41', '2026-07-01 11:31:41'),
('019f1ef3-7e0f-70a0-97d6-c97bf5aab3de', '550e8400-e29b-41d4-a716-446655440022', 11, 4, '2026-07-01 11:31:41', '2026-07-01 11:31:41'),
('019f1ef3-7e10-72c4-b07b-1ec47138957b', '550e8400-e29b-41d4-a716-446655440022', 12, 5, '2026-07-01 11:31:41', '2026-07-01 11:31:41'),
('019f5cdf-10da-716d-b1f9-477a2b868f9c', '019f5cdf-10d4-7249-a152-ee35ce45776a', 10, 1, '2026-07-13 12:05:50', '2026-07-13 12:05:50'),
('019f5cdf-10de-7343-873e-16faca0dc06e', '019f5cdf-10d4-7249-a152-ee35ce45776a', 11, 1, '2026-07-13 12:05:50', '2026-07-13 12:05:50'),
('019f5cdf-10e0-7084-bb2f-e56b4a197afd', '019f5cdf-10d4-7249-a152-ee35ce45776a', 12, 1, '2026-07-13 12:05:50', '2026-07-13 12:05:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `system_settings`
--

CREATE TABLE `system_settings` (
  `id` char(36) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `principal_name` varchar(255) DEFAULT NULL,
  `default_school_year_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `system_settings`
--

INSERT INTO `system_settings` (`id`, `school_name`, `short_name`, `logo_path`, `address`, `phone`, `email`, `website`, `principal_name`, `default_school_year_id`, `created_at`, `updated_at`) VALUES
('019f57b8-1ad3-728d-8956-d19cc8e22020', 'Quản lý trường THPT', 'TH', 'system/77vL8B6puXgcQ3Zq5386s8vjr8SO3nxR8LGy7hA4.png', 'Lê Bình, Cái Răng, Cần Thơ', '038 608 2608', 'thpt@gmail.com', NULL, NULL, NULL, '2026-07-12 12:05:11', '2026-07-14 11:47:38');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `teachers`
--

CREATE TABLE `teachers` (
  `id` varchar(50) NOT NULL,
  `teacher_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `joined_at` date DEFAULT NULL,
  `work_status` varchar(30) NOT NULL DEFAULT 'working',
  `qualification` varchar(255) DEFAULT NULL,
  `main_subject` varchar(255) DEFAULT NULL,
  `primary_subject_id` varchar(50) DEFAULT NULL,
  `department_id` varchar(50) DEFAULT NULL,
  `is_homeroom` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_code`, `name`, `dob`, `gender`, `phone`, `email`, `address`, `joined_at`, `work_status`, `qualification`, `main_subject`, `primary_subject_id`, `department_id`, `is_homeroom`, `created_at`, `updated_at`) VALUES
('019f3330-3bae-72c8-83b6-9092a5c09656', 'GV003', 'Nguyễn Thị Hoa', '1990-07-17', 'nu', NULL, NULL, NULL, '2025-07-15', 'working', 'Đại học', 'Hóa học', '019f24c6-5c9f-7318-8245-7aa173faefb4', '5fbc6d5d-4edd-4f53-9f3f-630473dfe3be', 0, '2026-07-05 09:50:26', '2026-07-20 11:42:24'),
('019f421e-061f-70a0-97a1-780f7ebad665', 'GV004', 'Nguyễn Văn Lý', '1991-06-19', 'nam', NULL, NULL, NULL, '2025-06-10', 'working', NULL, 'vật lý', '550e8400-e29b-41d4-a716-446655440024', 'eee2409e-cc3c-4501-86e9-c2ed4ad04a63', 0, '2026-07-08 07:24:51', '2026-07-20 11:42:24'),
('550e8400-e29b-41d4-a716-446655440031', 'GV001', 'Nguyễn Văn Toàn', NULL, NULL, '0901234567', 'gvtoan@example.com', NULL, NULL, 'working', 'Đại học', 'Đại số', '550e8400-e29b-41d4-a716-446655440021', '019f80a2-613a-73f7-949c-1dda4695e72a', 1, '2026-06-05 15:55:17', '2026-07-20 11:42:24'),
('550e8400-e29b-41d4-a716-446655440032', 'GV002', 'Trần Thị Chủ Nhiệm', NULL, NULL, '0908888888', 'gvcn@example.com', NULL, NULL, 'working', 'Đại học', 'Ngữ Văn', '550e8400-e29b-41d4-a716-446655440022', '2479dab3-cbfd-49dd-9c49-991f78e9d8cb', 1, '2026-06-05 15:55:17', '2026-07-20 11:42:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `teacher_departments`
--

CREATE TABLE `teacher_departments` (
  `id` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `leader_teacher_id` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `teacher_departments`
--

INSERT INTO `teacher_departments` (`id`, `code`, `name`, `leader_teacher_id`, `description`, `status`, `created_at`, `updated_at`) VALUES
('019f80a2-613a-73f7-949c-1dda4695e72a', 'TOAN_TIN', 'Tổ Toán Tin', NULL, NULL, 'active', '2026-07-20 10:45:53', '2026-07-20 10:45:53'),
('2479dab3-cbfd-49dd-9c49-991f78e9d8cb', 'NGUVAN', 'Tổ Ngữ văn', '550e8400-e29b-41d4-a716-446655440032', 'Tổ chuyên môn phụ trách Ngữ văn.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('3cf2a7d4-54f0-4b88-88be-39ef0157e68e', 'SINHHOC', 'Tổ Sinh học', NULL, 'Tổ chuyên môn phụ trách Sinh học.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('5fbc6d5d-4edd-4f53-9f3f-630473dfe3be', 'HOAHOC', 'Tổ Hóa học', '019f3330-3bae-72c8-83b6-9092a5c09656', 'Tổ chuyên môn phụ trách Hóa học.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('6038940b-5072-413b-ae1e-9b34ee3a993b', 'DIALY', 'Tổ Địa lý', NULL, 'Tổ chuyên môn phụ trách Địa lý.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('73b5694f-26de-470e-8052-2efbbe8a21cb', 'GDCD', 'Tổ GDCD', NULL, 'Tổ chuyên môn phụ trách GDCD.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('82166818-b8bb-4964-b7a4-9438e4308558', 'THEDUC', 'Tổ Thể dục', NULL, 'Tổ chuyên môn phụ trách Thể dục.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('84274c6c-ee54-44c7-8ef9-b76c6c0ff437', 'TIENGANH', 'Tổ Tiếng Anh', NULL, 'Tổ chuyên môn phụ trách Tiếng Anh.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('aaad9562-2cba-4960-918d-f9febfc6925a', 'TOAN', 'Tổ Toán', NULL, 'Tổ chuyên môn phụ trách Toán.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('eb905b8c-d4d3-4793-a6e9-95c48fc64c17', 'LICHSU', 'Tổ Lịch sử', NULL, 'Tổ chuyên môn phụ trách Lịch sử.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('eee2409e-cc3c-4501-86e9-c2ed4ad04a63', 'VATLY', 'Tổ Vật lý', '019f421e-061f-70a0-97a1-780f7ebad665', 'Tổ chuyên môn phụ trách Vật lý.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39'),
('fd1c9fb6-29e7-49be-bf2b-841b2d480a00', 'TINHOC', 'Tổ Tin học', NULL, 'Tổ chuyên môn phụ trách Tin học.', 'active', '2026-07-20 10:38:39', '2026-07-20 10:38:39');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `teacher_department_subject`
--

CREATE TABLE `teacher_department_subject` (
  `department_id` varchar(50) NOT NULL,
  `subject_id` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `teacher_department_subject`
--

INSERT INTO `teacher_department_subject` (`department_id`, `subject_id`, `created_at`, `updated_at`) VALUES
('019f80a2-613a-73f7-949c-1dda4695e72a', '550e8400-e29b-41d4-a716-446655440021', '2026-07-20 11:13:09', '2026-07-20 11:13:09'),
('2479dab3-cbfd-49dd-9c49-991f78e9d8cb', '550e8400-e29b-41d4-a716-446655440022', '2026-07-20 11:13:09', '2026-07-20 11:13:09'),
('3cf2a7d4-54f0-4b88-88be-39ef0157e68e', '019f24c6-8c31-735c-92a8-860852e6f6ec', '2026-07-20 11:13:09', '2026-07-20 11:13:09'),
('5fbc6d5d-4edd-4f53-9f3f-630473dfe3be', '019f24c6-5c9f-7318-8245-7aa173faefb4', '2026-07-20 11:13:09', '2026-07-20 11:13:09'),
('6038940b-5072-413b-ae1e-9b34ee3a993b', '019f24c6-deec-73ff-9c20-40d721ebfd90', '2026-07-20 11:13:09', '2026-07-20 11:13:09'),
('84274c6c-ee54-44c7-8ef9-b76c6c0ff437', '550e8400-e29b-41d4-a716-446655440023', '2026-07-20 11:13:09', '2026-07-20 11:13:09'),
('eb905b8c-d4d3-4793-a6e9-95c48fc64c17', '019f24c6-aecf-7373-a1e9-1ccb38edc09c', '2026-07-20 11:13:09', '2026-07-20 11:13:09'),
('eee2409e-cc3c-4501-86e9-c2ed4ad04a63', '550e8400-e29b-41d4-a716-446655440024', '2026-07-20 11:13:09', '2026-07-20 11:13:09'),
('fd1c9fb6-29e7-49be-bf2b-841b2d480a00', '019f24c7-213e-72c5-b045-22a4c88d09a2', '2026-07-20 11:13:09', '2026-07-20 11:13:09');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `teaching_assignments`
--

CREATE TABLE `teaching_assignments` (
  `id` varchar(50) NOT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `class_id` varchar(50) NOT NULL,
  `subject_id` varchar(50) NOT NULL,
  `school_year_id` varchar(50) NOT NULL,
  `semester_id` varchar(50) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'primary',
  `custom_role` varchar(255) DEFAULT NULL,
  `weekly_periods` tinyint(3) UNSIGNED DEFAULT NULL,
  `is_homeroom_assignment` tinyint(1) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `teaching_assignments`
--

INSERT INTO `teaching_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `school_year_id`, `semester_id`, `role`, `custom_role`, `weekly_periods`, `is_homeroom_assignment`, `note`, `status`, `archived_at`, `created_at`, `updated_at`) VALUES
('019f0fef-aa5f-722b-be4c-d9003fb15b62', '550e8400-e29b-41d4-a716-446655440032', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440022', '019f08c6-f17a-7251-b397-22f3a317dd4e', NULL, 'primary', NULL, NULL, 0, NULL, 'active', NULL, '2026-06-28 13:33:12', '2026-06-28 13:33:12'),
('019f0fef-fb46-70ce-bf98-da23b3aefc17', '550e8400-e29b-41d4-a716-446655440031', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440021', '019f08c6-f17a-7251-b397-22f3a317dd4e', NULL, 'primary', NULL, NULL, 0, NULL, 'active', NULL, '2026-06-28 13:33:33', '2026-06-28 13:33:33'),
('019f1ebc-582f-7321-9440-9875c81ac28e', '550e8400-e29b-41d4-a716-446655440032', '019f149a-fbfe-728f-baa7-748afe885ca6', '550e8400-e29b-41d4-a716-446655440022', '019f149a-fbe8-71f3-9491-7f037342cdfa', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', 'primary', NULL, NULL, 0, NULL, 'active', NULL, '2026-07-01 10:31:27', '2026-07-13 09:25:25'),
('019f1ebc-87cc-7186-a626-eb10e5814f1c', '550e8400-e29b-41d4-a716-446655440031', '019f149a-fbfe-728f-baa7-748afe885ca6', '550e8400-e29b-41d4-a716-446655440021', '019f149a-fbe8-71f3-9491-7f037342cdfa', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', 'primary', NULL, NULL, 0, NULL, 'active', NULL, '2026-07-01 10:31:39', '2026-07-01 10:31:39'),
('550e8400-e29b-41d4-a716-446655440061', '550e8400-e29b-41d4-a716-446655440031', '550e8400-e29b-41d4-a716-446655440041', '550e8400-e29b-41d4-a716-446655440021', '550e8400-e29b-41d4-a716-446655440001', NULL, 'primary', NULL, NULL, 0, NULL, 'active', NULL, '2026-06-05 15:55:17', '2026-06-05 15:55:17');

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
('019f1465-84f2-72d2-ab42-335cfbb3799a', '019f12b3-8c59-7100-97a1-d4d48b07a82a', '019f12b3-ddb7-7376-bc47-75803a7ac02d', '550e8400-e29b-41d4-a716-446655440041', NULL, NULL, '2026-06-29 10:20:25', '2026-06-29 10:20:25'),
('019f1de4-05a0-7071-8d44-e0ae4acc74bd', '019f149a-fbe8-71f3-9491-7f037342cdfa', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '550e8400-e29b-41d4-a716-446655440041', NULL, NULL, '2026-07-01 06:35:10', '2026-07-01 06:35:10'),
('019f1ed8-aa43-70a5-bdf3-ae173b68fb7c', '019f149a-fbe8-71f3-9491-7f037342cdfa', '019f1d58-7ca7-73e9-b531-9f27f624f5d6', '019f149a-fbfe-728f-baa7-748afe885ca6', NULL, NULL, '2026-07-01 11:02:23', '2026-07-01 11:02:23'),
('019f24cd-00dd-71a8-91a2-d12fe578584d', '019f149a-fbe8-71f3-9491-7f037342cdfa', '019f12b3-ddb7-7376-bc47-75803a7ac02d', '019f149a-fbfe-728f-baa7-748afe885ca6', NULL, NULL, '2026-07-02 14:47:22', '2026-07-02 14:47:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `timetable_entries`
--

CREATE TABLE `timetable_entries` (
  `id` varchar(50) NOT NULL,
  `timetable_id` varchar(50) NOT NULL,
  `assignment_id` varchar(50) DEFAULT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL,
  `period` tinyint(3) UNSIGNED NOT NULL,
  `subject_id` varchar(50) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `archived_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `timetable_entries`
--

INSERT INTO `timetable_entries` (`id`, `timetable_id`, `assignment_id`, `day_of_week`, `period`, `subject_id`, `teacher_id`, `room`, `room_id`, `note`, `status`, `archived_at`) VALUES
('019f1ed9-3e0f-72e1-8b2f-7e71eb2fd1cc', '019f1ed8-aa43-70a5-bdf3-ae173b68fb7c', '019f1ebc-582f-7321-9440-9875c81ac28e', 1, 1, '550e8400-e29b-41d4-a716-446655440022', '550e8400-e29b-41d4-a716-446655440032', '1', '5f453e48-11c3-4fb1-9c89-8f9840ecfc3f', 'Giảng dạy chính', 'active', NULL);

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
  `force_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `teacher_id`, `student_id`, `parent_id`, `is_active`, `force_change_password`, `created_at`, `updated_at`) VALUES
('019e9900-c151-70b5-914b-0b7dbf8daaa6', '0345387247', '$2y$12$oZ9pR6mbTgot/JC9sOfE4eVbmDCjX9rw0JEttPWJiRnVvjEgUVX/K', 'parent', NULL, NULL, '019e9900-c04a-7057-94ba-d3b2a1f726e2', 1, 0, '2026-06-05 11:17:04', '2026-07-13 11:42:59'),
('019f099f-b907-7281-b9b9-d8c5fbbf661d', '0123798356', '$2y$12$J84XcAyH09Tk9WcHVHWiS.r7I7FS4AmY3kWqmjrETVEa7VrQq9obC', 'parent', NULL, NULL, '019f099f-b83e-73f6-8574-0b054fdd6d4d', 1, 0, '2026-06-27 08:08:10', '2026-07-09 09:32:21'),
('019f237f-3242-7384-bc07-3984badae897', 'HS202611A1001', '$2y$12$b9aBB5rBaczHlHS.jrJ2buSsT7991H8hMLrX3ZFufyjJyY8BauF6i', 'student', NULL, '019f237f-316a-71d9-97bb-765763ae00b6', NULL, 1, 0, '2026-07-02 08:42:46', '2026-07-02 08:42:46'),
('019f2465-c69c-70d4-a555-60c6a14543c5', 'HS20260001', '$2y$12$J7P0jEyxmBM.bkgOcYQpMufXUfC1R7uRQaPZgfmVOM8TV9QO46vNi', 'student', NULL, '019f2465-c5d6-7215-a117-3229fdf89d17', NULL, 1, 0, '2026-07-02 12:54:37', '2026-07-03 02:37:27'),
('019f2465-c764-7288-a284-f9028b88ad6a', 'HS20260002', '$2y$12$mNfFCdeYrfwA2yNQv.vZ6.1DWSuQPWvIdDnyXqepwi/1IRDwfbJ8K', 'student', NULL, '019f2465-c6a4-7275-8cb5-8317df8a2301', NULL, 1, 0, '2026-07-02 12:54:37', '2026-07-02 12:54:37'),
('019f2468-5a48-71c5-b742-de6e9c450e01', 'HS20260003', '$2y$12$Tf.p9SfKGaScSrAWf.ydCuDodC1Hpx/HUOkYHgG4t1RxisYWMGoba', 'student', NULL, '019f2468-5982-7096-a622-a21e0470f5b2', NULL, 1, 0, '2026-07-02 12:57:26', '2026-07-02 12:57:26'),
('019f246d-7d47-7342-a8a1-81816f98f0f2', 'HS20260004', '$2y$12$N.02GMYgSgZ.q2srEpXtS.iHpSiqxfLQvwIcHNUgVkYXDTZPMfTaq', 'student', NULL, '019f246d-7c81-7324-a077-22a7b4d1c6b9', NULL, 1, 0, '2026-07-02 13:03:03', '2026-07-02 13:03:03'),
('019f2483-c3d9-7319-8bfd-2a9c9df5db4f', 'HS20260005', '$2y$12$TF3cqPLzv6XwUkS.guUxYOtUjZVaMdi2Tr0dHGn.KrVikJmlQyiWe', 'student', NULL, '019f2483-c309-73ab-ace6-efc291cd2279', NULL, 1, 0, '2026-07-02 13:27:22', '2026-07-02 13:27:22'),
('019f2483-c4aa-7310-a30b-ff3338b55660', 'HS20260006', '$2y$12$Awtd0k.1QXyQIGiGsIXFFuDJOrAcHSBsYxe0trl.Awidc1PMf0DXi', 'student', NULL, '019f2483-c3df-722c-a54e-4e00c18ea3e4', NULL, 1, 0, '2026-07-02 13:27:23', '2026-07-02 13:27:23'),
('019f3330-3c9a-70bc-8cf7-24515aac57f7', 'GV003', '$2y$12$3yfNCIaHfyNATNVxUR1Zt.YMLtTdxL9zPTHR.PkLjJhTcN.jAfriC', 'teacher', '019f3330-3bae-72c8-83b6-9092a5c09656', NULL, NULL, 1, 1, '2026-07-05 09:50:27', '2026-07-05 09:50:27'),
('019f421e-06f7-700f-bc69-00d892f0f65b', 'GV004', '$2y$12$Cwe5RECoCV7hjRLWgKEV2eDXyxT2RqDpGOyM6Xg3xgDFxVFB7qRTa', 'teacher', '019f421e-061f-70a0-97a1-780f7ebad665', NULL, NULL, 1, 0, '2026-07-08 07:24:51', '2026-07-08 08:04:25'),
('019f47e8-04cf-7189-9f9c-516cbf7b36d9', 'HS20260007', '$2y$12$4A5Jz4/CObQl/2wRD3QwveF3qp/0svJWqzCoWZm8Bg6E2ow/43tve', 'student', NULL, '019f47e8-040e-7143-a8c5-13c56caab718', NULL, 1, 0, '2026-07-09 10:23:35', '2026-07-09 10:23:35'),
('019f47e8-0598-70d2-9de5-bff32ad4f032', '032456789', '$2y$12$IbyMxPvQbxlNp8Oevlu.Luwd1FCM7r/mYvf/yWL.C.12ipMWV9m8m', 'parent', NULL, NULL, '019f47e8-04d4-729f-b0d9-11a741f1fe33', 1, 1, '2026-07-09 10:23:35', '2026-07-09 10:23:35'),
('019f5ccb-d337-7321-9eac-2170e2466f1d', 'HS002', '$2y$12$PONFdfiN.jOeAVn21KrD2.Z/8Gk6.DFx82KIAceONNotySLgRtDo.', 'student', NULL, '550e8400-e29b-41d4-a716-446655440052', NULL, 1, 1, '2026-07-13 11:44:49', '2026-07-13 11:48:56'),
('019f5cd8-8140-71e5-aab6-5bf656ebcb7a', 'HS20260008', '$2y$12$b7gEd4QyA63doAfE03qwTOmTiU5iPZagaGfkxAzzPfZfnN.ozupC2', 'student', NULL, '019f5cd8-807f-7063-92ef-45fe7adfa46a', NULL, 1, 0, '2026-07-13 11:58:40', '2026-07-13 11:58:40'),
('019f5cd8-8209-732a-b761-e6d10e0d6991', '0345117288', '$2y$12$gpE/ykHx/Yv12iuPvcuuEeOUbXkNZn3QaBODm4H9KPpWRirFpNyUe', 'parent', NULL, NULL, '019f5cd8-8146-71aa-b20c-b86b11a9ae2a', 1, 1, '2026-07-13 11:58:40', '2026-07-13 11:58:40'),
('550e8400-e29b-41d4-a716-446655440091', 'admin', '$2y$12$pZAhesxAO.M8Y0SJhr/RKO89/Dm/xWfs.ueXmHRteqKvyzQVHGHN.', 'admin', NULL, NULL, NULL, 1, 0, '2026-06-05 15:55:17', '2026-06-05 11:33:52'),
('550e8400-e29b-41d4-a716-446655440092', 'GV001', '$2y$12$zbrHHKuR1MQ2ahE4D3n5ZOhLpLsBWX9LodbMc96TWGA9QCe2LNeA6', 'teacher', '550e8400-e29b-41d4-a716-446655440031', NULL, NULL, 1, 0, '2026-06-05 15:55:17', '2026-07-13 11:37:50'),
('550e8400-e29b-41d4-a716-446655440093', 'GV002', '$2y$12$dIUM6yni7.05hdpx4i4b3u2qPjYd5iqeovka6XmmIVgNvEV0T3K/q', 'teacher', '550e8400-e29b-41d4-a716-446655440032', NULL, NULL, 1, 0, '2026-06-05 15:55:17', '2026-07-14 12:39:45'),
('550e8400-e29b-41d4-a716-446655440094', 'hs001', '$2y$12$BVpzWv6c8M8RYRj.TYuK1eB7dm5MaaL2Ww.gtcc59gwVwwgoWjSie', 'student', NULL, '550e8400-e29b-41d4-a716-446655440051', NULL, 1, 0, '2026-06-05 15:55:17', '2026-06-05 15:55:17');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_records_student_date_session_unique` (`student_id`,`attendance_date`,`session_key`),
  ADD KEY `attendance_records_student_id_index` (`student_id`),
  ADD KEY `attendance_records_class_id_index` (`class_id`),
  ADD KEY `attendance_records_semester_id_index` (`semester_id`),
  ADD KEY `attendance_records_attendance_date_index` (`attendance_date`),
  ADD KEY `attendance_records_session_type_index` (`session_type`),
  ADD KEY `attendance_records_timetable_entry_id_index` (`timetable_entry_id`),
  ADD KEY `attendance_records_session_order_index` (`session_order`),
  ADD KEY `attendance_records_session_key_index` (`session_key`),
  ADD KEY `attendance_records_status_index` (`status`),
  ADD KEY `attendance_records_recorded_by_index` (`recorded_by`);

--
-- Chỉ mục cho bảng `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `classes_year_semester_name_unique` (`school_year_id`,`semester_id`,`name`),
  ADD KEY `classes_school_year_idx` (`school_year_id`),
  ADD KEY `classes_homeroom_fk` (`homeroom_teacher_id`),
  ADD KEY `classes_semester_id_index` (`semester_id`),
  ADD KEY `classes_status_index` (`status`);

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
-- Chỉ mục cho bảng `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_schedules_type_index` (`type`),
  ADD KEY `exam_schedules_class_id_index` (`class_id`),
  ADD KEY `exam_schedules_subject_id_index` (`subject_id`),
  ADD KEY `exam_schedules_semester_id_index` (`semester_id`),
  ADD KEY `exam_schedules_exam_date_index` (`exam_date`);

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
-- Chỉ mục cho bảng `home_page_contents`
--
ALTER TABLE `home_page_contents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `home_page_contents_key_unique` (`key`);

--
-- Chỉ mục cho bảng `learning_documents`
--
ALTER TABLE `learning_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `learning_documents_category_index` (`category`),
  ADD KEY `learning_documents_subject_id_index` (`subject_id`),
  ADD KEY `learning_documents_class_id_index` (`class_id`),
  ADD KEY `learning_documents_uploaded_by_index` (`uploaded_by`),
  ADD KEY `learning_documents_is_published_index` (`is_published`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_sender_idx` (`sender_user_id`),
  ADD KEY `messages_receiver_idx` (`receiver_user_id`),
  ADD KEY `messages_conversation_id_index` (`conversation_id`),
  ADD KEY `messages_parent_message_id_index` (`parent_message_id`);

--
-- Chỉ mục cho bảng `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_attachments_message_idx` (`message_id`);

--
-- Chỉ mục cho bảng `message_recipients`
--
ALTER TABLE `message_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_recipient_unique` (`message_id`,`receiver_user_id`),
  ADD KEY `message_recipients_receiver_idx` (`receiver_user_id`);

--
-- Chỉ mục cho bảng `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parents_parent_code_unique` (`parent_code`);

--
-- Chỉ mục cho bảng `parent_student`
--
ALTER TABLE `parent_student`
  ADD PRIMARY KEY (`parent_id`,`student_id`),
  ADD KEY `parent_student_student_fk` (`student_id`);

--
-- Chỉ mục cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Chỉ mục cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_name_unique` (`name`),
  ADD KEY `rooms_status_index` (`status`);

--
-- Chỉ mục cho bảng `school_events`
--
ALTER TABLE `school_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_events_starts_at_index` (`starts_at`),
  ADD KEY `school_events_is_published_index` (`is_published`);

--
-- Chỉ mục cho bảng `school_posts`
--
ALTER TABLE `school_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_posts_type_index` (`type`),
  ADD KEY `school_posts_published_at_index` (`published_at`),
  ADD KEY `school_posts_is_published_index` (`is_published`);

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
  ADD KEY `score_details_header_idx` (`score_header_id`),
  ADD KEY `score_details_exam_schedule_id_index` (`exam_schedule_id`);

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
-- Chỉ mục cho bảng `student_class_assignments`
--
ALTER TABLE `student_class_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_class_year_unique` (`student_id`,`academic_year_id`,`class_id`),
  ADD KEY `student_class_assignments_student_id_index` (`student_id`),
  ADD KEY `student_class_assignments_class_id_index` (`class_id`),
  ADD KEY `student_class_assignments_academic_year_id_index` (`academic_year_id`),
  ADD KEY `student_class_assignments_status_index` (`status`);

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
  ADD UNIQUE KEY `subjects_name_unique` (`name`),
  ADD UNIQUE KEY `subjects_code_unique` (`code`);

--
-- Chỉ mục cho bảng `subject_period_norms`
--
ALTER TABLE `subject_period_norms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_grade_period_norm_unique` (`subject_id`,`grade_level`),
  ADD KEY `subject_period_norms_subject_id_index` (`subject_id`);

--
-- Chỉ mục cho bảng `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `system_settings_default_school_year_id_index` (`default_school_year_id`);

--
-- Chỉ mục cho bảng `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teachers_teacher_code_unique` (`teacher_code`),
  ADD KEY `teachers_primary_subject_id_index` (`primary_subject_id`),
  ADD KEY `teachers_department_id_index` (`department_id`);

--
-- Chỉ mục cho bảng `teacher_departments`
--
ALTER TABLE `teacher_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_departments_code_unique` (`code`),
  ADD UNIQUE KEY `teacher_departments_name_unique` (`name`),
  ADD KEY `teacher_departments_leader_teacher_id_index` (`leader_teacher_id`),
  ADD KEY `teacher_departments_status_index` (`status`);

--
-- Chỉ mục cho bảng `teacher_department_subject`
--
ALTER TABLE `teacher_department_subject`
  ADD PRIMARY KEY (`department_id`,`subject_id`),
  ADD UNIQUE KEY `department_subject_subject_unique` (`subject_id`);

--
-- Chỉ mục cho bảng `teaching_assignments`
--
ALTER TABLE `teaching_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_class_subject_unique` (`teacher_id`,`class_id`,`subject_id`,`school_year_id`),
  ADD UNIQUE KEY `assignment_unique_with_role` (`teacher_id`,`class_id`,`subject_id`,`school_year_id`,`semester_id`,`role`,`custom_role`),
  ADD KEY `assignments_class_fk` (`class_id`),
  ADD KEY `assignments_subject_fk` (`subject_id`),
  ADD KEY `assignments_school_year_fk` (`school_year_id`),
  ADD KEY `teaching_assignments_semester_id_index` (`semester_id`),
  ADD KEY `teaching_assignments_role_index` (`role`),
  ADD KEY `teaching_assignments_status_index` (`status`);

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
  ADD KEY `timetable_entries_teacher_fk` (`teacher_id`),
  ADD KEY `timetable_entries_assignment_id_index` (`assignment_id`),
  ADD KEY `timetable_entries_status_index` (`status`),
  ADD KEY `timetable_entries_room_id_index` (`room_id`);

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
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

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
-- Các ràng buộc cho bảng `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD CONSTRAINT `message_attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `message_recipients`
--
ALTER TABLE `message_recipients`
  ADD CONSTRAINT `message_recipients_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_recipients_receiver_user_id_foreign` FOREIGN KEY (`receiver_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Các ràng buộc cho bảng `student_class_assignments`
--
ALTER TABLE `student_class_assignments`
  ADD CONSTRAINT `student_class_assignments_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_class_assignments_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_class_assignments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `student_transfers`
--
ALTER TABLE `student_transfers`
  ADD CONSTRAINT `transfers_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_default_school_year_id_foreign` FOREIGN KEY (`default_school_year_id`) REFERENCES `school_years` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `teacher_departments` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `teacher_departments`
--
ALTER TABLE `teacher_departments`
  ADD CONSTRAINT `teacher_departments_leader_teacher_id_foreign` FOREIGN KEY (`leader_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `teacher_department_subject`
--
ALTER TABLE `teacher_department_subject`
  ADD CONSTRAINT `teacher_department_subject_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `teacher_departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_department_subject_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `timetable_entries_assignment_fk` FOREIGN KEY (`assignment_id`) REFERENCES `teaching_assignments` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `timetable_entries_room_fk` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `timetable_entries_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
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
