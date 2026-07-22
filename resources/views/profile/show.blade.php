@extends('layouts.app')
@section('title', 'Hồ sơ cá nhân')

@section('content')
@php
    $profileName = $user->display_name;
    $initials = mb_substr($profileName ?: $user->username, 0, 1);
@endphp

<div class="page-heading">
    <div>
        <h5>Hồ sơ cá nhân</h5>
        <div class="text-muted">Thông tin tài khoản và hồ sơ liên kết theo vai trò.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Sửa
        </a>
        <a href="{{ route('profile.change-password') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-key me-1"></i>Đổi mật khẩu
        </a>
    </div>
</div>

@if($user->isStudent() && $student)
    @php
        $studentName = $student->name ?: $profileName;
        $studentInitial = mb_strtoupper(mb_substr($studentName ?: 'H', 0, 1, 'UTF-8'), 'UTF-8');
        $systemStatus = $user->is_active ? 'Hoạt động' : 'Vô hiệu';
        $statusText = $student->statusLabel() ?: ($user->is_active ? 'Đang học' : 'Vô hiệu');
        $statusClass = $student->statusBadgeClass() ?: ($user->is_active ? 'bg-success' : 'bg-danger');
        $parentPhone = trim((string) $student->parent_phone);
    @endphp

    <div class="student-profile-page">
        <div class="student-profile-header card">
            <div class="card-body">
                <div class="profile-header-content">
                    <div class="profile-header-left">
                        <div class="student-profile-avatar">
                            <i class="bi bi-person-badge"></i>
                            <span>{{ $studentInitial }}</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="student-profile-name">{{ $studentName }}</h4>
                            <div class="student-profile-meta">
                                Mã số: {{ $student->student_code ?: 'Chưa cập nhật' }}
                                <span class="mx-1">|</span>
                                Vai trò: Học sinh
                            </div>
                        </div>
                    </div>
                    <span class="badge rounded-pill student-profile-status profile-status-end {{ $statusClass }}">
                        {{ $statusText }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-6">
                <div class="profile-info-block card h-100">
                    <div class="card-header profile-info-block-header">
                        <i class="bi bi-book"></i>
                        <span>Học tập</span>
                    </div>
                    <div class="card-body">
                        <div class="profile-field-list">
                            <div class="profile-field-row">
                                <span>Lớp</span>
                                <strong>{{ $student->classRoom?->name ?? 'Chưa cập nhật' }}</strong>
                            </div>
                            <div class="profile-field-row">
                                <span>Niên khóa</span>
                                <strong>{{ $student->cohortLabel() }}</strong>
                            </div>
                            <div class="profile-field-row">
                                <span>Trạng thái trên hệ thống</span>
                                <strong>{{ $systemStatus }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="profile-info-block card h-100">
                    <div class="card-header profile-info-block-header">
                        <i class="bi bi-person-vcard"></i>
                        <span>Thông tin cá nhân</span>
                    </div>
                    <div class="card-body">
                        <div class="profile-field-list">
                            <div class="profile-field-row">
                                <span>Ngày sinh</span>
                                <strong>{{ $student->dob ? $student->dob->format('d/m/Y') : 'Chưa cập nhật' }}</strong>
                            </div>
                            <div class="profile-field-row">
                                <span>Giới tính</span>
                                <strong>{{ $student->genderLabel() }}</strong>
                            </div>
                            <div class="profile-field-row">
                                <span>Địa chỉ</span>
                                <strong>{{ $student->address ?: 'Chưa cập nhật' }}</strong>
                            </div>
                            <div class="profile-field-row">
                                <span>SĐT phụ huynh</span>
                                @if($parentPhone !== '')
                                    <a href="tel:{{ $parentPhone }}" class="profile-phone-link">{{ $parentPhone }}</a>
                                @else
                                    <strong>Chưa cập nhật</strong>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif($user->isParent() && $parent)
    @php
        $parentName = $parent->name ?: $profileName;
        $parentInitial = 'P';
        $accountStatusText = $user->is_active ? 'Hoạt động' : 'Vô hiệu';
        $accountStatusClass = $user->is_active ? 'bg-success' : 'bg-danger';
        $parentPhone = trim((string) $parent->phone);
    @endphp

    <div class="student-profile-page parent-profile-page">
        <div class="parent-profile-header-card card">
            <div class="card-body">
                <div class="parent-profile-header-content">
                    <div class="parent-profile-identity">
                        <div class="parent-profile-avatar-letter">
                            {{ $parentInitial }}
                        </div>
                        <div class="min-w-0">
                            <h4 class="parent-profile-title">{{ $parentName }}</h4>
                            <div class="parent-profile-role">Vai trò: Phụ huynh</div>
                        </div>
                    </div>
                    <span class="parent-profile-status-badge {{ $accountStatusClass }}">
                        {{ $accountStatusText }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-4">
                <div class="parent-contact-card card h-100">
                    <div class="card-body">
                        <h6 class="parent-contact-title">
                            <i class="bi bi-telephone"></i>
                            <span>Thông tin liên hệ</span>
                        </h6>
                        <div class="parent-contact-list">
                            <div class="parent-contact-item">
                                <span class="parent-contact-label">Số điện thoại</span>
                                @if($parentPhone !== '')
                                    <a href="tel:{{ $parentPhone }}" class="parent-contact-value parent-contact-phone">{{ $parentPhone }}</a>
                                @else
                                    <strong class="parent-contact-value">Chưa cập nhật</strong>
                                @endif
                            </div>
                            <div class="parent-contact-item">
                                <span class="parent-contact-label">Địa chỉ</span>
                                <strong class="parent-contact-value parent-contact-address">{{ $parent->address ?: 'Chưa cập nhật' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-info-block card h-100">
                    <div class="card-header profile-info-block-header">
                        <i class="bi bi-person-lines-fill"></i>
                        <span>Danh sách con em đang theo học</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive parent-children-table">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Học sinh</th>
                                        <th>Mã HS</th>
                                        <th>Lớp</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($children ?? [] as $child)
                                    <tr>
                                        <td class="fw-semibold">{{ $child->name }}</td>
                                        <td>{{ $child->student_code ?: 'Chưa cập nhật' }}</td>
                                        <td>{{ $child->classRoom?->name ?? 'Chưa phân lớp' }}</td>
                                        <td>
                                            <span class="badge {{ $child->statusBadgeClass() }}">
                                                {{ $child->statusLabel() }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <div class="empty-state"><i class="bi bi-person-dash"></i>Chưa liên kết học sinh.</div>
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif($user->isTeacher() && $teacher)
    @php
        $teacherName = $teacher->name ?: $profileName;
        $teacherInitial = mb_strtoupper(mb_substr($teacherName ?: 'G', 0, 1, 'UTF-8'), 'UTF-8');
        $accountStatusText = ($user->is_active && $teacher->isWorking()) ? 'Hoạt động' : 'Vô hiệu';
        $accountStatusClass = ($user->is_active && $teacher->isWorking()) ? 'bg-success' : 'bg-danger';
        $teacherEmail = trim((string) $teacher->email);
        $teacherPhone = trim((string) $teacher->phone);
        $positionText = $teacher->is_homeroom ? 'Giáo viên chủ nhiệm (GVCN)' : 'Giáo viên bộ môn';
        $assignmentBadges = ($teachingAssignments ?? collect())
            ->filter(fn ($assignment) => $assignment->classRoom && $assignment->subject)
            ->map(fn ($assignment) => ($assignment->subject?->name ?? '-') . ' - ' . ($assignment->classRoom?->name ?? '-'))
            ->unique()
            ->values();
    @endphp

    <div class="teacher-profile-page">
        <div class="teacher-profile-header-card card">
            <div class="card-body">
                <div class="teacher-profile-header-content">
                    <div class="teacher-profile-identity">
                        <div class="teacher-profile-avatar-letter">
                            {{ $teacherInitial }}
                        </div>
                        <div class="min-w-0">
                            <h4 class="teacher-profile-title">{{ $teacherName }}</h4>
                            <div class="teacher-profile-role">
                                Mã GV: {{ $teacher->teacher_code ?: 'Chưa cập nhật' }}
                                <span class="mx-1">|</span>
                                Vai trò: Giáo viên
                            </div>
                        </div>
                    </div>
                    <span class="teacher-profile-status-badge {{ $accountStatusClass }}">
                        {{ $accountStatusText }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-4">
                <div class="teacher-contact-card card h-100">
                    <div class="card-body">
                        <h6 class="teacher-contact-title">
                            <i class="bi bi-envelope-paper"></i>
                            <span>Thông tin liên hệ</span>
                        </h6>
                        <div class="teacher-contact-list">
                            <div class="teacher-contact-item">
                                <span class="teacher-contact-label">Thư điện tử</span>
                                @if($teacherEmail !== '')
                                    <a href="mailto:{{ $teacherEmail }}" class="teacher-contact-value teacher-contact-email">{{ $teacherEmail }}</a>
                                @else
                                    <strong class="teacher-contact-value">Chưa cập nhật</strong>
                                @endif
                            </div>
                            <div class="teacher-contact-item">
                                <span class="teacher-contact-label">Số điện thoại</span>
                                @if($teacherPhone !== '')
                                    <a href="tel:{{ $teacherPhone }}" class="teacher-contact-value teacher-contact-phone">{{ $teacherPhone }}</a>
                                @else
                                    <strong class="teacher-contact-value">Chưa cập nhật</strong>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-info-block card h-100">
                    <div class="card-header profile-info-block-header">
                        <i class="bi bi-mortarboard"></i>
                        <span>Công tác chuyên môn</span>
                    </div>
                    <div class="card-body">
                        <div class="profile-field-list">
                            <div class="profile-field-row">
                                <span>Môn học chính phụ trách</span>
                                <strong>{{ $teacher->primarySubjectName() !== '-' ? $teacher->primarySubjectName() : 'Chưa cập nhật' }}</strong>
                            </div>
                            <div class="profile-field-row">
                                <span>Chức vụ công tác</span>
                                <strong>{{ $positionText }}</strong>
                            </div>
                        </div>

                        <div class="teacher-class-section">
                            <div class="teacher-class-title">Lớp đang giảng dạy</div>
                            <div class="teacher-class-badges">
                                @forelse($assignmentBadges as $assignmentBadge)
                                    <span class="teacher-class-badge">{{ $assignmentBadge }}</span>
                                @empty
                                    <span class="teacher-class-empty">Chưa có phân công giảng dạy.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif($user->isAdmin())
    @php
        $adminName = $profileName ?: 'Quản trị tối cao';
        $adminInitial = mb_strtoupper(mb_substr($adminName ?: 'Q', 0, 1, 'UTF-8'), 'UTF-8');
        $adminStatusText = $user->is_active ? 'Hoạt động' : 'Vô hiệu';
        $adminStatusClass = $user->is_active ? 'bg-success' : 'bg-danger';
        $adminPrivilege = $user->isSuperAdmin() ? 'Quản trị tối cao' : 'Quản trị viên';
    @endphp

    <div class="admin-profile-page">
        <div class="admin-profile-header-card card">
            <div class="card-body">
                <div class="admin-profile-header-content">
                    <div class="admin-profile-identity">
                        <div class="admin-profile-avatar-letter">
                            {{ $adminInitial }}
                        </div>
                        <div class="min-w-0">
                            <h4 class="admin-profile-title">{{ $adminName }}</h4>
                            <div class="admin-profile-role">Vai trò: Quản trị viên</div>
                        </div>
                    </div>
                    <span class="admin-profile-status-badge {{ $adminStatusClass }}">
                        {{ $adminStatusText }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-4">
                <div class="admin-profile-card card h-100">
                    <div class="card-body">
                        <h6 class="admin-profile-card-title">
                            <i class="bi bi-shield-lock"></i>
                            <span>Bảo mật tài khoản</span>
                        </h6>
                        <div class="admin-profile-field-list">
                            <div class="admin-profile-field">
                                <span>Tên đăng nhập</span>
                                <strong>{{ $user->username ?: 'Chưa cập nhật' }}</strong>
                            </div>
                        </div>
                        <a href="{{ route('profile.change-password') }}" class="btn admin-profile-password-btn">
                            <i class="bi bi-key"></i>
                            <span>Đổi mật khẩu</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="admin-profile-card card h-100">
                    <div class="card-body">
                        <h6 class="admin-profile-card-title">
                            <i class="bi bi-shield-check"></i>
                            <span>Đặc quyền hệ thống</span>
                        </h6>
                        <div class="admin-profile-privilege-line">
                            <span>Cấp độ tài khoản</span>
                            <strong class="admin-profile-privilege-badge">{{ $adminPrivilege }}</strong>
                        </div>
                        <ul class="admin-profile-privileges">
                            <li>Toàn quyền cấu hình và vận hành danh mục học vụ toàn trường.</li>
                            <li>Quản lý, khởi tạo và phân quyền cho các tài khoản người dùng trong hệ thống.</li>
                            <li>Truy cập và kết xuất toàn bộ hệ thống báo cáo thống kê vĩ mô.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body">
            <div class="empty-state"><i class="bi bi-info-circle"></i>Không có thông tin hồ sơ.</div>
        </div>
    </div>
@endif
@endsection
