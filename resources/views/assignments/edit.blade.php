@extends('layouts.app')
@section('title', 'Sửa phân công')

@section('content')
<h5 class="mb-3">Sửa phân công giảng dạy</h5>
<form method="POST" action="{{ route('assignments.update', $assignment) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Giáo viên</label>
            <select name="teacher_id" class="form-select" required>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected($teacher->id === $assignment->teacher_id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Lớp</label>
            <select name="class_id" class="form-select" required>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected($class->id === $assignment->class_id)>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Môn</label>
            <select name="subject_id" class="form-select" required>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected($subject->id === $assignment->subject_id)>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm học</label>
            <select name="school_year_id" class="form-select" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected($year->id === $assignment->school_year_id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Lưu</button>
        <a href="{{ route('assignments.index') }}" class="btn btn-link">Hủy</a>
    </div>
</form>
@endsection
