@extends('layouts.app')
@section('title', 'Sửa phụ huynh')

@section('content')
<h5 class="mb-3">Sửa phụ huynh</h5>

@if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form method="POST" action="{{ route('parents.update', $parent) }}" class="card shadow-sm p-4">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Họ tên</label>
            <input class="form-control" name="name" value="{{ $parent->name }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">SĐT</label>
            <input class="form-control" name="phone" value="{{ $parent->phone }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" type="email" value="{{ $parent->email }}">
        </div>
        <div class="col-12">
            <label class="form-label">Địa chỉ</label>
            <input class="form-control" name="address" value="{{ $parent->address }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Liên kết học sinh</label>
            <select class="form-select" name="student_ids[]" multiple>
                @foreach($students as $st)
                    <option value="{{ $st->id }}" @selected($parent->students->contains('id', $st->id))>{{ $st->student_code }} - {{ $st->name }}</option>
                @endforeach
            </select>
            <div class="text-muted small mt-1">Giữ Ctrl để chọn nhiều.</div>
        </div>
        <hr>
        <div class="col-md-4">
            <label class="form-label">Tài khoản</label>
            <input class="form-control" value="{{ $parent->user?->username }}" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label">Đặt lại mật khẩu</label>
            <input class="form-control" name="password" type="password">
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ ($parent->user && $parent->user->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Kích hoạt tài khoản</label>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a class="btn btn-link" href="{{ route('parents.index') }}">Hủy</a>
    </div>
</form>
@endsection
