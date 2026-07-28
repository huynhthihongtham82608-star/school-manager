@extends('layouts.app')
@section('title', 'Sửa môn học')

@section('content')
<form method="POST" action="{{ route('subjects.update', $subject) }}" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã môn</label>
            <input type="text" class="form-control" value="{{ $subject->code }}" readonly disabled>
            <div class="form-text">Mã môn được quản lý tự động theo định dạng MH001.</div>
            @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-5">
            <label class="form-label">Tên môn</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $subject->name) }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Hệ số môn</label>
            <input type="number" name="credit" class="form-control" value="{{ old('credit', $subject->credit) }}" min="1" max="10" required>
            @error('credit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Loại môn</label>
            <select name="type" id="subjectTypeSelect" class="form-select" required>
                @foreach(\App\Models\Subject::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $subject->isScorable() ? \App\Models\Subject::TYPE_OFFICIAL : $subject->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                @foreach(\App\Models\Subject::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $subject->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Tổ phụ trách</label>
            <div class="form-control bg-light text-muted">
                {{ $subject->isScorable() ? ($subject->departments->pluck('name')->join(', ') ?: 'Chưa phân tổ') : 'Không áp dụng cho môn Chủ nhiệm/Hoạt động' }}
            </div>
            <div class="form-text">Chỉ môn Chính khóa mới cần cấu hình tổ chuyên môn.</div>
        </div>
    </div>

    <div id="periodNormSection" class="mt-4 pt-3 border-top {{ old('type', $subject->isScorable() ? \App\Models\Subject::TYPE_OFFICIAL : $subject->type) === \App\Models\Subject::TYPE_OFFICIAL ? '' : 'd-none' }}">
        <h6 class="fw-semibold mb-1">Định mức tiết học theo khối <span class="text-muted fw-normal">(không bắt buộc)</span></h6>
        <div class="text-muted small mb-3">Nhập số tiết mỗi tuần cho từng khối. Để trống nếu môn chưa áp dụng cho khối đó.</div>
        <div class="row g-3">
            @foreach($gradeLevels as $gradeLevel)
                @php
                    $norm = $subject->periodNormForGrade($gradeLevel);
                @endphp
                <div class="col-md-3">
                    <label class="form-label">Khối {{ $gradeLevel }}</label>
                    <input type="number" name="period_norms[{{ $gradeLevel }}]" class="form-control" value="{{ old('period_norms.' . $gradeLevel, $norm?->periods_per_week) }}" min="1" max="10" placeholder="Số tiết/tuần">
                    @error('period_norms.' . $gradeLevel)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    </div>

    <div class="form-actions mt-4">
        <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Cập nhật</button>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('subjectTypeSelect');
    const periodSection = document.getElementById('periodNormSection');

    if (!typeSelect || !periodSection) {
        return;
    }

    const togglePeriodSection = () => {
        periodSection.classList.toggle('d-none', typeSelect.value !== '{{ \App\Models\Subject::TYPE_OFFICIAL }}');
    };

    typeSelect.addEventListener('change', togglePeriodSection);
    togglePeriodSection();
});
</script>
@endsection
