@extends('layouts.app')
@section('title', 'Phòng học')

@section('content')
@php
    $typeFilters = ['all' => 'Tất cả loại phòng'] + \App\Models\Room::TYPES;
    $statusFilters = ['all' => 'Tất cả trạng thái'] + \App\Models\Room::STATUSES;
@endphp

<div class="page-heading">
    <div>
        <h5>Phòng học</h5>
        <div class="text-muted">Quản lý dữ liệu nền phòng học dùng cho thời khóa biểu.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        @unless($readOnly)
            <a class="btn btn-primary" href="{{ route('rooms.create') }}"><i class="bi bi-plus-lg me-1"></i>Thêm phòng</a>
        @endunless
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Lọc loại phòng" aria-label="Lọc loại phòng">
                <i class="bi bi-funnel"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($typeFilters as $value => $label)
                    <li>
                        <a class="dropdown-item {{ $selectedType === $value ? 'active' : '' }}" href="{{ route('rooms.index', array_filter(['type' => $value === 'all' ? null : $value, 'status' => $selectedStatus === 'all' ? null : $selectedStatus])) }}">
                            @if($selectedType === $value)
                                <i class="bi bi-check2 me-2"></i>
                            @else
                                <i class="bi bi-circle me-2 text-muted"></i>
                            @endif
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="dropdown">
            <button type="button" class="content-action-btn icon-only dropdown-toggle-clean" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Lọc trạng thái" aria-label="Lọc trạng thái">
                <i class="bi bi-sliders"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($statusFilters as $value => $label)
                    <li>
                        <a class="dropdown-item {{ $selectedStatus === $value ? 'active' : '' }}" href="{{ route('rooms.index', array_filter(['type' => $selectedType === 'all' ? null : $selectedType, 'status' => $value === 'all' ? null : $value])) }}">
                            @if($selectedStatus === $value)
                                <i class="bi bi-check2 me-2"></i>
                            @else
                                <i class="bi bi-circle me-2 text-muted"></i>
                            @endif
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

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
                    <td class="text-end">
                        <div class="content-action-group justify-content-end">
                            @unless($readOnly)
                                <a href="{{ route('rooms.edit', $room) }}" class="content-action-btn icon-only edit" title="Sửa" aria-label="Sửa" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                </a>
                                @if($room->canDelete())
                                    <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="content-action-btn icon-only delete" title="Xóa" aria-label="Xóa" data-bs-toggle="tooltip">
                                            <i class="bi bi-trash"></i><span class="visually-hidden">Xóa</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">Đã có TKB</span>
                                @endif
                            @else
                                <span class="text-muted small">Chỉ xem</span>
                            @endunless
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
@endsection
