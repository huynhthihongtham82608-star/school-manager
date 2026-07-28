@extends('layouts.app')
@section('title', 'Cài đặt hệ thống')

@section('content')
@php
    $currentLogoUrl = $setting->logoUrl();
    $selectedSchoolYearId = old('default_school_year_id', $setting->default_school_year_id);
    $selectedSchoolYear = $selectedSchoolYearId
        ? $schoolYears->firstWhere('id', $selectedSchoolYearId)
        : $schoolYears->firstWhere('is_active', true);
    $currentSchoolYearText = $selectedSchoolYear?->name ?: 'Theo năm học đang hoạt động';
@endphp

<x-page-header
    class="system-settings-page-header"
    title="Cài đặt hệ thống"
    subtitle="Thiết lập thông tin nhận diện, liên hệ và cấu hình hiển thị chung của nhà trường."
/>

<form id="system-settings-form" method="POST" action="{{ route('system.settings.update') }}" enctype="multipart/form-data" class="home-content-form">
    @csrf
    @method('PUT')

    <section class="home-content-card">
        <div class="home-content-section-title">
            <h5>Thông tin nhận diện thương hiệu</h5>
            <p>Quản lý tên trường, logo và thông tin điều hành hiển thị xuyên suốt hệ thống.</p>
        </div>

        <div class="home-content-grid">
            <div class="home-content-field">
                <label for="school_name" class="form-label">Tên trường</label>
                <input id="school_name" type="text" name="school_name" class="form-control" value="{{ old('school_name', $setting->school_name) }}" required>
                @error('school_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field">
                <label for="short_name" class="form-label">Tên viết tắt</label>
                <input id="short_name" type="text" name="short_name" class="form-control" value="{{ old('short_name', $setting->short_name) }}" placeholder="TH">
                @error('short_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field">
                <label for="principal_name" class="form-label">Hiệu trưởng</label>
                <input id="principal_name" type="text" name="principal_name" class="form-control" value="{{ old('principal_name', $setting->principal_name) }}">
                @error('principal_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field full">
                <label class="form-label">Năm học hiện hành</label>
                <input type="hidden" name="default_school_year_id" value="{{ $selectedSchoolYearId }}">
                <div class="system-current-year-lock">
                    <i class="bi bi-lock"></i>
                    <span>{{ $currentSchoolYearText }}</span>
                </div>
                <div class="form-text text-xs text-gray-500 mt-1">Theo năm học đang hoạt động.</div>
                @error('default_school_year_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field full">
                <label for="logo" class="form-label">Logo trường</label>
                <input id="logo" type="file" name="logo" class="form-control" accept="image/*" data-logo-input data-logo-preview="#system-logo-preview" data-logo-empty="#system-logo-empty">
                <div class="form-text text-xs text-gray-500 mt-1">Hỗ trợ logo định dạng JPG, PNG, WEBP. Tối đa 5MB.</div>
                <div class="system-logo-preview {{ $currentLogoUrl ? '' : 'is-empty' }}" id="system-logo-preview" style="width:64px;height:64px;">
                    @if($currentLogoUrl)
                        <img src="{{ $currentLogoUrl }}" alt="Logo trường" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <span id="system-logo-empty">{{ $setting->short_name ?: 'TH' }}</span>
                    @endif
                </div>
                <span class="system-logo-caption">Logo hiện tại</span>
                @error('logo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="home-content-card">
        <div class="home-content-section-title">
            <h5>Thông tin liên hệ & Địa chỉ</h5>
            <p>Cấu hình các kênh liên hệ chính thức hiển thị trên cổng thông tin nhà trường.</p>
        </div>

        <div class="home-content-grid">
            <div class="home-content-field">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input id="phone" type="text" name="phone" class="form-control" value="{{ old('phone', $setting->phone) }}" placeholder="038 608 2608">
                @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field">
                <label for="email" class="form-label">Thư điện tử</label>
                <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $setting->email) }}" placeholder="thpt@gmail.com">
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field full">
                <label for="website" class="form-label">Trang web</label>
                <input id="website" type="text" name="website" class="form-control" value="{{ old('website', $setting->website) }}" placeholder="https://...">
                @error('website')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field full">
                <label for="address" class="form-label">Địa chỉ</label>
                <textarea id="address" name="address" class="form-control" rows="3" placeholder="Lê Bình, Cái Răng, Cần Thơ">{{ old('address', $setting->address) }}</textarea>
                @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <div class="home-content-actions">
        <a href="{{ route('system.settings.edit') }}" class="btn btn-secondary">Hủy thay đổi</a>
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-save me-2"></i>Lưu cấu hình hệ thống
        </button>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-logo-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            const preview = document.querySelector(input.dataset.logoPreview);

            if (!file || !preview) {
                return;
            }

            const imageUrl = URL.createObjectURL(file);
            preview.classList.remove('is-empty');
            preview.innerHTML = '';

            const image = document.createElement('img');
            image.src = imageUrl;
            image.alt = 'Logo trường vừa chọn';
            image.onload = () => URL.revokeObjectURL(imageUrl);
            preview.appendChild(image);
        });
    });
});
</script>
@endpush
@endsection
