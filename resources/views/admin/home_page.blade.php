@extends('layouts.app')
@section('title', 'Quản lý trang chủ')

@section('content')
@php
    $banner = $contents->get('banner');
    $about = $contents->get('about');
    $contact = $contents->get('contact');
@endphp

<div class="page-heading">
    <div>
        <h5>Quản lý trang chủ</h5>
        <div class="text-muted">Cập nhật nội dung công khai, tin tức, thông báo, sự kiện và tài liệu.</div>
    </div>
    <a href="{{ route('home') }}" class="btn btn-outline-primary" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i>Xem trang chủ</a>
</div>

@unless($tablesReady)
    <div class="alert alert-warning">Một số bảng mới chưa tồn tại. Vui lòng chạy migration trước khi lưu nội dung.</div>
@endunless

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header">Nội dung chính</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.home-page.content') }}" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Tiêu đề banner</label>
                        <input name="banner_title" class="form-control" value="{{ old('banner_title', $banner->title ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dòng phụ banner</label>
                        <input name="banner_subtitle" class="form-control" value="{{ old('banner_subtitle', data_get($banner, 'extra.subtitle')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mô tả banner</label>
                        <textarea name="banner_content" rows="3" class="form-control">{{ old('banner_content', $banner->content ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">URL ảnh banner</label>
                        <input name="banner_image_url" class="form-control" value="{{ old('banner_image_url', $banner->image_url ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tiêu đề giới thiệu</label>
                        <input name="about_title" class="form-control" value="{{ old('about_title', $about->title ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tiêu đề liên hệ</label>
                        <input name="contact_title" class="form-control" value="{{ old('contact_title', $contact->title ?? '') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Giới thiệu trường</label>
                        <textarea name="about_content" rows="4" class="form-control">{{ old('about_content', $about->content ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nội dung liên hệ</label>
                        <textarea name="contact_content" rows="3" class="form-control">{{ old('contact_content', $contact->content ?? '') }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Điện thoại</label>
                        <input name="contact_phone" class="form-control" value="{{ old('contact_phone', data_get($contact, 'extra.phone')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input name="contact_email" class="form-control" value="{{ old('contact_email', data_get($contact, 'extra.email')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Địa chỉ</label>
                        <input name="contact_address" class="form-control" value="{{ old('contact_address', data_get($contact, 'extra.address')) }}">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu nội dung</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card mb-3">
            <div class="card-header">Thêm tin tức hoặc thông báo</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.home-page.posts.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label">Loại</label>
                        <select name="type" class="form-select">
                            <option value="news">Tin tức</option>
                            <option value="announcement">Thông báo</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Ngày đăng</label>
                        <input type="date" name="published_at" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tiêu đề</label>
                        <input name="title" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tóm tắt</label>
                        <textarea name="summary" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nội dung</label>
                        <textarea name="content" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Thêm bài viết</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Bài viết gần đây</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Tiêu đề</th><th>Loại</th><th>Ngày</th></tr></thead>
                    <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td class="fw-semibold">{{ $post->title }}</td>
                            <td>{{ $post->type === 'news' ? 'Tin tức' : 'Thông báo' }}</td>
                            <td>{{ optional($post->published_at)->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><div class="empty-state"><i class="bi bi-inbox"></i>Chưa có bài viết.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">Thêm sự kiện</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.home-page.events.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12"><label class="form-label">Tên sự kiện</label><input name="title" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Bắt đầu</label><input type="datetime-local" name="starts_at" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Kết thúc</label><input type="datetime-local" name="ends_at" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Địa điểm</label><input name="location" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Mô tả</label><textarea name="description" rows="3" class="form-control"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Thêm sự kiện</button></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Sự kiện</th><th>Thời gian</th><th>Địa điểm</th></tr></thead>
                    <tbody>
                    @forelse($events as $event)
                        <tr><td class="fw-semibold">{{ $event->title }}</td><td>{{ optional($event->starts_at)->format('d/m/Y H:i') }}</td><td>{{ $event->location }}</td></tr>
                    @empty
                        <tr><td colspan="3"><div class="empty-state"><i class="bi bi-calendar-x"></i>Chưa có sự kiện.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">Thêm tài liệu</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.home-page.documents.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12"><label class="form-label">Tên tài liệu</label><input name="title" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Môn học</label><select name="subject_id" class="form-select"><option value="">Tất cả</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Lớp</label><select name="class_id" class="form-select"><option value="">Tất cả</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Nhóm tài liệu</label><input name="category" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">URL tài liệu</label><input name="file_url" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Mô tả</label><textarea name="description" rows="3" class="form-control"></textarea></div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Thêm tài liệu</button></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Tài liệu</th><th>Môn</th><th>Lớp</th></tr></thead>
                    <tbody>
                    @forelse($documents as $document)
                        <tr><td class="fw-semibold">{{ $document->title }}</td><td>{{ $document->subject->name ?? 'Tất cả' }}</td><td>{{ $document->classRoom->name ?? 'Tất cả' }}</td></tr>
                    @empty
                        <tr><td colspan="3"><div class="empty-state"><i class="bi bi-folder2-open"></i>Chưa có tài liệu.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
