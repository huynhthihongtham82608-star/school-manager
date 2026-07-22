@extends('layouts.app')
@section('title', 'Thêm tổ chuyên môn')

@section('content')
<form method="POST" action="{{ route('departments.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã tổ</label>
            <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code') }}" placeholder="TOAN_TIN" required>
            @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label">Tên tổ</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Tổ Toán - Tin" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                @foreach(\App\Models\TeacherDepartment::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', \App\Models\TeacherDepartment::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-8">
            <label class="form-label">Môn phụ trách</label>
            <select class="form-select d-none" name="subject_ids[]" multiple data-multi-select-picker-select>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(collect(old('subject_ids', []))->contains($subject->id))>
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
            <div class="form-control bg-light text-muted">Chọn tổ trưởng sau khi đã gán giáo viên vào tổ.</div>
            @error('leader_teacher_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="form-actions mt-4">
        <a href="{{ route('departments.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>
@include('parents.partials.student-picker')
@endsection
