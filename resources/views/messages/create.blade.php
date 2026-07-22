@extends('layouts.app')
@section('title', 'Soạn tin nhắn')

@section('content')
<x-page-header
    title="Hộp thư điện tử"
    subtitle="Kênh tương tác, trao đổi thông tin chính thống giữa Nhà trường, Giáo viên và Phụ huynh học sinh."
>
    <a class="btn btn-outline-primary" href="{{ route('messages.inbox') }}">
        <i class="bi bi-arrow-left me-1"></i>Quay lại hộp thư
    </a>
</x-page-header>

@if(auth()->user()->isStudent())
    <div class="alert alert-light border d-flex gap-2 align-items-start">
        <i class="bi bi-info-circle text-primary mt-1"></i>
        <div>
            <div class="fw-semibold">Gửi thắc mắc đến giáo viên</div>
            <div class="small text-muted">Danh sách người nhận chỉ hiển thị giáo viên bộ môn đang dạy lớp và giáo viên chủ nhiệm của bạn.</div>
        </div>
    </div>
@elseif(auth()->user()->isParent())
    <div class="alert alert-light border d-flex gap-2 align-items-start">
        <i class="bi bi-info-circle text-primary mt-1"></i>
        <div>
            <div class="fw-semibold">Trao đổi với giáo viên của học sinh đang chọn</div>
            <div class="small text-muted">Danh sách người nhận chỉ hiển thị giáo viên chủ nhiệm và giáo viên bộ môn của học sinh đang chọn trên header.</div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-5">
                <label class="form-label">Kiểu người nhận</label>
                <select class="form-select" name="target_type" data-message-target required>
                    @foreach($targetTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('target_type', 'individual') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-7 message-target-panel" data-target-panel="individual">
                <label class="form-label">Người nhận</label>
                <input type="search" class="form-control mb-2" placeholder="Tìm theo mã hoặc họ tên..." data-recipient-search>
                <div class="border rounded-3 p-2 bg-light" style="max-height: 260px; overflow:auto;">
                    @foreach($users as $user)
                        <label class="d-flex align-items-start gap-2 py-2 px-2 rounded message-recipient-option" data-recipient-option data-search="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($user['label'] . ' ' . $user['role'])) }}">
                            <input type="checkbox" name="recipient_user_ids[]" value="{{ $user['id'] }}" class="form-check-input mt-1" @checked(in_array($user['id'], old('recipient_user_ids', []), true))>
                            <span>
                                <span class="fw-semibold">{{ $user['label'] }}</span>
                                <span class="badge bg-light text-dark border ms-1">{{ $user['role'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="col-md-7 message-target-panel d-none" data-target-panel="class">
                <label class="form-label">Chọn lớp</label>
                <select name="class_ids[]" class="form-select" multiple size="6">
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(in_array($class->id, old('class_ids', []), true))>{{ $class->name }} - Khối {{ $class->grade_level }}</option>
                    @endforeach
                </select>
                <div class="form-text">Giữ Ctrl để chọn nhiều lớp.</div>
            </div>

            <div class="col-md-7 message-target-panel d-none" data-target-panel="grade">
                <label class="form-label">Chọn khối</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach([10, 11, 12] as $grade)
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="grade_levels[]" value="{{ $grade }}" @checked(in_array((string) $grade, old('grade_levels', []), true))>
                            <span class="form-check-label">Khối {{ $grade }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="col-md-7 message-target-panel d-none" data-target-panel="group">
                <div class="alert alert-light border mb-0">
                    Hệ thống sẽ tự động gửi đến toàn bộ người dùng thuộc nhóm đã chọn.
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Tiêu đề</label>
                <input class="form-control" name="title" value="{{ old('title') }}" required maxlength="255">
            </div>

            <div class="col-12">
                <label class="form-label">Nội dung</label>
                <textarea class="form-control" name="content" rows="7" required>{{ old('content') }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label">Tệp đính kèm</label>
                <input class="form-control" type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                <div class="form-text">Hỗ trợ PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, JPEG. Tối đa 5 tệp, mỗi tệp 10MB.</div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('messages.inbox') }}">Hủy</a>
                <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Gửi tin</button>
            </div>
        </form>
    </div>
</div>

<script>
    (() => {
        const target = document.querySelector('[data-message-target]');
        const panels = [...document.querySelectorAll('[data-target-panel]')];
        const groupTargets = ['all_teachers', 'all_homerooms', 'all_students', 'all_parents', 'school'];

        const syncPanels = () => {
            const value = target.value;
            panels.forEach((panel) => panel.classList.add('d-none'));

            if (value === 'individual') {
                document.querySelector('[data-target-panel="individual"]')?.classList.remove('d-none');
            } else if (value === 'class') {
                document.querySelector('[data-target-panel="class"]')?.classList.remove('d-none');
            } else if (value === 'grade') {
                document.querySelector('[data-target-panel="grade"]')?.classList.remove('d-none');
            } else if (groupTargets.includes(value)) {
                document.querySelector('[data-target-panel="group"]')?.classList.remove('d-none');
            }
        };

        target?.addEventListener('change', syncPanels);
        syncPanels();

        document.querySelector('[data-recipient-search]')?.addEventListener('input', (event) => {
            const keyword = event.target.value.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
            document.querySelectorAll('[data-recipient-option]').forEach((option) => {
                option.hidden = keyword && !option.dataset.search.includes(keyword);
            });
        });
    })();
</script>
@endsection
