@extends('layouts.app')
@section('title', 'Khen thưởng')

@section('content')
@php
    $selectedSemesterId = (string) ($selectedSemesterId ?? '');
    $selectedClassId = (string) ($selectedClassId ?? 'all');
    $selectedType = (string) ($selectedType ?? 'all');
@endphp

<style>
    .reward-table {
        width: 100%;
        margin: 0;
        table-layout: fixed;
    }

    .reward-table th,
    .reward-table td {
        color: #1f2937;
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        font-size: .95rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
        white-space: normal;
    }

    .reward-table th {
        color: #111827;
        font-weight: 500;
        background: #fff7ed;
        border-bottom: 1px solid #fed7aa;
    }

    .reward-toolbar {
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
        box-shadow: 0 1px 0 rgba(0, 0, 0, .03);
        text-align: left;
    }

    .reward-filter-group {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem;
        text-align: left;
    }

    .reward-filter {
        min-width: 150px;
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: .45rem .7rem;
        text-align: left;
    }

    .reward-filter:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .2rem rgba(255, 237, 213, .82);
        outline: none;
    }

    .reward-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .75rem 1rem;
        border-top: 1px solid #f3f4f6;
        color: #6b7280;
        font-size: .88rem;
        font-weight: 400;
        text-align: left;
    }

    .reward-modal-field label {
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        margin-bottom: .35rem;
        text-align: left;
    }

    .reward-modal-field .form-control,
    .reward-modal-field .form-select {
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        border-color: #e5e7eb;
        border-radius: 8px;
        text-align: left;
    }

    .reward-modal-field .form-control:focus,
    .reward-modal-field .form-select:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .2rem rgba(255, 237, 213, .82);
    }

    #reward-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 1050 !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1rem !important;
        background: rgba(0, 0, 0, .4) !important;
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        text-align: left !important;
    }

    #reward-modal.d-none {
        display: none !important;
    }

    #reward-modal:not(.d-none) {
        display: flex !important;
    }

    #reward-modal > div {
        width: 100% !important;
        max-width: 28rem !important;
        background: #fff !important;
        border: 1px solid #fed7aa !important;
        border-radius: 12px !important;
        padding: 1.5rem !important;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .22) !important;
        text-align: left !important;
    }
</style>

<x-page-header
    title="Khen thưởng"
    subtitle="Quản lý quyết định khen thưởng học sinh theo học kỳ, lớp học và hình thức ghi nhận."
>
    @if(! $readOnly)
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary" data-reward-open-create>
                <i class="bi bi-plus-lg me-1"></i>Thêm quyết định mới
            </button>
            <button type="button" class="bg-orange-100 text-orange-700 hover:bg-orange-200 border border-orange-300 px-3.5 py-2 rounded-lg text-xs md:text-sm font-normal cursor-pointer transition-all font-sans inline-flex items-center gap-1 ml-2" data-reward-scan>
                🚀 Tự động quét danh hiệu
            </button>
        </div>
    @endif
</x-page-header>

<form method="GET" action="{{ route('rewards.index') }}" class="reward-toolbar">
    <div class="reward-filter-group">
        <select name="semester_id" class="reward-filter" onchange="this.form.submit()">
            @foreach($semesters as $semester)
                <option value="{{ $semester->id }}" @selected((string) $selectedSemesterId === (string) $semester->id)>
                    {{ $semester->normalizedName() }} - {{ $semester->schoolYear->name ?? '' }}
                </option>
            @endforeach
        </select>

        <select name="class_id" class="reward-filter" onchange="this.form.submit()" @disabled($isHomeroomOnly)>
            @if(! $isHomeroomOnly)
                <option value="all" @selected($selectedClassId === 'all')>Tất cả lớp học</option>
            @endif
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((string) $selectedClassId === (string) $class->id)>
                    {{ $class->name }}
                </option>
            @endforeach
        </select>
        @if($isHomeroomOnly)
            <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
        @endif

        <select name="reward_type" class="reward-filter" onchange="this.form.submit()">
            <option value="all" @selected($selectedType === 'all')>Tất cả khen thưởng</option>
            @foreach($rewardTypes as $type => $label)
                <option value="{{ $type }}" @selected($selectedType === $type)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table reward-table">
            <thead>
                <tr>
                    <th style="width: 6%;">STT</th>
                    <th style="width: 12%;">Mã HS</th>
                    <th style="width: 20%;">Họ và Tên</th>
                    <th style="width: 10%;">Lớp</th>
                    <th style="width: 18%;">Hình thức Khen thưởng</th>
                    <th style="width: 22%;">Số Quyết định / Chi tiết</th>
                    <th style="width: 12%;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rewards as $index => $reward)
                    <tr>
                        <td class="text-gray-500">{{ $index + 1 }}</td>
                        <td>{{ $reward->student->student_code ?? '-' }}</td>
                        <td>{{ $reward->student->name ?? '-' }}</td>
                        <td>{{ $reward->classRoom->name ?? '-' }}</td>
                        <td>
                            <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">
                                {{ $reward->typeLabel() }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1 text-left">
                                <span class="text-sm font-normal text-gray-700">{{ $reward->decision_number ?: '-' }}</span>
                                <span class="text-xs font-normal text-gray-500">{{ $reward->detail ?: '-' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2 justify-content-start">
                                @if(! $readOnly)
                                    <button
                                        type="button"
                                        class="content-action-btn edit"
                                        data-reward-open-edit
                                        data-id="{{ $reward->id }}"
                                        data-student-id="{{ $reward->student_id }}"
                                        data-semester-id="{{ $reward->semester_id }}"
                                        data-reward-type="{{ $reward->reward_type }}"
                                        data-decision-number="{{ e($reward->decision_number) }}"
                                        data-detail="{{ e($reward->detail) }}"
                                        data-action="{{ route('rewards.update', $reward) }}"
                                    >
                                        <i class="bi bi-pencil-square"></i><span>Sửa</span>
                                    </button>
                                    <form method="POST" action="{{ route('rewards.destroy', $reward) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa quyết định khen thưởng này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="content-action-btn delete">
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
                            <div class="empty-state"><i class="bi bi-award"></i>Chưa có quyết định khen thưởng.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="reward-footer">
        <span>Hiển thị {{ $rewards->count() }} quyết định khen thưởng</span>
    </div>
</div>

@if(! $readOnly)
    <div id="reward-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 font-sans animate-fade-in d-none" aria-hidden="true">
        <div class="w-full max-w-md bg-white p-6 rounded-xl shadow-2xl flex flex-col gap-4 text-left border border-orange-100">
            <div class="w-full text-left">
                <h2 id="reward-modal-title" class="text-base font-semibold text-gray-900 text-left mb-1">Khen thưởng</h2>
                <p class="text-xs font-normal text-orange-700/70 text-left mb-0">Cập nhật quyết định khen thưởng rồi lưu về hệ thống.</p>
            </div>

            <form id="reward-form" method="POST" action="{{ route('rewards.store') }}" class="flex flex-col gap-3 text-left">
                @csrf
                <input id="reward-method" type="hidden" name="_method" value="PUT" disabled>

                <div class="reward-modal-field text-left">
                    <label for="reward-student" class="form-label">Học sinh</label>
                    <select id="reward-student" name="student_id" class="form-select" required>
                        <option value="">Chọn học sinh</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" data-class-id="{{ $student->class_id }}">
                                {{ $student->student_code }} - {{ $student->name }}{{ $student->classRoom ? ' - Lớp ' . $student->classRoom->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="reward-modal-field text-left">
                    <label for="reward-semester" class="form-label">Học kỳ</label>
                    <select id="reward-semester" name="semester_id" class="form-select" required>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected((string) $selectedSemesterId === (string) $semester->id)>
                                {{ $semester->normalizedName() }} - {{ $semester->schoolYear->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="reward-modal-field text-left">
                    <label for="reward-type" class="form-label">Danh hiệu</label>
                    <select id="reward-type" name="reward_type" class="form-select" required>
                        @foreach($rewardTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="reward-modal-field text-left">
                    <label for="reward-detail" class="form-label">Lời phê chi tiết cuộc thi</label>
                    <textarea id="reward-detail" name="detail" class="form-control" rows="3" placeholder="Nhập nội dung khen thưởng, cuộc thi hoặc thành tích cụ thể"></textarea>
                </div>

                <div class="reward-modal-field text-left">
                    <label for="reward-decision-number" class="form-label">Số Quyết định ký duyệt của Hiệu trưởng</label>
                    <input id="reward-decision-number" type="text" name="decision_number" class="form-control" placeholder="VD: QĐ-2026/KT-01">
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-secondary" data-reward-close>Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu quyết định</button>
                </div>
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('reward-modal');
    const form = document.getElementById('reward-form');
    const methodInput = document.getElementById('reward-method');
    const title = document.getElementById('reward-modal-title');
    const studentInput = document.getElementById('reward-student');
    const semesterInput = document.getElementById('reward-semester');
    const typeInput = document.getElementById('reward-type');
    const detailInput = document.getElementById('reward-detail');
    const decisionInput = document.getElementById('reward-decision-number');
    const storeAction = @json(route('rewards.store'));
    const scanAction = @json(route('rewards.scan'));
    const indexAction = @json(route('rewards.index'));
    const selectedSemesterId = @json($selectedSemesterId);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (!modal || !form) {
        return;
    }

    const openModal = () => {
        modal.classList.remove('d-none');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        modal.classList.add('d-none');
        modal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-reward-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.querySelectorAll('[data-reward-open-create]').forEach((button) => {
        button.addEventListener('click', () => {
            title.textContent = 'Thêm quyết định khen thưởng';
            form.action = storeAction;
            methodInput.disabled = true;
            studentInput.value = '';
            semesterInput.value = selectedSemesterId;
            typeInput.value = 'outstanding';
            detailInput.value = '';
            decisionInput.value = '';
            openModal();
        });
    });

    document.querySelectorAll('[data-reward-open-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            title.textContent = 'Cập nhật quyết định khen thưởng';
            form.action = button.dataset.action;
            methodInput.disabled = false;
            studentInput.value = button.dataset.studentId || '';
            semesterInput.value = button.dataset.semesterId || selectedSemesterId;
            typeInput.value = button.dataset.rewardType || 'outstanding';
            detailInput.value = button.dataset.detail || '';
            decisionInput.value = button.dataset.decisionNumber || '';
            openModal();
        });
    });

    document.querySelectorAll('[data-reward-scan]').forEach((button) => {
        button.addEventListener('click', async () => {
            const semesterInput = document.querySelector('select[name="semester_id"]');
            const classInput = document.querySelector('select[name="class_id"], input[name="class_id"]');
            const originalText = button.textContent;

            button.disabled = true;
            button.textContent = 'Đang quét danh hiệu...';

            try {
                const response = await fetch(scanAction, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        semester_id: semesterInput?.value || selectedSemesterId,
                        class_id: classInput?.value || 'all'
                    })
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || 'Không thể quét danh hiệu.');
                }

                button.textContent = payload.message || 'Đã quét danh hiệu';
                const nextUrl = new URL(indexAction, window.location.origin);
                nextUrl.searchParams.set('semester_id', semesterInput?.value || selectedSemesterId);
                nextUrl.searchParams.set('class_id', classInput?.value || 'all');
                nextUrl.searchParams.set('reward_type', 'all');
                window.setTimeout(() => window.location.href = nextUrl.toString(), 650);
            } catch (error) {
                window.alert(error.message || 'Không thể quét danh hiệu.');
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });
});
</script>
@endpush
@endsection
