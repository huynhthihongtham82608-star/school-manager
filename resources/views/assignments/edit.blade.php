@extends('layouts.app')
@section('title', 'Sửa phân công')

@section('content')
<form method="POST" action="{{ route('assignments.update', $assignment) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
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
            <div class="form-control bg-light">{{ $assignment->subject->name ?? '-' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Giáo viên</label>
            <select name="teacher_id" class="form-select" required>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('teacher_id', $assignment->teacher_id) === $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
            @error('teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Vai trò giảng dạy</label>
            <select name="role" class="form-select" required data-assignment-role>
                @foreach(\App\Models\TeachingAssignment::ROLES as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $assignment->role) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" data-assignment-custom-role-wrap>
            <label class="form-label">Nhập vai trò</label>
            <input type="text" name="custom_role" class="form-control" value="{{ old('custom_role', $assignment->custom_role) }}" placeholder="Ví dụ: Chuyên đề">
            @error('custom_role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
        <div class="col-md-8">
            <label class="form-label">Ghi chú</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note', $assignment->note) }}</textarea>
            @error('note')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="form-actions mt-4">
        <a href="{{ route('assignments.index', ['school_year_id' => $assignment->school_year_id]) }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Cập nhật</button>
    </div>
</form>

@include('assignments.partials.role-script')
@endsection
