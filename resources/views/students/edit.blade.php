@extends('layouts.app')
@section('title', 'Sửa học sinh')

@section('content')
<h5 class="mb-3">Sửa học sinh</h5>

@if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form method="POST" action="{{ route('students.update', $student) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã HS</label>
            <input type="text" name="student_code" class="form-control" value="{{ $student->student_code }}" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">Họ tên</label>
            <input type="text" name="name" class="form-control" value="{{ $student->name }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Giới tính</label>
            <select name="gender" class="form-select">
                @foreach(['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'] as $k => $label)
                    <option value="{{ $k }}" @selected($student->gender === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Ngày sinh</label>
            <input type="date" name="dob" class="form-control" value="{{ $student->dob?->format('Y-m-d') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Địa chỉ</label>
            <input type="text" name="address" class="form-control" value="{{ $student->address }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">SĐT phụ huynh</label>
            <input type="text" name="parent_phone" class="form-control" value="{{ $student->parent_phone }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ $student->email }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Lớp hiện tại</label>
            <select name="class_id" class="form-select" required>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected($class->id === $student->class_id)>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected($year->id === $student->school_year_id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select">
                @foreach(['studying' => 'Đang học', 'inactive' => 'Nghỉ'] as $k => $label)
                    <option value="{{ $k }}" @selected($student->status === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <hr>
        <div class="col-md-4">
            <label class="form-label">Đặt lại mật khẩu</label>
            <input type="password" name="password" class="form-control">
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a href="{{ route('students.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
