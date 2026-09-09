# Luồng nghiệp vụ

## Đăng nhập và phân quyền

1. Người dùng gửi username/password tới `AuthController`.
2. Hệ thống kiểm tra tài khoản, `is_active`, `login_status` và trạng thái bắt buộc đổi mật khẩu.
3. Middleware `role`, `permission`, `history.readonly` kiểm soát route sau đăng nhập.
4. Với staff/admin phụ, quyền hiệu lực lấy từ RBAC role đang active.

## Nhập và xem điểm

1. Admin hoặc giáo viên mở trang điểm.
2. Hệ thống xác định năm học, học kỳ, lớp, môn và scope phân công.
3. Giáo viên chỉ được nhập điểm trong phạm vi lớp/môn được phân công.
4. Điểm thành phần lưu trong `score_details`; công thức hiện tại dùng hệ số 1/2/3 và làm tròn 1 chữ số.
5. Học sinh/phụ huynh xem lại kết quả theo tài khoản hoặc con liên kết.

## Điểm danh và đơn xin nghỉ

1. Admin/GVCN/GVBM mở điểm danh theo lớp/ngày/tuần/nhật ký.
2. Hệ thống xác định buổi hoặc tiết có lịch học từ thời khóa biểu.
3. Người có quyền ghi nhận trạng thái có mặt, muộn, vắng, vắng có phép hoặc trạng thái tương ứng hiện có.
4. Đơn xin nghỉ do phụ huynh/học sinh gửi được GVCN/admin duyệt hoặc từ chối nếu route/UI hỗ trợ.
5. Điểm danh lịch sử không bị xóa chỉ để khớp sĩ số hiện tại.

## Chuyển lớp và chuyển tiếp năm học

1. Trong trang lớp học, Admin dùng modal xếp/chuyển học sinh.
2. Chuyển lớp thường giới hạn cùng năm học và cùng khối.
3. Khởi tạo năm học mới xử lý lên lớp/ở lại lớp theo lựa chọn/xác nhận của Admin, không tự quyết định nếu chưa có quy tắc xét duyệt rõ.
4. Lịch sử chuyển lớp và dữ liệu học tập cũ được giữ lại.

## Học phí

1. Admin cấu hình mức thu và QR trong hệ thống.
2. Học phí từng học sinh lấy theo cấu hình và trạng thái miễn giảm nếu có.
3. Admin cập nhật trạng thái thanh toán.
4. Phụ huynh xem đúng khoản thu của con liên kết; GVCN xem danh sách lớp chủ nhiệm nếu UI cho phép.

## Tin nhắn

1. Người gửi chọn người nhận và gửi tin.
2. `messages` lưu nội dung chính, `message_recipients` lưu từng người nhận.
3. Khi người nhận mở tin, trạng thái đọc cập nhật ở recipient tương ứng.
4. Bên thư đã gửi hiển thị tổng hoặc chi tiết theo từng người nhận, không dùng trạng thái đọc chung của message.

## Chatbot Gemini

1. Web/API gọi `SchoolChatbotService`.
2. `ChatbotOrchestrator` xây dựng system instruction và lịch sử hội thoại.
3. Gemini chọn tool từ `ChatbotToolRegistry`.
4. `ChatbotToolExecutor` kiểm tra quyền và gọi `ChatbotDataService`.
5. Dữ liệu trả về được Gemini diễn đạt lại hoặc fallback formatter xử lý.
6. Câu trả lời được sanitize để không lộ JSON, tool name, UUID hoặc Markdown kỹ thuật.
