# Tổng quan hệ thống School Manager

## Kiến trúc

School Manager là ứng dụng web Laravel chạy theo mô hình MVC:

- `routes/web.php` định nghĩa route web có session, CSRF, role middleware và permission middleware.
- `routes/api.php` định nghĩa API JSON, hiện có API đăng nhập v1 và API chatbot dùng Sanctum.
- `app/Http/Controllers` xử lý nghiệp vụ từng phân hệ.
- `app/Models` ánh xạ dữ liệu Eloquent.
- `resources/views` chứa giao diện Blade dùng Bootstrap 5, CSS tùy biến trong `public/css/school-ui.css`.
- `app/Services` chứa logic dùng chung như đánh giá học lực, RBAC protection và chatbot.

## Công nghệ thực tế

- PHP 8.2+.
- Laravel 12.
- MySQL/MariaDB.
- Laravel Sanctum cho API token.
- Bootstrap 5, Bootstrap Icons và CSS nội bộ.
- Gemini API cho chatbot, cấu hình qua `.env`.

## Nhóm chức năng đã triển khai

- Xác thực, đổi mật khẩu, khóa đăng nhập và phân quyền RBAC.
- Quản lý năm học, học kỳ, lớp học, chuyển lớp và khởi tạo năm học.
- Quản lý học sinh, giáo viên, phụ huynh, tài khoản quản trị.
- Quản lý môn học, tổ chuyên môn, phòng học, phân công giảng dạy.
- Quản lý thời khóa biểu, lịch kiểm tra, lịch dạy thay.
- Quản lý điểm số, cấu hình cột điểm, học bạ và báo cáo.
- Quản lý điểm danh, đơn xin nghỉ, hạnh kiểm và khen thưởng.
- Quản lý học phí, cấu hình mức thu và cổng phụ huynh/GVCN.
- Quản lý thông báo, sự kiện, tài liệu học tập.
- Tin nhắn nội bộ có người nhận, trạng thái đọc, trả lời, thùng rác và đính kèm.
- Chatbot web/API có lịch sử hội thoại, registry tool, phân quyền theo vai trò và lọc dữ liệu theo scope.

## Hoàn thành, hạn chế và chưa xác minh

Hoàn thành ở mức phục vụ demo:

- Các route chính đã được khai báo và `php artisan route:list` đọc được 238 route.
- UI chính có Blade riêng cho các phân hệ quản trị, giáo viên, học sinh và phụ huynh.
- Chatbot không expose raw SQL; Gemini chỉ được gọi qua client/service và tool registry.
- RBAC vai trò tùy chỉnh có thể bật/tắt qua form chỉnh sửa và làm mới quyền hiệu lực của user.

Hạn chế thực tế:

- Migration chain từng được audit là không reproducible từ zero. Cách an toàn hiện tại là dùng database clone `school_manager_testing` hoặc SQL mẫu khi kiểm thử.
- Một số test feature cần MySQL/MariaDB chạy; khi DB local tắt, test bị lỗi kết nối chứ không phản ánh lỗi nghiệp vụ.
- Gemini phụ thuộc API key, mạng và quota bên ngoài.

Chưa nên khẳng định nếu chưa kiểm thử lại trên trình duyệt:

- Toàn bộ luồng upload/download file trên từng môi trường.
- Export PDF/Excel với dữ liệu lớn.
- Hiệu năng cuối cùng ở danh sách nhiều bản ghi.
