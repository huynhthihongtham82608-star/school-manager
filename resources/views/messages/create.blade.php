@extends('layouts.app')
@section('title', 'Soạn tin nhắn')

@section('content')
@php
    $oldRecipientIds = collect(old('recipient_user_ids', []))->map(fn ($id) => (string) $id)->all();
@endphp

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

<form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data" class="message-compose-grid">
    @csrf

    <section class="message-compose-card message-compose-main">
        <div class="message-compose-section-title">
            <h5>Soạn nội dung thư</h5>
            <p>Chọn phạm vi nhận, nhập tiêu đề, nội dung và tệp đính kèm nếu cần.</p>
        </div>

        <div class="message-compose-fields">
            <div class="message-compose-field">
                <label class="form-label">Kiểu người nhận</label>
                <select class="form-select" name="target_type" data-message-target required>
                    @foreach($targetTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('target_type', 'individual') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="message-compose-field message-target-panel" data-target-panel="individual">
                <label class="form-label">Người nhận</label>
                <input type="search" class="form-control" placeholder="Tìm nhanh người nhận..." data-recipient-search-main>
                <div class="message-selected-recipients" data-selected-recipients>
                    <span class="message-selected-empty">Chưa chọn người nhận</span>
                </div>
            </div>

            <div class="message-compose-field full message-target-panel d-none" data-target-panel="class">
                <label class="form-label">Chọn lớp</label>
                <select name="class_ids[]" class="form-select" multiple size="6">
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(in_array($class->id, old('class_ids', []), true))>{{ $class->name }} - Khối {{ $class->grade_level }}</option>
                    @endforeach
                </select>
                <div class="form-text">Giữ Ctrl để chọn nhiều lớp.</div>
            </div>

            <div class="message-compose-field full message-target-panel d-none" data-target-panel="grade">
                <label class="form-label">Chọn khối</label>
                <div class="message-grade-options">
                    @foreach([10, 11, 12] as $grade)
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="grade_levels[]" value="{{ $grade }}" @checked(in_array((string) $grade, old('grade_levels', []), true))>
                            <span class="form-check-label">Khối {{ $grade }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="message-compose-field full message-target-panel d-none" data-target-panel="group">
                <div class="alert alert-light border mb-0">
                    Hệ thống sẽ tự động gửi đến toàn bộ người dùng thuộc nhóm đã chọn.
                </div>
            </div>

            <div class="message-compose-field full">
                <label class="form-label">Tiêu đề</label>
                <input class="form-control" name="title" value="{{ old('title') }}" required maxlength="255">
            </div>

            <div class="message-compose-field full">
                <label class="form-label">Nội dung</label>
                <textarea class="form-control message-compose-textarea" name="content" rows="9" required>{{ old('content') }}</textarea>
            </div>

            <div class="message-compose-field full">
                <label class="form-label">Tệp đính kèm</label>
                <input class="form-control" type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                <div class="form-text">Hỗ trợ PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, JPEG. Tối đa 5 tệp, mỗi tệp 10MB.</div>
            </div>
        </div>

        <div class="message-compose-actions">
            <a class="btn btn-outline-secondary" href="{{ route('messages.inbox') }}">Hủy</a>
            <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Gửi thư ngay</button>
        </div>
    </section>

    <aside class="message-directory-card" data-message-directory>
        <div class="message-directory-title">
            <h5><i class="bi bi-folder2-open"></i>Danh bạ lối tắt trường học</h5>
            <p>Click vào tài khoản để thêm hoặc bỏ khỏi danh sách người nhận.</p>
        </div>
        <div class="message-directory-search">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" placeholder="Tìm nhanh người nhận..." data-recipient-search>
        </div>
        <div class="message-directory-list" data-recipient-list>
            @forelse($users as $user)
                @php
                    $checked = in_array((string) $user['id'], $oldRecipientIds, true);
                @endphp
                <label class="message-directory-option" data-recipient-option data-label="{{ $user['label'] }}" data-role="{{ $user['role'] }}" data-search="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($user['label'] . ' ' . $user['role'])) }}">
                    <input type="checkbox" name="recipient_user_ids[]" value="{{ $user['id'] }}" class="form-check-input" @checked($checked)>
                    <span class="message-directory-name">{{ $user['label'] }}</span>
                    <span class="message-directory-role">{{ $user['role'] }}</span>
                </label>
            @empty
                <div class="message-directory-empty">Không có người nhận phù hợp.</div>
            @endforelse
        </div>
    </aside>
</form>

<script>
    (() => {
        const target = document.querySelector('[data-message-target]');
        const panels = [...document.querySelectorAll('[data-target-panel]')];
        const directory = document.querySelector('[data-message-directory]');
        const groupTargets = ['all_teachers', 'all_homerooms', 'all_students', 'all_parents', 'school'];
        const recipientOptions = [...document.querySelectorAll('[data-recipient-option]')];
        const selectedBox = document.querySelector('[data-selected-recipients]');
        const searchInputs = [...document.querySelectorAll('[data-recipient-search], [data-recipient-search-main]')];

        const normalize = (value) => (value || '')
            .toLocaleLowerCase('vi')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();

        const syncPanels = () => {
            const value = target?.value;
            panels.forEach((panel) => panel.classList.add('d-none'));
            directory?.classList.toggle('is-muted', value !== 'individual');

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

        const syncSelectedRecipients = () => {
            if (!selectedBox) {
                return;
            }

            const selected = recipientOptions
                .filter((option) => option.querySelector('input')?.checked)
                .map((option) => ({ label: option.dataset.label, role: option.dataset.role }));

            selectedBox.innerHTML = '';

            if (!selected.length) {
                const empty = document.createElement('span');
                empty.className = 'message-selected-empty';
                empty.textContent = 'Chưa chọn người nhận';
                selectedBox.appendChild(empty);
                return;
            }

            selected.slice(0, 6).forEach((item) => {
                const chip = document.createElement('span');
                chip.className = 'message-selected-chip';
                chip.textContent = `${item.label} · ${item.role}`;
                selectedBox.appendChild(chip);
            });

            if (selected.length > 6) {
                const more = document.createElement('span');
                more.className = 'message-selected-chip muted';
                more.textContent = `+${selected.length - 6} người khác`;
                selectedBox.appendChild(more);
            }
        };

        const applySearch = (keyword) => {
            const normalized = normalize(keyword);
            searchInputs.forEach((input) => {
                if (document.activeElement !== input) {
                    input.value = keyword;
                }
            });
            recipientOptions.forEach((option) => {
                option.hidden = normalized && !option.dataset.search.includes(normalized);
            });
        };

        target?.addEventListener('change', syncPanels);
        recipientOptions.forEach((option) => {
            option.addEventListener('change', syncSelectedRecipients);
        });
        searchInputs.forEach((input) => {
            input.addEventListener('input', (event) => applySearch(event.target.value));
        });

        syncPanels();
        syncSelectedRecipients();
    })();
</script>
@endsection
