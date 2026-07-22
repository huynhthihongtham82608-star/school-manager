@extends('layouts.app')
@section('title', 'Sửa tổ chuyên môn')

@section('content')
@php($selectedSubjectIds = collect(old('subject_ids', $department->subjects->pluck('id')->all())))

<form method="POST" action="{{ route('departments.update', $department) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã tổ</label>
            <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code', $department->code) }}" required>
            @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label">Tên tổ</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $department->name) }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                @foreach(\App\Models\TeacherDepartment::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $department->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-8">
            <label class="form-label">Môn phụ trách</label>
            <select class="form-select d-none" name="subject_ids[]" multiple data-multi-select-picker-select>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected($selectedSubjectIds->contains($subject->id))>
                        {{ $subject->code }} - {{ $subject->name }}
                    </option>
                @endforeach
            </select>
            <div class="parent-student-picker" data-multi-select-picker data-placeholder="Chưa chọn môn phụ trách" data-empty-text="Không tìm thấy môn học phù hợp." data-selected-text="Đã chọn" data-max-visible-tags="3">
                <div class="parent-student-tags" data-multi-select-tags></div>
                <input type="text" class="parent-student-search" data-multi-select-search placeholder="Tìm theo mã môn hoặc tên môn...">
                <div class="parent-student-dropdown" data-multi-select-dropdown></div>
            </div>
            <div class="text-muted small mt-1">Chỉ hiển thị môn Chính khóa. Mỗi môn chỉ thuộc một tổ chuyên môn.</div>
            @error('subject_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            @error('subject_ids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Tổ trưởng</label>
            <select name="leader_teacher_id" class="form-select">
                <option value="">Chưa phân công</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('leader_teacher_id', $department->leader_teacher_id) === $teacher->id)>{{ $teacher->teacher_code }} - {{ $teacher->name }}</option>
                @endforeach
            </select>
            <div class="form-text">Chỉ hiển thị giáo viên đang thuộc tổ này.</div>
            @error('leader_teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Số giáo viên</label>
            <div class="form-control bg-light">{{ $teachers->count() }}</div>
        </div>
        <div class="col-12">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $department->description) }}</textarea>
            @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="form-actions mt-4">
        <a href="{{ route('departments.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Cập nhật</button>
    </div>
</form>
@include('parents.partials.student-picker')
@endsection
