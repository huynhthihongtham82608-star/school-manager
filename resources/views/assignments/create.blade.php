@extends('layouts.app')
@section('title', 'Thêm phân công')

@section('content')
<form method="POST" action="{{ route('assignments.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected(old('school_year_id') === $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
            @error('school_year_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Học kỳ</label>
            <select name="semester_id" class="form-select" required>
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected(old('semester_id') === $semester->id)>{{ $semester->normalizedName() }}</option>
                @endforeach
            </select>
            @error('semester_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Lớp</label>
            <select name="class_id" class="form-select" required>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(old('class_id') === $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            @error('class_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Môn học</label>
            <select name="subject_id" class="form-select" required>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(old('subject_id') === $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
            @error('subject_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Giáo viên</label>
            <select name="teacher_id" class="form-select" required>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('teacher_id') === $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
            @error('teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Vai trò giảng dạy</label>
            <select name="role" class="form-select" required data-assignment-role>
                @foreach(\App\Models\TeachingAssignment::ROLES as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', \App\Models\TeachingAssignment::ROLE_PRIMARY) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" data-assignment-custom-role-wrap>
            <label class="form-label">Nhập vai trò</label>
            <input type="text" name="custom_role" class="form-control" value="{{ old('custom_role') }}" placeholder="Ví dụ: Ôn thi tốt nghiệp">
            @error('custom_role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                @foreach(\App\Models\TeachingAssignment::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', \App\Models\TeachingAssignment::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label class="form-label">Ghi chú</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
            @error('note')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="form-actions mt-4">
        <a href="{{ route('assignments.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

@include('assignments.partials.role-script')
@endsection
