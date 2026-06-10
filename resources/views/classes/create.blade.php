@extends('layouts.app')
@section('title', 'Thêm lớp học')

@section('content')
<h5 class="mb-3">Thêm lớp học</h5>
<form method="POST" action="{{ route('classes.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Tên lớp</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Khối</label>
            <select name="grade_level" class="form-select" required>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
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
            <label class="form-label">GVCN</label>
            <select name="homeroom_teacher_id" class="form-select">
                <option value="">--Chọn--</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Sĩ số tối đa</label>
            <input type="number" name="capacity" class="form-control" value="45">
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a href="{{ route('classes.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
