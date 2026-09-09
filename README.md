# School Manager

School Manager là hệ thống quản lý trường THPT xây bằng Laravel, Bootstrap 5 và MySQL/MariaDB. Ứng dụng phục vụ các vai trò Admin, nhân viên, giáo viên bộ môn, giáo viên chủ nhiệm, học sinh và phụ huynh.

## Yêu cầu môi trường

- PHP 8.2 trở lên.
- Composer.
- MySQL hoặc MariaDB, phù hợp với XAMPP/Laragon.
- Các extension PHP thường dùng: `pdo_mysql`, `openssl`, `mbstring`, `fileinfo`.
- Trình duyệt hiện đại để dùng giao diện quản trị.

## Cài đặt local

```bash
cd C:\xampp\htdocs\school-manager
composer install
copy .env.example .env
php artisan key:generate
```

Cấu hình database trong `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_manager
DB_USERNAME=root
DB_PASSWORD=
```

Sau đó tạo database `school_manager` trong MySQL/MariaDB rồi chọn một trong hai cách:

- Cài mới bằng migration/seed:

```bash
php artisan migrate --seed
```

- Hoặc import file SQL mẫu nếu cần khôi phục đúng dữ liệu demo:

```bash
mysql -u root school_manager < database/school_manager.sql
```

Chạy ứng dụng:

```bash
php artisan serve
```

Khi dùng XAMPP trực tiếp, trỏ trình duyệt tới thư mục `public/` hoặc cấu hình virtual host về `public`.

## Cấu hình kiểm thử

Project dùng database test riêng:

```dotenv
APP_ENV=testing
DB_CONNECTION=mysql
DB_DATABASE=school_manager_testing
SESSION_DRIVER=array
GEMINI_API_KEY=
```

Không chạy test trực tiếp trên database `school_manager`. Trước khi test, cần bảo đảm MySQL/MariaDB đang chạy và `school_manager_testing` đã được chuẩn bị.

Chạy test:

```bash
php artisan test --env=testing
```

## Gemini Chatbot

Chatbot dùng cấu hình qua biến môi trường, không hardcode API key trong source:

```dotenv
GEMINI_API_KEY=
GEMINI_ENDPOINT=https://generativelanguage.googleapis.com/v1beta
GEMINI_MODEL=gemini-flash-lite-latest
GEMINI_MODEL_PRIMARY=gemini-flash-lite-latest
GEMINI_MODEL_FALLBACK=gemini-3.7-flash
GEMINI_CONNECT_TIMEOUT=5
GEMINI_TIMEOUT=12
GEMINI_CA_BUNDLE=
```

Nếu chạy trên localhost bị lỗi chứng chỉ, cấu hình `GEMINI_CA_BUNDLE` tới file CA hợp lệ của PHP/XAMPP.

## Nhóm chức năng chính

- Quản lý năm học, học kỳ, lớp học và chuyển lớp.
- Quản lý học sinh, giáo viên, phụ huynh, tài khoản và phân quyền RBAC.
- Quản lý tổ chuyên môn, môn học, phòng học, phân công giảng dạy.
- Quản lý thời khóa biểu, lịch kiểm tra, điểm số, điểm danh, hạnh kiểm.
- Quản lý khen thưởng, học phí, lịch dạy thay.
- Quản lý thông báo, sự kiện, tài liệu học tập, tin nhắn nội bộ.
- Báo cáo tổng hợp và chatbot học vụ.

## Ghi chú an toàn

- Không commit file `.env`, file SQL backup riêng, API key hoặc mật khẩu thật.
- Không chạy `migrate:fresh`, `db:wipe`, restore hoặc seed trên database thật khi chưa backup.
- Database thật mặc định là `school_manager`; database kiểm thử là `school_manager_testing`.

## Tài liệu phục vụ tiểu luận

Các tài liệu phân tích hệ thống được đặt tại `docs/thesis/`:

- `system-overview.md`
- `roles-and-usecases.md`
- `database-design.md`
- `business-flows.md`
- `testing-and-limitations.md`
