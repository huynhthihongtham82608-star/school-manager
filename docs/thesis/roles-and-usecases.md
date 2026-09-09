# Vai trò và Use Case

## Vai trò thực tế

- `admin`: quản trị toàn hệ thống, được `User::hasPermission()` cho phép toàn quyền nếu role là `admin` hoặc super admin.
- `staff`: nhân viên quản trị theo RBAC động, quyền hiệu lực lấy từ `rbac_roles`, `rbac_permissions`, `rbac_permission_role`, `rbac_role_user` hoặc snapshot JSON.
- `teacher`: giáo viên bộ môn, truy cập lớp/môn được phân công, nhập điểm và điểm danh theo phạm vi.
- `homeroom`: giáo viên chủ nhiệm, xử lý lớp chủ nhiệm, điểm danh/hạnh kiểm/đơn xin nghỉ theo phạm vi.
- `student`: xem dữ liệu cá nhân, điểm, lịch, điểm danh, thông báo/tài liệu nếu route cho phép.
- `parent`: xem dữ liệu con liên kết qua `parent_student`, học phí và đơn xin nghỉ.

## Use Case đề xuất cho sơ đồ

Nhóm xác thực và tài khoản:

- Đăng nhập.
- Đổi mật khẩu.
- Khóa/mở đăng nhập người dùng.
- Quản lý vai trò và quyền.

Nhóm dữ liệu nền:

- Quản lý năm học/học kỳ.
- Quản lý lớp học.
- Quản lý học sinh/giáo viên/phụ huynh.
- Quản lý môn học, tổ chuyên môn, phòng học.

Nhóm tổ chức giảng dạy:

- Phân công giảng dạy.
- Xếp thời khóa biểu.
- Quản lý lịch kiểm tra.
- Quản lý lịch dạy thay.

Nhóm học vụ:

- Nhập và xem điểm.
- Điểm danh.
- Duyệt đơn xin nghỉ.
- Đánh giá hạnh kiểm.
- Quản lý khen thưởng.

Nhóm tài chính và truyền thông:

- Cấu hình mức thu.
- Ghi nhận học phí.
- Phụ huynh xem học phí.
- Đăng thông báo, sự kiện, tài liệu.
- Gửi và nhận tin nhắn.

Nhóm báo cáo và trợ lý:

- Xem báo cáo tổng hợp.
- Hỏi chatbot học vụ.

## Quan hệ include/extend phù hợp

- Đăng nhập là điều kiện trước của hầu hết use case nội bộ.
- Quản lý học sinh có thể include liên kết phụ huynh.
- Nhập điểm include kiểm tra phân công và cửa sổ điểm.
- Điểm danh include kiểm tra lịch học và quyền theo lớp.
- Duyệt đơn xin nghỉ extend luồng điểm danh khi đơn hợp lệ.
- Chatbot include kiểm tra scope người dùng trước khi trả dữ liệu.

Không nên tạo use case “kiểm tra dữ liệu” chung nếu không thể hiện một thao tác nghiệp vụ cụ thể.
