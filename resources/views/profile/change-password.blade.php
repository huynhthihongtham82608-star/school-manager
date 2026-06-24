@extends('layouts.app')
@section('title', 'Đổi mật khẩu')

@section('content')
<div class="page-heading">
    <div>
        <h5>Đổi mật khẩu</h5>
        <div class="text-muted">Cập nhật mật khẩu đăng nhập cho tài khoản hiện tại.</div>
    </div>
</div>

<form method="POST" action="{{ route('profile.update-password') }}" class="card" style="max-width: 560px;">
    @csrf
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
            @error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <small class="text-muted">Ít nhất 6 ký tự.</small>
        </div>

        <div class="mb-0">
            <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" required autocomplete="new-password">
            @error('password_confirmation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap gap-2 justify-content-end">
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Hủy
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i>Đổi mật khẩu
        </button>
    </div>
</form>
@endsection
