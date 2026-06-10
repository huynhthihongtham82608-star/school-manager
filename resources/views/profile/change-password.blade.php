@extends('layouts.app')
@section('title', 'Đổi mật khẩu')

@section('content')
<h5 class="mb-3">Đổi mật khẩu</h5>

<form method="POST" action="{{ route('profile.update-password') }}" class="card p-4 shadow-sm" style="max-width: 500px;">
    @csrf

    <div class="mb-3">
        <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" 
               required autocomplete="current-password">
        @error('current_password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
               required autocomplete="new-password">
        @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <small class="text-muted">Ít nhất 6 ký tự</small>
    </div>

    <div class="mb-3">
        <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
        <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" 
               required autocomplete="new-password">
        @error('password_confirmation')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Đổi mật khẩu
        </button>
        <a href="{{ route('profile.show') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Hủy
        </a>
    </div>
</form>
@endsection
