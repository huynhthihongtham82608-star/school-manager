@extends('layouts.app')
@section('title', 'Sửa lớp học')

@section('content')
<form method="POST" action="{{ route('classes.update', $class) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Tên lớp</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $class->name) }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Khối</label>
            <select name="grade_level" class="form-select" required data-class-grade>
                @foreach([10, 11, 12] as $grade)
                    <option value="{{ $grade }}" @selected(old('grade_level', $class->grade_level) == $grade)>{{ $grade }}</option>
                @endforeach
            </select>
            @error('grade_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select" required data-class-year>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" data-start-year="{{ $year->start_date?->format('Y') ?: preg_replace('/^.*?((?:19|20|21)\d{2}).*$/', '$1', $year->name) }}" @selected(old('school_year_id', $class->school_year_id) == $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
            @error('school_year_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Niên khóa</label>
            <input type="text" name="cohort" class="form-control" value="{{ old('cohort', $class->cohort) }}" placeholder="2026 - 2029" data-class-cohort data-cohort-autofill="{{ old('cohort', $class->cohort) ? '0' : '1' }}">
            @error('cohort')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Giáo viên chủ nhiệm</label>
            <select name="homeroom_teacher_id" class="form-select">
                <option value="">-- Chọn --</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('homeroom_teacher_id', $class->homeroom_teacher_id) == $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
            @error('homeroom_teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Sức chứa tối đa (1 - 45)</label>
            <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $class->maxCapacity()) }}" min="{{ $class->currentStudentCount() }}" max="45" required>
            <div class="form-text">Sĩ số hiện tại / sức chứa: {{ $class->currentStudentCount() }} / {{ $class->maxCapacity() }}</div>
            @error('capacity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái</label>
            <div class="form-control bg-light">{{ $class->statusLabel() }}</div>
        </div>
    </div>
    <div class="form-actions mt-4">
        <a href="{{ route('classes.index', ['school_year_id' => $class->school_year_id]) }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Cập nhật</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach((form) => {
        const gradeInput = form.querySelector('[data-class-grade]');
        const yearInput = form.querySelector('[data-class-year]');
        const cohortInput = form.querySelector('[data-class-cohort]');

        if (!gradeInput || !yearInput || !cohortInput) {
            return;
        }

        const suggestedCohort = () => {
            const grade = Number.parseInt(gradeInput.value || '', 10);
            const selectedYear = yearInput.options[yearInput.selectedIndex];
            const schoolYearStart = Number.parseInt(selectedYear?.dataset.startYear || '', 10);

            if (!schoolYearStart || ![10, 11, 12].includes(grade)) {
                return '';
            }

            const cohortStart = schoolYearStart - (grade - 10);
            return `${cohortStart} - ${cohortStart + 3}`;
        };

        const fillCohort = () => {
            if (cohortInput.dataset.cohortAutofill === '1') {
                cohortInput.value = suggestedCohort();
            }
        };

        cohortInput.addEventListener('input', () => {
            cohortInput.dataset.cohortAutofill = cohortInput.value.trim() === '' ? '1' : '0';
        });
        gradeInput.addEventListener('change', fillCohort);
        yearInput.addEventListener('change', fillCohort);
        fillCohort();
    });
});
</script>

@endsection
