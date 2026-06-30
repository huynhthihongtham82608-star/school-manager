@extends('layouts.app')
@section('title', 'Tài liệu học tập')

@section('content')
@php
    $canManageDocuments = auth()->user()->isAdmin() || auth()->user()->isStaff();
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
        <h5>Tài liệu học tập</h5>
        <div class="text-muted">Thư viện tài liệu phục vụ học tập và giảng dạy.</div>
    </div>
</div>

@if($canManageDocuments)
    <div class="content-management">
        <div class="management-card">
            <div class="management-card-header">
                <div>
                    <h6>Thêm tài liệu học tập</h6>
                    <p>Quản lý tài liệu học tập hiển thị trong hệ thống và Trang chủ.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('documents.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Tên tài liệu</label>
                    <input name="title" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Môn học</label>
                    <select name="subject_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lớp</label>
                    <select name="class_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nhóm tài liệu</label>
                    <input name="category" class="form-control">
                </div>
                <div class="col-md-8">
                    <label class="form-label">URL tài liệu</label>
                    <input name="file_url" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" rows="3" class="form-control"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Trạng thái</label>
                    <div class="target-role-grid">
                        <label class="form-check">
                            <input type="radio" name="is_published" value="0" class="form-check-input" checked>
                            <span class="form-check-label">Bản nháp</span>
                        </label>
                        <label class="form-check">
                            <input type="radio" name="is_published" value="1" class="form-check-input">
                            <span class="form-check-label">Công bố</span>
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Đối tượng xem</label>
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
                    <button class="btn btn-primary content-submit-btn">
                        <i class="bi bi-plus-circle"></i> Thêm tài liệu học tập
                    </button>
                </div>
            </form>
        </div>

        <div class="management-card">
            <div class="management-card-header">
                <div>
                    <h6>Danh sách tài liệu học tập</h6>
                    <p>Sửa, xóa hoặc xem chi tiết tài liệu học tập trong hệ thống.</p>
                </div>
            </div>
            <div class="table-responsive content-table-wrap">
                <table class="table content-table align-middle">
                    <thead>
                        <tr>
                            <th>Tài liệu</th>
                            <th>Môn học</th>
                            <th>Lớp</th>
                            <th>Nhóm</th>
                            <th>Đối tượng xem</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($documents as $document)
                        @php
                            $detailId = 'document-detail-' . $document->id;
                            $editId = 'document-edit-' . $document->id;
                            $targetRoleText = collect($document->targetRoles())->map(fn ($role) => $roleOptions[$role] ?? $role)->join(', ');
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $document->title }}</div>
                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($document->description ?: 'Chưa có mô tả.', 90, '...') }}</div>
                            </td>
                            <td>{{ $document->subject->name ?? 'Tất cả' }}</td>
                            <td>{{ $document->classRoom->name ?? 'Tất cả' }}</td>
                            <td>{{ $document->category ?: 'Chưa phân nhóm' }}</td>
                            <td>{{ $targetRoleText }}</td>
                            <td><span class="fw-semibold">{{ $document->is_published ? 'Công bố' : 'Bản nháp' }}</span></td>
                            <td>
                                <div class="content-action-group justify-content-end">
                                    <button type="button" class="content-action-btn icon-only detail" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}" title="Xem chi tiết" aria-label="Xem chi tiết">
                                        <i class="bi bi-eye"></i><span class="visually-hidden">Xem chi tiết</span>
                                    </button>
                                    @if($document->file_url)
                                        <a href="{{ $document->file_url }}" target="_blank" class="content-action-btn icon-only detail" title="Mở tài liệu" aria-label="Mở tài liệu" data-bs-toggle="tooltip">
                                            <i class="bi bi-box-arrow-up-right"></i><span class="visually-hidden">Mở tài liệu</span>
                                        </a>
                                    @endif
                                    <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#{{ $editId }}" title="Sửa" aria-label="Sửa">
                                        <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                    </button>
                                    <form method="POST" action="{{ route('documents.destroy', $document) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="content-action-btn icon-only delete" title="Xóa" aria-label="Xóa" data-bs-toggle="tooltip">
                                            <i class="bi bi-trash"></i><span class="visually-hidden">Xóa</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state"><i class="bi bi-folder2-open"></i>Chưa có tài liệu học tập.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @foreach($documents as $document)
                @php
                    $detailId = 'document-detail-' . $document->id;
                    $editId = 'document-edit-' . $document->id;
                    $targetRoleText = collect($document->targetRoles())->map(fn ($role) => $roleOptions[$role] ?? $role)->join(', ');
                @endphp

                <div class="modal fade content-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <div class="modal-kicker">Tài liệu học tập</div>
                                    <h5 class="modal-title">{{ $document->title }}</h5>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <dl class="content-detail-list">
                                    <div>
                                        <dt>Môn học</dt>
                                        <dd>{{ $document->subject->name ?? 'Tất cả' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Lớp</dt>
                                        <dd>{{ $document->classRoom->name ?? 'Tất cả' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Nhóm tài liệu</dt>
                                        <dd>{{ $document->category ?: 'Chưa phân nhóm' }}</dd>
                                    </div>
                                    <div>
                                        <dt>Đối tượng xem</dt>
                                        <dd>{{ $targetRoleText }}</dd>
                                    </div>
                                    <div>
                                        <dt>Trạng thái</dt>
                                        <dd>{{ $document->is_published ? 'Công bố' : 'Bản nháp' }}</dd>
                                    </div>
                                    @if($document->file_url)
                                        <div>
                                            <dt>URL tài liệu</dt>
                                            <dd><a href="{{ $document->file_url }}" target="_blank" class="btn btn-outline-primary btn-sm">Mở tài liệu</a></dd>
                                        </div>
                                    @endif
                                    <div>
                                        <dt>Mô tả đầy đủ</dt>
                                        <dd class="content-full-text">{!! nl2br(e($document->description ?: 'Chưa có mô tả.')) !!}</dd>
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
                            <form method="POST" action="{{ route('documents.update', $document) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <div>
                                        <div class="modal-kicker">Chỉnh sửa tài liệu</div>
                                        <h5 class="modal-title">{{ $document->title }}</h5>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tên tài liệu</label>
                                            <input name="title" class="form-control" value="{{ $document->title }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Môn học</label>
                                            <select name="subject_id" class="form-select">
                                                <option value="">Tất cả</option>
                                                @foreach($subjects as $subject)
                                                    <option value="{{ $subject->id }}" @selected($document->subject_id === $subject->id)>{{ $subject->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Lớp</label>
                                            <select name="class_id" class="form-select">
                                                <option value="">Tất cả</option>
                                                @foreach($classes as $class)
                                                    <option value="{{ $class->id }}" @selected($document->class_id === $class->id)>{{ $class->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Nhóm tài liệu</label>
                                            <input name="category" class="form-control" value="{{ $document->category }}">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">URL tài liệu</label>
                                            <input name="file_url" class="form-control" value="{{ $document->file_url }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Mô tả</label>
                                            <textarea name="description" rows="4" class="form-control">{{ $document->description }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Trạng thái</label>
                                            <div class="target-role-grid">
                                                <label class="form-check">
                                                    <input type="radio" name="is_published" value="0" class="form-check-input" @checked(! $document->is_published)>
                                                    <span class="form-check-label">Bản nháp</span>
                                                </label>
                                                <label class="form-check">
                                                    <input type="radio" name="is_published" value="1" class="form-check-input" @checked($document->is_published)>
                                                    <span class="form-check-label">Công bố</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Đối tượng xem</label>
                                            <div class="target-role-grid" data-target-role-group>
                                                @foreach($roleOptions as $roleValue => $roleLabel)
                                                    <label class="form-check">
                                                        <input type="checkbox" name="target_roles[]" value="{{ $roleValue }}" class="form-check-input" data-target-role="{{ $roleValue }}" @checked(in_array($roleValue, $document->targetRoles(), true))>
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
            @endforeach

            @if(method_exists($documents, 'links'))
                <div class="content-pagination">{{ $documents->links() }}</div>
            @endif
        </div>
    </div>
@else
    <div class="content-grid">
        @forelse($documents as $document)
            @php($detailId = 'document-detail-' . $document->id)
            <article class="info-card">
                <span class="feature-card-icon mb-3"><i class="bi bi-file-earmark-text"></i></span>
                <h6>{{ $document->title }}</h6>
                <p>{{ \Illuminate\Support\Str::limit($document->description ?: 'Tài liệu được nhà trường chia sẻ.', 120, '...') }}</p>
                <div class="small text-muted mb-3">
                    {{ $document->subject->name ?? 'Tất cả môn học' }} - {{ $document->classRoom->name ?? 'Tất cả lớp' }}
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}">
                        <i class="bi bi-eye me-1"></i>Xem chi tiết
                    </button>
                    @if($document->file_url)
                        <a href="{{ $document->file_url }}" target="_blank" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Xem tài liệu
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state"><i class="bi bi-folder2-open"></i>Chưa có tài liệu học tập.</div>
            </div>
        @endforelse
    </div>

    @foreach($documents as $document)
        @php($detailId = 'document-detail-' . $document->id)
        <div class="modal fade content-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <div class="modal-kicker">Tài liệu học tập</div>
                            <h5 class="modal-title">{{ $document->title }}</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="content-detail-list">
                            <div>
                                <dt>Môn học</dt>
                                <dd>{{ $document->subject->name ?? 'Tất cả' }}</dd>
                            </div>
                            <div>
                                <dt>Lớp</dt>
                                <dd>{{ $document->classRoom->name ?? 'Tất cả' }}</dd>
                            </div>
                            <div>
                                <dt>Nhóm tài liệu</dt>
                                <dd>{{ $document->category ?: 'Chưa phân nhóm' }}</dd>
                            </div>
                            @if($document->file_url)
                                <div>
                                    <dt>URL tài liệu</dt>
                                    <dd><a href="{{ $document->file_url }}" target="_blank" class="btn btn-outline-primary btn-sm">Mở tài liệu</a></dd>
                                </div>
                            @endif
                            <div>
                                <dt>Mô tả đầy đủ</dt>
                                <dd class="content-full-text">{!! nl2br(e($document->description ?: 'Chưa có mô tả.')) !!}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if(method_exists($documents, 'links'))
        <div class="mt-3">{{ $documents->links() }}</div>
    @endif
@endif
@endsection
