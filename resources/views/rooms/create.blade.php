@extends('layouts.app')
@section('title', 'Thêm phòng học')

@section('content')
<form method="POST" action="{{ route('rooms.store') }}" class="card p-4 shadow-sm" data-room-form data-academic-modal-size="xl">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Tên phòng</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Phòng 101" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Loại phòng</label>
            <select name="type" class="form-select" data-room-type required>
                <option value="{{ \App\Models\Room::TYPE_STANDARD }}" @selected(old('type', \App\Models\Room::TYPE_STANDARD) === \App\Models\Room::TYPE_STANDARD)>Phòng học thường</option>
                <option value="{{ \App\Models\Room::TYPE_COMPUTER }}" @selected(old('type') === \App\Models\Room::TYPE_COMPUTER)>Phòng máy tính</option>
                <option value="{{ \App\Models\Room::TYPE_LAB }}" @selected(old('type') === \App\Models\Room::TYPE_LAB)>Phòng thí nghiệm</option>
                <option value="{{ \App\Models\Room::TYPE_MULTIPURPOSE }}" @selected(old('type') === \App\Models\Room::TYPE_MULTIPURPOSE)>Sân thể chất</option>
            </select>
            @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 d-none" data-custom-room-type-wrap>
            <label class="form-label">Nhập loại phòng</label>
            <input type="text" name="custom_type" class="form-control" value="{{ old('custom_type') }}">
            @error('custom_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Sức chứa</label>
            <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 45) }}" min="1" max="100" required>
            @error('capacity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Lớp học cố định</label>
            <select name="fixed_class_id" class="form-select">
                <option value="">Chưa gán lớp cố định</option>
                @foreach(($classes ?? collect()) as $class)
                    <option value="{{ $class->id }}" @selected(old('fixed_class_id') == $class->id)>
                        {{ $class->name }}{{ $class->schoolYear ? ' - ' . $class->schoolYear->name : '' }}
                    </option>
                @endforeach
            </select>
            @error('fixed_class_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Trạng thái</label>
            <input type="hidden" name="status" value="{{ old('status', \App\Models\Room::STATUS_ACTIVE) }}">
            <div>
                <span class="academic-form-badge success"><i class="bi bi-check-circle"></i>Hoạt động</span>
            </div>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-12">
            <label class="form-label">Ghi chú</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
            @error('note')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="form-actions mt-4">
        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

@include('rooms.partials.type-script')
@endsection
