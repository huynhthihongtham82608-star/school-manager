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

<div class="card">
    <div class="card-body">
        <div class="profile-hero mb-4">
            <div class="profile-avatar">{{ mb_strtoupper($initials) }}</div>
            <div class="min-w-0">
                <div class="h5 mb-1">{{ $profileName }}</div>
                <div class="text-muted">{{ $user->username }}</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge badge-role">{{ $user->role }}</span>
                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $user->is_active ? 'Hoạt động' : 'Vô hiệu' }}
                    </span>
                </div>
            </div>
        </div>

        @if($user->isAdmin())
            <div class="info-list">
                <div class="info-item">
                    <div class="info-label">Tên đăng nhập</div>
                    <div class="info-value">{{ $user->username }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Quyền</div>
                    <div class="info-value">{{ ucfirst($user->role) }}</div>
                </div>
            </div>
        @elseif($user->isTeacher() && $teacher)
            <div class="info-list">
                <div class="info-item"><div class="info-label">Mã GV</div><div class="info-value">{{ $teacher->teacher_code }}</div></div>
                <div class="info-item"><div class="info-label">Họ tên</div><div class="info-value">{{ $teacher->name }}</div></div>
                <div class="info-item"><div class="info-label">Email</div><div class="info-value">{{ $teacher->email ?? 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div class="info-label">Số điện thoại</div><div class="info-value">{{ $teacher->phone ?? 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div class="info-label">Bộ môn</div><div class="info-value">{{ $teacher->main_subject ?? 'Chưa cập nhật' }}</div></div>
                <div class="info-item">
                    <div class="info-label">Chức vụ</div>
                    <div class="info-value">
                        @if($teacher->is_homeroom)
                            <span class="badge bg-warning">GVCN</span>
                        @else
                            <span class="badge bg-secondary">GV</span>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($user->isStudent() && $student)
            <div class="info-list">
                <div class="info-item"><div class="info-label">Mã HS</div><div class="info-value">{{ $student->student_code }}</div></div>
                <div class="info-item"><div class="info-label">Họ tên</div><div class="info-value">{{ $student->name }}</div></div>
                <div class="info-item"><div class="info-label">Ngày sinh</div><div class="info-value">{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d/m/Y') : 'Chưa cập nhật' }}</div></div>
                <div class="info-item">
                    <div class="info-label">Giới tính</div>
                    <div class="info-value">
                        @if($student->gender === 'male')
                            Nam
                        @elseif($student->gender === 'female')
                            Nữ
                        @else
                            {{ $student->gender ?? 'Chưa cập nhật' }}
                        @endif
                    </div>
                </div>
                <div class="info-item"><div class="info-label">Lớp</div><div class="info-value">{{ $student->classRoom?->name ?? 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div class="info-label">Trạng thái</div><div class="info-value"><span class="badge bg-info">{{ ucfirst($student->status ?? 'studying') }}</span></div></div>
                <div class="info-item"><div class="info-label">Địa chỉ</div><div class="info-value">{{ $student->address ?? 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div class="info-label">Email</div><div class="info-value">{{ $student->email ?? 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div class="info-label">SĐT phụ huynh</div><div class="info-value">{{ $student->parent_phone ?? 'Chưa cập nhật' }}</div></div>
            </div>
        @elseif($user->isParent() && $parent)
            <div class="info-list mb-4">
                <div class="info-item"><div class="info-label">Họ tên</div><div class="info-value">{{ $parent->name }}</div></div>
                <div class="info-item"><div class="info-label">Số điện thoại</div><div class="info-value">{{ $parent->phone ?? 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div class="info-label">Email</div><div class="info-value">{{ $parent->email ?? 'Chưa cập nhật' }}</div></div>
                <div class="info-item"><div class="info-label">Địa chỉ</div><div class="info-value">{{ $parent->address ?? 'Chưa cập nhật' }}</div></div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">Danh sách con em</div>
                <div class="table-responsive">
                    <table class="table">
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
                                <td>{{ $child->student_code }}</td>
                                <td>{{ $child->classRoom?->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-info">{{ ucfirst($child->status ?? 'studying') }}</span></td>
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
        @else
            <div class="empty-state"><i class="bi bi-info-circle"></i>Không có thông tin hồ sơ.</div>
        @endif
    </div>
</div>
@endsection
