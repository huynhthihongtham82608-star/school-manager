@extends('layouts.app')
@section('title', 'Khởi tạo năm học mới')

@php
    $selectedOptions = old('options', $input['options'] ?? array_keys($options));
    $currentSourceYear = old('source_year_id', $input['source_year_id'] ?? '');
    $currentStartYear = old('start_year', $input['start_year'] ?? '');
    $currentEndYear = old('end_year', $input['end_year'] ?? '');
@endphp

@section('content')
<div class="page-heading">
    <div>
        <h5>Khởi tạo năm học mới</h5>
        <div class="text-muted">Khởi tạo dữ liệu năm học mới từ một năm học đã kết thúc hoặc đã lưu trữ.</div>
    </div>
    <a href="{{ route('school-years.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại
    </a>
</div>

@if(isset($result))
    <div class="card p-4 shadow-sm">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="feature-icon bg-success-subtle text-success">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div>
                <h5 class="mb-1">Khởi tạo thành công</h5>
                <div class="text-muted">
                    Từ năm học <strong>{{ $result['sourceYear']->name }}</strong> sang năm học <strong>{{ $result['targetYear']->name }}</strong>.
                    Năm học mới đang ở trạng thái <strong>Chưa hoạt động</strong>.
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Môn học</div>
                    <div class="fs-4 fw-bold">{{ number_format($result['counts']['subjects'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Giáo viên</div>
                    <div class="fs-4 fw-bold">{{ number_format($result['counts']['teachers'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Phòng học</div>
                    <div class="fs-4 fw-bold">{{ number_format($result['counts']['rooms'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Hồ sơ học sinh</div>
                    <div class="fs-4 fw-bold">{{ number_format($result['counts']['students'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Lớp mới đã tạo</div>
                    <div class="fs-4 fw-bold">{{ number_format($result['counts']['created_classes'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Tài liệu học tập</div>
                    <div class="fs-4 fw-bold">{{ number_format($result['counts']['documents_copied'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Học sinh lên lớp</div>
                    <div class="fs-4 fw-bold">{{ number_format($result['counts']['promote_students'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Học sinh tốt nghiệp</div>
                    <div class="fs-4 fw-bold">{{ number_format($result['counts']['graduate_grade_12'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('school-years.index') }}" class="btn btn-primary">Về danh sách năm học</a>
        </div>
    </div>
@else
    <form method="POST" action="{{ route('school-years.initialize.preview') }}" class="card p-4 shadow-sm" data-initialize-preview-form>
        @csrf

        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-primary-subtle text-primary border">Bước 1</span>
            <h6 class="mb-0">Chọn năm học nguồn và năm học mới</h6>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Năm học nguồn</label>
                <select name="source_year_id" class="form-select" required>
                    <option value="">Chọn năm học nguồn</option>
                    @foreach($sourceYears as $year)
                        <option value="{{ $year->id }}" @selected($currentSourceYear == $year->id)>
                            {{ $year->name }} - {{ $year->statusLabel() }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Chỉ được khởi tạo từ năm học đã kết thúc hoặc đã lưu trữ.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Năm học mới</label>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" name="start_year" value="{{ $currentStartYear }}" class="form-control" min="1900" max="2100" placeholder="2026" required data-start-year>
                    <span class="fw-bold text-muted">-</span>
                    <input type="number" name="end_year" value="{{ $currentEndYear }}" class="form-control" min="1901" max="2101" placeholder="2027" required data-end-year>
                </div>
                <div class="form-text">Năm kết thúc phải bằng năm bắt đầu + 1.</div>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-primary-subtle text-primary border">Bước 2</span>
            <h6 class="mb-0">Khởi tạo dữ liệu năm học mới</h6>
        </div>

        <div class="row g-3">
            @foreach($options as $key => $label)
                <div class="col-md-4 col-sm-6">
                    <label class="border rounded-3 p-3 h-100 d-flex align-items-start gap-2 option-card">
                        <input class="form-check-input mt-1" type="checkbox" name="options[]" value="{{ $key }}" @checked(in_array($key, $selectedOptions, true))>
                        <span>
                            <span class="fw-semibold d-block">{{ $label }}</span>
                            @if($key === 'rooms')
                                <span class="text-muted small">Ghi nhận phòng học đang có trong dữ liệu nguồn, không tạo bảng phòng học mới.</span>
                            @elseif($key === 'students')
                                <span class="text-muted small">Giữ nguyên hồ sơ học sinh hiện có, không tạo hồ sơ mới.</span>
                            @elseif($key === 'documents')
                                <span class="text-muted small">Sao chép tài liệu gắn với lớp được thăng lên năm học mới.</span>
                            @elseif($key === 'promote_students')
                                <span class="text-muted small">Khối 10 lên 11, khối 11 lên 12.</span>
                            @elseif($key === 'graduate_grade_12')
                                <span class="text-muted small">Đánh dấu học sinh khối 12 là đã tốt nghiệp.</span>
                            @else
                                <span class="text-muted small">Sử dụng dữ liệu dùng chung hiện có của hệ thống.</span>
                            @endif
                        </span>
                    </label>
                </div>
            @endforeach
        </div>

        <div class="alert alert-light border mt-4 mb-0">
            Không khởi tạo lại điểm số, hạnh kiểm, điểm danh, lịch thi, thời khóa biểu, phân công giảng dạy, thông báo, sự kiện hoặc tin nhắn vì đây là dữ liệu riêng theo từng năm học.
        </div>

        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('school-years.index') }}" class="btn btn-secondary">Hủy</a>
            <button class="btn btn-primary">Xem trước</button>
        </div>
    </form>

    @if(isset($preview))
        <div class="card p-4 shadow-sm mt-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-primary-subtle text-primary border">Bước 3</span>
                <h6 class="mb-0">Xem trước dữ liệu sẽ được khởi tạo</h6>
            </div>

            <div class="row g-3">
                @foreach($options as $key => $label)
                    @if(in_array($key, $preview['selected_options'], true))
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small">{{ $label }}</div>
                                <div class="fs-4 fw-bold">{{ number_format($preview['counts'][$key] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="alert alert-warning mt-4">
                Vui lòng kiểm tra kỹ. Sau khi xác nhận, hệ thống sẽ tạo năm học <strong>{{ $preview['target_name'] }}</strong> ở trạng thái <strong>Chưa hoạt động</strong> và thực hiện các lựa chọn ở trên.
            </div>

            <form method="POST" action="{{ route('school-years.initialize.store') }}" data-initialize-submit-form>
                @csrf
                <input type="hidden" name="source_year_id" value="{{ $input['source_year_id'] }}">
                <input type="hidden" name="start_year" value="{{ $input['start_year'] }}">
                <input type="hidden" name="end_year" value="{{ $input['end_year'] }}">
                <input type="hidden" name="confirm_initialization" value="1">
                @foreach($preview['selected_options'] as $option)
                    <input type="hidden" name="options[]" value="{{ $option }}">
                @endforeach

                <div class="initialize-loading alert alert-light border d-none mt-3" data-initialize-loading>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
                        <strong>Đang khởi tạo dữ liệu năm học mới...</strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%;"></div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('school-years.initialize.form') }}" class="btn btn-secondary">Hủy</a>
                    <button class="btn btn-primary" data-initialize-submit>
                        <i class="bi bi-check2-circle me-1"></i>Xác nhận khởi tạo
                    </button>
                </div>
            </form>
        </div>
    @endif
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const syncEndYear = (startInput, endInput) => {
        const startYear = Number.parseInt(startInput?.value || '', 10);
        if (!Number.isNaN(startYear) && endInput && !endInput.dataset.touched) {
            endInput.value = startYear + 1;
        }
    };

    document.querySelectorAll('[data-start-year]').forEach((startInput) => {
        const form = startInput.closest('form');
        const endInput = form?.querySelector('[data-end-year]');

        endInput?.addEventListener('input', () => {
            endInput.dataset.touched = '1';
        });

        startInput.addEventListener('input', () => syncEndYear(startInput, endInput));
        syncEndYear(startInput, endInput);
    });

    document.querySelectorAll('[data-initialize-submit-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const loading = form.querySelector('[data-initialize-loading]');
            const submitButton = form.querySelector('[data-initialize-submit]');
            loading?.classList.remove('d-none');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Đang xử lý';
            }
        });
    });
});
</script>
@endsection
