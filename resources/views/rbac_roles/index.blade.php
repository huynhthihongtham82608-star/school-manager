@extends('layouts.app')
@section('title', 'Vai trò & quyền')

@section('content')
<x-page-header
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

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Mã vai trò</th>
                    <th>Tên vai trò</th>
                    <th>Số quyền</th>
                    <th>Số tài khoản</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td class="fw-semibold">{{ $role->key }}</td>
                    <td>
                        <div class="fw-semibold">{{ $role->name }}</div>
                        <div class="text-muted small">{{ $role->description ?: '-' }}</div>
                    </td>
                    <td>{{ $role->permissions->count() }}</td>
                    <td>{{ $role->users->count() }}</td>
                    <td>
                        <span class="badge {{ $role->is_system ? 'bg-secondary' : 'bg-primary' }}">
                            {{ $role->is_system ? 'Hệ thống' : 'Tùy chỉnh' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $role->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $role->is_active ? 'Đang sử dụng' : 'Đã tắt' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#editRole{{ $role->id }}" title="Xem hoặc chỉnh sửa">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" aria-expanded="false" title="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <form action="{{ route('rbac-roles.toggle', $role) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái vai trò này?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item" @disabled($role->is_system)>
                                            <i class="bi {{ $role->is_active ? 'bi-pause-circle' : 'bi-play-circle' }} me-2"></i>{{ $role->is_active ? 'Tắt vai trò' : 'Bật vai trò' }}
                                        </button>
                                    </form>
                                    <div class="dropdown-divider"></div>
                                    <form action="{{ route('rbac-roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa vai trò này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger" @disabled($role->is_system || $role->users->isNotEmpty())>
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
