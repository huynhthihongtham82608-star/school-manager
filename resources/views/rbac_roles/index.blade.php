@extends('layouts.app')
@section('title', 'Vai trò & quyền')

@section('content')
<style>
    .rbac-role-table th,
    .rbac-role-table td {
        color: #1f2937;
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
    }

    .rbac-role-table th {
        color: #111827;
        font-weight: 500;
        background: #fff7ed;
    }

    .rbac-type-badge,
    .rbac-status-badge {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 6px;
        padding: .25rem .55rem;
        font-size: .875rem;
        font-weight: 400;
        line-height: 1.25;
        text-align: left;
    }

    .rbac-type-badge.system,
    .rbac-status-badge.active {
        color: #166534;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .rbac-type-badge.custom,
    .rbac-status-badge.inactive {
        color: #c2410c;
        background: #fff7ed;
        border: 1px solid #fed7aa;
    }

    .rbac-detail-dialog {
        width: min(1240px, calc(100vw - 2rem));
        max-width: none;
    }

    .rbac-detail-modal .modal-content {
        border: 1px solid #fed7aa;
        border-radius: 8px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .22);
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        text-align: left;
    }

    .rbac-detail-body {
        padding: 1.25rem 1.5rem 1.5rem;
        text-align: left;
    }

    .rbac-detail-two-column {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 3fr);
        gap: 1.5rem;
        align-items: start;
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        font-weight: 400;
        text-align: left;
    }

    .rbac-detail-column {
        min-width: 0;
        text-align: left;
    }

    .rbac-detail-title {
        margin: 0 0 1rem;
        color: #111827;
        font-size: 1.125rem;
        font-weight: 400;
        line-height: 1.4;
        text-align: left;
    }

    .rbac-permission-heading {
        margin: 0 0 1rem;
        color: #111827;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.4;
        text-align: left;
    }

    .rbac-detail-stack {
        display: grid;
        gap: 1rem;
        text-align: left;
    }

    .rbac-detail-row {
        display: grid;
        gap: .2rem;
        text-align: left;
    }

    .rbac-detail-label {
        color: #ea580c;
        font-size: .75rem;
        font-weight: 400;
        line-height: 1.35;
        text-align: left;
    }

    .rbac-detail-description {
        color: #1f2937;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.6;
        text-align: left;
        overflow-wrap: anywhere;
    }

    .rbac-permission-tag-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .5rem;
        text-align: left;
    }

    .rbac-permission-tag {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        max-width: 100%;
        font-weight: 400;
        line-height: 1.35;
        overflow-wrap: anywhere;
        text-align: left;
        vertical-align: top;
    }

    .rbac-permission-tag-key {
        color: #c2410c;
        font-size: .78rem;
        font-weight: 400;
        line-height: 1.25;
        margin-top: .2rem;
    }

    @media (max-width: 768px) {
        .rbac-detail-two-column {
            grid-template-columns: 1fr;
        }
    }
</style>

<x-page-header
    class="rbac-page-header"
    title="Phân quyền & vai trò hệ thống"
    subtitle="Thiết lập ma trận quyền bảo mật cho từng nhóm tài khoản trong nhà trường."
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
    @php
        $roleDescription = $role->description ?: match ($role->key) {
            'admin', 'staff' => 'Cán bộ quản trị - Tài khoản dành cho nhân viên văn phòng, văn thư, kế toán của nhà trường.',
            'teacher', 'homeroom' => 'Giáo viên - Tài khoản phục vụ giảng dạy, chủ nhiệm và theo dõi học sinh.',
            'student' => 'Học sinh - Tài khoản truy cập thông tin học tập cá nhân.',
            'parent' => 'Phụ huynh - Tài khoản theo dõi học tập, học phí và trao đổi với nhà trường.',
            default => 'Vai trò nghiệp vụ tùy chỉnh trong hệ thống quản lý nhà trường.',
        };
    @endphp

    <div class="modal fade content-modal rbac-detail-modal" id="roleDetail{{ $role->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered rbac-detail-dialog">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body rbac-detail-body">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 text-left font-sans font-normal rbac-detail-two-column">
                        <section class="rbac-detail-column md:col-span-2">
                            <h2 class="rbac-detail-title">{{ $role->name }}</h2>
                            <div class="rbac-detail-stack">
                                <div class="rbac-detail-row mb-4">
                                    <span class="rbac-detail-label text-xs text-orange-600 block mb-1">Tên</span>
                                    <p class="rbac-detail-description text-base text-gray-950 mb-0">{{ $role->name }}</p>
                                </div>
                                <div class="rbac-detail-row mb-4">
                                    <span class="rbac-detail-label text-xs text-orange-600 block mb-1">Mã</span>
                                    <p class="rbac-detail-description text-base text-gray-950 mb-0">{{ $role->key }}</p>
                                </div>
                                <div class="rbac-detail-row mb-4">
                                    <span class="rbac-detail-label text-xs text-orange-600 block mb-1">Trạng thái</span>
                                    <span class="rbac-status-badge {{ $role->is_active ? 'active' : 'inactive' }}">
                                        {{ $role->is_active ? 'Đang sử dụng' : 'Đã tắt' }}
                                    </span>
                                </div>
                                <div class="rbac-detail-row mb-4">
                                    <span class="rbac-detail-label text-xs text-orange-600 block mb-1">Loại</span>
                                    <span class="rbac-type-badge {{ $role->is_system ? 'system' : 'custom' }}">
                                        {{ $role->is_system ? 'Vai trò hệ thống' : 'Vai trò tùy chỉnh' }}
                                    </span>
                                </div>
                                <div class="rbac-detail-row mb-4">
                                    <span class="rbac-detail-label text-xs text-orange-600 block mb-1">Mô tả nghiệp vụ</span>
                                    <p class="rbac-detail-description text-base text-gray-950 mb-0">{{ $roleDescription }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="rbac-detail-column md:col-span-3">
                            <div class="rbac-permission-heading">Quyền hạn ({{ $role->permissions->count() }} quyền)</div>
                            <div class="rbac-permission-tag-grid">
                                @forelse($role->permissions->sortBy([['group', 'asc'], ['name', 'asc']]) as $permission)
                                    <span class="rbac-permission-tag inline-flex flex-col bg-orange-50/60 border border-orange-100 px-3 py-2 rounded-lg text-sm text-orange-950 font-normal m-1 transition-all text-left">
                                        <span>{{ $permission->name }}</span>
                                        <span class="rbac-permission-tag-key">{{ $permission->key }}</span>
                                    </span>
                                @empty
                                    <span class="rbac-permission-tag inline-flex flex-col bg-orange-50/60 border border-orange-100 px-3 py-2 rounded-lg text-sm text-orange-950 font-normal m-1 transition-all text-left">
                                        Chưa gán quyền
                                    </span>
                                @endforelse
                            </div>
                        </section>
                    </div>
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
