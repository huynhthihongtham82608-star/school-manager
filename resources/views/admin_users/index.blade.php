@extends('layouts.app')
@section('title', 'Quản lý Admin')

@section('content')
<x-page-header
    title="Quản lý Admin"
    subtitle="Quản lý tài khoản quản trị phụ và gán vai trò theo ma trận quyền."
>
    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminUserModal">
            <i class="bi bi-plus-lg me-1"></i>Thêm tài khoản
        </button>
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Bộ lọc">
                <i class="bi bi-funnel"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 360px;">
                <form method="GET" action="{{ route('admin-users.index') }}" class="d-grid gap-3">
                    <div>
                        <label class="form-label small">Tìm kiếm</label>
                        <input type="search" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Tên đăng nhập, họ tên, vai trò">
                    </div>
                    <div>
                        <label class="form-label small">Vai trò</label>
                        <select name="role_id" class="form-select">
                            <option value="all">Tất cả vai trò</option>
                            @foreach($filterRoles as $role)
                                <option value="{{ $role->id }}" @selected($filters['role_id'] === $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="all" @selected($filters['status'] === 'all')>Tất cả</option>
                            <option value="active" @selected($filters['status'] === 'active')>Đang hoạt động</option>
                            <option value="inactive" @selected($filters['status'] === 'inactive')>Đã khóa</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin-users.index') }}" class="btn btn-secondary">Xóa lọc</a>
                        <button class="btn btn-primary">Áp dụng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Tài khoản</th>
                    <th>Họ tên</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Lần đổi mật khẩu</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $adminUser)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $adminUser->username }}</div>
                        @if($adminUser->isSuperAdmin())
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Super Admin</span>
                        @else
                            <span class="text-muted small">Admin phụ</span>
                        @endif
                    </td>
                    <td>
                        <div>{{ $adminUser->display_name }}</div>
                        <div class="text-muted small">{{ $adminUser->email ?: $adminUser->phone ?: '-' }}</div>
                    </td>
                    <td>
                        @forelse($adminUser->rbacRoles as $role)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">{{ $role->name }}</span>
                        @empty
                            <span class="text-muted">Chưa gán vai trò</span>
                        @endforelse
                    </td>
                    <td>
                        <span class="badge {{ $adminUser->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $adminUser->is_active ? 'Đang hoạt động' : 'Đã khóa' }}
                        </span>
                    </td>
                    <td>
                        @if($adminUser->force_change_password)
                            <span class="badge bg-warning text-dark">Bắt buộc đổi</span>
                        @else
                            <span class="text-muted">Không bắt buộc</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#editAdminUser{{ $adminUser->id }}" @disabled($adminUser->isSuperAdmin()) title="Chỉnh sửa">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" aria-expanded="false" title="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <form action="{{ route('admin-users.reset-password', $adminUser) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn đặt lại mật khẩu tài khoản này về 12345678?');">
                                        @csrf
                                        <button type="submit" class="dropdown-item" @disabled($adminUser->isSuperAdmin())>
                                            <i class="bi bi-key me-2"></i>Đặt lại mật khẩu
                                        </button>
                                    </form>
                                    <form action="{{ route('admin-users.toggle', $adminUser) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái tài khoản này?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item" @disabled($adminUser->isSuperAdmin())>
                                            <i class="bi {{ $adminUser->is_active ? 'bi-lock' : 'bi-unlock' }} me-2"></i>{{ $adminUser->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                                        </button>
                                    </form>
                                    <div class="dropdown-divider"></div>
                                    <form action="{{ route('admin-users.destroy', $adminUser) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản quản trị này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger" @disabled($adminUser->isSuperAdmin())>
                                            <i class="bi bi-trash me-2"></i>Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state"><i class="bi bi-shield-lock"></i>Chưa có tài khoản quản trị.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade content-modal" id="createAdminUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin-users.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Thêm tài khoản quản trị</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                @include('admin_users.partials.form', ['adminUser' => null, 'roles' => $roles])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

@foreach($users as $adminUser)
    <div class="modal fade content-modal" id="editAdminUser{{ $adminUser->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="POST" action="{{ route('admin-users.update', $adminUser) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh sửa tài khoản quản trị</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    @include('admin_users.partials.form', ['adminUser' => $adminUser, 'roles' => $roles])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary" @disabled($adminUser->isSuperAdmin())>Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
