@extends('layouts.app')
@section('title', 'Diện mạo trường')

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

<style>
    .appearance-card {
        background: #fff;
        border: 1px solid #fed7aa;
        border-radius: 12px;
        box-shadow: 0 1px 0 rgba(0, 0, 0, .03);
        color: #374151;
        font-family: Inter, Roboto, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        text-align: left;
    }

    #school-appearance-layout {
        width: 100% !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        gap: 1.5rem !important;
        text-align: left !important;
    }

    [data-appearance-left] {
        width: 65% !important;
        max-width: 65% !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 1.5rem !important;
        text-align: left !important;
    }

    [data-appearance-right] {
        width: 35% !important;
        max-width: 35% !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 1.5rem !important;
        text-align: left !important;
    }

    .appearance-field-grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 1rem !important;
        text-align: left !important;
    }

    .appearance-card-title {
        color: #111827;
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        text-align: left;
    }

    .appearance-card-subtitle {
        color: rgba(194, 65, 12, .72);
        font-size: .875rem;
        font-weight: 400;
        margin: .25rem 0 0;
        text-align: left;
    }

    .appearance-field label {
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        margin-bottom: .35rem;
        text-align: left;
    }

    .appearance-field .form-control {
        color: #374151;
        font-size: .875rem;
        font-weight: 400;
        text-align: left;
        border-color: #e5e7eb;
        border-radius: 8px;
    }

    .appearance-field .form-control:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 .22rem rgba(255, 237, 213, .82);
    }

    .appearance-preview-box {
        width: 100%;
        min-height: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: #9ca3af;
        font-size: .875rem;
        font-weight: 400;
        text-align: left;
        background: rgba(255, 247, 237, .42);
        border: 1px solid #fed7aa;
        border-radius: 12px;
    }

    .appearance-preview-box.logo {
        width: 140px;
        height: 140px;
        min-height: 140px;
    }

    .appearance-preview-box.banner {
        aspect-ratio: 16 / 9;
        min-height: 150px;
    }

    .appearance-preview-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    @media (max-width: 991.98px) {
        #school-appearance-layout {
            flex-direction: column !important;
        }

        [data-appearance-left],
        [data-appearance-right] {
            width: 100% !important;
            max-width: 100% !important;
        }

        .appearance-field-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<x-page-header
    class="system-settings-page-header"
    title="Diện mạo trường"
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

    <div id="school-appearance-layout" class="w-full flex flex-row gap-6 items-start justify-between text-left">
        <div class="w-[65%] flex flex-col gap-6" data-appearance-left>
            <section class="appearance-card bg-white border border-orange-100 p-5 rounded-xl shadow-2xs text-left w-full">
                <div class="w-full text-left mb-5">
                    <h2 class="appearance-card-title">Định danh & Liên hệ</h2>
                    <p class="appearance-card-subtitle">Thông tin nhận diện và kênh liên hệ chính thức của nhà trường.</p>
                </div>

                <div class="appearance-field-grid grid grid-cols-2 gap-4">
                    <div class="appearance-field text-left">
                        <label for="school_name" class="form-label">Tên trường</label>
                        <input id="school_name" type="text" name="school_name" class="form-control" value="{{ old('school_name', $setting->school_name) }}" required>
                        @error('school_name')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left">
                        <label for="short_name" class="form-label">Tên viết tắt</label>
                        <input id="short_name" type="text" name="short_name" class="form-control" value="{{ old('short_name', $setting->short_name) }}" placeholder="THPT">
                        @error('short_name')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left">
                        <label for="principal_name" class="form-label">Hiệu trưởng</label>
                        <input id="principal_name" type="text" name="principal_name" class="form-control" value="{{ old('principal_name', $setting->principal_name) }}" placeholder="Nhập họ tên hiệu trưởng">
                        @error('principal_name')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left">
                        <label class="form-label">Năm học hiện hành</label>
                        <input type="hidden" name="default_school_year_id" value="{{ $selectedSchoolYearId }}">
                        <div class="w-full text-left text-sm font-normal text-gray-700 bg-orange-50/50 border border-orange-100 rounded-md px-3 py-2 flex items-center gap-2">
                            <i class="bi bi-lock text-orange-600"></i>
                            <span>{{ $currentSchoolYearText }}</span>
                        </div>
                        @error('default_school_year_id')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left">
                        <label for="email" class="form-label">Thư điện tử (Email)</label>
                        <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $setting->email) }}" placeholder="thpt@example.edu.vn">
                        @error('email')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input id="phone" type="text" name="phone" class="form-control" value="{{ old('phone', $setting->phone) }}" placeholder="038 608 2608">
                        @error('phone')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left">
                        <label for="website" class="form-label">Trang web</label>
                        <input id="website" type="text" name="website" class="form-control" value="{{ old('website', $setting->website) }}" placeholder="https://...">
                        @error('website')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <textarea id="address" name="address" class="form-control" rows="3" placeholder="Nhập địa chỉ nhà trường">{{ old('address', $setting->address) }}</textarea>
                        @error('address')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="appearance-card bg-white border border-orange-100 p-5 rounded-xl shadow-2xs text-left w-full">
                <div class="w-full text-left mb-5">
                    <h2 class="appearance-card-title">Banner & Giới thiệu</h2>
                    <p class="appearance-card-subtitle">Nội dung chữ hiển thị ở trang chủ và phần giới thiệu nhà trường.</p>
                </div>

                <div class="appearance-field-grid grid grid-cols-2 gap-4">
                    <div class="appearance-field text-left">
                        <label for="banner_title" class="form-label">Tiêu đề banner</label>
                        <input id="banner_title" name="banner_title" class="form-control" value="{{ old('banner_title', $banner->title ?? '') }}" placeholder="Chào mừng đến với Trường Trung học Phổ thông">
                        @error('banner_title')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left">
                        <label for="intro_title" class="form-label">Tiêu đề giới thiệu</label>
                        <input id="intro_title" name="intro_title" class="form-control" value="{{ old('intro_title', $about->title ?? '') }}" placeholder="Giới thiệu nhà trường">
                        @error('intro_title')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left md:col-span-2">
                        <label for="banner_welcome" class="form-label">Lời chào</label>
                        <textarea id="banner_welcome" name="banner_welcome" rows="2" class="form-control" placeholder="Môi trường học tập hiện đại - Kỷ cương - Sáng tạo">{{ old('banner_welcome', data_get($banner, 'extra.subtitle')) }}</textarea>
                        @error('banner_welcome')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left md:col-span-2">
                        <label for="banner_description" class="form-label">Mô tả banner</label>
                        <textarea id="banner_description" name="banner_description" rows="3" class="form-control" placeholder="Nhập mô tả ngắn hiển thị dưới tiêu đề banner">{{ old('banner_description', $banner->content ?? '') }}</textarea>
                        @error('banner_description')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>

                    <div class="appearance-field text-left md:col-span-2">
                        <label for="intro_content" class="form-label">Nội dung giới thiệu trường</label>
                        <textarea id="intro_content" name="intro_content" rows="6" class="form-control" placeholder="Nhập nội dung giới thiệu trường">{{ old('intro_content', $about->content ?? '') }}</textarea>
                        @error('intro_content')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>
        </div>

        <aside class="w-[35%] flex flex-col gap-6" data-appearance-right>
            <section class="appearance-card bg-white border border-orange-100 p-5 rounded-xl shadow-2xs text-left w-full">
                <div class="w-full text-left mb-5">
                    <h2 class="appearance-card-title">Logo trường</h2>
                    <p class="appearance-card-subtitle">Ảnh nhận diện dùng trên header, đăng nhập và báo cáo.</p>
                </div>

                <div class="flex flex-col gap-4 text-left">
                    <div class="appearance-preview-box logo" id="system-logo-preview">
                        @if($currentLogoUrl)
                            <img src="{{ $currentLogoUrl }}" alt="Logo trường">
                        @else
                            <span id="system-logo-empty">{{ $setting->short_name ?: 'TH' }}</span>
                        @endif
                    </div>

                    <div class="appearance-field text-left">
                        <label for="logo" class="form-label">Tải logo mới</label>
                        <input id="logo" type="file" name="logo" class="form-control" accept="image/*" data-logo-input data-logo-preview="#system-logo-preview">
                        <div class="flex flex-wrap items-center gap-2 mt-3 text-left">
                            <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">JPG</span>
                            <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">PNG</span>
                            <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">WEBP</span>
                        </div>
                        @error('logo')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="appearance-card bg-white border border-orange-100 p-5 rounded-xl shadow-2xs text-left w-full">
                <div class="w-full text-left mb-5">
                    <h2 class="appearance-card-title">Banner ảnh</h2>
                    <p class="appearance-card-subtitle">Ảnh đại diện lớn của trang chủ, tối đa 20MB.</p>
                </div>

                <div class="flex flex-col gap-4 text-left">
                    <div class="appearance-preview-box banner" data-banner-preview>
                        @if($bannerImagePreviewUrl)
                            <img src="{{ $bannerImagePreviewUrl }}" alt="Banner hiện tại">
                        @else
                            <span><i class="bi bi-image me-1"></i>Chưa có ảnh banner</span>
                        @endif
                    </div>

                    <div class="appearance-field text-left">
                        <label for="banner_image_file" class="form-label">Tải banner mới</label>
                        <input type="hidden" name="banner_image_url" value="{{ $bannerImageUrl }}">
                        <input id="banner_image_file" type="file" name="banner_image_file" accept="image/*" class="form-control" data-banner-file>
                        <div class="flex flex-wrap items-center gap-2 mt-3 text-left">
                            <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">JPG</span>
                            <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">PNG</span>
                            <span class="bg-orange-50 text-orange-700 border border-orange-100 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">WEBP</span>
                            <span class="bg-gray-50 text-gray-500 border border-gray-200 text-xs font-normal px-2.5 py-0.5 rounded-full inline-flex items-center">Tối đa 20MB</span>
                        </div>
                        @error('banner_image_file')<div class="text-danger small mt-1 text-left">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <button type="submit" class="w-full bg-orange-600 text-white hover:bg-orange-700 py-2.5 rounded-lg text-sm font-normal cursor-pointer transition-all">
                <i class="bi bi-save me-2"></i>Lưu thay đổi diện mạo
            </button>
        </aside>
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
