@php
    $selectedRoleIds = collect(old('role_ids', $adminUser?->rbacRoles?->pluck('id')->all() ?? []))->map(fn ($id) => (string) $id)->all();
    $locked = $adminUser?->isSuperAdmin() && ! auth()->user()->isSuperAdmin();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Tên đăng nhập</label>
        <input type="text" name="username" class="form-control" value="{{ old('username', $adminUser?->username) }}" required @disabled($locked)>
    </div>
    <div class="col-md-6">
        <label class="form-label">Họ tên</label>
        <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $adminUser?->full_name) }}" required @disabled($locked)>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $adminUser?->email) }}" @disabled($locked)>
    </div>
    <div class="col-md-6">
        <label class="form-label">Số điện thoại</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $adminUser?->phone) }}" @disabled($locked)>
    </div>
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label mb-0">Vai trò quản trị</label>
            <span class="text-muted small">Tích chọn một hoặc nhiều vai trò</span>
        </div>
        <div class="row g-2">
            @foreach($roles as $role)
                <div class="col-md-6">
                    <label class="form-check border rounded-3 p-3 h-100">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="form-check-input me-2" @checked(in_array((string) $role->id, $selectedRoleIds, true)) @disabled($adminUser?->isSuperAdmin())>
                        <span class="form-check-label">
                            <span class="fw-semibold">{{ $role->name }}</span>
                            @if($role->description)
                                <span class="d-block text-muted small">{{ $role->description }}</span>
                            @endif
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
        @if($adminUser?->isSuperAdmin())
            <div class="form-text text-danger">Super Admin được bảo vệ, không chỉnh sửa vai trò tại đây.</div>
        @endif
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <label class="form-check form-switch">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $adminUser?->is_active ?? true)) @disabled($adminUser?->isSuperAdmin())>
            <span class="form-check-label">Tài khoản đang hoạt động</span>
        </label>
    </div>
    @if(! $adminUser)
        <div class="col-12">
            <div class="alert alert-info mb-0">
                Mật khẩu mặc định sẽ được tạo tự động là <strong>12345678</strong> và người dùng phải đổi mật khẩu ở lần đăng nhập đầu tiên.
            </div>
        </div>
    @endif
</div>
