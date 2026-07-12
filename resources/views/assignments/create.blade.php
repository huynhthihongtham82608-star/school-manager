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
            <select name="class_id" class="form-select" required>
                <option value="">Chọn lớp</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(old('class_id') === $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            @error('class_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Số tiết/tuần</label>
            <input type="number" name="weekly_periods" class="form-control" value="{{ old('weekly_periods') }}" min="1" max="20">
            @error('weekly_periods')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Giáo viên</label>
            <select name="teacher_id" class="form-select" required data-assignment-teacher>
                <option value="">Chọn giáo viên</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" data-subject="{{ $teacher->primarySubjectName() }}" @selected(old('teacher_id') === $teacher->id)>
                        {{ $teacher->teacher_code }} - {{ $teacher->name }}
                    </option>
                @endforeach
            </select>
            @error('teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Môn chính</label>
            <input type="text" class="form-control bg-light" value="Chọn giáo viên để hiển thị môn chính" readonly data-assignment-subject>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const teacherSelect = document.querySelector('[data-assignment-teacher]');
    const subjectInput = document.querySelector('[data-assignment-subject]');

    const updateSubject = () => {
        const selected = teacherSelect?.selectedOptions?.[0];
        subjectInput.value = selected?.dataset?.subject || 'Chưa cấu hình môn chính';
    };

    teacherSelect?.addEventListener('change', updateSubject);
    updateSubject();
});
</script>
@endsection
