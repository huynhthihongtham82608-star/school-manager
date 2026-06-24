@extends('layouts.app')
@section('title', 'AI hỗ trợ học tập')

@section('content')
@php
    $canRunAnalysis = auth()->user()->isAdmin() || auth()->user()->isStaff() || auth()->user()->isHomeroom();
    $tabs = [
        'analysis' => ['label' => 'Phân tích', 'icon' => 'bi-bar-chart-line', 'url' => route('ai.run.form')],
        'alerts' => ['label' => 'Cảnh báo', 'icon' => 'bi-exclamation-triangle', 'url' => route('ai.alerts')],
        'reports' => ['label' => 'Nhận xét', 'icon' => 'bi-pencil-square', 'url' => route('ai.reports')],
    ];
@endphp

<div class="ai-tabs">
    @foreach($tabs as $key => $tab)
        @if($key !== 'analysis' || $canRunAnalysis)
            <form method="GET" action="{{ $tab['url'] }}">
                <button type="submit" class="ai-tab-button {{ $activeTab === $key ? 'active' : '' }}">
                    <i class="bi {{ $tab['icon'] }}"></i>
                    <span>{{ $tab['label'] }}</span>
                </button>
            </form>
        @endif
    @endforeach
</div>

@if($activeTab === 'analysis')
    @if($canRunAnalysis)
        <div class="card ai-panel">
            <div class="card-header">Phân tích dữ liệu học tập</div>
            <div class="card-body">
                <form method="POST" action="{{ route('ai.run') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Lớp</label>
                        <select class="form-select" name="class_id" required>
                            <option value="">-- Chọn lớp --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Học kỳ</label>
                        <select class="form-select" name="semester_id" required>
                            <option value="">-- Chọn học kỳ --</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}">{{ $semester->name }} ({{ $semester->schoolYear->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 align-self-end">
                        <button class="btn btn-primary w-100"><i class="bi bi-cpu me-1"></i>Chạy phân tích</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="card">
            <div class="empty-state"><i class="bi bi-lock"></i>Bạn không có quyền chạy phân tích AI.</div>
        </div>
    @endif
@elseif($activeTab === 'alerts')
    <div class="ai-result-grid">
        @forelse($alerts as $alert)
            <article class="ai-result-card">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    @php($map = ['low' => 'secondary', 'medium' => 'warning', 'high' => 'danger'])
                    <span class="badge bg-{{ $map[$alert->risk_level] ?? 'secondary' }}">{{ strtoupper($alert->risk_level) }}</span>
                    <span class="text-muted small">{{ $alert->created_at }}</span>
                </div>
                <h6>{{ $alert->student?->name ?? 'Học sinh' }}</h6>
                <p>{{ $alert->message }}</p>
                <div class="ai-result-meta">
                    <span>{{ $alert->classRoom?->name ?? 'Chưa có lớp' }}</span>
                    <span>{{ $alert->semester?->name ?? 'Chưa có học kỳ' }}</span>
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state"><i class="bi bi-shield-check"></i>Chưa có cảnh báo.</div>
            </div>
        @endforelse
    </div>
@else
    <div class="ai-result-grid">
        @forelse($reports as $report)
            <article class="ai-result-card ai-report-card">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="badge bg-info">{{ $report->trend ?: 'Nhận xét' }}</span>
                    <span class="text-muted small">{{ $report->created_at }}</span>
                </div>
                <h6>{{ $report->student?->name ?? 'Học sinh' }}</h6>
                <p style="white-space: pre-wrap;">{{ $report->summary }}</p>
                <div class="ai-result-meta">
                    <span>{{ $report->semester?->name ?? 'Chưa có học kỳ' }}</span>
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state"><i class="bi bi-file-earmark-text"></i>Chưa có nhận xét.</div>
            </div>
        @endforelse
    </div>
@endif
@endsection
