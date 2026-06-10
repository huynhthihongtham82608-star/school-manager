@extends('layouts.app')
@section('title', 'Sửa lớp học')

@section('content')
<h5 class="mb-3">Sửa lớp học</h5>
<form method="POST" action="{{ route('classes.update', $class) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Tên lớp</label>
            <input type="text" name="name" class="form-control" value="{{ $class->name }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Khối</label>
            <select name="grade_level" class="form-select" required>
                @foreach([10,11,12] as $g)
                    <option value="{{ $g }}" @selected($g == $class->grade_level)>{{ $g }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected($year->id === $class->school_year_id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">GVCN</label>
            <select name="homeroom_teacher_id" class="form-select">
                <option value="">--Chọn--</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected($teacher->id === $class->homeroom_teacher_id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Sĩ số tối đa</label>
            <input type="number" name="capacity" class="form-control" value="{{ $class->capacity }}">
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a href="{{ route('classes.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
