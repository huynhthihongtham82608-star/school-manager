# Nền tảng API cho School Manager

## Hiện trạng dự án

- Dự án dùng Laravel 12 theo mô hình MVC.
- Web Admin đang dùng `routes/web.php`, controller trong `app/Http/Controllers`, view Blade trong `resources/views`.
- Model Eloquent nằm trong `app/Models` và đang dùng chung database hiện có.
- Authentication hiện tại dùng guard `web` theo session, đăng nhập bằng `username`, `password_hash`, `is_active`.
- Middleware hiện có: `role` để kiểm tra vai trò và `no-cache` để chống cache trang yêu cầu đăng nhập.

## Cấu trúc API đã chuẩn bị

API được đặt theo chuẩn Laravel để dùng chung framework, Model và Database với Web Admin:

```text
routes/
  api.php

app/
  Http/
    Controllers/
      Api/
        V1/
          ApiController.php
          Auth/
          SchoolYears/
          Semesters/
          Classes/
          Subjects/
          Teachers/
          Students/
          Assignments/
          Schedules/
          Exams/
          Attendance/
          Scores/
          Conduct/
          Announcements/
          Events/
          Documents/
          Messages/
          Ai/
          Chatbot/
    Middleware/
      ForceJsonResponse.php
      EnsureApiRole.php
  Support/
    Api/
      ApiResponse.php
      ApiAuth.php
```

## Nguyên tắc triển khai API sau này

- Không viết lại dự án.
- Không tạo database riêng cho Android.
- API dùng lại các Model hiện có trong `app/Models`.
- Web Admin tiếp tục chạy qua `routes/web.php`.
- Android gọi các endpoint dưới tiền tố `/api/v1`.
- Controller API mới kế thừa `App\Http\Controllers\Api\V1\ApiController`.
- Phản hồi API dùng `App\Support\Api\ApiResponse` để thống nhất JSON.
- Phân quyền API dùng middleware `api.role` khi cần giới hạn vai trò.

## Các module API sẽ triển khai sau

- Auth: đăng nhập, đăng xuất, hồ sơ người dùng.
- Học vụ: năm học, học kỳ, lớp học, môn học, phân công, thời khóa biểu, lịch thi.
- Người dùng: học sinh, giáo viên, phụ huynh.
- Theo dõi học tập: điểm số, hạnh kiểm, điểm danh.
- Nội dung: thông báo, sự kiện, tài liệu học tập.
- Giao tiếp: tin nhắn.
- AI: AI hỗ trợ học tập.
- Chatbot: trợ lý hỏi đáp.

## Lưu ý về xác thực API

API Authentication đang sử dụng Laravel Sanctum để cấp Bearer Token riêng cho Android. Web Admin vẫn sử dụng session guard `web` như hiện tại.

Các endpoint đã có:

```text
POST /api/v1/login
POST /api/v1/logout
GET  /api/v1/profile
```

Android gửi token trong header:

```text
Authorization: Bearer <token>
Accept: application/json
```

Ví dụ đăng nhập:

```http
POST /api/v1/login
Accept: application/json
Content-Type: application/json

{
  "username": "student",
  "password": "password",
  "device_name": "android"
}
```

Response thành công:

```json
{
  "success": true,
  "message": "Đăng nhập thành công",
  "data": {
    "user": {
      "id": "uuid",
      "username": "student",
      "display_name": "Nguyễn Văn A",
      "role": "student",
      "teacher_id": null,
      "student_id": "uuid",
      "parent_id": null,
      "is_active": true
    }
  },
  "token": "plain-text-token",
  "token_type": "Bearer"
}
```

Ví dụ lấy hồ sơ:

```http
GET /api/v1/profile
Accept: application/json
Authorization: Bearer <token>
```

Ví dụ đăng xuất:

```http
POST /api/v1/logout
Accept: application/json
Authorization: Bearer <token>
```

Bảng cần có cho Sanctum:

```text
personal_access_tokens
```

Nếu không chạy được toàn bộ migration vì database đã có bảng cũ, import riêng file:

```text
database/sql/create_api_auth_tables.sql
```
