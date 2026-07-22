@extends('layouts.app')
@section('title', 'Nhật ký hoạt động')

@section('content')
<div class="audit-log-page">
    <x-page-header
        title="Nhật ký hoạt động"
        subtitle="Theo dõi các thao tác thay đổi dữ liệu của Admin và Giáo viên trong hệ thống."
    />

    <form method="GET" action="{{ route('audit-logs.index') }}" class="audit-filter-bar">
        <div class="audit-filter-search">
            <i class="bi bi-search"></i>
            <input type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm kiếm...">
        </div>

        <select name="user_id" class="form-select audit-filter-control">
            <option value="">Tất cả người dùng</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>
                    {{ $user->display_name }} ({{ $user->username }})
                </option>
            @endforeach
        </select>

        <select name="module" class="form-select audit-filter-control">
            <option value="">Tất cả module</option>
            @foreach($modules as $module)
                <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ $module }}</option>
            @endforeach
        </select>

        <select name="action" class="form-select audit-filter-control">
            <option value="">Tất cả hành động</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
            @endforeach
        </select>

        <div class="audit-date-group">
            <label class="audit-date-field">
                <span>Từ ngày</span>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </label>
            <label class="audit-date-field">
                <span>Đến ngày</span>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </label>
        </div>

        <button class="btn btn-primary audit-filter-submit">
            <i class="bi bi-funnel me-1"></i>Lọc
        </button>
        <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary audit-filter-reset">Đặt lại</a>
    </form>

    <div class="card audit-log-card">
        <div class="table-responsive audit-table-wrap">
            <table class="table table-hover align-middle audit-log-table" data-admin-table-skip>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Vai trò</th>
                        <th>Hành động</th>
                        <th>Module</th>
                        <th>Nội dung thay đổi</th>
                        <th>Địa chỉ IP</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap">{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $log->user->display_name ?? 'Hệ thống' }}</div>
                            <div class="text-muted small">{{ $log->user->username ?? '-' }}</div>
                        </td>
                        <td>{{ $log->roleLabel() }}</td>
                        <td>
                            <span class="audit-action-badge {{ $log->actionBadgeClass() }}">{{ $log->actionTypeLabel() }}</span>
                        </td>
                        <td class="fw-semibold">{{ $log->moduleLabel() }}</td>
                        <td class="audit-change-content">
                            {{ $log->changeContent() }}
                            @if($log->action)
                                <div class="audit-technical-action">{{ $log->action }}</div>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $log->ip_address ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state"><i class="bi bi-shield-check"></i>Chưa có nhật ký hoạt động.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(method_exists($logs, 'links'))
        <div class="mt-3">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
