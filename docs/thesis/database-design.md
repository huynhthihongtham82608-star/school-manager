# Thiết kế dữ liệu

## Bảng vật lý trong SQL mẫu

Theo `database/school_manager.sql`, các bảng chính gồm:

- `users`
- `students`
- `teachers`
- `parents`
- `parent_student`
- `school_years`
- `semesters`
- `classes`
- `student_class_assignments`
- `student_transfers`
- `subjects`
- `subject_period_norms`
- `teacher_departments`
- `teacher_department_subject`
- `rooms`
- `teaching_assignments`
- `timetables`
- `timetable_entries`
- `score_headers`
- `score_details`
- `grade_windows`
- `attendance_records`
- `conducts`
- `exam_schedules`
- `messages`
- `message_recipients`
- `message_attachments`
- `home_page_contents`
- `school_posts`
- `school_events`
- `learning_documents`
- `system_settings`
- `audit_logs`
- `personal_access_tokens`
- `migrations`

Các migration mới trong source còn có các phân hệ mở rộng:

- `rewards`
- `tuition_fees`
- `substitute_teachings`
- `settings`
- `score_columns`
- `score_settings`
- `subject_grade_mappings`
- `parent_leave_requests`
- `rbac_roles`
- `rbac_permissions`
- `rbac_permission_role`
- `rbac_role_user`
- `chatbot_messages`

## Quan hệ dữ liệu chính

- `users` liên kết hồ sơ qua `teacher_id`, `student_id`, `parent_id` hoặc các cột hồ sơ hợp nhất.
- `parents` và `students` liên kết nhiều-nhiều qua `parent_student`.
- `classes` thuộc `school_years`, có thể có `semester_id` và `homeroom_teacher_id`.
- `students` thuộc lớp hiện hành qua `class_id`; lịch sử nằm ở `student_class_assignments` hoặc `student_transfers`.
- `teaching_assignments` nối giáo viên, lớp, môn, năm học và học kỳ.
- `timetables` thuộc lớp/năm học/học kỳ; `timetable_entries` chứa tiết, ngày, môn, giáo viên, phòng.
- `score_headers` là đầu phiếu điểm theo học sinh/lớp/môn/kỳ; `score_details` chứa điểm thành phần.
- `attendance_records` ghi nhận chuyên cần theo ngày, buổi hoặc tiết; `recorded_by` tham chiếu người ghi nhận.
- `conducts`, `rewards`, `tuition_fees` gắn học sinh, lớp, học kỳ/năm học tùy cấu trúc hiện tại.
- `messages` có người gửi; `message_recipients` lưu từng người nhận và trạng thái đọc riêng.
- `rbac_roles`, `rbac_permissions` và bảng pivot quản lý quyền động cho tài khoản staff/admin phụ.

## Ghi chú legacy và schema hiện tại

Source có các migration “consolidate/restore” năm 2026-08, thể hiện quá trình chuyển đổi giữa bảng chuẩn hóa và bảng gộp. Vì vậy migration chain hiện tại đã được ghi nhận có rủi ro không dựng lại sạch từ zero. Khi mô tả PDM, nên ưu tiên schema vật lý hiện đang chạy hoặc SQL mẫu/clone test thay vì chỉ đọc migration riêng lẻ.

## Nhóm lớp đề xuất cho sơ đồ lớp

- Nhóm tài khoản: `User`, `RbacRole`, `RbacPermission`.
- Nhóm nhân sự: `Teacher`, `TeacherDepartment`.
- Nhóm người học: `Student`, `ParentProfile`, `StudentClassAssignment`.
- Nhóm đào tạo: `SchoolYear`, `Semester`, `SchoolClass`, `Subject`, `TeachingAssignment`.
- Nhóm lịch: `Timetable`, `TimetableEntry`, `ExamSchedule`, `Room`, `SubstituteTeaching`.
- Nhóm kết quả: `ScoreHeader`, `ScoreDetail`, `ScoreColumn`, `Conduct`, `Reward`.
- Nhóm vận hành: `TuitionFee`, `AttendanceRecord`, `ParentLeaveRequest`, `Message`.
- Nhóm nội dung: `SchoolPost`, `SchoolEvent`, `LearningDocument`, `HomePageContent`.
