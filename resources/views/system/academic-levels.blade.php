@extends('layouts.app')
@section('title', 'Mốc điểm học lực')

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

    .academic-level-modal {
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

    .academic-level-modal.is-open {
        display: flex !important;
    }

    .academic-level-modal-card {
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
    title="⚖️ Mốc điểm học lực"
    subtitle="Quản lý tên hiển thị, mốc điểm trung bình GPA và điều kiện điểm khống khống của từng cấp độ."
>
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="add-academic-level" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Thêm mức loại mới
        </button>
    </div>
</x-page-header>

@if(! ($settingsTableReady ?? false))
    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm font-normal rounded-xl px-4 py-3 mb-6 text-left">
        Chưa có bảng settings. Vui lòng chạy migration trước khi lưu cấu hình.
    </div>
@endif

<form method="POST" action="{{ route('system.academic-levels.update') }}" class="w-full font-sans text-left text-gray-700 font-normal" data-academic-level-form>
    @csrf
    @method('PUT')

    <div class="card w-full bg-white border border-orange-100 p-0 rounded-xl shadow-2xs text-left">
        <div class="table-responsive">
            <table class="table evaluation-index-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">STT</th>
                        <th style="width: 34%;">Tên mức xếp loại</th>
                        <th style="width: 22%;">Điểm trung bình (GPA) ≥</th>
                        <th style="width: 22%;">Điểm khống từng môn ≥</th>
                        <th style="width: 14%;"></th>
                    </tr>
                </thead>
                <tbody id="academic-level-rows">
                    @foreach($academicLevels as $levelKey => $level)
                        <tr class="academic-level-row"
                            data-level-key="{{ $levelKey }}"
                            data-label="{{ old('academic_levels.' . $levelKey . '.label', $level['label']) }}"
                            data-gpa="{{ old('academic_levels.' . $levelKey . '.gpa_min', $level['gpa_min']) }}"
                            data-subject="{{ old('academic_levels.' . $levelKey . '.subject_min', $level['subject_min']) }}">
                            <td class="academic-row-index">{{ $loop->iteration }}</td>
                            <td>
                                <span class="evaluation-name-main" data-display-label>{{ old('academic_levels.' . $levelKey . '.label', $level['label']) }}</span>
                                <input type="hidden" name="academic_levels[{{ $levelKey }}][label]" value="{{ old('academic_levels.' . $levelKey . '.label', $level['label']) }}" data-hidden-label>
                            </td>
                            <td>
                                <span class="evaluation-muted-value" data-display-gpa>{{ old('academic_levels.' . $levelKey . '.gpa_min', $level['gpa_min']) }}</span>
                                <input type="hidden" name="academic_levels[{{ $levelKey }}][gpa_min]" value="{{ old('academic_levels.' . $levelKey . '.gpa_min', $level['gpa_min']) }}" data-hidden-gpa>
                            </td>
                            <td>
                                <span class="evaluation-muted-value" data-display-subject>{{ old('academic_levels.' . $levelKey . '.subject_min', $level['subject_min']) }}</span>
                                <input type="hidden" name="academic_levels[{{ $levelKey }}][subject_min]" value="{{ old('academic_levels.' . $levelKey . '.subject_min', $level['subject_min']) }}" data-hidden-subject>
                            </td>
                            <td>
                                <div class="evaluation-action-group">
                                    <button type="button" class="content-action-btn edit" title="Sửa" aria-label="Sửa" data-edit-academic-row>
                                        <i class="bi bi-pencil-square"></i><span>Sửa</span>
                                    </button>
                                    <button type="button" class="content-action-btn delete" title="Xóa" aria-label="Xóa" data-delete-academic-row>
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

    @if($errors->has('academic_levels') || collect($errors->getMessages())->keys()->contains(fn ($key) => str_starts_with($key, 'academic_levels.')))
        <div class="text-danger small text-left mt-3">Vui lòng kiểm tra lại các mốc học lực.</div>
    @endif
</form>

<div id="academic-level-modal" class="academic-level-modal fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 font-sans animate-fade-in" aria-hidden="true">
    <div class="academic-level-modal-card w-full max-w-md bg-white p-6 rounded-xl shadow-2xl flex flex-col gap-4 text-left border border-orange-100">
        <div class="border-b border-orange-100 pb-3 text-left">
            <h2 class="text-lg font-semibold text-gray-900 !text-left mb-1" data-modal-title>Mốc điểm học lực</h2>
            <p class="text-xs font-normal text-orange-700/60 !text-left mb-0">Cập nhật một dòng cấu hình rồi lưu về hệ thống.</p>
        </div>
        <div class="flex flex-col gap-3 text-left">
            <label class="text-sm font-normal text-gray-700 text-left">
                Tên mức xếp loại
                <input type="text" class="evaluation-modal-input mt-1" data-modal-label>
            </label>
            <label class="text-sm font-normal text-gray-700 text-left">
                Điểm trung bình (GPA) ≥
                <input type="number" step="0.1" min="0" max="10" class="evaluation-modal-input mt-1" data-modal-gpa>
            </label>
            <label class="text-sm font-normal text-gray-700 text-left">
                Điểm khống từng môn ≥
                <input type="number" step="0.1" min="0" max="10" class="evaluation-modal-input mt-1" data-modal-subject>
            </label>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2 text-left">
            <button type="button" class="btn btn-secondary text-sm font-normal" data-close-academic-modal>Đóng</button>
            <button type="button" class="btn btn-primary text-sm font-normal" data-save-academic-modal>
                <i class="bi bi-save me-1"></i>Lưu thay đổi
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-academic-level-form]');
        const rows = document.getElementById('academic-level-rows');
        const addButton = document.getElementById('add-academic-level');
        const modal = document.getElementById('academic-level-modal');
        const modalTitle = modal.querySelector('[data-modal-title]');
        const labelInput = modal.querySelector('[data-modal-label]');
        const gpaInput = modal.querySelector('[data-modal-gpa]');
        const subjectInput = modal.querySelector('[data-modal-subject]');
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
            rows.querySelectorAll('.academic-level-row').forEach((row, index) => {
                row.querySelector('.academic-row-index').textContent = index + 1;
            });
        };

        const openModal = (row = null) => {
            activeRow = row;
            modalMode = row ? 'edit' : 'create';
            modalTitle.textContent = row ? 'Sửa mốc điểm học lực' : 'Thêm mức loại mới';
            labelInput.value = row?.dataset.label || '';
            gpaInput.value = row?.dataset.gpa || '';
            subjectInput.value = row?.dataset.subject || '';
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
            row.querySelector('[data-edit-academic-row]')?.addEventListener('click', () => openModal(row));
            row.querySelector('[data-delete-academic-row]')?.addEventListener('click', () => {
                row.remove();
                refreshIndexes();
                if (rows.querySelectorAll('.academic-level-row').length > 0) {
                    submitForm();
                }
            });
        };

        const createRow = () => {
            const key = `level_${Date.now()}`;
            const row = document.createElement('tr');
            row.className = 'academic-level-row';
            row.dataset.levelKey = key;
            row.dataset.label = labelInput.value.trim();
            row.dataset.gpa = gpaInput.value;
            row.dataset.subject = subjectInput.value;
            row.innerHTML = `
                <td class="academic-row-index"></td>
                <td><span class="evaluation-name-main" data-display-label></span><input type="hidden" name="academic_levels[${key}][label]" data-hidden-label></td>
                <td><span class="evaluation-muted-value" data-display-gpa></span><input type="hidden" name="academic_levels[${key}][gpa_min]" data-hidden-gpa></td>
                <td><span class="evaluation-muted-value" data-display-subject></span><input type="hidden" name="academic_levels[${key}][subject_min]" data-hidden-subject></td>
                <td>
                    <div class="evaluation-action-group">
                        <button type="button" class="content-action-btn edit" title="Sửa" aria-label="Sửa" data-edit-academic-row><i class="bi bi-pencil-square"></i><span>Sửa</span></button>
                        <button type="button" class="content-action-btn delete" title="Xóa" aria-label="Xóa" data-delete-academic-row><i class="bi bi-trash"></i><span>Xóa</span></button>
                    </div>
                </td>
            `;
            rows.appendChild(row);
            bindRow(row);
            return row;
        };

        const syncRow = (row) => {
            row.dataset.label = labelInput.value.trim();
            row.dataset.gpa = gpaInput.value;
            row.dataset.subject = subjectInput.value;
            row.querySelector('[data-display-label]').textContent = row.dataset.label;
            row.querySelector('[data-display-gpa]').textContent = row.dataset.gpa;
            row.querySelector('[data-display-subject]').textContent = row.dataset.subject;
            row.querySelector('[data-hidden-label]').value = row.dataset.label;
            row.querySelector('[data-hidden-gpa]').value = row.dataset.gpa;
            row.querySelector('[data-hidden-subject]').value = row.dataset.subject;
        };

        rows.querySelectorAll('.academic-level-row').forEach(bindRow);
        addButton?.addEventListener('click', () => openModal());
        modal.querySelector('[data-close-academic-modal]')?.addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
        modal.querySelector('[data-save-academic-modal]')?.addEventListener('click', () => {
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
