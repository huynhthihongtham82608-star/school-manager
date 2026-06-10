@extends('layouts.app')
@section('title', 'Thêm giáo viên')

@section('content')
<h5 class="mb-3">Thêm giáo viên</h5>
<form method="POST" action="{{ route('teachers.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã giáo viên</label>
            <input type="text" name="teacher_code" class="form-control" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">Họ tên</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Môn chính</label>
            <input type="text" name="main_subject" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Số điện thoại</label>
            <input type="text" name="phone" class="form-control">
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="is_homeroom" value="1" id="is_homeroom">
                <label class="form-check-label" for="is_homeroom">GVCN</label>
            </div>
        </div>
        <hr>
        <div class="col-md-4">
            <label class="form-label">Tài khoản</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a href="{{ route('teachers.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
