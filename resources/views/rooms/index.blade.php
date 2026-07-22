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
