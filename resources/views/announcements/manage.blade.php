@extends('layouts.app')
@section('title', $activeTab === 'events' ? 'Quản lý Sự kiện' : 'Quản lý Thông báo')

@section('content')
@php
    $roleOptions = [
        'all' => 'Tất cả',
        'admin' => 'Admin',
        'teacher' => 'Giáo viên',
        'homeroom' => 'Giáo viên chủ nhiệm',
        'student' => 'Học sinh',
        'parent' => 'Phụ huynh',
    ];
@endphp
<div class="page-heading">
    <div>
        <h5>{{ $activeTab === 'events' ? 'Quản lý Sự kiện' : 'Quản lý Thông báo' }}</h5>
        <div class="text-muted">{{ $activeTab === 'events' ? 'Quản lý sự kiện theo trạng thái và đối tượng tham gia.' : 'Quản lý thông báo theo trạng thái và đối tượng nhận.' }}</div>
    </div>
</div>

<div class="content-management">
    @if($activeTab === 'announcements')
        <div class="management-card">
            <div class="management-card-header">
                <div>
                    <h6>Thêm thông báo</h6>
                    <p>Tạo tin tức hoặc thông báo công bố cho người dùng.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('announcements.store') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Loại</label>
                    <select name="type" class="form-select" required>
                        <option value="announcement">Thông báo</option>
                        <option value="news">Tin tức</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ngày đăng</label>
                    <input type="datetime-local" name="published_at" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Tóm tắt</label>
                    <input type="text" name="summary" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Nội dung</label>
                    <textarea name="content" class="form-control" rows="5"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Trạng thái</label>
                    <div class="target-role-grid">
                        <label class="form-check">
                            <input type="radio" name="is_published" value="0" class="form-check-input" checked>
                            <span class="form-check-label"> Bản nháp</span>
                        </label>
                        <label class="form-check">
                            <input type="radio" name="is_published" value="1" class="form-check-input">
                            <span class="form-check-label"> Công bố</span>
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Đối tượng nhận</label>
                    <div class="target-role-grid" data-target-role-group>
                        @foreach($roleOptions as $roleValue => $roleLabel)
                            <label class="form-check">
                                <input type="checkbox" name="target_roles[]" value="{{ $roleValue }}" class="form-check-input" data-target-role="{{ $roleValue }}" @checked($roleValue === 'all')>
                                <span class="form-check-label">{{ $roleLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary content-submit-btn">
                        <i class="bi bi-plus-circle"></i> Thêm thông báo
                    </button>
                </div>
            </form>
        </div>

        <div class="management-card">
            <div class="management-card-header">
                <div>
                    <h6>Danh sách thông báo</h6>
                    <p>Nội dung dài được rút gọn trong bảng, bấm Chi tiết để xem đầy đủ.</p>
                </div>
            </div>
            <div class="table-responsive content-table-wrap">
                <table class="table content-table align-middle">
                    <thead>
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Loại</th>
                            <th>Ngày đăng</th>
                            <th>Nội dung rút gọn</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($posts as $post)
                        @php
                            $postText = trim($post->summary ?: strip_tags($post->content ?? ''));
                            $detailId = 'post-detail-' . $loop->index;
                            $editId = 'post-edit-' . $loop->index;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $post->title }}</td>
                            <td>{{ $post->type === 'news' ? 'Tin tức' : 'Thông báo' }}</td>
                            <td>{{ optional($post->published_at)->format('d/m/Y H:i') ?: 'Đang cập nhật' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($postText ?: 'Chưa có nội dung.', 90, '......') }}</td>
                            <td>
                                <span class="content-status {{ $post->is_published ? 'published' : 'draft' }}">
                                    {{ $post->is_published ? ' Công bố' : ' Bản nháp' }}
                                </span>
                            </td>
                            <td>
                                <div class="content-action-group justify-content-end">
                                    <button type="button" class="content-action-btn icon-only detail" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}" title="Xem chi tiết" aria-label="Xem chi tiết">
                                        <i class="bi bi-eye"></i><span class="visually-hidden">Xem chi tiết</span>
                                    </button>
                                    <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#{{ $editId }}" title="Sửa" aria-label="Sửa">
                                        <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                    </button>
                                    <form method="POST" action="{{ route('announcements.destroy', $post) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="content-action-btn icon-only delete" title="Xóa" aria-label="Xóa" data-bs-toggle="tooltip">
                                            <i class="bi bi-trash"></i><span class="visually-hidden">Xóa</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade content-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <div class="modal-kicker">{{ $post->type === 'news' ? 'Tin tức' : 'Thông báo' }}</div>
                                            <h5 class="modal-title">{{ $post->title }}</h5>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                    </div>
                                    <div class="modal-body">
                                        <dl class="content-detail-list">
                                            <div>
                                                <dt>Ngày đăng</dt>
                                                <dd>{{ optional($post->published_at)->format('d/m/Y H:i') ?: 'Đang cập nhật' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Tóm tắt</dt>
                                                <dd>{{ $post->summary ?: 'Chưa có tóm tắt.' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Đối tượng nhận</dt>
                                                <dd>{{ collect($post->targetRoles())->map(fn ($role) => $roleOptions[$role] ?? $role)->join(', ') }}</dd>
                                            </div>
                                            <div>
                                                <dt>Nội dung đầy đủ</dt>
                                                <dd class="content-full-text">{!! nl2br(e($post->content ?: 'Chưa có nội dung.')) !!}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade content-modal" id="{{ $editId }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('announcements.update', $post) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <div>
                                                <div class="modal-kicker">Chỉnh sửa</div>
                                                <h5 class="modal-title">{{ $post->title }}</h5>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Loại</label>
                                                    <select name="type" class="form-select" required>
                                                        <option value="announcement" @selected($post->type === 'announcement')>Thông báo</option>
                                                        <option value="news" @selected($post->type === 'news')>Tin tức</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label">Tiêu đề</label>
                                                    <input type="text" name="title" class="form-control" value="{{ $post->title }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Ngày đăng</label>
                                                    <input type="datetime-local" name="published_at" class="form-control" value="{{ optional($post->published_at)->format('Y-m-d\TH:i') }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Trạng thái</label>
                                                    <div class="target-role-grid">
                                                        <label class="form-check">
                                                            <input type="radio" name="is_published" value="0" class="form-check-input" @checked(! $post->is_published)>
                                                            <span class="form-check-label">🟡 Bản nháp</span>
                                                        </label>
                                                        <label class="form-check">
                                                            <input type="radio" name="is_published" value="1" class="form-check-input" @checked($post->is_published)>
                                                            <span class="form-check-label">🟢 Công bố</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Tóm tắt</label>
                                                    <input type="text" name="summary" class="form-control" value="{{ $post->summary }}">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Nội dung</label>
                                                    <textarea name="content" class="form-control" rows="6">{{ $post->content }}</textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Đối tượng nhận</label>
                                                    <div class="target-role-grid" data-target-role-group>
                                                        @foreach($roleOptions as $roleValue => $roleLabel)
                                                            <label class="form-check">
                                                                <input type="checkbox" name="target_roles[]" value="{{ $roleValue }}" class="form-check-input" data-target-role="{{ $roleValue }}" @checked(in_array($roleValue, $post->targetRoles(), true))>
                                                                <span class="form-check-label">{{ $roleLabel }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Đóng</button>
                                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state"><i class="bi bi-inbox"></i>Chưa có thông báo.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($posts, 'links'))
                <div class="content-pagination">{{ $posts->links() }}</div>
            @endif
        </div>
    @else
        <div class="management-card">
            <div class="management-card-header">
                <div>
                    <h6>Thêm sự kiện</h6>
                    <p>Tạo sự kiện để hiển thị trên hệ thống và Trang chủ.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('events.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Tên sự kiện</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Địa điểm</label>
                    <input type="text" name="location" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Thời gian bắt đầu</label>
                    <input type="datetime-local" name="starts_at" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Thời gian kết thúc</label>
                    <input type="datetime-local" name="ends_at" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="5"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Trạng thái</label>
                    <div class="target-role-grid">
                        <label class="form-check">
                            <input type="radio" name="is_published" value="0" class="form-check-input" checked>
                            <span class="form-check-label"> Bản nháp</span>
                        </label>
                        <label class="form-check">
                            <input type="radio" name="is_published" value="1" class="form-check-input">
                            <span class="form-check-label"> Công bố</span>
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Đối tượng tham gia</label>
                    <div class="target-role-grid" data-target-role-group>
                        @foreach($roleOptions as $roleValue => $roleLabel)
                            <label class="form-check">
                                <input type="checkbox" name="target_roles[]" value="{{ $roleValue }}" class="form-check-input" data-target-role="{{ $roleValue }}" @checked($roleValue === 'all')>
                                <span class="form-check-label">{{ $roleLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary content-submit-btn">
                        <i class="bi bi-plus-circle"></i> Thêm sự kiện
                    </button>
                </div>
            </form>
        </div>

        <div class="management-card">
            <div class="management-card-header">
                <div>
                    <h6>Danh sách sự kiện</h6>
                    <p>Sự kiện đã công bố sẽ được Landing Page ưu tiên hiển thị.</p>
                </div>
            </div>
            <div class="table-responsive content-table-wrap">
                <table class="table content-table align-middle">
                    <thead>
                        <tr>
                            <th>Tên sự kiện</th>
                            <th>Thời gian</th>
                            <th>Địa điểm</th>
                            <th>Mô tả rút gọn</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($events as $event)
                        @php
                            $eventText = trim($event->description ?? '');
                            $detailId = 'event-detail-' . $loop->index;
                            $editId = 'event-edit-' . $loop->index;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $event->title }}</td>
                            <td>{{ optional($event->starts_at)->format('d/m/Y H:i') ?: 'Đang cập nhật' }}</td>
                            <td>{{ $event->location ?: 'Đang cập nhật' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($eventText ?: 'Chưa có mô tả.', 90, '......') }}</td>
                            <td>
                                <span class="content-status {{ $event->is_published ? 'published' : 'draft' }}">
                                    {{ $event->is_published ? ' Công bố' : ' Bản nháp' }}
                                </span>
                            </td>
                            <td>
                                <div class="content-action-group justify-content-end">
                                    <button type="button" class="content-action-btn icon-only detail" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}" title="Xem chi tiết" aria-label="Xem chi tiết">
                                        <i class="bi bi-eye"></i><span class="visually-hidden">Xem chi tiết</span>
                                    </button>
                                    <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#{{ $editId }}" title="Sửa" aria-label="Sửa">
                                        <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                    </button>
                                    <form method="POST" action="{{ route('events.destroy', $event) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="content-action-btn icon-only delete" title="Xóa" aria-label="Xóa" data-bs-toggle="tooltip">
                                            <i class="bi bi-trash"></i><span class="visually-hidden">Xóa</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade content-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <div class="modal-kicker">Sự kiện</div>
                                            <h5 class="modal-title">{{ $event->title }}</h5>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                    </div>
                                    <div class="modal-body">
                                        <dl class="content-detail-list">
                                            <div>
                                                <dt>Thời gian</dt>
                                                <dd>
                                                    {{ optional($event->starts_at)->format('d/m/Y H:i') ?: 'Đang cập nhật' }}
                                                    @if($event->ends_at)
                                                        - {{ $event->ends_at->format('d/m/Y H:i') }}
                                                    @endif
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Địa điểm</dt>
                                                <dd>{{ $event->location ?: 'Đang cập nhật' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Đối tượng tham gia</dt>
                                                <dd>{{ collect($event->targetRoles())->map(fn ($role) => $roleOptions[$role] ?? $role)->join(', ') }}</dd>
                                            </div>
                                            <div>
                                                <dt>Mô tả đầy đủ</dt>
                                                <dd class="content-full-text">{!! nl2br(e($event->description ?: 'Chưa có mô tả.')) !!}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade content-modal" id="{{ $editId }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('events.update', $event) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <div>
                                                <div class="modal-kicker">Chỉnh sửa sự kiện</div>
                                                <h5 class="modal-title">{{ $event->title }}</h5>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Tên sự kiện</label>
                                                    <input type="text" name="title" class="form-control" value="{{ $event->title }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Địa điểm</label>
                                                    <input type="text" name="location" class="form-control" value="{{ $event->location }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Thời gian bắt đầu</label>
                                                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ optional($event->starts_at)->format('Y-m-d\TH:i') }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Thời gian kết thúc</label>
                                                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ optional($event->ends_at)->format('Y-m-d\TH:i') }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Trạng thái</label>
                                                    <div class="target-role-grid">
                                                        <label class="form-check">
                                                            <input type="radio" name="is_published" value="0" class="form-check-input" @checked(! $event->is_published)>
                                                            <span class="form-check-label"> Bản nháp</span>
                                                        </label>
                                                        <label class="form-check">
                                                            <input type="radio" name="is_published" value="1" class="form-check-input" @checked($event->is_published)>
                                                            <span class="form-check-label"> Công bố</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Mô tả</label>
                                                    <textarea name="description" class="form-control" rows="6">{{ $event->description }}</textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Đối tượng tham gia</label>
                                                    <div class="target-role-grid" data-target-role-group>
                                                        @foreach($roleOptions as $roleValue => $roleLabel)
                                                            <label class="form-check">
                                                                <input type="checkbox" name="target_roles[]" value="{{ $roleValue }}" class="form-check-input" data-target-role="{{ $roleValue }}" @checked(in_array($roleValue, $event->targetRoles(), true))>
                                                                <span class="form-check-label">{{ $roleLabel }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Đóng</button>
                                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state"><i class="bi bi-calendar-x"></i>Chưa có sự kiện.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($events, 'links'))
                <div class="content-pagination">{{ $events->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
