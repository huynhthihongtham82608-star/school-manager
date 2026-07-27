@php
    $parentModel = $parent ?? null;
    $isEdit = (bool) $parentModel;
    $currentRelation = old('relation', $parentModel?->students?->first()?->pivot?->relation ?? \App\Models\ParentProfile::RELATION_GUARDIAN);
    $selectedStudentIds = collect(old('student_ids', $parentModel?->students?->pluck('id')->all() ?? []));
    $parentCodeDisplay = old('parent_code', $parentModel?->parent_code) ?: ($nextParentCode ?? 'Tự sinh khi lưu');
    $isActive = $parentModel?->user?->is_active ?? true;
@endphp

<form method="POST" action="{{ $action }}" class="student-form-shell parent-form-shell in-modal">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" name="_parent_form_modal" value="{{ $isEdit ? 'parentEdit' . $parentModel->id : 'parentCreateModal' }}">

    <section class="student-form-section">
        <div class="student-form-section-head">
            <div>
                <h5><i class="bi bi-person-badge"></i> Thông tin tài khoản & Nhân thân</h5>
                <p>Mã phụ huynh, họ tên và quan hệ với học sinh trong hồ sơ liên kết.</p>
            </div>
            <span class="student-status-pill {{ $isActive ? 'bg-success' : 'bg-secondary' }}">{{ $isActive ? 'Hoạt động' : 'Chưa kích hoạt' }}</span>
        </div>

        <div class="student-form-grid">
            <div class="student-form-field">
                <label class="form-label">Mã phụ huynh</label>
                <div class="form-control bg-light text-muted">{{ $parentCodeDisplay }}</div>
            </div>

            <div class="student-form-field">
                <label class="form-label">Họ tên phụ huynh</label>
                <input class="form-control" name="name" value="{{ old('name', $parentModel?->name) }}" required>
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Quan hệ</label>
                <select class="form-select" name="relation" required>
                    @foreach(\App\Models\ParentProfile::relationLabels() as $value => $label)
                        <option value="{{ $value }}" @selected($currentRelation === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('relation')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="student-form-section">
        <div class="student-form-section-head">
            <div>
                <h5><i class="bi bi-telephone"></i> Thông tin liên hệ</h5>
                <p>Số điện thoại và địa chỉ liên hệ của phụ huynh.</p>
            </div>
        </div>

        <div class="student-form-grid">
            <div class="student-form-field">
                <label class="form-label">Số điện thoại</label>
                <input class="form-control" name="phone" value="{{ old('phone', $parentModel?->phone) }}" required>
                <div class="text-muted small mt-1">Số điện thoại được dùng làm tên đăng nhập.</div>
                @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field span-3">
                <label class="form-label">Địa chỉ</label>
                <textarea class="form-control" name="address" rows="2">{{ old('address', $parentModel?->address) }}</textarea>
                @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="student-form-section">
        <div class="student-form-section-head">
            <div>
                <h5><i class="bi bi-link-45deg"></i> Liên kết học sinh</h5>
                <p>Chọn một hoặc nhiều học sinh đang được phụ huynh quản lý.</p>
            </div>
        </div>

        <div class="student-parent-note">
            <i class="bi bi-lightbulb"></i>
            Nhập mã học sinh hoặc họ tên để tìm nhanh. Nếu số điện thoại đã tồn tại, hệ thống chỉ liên kết thêm học sinh.
        </div>

        <div class="student-form-grid">
            <div class="student-form-field span-3">
                <label class="form-label">Học sinh liên kết</label>
                <select class="form-select d-none" name="student_ids[]" multiple data-parent-student-select>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected($selectedStudentIds->contains($student->id))>
                            {{ $student->student_code }} - {{ $student->name }}{{ $student->classRoom ? ' - ' . $student->classRoom->name : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="parent-student-picker parent-form-picker" data-parent-student-picker>
                    <div class="parent-student-tags" data-parent-student-tags></div>
                    <input type="text" class="parent-student-search" data-parent-student-search placeholder="Tìm theo mã học sinh hoặc họ tên...">
                    <div class="parent-student-dropdown" data-parent-student-dropdown></div>
                </div>
                @error('student_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('student_ids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <div class="student-form-actions">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
        <button class="btn btn-primary">Lưu kết quả</button>
    </div>
</form>
