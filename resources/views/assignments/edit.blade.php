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
            <label class="form-label">Môn học</label>
            <select name="subject_id" class="form-select" required data-assignment-subject-select>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}"
                        data-departments="{{ $subject->departments->pluck('id')->implode(',') }}"
                        data-department-names="{{ $subject->departments->pluck('name')->join(', ') }}"
                        @selected(old('subject_id', $assignment->subject_id) === $subject->id)>
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
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}"
                        data-department="{{ $teacher->department_id }}"
                        data-primary-subject="{{ $teacher->primarySubjectName() }}"
                        @selected(old('teacher_id', $assignment->teacher_id) === $teacher->id)>
                        {{ $teacher->teacher_code }} - {{ $teacher->name }}{{ $teacher->department ? ' - ' . $teacher->department->name : '' }}
                    </option>
                @endforeach
            </select>
            <div class="form-text text-warning d-none" data-assignment-department-warning>Giáo viên này không thuộc tổ phụ trách môn học.</div>
            @error('teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Điều chỉnh số tiết/tuần</label>
            <input type="number" name="weekly_periods" class="form-control" value="{{ old('weekly_periods', $assignment->weekly_periods) }}" min="1" max="20">
            <div class="form-text">
                Để trống nếu dùng định mức môn học{{ $assignment->standardWeeklyPeriods() ? ': ' . $assignment->standardWeeklyPeriods() . ' tiết/tuần' : '.' }}
            </div>
            @error('weekly_periods')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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

@include('assignments.partials.department-subject-script')
@endsection
