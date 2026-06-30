@extends('layouts.app')
@section('title', 'Lịch thi')

@section('content')
@php
    $canManageSchedules = auth()->user()->isAdmin() || auth()->user()->isStaff();
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

<div class="page-heading">
    <div>
        <h5>Lịch thi</h5>
        <div class="text-muted">Quản lý lịch kiểm tra, lịch thi, phòng thi và ghi chú.</div>
    </div>
</div>

@if($canManageSchedules)
    <div class="management-card mb-3">
        <div class="management-card-header">
            <div>
                <h6>Thêm lịch thi</h6>
                <p>Tạo lịch thi theo năm học, học kỳ, lớp, môn học và phòng thi.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('exam-schedules.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Năm học</label>
                <select name="school_year_id" class="form-select" required>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Học kỳ</label>
                <select name="semester_id" class="form-select" required>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
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
                <label class="form-label">Loại kỳ thi</label>
                <select name="title" class="form-select" required>
                    @foreach($examTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ngày thi</label>
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
                    Thêm lịch thi
                </button>
            </div>
        </form>
    </div>
@endif

<div class="management-card">
    <div class="management-card-header">
        <div>
            <h6>Danh sách lịch thi</h6>
            <p>Trạng thái thời gian được hệ thống tự động xác định theo ngày giờ thi.</p>
        </div>
    </div>
    <div class="table-responsive content-table-wrap">
        <table class="table content-table align-middle">
            <thead>
                <tr>
                    <th>Lớp</th>
                    <th>Môn học</th>
                    <th>Loại kỳ thi</th>
                    <th>Ngày thi</th>
                    <th>Thời gian</th>
                    <th>Phòng thi</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($schedules as $schedule)
                @php
                    $detailId = 'exam-schedule-detail-' . $schedule->id;
                    $editId = 'exam-schedule-edit-' . $schedule->id;
                    $selectedYearId = $schedule->schoolYearId();
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $schedule->classRoom->name ?? 'Đang cập nhật' }}</td>
                    <td>{{ $schedule->subject->name ?? 'Đang cập nhật' }}</td>
                    <td>{{ $schedule->title }}</td>
                    <td>{{ optional($schedule->exam_date)->format('d/m/Y') }}</td>
                    <td>{{ $schedule->timeRange() }}</td>
                    <td>{{ $schedule->room ?: 'Đang cập nhật' }}</td>
                    <td><span class="badge {{ $statusClass($schedule) }}">{{ $schedule->statusLabel() }}</span></td>
                    <td>
                        <div class="content-action-group justify-content-end">
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

                <div class="modal fade content-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <div class="modal-kicker">Lịch thi</div>
                                    <h5 class="modal-title">{{ $schedule->title }}</h5>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <dl class="content-detail-list">
                                    <div><dt>Năm học</dt><dd>{{ $yearName($schedule) }}</dd></div>
                                    <div><dt>Học kỳ</dt><dd>{{ $schedule->semester->name ?? 'Đang cập nhật' }}</dd></div>
                                    <div><dt>Lớp</dt><dd>{{ $schedule->classRoom->name ?? 'Đang cập nhật' }}</dd></div>
                                    <div><dt>Môn học</dt><dd>{{ $schedule->subject->name ?? 'Đang cập nhật' }}</dd></div>
                                    <div><dt>Loại kỳ thi</dt><dd>{{ $schedule->title }}</dd></div>
                                    <div><dt>Ngày thi</dt><dd>{{ optional($schedule->exam_date)->format('d/m/Y') }}</dd></div>
                                    <div><dt>Giờ bắt đầu</dt><dd>{{ $schedule->start_time ? substr($schedule->start_time, 0, 5) : 'Đang cập nhật' }}</dd></div>
                                    <div><dt>Giờ kết thúc</dt><dd>{{ $schedule->end_time ? substr($schedule->end_time, 0, 5) : 'Đang cập nhật' }}</dd></div>
                                    <div><dt>Phòng thi</dt><dd>{{ $schedule->room ?: 'Đang cập nhật' }}</dd></div>
                                    <div><dt>Ghi chú</dt><dd class="content-full-text">{!! nl2br(e($schedule->note ?: 'Không có ghi chú.')) !!}</dd></div>
                                    <div><dt>Trạng thái</dt><dd>{{ $schedule->statusLabel() }}</dd></div>
                                </dl>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>

                @if($canManageSchedules)
                    <div class="modal fade content-modal" id="{{ $editId }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('exam-schedules.update', $schedule) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <div>
                                            <div class="modal-kicker">Chỉnh sửa lịch thi</div>
                                            <h5 class="modal-title">{{ $schedule->title }}</h5>
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
                                                <label class="form-label">Loại kỳ thi</label>
                                                <select name="title" class="form-select" required>
                                                    @foreach($examTypes as $type)
                                                        <option value="{{ $type }}" @selected($schedule->title === $type)>{{ $type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Ngày thi</label>
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
                    <td colspan="8"><div class="empty-state"><i class="bi bi-calendar2-x"></i>Chưa có lịch thi.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($schedules, 'links'))
        <div class="content-pagination">{{ $schedules->links() }}</div>
    @endif
</div>
@endsection
