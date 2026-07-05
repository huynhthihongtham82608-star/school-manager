@extends('layouts.app')
@section('title', 'Sửa học sinh')

@section('content')
@php
    $currentEthnicity = old('ethnicity_custom', $student->ethnicity);
    $ethnicityChoice = old('ethnicity_choice', in_array($student->ethnicity, [null, '', 'Kinh'], true) ? 'Kinh' : 'Khác');
    $currentReligion = old('religion_custom', $student->religion);
    $religionChoice = old('religion_choice', in_array($student->religion, [null, '', 'Không'], true) ? 'Không' : 'Khác');
@endphp
<form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data" class="card p-4 shadow-sm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Mã học sinh</label>
            <div class="form-control bg-light fw-semibold">{{ $student->student_code }}</div>
        </div>
        <div class="col-md-5">
            <label class="form-label">Họ tên</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $student->name) }}" required>
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Giới tính</label>
            <select name="gender" class="form-select" required>
                @foreach(\App\Models\Student::genderLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender', $student->gender ?: \App\Models\Student::GENDER_NAM) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('gender')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Ngày sinh</label>
            <input type="date" name="dob" class="form-control" value="{{ old('dob', $student->dob?->format('Y-m-d')) }}">
            @error('dob')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Lớp</label>
            <select name="class_id" class="form-select" required data-student-class>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" data-year="{{ $class->school_year_id }}" @selected(old('class_id', $student->class_id) === $class->id)>
                        {{ $class->name }} - {{ $class->schoolYear->name ?? '' }} ({{ $class->currentStudentCount() }}/{{ $class->maxCapacity() }})
                    </option>
                @endforeach
            </select>
            @error('class_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Ngày nhập học</label>
            <input type="date" name="enrollment_date" class="form-control" value="{{ old('enrollment_date', $student->enrollment_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
            @error('enrollment_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Loại nhập học</label>
            <select name="admission_type" class="form-select" required data-admission-type>
                @foreach(\App\Models\Student::admissionTypeLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(old('admission_type', $student->admission_type ?: \App\Models\Student::ADMISSION_NEW) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('admission_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                @foreach(\App\Models\Student::statuses() as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $student->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" data-transfer-field>
            <label class="form-label">Trường cũ</label>
            <input type="text" name="previous_school" class="form-control" value="{{ old('previous_school', $student->previous_school) }}">
            @error('previous_school')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" data-transfer-field>
            <label class="form-label">Khối hiện tại</label>
            <select name="transfer_grade_level" class="form-select">
                <option value="">Theo lớp đang chọn</option>
                @foreach([10, 11, 12] as $grade)
                    <option value="{{ $grade }}" @selected((string) old('transfer_grade_level', $student->transfer_grade_level) === (string) $grade)>Khối {{ $grade }}</option>
                @endforeach
            </select>
            @error('transfer_grade_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Nơi sinh</label>
            <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $student->place_of_birth) }}">
            @error('place_of_birth')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Dân tộc</label>
            <select name="ethnicity_choice" class="form-select" data-custom-toggle="ethnicity">
                <option value="Kinh" @selected($ethnicityChoice === 'Kinh')>Kinh</option>
                <option value="Khác" @selected($ethnicityChoice === 'Khác')>Khác</option>
            </select>
            @error('ethnicity_choice')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" data-custom-field="ethnicity">
            <label class="form-label">Nhập dân tộc</label>
            <input type="text" name="ethnicity_custom" class="form-control" value="{{ $ethnicityChoice === 'Khác' ? $currentEthnicity : '' }}">
            @error('ethnicity_custom')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Tôn giáo</label>
            <select name="religion_choice" class="form-select" data-custom-toggle="religion">
                <option value="Không" @selected($religionChoice === 'Không')>Không</option>
                <option value="Khác" @selected($religionChoice === 'Khác')>Khác</option>
            </select>
            @error('religion_choice')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4" data-custom-field="religion">
            <label class="form-label">Nhập tôn giáo</label>
            <input type="text" name="religion_custom" class="form-control" value="{{ $religionChoice === 'Khác' ? $currentReligion : '' }}">
            @error('religion_custom')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Ảnh đại diện</label>
            <input type="file" name="avatar" class="form-control" accept="image/*">
            @if($student->avatar)
                <div class="text-muted small mt-1">Đã có ảnh đại diện.</div>
            @endif
            @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">SĐT phụ huynh</label>
            <input type="text" name="parent_phone" class="form-control" value="{{ old('parent_phone', $student->parent_phone) }}">
            @error('parent_phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Email phụ huynh</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}">
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Địa chỉ</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $student->address) }}">
            @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Ghi chú</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note', $student->note) }}</textarea>
            @error('note')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <input type="hidden" name="school_year_id" value="{{ old('school_year_id', $student->school_year_id) }}" data-student-year>
    <div class="form-actions mt-4">
        <a href="{{ route('students.index', ['school_year_id' => $student->school_year_id]) }}" class="btn btn-secondary">Hủy</a>
        <button class="btn btn-primary">Cập nhật</button>
    </div>
</form>

@include('students.partials.class-year-script')
@endsection
