@extends('layouts.app')
@section('title', 'Sửa phòng học')

@section('content')
<form method="POST" action="{{ route('rooms.update', $room) }}" class="card p-4 shadow-sm" data-room-form>
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Tên phòng</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $room->name) }}" required @disabled($isUsed)>
            @if($isUsed)
                <input type="hidden" name="name" value="{{ $room->name }}">
                <div class="form-text">Phòng đã phát sinh thời khóa biểu nên không thể sửa tên.</div>
            @endif
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Loại phòng</label>
            <select name="type" class="form-select" data-room-type required>
                @foreach(\App\Models\Room::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $room->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" data-custom-room-type-wrap>
            <label class="form-label">Nhập loại phòng</label>
            <input type="text" name="custom_type" class="form-control" value="{{ old('custom_type', $room->custom_type) }}">
            @error('custom_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Sức chứa</label>
            <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $room->capacity) }}" min="1" max="100" required>
            @error('capacity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                @foreach(\App\Models\Room::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $room->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-12">
            <label class="form-label">Ghi chú</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note', $room->note) }}</textarea>
            @error('note')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="form-actions mt-4">
        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Cập nhật</button>
    </div>
</form>

@include('rooms.partials.type-script')
@endsection
