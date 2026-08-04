@extends('layouts.app')
@section('title', 'Thêm môn học')

@section('content')
@php
    $selectedGradeLevels = collect(old('applicable_grade_levels', $gradeLevels))->map(fn ($gradeLevel) => (int) $gradeLevel)->all();
@endphp
<style>
    .subject-grade-checkbox-row {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: .75rem;
    }

    .subject-grade-option {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .5rem .75rem;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        color: #374151;
        background: #fff7ed;
        font-size: .95rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .subject-grade-option input {
        border-color: #fdba74;
    }

    .subject-grade-option input:checked {
        border-color: #ea580c;
        background-color: #ea580c;
    }
</style>
<form method="POST" action="{{ route('subjects.store') }}" class="card p-4 shadow-sm" data-academic-modal-size="2xl">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Mã môn</label>
            <input type="text" class="form-control" value="{{ $nextCode }}" readonly disabled>
            <div class="form-text">Mã môn được hệ thống tự sinh theo định dạng MH001.</div>
            @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Tên môn</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Hệ số môn</label>
            <input type="number" name="credit" class="form-control" value="{{ old('credit', 1) }}" min="1" max="10" required>
            @error('credit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Loại môn</label>
            <select name="type" id="subjectTypeSelect" class="form-select" required>
                @foreach(\App\Models\Subject::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', \App\Models\Subject::TYPE_OFFICIAL) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Hình thức đánh giá</label>
            <select name="assessment_type" class="form-select" required>
                @foreach(\App\Models\Subject::ASSESSMENT_TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('assessment_type', \App\Models\Subject::ASSESSMENT_GRADE_10) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="form-text">Chọn Không đánh giá cho môn chỉ xếp lịch như Chào cờ, Sinh hoạt lớp hoặc hoạt động phát sinh.</div>
            @error('assessment_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Khối học áp dụng</label>
            <div class="subject-grade-checkbox-row">
                @foreach($gradeLevels as $gradeLevel)
                    <label class="subject-grade-option">
                        <input class="form-check-input m-0" type="checkbox" name="applicable_grade_levels[]" value="{{ $gradeLevel }}" data-subject-grade-checkbox="{{ $gradeLevel }}" @checked(in_array((int) $gradeLevel, $selectedGradeLevels, true))>
                        Khối {{ $gradeLevel }}
                    </label>
                @endforeach
            </div>
            @error('applicable_grade_levels')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            @error('applicable_grade_levels.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Trạng thái</label>
            <input type="hidden" name="status" value="{{ old('status', \App\Models\Subject::STATUS_ACTIVE) }}">
            <div>
                <span class="academic-form-badge success"><i class="bi bi-check-circle"></i>Hoạt động</span>
            </div>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Tổ phụ trách</label>
            <div class="form-control bg-light text-muted">Chỉ áp dụng cho môn Chính khóa</div>
            <div class="form-text">Cấu hình tổ phụ trách tại Quản lý Tổ chuyên môn sau khi tạo môn.</div>
        </div>
    </div>

    <div id="periodNormSection" class="academic-subform-box mt-4 {{ old('type', \App\Models\Subject::TYPE_OFFICIAL) === \App\Models\Subject::TYPE_OFFICIAL ? '' : 'd-none' }}">
        <h6 class="fw-semibold mb-1">Định mức tiết học theo khối <span class="text-muted fw-normal">(không bắt buộc)</span></h6>
        <div class="text-muted small mb-3">Nhập số tiết mỗi tuần cho từng khối. Có thể để trống và cấu hình sau.</div>
        <div class="row g-3">
            @foreach($gradeLevels as $gradeLevel)
                <div class="col-md-4">
                    <label class="form-label">Khối {{ $gradeLevel }}</label>
                    <input type="number" name="period_norms[{{ $gradeLevel }}]" class="form-control" value="{{ old('period_norms.' . $gradeLevel) }}" min="1" max="10" placeholder="Số tiết/tuần" data-period-norm-input="{{ $gradeLevel }}">
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('subjectTypeSelect');
    const periodSection = document.getElementById('periodNormSection');
    const gradeCheckboxes = document.querySelectorAll('[data-subject-grade-checkbox]');

    if (!typeSelect || !periodSection) {
        return;
    }

    const syncPeriodNormInputs = () => {
        gradeCheckboxes.forEach((checkbox) => {
            const input = document.querySelector(`[data-period-norm-input="${checkbox.dataset.subjectGradeCheckbox}"]`);
            if (!input) return;
            input.disabled = !checkbox.checked;
            input.closest('.col-md-4')?.classList.toggle('opacity-50', !checkbox.checked);
        });
    };

    const togglePeriodSection = () => {
        periodSection.classList.toggle('d-none', typeSelect.value !== '{{ \App\Models\Subject::TYPE_OFFICIAL }}');
        syncPeriodNormInputs();
    };

    typeSelect.addEventListener('change', togglePeriodSection);
    gradeCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', syncPeriodNormInputs));
    togglePeriodSection();
});
</script>
@endsection
