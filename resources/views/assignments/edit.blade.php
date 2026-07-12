@extends('layouts.app')
@section('title', 'Sửa phân công')

@section('content')
<form method="POST" action="{{ route('assignments.update', $assignment) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <input type="hidden" name="role" value="{{ \App\Models\TeachingAssignment::ROLE_PRIMARY }}">

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Năm học</label>
            <div class="form-control bg-light">{{ $assignment->schoolYear->name ?? '-' }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Học kỳ</label>
            <div class="form-control bg-light">{{ $assignment->semester?->normalizedName() ?? '-' }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Lớp</label>
            <div class="form-control bg-light">{{ $assignment->classRoom->name ?? '-' }}</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Số tiết/tuần</label>
            <input type="number" name="weekly_periods" class="form-control" value="{{ old('weekly_periods', $assignment->weekly_periods) }}" min="1" max="20">
            @error('weekly_periods')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Giáo viên</label>
            <select name="teacher_id" class="form-select" required data-assignment-teacher>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" data-subject="{{ $teacher->primarySubjectName() }}" @selected(old('teacher_id', $assignment->teacher_id) === $teacher->id)>
                        {{ $teacher->teacher_code }} - {{ $teacher->name }}
                    </option>
                @endforeach
            </select>
            @error('teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Môn chính</label>
            <input type="text" class="form-control bg-light" value="{{ $assignment->teacher?->primarySubjectName() ?? ($assignment->subject->name ?? '-') }}" readonly data-assignment-subject>
        </div>
        <div class="col-md-4">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                @foreach(\App\Models\TeachingAssignment::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $assignment->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8"></div>
        <div class="col-12">
            <label class="form-label">Ghi chú</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note', $assignment->note) }}</textarea>
            @error('note')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="form-actions mt-4">
        <a href="{{ route('assignments.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Cập nhật</button>
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
