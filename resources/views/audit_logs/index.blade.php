@extends('layouts.app')
@section('title', 'Nhật ký hoạt động')

@section('content')
<div class="page-heading">
    <div>
        <h5>Nhật ký hoạt động</h5>
        <div class="text-muted">Theo dõi các thao tác quan trọng trong hệ thống.</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người dùng</th>
                    <th>Hành động</th>
                    <th>Đối tượng</th>
                    <th>Mô tả</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="fw-semibold">{{ $log->user->display_name ?? 'Hệ thống' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ class_basename($log->entity_type) }} {{ $log->entity_id }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><i class="bi bi-shield-check"></i>Chưa có nhật ký hoạt động.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($logs, 'links'))
    <div class="mt-3">{{ $logs->links() }}</div>
@endif
@endsection
