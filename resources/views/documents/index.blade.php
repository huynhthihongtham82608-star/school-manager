@extends('layouts.app')
@section('title', 'Tài liệu học tập')

@section('content')
<div class="page-heading">
    <div>
        <h5>Tài liệu học tập</h5>
        <div class="text-muted">Thư viện tài liệu phục vụ học tập và giảng dạy.</div>
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isStaff())
    <div class="card mb-3">
        <div class="card-header">Thêm tài liệu</div>
        <div class="card-body">
            <form method="POST" action="{{ route('documents.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6"><label class="form-label">Tên tài liệu</label><input name="title" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Môn học</label><select name="subject_id" class="form-select"><option value="">Tất cả</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Lớp</label><select name="class_id" class="form-select"><option value="">Tất cả</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Nhóm tài liệu</label><input name="category" class="form-control"></div>
                <div class="col-md-8"><label class="form-label">URL tài liệu</label><input name="file_url" class="form-control"></div>
                <div class="col-12"><label class="form-label">Mô tả</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                <div class="col-12"><button class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Thêm tài liệu</button></div>
            </form>
        </div>
    </div>
@endif

<div class="content-grid">
    @forelse($documents as $document)
        <article class="info-card">
            <span class="feature-card-icon mb-3"><i class="bi bi-file-earmark-text"></i></span>
            <h6>{{ $document->title }}</h6>
            <p>{{ $document->description ?: 'Tài liệu được nhà trường chia sẻ.' }}</p>
            <div class="small text-muted mb-3">
                {{ $document->subject->name ?? 'Tất cả môn học' }} · {{ $document->classRoom->name ?? 'Tất cả lớp' }}
            </div>
            @if($document->file_url)
                <a href="{{ $document->file_url }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Xem tài liệu</a>
            @endif
        </article>
    @empty
        <div class="card">
            <div class="empty-state"><i class="bi bi-folder2-open"></i>Chưa có tài liệu học tập.</div>
        </div>
    @endforelse
</div>

@if(method_exists($documents, 'links'))
    <div class="mt-3">{{ $documents->links() }}</div>
@endif
@endsection
