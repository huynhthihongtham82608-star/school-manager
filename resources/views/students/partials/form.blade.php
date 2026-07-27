@php
    $studentModel = $student ?? null;
    $isEdit = (bool) $studentModel;
    $primaryParent = $primaryParent ?? $studentModel?->parents?->first();
    $ethnicityChoice = old('ethnicity_choice', in_array($studentModel?->ethnicity, [null, '', 'Kinh'], true) ? 'Kinh' : 'Khác');
    $religionChoice = old('religion_choice', in_array($studentModel?->religion, [null, '', 'Không'], true) ? 'Không' : 'Khác');
    $parentRelation = old('parent_relation', $primaryParent?->pivot?->relation ?? \App\Models\ParentProfile::RELATION_GUARDIAN);
    $parentPhone = old('parent_phone', $studentModel?->parent_phone ?: $primaryParent?->phone);
    $parentRequired = ! $isEdit;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="student-form-shell in-modal" data-student-form>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <section class="student-form-section">
        <div class="student-form-section-head">
            <div>
                <h5><i class="bi bi-mortarboard"></i> Thông tin học tập</h5>
                <p>Thông tin nhập học, lớp học và trạng thái hồ sơ.</p>
            </div>
            <span class="student-status-pill bg-success">Đang học</span>
        </div>

        <div class="student-form-grid">
            <div class="student-form-field">
                <label class="form-label">Mã học sinh</label>
                <div class="form-control bg-light text-muted">{{ $studentModel?->student_code ?: 'Tự sinh khi lưu' }}</div>
            </div>

            <div class="student-form-field">
                <label class="form-label">Lớp học</label>
                <select name="class_id" class="form-select" required data-student-class>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" data-year="{{ $class->school_year_id }}" @selected(old('class_id', $studentModel?->class_id) === $class->id)>
                            {{ $class->name }} - {{ $class->schoolYear->name ?? '' }} ({{ $class->currentStudentCount() }}/{{ $class->maxCapacity() }})
                        </option>
                    @endforeach
                </select>
                @error('class_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Ngày nhập học</label>
                <input type="date" name="enrollment_date" class="form-control" value="{{ old('enrollment_date', $studentModel?->enrollment_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                @error('enrollment_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Loại nhập học</label>
                <select name="admission_type" class="form-select" required data-admission-type>
                    @foreach(\App\Models\Student::admissionTypeLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(old('admission_type', $studentModel?->admission_type ?: \App\Models\Student::ADMISSION_NEW) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('admission_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select" required>
                    @foreach(\App\Models\Student::statuses() as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $studentModel?->status ?: \App\Models\Student::STATUS_STUDYING) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field" data-transfer-field>
                <label class="form-label">Trường cũ</label>
                <input type="text" name="previous_school" class="form-control" value="{{ old('previous_school', $studentModel?->previous_school) }}">
                @error('previous_school')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field" data-transfer-field>
                <label class="form-label">Khối hiện tại</label>
                <select name="transfer_grade_level" class="form-select">
                    <option value="">Theo lớp đang chọn</option>
                    @foreach([10, 11, 12] as $grade)
                        <option value="{{ $grade }}" @selected((string) old('transfer_grade_level', $studentModel?->transfer_grade_level) === (string) $grade)>Khối {{ $grade }}</option>
                    @endforeach
                </select>
                @error('transfer_grade_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="student-form-section">
        <div class="student-form-section-head">
            <div>
                <h5><i class="bi bi-person-vcard"></i> Thông tin cá nhân</h5>
                <p>Thông tin cá nhân cơ bản của học sinh.</p>
            </div>
        </div>

        <div class="student-form-grid">
            <div class="student-form-field">
                <label class="form-label">Họ tên</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $studentModel?->name) }}" required>
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Ngày sinh</label>
                <input type="date" name="dob" class="form-control" value="{{ old('dob', $studentModel?->dob?->format('Y-m-d')) }}">
                @error('dob')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Giới tính</label>
                <select name="gender" class="form-select" required>
                    @foreach(\App\Models\Student::genderLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $studentModel?->gender ?: \App\Models\Student::GENDER_NAM) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Nơi sinh</label>
                <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $studentModel?->place_of_birth) }}">
                @error('place_of_birth')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Dân tộc</label>
                <select name="ethnicity_choice" class="form-select" data-custom-toggle="ethnicity">
                    <option value="Kinh" @selected($ethnicityChoice === 'Kinh')>Kinh</option>
                    <option value="Khác" @selected($ethnicityChoice === 'Khác')>Khác</option>
                </select>
                @error('ethnicity_choice')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field" data-custom-field="ethnicity">
                <label class="form-label">Nhập dân tộc</label>
                <input type="text" name="ethnicity_custom" class="form-control" value="{{ $ethnicityChoice === 'Khác' ? old('ethnicity_custom', $studentModel?->ethnicity) : old('ethnicity_custom') }}">
                @error('ethnicity_custom')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Tôn giáo</label>
                <select name="religion_choice" class="form-select" data-custom-toggle="religion">
                    <option value="Không" @selected($religionChoice === 'Không')>Không</option>
                    <option value="Khác" @selected($religionChoice === 'Khác')>Khác</option>
                </select>
                @error('religion_choice')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field" data-custom-field="religion">
                <label class="form-label">Nhập tôn giáo</label>
                <input type="text" name="religion_custom" class="form-control" value="{{ $religionChoice === 'Khác' ? old('religion_custom', $studentModel?->religion) : old('religion_custom') }}">
                @error('religion_custom')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Ảnh đại diện</label>
                <input type="file" name="avatar" class="form-control student-file-input" accept="image/*">
                @if($studentModel?->avatar)
                    <div class="text-muted small mt-1">Đã có ảnh đại diện.</div>
                @endif
                @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="student-form-section">
        <div class="student-form-section-head">
            <div>
                <h5><i class="bi bi-people"></i> Thông tin gia đình & liên hệ</h5>
                <p>Thông tin phụ huynh và liên hệ khi cần trao đổi.</p>
            </div>
        </div>

        <div class="student-parent-note">
            <i class="bi bi-lightbulb"></i>
            Nếu số điện thoại đã tồn tại, hệ thống sẽ cập nhật liên kết học sinh với tài khoản phụ huynh tương ứng.
        </div>

        <div class="student-form-grid">
            <div class="student-form-field">
                <label class="form-label">Họ tên phụ huynh</label>
                <input type="text" name="parent_name" class="form-control" value="{{ old('parent_name', $primaryParent?->name) }}" @required($parentRequired)>
                @error('parent_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">Quan hệ</label>
                <select name="parent_relation" class="form-select" @required($parentRequired)>
                    @foreach(\App\Models\ParentProfile::relationLabels() as $value => $label)
                        <option value="{{ $value }}" @selected($parentRelation === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('parent_relation')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field">
                <label class="form-label">SĐT phụ huynh</label>
                <input type="text" name="parent_phone" class="form-control" value="{{ $parentPhone }}" @required($parentRequired)>
                @error('parent_phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field span-3">
                <label class="form-label">Địa chỉ phụ huynh</label>
                <textarea name="parent_address" class="form-control" rows="2">{{ old('parent_address', $primaryParent?->address) }}</textarea>
                @error('parent_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field span-3">
                <label class="form-label">Địa chỉ học sinh</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $studentModel?->address) }}</textarea>
                @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="student-form-field span-3">
                <label class="form-label">Ghi chú</label>
                <textarea name="note" class="form-control" rows="2">{{ old('note', $studentModel?->note) }}</textarea>
                @error('note')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <input type="hidden" name="school_year_id" value="{{ old('school_year_id', $studentModel?->school_year_id) }}" data-student-year>

    <div class="student-form-actions">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
        <button class="btn btn-primary">Lưu kết quả</button>
    </div>
</form>
