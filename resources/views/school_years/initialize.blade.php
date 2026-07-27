@extends('layouts.app')
@section('title', 'Khởi tạo năm học mới')

@php
    $selectedOptions = old('options', $input['options'] ?? array_keys($options));
    $currentSourceYear = old('source_year_id', $input['source_year_id'] ?? '');
    $currentStartYear = old('start_year', $input['start_year'] ?? '');
    $currentEndYear = old('end_year', $input['end_year'] ?? '');
    $hasPreview = isset($preview);
@endphp

@section('content')
<x-page-header
    title="Khởi tạo năm học mới"
    subtitle="Khởi tạo dữ liệu năm học mới từ một năm học đã kết thúc hoặc đã lưu trữ."
>
    <a href="{{ route('school-years.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại
    </a>
</x-page-header>

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
                    <div class="text-muted small">Lớp mới đã tạo</div>
                    <div class="fs-4 fw-bold">{{ number_format($result['counts']['created_classes'] ?? 0, 0, ',', '.') }}</div>
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
    <section class="academic-initialize-panel" data-initialize-wizard data-initial-step="{{ $hasPreview ? 3 : 1 }}">
        <div class="academic-initialize-steps" aria-label="Tiến trình khởi tạo năm học">
            <div class="academic-initialize-step" data-wizard-indicator="1">
                <span>1</span>
                <strong>Chọn năm học</strong>
            </div>
            <i class="bi bi-arrow-right"></i>
            <div class="academic-initialize-step" data-wizard-indicator="2">
                <span>2</span>
                <strong>Thiết lập dữ liệu</strong>
            </div>
            <i class="bi bi-arrow-right"></i>
            <div class="academic-initialize-step" data-wizard-indicator="3">
                <span>3</span>
                <strong>Xem trước và xác nhận</strong>
            </div>
        </div>

        <form method="POST" action="{{ route('school-years.initialize.preview') }}" data-initialize-preview-form>
            @csrf

            <div class="academic-wizard-panel" data-wizard-panel="1">
                <div class="academic-wizard-heading">
                    <h5>Bước 1: Chọn năm học nguồn và năm học mới</h5>
                    <p>Thiết lập năm học nguồn để hệ thống chuẩn bị dữ liệu chuyển tiếp.</p>
                </div>

                <div class="academic-initialize-grid">
                    <div>
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
                    <div>
                        <label class="form-label">Năm học mới</label>
                        <div class="academic-year-range">
                            <input type="number" name="start_year" value="{{ $currentStartYear }}" class="form-control" min="1900" max="2100" placeholder="2026" required data-start-year>
                            <span class="fw-bold text-muted">-</span>
                            <input type="number" name="end_year" value="{{ $currentEndYear }}" class="form-control" min="1901" max="2101" placeholder="2027" required data-end-year>
                        </div>
                        <div class="form-text">Năm kết thúc phải bằng năm bắt đầu + 1.</div>
                    </div>
                </div>

                <div class="academic-wizard-actions">
                    <button type="button" class="btn btn-primary" data-step-next="2">
                        Tiếp theo <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

            <div class="academic-wizard-panel" data-wizard-panel="2">
                <div class="academic-wizard-heading">
                    <h5>Bước 2: Khởi tạo dữ liệu năm học mới</h5>
                    <p>Chọn các nghiệp vụ chuyển lớp cần thực hiện trước khi xem trước dữ liệu.</p>
                </div>

                <div class="academic-initialize-options">
                    @foreach($options as $key => $label)
                        <div>
                            <label class="academic-initialize-option">
                                <input class="form-check-input" type="checkbox" name="options[]" value="{{ $key }}" @checked(in_array($key, $selectedOptions, true))>
                                <span>
                                    <span class="fw-semibold d-block">{{ $label }}</span>
                                    @if($key === 'promote_students')
                                        <span class="text-muted small">Khối 10 lên 11, khối 11 lên 12.</span>
                                    @elseif($key === 'graduate_grade_12')
                                        <span class="text-muted small">Đánh dấu học sinh khối 12 là đã tốt nghiệp.</span>
                                    @endif
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="academic-initialize-rules">
                    <h6><i class="bi bi-shield-check me-1"></i>Quy tắc hệ thống</h6>
                    <ul>
                        <li>Môn học, giáo viên, phòng học và tài liệu học tập là dữ liệu dùng chung của toàn trường nên không được sao chép khi tạo năm học mới.</li>
                        <li>Không khởi tạo lại điểm số, hạnh kiểm, điểm danh, lịch kiểm tra, thời khóa biểu, phân công giảng dạy, thông báo, sự kiện hoặc tin nhắn vì đây là dữ liệu riêng theo từng năm học.</li>
                    </ul>
                </div>

                <div class="academic-wizard-actions">
                    <button type="button" class="btn btn-secondary" data-step-prev="1">
                        <i class="bi bi-arrow-left me-1"></i>Quay lại
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Tiếp theo <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </form>

        @if($hasPreview)
            <div class="academic-wizard-panel" data-wizard-panel="3">
                <div class="academic-wizard-heading">
                    <h5>Bước 3: Xem trước dữ liệu sẽ được khởi tạo</h5>
                    <p>Vui lòng kiểm tra kỹ. Sau khi xác nhận, hệ thống sẽ tạo năm học <strong>{{ $preview['target_name'] }}</strong> ở trạng thái <strong>Chưa hoạt động</strong>.</p>
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

                    <div class="academic-preview-summary">
                        <div>
                            <span>Thăng lớp</span>
                            <strong>{{ number_format($preview['counts']['promote_students'] ?? 0, 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span>Tốt nghiệp</span>
                            <strong>{{ number_format($preview['counts']['graduate_grade_12'] ?? 0, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="academic-preview-table">
                        <div class="academic-preview-table-head">
                            <div>
                                <h6>Danh sách học sinh thăng lớp</h6>
                                <p>Chỉ các học sinh được chọn mới được chuyển sang lớp mới.</p>
                            </div>
                            <label class="form-check d-flex align-items-center gap-2 mb-0">
                                <input class="form-check-input" type="checkbox" data-select-all="promote" checked>
                                <span class="fw-semibold">Chọn tất cả</span>
                            </label>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" data-admin-table-skip>
                                <thead>
                                    <tr>
                                        <th style="width: 44px;"></th>
                                        <th>Mã học sinh</th>
                                        <th>Họ tên</th>
                                        <th>Lớp hiện tại</th>
                                        <th>Lớp mới</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($preview['promotion_groups'] as $group)
                                        @foreach($group['students'] as $student)
                                            <tr>
                                                <td>
                                                    <input class="form-check-input" type="checkbox" name="promote_student_ids[]" value="{{ $student->id }}" data-select-item="promote" checked>
                                                </td>
                                                <td class="fw-semibold">{{ $student->student_code }}</td>
                                                <td>{{ $student->name }}</td>
                                                <td>{{ $group['source_class']->name }}</td>
                                                <td>{{ $group['target_name'] }}</td>
                                                <td><span class="badge {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span></td>
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="6">
                                                <div class="empty-state"><i class="bi bi-person-check"></i>Không có học sinh đủ điều kiện thăng lớp.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="academic-preview-table">
                        <div class="academic-preview-table-head">
                            <div>
                                <h6>Danh sách học sinh tốt nghiệp</h6>
                                <p>Chỉ các học sinh được chọn mới được cập nhật trạng thái tốt nghiệp.</p>
                            </div>
                            <label class="form-check d-flex align-items-center gap-2 mb-0">
                                <input class="form-check-input" type="checkbox" data-select-all="graduate" checked>
                                <span class="fw-semibold">Chọn tất cả</span>
                            </label>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" data-admin-table-skip>
                                <thead>
                                    <tr>
                                        <th style="width: 44px;"></th>
                                        <th>Mã học sinh</th>
                                        <th>Họ tên</th>
                                        <th>Lớp</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($preview['graduation_groups'] as $group)
                                        @foreach($group['students'] as $student)
                                            <tr>
                                                <td>
                                                    <input class="form-check-input" type="checkbox" name="graduate_student_ids[]" value="{{ $student->id }}" data-select-item="graduate" checked>
                                                </td>
                                                <td class="fw-semibold">{{ $student->student_code }}</td>
                                                <td>{{ $student->name }}</td>
                                                <td>{{ $group['class']->name }}</td>
                                                <td><span class="badge {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span></td>
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="5">
                                                <div class="empty-state"><i class="bi bi-mortarboard"></i>Không có học sinh đủ điều kiện đánh dấu tốt nghiệp.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="initialize-loading alert alert-light border d-none mt-3" data-initialize-loading>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
                            <strong>Đang khởi tạo dữ liệu năm học mới...</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%;"></div>
                        </div>
                    </div>

                    <div class="academic-wizard-actions">
                        <button type="button" class="btn btn-secondary" data-step-prev="2">
                            <i class="bi bi-arrow-left me-1"></i>Quay lại
                        </button>
                        <button class="btn btn-danger" data-initialize-submit>
                            <i class="bi bi-rocket-takeoff me-1"></i>Xác nhận khởi tạo năm học mới
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </section>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const wizard = document.querySelector('[data-initialize-wizard]');
    let currentStep = Number.parseInt(wizard?.dataset.initialStep || '1', 10);

    const showStep = (step) => {
        if (!wizard) {
            return;
        }

        currentStep = step;
        wizard.querySelectorAll('[data-wizard-panel]').forEach((panel) => {
            panel.hidden = Number.parseInt(panel.dataset.wizardPanel, 10) !== currentStep;
        });

        wizard.querySelectorAll('[data-wizard-indicator]').forEach((indicator) => {
            const index = Number.parseInt(indicator.dataset.wizardIndicator, 10);
            indicator.classList.toggle('active', index === currentStep);
            indicator.classList.toggle('completed', index < currentStep);
        });
    };

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

    document.querySelectorAll('[data-step-next]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetStep = Number.parseInt(button.dataset.stepNext, 10);
            const panel = button.closest('[data-wizard-panel]');
            const invalid = panel?.querySelector(':invalid');

            if (invalid) {
                invalid.reportValidity();
                return;
            }

            showStep(targetStep);
        });
    });

    document.querySelectorAll('[data-step-prev]').forEach((button) => {
        button.addEventListener('click', () => showStep(Number.parseInt(button.dataset.stepPrev, 10)));
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

    const refreshSelectAll = (group) => {
        const items = Array.from(document.querySelectorAll(`[data-select-item="${group}"]`));
        const selectAll = document.querySelector(`[data-select-all="${group}"]`);

        if (!selectAll || !items.length) {
            return;
        }

        selectAll.checked = items.every((item) => item.checked);
        selectAll.indeterminate = items.some((item) => item.checked) && !selectAll.checked;
    };

    document.querySelectorAll('[data-select-all]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const group = checkbox.dataset.selectAll;
            document.querySelectorAll(`[data-select-item="${group}"]`).forEach((item) => {
                item.checked = checkbox.checked;
            });
            checkbox.indeterminate = false;
        });
    });

    document.querySelectorAll('[data-select-item]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => refreshSelectAll(checkbox.dataset.selectItem));
    });

    ['promote', 'graduate'].forEach(refreshSelectAll);
    showStep(currentStep);
});
</script>
@endsection
