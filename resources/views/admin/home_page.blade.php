@extends('layouts.app')
@section('title', 'Quản lý trang chủ')

@section('content')
@php
    $banner = $contents->get('banner');
    $about = $contents->get('about');
    $bannerImageUrl = old('banner_image_url', $banner->image_url ?? '');
    $bannerImagePreviewUrl = $bannerImageUrl
        ? (\Illuminate\Support\Str::startsWith($bannerImageUrl, ['http://', 'https://']) ? $bannerImageUrl : asset(ltrim($bannerImageUrl, '/')))
        : '';
@endphp

<x-page-header
    title="Cấu hình nội dung hệ thống"
    subtitle="Quản lý giao diện trang chủ, biên tập các bài viết tin tức, chỉnh sửa thư viện ảnh và thông tin hiển thị trên cổng thông tin nhà trường."
>
    <a href="{{ route('home') }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
        <i class="bi bi-box-arrow-up-right me-2"></i>Xem trang chủ
    </a>
</x-page-header>

@unless($tablesReady)
    <div class="alert alert-warning">Chưa có bảng home_page_contents. Vui lòng import SQL tạo bảng trước khi lưu nội dung.</div>
@endunless

<form
    method="POST"
    action="{{ route('admin.home-page.content') }}"
    enctype="multipart/form-data"
    class="home-content-form"
    id="home-page-content"
    data-config-url="{{ route('api.admin.homepage-config.show') }}"
    data-save-url="{{ route('api.admin.homepage-config.update') }}"
>
    @csrf

    <section class="home-content-card">
        <div class="home-content-section-title">
            <h5>Cấu hình banner đầu trang</h5>
            <p>Thiết lập nội dung chính và hình ảnh hiển thị ở khu vực đầu trang chủ.</p>
        </div>

        <div class="home-content-grid">
            <div class="home-content-field">
                <label class="form-label">Tiêu đề banner</label>
                <input name="banner_title" class="form-control" value="{{ old('banner_title', $banner->title ?? '') }}">
                @error('banner_title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field">
                <label class="form-label">Hình ảnh Banner / Slider</label>
                <input type="hidden" name="banner_image_url" value="{{ $bannerImageUrl }}">
                <input type="file" name="banner_image_file" accept="image/*" class="form-control" data-banner-file>
                <div class="form-text text-xs text-gray-500 mt-1">Hỗ trợ hình ảnh định dạng JPG, PNG, WEBP. Tối đa 20MB.</div>
                <div class="home-banner-preview {{ $bannerImagePreviewUrl ? '' : 'is-empty' }}" data-banner-preview>
                    @if($bannerImagePreviewUrl)
                        <img src="{{ $bannerImagePreviewUrl }}" alt="Banner hiện tại">
                    @else
                        <span><i class="bi bi-image"></i>Chưa có ảnh banner</span>
                    @endif
                </div>
                @error('banner_image_file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field full">
                <label class="form-label">Nội dung chào mừng</label>
                <textarea name="banner_subtitle" rows="2" class="form-control">{{ old('banner_subtitle', data_get($banner, 'extra.subtitle')) }}</textarea>
                @error('banner_subtitle')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field full">
                <label class="form-label">Mô tả banner</label>
                <textarea name="banner_content" rows="3" class="form-control">{{ old('banner_content', $banner->content ?? '') }}</textarea>
                @error('banner_content')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="home-content-card">
        <div class="home-content-section-title">
            <h5>Khối giới thiệu nhà trường</h5>
            <p>Nội dung giới thiệu ngắn gọn về nhà trường trên cổng thông tin.</p>
        </div>

        <div class="home-content-grid">
            <div class="home-content-field full">
                <label class="form-label">Tiêu đề giới thiệu</label>
                <input name="about_title" class="form-control" value="{{ old('about_title', $about->title ?? '') }}">
                @error('about_title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="home-content-field full">
                <label class="form-label">Giới thiệu trường</label>
                <textarea name="about_content" rows="6" class="form-control home-content-textarea-large">{{ old('about_content', $about->content ?? '') }}</textarea>
                @error('about_content')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <div class="home-content-actions">
        <a href="{{ route('admin.home-page.index') }}" class="btn btn-secondary">Hủy thay đổi</a>
        <button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu cấu hình trang chủ</button>
    </div>
</form>

@if($tablesReady)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('home-page-content');
            if (!form || !window.fetch) {
                return;
            }

            const fields = {
                banner_title: form.elements.banner_title,
                banner_welcome: form.elements.banner_subtitle,
                banner_description: form.elements.banner_content,
                banner_image_url: form.elements.banner_image_url,
                banner_image_file: form.elements.banner_image_file,
                intro_title: form.elements.about_title,
                intro_content: form.elements.about_content,
            };
            const bannerPreview = form.querySelector('[data-banner-preview]');

            const setBannerPreview = (url) => {
                if (!bannerPreview) {
                    return;
                }

                bannerPreview.classList.toggle('is-empty', !url);
                bannerPreview.innerHTML = url
                    ? `<img src="${url}" alt="Banner hiện tại">`
                    : '<span><i class="bi bi-image"></i>Chưa có ảnh banner</span>';
            };

            const fillForm = (data) => {
                Object.entries(fields).forEach(([key, field]) => {
                    if (key === 'banner_image_file') {
                        return;
                    }

                    if (!field || typeof data[key] === 'undefined' || data[key] === null) {
                        return;
                    }

                    field.value = data[key];
                });

                if (fields.banner_image_url && typeof data.banner_image_url !== 'undefined') {
                    fields.banner_image_url.value = data.banner_image_url || '';
                }

                setBannerPreview(data.banner_image_preview_url || data.banner_image_url || '');
            };

            const payloadFromForm = () => {
                const payload = new FormData();
                payload.append('banner_title', fields.banner_title?.value || '');
                payload.append('banner_welcome', fields.banner_welcome?.value || '');
                payload.append('banner_description', fields.banner_description?.value || '');
                payload.append('banner_image_url', fields.banner_image_url?.value || '');
                payload.append('intro_title', fields.intro_title?.value || '');
                payload.append('intro_content', fields.intro_content?.value || '');

                if (fields.banner_image_file?.files?.[0]) {
                    payload.append('banner_image_file', fields.banner_image_file.files[0]);
                }

                return payload;
            };

            const csrfToken = form.querySelector('input[name="_token"]')?.value || '';
            const saveButton = form.querySelector('button[type="submit"], .home-content-actions .btn-primary');

            fields.banner_image_file?.addEventListener('change', () => {
                const file = fields.banner_image_file.files?.[0];

                if (!file) {
                    return;
                }

                setBannerPreview(URL.createObjectURL(file));
            });

            const showHomepageToast = (message, type = 'success') => {
                const toast = document.createElement('div');
                toast.className = `homepage-config-toast ${type === 'success' ? 'success' : 'error'}`;
                const icon = document.createElement('i');
                icon.className = `bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}`;
                const text = document.createElement('span');
                text.textContent = message;
                toast.append(icon, text);
                document.body.appendChild(toast);

                window.setTimeout(() => toast.classList.add('show'), 20);
                window.setTimeout(() => {
                    toast.classList.remove('show');
                    window.setTimeout(() => toast.remove(), 240);
                }, 2800);
            };

            fetch(form.dataset.configUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((response) => response.ok ? response.json() : null)
                .then((json) => {
                    if (json?.data) {
                        fillForm(json.data);
                    }
                })
                .catch(() => {
                    showHomepageToast('Không thể tải cấu hình mới nhất. Form đang hiển thị dữ liệu sẵn có.', 'error');
                });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (saveButton) {
                    saveButton.disabled = true;
                    saveButton.dataset.originalText = saveButton.innerHTML;
                    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Đang lưu...';
                }

                try {
                    const response = await fetch(form.dataset.saveUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: payloadFromForm(),
                    });

                    const json = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(json.message || 'Không thể lưu cấu hình trang chủ.');
                    }

                    if (json.data) {
                        fillForm(json.data);
                    }

                    showHomepageToast('🎉 Cập nhật cấu hình nội dung trang chủ thành công!');
                } catch (error) {
                    showHomepageToast(error.message || 'Không thể lưu cấu hình trang chủ.', 'error');
                } finally {
                    if (saveButton) {
                        saveButton.disabled = false;
                        saveButton.innerHTML = saveButton.dataset.originalText || '<i class="bi bi-save me-2"></i>Lưu cấu hình trang chủ';
                    }
                }
            });
        });
    </script>
@endif
@endsection
