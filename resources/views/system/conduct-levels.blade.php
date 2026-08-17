@extends('layouts.app')
@section('title', 'Định mức hạnh kiểm')

@section('content')
<style>
    .evaluation-index-table {
        width: 100%;
        margin: 0;
        table-layout: fixed;
    }

    .evaluation-index-table th,
    .evaluation-index-table td {
        display: table-cell !important;
        color: #1f2937;
        font-size: 1rem;
        font-weight: 400;
        text-align: left !important;
        vertical-align: middle;
        white-space: normal;
    }

    .evaluation-index-table th {
        color: #111827;
        font-weight: 500;
        background: #fff7ed;
    }

    .evaluation-name-main {
        color: #111827;
        font-size: 1rem;
        font-weight: 600;
    }

    .evaluation-muted-value {
        color: #374151;
        font-size: .95rem;
        font-weight: 400;
    }

    .evaluation-action-group {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: .4rem;
    }

    .evaluation-modal-input {
        width: 100%;
        color: #374151;
        font-size: .9rem;
        font-weight: 400;
        text-align: left !important;
        background: #f9fafb;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        padding: .55rem .75rem;
    }

    .evaluation-modal-input:focus {
        border-color: #f97316;
        outline: none;
        box-shadow: 0 0 0 .2rem rgba(255, 237, 213, .78);
    }

    .conduct-level-modal {
        display: none !important;
        position: fixed !important;
        inset: 0 !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1rem !important;
        background: rgba(0, 0, 0, .4) !important;
        z-index: 1055 !important;
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .conduct-level-modal.is-open {
        display: flex !important;
    }

    .conduct-level-modal-card {
        width: 100%;
        max-width: 28rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1.5rem;
        color: #374151;
        text-align: left;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .28);
    }
</style>

<x-page-header
    title="🏆 Định mức hạnh kiểm"
    subtitle="Quản lý các ngưỡng chuyên cần không phép, vắng tiết lẻ để làm căn cứ gợi ý hạ bậc rèn luyện."
>
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="add-conduct-level" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Thêm mốc mới
        </button>
    </div>
</x-page-header>

@if(! ($settingsTableReady ?? false))
    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm font-normal rounded-xl px-4 py-3 mb-6 text-left">
        Chưa có bảng settings. Vui lòng chạy migration trước khi lưu cấu hình.
    </div>
@endif

<form method="POST" action="{{ route('system.conduct-levels.update') }}" class="w-full font-sans text-left text-gray-700 font-normal" data-conduct-level-form>
    @csrf
    @method('PUT')

    <div class="card w-full bg-white border border-orange-100 p-0 rounded-xl shadow-2xs text-left">
        <div class="table-responsive">
            <table class="table evaluation-index-table">
                <thead>
                    <tr>
                        <th style="width: 7%;">STT</th>
                        <th style="width: 25%;">Tên bậc Hạnh kiểm</th>
                        <th style="width: 17%;">Nghỉ không phép ≤</th>
                        <th style="width: 18%;">Trốn tiết bộ môn ≤</th>
                        <th style="width: 17%;">Đi muộn ≤</th>
                        <th style="width: 16%;"></th>
                    </tr>
                </thead>
                <tbody id="conduct-level-rows">
                    @foreach($conductLevels as $levelKey => $level)
                        <tr class="conduct-level-row"
                            data-level-key="{{ $levelKey }}"
                            data-label="{{ old('conduct_levels.' . $levelKey . '.label', $level['label']) }}"
                            data-unexcused="{{ old('conduct_levels.' . $levelKey . '.max_unexcused_absence', $level['max_unexcused_absence']) }}"
                            data-period="{{ old('conduct_levels.' . $levelKey . '.max_period_absence', $level['max_period_absence']) }}"
                            data-late="{{ old('conduct_levels.' . $levelKey . '.max_late', $level['max_late']) }}">
                            <td class="conduct-row-index">{{ $loop->iteration }}</td>
                            <td>
                                <span class="evaluation-name-main" data-display-label>{{ old('conduct_levels.' . $levelKey . '.label', $level['label']) }}</span>
                                <input type="hidden" name="conduct_levels[{{ $levelKey }}][label]" value="{{ old('conduct_levels.' . $levelKey . '.label', $level['label']) }}" data-hidden-label>
                            </td>
                            <td>
                                <span class="evaluation-muted-value" data-display-unexcused>{{ old('conduct_levels.' . $levelKey . '.max_unexcused_absence', $level['max_unexcused_absence']) }}</span>
                                <input type="hidden" name="conduct_levels[{{ $levelKey }}][max_unexcused_absence]" value="{{ old('conduct_levels.' . $levelKey . '.max_unexcused_absence', $level['max_unexcused_absence']) }}" data-hidden-unexcused>
                            </td>
                            <td>
                                <span class="evaluation-muted-value" data-display-period>{{ old('conduct_levels.' . $levelKey . '.max_period_absence', $level['max_period_absence']) }}</span>
                                <input type="hidden" name="conduct_levels[{{ $levelKey }}][max_period_absence]" value="{{ old('conduct_levels.' . $levelKey . '.max_period_absence', $level['max_period_absence']) }}" data-hidden-period>
                            </td>
                            <td>
                                <span class="evaluation-muted-value" data-display-late>{{ old('conduct_levels.' . $levelKey . '.max_late', $level['max_late']) }}</span>
                                <input type="hidden" name="conduct_levels[{{ $levelKey }}][max_late]" value="{{ old('conduct_levels.' . $levelKey . '.max_late', $level['max_late']) }}" data-hidden-late>
                            </td>
                            <td>
                                <div class="evaluation-action-group">
                                    <button type="button" class="content-action-btn edit" title="Sửa" aria-label="Sửa" data-edit-conduct-row>
                                        <i class="bi bi-pencil-square"></i><span>Sửa</span>
                                    </button>
                                    <button type="button" class="content-action-btn delete" title="Xóa" aria-label="Xóa" data-delete-conduct-row>
                                        <i class="bi bi-trash"></i><span>Xóa</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($errors->has('conduct_levels') || collect($errors->getMessages())->keys()->contains(fn ($key) => str_starts_with($key, 'conduct_levels.')))
        <div class="text-danger small text-left mt-3">Vui lòng kiểm tra lại định mức hạnh kiểm.</div>
    @endif
</form>

<div id="conduct-level-modal" class="conduct-level-modal fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 font-sans animate-fade-in" aria-hidden="true">
    <div class="conduct-level-modal-card w-full max-w-md bg-white p-6 rounded-xl shadow-2xl flex flex-col gap-4 text-left border border-orange-100">
        <div class="border-b border-orange-100 pb-3 text-left">
            <h2 class="text-lg font-semibold text-gray-900 !text-left mb-1" data-modal-title>Định mức hạnh kiểm</h2>
            <p class="text-xs font-normal text-orange-700/60 !text-left mb-0">Cập nhật một dòng cấu hình rồi lưu về hệ thống.</p>
        </div>
        <div class="flex flex-col gap-3 text-left">
            <label class="text-sm font-normal text-gray-700 text-left">
                Tên bậc Hạnh kiểm
                <input type="text" class="evaluation-modal-input mt-1" data-modal-label>
            </label>
            <label class="text-sm font-normal text-gray-700 text-left">
                Nghỉ không phép ≤
                <input type="number" min="0" max="365" class="evaluation-modal-input mt-1" data-modal-unexcused>
            </label>
            <label class="text-sm font-normal text-gray-700 text-left">
                Trốn tiết bộ môn ≤
                <input type="number" min="0" max="500" class="evaluation-modal-input mt-1" data-modal-period>
            </label>
            <label class="text-sm font-normal text-gray-700 text-left">
                Đi muộn ≤
                <input type="number" min="0" max="500" class="evaluation-modal-input mt-1" data-modal-late>
            </label>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2 text-left">
            <button type="button" class="btn btn-secondary text-sm font-normal" data-close-conduct-modal>Đóng</button>
            <button type="button" class="btn btn-primary text-sm font-normal" data-save-conduct-modal>
                <i class="bi bi-save me-1"></i>Lưu thay đổi
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-conduct-level-form]');
        const rows = document.getElementById('conduct-level-rows');
        const addButton = document.getElementById('add-conduct-level');
        const modal = document.getElementById('conduct-level-modal');
        const modalTitle = modal.querySelector('[data-modal-title]');
        const labelInput = modal.querySelector('[data-modal-label]');
        const unexcusedInput = modal.querySelector('[data-modal-unexcused]');
        const periodInput = modal.querySelector('[data-modal-period]');
        const lateInput = modal.querySelector('[data-modal-late]');
        let activeRow = null;
        let modalMode = 'edit';

        const submitForm = () => {
            if (form?.requestSubmit) {
                form.requestSubmit();
                return;
            }

            form?.submit();
        };

        const refreshIndexes = () => {
            rows.querySelectorAll('.conduct-level-row').forEach((row, index) => {
                row.querySelector('.conduct-row-index').textContent = index + 1;
            });
        };

        const openModal = (row = null) => {
            activeRow = row;
            modalMode = row ? 'edit' : 'create';
            modalTitle.textContent = row ? 'Sửa định mức hạnh kiểm' : 'Thêm mốc hạnh kiểm mới';
            labelInput.value = row?.dataset.label || '';
            unexcusedInput.value = row?.dataset.unexcused || '';
            periodInput.value = row?.dataset.period || '';
            lateInput.value = row?.dataset.late || '';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            labelInput.focus();
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            activeRow = null;
        };

        const bindRow = (row) => {
            row.querySelector('[data-edit-conduct-row]')?.addEventListener('click', () => openModal(row));
            row.querySelector('[data-delete-conduct-row]')?.addEventListener('click', () => {
                row.remove();
                refreshIndexes();
                if (rows.querySelectorAll('.conduct-level-row').length > 0) {
                    submitForm();
                }
            });
        };

        const createRow = () => {
            const key = `conduct_level_${Date.now()}`;
            const row = document.createElement('tr');
            row.className = 'conduct-level-row';
            row.dataset.levelKey = key;
            row.dataset.label = labelInput.value.trim();
            row.dataset.unexcused = unexcusedInput.value;
            row.dataset.period = periodInput.value;
            row.dataset.late = lateInput.value;
            row.innerHTML = `
                <td class="conduct-row-index"></td>
                <td><span class="evaluation-name-main" data-display-label></span><input type="hidden" name="conduct_levels[${key}][label]" data-hidden-label></td>
                <td><span class="evaluation-muted-value" data-display-unexcused></span><input type="hidden" name="conduct_levels[${key}][max_unexcused_absence]" data-hidden-unexcused></td>
                <td><span class="evaluation-muted-value" data-display-period></span><input type="hidden" name="conduct_levels[${key}][max_period_absence]" data-hidden-period></td>
                <td><span class="evaluation-muted-value" data-display-late></span><input type="hidden" name="conduct_levels[${key}][max_late]" data-hidden-late></td>
                <td>
                    <div class="evaluation-action-group">
                        <button type="button" class="content-action-btn edit" title="Sửa" aria-label="Sửa" data-edit-conduct-row><i class="bi bi-pencil-square"></i><span>Sửa</span></button>
                        <button type="button" class="content-action-btn delete" title="Xóa" aria-label="Xóa" data-delete-conduct-row><i class="bi bi-trash"></i><span>Xóa</span></button>
                    </div>
                </td>
            `;
            rows.appendChild(row);
            bindRow(row);
            return row;
        };

        const syncRow = (row) => {
            row.dataset.label = labelInput.value.trim();
            row.dataset.unexcused = unexcusedInput.value;
            row.dataset.period = periodInput.value;
            row.dataset.late = lateInput.value;
            row.querySelector('[data-display-label]').textContent = row.dataset.label;
            row.querySelector('[data-display-unexcused]').textContent = row.dataset.unexcused;
            row.querySelector('[data-display-period]').textContent = row.dataset.period;
            row.querySelector('[data-display-late]').textContent = row.dataset.late;
            row.querySelector('[data-hidden-label]').value = row.dataset.label;
            row.querySelector('[data-hidden-unexcused]').value = row.dataset.unexcused;
            row.querySelector('[data-hidden-period]').value = row.dataset.period;
            row.querySelector('[data-hidden-late]').value = row.dataset.late;
        };

        rows.querySelectorAll('.conduct-level-row').forEach(bindRow);
        addButton?.addEventListener('click', () => openModal());
        modal.querySelector('[data-close-conduct-modal]')?.addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
        modal.querySelector('[data-save-conduct-modal]')?.addEventListener('click', () => {
            const row = modalMode === 'create' ? createRow() : activeRow;
            if (! row) {
                return;
            }

            syncRow(row);
            refreshIndexes();
            closeModal();
            submitForm();
        });
    });
</script>
@endpush
@endsection
