@extends('layouts.app')
@section('title', 'Sửa phụ huynh')

@section('content')
@php
    $currentRelation = old('relation', $parent->students->first()?->pivot?->relation ?? \App\Models\ParentProfile::RELATION_GUARDIAN);
@endphp

<form method="POST" action="{{ route('parents.update', $parent) }}" class="card shadow-sm p-4">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã phụ huynh</label>
            <div class="form-control bg-light text-muted">{{ $parent->parent_code ?: 'Sẽ tự bổ sung khi lưu' }}</div>
        </div>
        <div class="col-md-5">
            <label class="form-label">Họ tên phụ huynh</label>
            <input class="form-control" name="name" value="{{ old('name', $parent->name) }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Quan hệ</label>
            <select class="form-select" name="relation" required>
                @foreach(\App\Models\ParentProfile::relationLabels() as $value => $label)
                    <option value="{{ $value }}" @selected($currentRelation === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('relation')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Số điện thoại</label>
            <input class="form-control" name="phone" value="{{ old('phone', $parent->phone) }}" required>
            <div class="text-muted small mt-1">Số điện thoại được dùng làm tên đăng nhập.</div>
            @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Địa chỉ</label>
            <input class="form-control" name="address" value="{{ old('address', $parent->address) }}">
            @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-7">
            <label class="form-label">Liên kết học sinh</label>
            <select class="form-select d-none" name="student_ids[]" multiple data-parent-student-select>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected(collect(old('student_ids', $parent->students->pluck('id')->all()))->contains($student->id))>
                        {{ $student->student_code }} - {{ $student->name }}{{ $student->classRoom ? ' - ' . $student->classRoom->name : '' }}
                    </option>
                @endforeach
            </select>
            <div class="parent-student-picker" data-parent-student-picker>
                <div class="parent-student-tags" data-parent-student-tags></div>
                <input type="text" class="parent-student-search" data-parent-student-search placeholder="Tìm theo mã học sinh hoặc họ tên...">
                <div class="parent-student-dropdown" data-parent-student-dropdown></div>
            </div>
            <div class="text-muted small mt-1">Nhập mã học sinh hoặc họ tên để tìm nhanh. Đặt lại mật khẩu thực hiện ở menu 3 chấm ngoài danh sách.</div>
            @error('student_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="form-actions mt-4">
        <a class="btn btn-secondary" href="{{ route('parents.index') }}">Hủy</a>
        <button class="btn btn-primary">Cập nhật</button>
    </div>
</form>
@include('parents.partials.student-picker')
@endsection
