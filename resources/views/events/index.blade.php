@extends('layouts.app')
@section('title', 'Sá»± kiá»‡n')

@section('content')
<div class="page-heading">
    <div>
        <h5>Sá»± kiá»‡n nhÃ  trÆ°á»ng</h5>
        <div class="text-muted">Danh sÃ¡ch hoáº¡t Ä‘á»™ng vÃ  sá»± kiá»‡n Ä‘Æ°á»£c cÃ´ng bá»‘.</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Sá»± kiá»‡n</th>
                    <th>Thá»i gian</th>
                    <th>Äá»‹a Ä‘iá»ƒm</th>
                    <th>MÃ´ táº£</th>
                    <th class="text-end">Thao tÃ¡c</th>
                </tr>
            </thead>
            <tbody>
            @forelse($events as $event)
                @php
                    $detailId = 'event-detail-' . $loop->index;
                    $description = $event->description ?: 'ChÆ°a cÃ³ mÃ´ táº£.';
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $event->title }}</td>
                    <td>{{ optional($event->starts_at)->format('d/m/Y H:i') ?: 'Äang cáº­p nháº­t' }}</td>
                    <td>{{ $event->location ?: 'Äang cáº­p nháº­t' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($description, 120, '...') }}</td>
                    <td>
                        <div class="content-action-group justify-content-end">
                            <button type="button" class="content-action-btn icon-only detail" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}" title="Xem chi tiáº¿t" aria-label="Xem chi tiáº¿t">
                                <i class="bi bi-eye"></i><span class="visually-hidden">Xem chi tiáº¿t</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><div class="empty-state"><i class="bi bi-calendar-x"></i>ChÆ°a cÃ³ sá»± kiá»‡n.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($events as $event)
    @php
        $detailId = 'event-detail-' . $loop->index;
        $description = $event->description ?: 'ChÆ°a cÃ³ mÃ´ táº£.';
    @endphp
    <div class="modal fade content-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="modal-kicker">Sá»± kiá»‡n</div>
                        <h5 class="modal-title">{{ $event->title }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ÄÃ³ng"></button>
                </div>
                <div class="modal-body">
                    <dl class="content-detail-list">
                        <div>
                            <dt>Thá»i gian</dt>
                            <dd>
                                {{ optional($event->starts_at)->format('d/m/Y H:i') ?: 'Äang cáº­p nháº­t' }}
                                @if($event->ends_at)
                                    - {{ $event->ends_at->format('d/m/Y H:i') }}
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Äá»‹a Ä‘iá»ƒm</dt>
                            <dd>{{ $event->location ?: 'Äang cáº­p nháº­t' }}</dd>
                        </div>
                        <div>
                            <dt>MÃ´ táº£ Ä‘áº§y Ä‘á»§</dt>
                            <dd class="content-full-text">{!! nl2br(e($description)) !!}</dd>
                        </div>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">ÄÃ³ng</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@if(method_exists($events, 'links'))
    <div class="mt-3">{{ $events->links() }}</div>
@endif
@endsection
