@extends('layouts.app')
@section('title', (auth()->user()->isStudent() || auth()->user()->isParent()) ? 'Điểm số' : (auth()->user()->isAdmin() ? 'Quản lý bảng điểm tập trung' : 'Nhập điểm số'))

@section('content')
@if(auth()->user()->isStudent() || auth()->user()->isParent())
    @php
        $detailLabels = $detailLabels ?? [];
        $formatScoreList = function ($score, array $types) use ($detailLabels) {
            $items = $score->details->filter(function ($detail) use ($types) {
                $normalizedType = match ($detail->type) {
                    'oral', 'quiz', 'test', 'regular' => 'regular',
                    'final', 'final_test' => 'final',
                    default => $detail->type,
                };

                return in_array($normalizedType, $types, true);
            });

            if ($items->isEmpty()) {
                return '<span class="text-muted">Chưa có</span>';
            }

            return $items
                ->sortBy(fn ($detail) => $detail->scoreColumn?->sort_order ?? 999)
                ->map(function ($detail) use ($detailLabels) {
                    $label = $detail->scoreColumn?->name ?: ($detail->name ?: ($detailLabels[$detail->type] ?? $detail->type));
                    $value = rtrim(rtrim(number_format((float) $detail->value, 2, '.', ''), '0'), '.');

                    return '<span class="score-chip"><span>' . e($label) . '</span><strong>' . e($value) . '</strong></span>';
                })
                ->implode('');
        };
    @endphp

    <x-page-header
        :title="auth()->user()->isParent() ? 'Điểm số của con' : 'Điểm số của tôi'"
        :subtitle="auth()->user()->isParent()
            ? 'Theo dõi điểm thành phần và điểm trung bình của học sinh đang chọn.'
            : 'Theo dõi điểm thành phần và điểm trung bình theo từng môn học.'"
    />

    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row gap-3 justify-content-between">
            <div>
                <div class="text-muted small">Học sinh</div>
                <div class="fw-bold">{{ $student?->student_code }} - {{ $student?->name }}</div>
            </div>
            <div>
                <div class="text-muted small">Lớp</div>
                <div class="fw-bold">{{ $student?->classRoom?->name ?? 'Chưa phân lớp' }}</div>
            </div>
            <div>
                <div class="text-muted small">Học kỳ</div>
                <div class="fw-bold">{{ $semesters->firstWhere('id', $selectedSemesterId)?->normalizedName() ?? 'Học kỳ hiện hành' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Môn học</th>
                        <th>Đánh giá thường xuyên</th>
                        <th>Đánh giá giữa kỳ</th>
                        <th>Đánh giá cuối kỳ</th>
                        <th>Điểm trung bình môn</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($studentScores as $score)
                    <tr>
                        <td class="fw-semibold">{{ $score->subject->name ?? '-' }}</td>
                        <td><div class="score-chip-list">{!! $formatScoreList($score, ['regular']) !!}</div></td>
                        <td><div class="score-chip-list">{!! $formatScoreList($score, ['midterm']) !!}</div></td>
                        <td><div class="score-chip-list">{!! $formatScoreList($score, ['final']) !!}</div></td>
                        <td>
                            @if($score->average !== null)
                                <span class="badge bg-info">{{ rtrim(rtrim(number_format($score->average, 2), '0'), '.') }}</span>
                            @else
                                <span class="text-muted">Chưa có</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state"><i class="bi bi-clipboard-data"></i>Chưa có dữ liệu điểm trong học kỳ này.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    @php
        $isScoreAdmin = auth()->user()->isAdmin() || auth()->user()->isStaff();
        $availableGrades = $classes->pluck('grade_level')->filter()->unique()->sort()->values();
    @endphp

    <x-page-header
        :title="$isScoreAdmin ? 'Quản lý bảng điểm tập trung' : 'Nhập điểm số học sinh'"
        :subtitle="$isScoreAdmin
            ? 'Tra cứu, giám sát tiến độ nhập điểm và phê duyệt yêu cầu sửa đổi điểm số của giáo viên toàn trường.'
            : 'Giáo viên bộ môn nhập điểm theo các cột điểm do Admin cấu hình.'"
    >
        @if(auth()->user()->hasPermission('scores.manage'))
            <a href="{{ route('score-columns.index') }}" class="btn btn-outline-primary score-config-link">
                <i class="bi bi-sliders"></i>
                Cấu hình cột điểm
            </a>
        @endif
    </x-page-header>

    <div class="score-entry-shell">
        <div class="score-entry-filter-card">
            <form method="GET" action="{{ route('scores.entry') }}" class="score-entry-filter" data-score-assignment-form>
                @if(! $isScoreAdmin && auth()->user()->isTeacher())
                    @php($firstAssignment = $assignments->first())
                    <input type="hidden" name="class_id" data-score-class-id value="{{ $firstAssignment?->class_id }}">
                    <input type="hidden" name="subject_id" data-score-subject-id value="{{ $firstAssignment?->subject_id }}">
                    <input type="hidden" name="semester_id" data-score-semester-id value="{{ $firstAssignment?->semester_id }}">
                    <div class="score-filter-field">
                        <label>Lớp</label>
                        <select class="form-select" data-score-assignment-class @disabled($assignments->isEmpty())>
                            @forelse($assignments as $assignment)
                                <option
                                    value="{{ $assignment->id }}"
                                    data-class-id="{{ $assignment->class_id }}"
                                    data-subject-id="{{ $assignment->subject_id }}"
                                    data-semester-id="{{ $assignment->semester_id }}"
                                >{{ $assignment->classRoom?->name ?? 'Không rõ lớp' }}</option>
                            @empty
                                <option value="">Chưa có lớp</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Môn</label>
                        <select class="form-select" data-score-assignment-subject @disabled($assignments->isEmpty())>
                            @forelse($assignments as $assignment)
                                <option
                                    value="{{ $assignment->id }}"
                                    data-class-id="{{ $assignment->class_id }}"
                                    data-subject-id="{{ $assignment->subject_id }}"
                                    data-semester-id="{{ $assignment->semester_id }}"
                                >{{ $assignment->subject?->name ?? 'Không rõ môn' }}</option>
                            @empty
                                <option value="">Chưa có môn</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Học kỳ</label>
                        <select class="form-select" data-score-assignment-semester @disabled($assignments->isEmpty())>
                            @forelse($assignments as $assignment)
                                <option
                                    value="{{ $assignment->id }}"
                                    data-class-id="{{ $assignment->class_id }}"
                                    data-subject-id="{{ $assignment->subject_id }}"
                                    data-semester-id="{{ $assignment->semester_id }}"
                                >{{ $assignment->semester?->normalizedName() ?? 'Không rõ học kỳ' }}</option>
                            @empty
                                <option value="">Chưa có học kỳ</option>
                            @endforelse
                        </select>
                    </div>
                @else
                    <div class="score-filter-field compact">
                        <label>Khối</label>
                        <select name="grade_level" class="form-select" data-score-admin-grade>
                            <option value="">Tất cả khối</option>
                            @foreach($availableGrades as $grade)
                                <option value="{{ $grade }}">Khối {{ $grade }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Lớp</label>
                        <select name="class_id" class="form-select" required data-score-admin-class>
                            <option value="">Chọn lớp</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" data-grade="{{ $class->grade_level }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Môn học</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Chọn môn học</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Giáo viên</label>
                        <select name="teacher_id" class="form-select" data-score-admin-teacher>
                            <option value="">Tất cả giáo viên</option>
                            @foreach(($teachers ?? collect()) as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->teacher_code ? ' - ' . $teacher->teacher_code : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="score-filter-field">
                        <label>Học kỳ</label>
                        <select name="semester_id" class="form-select" required data-score-admin-semester>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected($selectedSemesterId === $semester->id)>
                                    {{ $semester->normalizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <button class="btn score-open-sheet-btn" @disabled((! $isScoreAdmin && auth()->user()->isTeacher()) && $assignments->isEmpty())>
                    <i class="bi bi-box-arrow-in-right"></i>
                    {{ $isScoreAdmin ? 'Tra cứu bảng điểm' : 'Mở bảng nhập' }}
                </button>
            </form>
        </div>

        @if(! $isScoreAdmin)
            <div class="score-assignment-strip">
                <div class="score-assignment-heading">
                    <span>Phân công của bạn</span>
                    <small>Chọn nhanh lớp và môn để bắt đầu nhập điểm</small>
                </div>
                <div class="score-assignment-badges">
                    @forelse($assignments as $assignment)
                        <a
                            href="{{ route('scores.entry', [
                                'class_id' => $assignment->class_id,
                                'subject_id' => $assignment->subject_id,
                                'semester_id' => $assignment->semester_id,
                            ]) }}"
                            class="score-assignment-badge"
                            data-score-assignment-badge="{{ $assignment->id }}"
                        >
                            <strong>{{ $assignment->classRoom?->name ?? 'Không rõ lớp' }}</strong>
                            <span>{{ $assignment->subject?->name ?? 'Không rõ môn' }} · {{ $assignment->semester?->normalizedName() ?? 'Không rõ học kỳ' }}</span>
                        </a>
                    @empty
                        <div class="score-entry-empty">
                            <i class="bi bi-inbox"></i>
                            Thầy/cô vui lòng chọn Lớp và Môn học để bắt đầu nhập điểm.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    <div class="score-entry-empty-panel">
        <i class="bi bi-clipboard-data"></i>
        <span>
            {{ $isScoreAdmin
                ? 'Vui lòng lựa chọn Khối, Lớp và Môn học để tiến hành tra cứu bảng điểm số.'
                : 'Thầy/cô vui lòng chọn Lớp và Môn học để bắt đầu nhập điểm.' }}
        </span>
    </div>

    @if($isScoreAdmin)
        <script type="application/json" id="score-admin-assignments">
            {!! $assignments->map(fn ($assignment) => [
                'teacher_id' => (string) $assignment->teacher_id,
                'class_id' => (string) $assignment->class_id,
                'subject_id' => (string) $assignment->subject_id,
                'semester_id' => (string) $assignment->semester_id,
            ])->values()->toJson() !!}
        </script>
    @endif

    <script>
        document.querySelectorAll('[data-score-assignment-form]').forEach((form) => {
            const selects = [
                form.querySelector('[data-score-assignment-class]'),
                form.querySelector('[data-score-assignment-subject]'),
                form.querySelector('[data-score-assignment-semester]'),
            ].filter(Boolean);

            if (selects.length) {
                const classInput = form.querySelector('[data-score-class-id]');
                const subjectInput = form.querySelector('[data-score-subject-id]');
                const semesterInput = form.querySelector('[data-score-semester-id]');

                const syncAssignment = (source) => {
                    const selectedId = source?.value || selects[0]?.value || '';

                    selects.forEach((select) => {
                        select.value = selectedId;
                    });

                    const option = selects[0]?.selectedOptions[0];
                    classInput.value = option?.dataset.classId || '';
                    subjectInput.value = option?.dataset.subjectId || '';
                    semesterInput.value = option?.dataset.semesterId || '';
                };

                selects.forEach((select) => {
                    select.addEventListener('change', () => syncAssignment(select));
                });

                syncAssignment(selects[0]);
            }

            const gradeSelect = form.querySelector('[data-score-admin-grade]');
            const classSelect = form.querySelector('[data-score-admin-class]');
            const subjectSelect = form.querySelector('[name="subject_id"]');
            const teacherSelect = form.querySelector('[data-score-admin-teacher]');
            const semesterSelect = form.querySelector('[data-score-admin-semester]');
            const assignmentPayload = document.getElementById('score-admin-assignments')?.textContent || '[]';
            const adminAssignments = JSON.parse(assignmentPayload);

            const syncAdminFilters = () => {
                if (! classSelect || ! subjectSelect) {
                    return;
                }

                const grade = gradeSelect?.value || '';
                const teacherId = teacherSelect?.value || '';
                const semesterId = semesterSelect?.value || '';
                const scopedAssignments = teacherId
                    ? adminAssignments.filter((assignment) => assignment.teacher_id === teacherId && (! semesterId || assignment.semester_id === semesterId))
                    : [];
                const allowedClassIds = new Set(scopedAssignments.map((assignment) => assignment.class_id));
                const allowedSubjectIds = new Set(scopedAssignments.map((assignment) => assignment.subject_id));

                Array.from(classSelect.options).forEach((option) => {
                    if (! option.value) {
                        option.hidden = false;
                        return;
                    }

                    const matchesGrade = grade === '' || option.dataset.grade === grade;
                    const matchesTeacher = ! teacherId || allowedClassIds.has(option.value);
                    option.hidden = ! (matchesGrade && matchesTeacher);
                });

                Array.from(subjectSelect.options).forEach((option) => {
                    if (! option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = Boolean(teacherId) && ! allowedSubjectIds.has(option.value);
                });

                if (classSelect.selectedOptions[0]?.hidden) {
                    classSelect.value = '';
                }

                if (subjectSelect.selectedOptions[0]?.hidden) {
                    subjectSelect.value = '';
                }
            };

            if (gradeSelect && classSelect) {
                [gradeSelect, teacherSelect, semesterSelect].filter(Boolean).forEach((select) => {
                    select.addEventListener('change', syncAdminFilters);
                });
                syncAdminFilters();
            }
        });
    </script>
@endif
@endsection
