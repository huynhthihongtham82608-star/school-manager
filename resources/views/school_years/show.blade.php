@extends('layouts.app')
@section('title', 'Chi tiết năm học')

@php
    [$startYear, $endYear] = $yearParts;
    $tab = request('tab', 'overview');
    $formatUser = fn ($log) => $log?->user?->display_name ?? $log?->user?->username ?? 'Chưa ghi nhận';
    $formatDate = fn ($date) => $date ? $date->format('d/m/Y H:i') : 'Chưa ghi nhận';
    $actionLabels = [
        'school_year_created' => 'Tạo năm học',
        'school_year_updated' => 'Chỉnh sửa',
        'school_year_activated' => 'Kích hoạt',
        'school_year_archived' => 'Lưu trữ',
        'school_year_initialized' => 'Khởi tạo năm học mới từ năm học này',
    ];
    $logText = function ($log) {
        $decoded = json_decode((string) $log->description, true);

        if (is_array($decoded) && ($log->action === 'school_year_initialized')) {
            return 'Khởi tạo năm học mới ' . ($decoded['target_year_name'] ?? '') . ' từ năm học ' . ($decoded['source_year_name'] ?? '') . '.';
        }

        return $log->description;
    };
@endphp

@section('content')
<div class="page-heading">
    <div>
        <h5>Chi tiết năm học {{ $schoolYear->name }}</h5>
        <div class="text-muted">Giao diện chỉ đọc, phục vụ kiểm tra dữ liệu và lịch sử thao tác của năm học.</div>
    </div>
</div>

<div class="school-year-tabs mb-3">
    <a href="{{ route('school-years.detail', ['school_year' => $schoolYear, 'tab' => 'overview']) }}" class="school-year-tab {{ $tab === 'overview' ? 'active' : '' }}">
        <i class="bi bi-info-circle"></i>Tổng quan
    </a>
    <a href="{{ route('school-years.detail', ['school_year' => $schoolYear, 'tab' => 'data']) }}" class="school-year-tab {{ $tab === 'data' ? 'active' : '' }}">
        <i class="bi bi-grid-3x3-gap"></i>Dữ liệu
    </a>
    <a href="{{ route('school-years.detail', ['school_year' => $schoolYear, 'tab' => 'logs']) }}" class="school-year-tab {{ $tab === 'logs' ? 'active' : '' }}">
        <i class="bi bi-clock-history"></i>Nhật ký
    </a>
</div>

@if($tab === 'data')
    <div class="school-year-data-grid">
        @foreach($dataCards as $card)
            <a href="{{ $card['url'] }}" class="school-year-data-card">
                <span class="school-year-data-icon"><i class="bi {{ $card['icon'] }}"></i></span>
                <span>
                    <strong>{{ number_format($card['count'], 0, ',', '.') }}</strong>
                    <span>{{ $card['label'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
@elseif($tab === 'logs')
    <div class="card p-4 shadow-sm">
        <h6 class="card-accent-title mb-3">Nhật ký năm học</h6>
        @if($logs->isEmpty())
            <div class="empty-state">
                <i class="bi bi-clock-history"></i>
                Chưa có nhật ký thao tác cho năm học này.
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
                            @if($logText($log))
                                <p class="mb-0 mt-2">{{ $logText($log) }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@else
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card p-4 shadow-sm h-100">
                <h6 class="card-accent-title mb-3">Thông tin năm học</h6>
                <dl class="content-detail-list">
                    <div><dt>Tên năm học</dt><dd>{{ $schoolYear->name }}</dd></div>
                    <div><dt>Năm bắt đầu</dt><dd>{{ $startYear ?: 'Chưa xác định' }}</dd></div>
                    <div><dt>Năm kết thúc</dt><dd>{{ $endYear ?: 'Chưa xác định' }}</dd></div>
                    <div><dt>Ngày bắt đầu</dt><dd>{{ optional($schoolYear->start_date)->format('d/m/Y') ?: 'Chưa thiết lập' }}</dd></div>
                    <div><dt>Ngày kết thúc</dt><dd>{{ optional($schoolYear->end_date)->format('d/m/Y') ?: 'Chưa thiết lập' }}</dd></div>
                    <div>
                        <dt>Trạng thái</dt>
                        <dd><span class="badge {{ $schoolYear->statusBadgeClass() }}">{{ $schoolYear->statusLabel() }}</span></dd>
                    </div>
                    <div><dt>Ghi chú</dt><dd>Chưa có ghi chú.</dd></div>
                </dl>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card p-4 shadow-sm h-100">
                <h6 class="card-accent-title mb-3">Thông tin thao tác</h6>
                <dl class="content-detail-list">
                    <div><dt>Người tạo</dt><dd>{{ $formatUser($logSummary['created'] ?? null) }}</dd></div>
                    <div><dt>Ngày tạo</dt><dd>{{ $formatDate($schoolYear->created_at) }}</dd></div>
                    <div><dt>Người cập nhật gần nhất</dt><dd>{{ $formatUser($logSummary['updated'] ?? null) }}</dd></div>
                    <div><dt>Ngày cập nhật</dt><dd>{{ $formatDate($schoolYear->updated_at) }}</dd></div>
                    <div><dt>Người kích hoạt</dt><dd>{{ $formatUser($logSummary['activated'] ?? null) }}</dd></div>
                    <div><dt>Ngày kích hoạt</dt><dd>{{ $formatDate(($logSummary['activated'] ?? null)?->created_at) }}</dd></div>
                    <div><dt>Người lưu trữ</dt><dd>{{ $formatUser($logSummary['archived'] ?? null) }}</dd></div>
                    <div><dt>Ngày lưu trữ</dt><dd>{{ $schoolYear->archived_at ? $schoolYear->archived_at->format('d/m/Y H:i') : $formatDate(($logSummary['archived'] ?? null)?->created_at) }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    <div class="card p-4 shadow-sm mt-3">
        <h6 class="card-accent-title mb-3">Thống kê dữ liệu nhanh</h6>
        <div class="school-year-data-grid compact">
            @foreach($dataCards as $card)
                <a href="{{ $card['url'] }}" class="school-year-data-card">
                    <span class="school-year-data-icon"><i class="bi {{ $card['icon'] }}"></i></span>
                    <span>
                        <strong>{{ number_format($card['count'], 0, ',', '.') }}</strong>
                        <span>{{ $card['label'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
@endsection
