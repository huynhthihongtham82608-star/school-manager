@extends('layouts.app')
@section('title', 'Sửa giáo viên')

@section('content')
<h5 class="mb-3">Sửa giáo viên</h5>

@if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form method="POST" action="{{ route('teachers.update', $teacher) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã giáo viên</label>
            <input type="text" name="teacher_code" class="form-control" value="{{ $teacher->teacher_code }}" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">Họ tên</label>
            <input type="text" name="name" class="form-control" value="{{ $teacher->name }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Môn chính</label>
            <input type="text" name="main_subject" class="form-control" value="{{ $teacher->main_subject }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ $teacher->email }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Số điện thoại</label>
            <input type="text" name="phone" class="form-control" value="{{ $teacher->phone }}">
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="is_homeroom" value="1" id="is_homeroom" {{ $teacher->is_homeroom ? 'checked' : '' }}>
                <label class="form-check-label" for="is_homeroom">GVCN</label>
            </div>
        </div>
        <hr>
        <div class="col-md-4">
            <label class="form-label">Đặt lại mật khẩu (bỏ trống nếu giữ nguyên)</label>
            <input type="password" name="password" class="form-control">
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a href="{{ route('teachers.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
