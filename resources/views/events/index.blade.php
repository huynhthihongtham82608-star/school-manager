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
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($events as $event)
                @php
                    $detailId = 'event-detail-' . $loop->index;
                    $description = $event->description ?: 'Chưa có mô tả.';
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $event->title }}</td>
                    <td>{{ optional($event->starts_at)->format('d/m/Y H:i') ?: 'Đang cập nhật' }}</td>
                    <td>{{ $event->location ?: 'Đang cập nhật' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($description, 120, '...') }}</td>
                    <td>
                        <div class="content-action-group justify-content-end">
                            <button type="button" class="content-action-btn icon-only detail" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}" title="Xem chi tiết" aria-label="Xem chi tiết">
                                <i class="bi bi-eye"></i><span class="visually-hidden">Xem chi tiết</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><div class="empty-state"><i class="bi bi-calendar-x"></i>Chưa có sự kiện.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($events as $event)
    @php
        $detailId = 'event-detail-' . $loop->index;
        $description = $event->description ?: 'Chưa có mô tả.';
    @endphp
    <div class="modal fade content-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="modal-kicker">Sự kiện</div>
                        <h5 class="modal-title">{{ $event->title }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <dl class="content-detail-list">
                        <div>
                            <dt>Thời gian</dt>
                            <dd>
                                {{ optional($event->starts_at)->format('d/m/Y H:i') ?: 'Đang cập nhật' }}
                                @if($event->ends_at)
                                    - {{ $event->ends_at->format('d/m/Y H:i') }}
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Địa điểm</dt>
                            <dd>{{ $event->location ?: 'Đang cập nhật' }}</dd>
                        </div>
                        <div>
                            <dt>Mô tả đầy đủ</dt>
                            <dd class="content-full-text">{!! nl2br(e($description)) !!}</dd>
                        </div>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@if(method_exists($events, 'links'))
    <div class="mt-3">{{ $events->links() }}</div>
@endif
@endsection
