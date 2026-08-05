@extends('layouts.app')
@section('title', 'Lịch kiểm tra')

@section('content')
@php
    $canManageSchedules = auth()->user()->isAdmin() || auth()->user()->isStaff();
    $canEnterExamScores = auth()->user()->isAdmin() || auth()->user()->isStaff() || auth()->user()->isTeacher();
    $yearName = function ($schedule) use ($years) {
        $yearId = $schedule->schoolYearId();
        return optional($years->firstWhere('id', $yearId))->name
            ?? optional($schedule->semester?->schoolYear)->name
            ?? 'Đang cập nhật';
    };
    $statusClass = fn ($schedule) => match ($schedule->statusLabel()) {
        'Công bố', 'Sắp diễn ra' => 'bg-success',
        'Đang diễn ra' => 'bg-primary',
        'Bản nháp' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
@endphp

<style>
    .exam-score-sync-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        color: #c2410c;
        background: #fff7ed;
        transition: all .18s ease;
    }

    .exam-score-sync-btn:hover {
        color: #9a3412;
        background: #ffedd5;
    }

    .exam-score-sync-table th,
    .exam-score-sync-table td {
        font-size: 1rem;
        font-weight: 400;
        text-align: left;
        vertical-align: middle;
    }

    .exam-score-sync-table th {
        color: #111827;
        font-weight: 500;
    }

    .exam-score-sync-table .form-control {
        max-width: 110px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 400;
    }

    .exam-score-sync-table .form-control:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .2rem rgba(255, 237, 213, .9);
    }

    .exam-filter-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        width: 100%;
        padding: 1rem;
        border: 1px solid #ffedd5;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .exam-filter-toolbar .admin-table-tools-left {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .75rem;
        width: 100%;
        min-width: 0;
    }

    .exam-filter-toolbar .form-select {
        width: 100%;
        min-width: 0;
        height: 40px;
        border-color: #e5e7eb;
        border-radius: 8px;
        color: #374151;
        background-color: #fff;
        font-size: 1rem;
        font-weight: 400;
    }

    .exam-filter-toolbar .form-select:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .22rem rgba(255, 237, 213, .75);
    }

    @media (max-width: 991.98px) {
        .exam-filter-toolbar .admin-table-tools-left {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .exam-filter-toolbar .admin-table-tools-left {
            grid-template-columns: 1fr;
        }
    }
</style>

<x-page-header
    title="Lịch kiểm tra"
    :subtitle="$canManageSchedules
        ? 'Quản lý các kỳ kiểm tra chung của nhà trường, thời gian mở nhập điểm và ghi chú.'
        : 'Theo dõi lịch kiểm tra phù hợp với lớp, môn học và vai trò đang đăng nhập.'"
>
    <div class="d-flex align-items-center gap-2">
        <div class="dropdown d-none">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Bộ lọc" aria-label="Bộ lọc">
                <i class="bi bi-funnel"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 280px;">
                <form method="GET" action="{{ route('exam-schedules.index') }}" class="d-grid gap-3">
                    <div>
                        <label class="form-label small">Năm học</label>
                        <select name="school_year_id" class="form-select">
                            <option value="">Tất cả năm học</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('exam-schedules.index') }}" class="btn btn-secondary">Xóa lọc</a>
                        <button class="btn btn-primary">Áp dụng</button>
                    </div>
                </form>
            </div>
        </div>
        @if($canManageSchedules)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#examScheduleCreateModal">
                <i class="bi bi-plus-lg me-1"></i>Thêm lịch kiểm tra
            </button>
        @endif
    </div>
</x-page-header>

@if($canManageSchedules)
    <div class="modal fade content-modal" id="examScheduleCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
            <div>
                <h6>Thêm lịch kiểm tra</h6>
                <p>Tạo lịch kiểm tra theo năm học, học kỳ, lớp, môn học và phòng.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('exam-schedules.store') }}">
            <div class="modal-body">
                <div class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Năm học</label>
                <select name="school_year_id" class="form-select" required>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Học kỳ</label>
                <select name="semester_id" class="form-select" required>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected($selectedSemesterId === $semester->id)>{{ $semester->normalizedName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Lớp</label>
                <select name="class_id" class="form-select" required>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Môn học</label>
                <select name="subject_id" class="form-select" required>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Loại kiểm tra</label>
                <select name="type" class="form-select" required data-exam-type-select>
                    @foreach($examTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-none" data-custom-exam-type-wrap>
                <label class="form-label">Tên loại kiểm tra</label>
                <input name="custom_display_name" class="form-control" maxlength="255" placeholder="Ví dụ: Thi thử tốt nghiệp" data-custom-exam-type-input>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ngày kiểm tra</label>
                <input type="date" name="exam_date" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Giờ bắt đầu</label>
                <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Giờ kết thúc</label>
                <input type="time" name="end_time" class="form-control" required>
            </div>
            <div class="col-md-1">
                <label class="form-label">Phòng</label>
                <input name="room" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ngày mở nhập điểm</label>
                <input type="date" name="score_input_opens_at" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ngày khóa nhập điểm</label>
                <input type="date" name="score_input_closes_at" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Trạng thái quản lý</label>
                <select name="status" class="form-select" required>
                    <option value="draft">Bản nháp</option>
                    <option value="published">Công bố</option>
                    <option value="canceled">Đã hủy</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Ghi chú</label>
                <input name="note" class="form-control">
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    Thêm lịch kiểm tra
                </button>
            </div>
                </div>
            </div>
        </form>
            </div>
        </div>
    </div>
@endif

<div class="management-card">
    <div class="management-card-header">
        <div>
            <h6>Danh sách lịch kiểm tra</h6>
            <p>Trạng thái thời gian được hệ thống tự động xác định theo ngày giờ kiểm tra.</p>
        </div>
    </div>
    <div class="unified-table-toolbar exam-filter-toolbar mb-3" data-exam-filter-toolbar>
        <div class="admin-table-tools-left">
            <select class="form-select form-select-sm" data-exam-filter="class">
                <option value="">Tất cả lớp</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm" data-exam-filter="subject">
                <option value="">Tất cả môn học</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm" data-exam-filter="type">
                <option value="">Tất cả loại kiểm tra</option>
                @foreach($examTypes as $type => $label)
                    <option value="{{ $type }}">{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm" data-exam-filter="status">
                <option value="">Tất cả trạng thái</option>
                <option value="draft">Bản nháp</option>
                <option value="published">Công bố</option>
                <option value="canceled">Đã hủy</option>
            </select>
        </div>
    </div>
    <div class="table-responsive content-table-wrap">
        <table class="table content-table align-middle">
            <thead>
                <tr>
                    <th>Lớp</th>
                    <th>Môn học</th>
                    <th>Loại kiểm tra</th>
                    <th>Ngày kiểm tra</th>
                    <th>Thời gian</th>
                    <th>Phòng</th>
                    <th>Nhập điểm</th>
                    <th>Trạng thái</th>
                    <th class="text-end action-column-header" aria-label="Thao tác"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($schedules as $schedule)
                @php
                    $detailId = 'exam-schedule-detail-' . $schedule->id;
                    $editId = 'exam-schedule-edit-' . $schedule->id;
                    $scoreInputId = 'exam-schedule-score-input-' . $schedule->id;
                    $selectedYearId = $schedule->schoolYearId();
                    $scoreColumnType = match ($schedule->type) {
                        \App\Models\ExamSchedule::TYPE_MIDTERM => \App\Models\ScoreColumn::TYPE_MIDTERM,
                        \App\Models\ExamSchedule::TYPE_FINAL_TEST => \App\Models\ScoreColumn::TYPE_FINAL,
                        default => null,
                    };
                    $canSyncThisExam = $canEnterExamScores && $scoreColumnType && (
                        auth()->user()->isAdmin()
                        || auth()->user()->isStaff()
                        || \App\Models\TeachingAssignment::where('teacher_id', auth()->user()->teacher?->id)
                            ->where('class_id', $schedule->class_id)
                            ->where('subject_id', $schedule->subject_id)
                            ->where('semester_id', $schedule->semester_id)
                            ->where('status', \App\Models\TeachingAssignment::STATUS_ACTIVE)
                            ->exists()
                    );
                @endphp
                <tr data-exam-row data-class-id="{{ $schedule->class_id }}" data-subject-id="{{ $schedule->subject_id }}" data-type="{{ $schedule->type }}" data-status="{{ $schedule->statusValue() }}">
                    <td class="fw-semibold">{{ $schedule->classRoom->name ?? 'Đang cập nhật' }}</td>
                    <td>{{ $schedule->subject->name ?? 'Đang cập nhật' }}</td>
                    <td>{{ $schedule->displayName() }}</td>
                    <td>{{ optional($schedule->exam_date)->format('d/m/Y') }}</td>
                    <td>{{ $schedule->timeRange() }}</td>
                    <td>{{ $schedule->room ?: 'Đang cập nhật' }}</td>
                    <td>
                        <span class="badge {{ $schedule->scoreInputBadgeClass() }}">{{ $schedule->scoreInputStatusLabel() }}</span>
                        <div class="text-muted small">
                            {{ optional($schedule->score_input_opens_at)->format('d/m/Y') ?: 'Chưa đặt' }}
                            -
                            {{ optional($schedule->score_input_closes_at)->format('d/m/Y') ?: 'Chưa đặt' }}
                        </div>
                    </td>
                    <td><span class="badge {{ $statusClass($schedule) }}">{{ $schedule->statusLabel() }}</span></td>
                    <td>
                        <div class="content-action-group justify-content-end">
                            @if($canSyncThisExam)
                                <button type="button" class="exam-score-sync-btn" data-bs-toggle="modal" data-bs-target="#{{ $scoreInputId }}" title="Nhập điểm và đồng bộ sổ điểm" aria-label="Nhập điểm và đồng bộ sổ điểm">
                                    <i class="bi bi-journal-check"></i>
                                </button>
                            @endif
                            <button type="button" class="content-action-btn icon-only detail" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}" title="Xem chi tiết" aria-label="Xem chi tiết">
                                <i class="bi bi-eye"></i><span class="visually-hidden">Xem chi tiết</span>
                            </button>
                            @if($canManageSchedules)
                                <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#{{ $editId }}" title="Sửa" aria-label="Sửa">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                </button>
                                <form method="POST" action="{{ route('exam-schedules.destroy', $schedule) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="content-action-btn icon-only delete" title="Xóa" aria-label="Xóa" data-bs-toggle="tooltip">
                                        <i class="bi bi-trash"></i><span class="visually-hidden">Xóa</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>

                <div class="modal fade content-modal exam-detail-modal academic-detail-center-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div class="academic-detail-header">
                                    <div class="academic-detail-identity">
                                        <h5 class="modal-title">{{ \Illuminate\Support\Str::upper($schedule->displayName()) }} — MÔN {{ \Illuminate\Support\Str::upper($schedule->subject->name ?? 'ĐANG CẬP NHẬT') }}</h5>
                                        <div>Lớp: {{ $schedule->classRoom->name ?? 'Đang cập nhật' }} | {{ $schedule->semester?->normalizedName() ?? $schedule->semester?->name ?? '-' }} (Niên khóa {{ $yearName($schedule) }})</div>
                                    </div>
                                    <span class="badge {{ $statusClass($schedule) }}">{{ $schedule->statusLabel() }}</span>
                                </div>
                                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <section class="academic-detail-section">
                                    <h6>Thời gian và địa điểm tổ chức</h6>
                                    <div class="academic-detail-grid">
                                        <article>
                                            <span>Ngày kiểm tra</span>
                                            <strong>{{ optional($schedule->exam_date)->format('d/m/Y') ?: '-' }}</strong>
                                        </article>
                                        <article>
                                            <span>Thời gian làm bài</span>
                                            <strong>{{ $schedule->timeRange() }}</strong>
                                        </article>
                                        <article>
                                            <span>Phòng thi</span>
                                            <strong>{{ $schedule->room ?: '-' }}</strong>
                                        </article>
                                        <article>
                                            <span>Loại kiểm tra</span>
                                            <strong>{{ $schedule->displayName() }}</strong>
                                        </article>
                                    </div>
                                </section>

                                <section class="academic-detail-section mt-3">
                                    <h6>Thời hạn nhập điểm số & Trạng thái khóa sổ</h6>
                                    <div class="exam-score-window-box">
                                        <div class="academic-detail-grid">
                                            <article>
                                                <span>Ngày mở nhập điểm</span>
                                                <strong>{{ optional($schedule->score_input_opens_at)->format('d/m/Y') ?: '-' }}</strong>
                                            </article>
                                            <article>
                                                <span>Ngày khóa nhập điểm</span>
                                                <strong>{{ optional($schedule->score_input_closes_at)->format('d/m/Y') ?: '-' }}</strong>
                                            </article>
                                        </div>
                                        <span class="badge {{ $schedule->scoreInputBadgeClass() }} mt-3">{{ $schedule->scoreInputStatusLabel() }}</span>
                                    </div>
                                </section>

                                <section class="academic-detail-section mt-3">
                                    <h6>Ghi chú</h6>
                                    <div class="exam-detail-note">{{ trim((string) $schedule->note) !== '' ? $schedule->note : '-' }}</div>
                                </section>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng cửa sổ</button>
                            </div>
                        </div>
                    </div>
                </div>

                @if($canSyncThisExam)
                    @php
                        $examStudents = $schedule->classRoom?->students
                            ? $schedule->classRoom->students->where('status', \App\Models\Student::STATUS_STUDYING)->sortBy('student_code')->values()
                            : collect();
                        $examScoreColumn = \App\Models\ScoreColumn::where('school_year_id', $schedule->semester?->school_year_id ?? $schedule->schoolYearId())
                            ->where('subject_id', $schedule->subject_id)
                            ->where('grade_level', (int) ($schedule->classRoom?->grade_level ?? 0))
                            ->where('type', $scoreColumnType)
                            ->orderBy('sort_order')
                            ->first();
                        $examHeaders = \App\Models\ScoreHeader::with(['details.scoreColumn'])
                            ->whereIn('student_id', $examStudents->pluck('id'))
                            ->where('subject_id', $schedule->subject_id)
                            ->where('semester_id', $schedule->semester_id)
                            ->get()
                            ->keyBy('student_id');
                        $scoreModalLocked = ! (auth()->user()->isAdmin() || auth()->user()->isStaff()) && ! $schedule->isScoreInputOpen();
                    @endphp
                    <div class="modal fade content-modal" id="{{ $scoreInputId }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('exam-schedules.scores.store', $schedule) }}" data-exam-score-sync-form>
                                    @csrf
                                    <div class="modal-header">
                                        <div>
                                            <div class="modal-kicker">Đồng bộ điểm tự động</div>
                                            <h5 class="modal-title">{{ $schedule->displayName() }} - {{ $schedule->subject?->name ?? '-' }}</h5>
                                            <p class="text-muted small mb-0 fw-normal">{{ $schedule->classRoom?->name ?? '-' }} · {{ $schedule->semester?->normalizedName() ?? '-' }} · {{ $scoreColumnType === \App\Models\ScoreColumn::TYPE_MIDTERM ? 'Cột Kiểm tra Giữa kỳ' : 'Cột Kiểm tra Cuối kỳ' }}</p>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if($scoreModalLocked)
                                            <div class="alert alert-warning fw-normal">
                                                Lịch kiểm tra chưa mở hoặc đã khóa nhập điểm. Dữ liệu bên dưới đang ở chế độ chỉ xem.
                                            </div>
                                        @endif
                                        <label class="d-inline-flex align-items-center gap-2 mb-3 fw-normal text-gray-800">
                                            <input type="checkbox" class="form-check-input mt-0" name="is_retest" value="1" @disabled($scoreModalLocked)>
                                            Đánh dấu đây là điểm kiểm tra bù/thi lại nếu ghi đè điểm cũ
                                        </label>
                                        <div class="table-responsive">
                                            <table class="table align-middle exam-score-sync-table">
                                                <thead>
                                                    <tr>
                                                        <th>Mã HS</th>
                                                        <th>Họ tên</th>
                                                        <th>Điểm bài kiểm tra</th>
                                                        <th>Trạng thái đồng bộ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @forelse($examStudents as $student)
                                                    @php
                                                        $header = $examHeaders->get($student->id);
                                                        $detail = $examScoreColumn
                                                            ? $header?->details?->firstWhere('score_column_id', $examScoreColumn->id)
                                                            : $header?->details?->firstWhere('exam_schedule_id', $schedule->id);
                                                        $value = $detail?->value !== null ? rtrim(rtrim(number_format((float) $detail->value, 1, '.', ''), '0'), '.') : '';
                                                    @endphp
                                                    <tr>
                                                        <td class="fw-semibold">{{ $student->student_code }}</td>
                                                        <td>{{ $student->name }}</td>
                                                        <td>
                                                            <input
                                                                type="text"
                                                                name="scores[{{ $student->id }}]"
                                                                class="form-control"
                                                                value="{{ $value }}"
                                                                inputmode="decimal"
                                                                pattern="^(10(\.0)?|[0-9](\.[0-9])?)$"
                                                                placeholder="0-10"
                                                                @disabled($scoreModalLocked)
                                                            >
                                                        </td>
                                                        <td>
                                                            @if($detail)
                                                                <span class="badge bg-success">Đã có trong sổ điểm</span>
                                                            @else
                                                                <span class="text-muted">Chưa nhập</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4">
                                                            <div class="empty-state"><i class="bi bi-person-dash"></i>Lớp chưa có học sinh.</div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                        <button class="btn btn-primary" @disabled($scoreModalLocked || $examStudents->isEmpty())>
                                            <i class="bi bi-save me-1"></i>Lưu và đồng bộ sổ điểm
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                @if($canManageSchedules)
                    <div class="modal fade content-modal" id="{{ $editId }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('exam-schedules.update', $schedule) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <div>
                                            <div class="modal-kicker">Chỉnh sửa lịch kiểm tra</div>
                                            <h5 class="modal-title">{{ $schedule->displayName() }}</h5>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Năm học</label>
                                                <select name="school_year_id" class="form-select" required>
                                                    @foreach($years as $year)
                                                        <option value="{{ $year->id }}" @selected($selectedYearId === $year->id)>{{ $year->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Học kỳ</label>
                                                <select name="semester_id" class="form-select" required>
                                                    @foreach($semesters as $semester)
                                                        <option value="{{ $semester->id }}" @selected($schedule->semester_id === $semester->id)>{{ $semester->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Lớp</label>
                                                <select name="class_id" class="form-select" required>
                                                    @foreach($classes as $class)
                                                        <option value="{{ $class->id }}" @selected($schedule->class_id === $class->id)>{{ $class->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Môn học</label>
                                                <select name="subject_id" class="form-select" required>
                                                    @foreach($subjects as $subject)
                                                        <option value="{{ $subject->id }}" @selected($schedule->subject_id === $subject->id)>{{ $subject->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Loại kiểm tra</label>
                                                <select name="type" class="form-select" required data-exam-type-select>
                                                    @foreach($examTypes as $type => $label)
                                                        <option value="{{ $type }}" @selected($schedule->type === $type)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 {{ $schedule->isCustomType() ? '' : 'd-none' }}" data-custom-exam-type-wrap>
                                                <label class="form-label">Tên loại kiểm tra</label>
                                                <input name="custom_display_name" class="form-control" value="{{ $schedule->isCustomType() ? $schedule->displayName() : '' }}" maxlength="255" placeholder="Ví dụ: Thi thử tốt nghiệp" data-custom-exam-type-input>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Ngày kiểm tra</label>
                                                <input type="date" name="exam_date" class="form-control" value="{{ optional($schedule->exam_date)->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Giờ bắt đầu</label>
                                                <input type="time" name="start_time" class="form-control" value="{{ $schedule->start_time ? substr($schedule->start_time, 0, 5) : '' }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Giờ kết thúc</label>
                                                <input type="time" name="end_time" class="form-control" value="{{ $schedule->end_time ? substr($schedule->end_time, 0, 5) : '' }}" required>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">Phòng</label>
                                                <input name="room" class="form-control" value="{{ $schedule->room }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Ngày mở nhập điểm</label>
                                                <input type="date" name="score_input_opens_at" class="form-control" value="{{ optional($schedule->score_input_opens_at)->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Ngày khóa nhập điểm</label>
                                                <input type="date" name="score_input_closes_at" class="form-control" value="{{ optional($schedule->score_input_closes_at)->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Trạng thái quản lý</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="draft" @selected($schedule->isDraft())>Bản nháp</option>
                                                    <option value="published" @selected($schedule->isPublished())>Công bố</option>
                                                    <option value="canceled" @selected($schedule->isCanceled())>Đã hủy</option>
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Ghi chú</label>
                                                <input name="note" class="form-control" value="{{ $schedule->note }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <tr>
                    <td colspan="9"><div class="empty-state"><i class="bi bi-calendar2-x"></i>Chưa có lịch kiểm tra.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($schedules, 'links'))
        <div class="content-pagination">{{ $schedules->links() }}</div>
    @endif
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const examToolbar = document.querySelector('[data-exam-filter-toolbar]');
        const examRows = Array.from(document.querySelectorAll('[data-exam-row]'));
        let examFilterTimer = null;

        const applyExamFilters = () => {
            const values = {
                class: examToolbar?.querySelector('[data-exam-filter="class"]')?.value || '',
                subject: examToolbar?.querySelector('[data-exam-filter="subject"]')?.value || '',
                type: examToolbar?.querySelector('[data-exam-filter="type"]')?.value || '',
                status: examToolbar?.querySelector('[data-exam-filter="status"]')?.value || '',
            };

            examRows.forEach((row) => {
                const visible = (!values.class || row.dataset.classId === values.class)
                    && (!values.subject || row.dataset.subjectId === values.subject)
                    && (!values.type || row.dataset.type === values.type)
                    && (!values.status || row.dataset.status === values.status);

                row.classList.toggle('d-none', !visible);
            });
        };

        examToolbar?.querySelectorAll('[data-exam-filter]').forEach((field) => {
            field.addEventListener('change', () => {
                clearTimeout(examFilterTimer);
                examFilterTimer = setTimeout(applyExamFilters, 300);
            });
        });

        document.querySelectorAll('form').forEach((form) => {
            const select = form.querySelector('[data-exam-type-select]');
            const customWrap = form.querySelector('[data-custom-exam-type-wrap]');
            const customInput = form.querySelector('[data-custom-exam-type-input]');

            if (!select || !customWrap || !customInput) {
                return;
            }

            const syncCustomType = () => {
                const isCustom = select.value === 'custom';
                customWrap.classList.toggle('d-none', !isCustom);
                customInput.required = isCustom;

                if (!isCustom) {
                    customInput.value = '';
                }
            };

            select.addEventListener('change', syncCustomType);
            syncCustomType();
        });
    });
</script>
@endsection
