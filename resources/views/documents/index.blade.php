@extends('layouts.app')
@section('title', 'Tài liệu học tập')

@section('content')
@php
    $canManageDocuments = auth()->user()->isAdmin() || auth()->user()->isStaff() || auth()->user()->isTeacher();
    $isTeacherDocumentManager = auth()->user()->isTeacher() && ! (auth()->user()->isAdmin() || auth()->user()->isStaff());
    $roleOptions = [
        'all' => 'Tất cả',
        'admin' => 'Admin',
        'teacher' => 'Giáo viên',
        'homeroom' => 'Giáo viên chủ nhiệm',
        'student' => 'Học sinh',
        'parent' => 'Phụ huynh',
    ];
@endphp

<x-page-header
    title="Cấu hình nội dung hệ thống"
    subtitle="Quản lý giao diện trang chủ, biên tập các bài viết tin tức, chỉnh sửa thư viện ảnh và thông tin hiển thị trên cổng thông tin nhà trường."
>
    @if($canManageDocuments)
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#document-create-modal">
            <i class="bi bi-plus-lg me-1"></i>Thêm tài liệu mới
        </button>
    @endif
</x-page-header>

@if($canManageDocuments)
    <div class="content-management" id="document-create-form">
        <div class="modal fade content-modal content-create-modal document-create-modal" id="document-create-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <div class="content-modal-section-title">
                                <h5 class="modal-title">Thêm tài liệu học tập</h5>
                                <p>Quản lý tài liệu học tập hiển thị trong hệ thống và Trang chủ.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <div class="content-form-grid">
                                <div class="content-form-field">
                                    <label class="form-label">Tên tài liệu</label>
                                    <input name="title" class="form-control" required>
                                </div>
                                <div class="content-form-field">
                                    <label class="form-label">Nhóm tài liệu</label>
                                    <input name="category" class="form-control" list="document-category-options" placeholder="Ví dụ: Giáo trình, Bài tập, Tài liệu tham khảo">
                                    <datalist id="document-category-options">
                                        <option value="Giáo trình">
                                        <option value="Bài tập">
                                        <option value="Tài liệu tham khảo">
                                        <option value="Đề cương ôn tập">
                                    </datalist>
                                </div>
                                <div class="content-form-field">
                                    <label class="form-label">Môn học</label>
                                    <select name="subject_id" class="form-select">
                                        @if(! $isTeacherDocumentManager)
                                            <option value="">Tất cả</option>
                                        @else
                                            <option value="">Chọn môn học</option>
                                        @endif
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="content-form-field">
                                    <label class="form-label">Lớp</label>
                                    <select name="class_id" class="form-select">
                                        <option value="">{{ $isTeacherDocumentManager ? 'Áp dụng chung theo môn' : 'Tất cả' }}</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="content-form-field full">
                                    <label class="form-label">Tệp tài liệu</label>
                                    <input type="file" name="document_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg" required>
                                    <div class="form-text">Hỗ trợ PDF, Word, Excel, PowerPoint và hình ảnh. Tối đa 20MB.</div>
                                </div>
                                <div class="content-form-field">
                                    <label class="form-label">Trạng thái</label>
                                    <div class="content-status-toggle">
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
                                <div class="content-form-field full">
                                    <label class="form-label">Mô tả</label>
                                    <textarea name="description" rows="5" class="form-control content-textarea-large"></textarea>
                                </div>
                                <div class="content-form-field full">
                                    <label class="form-label">Đối tượng xem</label>
                                    <div class="content-target-panel" data-target-role-group>
                                        @foreach($roleOptions as $roleValue => $roleLabel)
                                            <label class="form-check">
                                                <input type="checkbox" name="target_roles[]" value="{{ $roleValue }}" class="form-check-input" data-target-role="{{ $roleValue }}" @checked($roleValue === 'all')>
                                                <span class="form-check-label">{{ $roleLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Lưu dữ liệu</button>
                        </div>
                    </form>
                </div>
            </div>
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
                            <th class="text-end action-column-header" aria-label="Thao tác"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($documents as $document)
                        @php
                            $detailId = 'document-detail-' . $document->id;
                            $editId = 'document-edit-' . $document->id;
                            $targetRoleText = collect($document->targetRoles())->map(fn ($role) => $roleOptions[$role] ?? $role)->join(', ');
                            $canManageThisDocument = in_array($document->id, $manageableDocumentIds ?? [], true);
                            $documentFileUrl = $document->fileUrl();
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold content-break-cell">{{ $document->title }}</div>
                                <div class="small text-muted content-break-cell">{{ \Illuminate\Support\Str::limit($document->description ?: 'Chưa có mô tả.', 90, '...') }}</div>
                            </td>
                            <td>{{ $document->subject->name ?? 'Tất cả' }}</td>
                            <td>{{ $document->classRoom->name ?? 'Tất cả' }}</td>
                            <td>{{ $document->category ?: 'Chưa phân nhóm' }}</td>
                            <td>{{ $targetRoleText }}</td>
                            <td>
                                <span class="content-status {{ $document->is_published ? 'published' : 'draft' }}">
                                    {{ $document->is_published ? 'Công bố' : 'Bản nháp' }}
                                </span>
                            </td>
                            <td>
                                <div class="content-action-group justify-content-end">
                                    @if($canManageThisDocument)
                                        <button type="button" class="content-action-btn icon-only edit" data-bs-toggle="modal" data-bs-target="#{{ $editId }}" title="Sửa" aria-label="Sửa">
                                            <i class="bi bi-pencil-square"></i><span class="visually-hidden">Sửa</span>
                                        </button>
                                    @endif
                                    <div class="dropdown">
                                        <button type="button" class="content-action-btn icon-only more" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" title="Thao tác khác" aria-label="Thao tác khác">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end content-action-menu">
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}">
                                                <i class="bi bi-eye"></i>Xem chi tiết / Tải về
                                            </button>
                                            @if($canManageThisDocument)
                                                <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài liệu này? Hành động này không thể hoàn tác!')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item danger">
                                                        <i class="bi bi-trash"></i>Xóa tài liệu
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
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
                    $documentFileUrl = $document->fileUrl();
                @endphp

                <div class="modal fade content-modal document-detail-modal" id="{{ $detailId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header document-detail-modal-head">
                                <div class="document-detail-profile">
                                    <div class="document-detail-icon"><i class="bi bi-file-earmark-text"></i></div>
                                    <div class="document-detail-title-wrap">
                                        <h5 class="modal-title">{{ $document->title }}</h5>
                                        <div>Nhóm tài liệu: {{ $document->category ?: 'Chưa phân nhóm' }}</div>
                                    </div>
                                    <span class="content-status {{ $document->is_published ? 'published' : 'draft' }} document-detail-status">
                                        {{ $document->is_published ? 'Công bố' : 'Bản nháp' }}
                                    </span>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <section class="document-detail-section">
                                    <div class="document-detail-section-title">Phạm vi áp dụng & Phân quyền</div>
                                    <div class="document-detail-grid">
                                        <div class="document-detail-field">
                                            <span>Môn học</span>
                                            <strong>{{ $document->subject->name ?? 'Tất cả' }}</strong>
                                        </div>
                                        <div class="document-detail-field">
                                            <span>Lớp</span>
                                            <strong>{{ $document->classRoom->name ?? 'Tất cả' }}</strong>
                                        </div>
                                        <div class="document-detail-field full">
                                            <span>Đối tượng xem</span>
                                            <strong>{{ $targetRoleText ?: 'Tất cả' }}</strong>
                                        </div>
                                    </div>
                                </section>

                                <section class="document-detail-section mt-3">
                                    <div class="document-detail-section-title">Nội dung tệp đính kèm & Mô tả</div>
                                    <div class="document-file-box">
                                    @if($documentFileUrl)
                                            <a href="{{ $documentFileUrl }}" target="_blank" class="document-download-btn">
                                                <i class="bi bi-download"></i>Tải tệp xuống hệ thống
                                            </a>
                                        @else
                                            <span class="document-file-empty"><i class="bi bi-file-earmark-x"></i>Chưa có tệp đính kèm</span>
                                    @endif
                                        <div class="document-description">
                                            <span>Mô tả đầy đủ</span>
                                            <p>{!! nl2br(e($document->description ?: 'Không có mô tả.')) !!}</p>
                                        </div>
                                    </div>
                                </section>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-primary document-detail-close" data-bs-dismiss="modal">Đóng hồ sơ tài liệu</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade content-modal" id="{{ $editId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data">
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
                                    <div class="content-form-grid">
                                        <div class="content-form-field">
                                            <label class="form-label">Tên tài liệu</label>
                                            <input name="title" class="form-control" value="{{ $document->title }}" required>
                                        </div>
                                        <div class="content-form-field">
                                            <label class="form-label">Nhóm tài liệu</label>
                                            <input name="category" class="form-control" value="{{ $document->category }}" list="document-category-options-{{ $document->id }}">
                                            <datalist id="document-category-options-{{ $document->id }}">
                                                <option value="Giáo trình">
                                                <option value="Bài tập">
                                                <option value="Tài liệu tham khảo">
                                                <option value="Đề cương ôn tập">
                                            </datalist>
                                        </div>
                                        <div class="content-form-field">
                                            <label class="form-label">Môn học</label>
                                            <select name="subject_id" class="form-select">
                                                @if(! $isTeacherDocumentManager)
                                                    <option value="">Tất cả</option>
                                                @else
                                                    <option value="">Chọn môn học</option>
                                                @endif
                                                @foreach($subjects as $subject)
                                                    <option value="{{ $subject->id }}" @selected($document->subject_id === $subject->id)>{{ $subject->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="content-form-field">
                                            <label class="form-label">Lớp</label>
                                            <select name="class_id" class="form-select">
                                                <option value="">{{ $isTeacherDocumentManager ? 'Áp dụng chung theo môn' : 'Tất cả' }}</option>
                                                @foreach($classes as $class)
                                                    <option value="{{ $class->id }}" @selected($document->class_id === $class->id)>{{ $class->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="content-form-field full">
                                            <label class="form-label">Tệp tài liệu</label>
                                            <input type="file" name="document_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg">
                                            <div class="form-text">
                                                @if($documentFileUrl)
                                                    Để trống nếu muốn giữ tệp hiện tại.
                                                @else
                                                    Chọn tệp tài liệu để đưa lên hệ thống.
                                                @endif
                                                Hỗ trợ PDF, Word, Excel, PowerPoint và hình ảnh. Tối đa 20MB.
                                            </div>
                                        </div>
                                        <div class="content-form-field">
                                            <label class="form-label">Trạng thái</label>
                                            <div class="content-status-toggle">
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
                                        <div class="content-form-field full">
                                            <label class="form-label">Mô tả</label>
                                            <textarea name="description" rows="5" class="form-control content-textarea-large">{{ $document->description }}</textarea>
                                        </div>
                                        <div class="content-form-field full">
                                            <label class="form-label">Đối tượng xem</label>
                                            <div class="content-target-panel" data-target-role-group>
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
    @if(auth()->user()->isStudent())
        @php
            $studentDocumentItems = method_exists($documents, 'items') ? $documents->items() : $documents;
            $groupedDocuments = collect($studentDocumentItems)
                ->groupBy(fn ($document) => $document->subject->name ?? 'Tài liệu chung');
        @endphp

        <div class="student-document-groups">
            @forelse($groupedDocuments as $subjectName => $subjectDocuments)
                <div class="card">
                    <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">{{ $subjectName }}</div>
                            <div class="text-muted small">{{ $subjectDocuments->count() }} tài liệu học tập</div>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($subjectDocuments as $document)
                            @php($detailId = 'document-detail-' . $document->id)
                            @php($documentFileUrl = $document->fileUrl())
                            <div class="list-group-item">
                                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold">{{ $document->title }}</div>
                                        <div class="text-muted small">
                                            {{ $document->category ?: 'Chưa phân nhóm' }}
                                            <span class="mx-1">•</span>
                                            {{ $document->classRoom->name ?? 'Dùng chung' }}
                                        </div>
                                        <div class="small mt-1">{{ \Illuminate\Support\Str::limit($document->description ?: 'Tài liệu được nhà trường chia sẻ.', 120, '...') }}</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 align-self-md-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#{{ $detailId }}">
                                            <i class="bi bi-eye me-1"></i>Xem nhanh
                                        </button>
                                        @if($documentFileUrl)
                                            <a href="{{ $documentFileUrl }}" target="_blank" class="btn btn-primary btn-sm">
                                                <i class="bi bi-download me-1"></i>Tải về
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card">
                    <div class="empty-state"><i class="bi bi-folder2-open"></i>Chưa có tài liệu học tập.</div>
                </div>
            @endforelse
        </div>
    @else
    <div class="content-grid">
        @forelse($documents as $document)
            @php($detailId = 'document-detail-' . $document->id)
            @php($documentFileUrl = $document->fileUrl())
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
                    @if($documentFileUrl)
                        <a href="{{ $documentFileUrl }}" target="_blank" class="btn btn-primary btn-sm">
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
    @endif

    @foreach($documents as $document)
        @php($detailId = 'document-detail-' . $document->id)
        @php($documentFileUrl = $document->fileUrl())
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
                            @if($documentFileUrl)
                                <div>
                                    <dt>Tệp tài liệu</dt>
                                    <dd><a href="{{ $documentFileUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">Mở tài liệu</a></dd>
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
