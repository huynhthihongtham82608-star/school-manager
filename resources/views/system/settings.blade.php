@extends('layouts.app')
@section('title', 'Thông tin & Diện mạo trường')

@section('content')
@php
    $homePageTablesReady = $homePageTablesReady ?? false;
    $homePageContents = $homePageContents ?? collect();
    $banner = $homePageContents->get('banner');
    $about = $homePageContents->get('about');
    $currentLogoUrl = $setting->logoUrl();
    $selectedSchoolYearId = old('default_school_year_id', $setting->default_school_year_id);
    $selectedSchoolYear = $selectedSchoolYearId
        ? $schoolYears->firstWhere('id', $selectedSchoolYearId)
        : $schoolYears->firstWhere('is_active', true);
    $currentSchoolYearText = $selectedSchoolYear?->name ?: 'Theo năm học đang hoạt động';
    $bannerImageUrl = old('banner_image_url', $banner->image_url ?? '');
    $bannerImagePreviewUrl = $bannerImageUrl
        ? (\Illuminate\Support\Str::startsWith($bannerImageUrl, ['http://', 'https://']) ? $bannerImageUrl : asset(ltrim($bannerImageUrl, '/')))
        : '';
@endphp

<x-page-header
    class="system-settings-page-header"
    title="🌐 Thông tin & Diện mạo trường"
    subtitle="Quản lý định danh, liên hệ, logo, banner và nội dung giới thiệu hiển thị xuyên suốt hệ thống."
/>

@unless($homePageTablesReady)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm font-normal rounded-xl px-4 py-3 mb-6 text-left">
        Chưa có bảng home_page_contents. Vui lòng chạy migration trước khi lưu banner và giới thiệu.
    </div>
@endunless

<form id="school-appearance-form" method="POST" action="{{ route('system.settings.update') }}" enctype="multipart/form-data" class="w-full font-sans text-left text-gray-700 font-normal">
    @csrf
    @method('PUT')

    <section class="bg-white border border-orange-100 p-6 rounded-xl shadow-2xs mb-6 text-left">
        <div class="w-full text-left items-start flex flex-col gap-1 mb-5 px-1">
            <h5 class="text-base font-semibold text-gray-900 !text-left mb-0">Định danh & Liên hệ</h5>
            <p class="text-sm font-normal text-orange-700/60 mt-1 !text-left mb-0">Tên trường, logo, email, số điện thoại và địa chỉ dùng chung trên toàn hệ thống.</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_260px] gap-5 items-start">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="home-content-field text-left">
                    <label for="school_name" class="form-label text-sm font-normal text-gray-700 text-left">Tên trường</label>
                    <input id="school_name" type="text" name="school_name" class="form-control text-sm font-normal text-gray-700 text-left" value="{{ old('school_name', $setting->school_name) }}" required>
                    @error('school_name')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="short_name" class="form-label text-sm font-normal text-gray-700 text-left">Tên viết tắt</label>
                    <input id="short_name" type="text" name="short_name" class="form-control text-sm font-normal text-gray-700 text-left" value="{{ old('short_name', $setting->short_name) }}" placeholder="THPT">
                    @error('short_name')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="principal_name" class="form-label text-sm font-normal text-gray-700 text-left">Hiệu trưởng</label>
                    <input id="principal_name" type="text" name="principal_name" class="form-control text-sm font-normal text-gray-700 text-left" value="{{ old('principal_name', $setting->principal_name) }}" placeholder="Nhập họ tên hiệu trưởng">
                    @error('principal_name')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label class="form-label text-sm font-normal text-gray-700 text-left">Năm học hiện hành</label>
                    <input type="hidden" name="default_school_year_id" value="{{ $selectedSchoolYearId }}">
                    <div class="w-full text-left text-sm font-normal text-gray-700 bg-orange-50/50 border border-orange-100 rounded-md px-3 py-2 flex items-center gap-2">
                        <i class="bi bi-lock text-orange-600"></i>
                        <span class="truncate">{{ $currentSchoolYearText }}</span>
                    </div>
                    @error('default_school_year_id')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="email" class="form-label text-sm font-normal text-gray-700 text-left">Thư điện tử (Email)</label>
                    <input id="email" type="email" name="email" class="form-control text-sm font-normal text-gray-700 text-left" value="{{ old('email', $setting->email) }}" placeholder="thpt@example.edu.vn">
                    @error('email')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="phone" class="form-label text-sm font-normal text-gray-700 text-left">Số điện thoại</label>
                    <input id="phone" type="text" name="phone" class="form-control text-sm font-normal text-gray-700 text-left" value="{{ old('phone', $setting->phone) }}" placeholder="038 608 2608">
                    @error('phone')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="website" class="form-label text-sm font-normal text-gray-700 text-left">Trang web</label>
                    <input id="website" type="text" name="website" class="form-control text-sm font-normal text-gray-700 text-left" value="{{ old('website', $setting->website) }}" placeholder="https://...">
                    @error('website')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="address" class="form-label text-sm font-normal text-gray-700 text-left">Địa chỉ</label>
                    <textarea id="address" name="address" class="form-control text-sm font-normal text-gray-700 text-left" rows="3" placeholder="Nhập địa chỉ nhà trường">{{ old('address', $setting->address) }}</textarea>
                    @error('address')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="bg-orange-50/30 border border-orange-100 rounded-xl p-4 text-left">
                <label for="logo" class="form-label text-sm font-normal text-gray-700 text-left">Logo trường</label>
                <div class="system-logo-preview-wrap justify-items-start text-left">
                    <div class="system-logo-preview {{ $currentLogoUrl ? '' : 'is-empty' }}" id="system-logo-preview" style="width:112px !important;height:112px !important;max-width:112px !important;max-height:112px !important;">
                        @if($currentLogoUrl)
                            <img src="{{ $currentLogoUrl }}" alt="Logo trường">
                        @else
                            <span id="system-logo-empty">{{ $setting->short_name ?: 'TH' }}</span>
                        @endif
                    </div>
                    <span class="system-logo-caption text-xs font-normal text-gray-500 text-left mt-2">Logo hiện tại</span>
                </div>
                <input id="logo" type="file" name="logo" class="form-control system-logo-input text-sm font-normal text-gray-700 text-left mt-3" accept="image/*" data-logo-input data-logo-preview="#system-logo-preview">
                <div class="flex flex-wrap items-center gap-2 mt-3 text-left">
                    <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">JPG</span>
                    <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">PNG</span>
                    <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">WEBP</span>
                </div>
                @error('logo')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="bg-white border border-orange-100 p-6 rounded-xl shadow-2xs mb-6 text-left">
        <div class="w-full text-left items-start flex flex-col gap-1 mb-5 px-1">
            <h5 class="text-base font-semibold text-gray-900 !text-left mb-0">Banner & Giới thiệu</h5>
            <p class="text-sm font-normal text-orange-700/60 mt-1 !text-left mb-0">Ảnh banner, tiêu đề, lời chào và nội dung giới thiệu trên cổng thông tin nhà trường.</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-5 items-start">
            <div class="grid grid-cols-1 gap-4">
                <div class="home-content-field text-left">
                    <label for="banner_title" class="form-label text-sm font-normal text-gray-700 text-left">Tiêu đề banner</label>
                    <input id="banner_title" name="banner_title" class="form-control text-sm font-normal text-gray-700 text-left" value="{{ old('banner_title', $banner->title ?? '') }}" placeholder="Chào mừng đến với Trường Trung học Phổ thông">
                    @error('banner_title')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="banner_welcome" class="form-label text-sm font-normal text-gray-700 text-left">Lời chào</label>
                    <textarea id="banner_welcome" name="banner_welcome" rows="2" class="form-control text-sm font-normal text-gray-700 text-left" placeholder="Môi trường học tập hiện đại - Kỷ cương - Sáng tạo">{{ old('banner_welcome', data_get($banner, 'extra.subtitle')) }}</textarea>
                    @error('banner_welcome')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="banner_description" class="form-label text-sm font-normal text-gray-700 text-left">Mô tả banner</label>
                    <textarea id="banner_description" name="banner_description" rows="3" class="form-control text-sm font-normal text-gray-700 text-left" placeholder="Nhập mô tả ngắn hiển thị dưới tiêu đề banner">{{ old('banner_description', $banner->content ?? '') }}</textarea>
                    @error('banner_description')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="intro_title" class="form-label text-sm font-normal text-gray-700 text-left">Tiêu đề giới thiệu</label>
                    <input id="intro_title" name="intro_title" class="form-control text-sm font-normal text-gray-700 text-left" value="{{ old('intro_title', $about->title ?? '') }}" placeholder="Giới thiệu nhà trường">
                    @error('intro_title')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>

                <div class="home-content-field text-left">
                    <label for="intro_content" class="form-label text-sm font-normal text-gray-700 text-left">Giới thiệu trường</label>
                    <textarea id="intro_content" name="intro_content" rows="6" class="form-control home-content-textarea-large text-sm font-normal text-gray-700 text-left" placeholder="Nhập nội dung giới thiệu trường">{{ old('intro_content', $about->content ?? '') }}</textarea>
                    @error('intro_content')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="bg-orange-50/30 border border-orange-100 rounded-xl p-4 text-left">
                <label for="banner_image_file" class="form-label text-sm font-normal text-gray-700 text-left">Banner ảnh</label>
                <input type="hidden" name="banner_image_url" value="{{ $bannerImageUrl }}">
                <input id="banner_image_file" type="file" name="banner_image_file" accept="image/*" class="form-control text-sm font-normal text-gray-700 text-left" data-banner-file>
                <div class="flex flex-wrap items-center gap-2 mt-3 text-left">
                    <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">JPG</span>
                    <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">PNG</span>
                    <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">WEBP</span>
                    <span class="bg-gray-50 text-gray-500 border border-gray-200 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">Tối đa 20MB</span>
                </div>
                <div class="home-banner-preview {{ $bannerImagePreviewUrl ? '' : 'is-empty' }} mt-3" data-banner-preview>
                    @if($bannerImagePreviewUrl)
                        <img src="{{ $bannerImagePreviewUrl }}" alt="Banner hiện tại">
                    @else
                        <span><i class="bi bi-image"></i>Chưa có ảnh banner</span>
                    @endif
                </div>
                @error('banner_image_file')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <div class="home-content-actions flex flex-wrap items-center justify-end gap-2 text-left">
        <a href="{{ route('system.settings.edit') }}" class="btn btn-secondary text-sm font-normal">Hủy thay đổi</a>
        <button class="btn btn-primary text-sm font-normal" type="submit">
            <i class="bi bi-save me-2"></i>Lưu thông tin & diện mạo
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

    const bannerInput = document.querySelector('[data-banner-file]');
    const bannerPreview = document.querySelector('[data-banner-preview]');

    if (bannerInput && bannerPreview) {
        bannerInput.addEventListener('change', () => {
            const file = bannerInput.files && bannerInput.files[0];

            if (!file) {
                return;
            }

            const imageUrl = URL.createObjectURL(file);
            bannerPreview.classList.remove('is-empty');
            bannerPreview.innerHTML = '';

            const image = document.createElement('img');
            image.src = imageUrl;
            image.alt = 'Banner vừa chọn';
            image.onload = () => URL.revokeObjectURL(imageUrl);
            bannerPreview.appendChild(image);
        });
    }
});
</script>
@endpush
@endsection
