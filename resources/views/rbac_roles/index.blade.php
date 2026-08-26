@extends('layouts.app')
@section('title', 'Vai trò & quyền')

@section('content')
<x-page-header
    class="rbac-page-header"
    title="Phân quyền & Vai trò hệ thống"
    subtitle="Thiết lập ma trận đặc quyền bảo mật chi tiết (Xem, Thêm, Sửa, Xóa) cho từng nhóm tài khoản trong nhà trường."
>
    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
            <i class="bi bi-plus-lg me-1"></i>Tạo vai trò mới
        </button>
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Bộ lọc">
                <i class="bi bi-funnel"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 320px;">
                <form method="GET" action="{{ route('rbac-roles.index') }}" class="d-grid gap-3">
                    <div>
                        <label class="form-label small">Tìm kiếm</label>
                        <input type="search" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Mã, tên vai trò, mô tả">
                    </div>
                    <div>
                        <label class="form-label small">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="all" @selected($filters['status'] === 'all')>Tất cả</option>
                            <option value="active" @selected($filters['status'] === 'active')>Đang sử dụng</option>
                            <option value="inactive" @selected($filters['status'] === 'inactive')>Đã tắt</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('rbac-roles.index') }}" class="btn btn-secondary">Xóa lọc</a>
                        <button class="btn btn-primary">Áp dụng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-page-header>

<div class="card rbac-role-card">
    <div class="table-responsive rbac-table-wrap">
        <table class="table align-middle rbac-role-table" data-no-auto-toolbar>
            <thead>
                <tr>
                    <th>Mã vai trò</th>
                    <th>Tên vai trò</th>
                    <th>Số quyền</th>
                    <th>Số tài khoản</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th class="text-end action-column-header"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td class="fw-semibold content-break-cell">{{ $role->key }}</td>
                    <td>
                        <div class="fw-semibold">{{ $role->name }}</div>
                        <div class="text-muted small">{{ $role->description ?: '-' }}</div>
                    </td>
                    <td>{{ $role->permissions->count() }}</td>
                    <td>{{ $role->users->count() }}</td>
                    <td>
                        <span class="rbac-type-badge {{ $role->is_system ? 'system' : 'custom' }}">
                            {{ $role->is_system ? 'Hệ thống' : 'Tùy chỉnh' }}
                        </span>
                    </td>
                    <td>
                        <span class="rbac-status-badge {{ $role->is_active ? 'active' : 'inactive' }}">
                            {{ $role->is_active ? 'Đang sử dụng' : 'Đã tắt' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <button type="button" class="content-action-btn icon-only view" data-bs-toggle="modal" data-bs-target="#roleDetail{{ $role->id }}" title="Xem chi tiết vai trò">
                                <i class="bi bi-eye"></i>
                            </button>
                            @if(! $role->is_system)
                                <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#editRole{{ $role->id }}" title="Chỉnh sửa">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7"><div class="empty-state"><i class="bi bi-shield-check"></i>Chưa có vai trò.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade content-modal" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form class="modal-content" method="POST" action="{{ route('rbac-roles.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Thêm vai trò</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                @include('rbac_roles.partials.form', ['role' => null, 'permissionGroups' => $permissionGroups])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

@foreach($roles as $role)
    <div class="modal fade content-modal system-detail-modal rbac-detail-modal" id="roleDetail{{ $role->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered rbac-detail-dialog">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="system-modal-profile-header rbac-detail-header">
                        <div class="min-w-0">
                            <h2>{{ $role->name }}</h2>
                            <p>
                                Mã hệ thống: <strong>{{ $role->key }}</strong>
                                <span>•</span>
                                {{ $role->is_system ? 'Vai trò hệ thống' : 'Vai trò tùy chỉnh' }}
                            </p>
                        </div>
                        <span class="rbac-status-badge {{ $role->is_active ? 'active' : 'inactive' }} ms-auto">
                            {{ $role->is_active ? 'Đang sử dụng' : 'Đã tắt' }}
                        </span>
                    </div>

                    <section class="system-modal-section rbac-detail-section mt-4">
                        <h3 class="system-section-title">Mô tả vai trò</h3>
                        <div class="rbac-detail-description">
                            {{ $role->description ?: 'Vai trò này chưa có mô tả nghiệp vụ. Hệ thống vẫn hiển thị đầy đủ danh sách quyền đang được gán ở bên dưới.' }}
                        </div>
                    </section>

                    <section class="system-modal-section rbac-detail-section mt-4">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <h3 class="system-section-title">Danh sách quyền hạn</h3>
                            <span class="rbac-detail-count">{{ $role->permissions->count() }} quyền</span>
                        </div>

                        <div class="rbac-permission-tag-list">
                            @forelse($role->permissions->sortBy([['group', 'asc'], ['name', 'asc']]) as $permission)
                                <span class="rbac-permission-tag">
                                    <span class="rbac-permission-tag-name">{{ $permission->name }}</span>
                                    <span class="rbac-permission-tag-key">{{ $permission->key }}</span>
                                </span>
                            @empty
                                <div class="empty-state">Vai trò này chưa được gán quyền.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn system-modal-close-btn" data-bs-dismiss="modal">Đóng cửa sổ</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade content-modal" id="editRole{{ $role->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <form class="modal-content" method="POST" action="{{ route('rbac-roles.update', $role) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ $role->is_system ? 'Xem vai trò hệ thống' : 'Chỉnh sửa vai trò' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    @include('rbac_roles.partials.form', ['role' => $role, 'permissionGroups' => $permissionGroups])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    @if(! $role->is_system)
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
