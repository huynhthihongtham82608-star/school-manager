@extends('layouts.app')
@section('title', 'Thêm lớp học')

@section('content')
<form method="POST" action="{{ route('classes.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Tên lớp</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Khối</label>
            <select name="grade_level" class="form-select" required>
                @foreach([10, 11, 12] as $grade)
                    <option value="{{ $grade }}" @selected(old('grade_level') == $grade)>{{ $grade }}</option>
                @endforeach
            </select>
            @error('grade_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select" required data-class-year>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected(old('school_year_id', $selectedYearId) == $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
            @error('school_year_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Niên khóa</label>
            <input type="text" name="cohort" class="form-control" value="{{ old('cohort') }}" placeholder="2026 - 2029">
            <div class="form-text">Áp dụng cho mọi khối lớp khi khởi tạo lớp.</div>
            @error('cohort')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Giáo viên chủ nhiệm</label>
            <select name="homeroom_teacher_id" class="form-select">
                <option value="">-- Chọn --</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('homeroom_teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
            @error('homeroom_teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Sức chứa tối đa (1 - 45)</label>
            <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 45) }}" min="1" max="45" required>
            @error('capacity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái mặc định</label>
            <div class="form-control bg-light">Bản nháp</div>
        </div>
    </div>
    <div class="form-actions mt-4">
        <a href="{{ route('classes.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

@endsection
