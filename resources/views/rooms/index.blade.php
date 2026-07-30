@extends('layouts.app')
@section('title', 'Phòng học')

@section('content')
@php
    $typeFilters = ['all' => 'Tất cả loại phòng'] + \App\Models\Room::TYPES;
    $statusFilters = ['all' => 'Tất cả trạng thái'] + \App\Models\Room::STATUSES;
@endphp

<x-page-header
    title="Quản lý phòng học"
    subtitle="Thiết lập cơ sở vật chất, loại phòng, sức chứa và trạng thái sử dụng cho thời khóa biểu."
>
    <div class="d-flex align-items-center gap-2">
        @unless($readOnly)
            <a class="btn btn-primary" href="{{ route('rooms.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm phòng</a>
        @endunless
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Bộ lọc" aria-label="Bộ lọc">
                <i class="bi bi-funnel"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 320px;">
                <form method="GET" action="{{ route('rooms.index') }}" class="d-grid gap-3">
                    <div>
                        <label class="form-label small">Loại phòng</label>
                        <select name="type" class="form-select">
                            @foreach($typeFilters as $value => $label)
                                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small">Trạng thái</label>
                        <select name="status" class="form-select">
                            @foreach($statusFilters as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">Xóa lọc</a>
                        <button class="btn btn-primary">Áp dụng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tên phòng</th>
                    <th>Loại phòng</th>
                    <th>Sức chứa</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                    <th>Lớp cố định</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($rooms as $room)
                <tr>
                    <td class="fw-semibold">{{ $room->name }}</td>
                    <td>{{ $room->typeLabel() }}</td>
                    <td>{{ $room->capacity }}</td>
                    <td><span class="badge {{ $room->statusBadgeClass() }}">{{ $room->statusLabel() }}</span></td>
                    <td class="text-muted">{{ \Illuminate\Support\Str::limit($room->note, 80) ?: '-' }}</td>
                    <td>{{ $room->fixedClass?->name ?? '-' }}</td>
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            @unless($readOnly)
                                <a href="{{ route('rooms.edit', $room) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                </a>
                            @endunless
                            <div class="dropdown">
                                <button type="button" class="content-action-btn icon-only dropdown-toggle-clean more" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Thao tác" aria-label="Thao tác">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end content-action-menu">
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#roomDetailModal{{ $room->id }}">
                                            <i class="bi bi-eye"></i>Xem chi tiết
                                        </button>
                                    </li>
                                    @unless($readOnly)
                                        @if($room->canDelete())
                                            <li>
                                                <form action="{{ route('rooms.destroy', $room) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng học này? Hành động này không thể hoàn tác.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item danger">
                                                        <i class="bi bi-trash"></i>Xóa bỏ
                                                    </button>
                                                </form>
                                            </li>
                                        @else
                                            <li><span class="dropdown-item text-muted">Đã có TKB</span></li>
                                        @endif
                                    @endunless
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty-state"><i class="bi bi-door-open"></i>Chưa có phòng học.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($rooms as $room)
    <div class="modal fade content-modal room-detail-modal" id="roomDetailModal{{ $room->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="room-detail-header">
                        <div class="room-detail-identity">
                            <h5 class="modal-title">{{ \Illuminate\Support\Str::upper($room->name) }}</h5>
                            <div>Cơ sở vật chất trường THPT</div>
                        </div>
                        <span class="badge {{ $room->statusBadgeClass() }}">{{ $room->statusLabel() }}</span>
                    </div>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <section class="room-detail-section">
                        <h6>Thông số và phân loại phòng</h6>
                        <div class="room-detail-grid">
                            <article>
                                <span>Loại phòng</span>
                                <strong>{{ $room->typeLabel() }}</strong>
                            </article>
                            <article>
                                <span>Sức chứa</span>
                                <strong>{{ $room->capacity }} học sinh</strong>
                            </article>
                        </div>
                    </section>

                    <section class="room-detail-section mt-3">
                        <h6>Ghi chú thông tin bổ trợ</h6>
                        <div class="room-detail-note">
                            {{ trim((string) $room->note) !== '' ? $room->note : '-' }}
                        </div>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng hồ sơ phòng học</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
