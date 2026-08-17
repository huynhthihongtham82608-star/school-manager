@extends('layouts.app')
@section('title', 'Lịch dạy thay')

@section('content')
@php
    $dayLabels = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7'];
@endphp

<style>
    .substitute-page,
    .substitute-page * {
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        text-align: left;
    }

    .substitute-card {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
    }

    .substitute-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        width: 100%;
        margin-bottom: 1rem;
        padding: .875rem;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
    }

    .substitute-filter-group {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem;
    }

    .substitute-filter,
    .substitute-input {
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: .45rem .7rem;
        outline: none;
    }

    .substitute-filter {
        min-width: 150px;
    }

    .substitute-filter:focus,
    .substitute-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .18rem rgba(255, 237, 213, .82);
    }

    .substitute-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin: 0;
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
    }

    .substitute-table th {
        padding: .85rem .75rem;
        color: #111827;
        font-size: .875rem;
        font-weight: 500;
        background: rgba(255, 247, 237, .7);
        border-bottom: 1px solid rgba(254, 215, 170, .7);
    }

    .substitute-table td {
        padding: .8rem .75rem;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
        font-size: .875rem;
        font-weight: 400;
    }

    .substitute-table tbody tr:nth-child(even) {
        background: rgba(255, 247, 237, .18);
    }

    .substitute-table tbody tr:hover {
        background: rgba(255, 247, 237, .45);
    }

    .substitute-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .28rem .65rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 400;
        white-space: nowrap;
    }

    .substitute-status-pill.approved {
        color: #15803d;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .substitute-status-pill.pending {
        color: #a16207;
        background: #fefce8;
        border: 1px solid #fde68a;
    }

    .substitute-action-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .42rem .72rem;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 400;
        cursor: pointer;
        transition: all .15s ease;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .substitute-action-btn.edit {
        color: #c2410c;
        background: #fff7ed;
        border-color: #fed7aa;
    }

    .substitute-action-btn.edit:hover {
        background: #ffedd5;
        color: #9a3412;
    }

    .substitute-action-btn.delete {
        color: #b91c1c;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .substitute-action-btn.delete:hover {
        background: #fee2e2;
    }

    .substitute-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .8rem 1rem;
        color: #6b7280;
        font-size: .82rem;
        font-weight: 400;
    }

    #substitute-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 1050 !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1rem !important;
        background: rgba(0, 0, 0, .4) !important;
    }

    #substitute-modal.d-none {
        display: none !important;
    }

    #substitute-modal:not(.d-none) {
        display: flex !important;
    }

    .substitute-modal-card {
        width: 100%;
        max-width: 30rem;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .22);
    }

    .substitute-error-box {
        color: #b91c1c;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: .75rem;
        font-size: .82rem;
        font-weight: 400;
    }

    .substitute-suggestions-box {
        margin-top: .5rem;
        padding: .75rem;
        background: rgba(255, 247, 237, .6);
        border: 1px solid #ffedd5;
        border-radius: 10px;
        text-align: left;
        width: 100%;
    }

    .substitute-suggestion-btn {
        color: #9a3412;
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        padding: .25rem .55rem;
        font-size: .75rem;
        font-weight: 400;
        cursor: pointer;
        transition: all .15s ease;
        display: inline-block;
        margin: .2rem .35rem .2rem 0;
    }

    .substitute-suggestion-btn:hover {
        background: #ffedd5;
        color: #7c2d12;
    }
</style>

<div class="substitute-page w-full text-left font-sans">
    <x-page-header
        title="Lịch dạy thay"
        subtitle="Quản lý giáo viên dạy thay và đổi tiết theo thời khóa biểu đã phân công."
    >
        @if(! $readOnly)
            <button type="button" class="btn btn-primary" data-substitute-open-create>
                <i class="bi bi-plus-lg me-1"></i>Thêm lịch dạy thay
            </button>
        @endif
    </x-page-header>

    <form method="GET" action="{{ route('substitute-teachings.index') }}" class="substitute-toolbar">
        <div class="substitute-filter-group">
            <select name="semester_id" class="substitute-filter" onchange="this.form.submit()">
                @foreach($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected((string) $selectedSemesterId === (string) $semester->id)>
                        {{ $semester->normalizedName() }} - {{ $semester->schoolYear->name ?? '' }}
                    </option>
                @endforeach
            </select>

            <select name="class_id" class="substitute-filter" onchange="this.form.submit()">
                <option value="all" @selected($selectedClassId === 'all')>Tất cả lớp học</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) $selectedClassId === (string) $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>

            <select name="status" class="substitute-filter" onchange="this.form.submit()">
                <option value="all" @selected($selectedStatus === 'all')>Tất cả trạng thái</option>
                @foreach($statusLabels as $status => $label)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="substitute-card">
        <table class="substitute-table">
            <thead>
                <tr>
                    <th style="width: 6%;">STT</th>
                    <th style="width: 13%;">Ngày đổi</th>
                    <th style="width: 20%;">Lớp & Tiết</th>
                    <th style="width: 18%;">Giáo viên gốc</th>
                    <th style="width: 18%;">Giáo viên dạy thay</th>
                    <th style="width: 13%;">Trạng thái</th>
                    <th style="width: 12%;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($substitutes as $index => $substitute)
                    @php
                        $entry = $substitute->timetableEntry;
                        $periodLabel = $entry ? (($dayLabels[(int) $entry->day_of_week] ?? 'Thứ ' . $entry->day_of_week) . ' • ' . $entry->displayPeriod()) : '-';
                        $slotLabel = trim(($substitute->classRoom->name ?? '-') . ' • ' . $periodLabel);
                        $subjectLabel = $entry?->displaySubjectName() ?: '-';
                    @endphp
                    <tr>
                        <td class="text-gray-500">{{ $index + 1 }}</td>
                        <td>{{ $substitute->substitute_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            <div class="d-flex flex-column gap-1 text-left">
                                <span class="text-sm font-normal text-gray-700">{{ $slotLabel }}</span>
                                <span class="text-xs font-normal text-gray-500">{{ $subjectLabel }}</span>
                            </div>
                        </td>
                        <td>{{ $substitute->originalTeacher->name ?? '-' }}</td>
                        <td>{{ $substitute->substituteTeacher->name ?? '-' }}</td>
                        <td>
                            <span class="substitute-status-pill {{ $substitute->status }}">
                                {{ $substitute->status === \App\Models\SubstituteTeaching::STATUS_APPROVED ? '🟢' : '🟡' }} {{ $substitute->statusLabel() }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2 justify-content-start">
                                @if(! $readOnly)
                                    <button
                                        type="button"
                                        class="substitute-action-btn edit"
                                        data-substitute-open-edit
                                        data-action="{{ route('substitute-teachings.update', $substitute) }}"
                                        data-date="{{ $substitute->substitute_date?->format('Y-m-d') }}"
                                        data-from-date="{{ $substitute->from_date?->format('Y-m-d') }}"
                                        data-to-date="{{ $substitute->to_date?->format('Y-m-d') }}"
                                        data-scope="{{ $substitute->scope_type ?: \App\Models\SubstituteTeaching::SCOPE_PERIOD }}"
                                        data-id="{{ $substitute->id }}"
                                        data-entry-id="{{ $substitute->timetable_entry_id }}"
                                        data-teacher-id="{{ $substitute->substitute_teacher_id }}"
                                        data-status="{{ $substitute->status }}"
                                        data-note="{{ e($substitute->note) }}"
                                    >
                                        <i class="bi bi-pencil-square"></i><span>Duyệt</span>
                                    </button>
                                    <form method="POST" action="{{ route('substitute-teachings.destroy', $substitute) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa lịch dạy thay này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="substitute-action-btn delete">
                                            <i class="bi bi-trash"></i><span>Xóa</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs font-normal text-gray-400">Chỉ xem</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state"><i class="bi bi-calendar2-plus"></i>Chưa có lịch dạy thay.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="substitute-footer">
            <span>Hiển thị {{ $substitutes->count() }} lịch dạy thay</span>
        </div>
    </div>
</div>

@if(! $readOnly)
    <div id="substitute-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 font-sans d-none" aria-hidden="true">
        <div class="substitute-modal-card w-full max-w-md bg-white p-6 rounded-xl shadow-2xl flex flex-col gap-4 text-left border border-orange-100">
            <div class="w-full text-left">
                <h2 id="substitute-modal-title" class="text-base font-semibold text-gray-900 text-left mb-1">Lịch dạy thay</h2>
                <p class="text-xs font-normal text-orange-700/70 text-left mb-0">Chọn phạm vi, tiết học, giáo viên dạy thay và trạng thái duyệt.</p>
            </div>

            @if($errors->any())
                <div class="substitute-error-box">
                    @foreach($errors->all() as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            @endif

            <div id="substitute-suggestions" class="substitute-suggestions-box mt-2 p-3 bg-orange-50/60 border border-orange-100 rounded-lg text-left w-full flex flex-col gap-2 d-none">
                <div class="text-xs font-normal text-orange-900 text-left">💡 Gợi ý giáo viên cùng bộ môn đang trống tiết này:</div>
                <div id="substitute-suggestion-list" class="w-full text-left"></div>
            </div>

            <form id="substitute-form" method="POST" action="{{ route('substitute-teachings.store') }}" class="flex flex-col gap-3 text-left">
                @csrf
                <input id="substitute-method" type="hidden" name="_method" value="PUT" disabled>
                <input id="substitute-ignore-id" type="hidden" value="">

                <div class="text-left">
                    <label for="substitute-scope" class="form-label text-sm font-normal text-gray-700">Phạm vi thời gian</label>
                    <select id="substitute-scope" name="scope_type" class="substitute-input w-full" required>
                        @foreach($scopeLabels as $scope => $label)
                            <option value="{{ $scope }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="period-date-fields" class="text-left">
                    <label for="substitute-date" class="form-label text-sm font-normal text-gray-700">Ngày nghỉ / Ngày đổi</label>
                    <input id="substitute-date" type="date" name="substitute_date" class="substitute-input w-full" value="{{ now()->format('Y-m-d') }}">
                </div>

                <div id="range-date-fields" class="grid grid-cols-2 gap-3 text-left d-none">
                    <div class="text-left">
                        <label for="substitute-from-date" class="form-label text-sm font-normal text-gray-700">Từ ngày</label>
                        <input id="substitute-from-date" type="date" name="from_date" class="substitute-input w-full">
                    </div>
                    <div class="text-left">
                        <label for="substitute-to-date" class="form-label text-sm font-normal text-gray-700">Đến ngày</label>
                        <input id="substitute-to-date" type="date" name="to_date" class="substitute-input w-full">
                    </div>
                </div>

                <div class="text-left">
                    <label for="substitute-entry" class="form-label text-sm font-normal text-gray-700">Lớp & Tiết học</label>
                    <select id="substitute-entry" name="timetable_entry_id" class="substitute-input w-full" required>
                        <option value="">Chọn lớp & tiết học</option>
                        @foreach($entries as $entry)
                            <option value="{{ $entry->id }}">
                                {{ $entry->timetable?->classRoom?->name ?? '-' }} • {{ $dayLabels[(int) $entry->day_of_week] ?? 'Thứ ' . $entry->day_of_week }} • {{ $entry->displayPeriod() }} • {{ $entry->displaySubjectName() }} • {{ $entry->displayTeacherName() ?: 'Chưa có giáo viên' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="text-left">
                    <label for="substitute-teacher" class="form-label text-sm font-normal text-gray-700">Giáo viên dạy thay</label>
                    <select id="substitute-teacher" name="substitute_teacher_id" class="substitute-input w-full" required>
                        <option value="">Chọn giáo viên dạy thay</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->teacher_code }} - {{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-left">
                    <label for="substitute-status" class="form-label text-sm font-normal text-gray-700">Trạng thái</label>
                    <select id="substitute-status" name="status" class="substitute-input w-full" required>
                        @foreach($statusLabels as $status => $label)
                            <option value="{{ $status }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-left">
                    <label for="substitute-note" class="form-label text-sm font-normal text-gray-700">Ghi chú</label>
                    <textarea id="substitute-note" name="note" class="substitute-input w-full" rows="2" placeholder="Lý do đổi tiết hoặc ghi chú duyệt"></textarea>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-secondary" data-substitute-close>Đóng</button>
                    <button type="submit" class="btn btn-primary" data-substitute-submit><i class="bi bi-save me-1"></i>Lưu lịch dạy thay</button>
                </div>
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('substitute-modal');
    const form = document.getElementById('substitute-form');
    const methodInput = document.getElementById('substitute-method');
    const title = document.getElementById('substitute-modal-title');
    const scopeInput = document.getElementById('substitute-scope');
    const periodDateFields = document.getElementById('period-date-fields');
    const rangeDateFields = document.getElementById('range-date-fields');
    const dateInput = document.getElementById('substitute-date');
    const fromDateInput = document.getElementById('substitute-from-date');
    const toDateInput = document.getElementById('substitute-to-date');
    const entryInput = document.getElementById('substitute-entry');
    const teacherInput = document.getElementById('substitute-teacher');
    const statusInput = document.getElementById('substitute-status');
    const noteInput = document.getElementById('substitute-note');
    const ignoreInput = document.getElementById('substitute-ignore-id');
    const suggestionBox = document.getElementById('substitute-suggestions');
    const suggestionList = document.getElementById('substitute-suggestion-list');
    const submitButton = document.querySelector('[data-substitute-submit]');
    const storeAction = @json(route('substitute-teachings.store'));
    const recommendationAction = @json(route('substitute-teachings.recommendations'));
    const csrfToken = @json(csrf_token());
    let bypassAvailabilityCheck = false;

    if (!modal || !form) {
        return;
    }

    const today = () => new Date().toISOString().slice(0, 10);

    const syncScopeFields = () => {
        const isRange = scopeInput.value === 'date_range';
        periodDateFields.classList.toggle('d-none', isRange);
        rangeDateFields.classList.toggle('d-none', !isRange);
        dateInput.required = !isRange;
        fromDateInput.required = isRange;
        toDateInput.required = isRange;
    };

    const hideSuggestions = () => {
        suggestionBox?.classList.add('d-none');
        if (suggestionList) {
            suggestionList.innerHTML = '';
        }
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-60');
        }
    };

    const showSuggestions = (teachers, message) => {
        if (!suggestionBox || !suggestionList) {
            return;
        }

        suggestionList.innerHTML = '';
        const warning = document.createElement('div');
        warning.className = 'substitute-error-box mb-2';
        warning.textContent = message || '⚠️ Giáo viên này đang có tiết dạy tại lớp khác, vui lòng chọn giáo viên khác';
        suggestionList.appendChild(warning);

        if (!teachers.length) {
            const empty = document.createElement('div');
            empty.className = 'text-xs font-normal text-gray-500 text-left';
            empty.textContent = 'Chưa tìm thấy giáo viên cùng bộ môn đang trống tiết này.';
            suggestionList.appendChild(empty);
        }

        teachers.forEach((teacher) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'substitute-suggestion-btn text-xs font-normal text-orange-850 bg-white border border-orange-200 px-2 py-1 rounded-md hover:bg-orange-100 transition-all cursor-pointer inline-block mr-2';
            button.textContent = `${teacher.teacher_code ? teacher.teacher_code + ' - ' : ''}${teacher.name}`;
            button.title = [teacher.subject, teacher.department].filter(Boolean).join(' • ');
            button.addEventListener('click', () => {
                teacherInput.value = teacher.id;
                hideSuggestions();
                teacherInput.dispatchEvent(new Event('change', { bubbles: true }));
            });
            suggestionList.appendChild(button);
        });

        suggestionBox.classList.remove('d-none');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-60');
        }
    };

    const availabilityPayload = () => {
        const body = new URLSearchParams();
        body.set('_token', csrfToken);
        body.set('scope_type', scopeInput.value || 'period');
        body.set('substitute_date', dateInput.value || '');
        body.set('from_date', fromDateInput.value || '');
        body.set('to_date', toDateInput.value || '');
        body.set('timetable_entry_id', entryInput.value || '');
        body.set('substitute_teacher_id', teacherInput.value || '');
        body.set('ignore_substitute_id', ignoreInput.value || '');
        return body;
    };

    const hasEnoughAvailabilityData = () => {
        if (!entryInput.value || !teacherInput.value) {
            return false;
        }

        if (scopeInput.value === 'date_range') {
            return Boolean(fromDateInput.value && toDateInput.value);
        }

        return Boolean(dateInput.value);
    };

    const checkAvailability = async () => {
        if (!hasEnoughAvailabilityData()) {
            hideSuggestions();
            return { busy: false };
        }

        const response = await fetch(recommendationAction, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: availabilityPayload(),
        });

        if (!response.ok) {
            return { busy: false };
        }

        const payload = await response.json();
        if (payload.busy) {
            showSuggestions(payload.teachers || [], payload.message);
            return payload;
        }

        hideSuggestions();
        return payload;
    };

    const openModal = () => {
        modal.classList.remove('d-none');
        modal.setAttribute('aria-hidden', 'false');
        syncScopeFields();
    };

    const closeModal = () => {
        modal.classList.add('d-none');
        modal.setAttribute('aria-hidden', 'true');
        hideSuggestions();
    };

    scopeInput.addEventListener('change', syncScopeFields);
    document.querySelectorAll('[data-substitute-close]').forEach((button) => button.addEventListener('click', closeModal));
    modal.addEventListener('click', (event) => event.target === modal && closeModal());

    document.querySelectorAll('[data-substitute-open-create]').forEach((button) => {
        button.addEventListener('click', () => {
            title.textContent = 'Thêm lịch dạy thay';
            form.action = storeAction;
            methodInput.disabled = true;
            ignoreInput.value = '';
            scopeInput.value = 'period';
            dateInput.value = today();
            fromDateInput.value = '';
            toDateInput.value = '';
            entryInput.value = '';
            teacherInput.value = '';
            statusInput.value = 'pending';
            noteInput.value = '';
            hideSuggestions();
            openModal();
        });
    });

    document.querySelectorAll('[data-substitute-open-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            title.textContent = 'Duyệt lịch dạy thay';
            form.action = button.dataset.action;
            methodInput.disabled = false;
            ignoreInput.value = button.dataset.id || '';
            scopeInput.value = button.dataset.scope || 'period';
            dateInput.value = button.dataset.date || today();
            fromDateInput.value = button.dataset.fromDate || '';
            toDateInput.value = button.dataset.toDate || '';
            entryInput.value = button.dataset.entryId || '';
            teacherInput.value = button.dataset.teacherId || '';
            statusInput.value = button.dataset.status || 'pending';
            noteInput.value = button.dataset.note || '';
            hideSuggestions();
            openModal();
        });
    });

    [scopeInput, dateInput, fromDateInput, toDateInput, entryInput, teacherInput].forEach((input) => {
        input.addEventListener('change', () => {
            bypassAvailabilityCheck = false;
            checkAvailability();
        });
    });

    form.addEventListener('submit', async (event) => {
        if (bypassAvailabilityCheck) {
            return;
        }

        event.preventDefault();
        const payload = await checkAvailability();

        if (payload.busy) {
            return;
        }

        bypassAvailabilityCheck = true;
        form.requestSubmit();
    });

    @if($errors->any())
        openModal();
    @endif
});
</script>
@endpush
@endsection
