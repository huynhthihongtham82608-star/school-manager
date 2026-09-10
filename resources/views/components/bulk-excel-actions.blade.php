@props([
    'module',
    'context' => [],
    'allowImport' => true,
    'showTemplateButton' => true,
    'templateButtonLabel' => 'Xuất File Mẫu Excel',
    'exportButtonLabel' => 'Xuất danh sách Excel',
    'importButtonLabel' => 'Nhập Dữ Liệu Từ Excel',
])

@php
    $context = collect($context)
        ->filter(fn ($value) => ! is_null($value) && $value !== '' && $value !== 'all')
        ->map(fn ($value) => (string) $value)
        ->all();
    $modalId = 'bulkExcelPreviewModal_' . preg_replace('/[^A-Za-z0-9_]/', '_', $module);
    $exportModalId = 'bulkExcelExportModal_' . preg_replace('/[^A-Za-z0-9_]/', '_', $module);
@endphp

<div
    class="bulk-excel-actions flex items-center gap-2 flex-wrap sm:flex-nowrap justify-content-end"
    data-bulk-excel-root
    data-module="{{ $module }}"
    data-preview-url="{{ route('bulk-excel.preview', ['module' => $module]) }}"
    data-commit-url="{{ route('bulk-excel.commit', ['module' => $module]) }}"
    data-fields-url="{{ route('bulk-excel.fields', ['module' => $module]) }}"
    data-export-url="{{ route('bulk-excel.export', ['module' => $module]) }}"
    data-modal-id="{{ $modalId }}"
    data-export-modal-id="{{ $exportModalId }}"
>
    @if($showTemplateButton)
        <a
            href="{{ route('bulk-excel.template', array_merge(['module' => $module], $context)) }}"
            class="bulk-excel-btn"
        >
            📥 {{ $templateButtonLabel }}
        </a>
    @endif
    <button type="button" class="bulk-excel-btn" data-bulk-excel-export>
        📥 {{ $exportButtonLabel }}
    </button>
    @if($allowImport)
        <label class="bulk-excel-btn mb-0">
            📤 {{ $importButtonLabel }}
            <input type="file" accept=".xlsx,.csv,.txt" class="d-none" data-bulk-excel-file>
        </label>
    @endif
    @csrf
    @foreach($context as $key => $value)
        <input type="hidden" data-bulk-context name="{{ $key }}" value="{{ $value }}">
    @endforeach
</div>

<div class="bulk-excel-modal-backdrop" id="{{ $exportModalId }}" data-bulk-export-modal aria-hidden="true">
    <div class="bulk-excel-modal bulk-excel-export-modal">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
            <div>
                <h5 class="bulk-excel-title">👁️ XEM TRƯỚC BẢN IN VÀ FILE XUẤT BÁO CÁO</h5>
                <div class="bulk-excel-note">Mô phỏng cấu trúc các trường dữ liệu và định dạng file báo cáo sẽ tải về theo bộ lọc hiện tại.</div>
            </div>
            <button type="button" class="btn-close" data-bulk-export-cancel aria-label="Đóng"></button>
        </div>

        <div class="bulk-excel-alert d-none" data-bulk-export-alert></div>

        <div class="bulk-excel-section-label">Định dạng file</div>
        <div class="bulk-export-format-group" data-bulk-export-format>
            <label class="bulk-export-radio">
                <input type="radio" name="{{ $exportModalId }}_format" value="xlsx" checked>
                <span>Xuất file Excel (.xlsx)</span>
            </label>
            <label class="bulk-export-radio">
                <input type="radio" name="{{ $exportModalId }}_format" value="pdf">
                <span>Xuất file PDF báo cáo (.pdf)</span>
            </label>
        </div>

        <div class="bulk-excel-section-label mt-3">Cột thông tin</div>
        <div class="bulk-export-checkbox-grid" data-bulk-export-fields>
            <span class="bulk-excel-note">Đang tải danh sách trường dữ liệu...</span>
        </div>

        <div class="d-flex align-items-center justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-secondary bulk-excel-cancel" data-bulk-export-cancel>Hủy bỏ</button>
            <button type="button" class="btn bulk-excel-confirm" data-bulk-export-download>🚀 Xác nhận OK - Tải xuống file ngay</button>
        </div>
    </div>
</div>

<div class="bulk-excel-modal-backdrop" id="{{ $modalId }}" data-bulk-excel-modal aria-hidden="true">
    <div class="bulk-excel-modal">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
            <div>
                <h5 class="bulk-excel-title">📋 KIỂM TRA DỮ LIỆU FILE NHẬP HÀNG LOẠT</h5>
                <div class="bulk-excel-note">Vui lòng rà soát lại thông tin trước khi xác nhận nạp chính thức vào Cơ sở dữ liệu.</div>
            </div>
            <button type="button" class="btn-close" data-bulk-excel-cancel aria-label="Đóng"></button>
        </div>
        <div class="bulk-excel-alert d-none" data-bulk-excel-alert></div>
        @if(! $showTemplateButton)
            <div class="d-flex align-items-center justify-content-start mb-3">
                <a
                    href="{{ route('bulk-excel.template', array_merge(['module' => $module], $context)) }}"
                    class="bulk-excel-btn"
                >
                    📥 {{ $templateButtonLabel }}
                </a>
            </div>
        @endif
        <div class="table-responsive bulk-excel-table-wrap">
            <table class="table align-middle bulk-excel-table">
                <thead data-bulk-excel-head></thead>
                <tbody data-bulk-excel-body></tbody>
            </table>
        </div>
        <div class="d-flex align-items-center justify-content-between gap-3 mt-3">
            <span class="bulk-excel-count" data-bulk-excel-count>Chưa có dữ liệu xem trước.</span>
            <div class="d-flex align-items-center justify-content-end gap-2">
                <button type="button" class="btn btn-secondary bulk-excel-cancel" data-bulk-excel-cancel>Hủy bỏ tiến trình</button>
                <button type="button" class="btn bulk-excel-confirm" data-bulk-excel-confirm disabled>🔒 Tiến hành nạp vào hệ thống</button>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .bulk-excel-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        @media (min-width: 640px) {
            .bulk-excel-actions {
                flex-wrap: nowrap;
            }
        }

        .bulk-excel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .5rem .75rem;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            color: #c2410c;
            background: #fff7ed;
            font-size: .875rem;
            font-weight: 400;
            line-height: 1.2;
            text-decoration: none;
            cursor: pointer;
            transition: all .18s ease;
            white-space: nowrap;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bulk-excel-btn:hover {
            color: #9a3412;
            background: #ffedd5;
        }

        .bulk-excel-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1060;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, .45);
        }

        .bulk-excel-modal-backdrop.active {
            display: flex;
        }

        .bulk-excel-modal {
            width: 100%;
            max-width: 1024px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 1.5rem;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .bulk-excel-export-modal {
            max-width: 896px;
        }

        .bulk-excel-title {
            display: flex;
            align-items: center;
            margin: 0;
            color: #111827;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .bulk-excel-title::before {
            width: 4px;
            height: 16px;
            margin-right: .5rem;
            border-radius: 999px;
            background: #f97316;
            content: "";
        }

        .bulk-excel-note,
        .bulk-excel-count {
            color: #6b7280;
            font-size: .88rem;
            font-weight: 400;
        }

        .bulk-excel-section-label {
            margin-bottom: .45rem;
            color: #111827;
            font-size: .9rem;
            font-weight: 500;
        }

        .bulk-export-format-group {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
        }

        .bulk-export-radio,
        .bulk-export-check {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin: 0;
            color: #1f2937;
            font-size: .92rem;
            font-weight: 400;
            cursor: pointer;
        }

        .bulk-export-radio input,
        .bulk-export-check input {
            width: 1rem;
            height: 1rem;
            accent-color: #ea580c;
        }

        .bulk-export-radio {
            padding: .55rem .7rem;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            background: #fff7ed;
        }

        .bulk-export-checkbox-grid {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem .85rem;
            max-height: 280px;
            overflow-y: auto;
            padding: 1rem;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            background: rgba(255, 247, 237, .4);
        }

        .bulk-export-check {
            min-width: 45%;
            padding: .25rem 0;
        }

        .bulk-excel-table th,
        .bulk-excel-table td {
            color: #1f2937;
            font-size: 1rem;
            font-weight: 400;
            text-align: left;
            vertical-align: top;
            white-space: nowrap;
        }

        .bulk-excel-cell-value {
            min-height: 1.5rem;
            white-space: nowrap;
        }

        .bulk-excel-table th {
            color: #111827;
            font-weight: 500;
            background: #fff7ed;
        }

        .bulk-excel-select-cell {
            width: 3rem;
            min-width: 3rem;
            text-align: left;
        }

        .bulk-excel-row-check {
            width: 1rem;
            height: 1rem;
            accent-color: #ea580c;
            cursor: pointer;
        }

        .bulk-excel-cell-error {
            border: 1px solid #ef4444 !important;
            background: #fef2f2 !important;
        }

        .bulk-excel-error-text {
            display: inline-flex;
            align-items: center;
            margin-top: .22rem;
            color: #b91c1c;
            font-size: .72rem;
            font-weight: 400;
            line-height: 1.2;
            white-space: nowrap;
        }

        .bulk-excel-warning-text,
        .bulk-excel-status-note {
            display: block;
            margin-top: .22rem;
            color: #c2410c;
            font-size: .75rem;
            font-weight: 400;
            line-height: 1.25;
            white-space: normal;
            max-width: 18rem;
        }

        .bulk-excel-status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #c2410c;
            padding: .18rem .55rem;
            font-size: .75rem;
            font-weight: 400;
            white-space: nowrap;
        }

        .bulk-excel-status-badge[data-status="update"] {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #15803d;
        }

        .bulk-excel-status-badge[data-status="need_confirmation"],
        .bulk-excel-status-badge[data-status="invalid"] {
            border-color: #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .text-red-650 {
            color: #b91c1c !important;
        }

        .bulk-excel-confirm {
            color: #fff;
            background: #ea580c;
            border-color: #ea580c;
            border-radius: 6px;
            padding: .5rem .9rem;
            font-size: .88rem;
            font-weight: 400;
        }

        .bulk-excel-confirm:disabled {
            color: #9ca3af;
            background: #f3f4f6;
            border-color: #e5e7eb;
            cursor: not-allowed;
        }

        .bulk-excel-alert {
            margin-bottom: .75rem;
            padding: .65rem .75rem;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            color: #c2410c;
            background: #fff7ed;
            font-size: .88rem;
            font-weight: 400;
        }

        .bulk-excel-toast {
            position: fixed;
            top: 1.5rem;
            left: 50%;
            z-index: 9999;
            display: inline-flex;
            align-items: center;
            gap: .625rem;
            max-width: min(34rem, calc(100vw - 2rem));
            padding: 1rem;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            color: #166534;
            background: #f0fdf4 !important;
            background-color: #f0fdf4 !important;
            background-image: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            opacity: 1 !important;
            isolation: isolate;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .18);
            font-size: 1rem;
            font-weight: 400;
            text-align: left;
            transform: translateX(-50%);
        }
    </style>
    <script>
        (() => {
            if (window.__bulkExcelBound) {
                return;
            }
            window.__bulkExcelBound = true;

            const ensureUtf8Meta = () => {
                if (document.querySelector('meta[charset]')) {
                    document.querySelector('meta[charset]').setAttribute('charset', 'utf-8');
                    return;
                }

                const meta = document.createElement('meta');
                meta.setAttribute('charset', 'utf-8');
                document.head.prepend(meta);
            };

            ensureUtf8Meta();

            const token = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value
                || '';

            const toast = (message, type = 'info') => {
                if (window.SchoolToast) {
                    window.SchoolToast(type, message);
                    return;
                }

                const element = document.createElement('div');
                element.className = 'bulk-excel-toast';
                const icon = document.createElement('span');
                icon.textContent = type === 'error' ? '🔴' : '🟢';
                icon.className = 'shrink-0';
                const text = document.createElement('span');
                text.textContent = message;
                text.style.fontWeight = '400';
                text.style.textAlign = 'left';
                element.append(icon, text);
                document.body.appendChild(element);
                setTimeout(() => element.remove(), 2600);
            };

            const clearInput = (root) => {
                const input = root.querySelector('[data-bulk-excel-file]');
                if (input) {
                    input.value = '';
                }
            };

            const contextFormData = (root) => {
                const formData = new FormData();
                root.querySelectorAll('[data-bulk-context]').forEach((input) => {
                    if (input.value) {
                        formData.append(input.name, input.value);
                    }
                });

                return formData;
            };

            const contextSearchParams = (root) => {
                const params = new URLSearchParams(window.location.search);
                root.querySelectorAll('[data-bulk-context]').forEach((input) => {
                    if (input.value) {
                        params.set(input.name, input.value);
                    }
                });

                const set = (key, value) => {
                    if (value !== undefined && value !== null && String(value) !== '' && String(value) !== 'all') {
                        params.set(key, String(value));
                    }
                };

                const adminScore = document.querySelector('[data-admin-score-app]');
                if (adminScore && root.dataset.module === 'scores') {
                    set('q', document.querySelector('[data-admin-score-search]')?.value);
                    set('school_year_id', document.querySelector('[data-admin-score-year]')?.value);
                    set('grade_level', document.querySelector('[data-admin-score-grade]')?.value);
                    set('class_id', document.querySelector('[data-admin-score-class]')?.value);
                    set('subject_id', document.querySelector('[data-admin-score-subject]')?.value);
                    set('teacher_id', document.querySelector('[data-admin-score-teacher]')?.value);
                    set('semester_id', document.querySelector('[data-admin-score-semester]')?.value);
                    return params;
                }

                ['q', 'school_year_id', 'grade_level', 'class_id', 'subject_id', 'teacher_id', 'semester_id', 'date', 'attendance_date', 'attendance_type', 'status', 'gender', 'department_id'].forEach((name) => {
                    const field = document.querySelector(`[name="${name}"]`);
                    if (field) {
                        set(name, field.value);
                    }
                });

                return params;
            };

            const setAlert = (modal, message) => {
                const alert = modal.querySelector('[data-bulk-excel-alert]');
                alert.textContent = message || '';
                alert.classList.toggle('d-none', ! message);
            };

            const setExportAlert = (modal, message) => {
                const alert = modal.querySelector('[data-bulk-export-alert]');
                alert.textContent = message || '';
                alert.classList.toggle('d-none', ! message);
            };

            const renderExportFields = (root, fields, queryString) => {
                const modal = document.getElementById(root.dataset.exportModalId);
                const wrap = modal.querySelector('[data-bulk-export-fields]');
                wrap.innerHTML = '';
                modal.dataset.query = queryString || '';
                setExportAlert(modal, '');

                if (! fields.length) {
                    wrap.innerHTML = '<span class="bulk-excel-note">Không có trường dữ liệu phù hợp để xuất.</span>';
                    return;
                }

                fields.forEach((field) => {
                    const label = document.createElement('label');
                    label.className = 'bulk-export-check';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = String(field.index);
                    checkbox.checked = Boolean(field.selected);

                    const text = document.createElement('span');
                    text.textContent = field.label;

                    label.appendChild(checkbox);
                    label.appendChild(text);
                    wrap.appendChild(label);
                });
            };

            const renderPreview = (root, payload) => {
                const modal = document.getElementById(root.dataset.modalId);
                const head = modal.querySelector('[data-bulk-excel-head]');
                const body = modal.querySelector('[data-bulk-excel-body]');
                const count = modal.querySelector('[data-bulk-excel-count]');
                const confirm = modal.querySelector('[data-bulk-excel-confirm]');

                modal.dataset.token = payload.token || '';
                head.innerHTML = '';
                body.innerHTML = '';
                const warningRows = payload.warning_row_count ?? payload.error_count ?? 0;
                setAlert(modal, warningRows > 0 ? `Phát hiện ${warningRows} dòng dữ liệu cần lưu ý.` : '');

                const headerRow = document.createElement('tr');
                const selectHeader = document.createElement('th');
                selectHeader.className = 'bulk-excel-select-cell';
                const selectAll = document.createElement('input');
                selectAll.type = 'checkbox';
                selectAll.className = 'bulk-excel-row-check';
                selectAll.checked = true;
                selectAll.setAttribute('data-bulk-row-select-all', '');
                selectAll.setAttribute('aria-label', 'Chọn tất cả dòng import');
                selectHeader.appendChild(selectAll);
                headerRow.appendChild(selectHeader);
                const hasStatusColumn = (payload.rows || []).some((row) => row.status_label || row.planned_action || (row.notes || []).length);
                if (hasStatusColumn) {
                    const statusTh = document.createElement('th');
                    statusTh.textContent = 'Trạng thái';
                    headerRow.appendChild(statusTh);
                }
                (payload.headers || []).forEach((header) => {
                    const th = document.createElement('th');
                    th.textContent = header.label;
                    headerRow.appendChild(th);
                });
                head.appendChild(headerRow);

                (payload.rows || []).forEach((row) => {
                    const tr = document.createElement('tr');
                    const rowHasError = Boolean(row.blocking) || (row.cells || []).some((cell) => Boolean(cell.error));
                    tr.dataset.hasError = rowHasError ? '1' : '0';

                    const selectCell = document.createElement('td');
                    selectCell.className = 'bulk-excel-select-cell';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'bulk-excel-row-check';
                    checkbox.checked = true;
                    checkbox.value = String(row.position ?? 0);
                    checkbox.setAttribute('data-bulk-row-select', '');
                    checkbox.setAttribute('aria-label', `Chọn dòng ${row.index || ''}`);
                    if (row.blocking) {
                        checkbox.checked = false;
                        checkbox.disabled = true;
                    }
                    selectCell.appendChild(checkbox);
                    tr.appendChild(selectCell);

                    if (hasStatusColumn) {
                        const statusCell = document.createElement('td');
                        const badge = document.createElement('span');
                        const status = String(row.status || '');
                        badge.className = 'bulk-excel-status-badge';
                        badge.dataset.status = status;
                        badge.textContent = row.status_label || 'Mới';
                        statusCell.appendChild(badge);

                        if (row.planned_action) {
                            const action = document.createElement('div');
                            action.className = 'bulk-excel-status-note';
                            action.textContent = row.planned_action;
                            statusCell.appendChild(action);
                        }

                        (row.notes || []).forEach((noteText) => {
                            const note = document.createElement('div');
                            note.className = 'bulk-excel-status-note';
                            note.textContent = noteText;
                            statusCell.appendChild(note);
                        });

                        if (row.requires_decision) {
                            const decisionWrap = document.createElement('div');
                            decisionWrap.className = 'bulk-excel-status-note d-flex flex-column gap-2 mt-2';

                            const actionSelect = document.createElement('select');
                            actionSelect.className = 'form-select form-select-sm font-normal';
                            actionSelect.setAttribute('data-bulk-row-action', '');
                            actionSelect.setAttribute('aria-label', 'Chọn cách xử lý dòng cần xác nhận');
                            actionSelect.innerHTML = `
                                <option value="">Chọn cách xử lý</option>
                                <option value="update_existing">Cập nhật học sinh hiện có</option>
                                <option value="create_new">Tạo học sinh mới</option>
                                <option value="skip">Bỏ qua</option>
                            `;

                            const candidateSelect = document.createElement('select');
                            candidateSelect.className = 'form-select form-select-sm font-normal d-none';
                            candidateSelect.setAttribute('data-bulk-row-student-id', '');
                            candidateSelect.setAttribute('aria-label', 'Chọn học sinh hiện có cần cập nhật');
                            candidateSelect.disabled = true;
                            candidateSelect.innerHTML = '<option value="">Chọn học sinh hiện có</option>';
                            (row.candidates || []).forEach((candidate) => {
                                const option = document.createElement('option');
                                option.value = String(candidate.id || '');
                                option.textContent = [
                                    candidate.code || 'Chưa có mã',
                                    candidate.name || 'Chưa có tên',
                                    candidate.dob || 'Chưa có ngày sinh',
                                    candidate.class || 'Chưa có lớp'
                                ].join(' - ');
                                candidateSelect.appendChild(option);
                            });

                            decisionWrap.appendChild(actionSelect);
                            decisionWrap.appendChild(candidateSelect);
                            statusCell.appendChild(decisionWrap);
                        }

                        tr.appendChild(statusCell);
                    }

                    (row.cells || []).forEach((cell) => {
                        const td = document.createElement('td');
                        if (cell.error) {
                            td.className = 'bulk-excel-cell-error';
                        }
                        const value = document.createElement('div');
                        value.className = 'bulk-excel-cell-value';
                        value.textContent = cell.value || '—';
                        td.appendChild(value);
                        if (cell.error) {
                            const error = document.createElement('span');
                            error.className = 'bulk-excel-error-text text-red-650 bg-red-50 text-xs font-normal px-2 py-0.5 rounded';
                            error.textContent = cell.error;
                            td.appendChild(error);
                        }
                        if (cell.warning) {
                            const warning = document.createElement('span');
                            warning.className = 'bulk-excel-warning-text text-orange-700 bg-orange-50 text-xs font-normal px-2 py-0.5 rounded';
                            warning.textContent = cell.warning;
                            td.appendChild(warning);
                        }
                        tr.appendChild(td);
                    });
                    body.appendChild(tr);
                });

                count.textContent = `Hiển thị ${payload.rows?.length || 0} dòng đọc từ file.`;
                syncPreviewSelectionState(modal);
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
            };

            const syncPreviewSelectionState = (modal) => {
                const confirm = modal.querySelector('[data-bulk-excel-confirm]');
                const selectAll = modal.querySelector('[data-bulk-row-select-all]');
                const checkboxes = [...modal.querySelectorAll('[data-bulk-row-select]')];
                const selectable = checkboxes.filter((checkbox) => ! checkbox.disabled);
                const selected = checkboxes.filter((checkbox) => checkbox.checked);
                const selectedHasError = selected.some((checkbox) => checkbox.closest('tr')?.dataset.hasError === '1');
                const selectedNeedsDecision = selected.some((checkbox) => {
                    const row = checkbox.closest('tr');
                    const actionSelect = row?.querySelector('[data-bulk-row-action]');
                    if (! actionSelect) {
                        return false;
                    }
                    if (actionSelect.value === 'skip' || actionSelect.value === 'create_new') {
                        return false;
                    }
                    if (actionSelect.value === 'update_existing') {
                        return ! row.querySelector('[data-bulk-row-student-id]')?.value;
                    }

                    return true;
                });

                if (selectAll) {
                    selectAll.checked = selectable.length > 0 && selectable.every((checkbox) => checkbox.checked);
                    selectAll.indeterminate = selectable.some((checkbox) => checkbox.checked) && ! selectAll.checked;
                }

                if (confirm) {
                    confirm.disabled = selected.length === 0 || selectedHasError || selectedNeedsDecision;
                }
            };

            document.addEventListener('change', async (event) => {
                const input = event.target.closest('[data-bulk-excel-file]');
                if (! input || ! input.files?.length) {
                    return;
                }

                const root = input.closest('[data-bulk-excel-root]');
                const formData = contextFormData(root);
                formData.append('file', input.files[0]);

                try {
                    const response = await fetch(root.dataset.previewUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (! response.ok) {
                        throw new Error(payload.message || 'Không thể đọc file Excel.');
                    }
                    renderPreview(root, payload);
                } catch (error) {
                    toast(error.message, 'error');
                    clearInput(root);
                }
            });

            document.addEventListener('click', async (event) => {
                const exportCancel = event.target.closest('[data-bulk-export-cancel]');
                if (exportCancel) {
                    const modal = exportCancel.closest('[data-bulk-export-modal]');
                    modal.classList.remove('active');
                    modal.setAttribute('aria-hidden', 'true');
                    return;
                }

                const cancel = event.target.closest('[data-bulk-excel-cancel]');
                if (cancel) {
                    const modal = cancel.closest('[data-bulk-excel-modal]');
                    modal.classList.remove('active');
                    modal.setAttribute('aria-hidden', 'true');
                    document.querySelectorAll(`[data-modal-id="${modal.id}"]`).forEach(clearInput);
                    return;
                }

                const selectAll = event.target.closest('[data-bulk-row-select-all]');
                if (selectAll) {
                    const modal = selectAll.closest('[data-bulk-excel-modal]');
                    modal.querySelectorAll('[data-bulk-row-select]').forEach((checkbox) => {
                        if (checkbox.disabled) {
                            return;
                        }
                        checkbox.checked = selectAll.checked;
                    });
                    syncPreviewSelectionState(modal);
                    return;
                }

                const exportButton = event.target.closest('[data-bulk-excel-export]');
                if (exportButton) {
                    const root = exportButton.closest('[data-bulk-excel-root]');
                    const modal = document.getElementById(root.dataset.exportModalId);
                    const fieldsUrl = new URL(root.dataset.fieldsUrl, window.location.origin);
                    const params = contextSearchParams(root);
                    params.forEach((value, key) => fieldsUrl.searchParams.set(key, value));
                    modal.classList.add('active');
                    modal.setAttribute('aria-hidden', 'false');
                    modal.querySelector('[data-bulk-export-fields]').innerHTML = '<span class="bulk-excel-note">Đang tải danh sách trường dữ liệu...</span>';

                    try {
                        const response = await fetch(fieldsUrl.toString(), {
                            headers: {'Accept': 'application/json'},
                        });
                        const payload = await response.json().catch(() => ({}));
                        if (! response.ok) {
                            throw new Error(payload.message || 'Không thể tải danh sách trường xuất.');
                        }
                        renderExportFields(root, payload.fields || [], params.toString());
                    } catch (error) {
                        setExportAlert(modal, error.message);
                    }
                    return;
                }

                const download = event.target.closest('[data-bulk-export-download]');
                if (download) {
                    const modal = download.closest('[data-bulk-export-modal]');
                    const root = document.querySelector(`[data-bulk-excel-root][data-export-modal-id="${modal.id}"]`);
                    const params = new URLSearchParams(modal.dataset.query || '');
                    const format = modal.querySelector('[data-bulk-export-format] input:checked')?.value || 'xlsx';
                    const checked = [...modal.querySelectorAll('[data-bulk-export-fields] input[type="checkbox"]:checked')];
                    if (! checked.length) {
                        setExportAlert(modal, 'Vui lòng chọn ít nhất một cột thông tin để xuất file.');
                        return;
                    }

                    params.set('format', format);
                    params.delete('fields[]');
                    params.delete('fields');
                    checked.forEach((checkbox) => params.append('fields[]', checkbox.value));

                    const url = new URL(root.dataset.exportUrl, window.location.origin);
                    params.forEach((value, key) => url.searchParams.append(key, value));
                    window.location.href = url.toString();
                    modal.classList.remove('active');
                    modal.setAttribute('aria-hidden', 'true');
                    return;
                }

                const confirm = event.target.closest('[data-bulk-excel-confirm]');
                if (! confirm) {
                    return;
                }

                const modal = confirm.closest('[data-bulk-excel-modal]');
                const root = document.querySelector(`[data-bulk-excel-root][data-modal-id="${modal.id}"]`);
                const formData = contextFormData(root);
                formData.append('token', modal.dataset.token || '');
                modal.querySelectorAll('[data-bulk-row-select]:checked').forEach((checkbox) => {
                    formData.append('selected_rows[]', checkbox.value);
                    const row = checkbox.closest('tr');
                    const action = row?.querySelector('[data-bulk-row-action]')?.value;
                    const studentId = row?.querySelector('[data-bulk-row-student-id]')?.value;
                    if (action) {
                        formData.append(`row_actions[${checkbox.value}]`, action);
                    }
                    if (studentId) {
                        formData.append(`row_student_ids[${checkbox.value}]`, studentId);
                    }
                });
                confirm.disabled = true;

                try {
                    const response = await fetch(root.dataset.commitUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (! response.ok) {
                        throw new Error(payload.message || 'Không thể nạp dữ liệu.');
                    }
                    toast(payload.message || 'Đã nạp dữ liệu Excel.', 'success');
                    modal.classList.remove('active');
                    setTimeout(() => {
                        window.location.href = payload.redirect || window.location.href;
                    }, 700);
                } catch (error) {
                    setAlert(modal, error.message);
                    confirm.disabled = false;
                }
            });

            document.addEventListener('change', (event) => {
                const actionSelect = event.target.closest('[data-bulk-row-action]');
                if (actionSelect) {
                    const row = actionSelect.closest('tr');
                    const candidateSelect = row?.querySelector('[data-bulk-row-student-id]');
                    if (candidateSelect) {
                        const needsCandidate = actionSelect.value === 'update_existing';
                        candidateSelect.classList.toggle('d-none', ! needsCandidate);
                        candidateSelect.disabled = ! needsCandidate;
                        if (! needsCandidate) {
                            candidateSelect.value = '';
                        }
                    }
                    syncPreviewSelectionState(actionSelect.closest('[data-bulk-excel-modal]'));
                    return;
                }

                const candidateSelect = event.target.closest('[data-bulk-row-student-id]');
                if (candidateSelect) {
                    syncPreviewSelectionState(candidateSelect.closest('[data-bulk-excel-modal]'));
                    return;
                }

                const rowCheckbox = event.target.closest('[data-bulk-row-select]');
                if (! rowCheckbox) {
                    return;
                }

                syncPreviewSelectionState(rowCheckbox.closest('[data-bulk-excel-modal]'));
            });
        })();
    </script>
@endonce
