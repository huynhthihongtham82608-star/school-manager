# CHANGELOG_UI.md

## 2026-06-24 - Thiết kế lại Admin UI theo phong cách Học sinh

### File đã sửa

- `resources/views/layouts/app.blade.php`
  - Bỏ bố cục Sidebar Admin dạng accordion cũ.
  - Thiết kế Sidebar Admin theo phong cách giống menu Học sinh.
  - Mỗi chức năng là một nút riêng, nằm trong một dòng riêng.
  - Bọc từng menu bằng khối riêng để không thể dồn nhiều chức năng vào cùng một hàng.
  - Thêm version cho `school-ui.css` để tránh trình duyệt dùng lại CSS cũ.
- `resources/views/dashboard.blade.php`
  - Thiết kế lại Dashboard Admin.
  - Bỏ các thẻ thống kê dạng thanh dài.
  - Bỏ các panel phụ dạng danh sách văn bản.
  - Chuyển toàn bộ chức năng quản trị chính thành các ô lớn giống Dashboard Học sinh.
  - Mỗi ô có icon, tiêu đề, mô tả ngắn và số liệu nếu có.
  - Hiển thị 4 ô mỗi hàng trên màn hình lớn, 2 ô trên tablet và 1 ô trên điện thoại.
- `resources/views/ai/index.blade.php`
  - Chuyển tab AI từ hyperlink sang button trong form GET.
  - Chỉ hiển thị 3 nút lớn: `Phân tích`, `Cảnh báo`, `Nhận xét`.
  - Xóa khối mô tả/giới thiệu ở đầu trang AI.
  - Xóa phần ghi chú giải thích trong tab Phân tích.
  - Chỉ hiển thị nội dung của tab đang được chọn.
- `public/css/school-ui.css`
  - Đồng bộ Sidebar Admin với phong cách menu Học sinh.
  - Thêm style card lớn cho Dashboard Admin.
  - Thêm style button lớn cho AI hỗ trợ học tập.
  - Ép sidebar, card và nút AI không hiển thị kiểu hyperlink mặc định.
  - Cập nhật responsive cho sidebar, dashboard và AI.

### Sidebar Admin mới

- Tổng quan
  - Dashboard
- Quản lý học vụ
  - Năm học
  - Học kỳ
  - Lớp học
  - Môn học
  - Phân công giảng dạy
  - Thời khóa biểu
  - Điểm số
  - Hạnh kiểm
  - Điểm danh
- Quản lý người dùng
  - Học sinh
  - Giáo viên
  - Phụ huynh
- Nội dung hệ thống
  - Quản lý trang chủ
  - Thông báo
  - Sự kiện
  - Tài liệu học tập
  - Lịch thi
  - Tin nhắn
- AI hỗ trợ
  - AI hỗ trợ học tập
  - Chatbot hỗ trợ
- Báo cáo
  - Báo cáo
  - Nhật ký hoạt động
- Cài đặt
  - Khóa nhập điểm
  - Hồ sơ cá nhân
  - Đổi mật khẩu

### Ghi chú

- Không thay đổi database.
- Không thay đổi route.
- Không thay đổi controller.
- Không thay đổi model.
- Không thay đổi logic nghiệp vụ.
- Chỉ chỉnh giao diện và điều hướng hiển thị trong Blade/CSS.
- Đã kiểm tra để không còn menu ghép nhiều chức năng trên cùng một dòng.
- Đã kiểm tra để không còn khối giới thiệu và nội dung mô tả AI cũ.
- Đã kiểm tra `php artisan view:cache` thành công.
