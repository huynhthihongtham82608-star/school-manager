@props([
    'title' => null,
    'subtitle' => null,
])

@php
    $routeHeader = match (true) {
        request()->routeIs('students.*') => [
            'title' => 'Quản lý học sinh',
            'subtitle' => 'Khởi tạo, tra cứu hồ sơ lý lịch, quản lý trạng thái học tập và thông tin liên hệ của học sinh toàn trường.',
        ],
        request()->routeIs('teachers.*') => [
            'title' => 'Quản lý giáo viên',
            'subtitle' => 'Quản lý hồ sơ nhân sự, thông tin liên lạc, chức vụ công tác và trạng thái giảng dạy của cán bộ giáo viên.',
        ],
        request()->routeIs('parents.*') => [
            'title' => 'Quản lý tài khoản phụ huynh',
            'subtitle' => 'Giám sát danh sách tài khoản của cha mẹ, quản lý liên kết định danh giữa phụ huynh và học sinh.',
        ],
        request()->routeIs('admin.home-page.*', 'announcements.*', 'events.*', 'documents.*') => [
            'title' => 'Cấu hình nội dung hệ thống',
            'subtitle' => 'Quản lý giao diện trang chủ, biên tập các bài viết tin tức, chỉnh sửa thư viện ảnh và thông tin hiển thị trên cổng thông tin nhà trường.',
        ],
        request()->routeIs('messages.*') => [
            'title' => 'Hộp thư điện tử',
            'subtitle' => 'Kênh tương tác, trao đổi thông tin chính thống giữa Nhà trường, Giáo viên và Phụ huynh học sinh.',
        ],
        request()->routeIs('rbac-roles.*') => [
            'title' => 'Phân quyền & Vai trò hệ thống',
            'subtitle' => 'Thiết lập ma trận đặc quyền bảo mật chi tiết (Xem, Thêm, Sửa, Xóa) cho từng nhóm tài khoản trong nhà trường.',
        ],
        request()->routeIs('admin-users.*') => [
            'title' => 'Quản lý Admin',
            'subtitle' => 'Quản lý tài khoản quản trị phụ và gán vai trò theo ma trận quyền.',
        ],
        request()->routeIs('audit-logs.*') => [
            'title' => 'Nhật ký hoạt động',
            'subtitle' => 'Theo dõi các thao tác thay đổi dữ liệu của Admin và Giáo viên trong hệ thống.',
        ],
        request()->routeIs('system.settings.*') => [
            'title' => 'Cài đặt hệ thống',
            'subtitle' => 'Thiết lập thông tin nhận diện, liên hệ và cấu hình hiển thị chung của nhà trường.',
        ],
        request()->routeIs('system.backups.*') => [
            'title' => 'Sao lưu & Khôi phục dữ liệu',
            'subtitle' => 'Tạo và tải bản sao lưu database. Chức năng khôi phục đang được khóa để bảo vệ dữ liệu.',
        ],
        default => ['title' => $title, 'subtitle' => $subtitle],
    };

    $title = $title ?? $routeHeader['title'] ?? null;
    $subtitle = $subtitle ?? $routeHeader['subtitle'] ?? null;
@endphp

<div {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="page-header-text">
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if($slot->isNotEmpty())
        <div class="page-header-actions">
            {{ $slot }}
        </div>
    @endif
</div>
