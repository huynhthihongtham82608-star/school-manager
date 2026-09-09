# Kiểm thử và hạn chế

## Kiểm thử cuối

Môi trường kiểm thử:

- `APP_ENV=testing`.
- Database kiểm thử: `school_manager_testing`.
- Database vận hành: `school_manager`, không dùng cho automated test.

Kết quả regression gần nhất:

- `php artisan test --env=testing`: PASS, 62 tests, 327 assertions.
- `php artisan view:cache`: PASS.
- `php artisan view:clear`: PASS.
- `git diff --check`: PASS.

Các nhóm đã được kiểm tra bằng feature/unit test:

- Auth, logout, khóa đăng nhập và RBAC.
- Quản lý học sinh, giáo viên, phụ huynh, vai trò và quyền.
- Lớp học, xếp/chuyển học sinh, niên khóa, năm học và học kỳ.
- Phân công giảng dạy, thời khóa biểu, phòng học, lịch kiểm tra và dạy thay.
- Điểm số, cấu hình cột điểm, công thức tổng hợp và scope giáo viên.
- Điểm danh theo ngày, tuần, nhật ký, sáng/chiều, theo tiết và đơn xin nghỉ đã duyệt.
- Hạnh kiểm, khen thưởng, học phí.
- Nội dung, tin nhắn, báo cáo.
- Portal học sinh, phụ huynh, giáo viên bộ môn và giáo viên chủ nhiệm.
- Chatbot Gemini, function calling, history, sanitization, scope theo vai trò và các tình huống lỗi 429/503/timeout/missing key.

## Hạn chế còn tồn tại

- Migration chain legacy từng có rủi ro dựng mới từ zero; môi trường kiểm thử hiện dùng database `school_manager_testing` được chuẩn bị riêng để bám theo schema vận hành.
- Migration mới nhất `2026_09_07_000001_fix_teaching_assignment_semester_unique_index.php` đã được áp dụng cho database vận hành sau khi backup, chỉ thay đổi unique index của `teaching_assignments`.
- Gemini phụ thuộc API key, mạng, quota và cấu hình CA bundle bên ngoài.
- Một số kiểm tra UI cuối cùng vẫn nên được thao tác thủ công trên trình duyệt demo thật để xác nhận cảm giác sử dụng.

## Đánh giá phục vụ khóa luận

Hệ thống đã có đầy đủ nhóm chức năng quản lý trường học ở mức demo khóa luận. Sau vòng kiểm thử cuối, có thể khóa code để chuyển sang hoàn thiện tài liệu luận văn và UML.
