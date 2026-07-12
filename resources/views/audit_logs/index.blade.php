@extends('layouts.app')
@section('title', 'Nhật ký hoạt động')

@section('content')
<div class="card">
    <div class="admin-table-tools">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="admin-table-tools-left flex-grow-1">
            <div class="admin-table-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm kiếm...">
            </div>
            <div class="admin-table-filters">
                <select name="user_id" class="form-select">
                    <option value="">Tất cả người dùng</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->display_name }} ({{ $user->username }})</option>
                    @endforeach
                </select>
                <select name="module" class="form-select">
                    <option value="">Tất cả module</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ class_basename($module) }}</option>
                    @endforeach
                </select>
                <select name="action" class="form-select">
                    <option value="">Tất cả hành động</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                <button class="btn btn-primary"><i class="bi bi-funnel me-2"></i>Lọc</button>
                <a href="{{ route('audit-logs.index') }}" class="btn btn-secondary">Đặt lại</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table" data-admin-table-skip>
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người thực hiện</th>
                    <th>Vai trò</th>
                    <th>Hành động</th>
                    <th>Module</th>
                    <th>Nội dung thay đổi</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="fw-semibold">{{ $log->user->display_name ?? 'Hệ thống' }}</td>
                    <td>{{ $log->user?->role ?? '-' }}</td>
                    <td>
                        <div class="fw-semibold">{{ $log->actionTypeLabel() }}</div>
                        <div class="text-muted small">{{ $log->action }}</div>
                    </td>
                    <td>{{ $log->moduleLabel() }}</td>
                    <td>{{ $log->description ?: '-' }}</td>
                    <td>{{ $log->ip_address ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-shield-check"></i>Chưa có nhật ký hoạt động.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($logs, 'links'))
    <div class="mt-3">{{ $logs->links() }}</div>
@endif
@endsection
