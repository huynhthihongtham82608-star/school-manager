# CHANGELOG

## 2026-06-24 - Cải thiện Admin UI, bảo mật đăng nhập/đăng xuất và gộp AI

### File đã sửa

- `app/Http/Controllers/AuthController.php`
  - Đăng nhập thành công chuyển về Trang chủ hệ thống.
  - Đăng xuất xóa session, tạo lại CSRF token và chuyển về Trang chủ hệ thống.
  - Bổ sung header chống cache cho trang đăng nhập và response đăng xuất.
  - Sửa thông báo lỗi đăng nhập bằng tiếng Việt có dấu.
- `bootstrap/app.php`
  - Đăng ký middleware `no-cache`.
- `routes/web.php`
  - Áp dụng middleware `no-cache` cho toàn bộ nhóm route yêu cầu đăng nhập.
  - Không đổi tên route, controller hoặc URL đang sử dụng.
- `app/Http/Controllers/AiController.php`
  - Gộp luồng AI phân tích, AI cảnh báo, AI nhận xét vào một màn hình chung.
  - Giữ nguyên các route cũ `ai.run.form`, `ai.alerts`, `ai.reports`, `ai.run`.
- `resources/views/layouts/app.blade.php`
  - Sửa Admin sidebar: chia nhóm rõ ràng, menu gọn hơn, icon và chữ thẳng hàng.
  - Gộp 3 menu AI thành một menu `AI hỗ trợ học tập`.
  - Bổ sung meta chống cache và xử lý reload khi quay lại từ bfcache.
  - Đăng xuất từ dropdown chuyển về Trang chủ hệ thống.
- `resources/views/dashboard.blade.php`
  - Gộp các card AI riêng thành một card `AI hỗ trợ học tập`.
- `resources/views/home.blade.php`
  - Khi chưa đăng nhập: hiển thị nút `Đăng nhập`.
  - Khi đã đăng nhập: hiển thị nút `Vào hệ thống`.
  - Bổ sung meta chống cache.
- `resources/views/auth/login.blade.php`
  - Sửa tiếng Việt có dấu.
  - Bổ sung meta chống cache.
  - Đồng bộ giao diện theo màu cam chủ đạo.
- `resources/views/ai/index.blade.php`
  - Tạo giao diện AI hợp nhất với 3 tab: Phân tích, Cảnh báo, Nhận xét.
- `resources/views/ai/alerts.blade.php`
- `resources/views/ai/reports.blade.php`
- `resources/views/ai/run.blade.php`
  - Giữ lại để tương thích view cũ, chuyển sang dùng giao diện AI hợp nhất.
- `public/css/school-ui.css`
  - Tăng chiều rộng sidebar Admin.
  - Cho menu Admin tự xuống dòng khi tên dài.
  - Tăng khoảng cách giữa các menu.
  - Căn icon và chữ theo lưới ổn định.
  - Thêm style cho tab AI.

### File đã tạo

- `app/Http/Middleware/NoCacheHeaders.php`
  - Thêm header `Cache-Control: no-store`, `Pragma: no-cache`, `Expires` và `Surrogate-Control` cho các trang yêu cầu đăng nhập.
- `resources/views/ai/index.blade.php`
  - Màn hình AI hỗ trợ học tập hợp nhất.

### Chức năng đã thay đổi

- Luồng truy cập:
  - Truy cập website vào Trang chủ hệ thống.
  - Nhấn Đăng nhập mới vào trang đăng nhập.
  - Đăng nhập thành công quay về Trang chủ, sau đó người dùng bấm `Vào hệ thống` để vào đúng dashboard theo role.
  - Đăng xuất quay về Trang chủ, không quay về trang đăng nhập.
- Bảo mật phiên đăng nhập:
  - Session cũ bị xóa khi đăng xuất.
  - CSRF token được tạo lại sau đăng xuất.
  - Các trang yêu cầu đăng nhập có header chống cache.
  - Khi dùng nút Back, trang đăng nhập yêu cầu reload thay vì dùng bản cache cũ.
- Admin UI:
  - Sidebar Admin dễ đọc hơn, không tràn chữ trên một dòng.
  - Menu được chia nhóm: Tổng quan, Học vụ, Cổng thông tin, Theo dõi.
  - Icon và chữ căn đều, khoảng cách menu rộng hơn.
- AI:
  - Không còn 3 menu riêng `AI phân tích`, `AI cảnh báo`, `AI nhận xét`.
  - Chỉ còn một menu `AI hỗ trợ học tập`.
  - Bên trong có tab `Phân tích`, `Cảnh báo`, `Nhận xét`.

### Lỗi đã khắc phục

- Sửa lỗi đăng xuất rồi đăng nhập tài khoản khác nhưng bấm Back có thể thấy lại trang của tài khoản trước.
- Sửa lỗi Admin sidebar bị tràn chữ và khó đọc.
- Sửa lỗi menu AI bị phân tán thành nhiều mục riêng.
- Sửa một số chuỗi tiếng Việt bị lỗi font ở layout, login và các màn hình auth/AI mới.

### Cách kiểm tra

1. Kiểm tra luồng Trang chủ:
   - Mở `/`.
   - Xác nhận hiển thị Landing Page.
   - Khi chưa đăng nhập, nút chính là `Đăng nhập`.
2. Kiểm tra đăng nhập:
   - Nhấn `Đăng nhập`.
   - Đăng nhập bằng tài khoản hợp lệ.
   - Xác nhận được chuyển về `/` và thấy nút `Vào hệ thống`.
   - Nhấn `Vào hệ thống` để vào dashboard theo role.
3. Kiểm tra đăng xuất:
   - Từ tài khoản bất kỳ, nhấn `Đăng xuất`.
   - Xác nhận quay về Landing Page, không quay về `/login`.
4. Kiểm tra lỗi Back:
   - Đăng nhập Admin.
   - Đăng xuất.
   - Đăng nhập Student.
   - Ở trang Student, nhấn Back của trình duyệt.
   - Xác nhận không quay lại được nội dung Admin đã đăng nhập trước đó.
5. Kiểm tra header chống cache:
   - Mở trang cần đăng nhập, ví dụ `/dashboard`.
   - Kiểm tra response có `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`.
6. Kiểm tra Admin sidebar:
   - Đăng nhập Admin.
   - Mở Dashboard.
   - Xác nhận sidebar không tràn chữ, mục dài tự xuống dòng, icon và chữ thẳng hàng.
7. Kiểm tra AI hợp nhất:
   - Mở menu `AI hỗ trợ học tập`.
   - Xác nhận chỉ có một menu AI.
   - Kiểm tra 3 tab `Phân tích`, `Cảnh báo`, `Nhận xét`.
   - Với Admin/GVCN/Staff, tab `Phân tích` có form chạy phân tích.
   - Với Học sinh/Phụ huynh, chỉ xem được các tab phù hợp theo dữ liệu được cấp quyền.

### Kiểm tra kỹ thuật đã chạy

- `php artisan view:cache`
- `php artisan route:list --path=ai`
- `php -l app/Http/Controllers/AuthController.php`
- `php -l app/Http/Controllers/AiController.php`
- `php -l app/Http/Middleware/NoCacheHeaders.php`
