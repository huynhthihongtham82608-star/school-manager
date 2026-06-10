@extends('layouts.app')
@section('title', 'Thêm học sinh')

@section('content')
<h5 class="mb-3">Thêm học sinh</h5>
<form method="POST" action="{{ route('students.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã HS</label>
            <input type="text" name="student_code" class="form-control" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">Họ tên</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Giới tính</label>
            <select name="gender" class="form-select">
                <option value="">--</option>
                <option value="male">Nam</option>
                <option value="female">Nữ</option>
                <option value="other">Khác</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Ngày sinh</label>
            <input type="date" name="dob" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Địa chỉ</label>
            <input type="text" name="address" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">SĐT phụ huynh</label>
            <input type="text" name="parent_phone" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Lớp hiện tại</label>
            <select name="class_id" class="form-select" required>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="studying">Đang học</option>
                <option value="inactive">Nghỉ</option>
            </select>
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
        <a href="{{ route('students.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
