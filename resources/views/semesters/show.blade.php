@extends('layouts.app')
@section('title', 'Chi tiết học kỳ')

@php
    $formatUser = fn ($log) => $log?->user?->display_name ?? $log?->user?->username ?? 'Chưa ghi nhận';
    $formatDate = fn ($date) => $date ? $date->format('d/m/Y H:i') : 'Chưa ghi nhận';
    $actionLabels = [
        'semester_created' => 'Tạo học kỳ',
        'semester_updated' => 'Chỉnh sửa',
        'semester_marked_inactive' => 'Chuyển sang Chưa hoạt động',
        'semester_activated' => 'Đặt làm hiện hành',
        'semester_locked' => 'Khóa học kỳ',
        'semester_archived' => 'Lưu trữ',
        'semester_deleted' => 'Xóa',
    ];
@endphp

@section('content')
<div class="page-heading">
    <div>
        <h5>Chi tiết học kỳ {{ $semester->normalizedName() }}</h5>
        <div class="text-muted">Giao diện chỉ đọc, phục vụ kiểm tra thông tin và lịch sử thao tác.</div>
    </div>
    <a href="{{ route('semesters.index', ['school_year_id' => $semester->school_year_id]) }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card p-4 shadow-sm h-100">
            <h6 class="card-accent-title mb-3">Thông tin học kỳ</h6>
            <dl class="content-detail-list">
                <div><dt>Tên học kỳ</dt><dd>{{ $semester->normalizedName() }}</dd></div>
                <div><dt>Năm học</dt><dd>{{ $semester->schoolYear->name ?? 'Chưa xác định' }}</dd></div>
                <div><dt>Trạng thái</dt><dd><span class="badge {{ $semester->statusBadgeClass() }}">{{ $semester->statusLabel() }}</span></dd></div>
                <div><dt>Nhập điểm</dt><dd>{{ $semester->is_score_input_open && $semester->isActive() ? 'Mở' : 'Khóa' }}</dd></div>
                <div><dt>Ngày khóa</dt><dd>{{ $formatDate($semester->locked_at) }}</dd></div>
                <div><dt>Ngày lưu trữ</dt><dd>{{ $formatDate($semester->archived_at) }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card p-4 shadow-sm h-100">
            <h6 class="card-accent-title mb-3">Thông tin hệ thống</h6>
            <dl class="content-detail-list">
                <div><dt>Ngày tạo</dt><dd>{{ $formatDate($semester->created_at) }}</dd></div>
                <div><dt>Ngày cập nhật</dt><dd>{{ $formatDate($semester->updated_at) }}</dd></div>
                <div><dt>Cho phép sửa</dt><dd>{{ $semester->canEdit() && ! $readOnly ? 'Có' : 'Không' }}</dd></div>
                <div><dt>Cho phép xóa</dt><dd>{{ $deleteCheck['allowed'] && ! $readOnly ? 'Có' : 'Không' }}</dd></div>
                @if(! $deleteCheck['allowed'] && $deleteCheck['message'])
                    <div><dt>Lý do không xóa</dt><dd>{{ $deleteCheck['message'] }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
</div>

<div class="card p-4 shadow-sm mt-3">
    <h6 class="card-accent-title mb-3">Nhật ký học kỳ</h6>
    @if($logs->isEmpty())
        <div class="empty-state">
            <i class="bi bi-clock-history"></i>
            Chưa có nhật ký thao tác cho học kỳ này.
        </div>
    @else
        <div class="school-year-timeline">
            @foreach($logs as $log)
                <div class="school-year-timeline-item">
                    <div class="school-year-timeline-dot"></div>
                    <div class="school-year-timeline-content">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-1">
                            <strong>{{ $actionLabels[$log->action] ?? $log->action }}</strong>
                            <span class="text-muted small">{{ $formatDate($log->created_at) }}</span>
                        </div>
                        <div class="text-muted small mt-1">Người thực hiện: {{ $formatUser($log) }}</div>
                        @if($log->description)
                            <p class="mb-0 mt-2">{{ $log->description }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
