@extends('layouts.app')
@section('title', 'Thêm phân công')

@section('content')
@php
    $workingYear = $years->first();
    $workingSemester = $semesters->first();
@endphp

<form method="POST" action="{{ route('assignments.store') }}" class="card p-4 shadow-sm">
    @csrf
    <input type="hidden" name="school_year_id" value="{{ old('school_year_id', $workingYear?->id) }}">
    <input type="hidden" name="semester_id" value="{{ old('semester_id', $workingSemester?->id) }}">
    <input type="hidden" name="role" value="{{ \App\Models\TeachingAssignment::ROLE_PRIMARY }}">
    <input type="hidden" name="status" value="{{ \App\Models\TeachingAssignment::STATUS_ACTIVE }}">

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Năm học đang làm việc</label>
            <div class="form-control bg-light">{{ $workingYear->name ?? 'Chưa thiết lập' }}</div>
            @error('school_year_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Học kỳ hiện hành</label>
            <div class="form-control bg-light">{{ $workingSemester?->normalizedName() ?? 'Chưa thiết lập' }}</div>
            @error('semester_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Lớp</label>
            <select name="class_ids[]" class="form-select" multiple size="6" required>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(collect(old('class_ids', []))->contains($class->id))>
                        {{ $class->name }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Có thể chọn nhiều lớp cho cùng một giáo viên và môn học.</div>
            @error('class_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            @error('class_ids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Môn học</label>
            <select name="subject_id" class="form-select" required data-assignment-subject-select>
                <option value="">Chọn môn học</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}"
                        data-departments="{{ $subject->departments->pluck('id')->implode(',') }}"
                        data-department-names="{{ $subject->departments->pluck('name')->join(', ') }}"
                        data-grade-levels="{{ implode(',', $subject->applicableGradeLevels()) }}"
                        @selected(old('subject_id') === $subject->id)>
                        {{ $subject->code }} - {{ $subject->name }}
                    </option>
                @endforeach
            </select>
            <div class="form-text" data-assignment-subject-departments>Chọn môn để xem tổ phụ trách.</div>
            @error('subject_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Lọc theo tổ chuyên môn</label>
            <select class="form-select" data-assignment-department-filter>
                <option value="">Tất cả tổ</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">Giáo viên</label>
            <select name="teacher_id" class="form-select" required data-assignment-teacher>
                <option value="">Chọn giáo viên</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}"
                        data-department="{{ $teacher->department_id }}"
                        data-primary-subject="{{ $teacher->primarySubjectName() }}"
                        @selected(old('teacher_id') === $teacher->id)>
                        {{ $teacher->teacher_code }} - {{ $teacher->name }}{{ $teacher->department ? ' - ' . $teacher->department->name : '' }}
                    </option>
                @endforeach
            </select>
            <div class="form-text text-warning d-none" data-assignment-department-warning>Giáo viên này không thuộc tổ phụ trách môn học.</div>
            @error('teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Điều chỉnh số tiết/tuần</label>
            <input type="number" name="weekly_periods" class="form-control" value="{{ old('weekly_periods') }}" min="1" max="20">
            <div class="form-text">Để trống nếu dùng định mức tiết của môn học.</div>
            @error('weekly_periods')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
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

@include('assignments.partials.department-subject-script')
@endsection
