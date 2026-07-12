# Báo cáo hoàn thiện module Giáo viên

## File đã sửa

- `app/Http/Controllers/TeacherController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/TeacherPortalController.php`
- `app/Http/Controllers/ScoreController.php`
- `app/Http/Controllers/AttendanceController.php`
- `app/Http/Controllers/LearningDocumentController.php`
- `app/Http/Controllers/MessageController.php`
- `resources/views/dashboard.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/teachers/create.blade.php`
- `resources/views/teachers/edit.blade.php`
- `resources/views/teachers/classes.blade.php`
- `resources/views/teachers/class-students.blade.php`
- `resources/views/documents/index.blade.php`
- `resources/views/messages/create.blade.php`
- `routes/web.php`
- `database/seeders/DatabaseSeeder.php`

## Migration mới

- `database/migrations/2026_07_08_000002_sync_teacher_usernames_to_teacher_codes.php`
- `database/migrations/2026_07_08_000003_reset_teacher_passwords_to_default.php`

Các migration này đồng bộ `users.username` của tài khoản giáo viên theo `teachers.teacher_code`, reset mật khẩu giáo viên về `12345678` và bật trạng thái bắt buộc đổi mật khẩu. Không tạo bảng mới và không xóa dữ liệu.

## Bảng dữ liệu thay đổi

- `users`: dữ liệu local đã được cập nhật để giáo viên đăng nhập bằng mã giáo viên, dùng mật khẩu mặc định `12345678` và `force_change_password = true`.
- Không thêm bảng mới.
- Không thay đổi cấu trúc các bảng nghiệp vụ hiện có.

## Chức năng đã hoàn thành

- Chuẩn hóa đăng nhập giáo viên bằng mã giáo viên.
- Khi tạo giáo viên, tài khoản đăng nhập tự động lấy theo mã giáo viên.
- Khi tạo giáo viên, hệ thống tự tạo mật khẩu mặc định `12345678`, hash bằng Laravel và bắt buộc đổi mật khẩu lần đầu.
- Khi sửa mã giáo viên, username liên kết được đồng bộ theo mã mới.
- Form thêm/sửa giáo viên không còn cho Admin nhập tên đăng nhập hoặc mật khẩu.
- Bảng danh sách giáo viên đã bỏ cột Tài khoản.
- Chức năng Đặt lại mật khẩu reset về `12345678` và bật bắt buộc đổi mật khẩu.
- Seeder mẫu dùng `GV001`, `GV002` làm username giáo viên và mật khẩu mặc định `12345678`.
- Dashboard giáo viên hiển thị lời chào, số lớp đang dạy, số tiết hôm nay, thời khóa biểu hôm nay, thông báo mới, lịch thi gần nhất và lớp chủ nhiệm nếu có.
- Bổ sung trang `Lớp đang giảng dạy` cho giáo viên.
- Giáo viên xem được danh sách học sinh của lớp mình dạy hoặc lớp chủ nhiệm.
- Hồ sơ học sinh trong lớp hiển thị ở chế độ chỉ xem.
- Nhập điểm chỉ cho phép theo lớp, môn, học kỳ đã được phân công.
- Điểm danh cho phép giáo viên điểm danh lớp mình dạy nếu ngày đó có tiết học trong thời khóa biểu.
- GVCN vẫn được điểm danh lớp chủ nhiệm.
- Giáo viên được upload, sửa, xóa tài liệu do mình tạo trong phạm vi môn/lớp được phân công.
- Tin nhắn của giáo viên chỉ hiển thị người nhận phù hợp: Admin, nhân viên, giáo viên/GVCN và học sinh thuộc lớp mình dạy hoặc chủ nhiệm.
- GVCN có thêm các ô chức năng trên dashboard: lớp chủ nhiệm, theo dõi điểm toàn lớp, theo dõi điểm danh, hạnh kiểm, sổ chủ nhiệm và liên hệ phụ huynh.

## Chức năng chưa làm

- Chưa tách riêng một module Sổ chủ nhiệm có bảng dữ liệu độc lập. Hiện tại sử dụng dữ liệu lớp, hạnh kiểm, điểm danh, báo cáo lớp và tin nhắn hiện có.
- Chưa mở rộng REST API riêng cho toàn bộ chức năng giáo viên. API hiện tại mới có xác thực.

## Kiểm tra đã thực hiện

- Kiểm tra cú pháp PHP các controller và route đã sửa.
- Cache Blade thành công bằng `php artisan view:cache`.
- Kiểm tra route và middleware cho `teacher/classes`, `documents`, `attendance`, `scores`, `messages`.
- Kiểm tra dữ liệu local: tài khoản giáo viên đang có username là mã giáo viên.
- Kiểm tra API login `GV001 / 12345678` trả 200, có token và `must_change_password = true`.
- Kiểm tra dữ liệu local: toàn bộ user role `teacher` có `force_change_password = 1`.
- `php artisan test` chưa pass hoàn toàn do môi trường test đang thiếu SQLite PDO driver; lỗi phát sinh ở test mẫu `GET /`, không phải lỗi module giáo viên.

## Lưu ý trước khi phát triển module Học sinh

- Module Học sinh nên dùng lại nguyên tắc định danh cố định giống module Giáo viên: không đổi mã sau khi tạo.
- Các trang học sinh cần tách rõ chế độ xem của học sinh, phụ huynh và giáo viên.
- Nếu phát triển API Android cho học sinh, nên dùng chung `ApiAuth::userPayload()` và bổ sung endpoint theo vai trò.
- Nếu muốn hoàn thiện sâu Sổ chủ nhiệm, nên thiết kế bảng riêng cho ghi chú chủ nhiệm, nhận xét định kỳ và trao đổi với phụ huynh.
