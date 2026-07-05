@extends('layouts.app')
@section('title', 'Thêm môn học')

@section('content')
<form method="POST" action="{{ route('subjects.store') }}" class="card p-4 shadow-sm">
    @csrf
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã môn</label>
            <input type="text" name="code" class="form-control" value="{{ old('code') }}" maxlength="50" required>
            @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label">Tên môn</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Hệ số môn</label>
            <input type="number" name="credit" class="form-control" value="{{ old('credit', 1) }}" min="1" max="10" required>
            @error('credit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Loại môn</label>
            <select name="type" class="form-select" required>
                @foreach(\App\Models\Subject::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', \App\Models\Subject::TYPE_REQUIRED) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                @foreach(\App\Models\Subject::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', \App\Models\Subject::STATUS_ACTIVE) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="mt-4 pt-3 border-top">
        <h6 class="fw-semibold mb-1">Định mức tiết học theo khối <span class="text-muted fw-normal">(không bắt buộc)</span></h6>
        <div class="text-muted small mb-3">Nhập số tiết mỗi tuần cho từng khối. Có thể để trống và cấu hình sau.</div>
        <div class="row g-3">
            @foreach($gradeLevels as $gradeLevel)
                <div class="col-md-3">
                    <label class="form-label">Khối {{ $gradeLevel }}</label>
                    <input type="number" name="period_norms[{{ $gradeLevel }}]" class="form-control" value="{{ old('period_norms.' . $gradeLevel) }}" min="1" max="10" placeholder="Số tiết/tuần">
                    @error('period_norms.' . $gradeLevel)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    </div>

    <div class="form-actions mt-4">
        <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>
@endsection
