@extends('layouts.app')
@section('title', 'Cấu hình cột điểm')

@section('content')
@php
    $typeOptions = \App\Models\ScoreColumn::TYPES;
@endphp

<x-page-header
    title="Cấu hình cột điểm"
    subtitle="Admin quản lý tên, loại và số lượng cột điểm theo năm học, khối và môn học."
>
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <a href="{{ route('scores.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            Quay lại điểm số
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createScoreColumnModal">
            <i class="bi bi-plus-circle"></i>
            Thêm cột điểm
        </button>
    </div>
</x-page-header>

<div class="card mb-3 score-column-filter-card">
    <div class="card-body">
        <form method="GET" class="score-column-filter-row">
            <div class="score-column-filter-left">
                <div class="score-filter-field wide score-filter-search-field">
                    <label class="form-label">Tìm kiếm</label>
                    <div class="toolbar-inline-search">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" class="form-control" value="{{ $keyword ?? '' }}" placeholder="Tên cột điểm hoặc môn học">
                    </div>
                </div>
                <div class="score-filter-field">
                    <label class="form-label">Năm học</label>
                    <select name="school_year_id" class="form-select">
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="score-filter-field compact">
                    <label class="form-label">Khối</label>
                    <select name="grade_level" class="form-select">
                        <option value="all" @selected($selectedGrade === 'all')>Tất cả</option>
                        @foreach([10, 11, 12] as $grade)
                            <option value="{{ $grade }}" @selected((string) $selectedGrade === (string) $grade)>Khối {{ $grade }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="score-filter-field wide">
                    <label class="form-label">Môn học</label>
                    <select name="subject_id" class="form-select">
                        <option value="all" @selected($selectedSubjectId === 'all')>Tất cả môn học</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) $selectedSubjectId === (string) $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="score-filter-actions">
                <button class="btn btn-primary">
                    <i class="bi bi-search"></i>
                    Lọc
                </button>
                <a href="{{ route('score-columns.index') }}" class="btn btn-outline-secondary">Đặt lại</a>
                <button type="button" class="score-bulk-btn open" data-score-column-bulk="open" data-url="{{ route('score-columns.bulk-lock') }}">
                    <i class="bi bi-unlock"></i>
                    Mở tất cả theo bộ lọc
                </button>
                <button type="button" class="score-bulk-btn lock" data-score-column-bulk="locked" data-url="{{ route('score-columns.bulk-lock') }}">
                    <i class="bi bi-lock"></i>
                    Khóa tất cả theo bộ lọc
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade content-modal score-column-config-modal" id="createScoreColumnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form method="POST" action="{{ route('score-columns.store') }}" data-score-column-form>
                @csrf
                <div class="modal-header">
                    <div class="score-column-modal-title">
                        <h5 class="modal-title">Thêm cột điểm số mới</h5>
                        <p>Thiết lập tên cột, loại điểm và thời hạn nhập cho từng môn học theo khối.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="score-column-form-grid">
                        <div>
                            <label class="form-label">Năm học</label>
                            <select name="school_year_id" class="form-select" required>
                                @foreach($years as $year)
                                    <option value="{{ $year->id }}" @selected((string) old('school_year_id', $selectedYearId) === (string) $year->id)>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Khối</label>
                            <select name="grade_level" class="form-select" required>
                                @foreach([10, 11, 12] as $grade)
                                    <option value="{{ $grade }}" @selected((string) old('grade_level', $selectedGrade !== 'all' ? $selectedGrade : 10) === (string) $grade)>Khối {{ $grade }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Môn học</label>
                            <select name="subject_id" class="form-select" required>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" @selected((string) old('subject_id', $selectedSubjectId !== 'all' ? $selectedSubjectId : '') === (string) $subject->id)>{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Tên cột điểm</label>
                            <input name="name" class="form-control" value="{{ old('name') }}" placeholder="Ví dụ: Kiểm tra 15 phút lần 1" required maxlength="255">
                        </div>
                        <div>
                            <label class="form-label">Loại điểm</label>
                            <select name="type" class="form-select" required>
                                @foreach($typeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Thứ tự</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0" max="1000">
                        </div>
                        <div>
                            <label class="form-label">Ngày mở</label>
                            <input type="date" name="input_opens_at" class="form-control" value="{{ old('input_opens_at') }}">
                        </div>
                        <div>
                            <label class="form-label">Ngày khóa</label>
                            <input type="date" name="input_closes_at" class="form-control" value="{{ old('input_closes_at') }}">
                        </div>
                        <div class="score-column-checkline">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveAdd" checked>
                            <label class="form-check-label" for="isActiveAdd">Đang mở nhập điểm</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Thêm cột điểm</button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $scoreColumnGroups = $columns->groupBy(fn ($column) => implode('|', [
        $column->school_year_id,
        $column->grade_level,
        $column->subject_id,
    ]));

    $typeBadgeClass = fn ($type) => match ($type) {
        \App\Models\ScoreColumn::TYPE_MIDTERM => 'midterm',
        \App\Models\ScoreColumn::TYPE_FINAL => 'final',
        default => 'regular',
    };
@endphp

<div class="card score-column-list-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>Danh sách cột điểm</span>
        <span class="text-muted small">{{ $columns->count() }} đầu điểm</span>
    </div>
    <div class="card-body">
        <div class="score-column-list">
            @forelse($scoreColumnGroups as $group)
                @php
                    $firstColumn = $group->first();
                @endphp
                <section class="score-column-group">
                    <div class="score-column-group-header">
                        <div>
                            <strong>📖 MÔN: {{ mb_strtoupper($firstColumn->subject?->name ?? '-') }}</strong>
                            <span>Khối {{ $firstColumn->grade_level }} · {{ $firstColumn->schoolYear?->name ?? '-' }}</span>
                        </div>
                        <span>{{ $group->count() }} đầu điểm</span>
                    </div>

                    <div class="score-column-group-rows">
                        @foreach($group as $column)
                            <article class="score-column-list-item">
                                <div class="score-column-name-cell">
                                    <strong>{{ $column->name }}</strong>
                                </div>

                                <div class="score-column-type-cell">
                                    <span class="score-column-type-badge {{ $typeBadgeClass($column->type) }}">{{ $column->typeLabel() }}</span>
                                </div>

                                <div
                                    class="score-column-time-compact"
                                    data-score-column-time="{{ $column->id }}"
                                    data-open-label="{{ $column->input_opens_at?->format('d/m/Y') ?? 'Vô thời hạn' }}"
                                    data-close-label="{{ $column->input_closes_at?->format('d/m/Y') ?? 'Chưa khóa' }}"
                                    data-updated-label="{{ $column->updated_at?->timezone(config('app.timezone'))->format('H:i d/m/Y') ?? now()->format('H:i d/m/Y') }}"
                                >
                                    <span><i class="bi bi-calendar3"></i><span data-time-open>{{ $column->input_opens_at?->format('d/m/Y') ?? 'Vô thời hạn' }}</span></span>
                                    <span data-time-close-row @class(['is-manual-lock' => ! $column->is_active])>
                                        <i @class(['bi', 'bi-lock-fill' => ! $column->is_active, 'bi-hourglass-split' => $column->is_active]) data-time-close-icon></i>
                                        <em data-time-close @class(['text-muted' => $column->is_active && ! $column->input_closes_at, 'score-column-manual-lock' => ! $column->is_active])>
                                            {{ $column->is_active ? ($column->input_closes_at?->format('d/m/Y') ?? 'Chưa khóa') : 'Đã khóa thủ công lúc '.($column->updated_at?->timezone(config('app.timezone'))->format('H:i d/m/Y') ?? now()->format('H:i d/m/Y')) }}
                                        </em>
                                    </span>
                                </div>

                                <div class="score-column-row-actions">
                                    <button
                                        type="button"
                                        class="score-column-toggle {{ $column->is_active ? 'open' : 'locked' }}"
                                        data-score-column-toggle
                                        data-url="{{ route('score-columns.toggle-lock', $column) }}"
                                        data-active="{{ $column->is_active ? '1' : '0' }}"
                                        data-column-id="{{ $column->id }}"
                                    >
                                        {{ $column->is_active ? '🟢 Đang mở' : '🔒 Đã khóa' }}
                                    </button>
                                    <div class="content-action-group justify-content-end" data-action-synced="true">
                                        <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#editScoreColumn{{ $column->id }}" title="Chỉnh sửa" aria-label="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <div class="dropdown">
                                            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean more" data-bs-toggle="dropdown" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end content-action-menu">
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#showScoreColumn{{ $column->id }}">
                                                        <i class="bi bi-eye"></i>Xem chi tiết
                                                    </button>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ route('score-columns.destroy', $column) }}" onsubmit="return confirm('Xóa đầu điểm này? Hành động này không thể hoàn tác.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item danger" type="submit">
                                                            <i class="bi bi-trash"></i>Xóa đầu điểm
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="empty-state"><i class="bi bi-table"></i>Chưa có cột điểm nào.</div>
            @endforelse
        </div>
    </div>
</div>

@foreach($columns as $column)
    <div class="modal fade content-modal score-column-detail-modal" id="showScoreColumn{{ $column->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="score-column-modal-title">
                        <h5 class="modal-title">{{ $column->name }}</h5>
                        <p>{{ $column->subject?->name ?? '-' }} · Khối {{ $column->grade_level }} · {{ $column->schoolYear?->name ?? '-' }}</p>
                    </div>
                    <span class="score-column-status-badge {{ $column->is_active ? 'open' : 'closed' }}">{{ $column->is_active ? 'Đang mở nhập điểm' : 'Đã khóa nhập điểm' }}</span>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <section class="score-column-detail-section">
                        <h6>Thông tin cấu hình đầu điểm</h6>
                        <div class="score-column-detail-grid">
                            <article>
                                <span>Loại điểm</span>
                                <strong>{{ $column->typeLabel() }}</strong>
                            </article>
                            <article>
                                <span>Thứ tự hiển thị</span>
                                <strong>{{ $column->sort_order }}</strong>
                            </article>
                            <article>
                                <span>Ngày mở nhập</span>
                                <strong>{{ $column->input_opens_at?->format('d/m/Y') ?? 'Không giới hạn' }}</strong>
                            </article>
                            <article>
                                <span>Ngày khóa nhập</span>
                                <strong>{{ $column->input_closes_at?->format('d/m/Y') ?? 'Chưa khóa' }}</strong>
                            </article>
                        </div>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng chi tiết</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade content-modal" id="editScoreColumn{{ $column->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('score-columns.update', $column) }}" data-score-column-form>
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div class="score-column-modal-title">
                            <h5 class="modal-title">Chỉnh sửa cột điểm</h5>
                            <p>Cập nhật cấu hình đầu điểm đang chọn.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Năm học</label>
                                <select name="school_year_id" class="form-select" required>
                                    @foreach($years as $year)
                                        <option value="{{ $year->id }}" @selected($column->school_year_id === $year->id)>{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Khối</label>
                                <select name="grade_level" class="form-select" required>
                                    @foreach([10, 11, 12] as $grade)
                                        <option value="{{ $grade }}" @selected($column->grade_level === $grade)>Khối {{ $grade }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Môn học</label>
                                <select name="subject_id" class="form-select" required>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" @selected($column->subject_id === $subject->id)>{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tên cột điểm</label>
                                <input name="name" class="form-control" value="{{ $column->name }}" required maxlength="255">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Loại điểm</label>
                                <select name="type" class="form-select" required>
                                    @foreach($typeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($column->type === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Thứ tự</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ $column->sort_order }}" min="0" max="1000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày mở</label>
                                <input type="date" name="input_opens_at" class="form-control" value="{{ $column->input_opens_at?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày khóa</label>
                                <input type="date" name="input_closes_at" class="form-control" value="{{ $column->input_closes_at?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive{{ $column->id }}" @checked($column->is_active)>
                                    <label class="form-check-label" for="isActive{{ $column->id }}">Đang mở nhập điểm</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.querySelectorAll('[data-score-column-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const openInput = form.querySelector('[name="input_opens_at"]');
            const closeInput = form.querySelector('[name="input_closes_at"]');

            if (openInput?.value && closeInput?.value && closeInput.value < openInput.value) {
                event.preventDefault();
                closeInput.setCustomValidity('Ngày khóa nhập điểm phải sau hoặc bằng ngày mở.');
                closeInput.reportValidity();
                return;
            }

            closeInput?.setCustomValidity('');
        });
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '{{ csrf_token() }}';
    const openScoreColumnLabel = '🟢 Đang mở';
    const lockedScoreColumnLabel = '🔒 Đã khóa';

    const showScoreColumnToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.className = `score-column-toast ${type === 'success' ? 'success' : 'error'}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        window.setTimeout(() => toast.classList.add('show'), 20);
        window.setTimeout(() => {
            toast.classList.remove('show');
            window.setTimeout(() => toast.remove(), 240);
        }, 2800);
    };

    const formatScoreColumnNow = () => {
        const now = new Date();
        const time = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false });
        const date = now.toLocaleDateString('vi-VN');
        return `${time} ${date}`;
    };

    const renderScoreColumnTime = (columnId, isActive, payload = {}) => {
        const timeCell = Array.from(document.querySelectorAll('[data-score-column-time]'))
            .find((element) => element.dataset.scoreColumnTime === String(columnId));

        if (! timeCell) {
            return;
        }

        const openLabel = payload.input_opens_display || timeCell.dataset.openLabel || 'Vô thời hạn';
        const closeLabel = payload.input_closes_display || timeCell.dataset.closeLabel || 'Chưa khóa';
        const updatedLabel = payload.updated_at_display || timeCell.dataset.updatedLabel || formatScoreColumnNow();
        const openElement = timeCell.querySelector('[data-time-open]');
        const closeElement = timeCell.querySelector('[data-time-close]');
        const closeRow = timeCell.querySelector('[data-time-close-row]');
        const closeIcon = timeCell.querySelector('[data-time-close-icon]');

        if (openElement) {
            openElement.textContent = openLabel;
        }

        if (! closeElement) {
            return;
        }

        closeElement.classList.remove('score-column-manual-lock', 'text-muted');
        closeRow?.classList.remove('is-manual-lock');
        closeIcon?.classList.remove('bi-lock-fill');
        closeIcon?.classList.add('bi-hourglass-split');

        if (isActive) {
            closeElement.textContent = closeLabel;
            closeElement.classList.toggle('text-muted', closeLabel === 'Chưa khóa');
            return;
        }

        timeCell.dataset.updatedLabel = updatedLabel;
        closeElement.textContent = `Đã khóa thủ công lúc ${updatedLabel}`;
        closeElement.classList.add('score-column-manual-lock');
        closeRow?.classList.add('is-manual-lock');
        closeIcon?.classList.remove('bi-hourglass-split');
        closeIcon?.classList.add('bi-lock-fill');
    };

    const applyScoreColumnToggleState = (button, isActive, payload = {}) => {
        button.dataset.active = isActive ? '1' : '0';
        button.classList.toggle('open', isActive);
        button.classList.toggle('locked', ! isActive);
        button.textContent = isActive ? openScoreColumnLabel : lockedScoreColumnLabel;
        renderScoreColumnTime(button.dataset.columnId, isActive, payload);
    };

    document.querySelectorAll('[data-score-column-toggle]').forEach((button) => {
        button.addEventListener('click', async () => {
            const isCurrentlyActive = button.dataset.active === '1';
            button.classList.add('is-loading');

            try {
                const response = await fetch(button.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ is_active: ! isCurrentlyActive }),
                });
                const payload = await response.json().catch(() => ({}));

                if (! response.ok) {
                    throw new Error(payload.message || 'Không thể cập nhật trạng thái cột điểm.');
                }

                applyScoreColumnToggleState(button, Boolean(payload.is_active), payload);
            } catch (error) {
                showScoreColumnToast(error.message || 'Không thể cập nhật trạng thái cột điểm.', 'error');
            } finally {
                button.classList.remove('is-loading');
            }
        });
    });

    document.querySelectorAll('[data-score-column-bulk]').forEach((button) => {
        button.addEventListener('click', async () => {
            const toggles = Array.from(document.querySelectorAll('[data-score-column-toggle]'));
            const columnIds = toggles
                .map((toggle) => toggle.dataset.columnId)
                .filter((id) => typeof id === 'string' && id.length > 0);

            if (columnIds.length === 0) {
                showScoreColumnToast('Không có cột điểm nào trong danh sách hiện tại để cập nhật.', 'error');
                return;
            }

            button.classList.add('is-loading');

            try {
                const response = await fetch(button.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        column_ids: columnIds,
                        status: button.dataset.scoreColumnBulk,
                    }),
                });
                const payload = await response.json().catch(() => ({}));

                if (! response.ok) {
                    throw new Error(payload.message || 'Không thể cập nhật hàng loạt cột điểm.');
                }

                const updatedIds = new Set((payload.column_ids || columnIds).map((id) => String(id)));
                toggles.forEach((toggle) => {
                    if (updatedIds.has(toggle.dataset.columnId)) {
                        applyScoreColumnToggleState(toggle, Boolean(payload.is_active), payload);
                    }
                });

                const actionLabel = payload.is_active ? 'mở' : 'khóa';
                showScoreColumnToast(`🎉 Đã ${actionLabel} thành công hàng loạt ${payload.count ?? columnIds.length} cột điểm được chọn!`);
            } catch (error) {
                showScoreColumnToast(error.message || 'Không thể cập nhật hàng loạt cột điểm.', 'error');
            } finally {
                button.classList.remove('is-loading');
            }
        });
    });
</script>
@endsection
