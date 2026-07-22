@php
    $locked = (bool) $role?->is_system;
    $selectedPermissionIds = collect(old('permission_ids', $role?->permissions?->pluck('id')->all() ?? []))->map(fn ($id) => (string) $id)->all();
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Mã vai trò</label>
        <input type="text" name="key" class="form-control" value="{{ old('key', $role?->key) }}" placeholder="vi_du: giao_vu" required @disabled($locked)>
    </div>
    <div class="col-md-4">
        <label class="form-label">Tên vai trò</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $role?->name) }}" placeholder="Ví dụ: Cán bộ Giáo vụ" required @disabled($locked)>
    </div>
    <div class="col-md-4">
        <label class="form-label">Trạng thái</label>
        <input type="hidden" name="is_active" value="0">
        <label class="form-check form-switch mt-2">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $role?->is_active ?? true)) @disabled($locked)>
            <span class="form-check-label">Đang sử dụng</span>
        </label>
    </div>
    <div class="col-12">
        <label class="form-label">Mô tả</label>
        <textarea name="description" class="form-control" rows="2" @disabled($locked)>{{ old('description', $role?->description) }}</textarea>
    </div>
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label mb-0">Ma trận quyền</label>
            <span class="text-muted small">Chọn các quyền mà vai trò này được phép sử dụng</span>
        </div>
        <div class="row g-3">
            @foreach($permissionGroups as $groupName => $permissions)
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="fw-semibold mb-2">{{ $groupName }}</div>
                        <div class="row g-2">
                            @foreach($permissions as $permission)
                                <div class="col-12">
                                    <label class="form-check mb-0">
                                        <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" class="form-check-input" @checked(in_array((string) $permission->id, $selectedPermissionIds, true)) @disabled($locked)>
                                        <span class="form-check-label">
                                            {{ $permission->name }}
                                            <span class="text-muted small d-block">{{ $permission->key }}</span>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($locked)
            <div class="form-text text-danger mt-2">Vai trò hệ thống bị khóa để bảo đảm tương thích với các chức năng cũ.</div>
        @endif
    </div>
</div>
