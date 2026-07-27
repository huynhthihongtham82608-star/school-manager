@php
    $teacherModel = $teacher ?? null;
    $isEdit = (bool) $teacherModel;
    $teacherCodeValue = old('teacher_code', $teacherModel?->teacher_code);
    $teacherCodeDisplay = $teacherCodeValue ?: ($nextTeacherCode ?? 'Tự sinh khi lưu');
    $qualificationOptions = ['Cao đẳng', 'Đại học', 'Thạc sĩ', 'Tiến sĩ'];
    $qualificationValue = old('qualification', $teacherModel?->qualification);
    $workStatusValue = old('work_status', $teacherModel?->work_status ?: \App\Models\Teacher::STATUS_WORKING);
    $workStatusLabel = \App\Models\Teacher::workStatuses()[$workStatusValue] ?? 'Đang công tác';
    $workStatusPillClass = $workStatusValue === \App\Models\Teacher::STATUS_RESIGNED ? 'bg-secondary' : 'bg-success';
@endphp

<form method="POST" action="{{ $action }}" class="student-form-shell teacher-form-shell in-modal">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" name="teacher_code" value="{{ $teacherCodeValue }}">
    <input type="hidden" name="_teacher_form_modal" value="{{ $isEdit ? 'teacherEdit' . $teacherModel->id : 'teacherCreateModal' }}">

    <section class="student-form-section">
        <div class="student-form-section-head">
            <div>
                <h5><i class="bi bi-briefcase"></i> Thông tin công tác</h5>
                <p>Mã giáo viên, bộ môn phụ trách, tổ chuyên môn và trạng thái làm việc.</p>
            </div>
            <span class="student-status-pill {{ $workStatusPillClass }}">{{ $workStatusLabel }}</span>
        </div>

        <div class="student-form-grid">
            <div class="student-form-field">
                <label class="form-label">Mã giáo viên</label>
                <div class="form-control bg-light text-muted">{{ $teacherCodeDisplay }}</div>
                @error('teacher_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Môn chính</label>
                <select name="primary_subject_id" class="form-select" required>
                    <option value="">Chọn môn chính</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected(old('primary_subject_id', $teacherModel?->primary_subject_id) === $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
                @error('primary_subject_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Tổ chuyên môn</label>
                <select name="department_id" class="form-select">
                    <option value="">Chưa phân tổ</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id', $teacherModel?->department_id) === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Ngày vào trường</label>
                <input type="date" name="joined_at" class="form-control" value="{{ old('joined_at', $teacherModel?->joined_at?->format('Y-m-d')) }}">
                @error('joined_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Trạng thái làm việc</label>
                <select name="work_status" class="form-select" required>
                    @foreach(\App\Models\Teacher::workStatuses() as $value => $label)
                        <option value="{{ $value }}" @selected($workStatusValue === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('work_status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="student-form-section">
        <div class="student-form-section-head">
            <div>
                <h5><i class="bi bi-person-vcard"></i> Thông tin cá nhân</h5>
                <p>Thông tin định danh và trình độ chuyên môn của giáo viên.</p>
            </div>
        </div>

        <div class="student-form-grid">
            <div class="student-form-field">
                <label class="form-label">Họ tên</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $teacherModel?->name) }}" required>
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Ngày sinh</label>
                <input type="date" name="dob" class="form-control" value="{{ old('dob', $teacherModel?->dob?->format('Y-m-d')) }}">
                @error('dob')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Giới tính</label>
                <select name="gender" class="form-select">
                    <option value="">Chọn giới tính</option>
                    @foreach(\App\Models\Teacher::genderLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $teacherModel?->gender) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Trình độ</label>
                <select name="qualification" class="form-select">
                    <option value="">Chọn trình độ</option>
                    @if($qualificationValue && ! in_array($qualificationValue, $qualificationOptions, true))
                        <option value="{{ $qualificationValue }}" selected>{{ $qualificationValue }}</option>
                    @endif
                    @foreach($qualificationOptions as $qualification)
                        <option value="{{ $qualification }}" @selected($qualificationValue === $qualification)>{{ $qualification }}</option>
                    @endforeach
                </select>
                @error('qualification')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="student-form-section">
        <div class="student-form-section-head">
            <div>
                <h5><i class="bi bi-envelope-at"></i> Thông tin liên hệ</h5>
                <p>Thông tin liên lạc phục vụ trao đổi công tác và quản lý hồ sơ.</p>
            </div>
        </div>

        <div class="student-form-grid">
            <div class="student-form-field">
                <label class="form-label">Thư điện tử</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $teacherModel?->email) }}">
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $teacherModel?->phone) }}">
                @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field span-3">
                <label class="form-label">Địa chỉ</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $teacherModel?->address) }}</textarea>
                @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <div class="student-form-actions">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
        <button class="btn btn-primary">Lưu kết quả</button>
    </div>
</form>
