# Quản lý trường THPT (Laravel + Bootstrap 5)

Website quản lý trường THPT: phân quyền Admin/Giáo viên/GVCN/Học sinh, CRUD danh mục, phân công, nhập điểm hệ số, hạnh kiểm và báo cáo thống kê.

## Yêu cầu môi trường
- PHP 8.2+ (XAMPP/Laragon ok) + Composer
- MySQL 5.7/8.0 (database: `school_manager`)
- PHP `openssl`, `pdo_mysql`, `mbstring`

## Cài đặt nhanh (localhost)
```bash
cd school-manager
composer install   # nếu chưa chạy
cp .env.example .env
# chỉnh DB_* trong .env theo MySQL của bạn (root, password…)
php artisan key:generate
php artisan migrate --seed
# hoặc import file SQL: database/school_manager.sql
php artisan serve   # hoặc cấu hình vhost XAMPP/Laragon trỏ vào public/
```

## Tài khoản demo
- Admin: `admin / admin123`
- GV Toán: `gvtoan / gv123`
- GVCN 10A1: `gvcn10a1 / gv123`
- HS001: `hs001 / hs123`

## Chức năng chính
- Đăng nhập phiên (Auth::attempt), phân quyền middleware `role`.
- CRUD: năm học, học kỳ (mở/khóa nhập điểm), lớp (GVCN, sĩ số), môn (hệ số 2), giáo viên, học sinh.
- Phân công giảng dạy GV–Lớp–Môn–Năm học.
- Khóa/mở kỳ nhập điểm theo lớp/môn/học kỳ (grade window).
- Nhập điểm dạng bảng (miệng/15p/1 tiết/giữa kỳ/cuối kỳ) bằng chuỗi nhiều giá trị, tự tính TB theo hệ số HS1=1, HS2=2, HS3=3.
- GVCN nhập hạnh kiểm + nhận xét theo học kỳ.
- Báo cáo tổng kết lớp: TB, xếp loại học lực (>=8 Giỏi, >=6.5 Khá, >=5 TB), hạnh kiểm, tỷ lệ Giỏi/Khá/TB/Yếu.
- Dashboard theo role, sidebar Bootstrap 5 responsive.

## Cấu trúc chính
- Migrations: `database/migrations/` (users, school_years, semesters, classes, students, teachers, subjects, teaching_assignments, score_headers/details, conducts, grade_windows).
- Models: `app/Models/*` (SchoolClass, SchoolYear, Semester, Subject, Teacher, Student, TeachingAssignment, ScoreHeader/Detail, Conduct, GradeWindow).
- Controllers/Views: `app/Http/Controllers`, `resources/views` (layout + các trang CRUD, nhập điểm, báo cáo).
- Seed + SQL mẫu: `database/seeders/DatabaseSeeder.php`, `database/school_manager.sql`.

## Ghi chú vận hành
- Mặc định session dùng database (`sessions` table). Kiểm tra `SESSION_DRIVER=database` trong .env.
- Khi cần mở/khóa nhập điểm: trang `grade-windows` hoặc bật/tắt nhanh ở Học kỳ (`is_score_input_open`).
- Nếu chạy artisan không kết nối MySQL, kiểm tra lại DB host/port và quyền user.

## Phần mở rộng gợi ý
- Import/Export Excel/PDF, lịch sử điểm, phân quyền chi tiết hơn, gửi SMS/Email phụ huynh, audit log.
