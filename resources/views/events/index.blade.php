@extends('layouts.app')
@section('title', 'Sự kiện')

@section('content')
<div class="page-heading">
    <div>
        <h5>Sự kiện nhà trường</h5>
        <div class="text-muted">Danh sách hoạt động và sự kiện được công bố.</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Sự kiện</th>
                    <th>Thời gian</th>
                    <th>Địa điểm</th>
                    <th>Mô tả</th>
                </tr>
            </thead>
            <tbody>
            @forelse($events as $event)
                <tr>
                    <td class="fw-semibold">{{ $event->title }}</td>
                    <td>{{ optional($event->starts_at)->format('d/m/Y H:i') ?: 'Đang cập nhật' }}</td>
                    <td>{{ $event->location ?: 'Đang cập nhật' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($event->description, 120) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4"><div class="empty-state"><i class="bi bi-calendar-x"></i>Chưa có sự kiện.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($events, 'links'))
    <div class="mt-3">{{ $events->links() }}</div>
@endif
@endsection
