# Kiểm thử và hạn chế

## Kiểm thử đã thực hiện trong đợt này

Các kiểm tra không cần database:

- `php -l app/Models/User.php`: PASS.
- `php -l app/Http/Controllers/RbacRoleController.php`: PASS.
- `php -l tests/Feature/RbacRoleStatusTest.php`: PASS.
- `php artisan route:list`: PASS, đọc được 238 route.
- `php artisan route:list --path=rbac-roles`: PASS, có route index/store/update/destroy/toggle.
- `php artisan view:clear`: PASS.

Kiểm thử cần database:

- `php artisan test --env=testing --filter=RbacRoleStatusTest`: SKIP do môi trường, MySQL/MariaDB tại `127.0.0.1:3306` từ chối kết nối.

## Fix đã thực hiện

- Sửa luồng RBAC để checkbox “Đang sử dụng” trong modal chỉnh sửa vai trò có tác dụng thực tế lên quyền hiệu lực.
- Khi role bị tắt, user đang gán role mất quyền hiệu lực nhưng quan hệ gán role vẫn được giữ.
- Khi role bật lại, snapshot quyền được khôi phục từ các role đang active.
- Không tạo thêm nút bật/tắt ngoài bảng.

## Hạn chế còn tồn tại

- Không xác minh được dynamic test có ghi DB trong phiên này vì database testing không kết nối được.
- Migration chain hiện tại có rủi ro dựng mới từ zero; nên dùng clone `school_manager_testing` từ database thật đã kiểm soát để kiểm thử nghiệp vụ.
- Gemini phụ thuộc API key, mạng, quota và cấu hình CA bundle.
- Một số kiểm thử UI cuối cùng vẫn nên chạy thủ công trên trình duyệt trước khi demo.

## Khuyến nghị kiểm thử trước demo

1. Bật MySQL/MariaDB trong XAMPP.
2. Xác nhận `.env.testing` trỏ tới `school_manager_testing`.
3. Chạy các test liên quan:

```bash
php artisan test --env=testing --filter=RbacRoleStatusTest
php artisan test --env=testing --filter=AdminFinalBatchTest
php artisan test --env=testing --filter=ChatbotRequestFlowTest
php artisan test --env=testing --filter=ChatbotToolRegistryTest
```

4. Smoke test trình duyệt: đăng nhập Admin, chỉnh sửa một vai trò tùy chỉnh, tắt/bật “Đang sử dụng”, kiểm tra tài khoản staff gán role đó mất/có lại quyền.

## Đánh giá phục vụ tiểu luận

Hệ thống đã có đầy đủ nhóm chức năng quản lý trường học ở mức demo khóa luận. Cần chạy lại dynamic tests sau khi MySQL testing hoạt động để xác nhận dữ liệu và phân quyền trong môi trường thực thi.
