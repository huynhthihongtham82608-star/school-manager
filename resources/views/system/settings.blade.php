@extends('layouts.app')
@section('title', 'Cài đặt hệ thống')

@section('content')
<x-page-header
    title="Cài đặt hệ thống"
    subtitle="Thiết lập thông tin nhận diện, liên hệ và cấu hình hiển thị chung của nhà trường."
>
    <button class="btn btn-primary" type="submit" form="system-settings-form">
        <i class="bi bi-save me-1"></i>Lưu cài đặt
    </button>
</x-page-header>

<form id="system-settings-form" method="POST" action="{{ route('system.settings.update') }}" enctype="multipart/form-data" class="card shadow-sm p-4">
    @csrf
    @method('PUT')

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Tên trường</label>
            <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $setting->school_name) }}" required>
            @error('school_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tên viết tắt</label>
            <input type="text" name="short_name" class="form-control" value="{{ old('short_name', $setting->short_name) }}">
            @error('short_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Năm học hiện hành</label>
            <select name="default_school_year_id" class="form-select">
                <option value="">Theo năm học đang hoạt động</option>
                @foreach($schoolYears as $year)
                    <option value="{{ $year->id }}" @selected(old('default_school_year_id', $setting->default_school_year_id) == $year->id)>
                        {{ $year->name }}
                    </option>
                @endforeach
            </select>
            @error('default_school_year_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Logo trường</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            @error('logo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            @if($setting->logoUrl())
                <div class="mt-2 d-flex align-items-center gap-2">
                    <img src="{{ $setting->logoUrl() }}" alt="Logo trường" style="width:48px;height:48px;object-fit:cover;border-radius:12px">
                    <span class="text-muted small">Logo hiện tại</span>
                </div>
            @endif
        </div>
        <div class="col-md-8">
            <label class="form-label">Địa chỉ</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $setting->address) }}">
            @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Số điện thoại</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $setting->phone) }}">
            @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Thư điện tử</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $setting->email) }}">
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Trang web</label>
            <input type="text" name="website" class="form-control" value="{{ old('website', $setting->website) }}">
            @error('website')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Hiệu trưởng</label>
            <input type="text" name="principal_name" class="form-control" value="{{ old('principal_name', $setting->principal_name) }}">
            @error('principal_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="form-actions mt-4">
        <button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu cài đặt</button>
    </div>
</form>
@endsection
