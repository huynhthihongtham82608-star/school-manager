<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Conduct;
use App\Models\ParentLeaveRequest;
use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\ScoreColumn;
use App\Models\ScoreDetail;
use App\Models\ScoreHeader;
use App\Models\ScoreSetting;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentClassAssignment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherDepartment;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\SimpleExcel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BulkExcelController extends Controller
{
    private const MODULES = ['students', 'teachers', 'parents', 'scores', 'conduct', 'attendance'];

    public function template(Request $request, string $module)
    {
        $module = $this->normalizeModule($module);
        $this->authorizeExport($module);

        [$headers, $rows, $filename] = match ($module) {
            'students' => $this->studentTemplate($request),
            'teachers' => $this->teacherTemplate(),
            'parents' => $this->parentTemplate(),
            'scores' => $this->scoreTemplate($request),
            'conduct' => $this->conductTemplate($request),
            'attendance' => $this->attendanceTemplate($request),
        };

        return SimpleExcel::downloadXlsx($filename, $headers, $rows);
    }

    public function export(Request $request, string $module)
    {
        $module = $this->normalizeModule($module);
        $this->authorizeExport($module);

        [$headers, $rows, $filename] = $this->exportDataset($request, $module);
        $selectedIndexes = $this->selectedFieldIndexes($request, count($headers));
        if ($selectedIndexes !== []) {
            [$headers, $rows] = $this->filterExportColumns($headers, $rows, $selectedIndexes);
        }

        if ($request->query('format') === 'pdf') {
            return SimpleExcel::downloadPdf($this->filenameWithExtension($filename, 'pdf'), $headers, $rows);
        }

        return SimpleExcel::downloadXlsx($this->filenameWithExtension($filename, 'xlsx'), $headers, $rows);
    }

    public function fields(Request $request, string $module)
    {
        $module = $this->normalizeModule($module);
        $this->authorizeExport($module);

        [$headers] = $this->exportDataset($request, $module);

        return response()->json([
            'module' => $module,
            'fields' => $this->fieldOptions($headers),
        ]);
    }

    public function preview(Request $request, string $module)
    {
        $module = $this->normalizeModule($module);
        $this->authorizeExport($module);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $rows = SimpleExcel::readRows($validated['file']);
        if ($rows === []) {
            return response()->json([
                'message' => 'File Excel không có dữ liệu hợp lệ.',
            ], 422);
        }

        $context = $this->context($request);
        $result = $this->validateRows($module, $rows, $context);
        $token = (string) Str::uuid();

        session()->put('bulk_excel.' . $token, [
            'module' => $module,
            'rows' => $rows,
            'context' => $context,
            'created_at' => now()->timestamp,
        ]);

        return response()->json([
            'token' => $token,
            'module' => $module,
            'headers' => $result['headers'],
            'rows' => $result['rows'],
            'valid' => $result['valid'],
            'error_count' => $result['error_count'],
        ]);
    }

    public function commit(Request $request, string $module)
    {
        $module = $this->normalizeModule($module);
        $token = (string) $request->input('token');
        $draft = session('bulk_excel.' . $token);

        if (! is_array($draft) || ($draft['module'] ?? null) !== $module) {
            return response()->json(['message' => 'Phiên import đã hết hạn, vui lòng chọn lại file.'], 422);
        }

        $this->authorizeCommit($module, $draft['context'] ?? []);
        $result = $this->validateRows($module, $draft['rows'] ?? [], $draft['context'] ?? []);

        if (! $result['valid']) {
            return response()->json([
                'message' => 'File vẫn còn ô dữ liệu lỗi, chưa thể nạp vào hệ thống.',
                'headers' => $result['headers'],
                'rows' => $result['rows'],
                'valid' => false,
                'error_count' => $result['error_count'],
            ], 422);
        }

        $affected = DB::transaction(fn () => match ($module) {
            'students' => $this->commitStudents($draft['rows'], $draft['context']),
            'teachers' => $this->commitTeachers($draft['rows']),
            'parents' => $this->commitParents($draft['rows']),
            'scores' => $this->commitScores($draft['rows'], $draft['context']),
            'conduct' => $this->commitConduct($draft['rows'], $draft['context']),
            'attendance' => $this->commitAttendance($draft['rows'], $draft['context']),
        });

        session()->forget('bulk_excel.' . $token);
        AuditLogger::log('bulk_excel_imported', null, null, 'Nạp Excel phân hệ ' . $module . ': ' . $affected . ' dòng');

        return response()->json([
            'message' => 'Đã nạp thành công ' . $affected . ' dòng dữ liệu.',
            'affected' => $affected,
            'redirect' => $this->moduleRedirect($module, $draft['context'] ?? []),
        ]);
    }

    private function normalizeModule(string $module): string
    {
        abort_unless(in_array($module, self::MODULES, true), 404);

        return $module;
    }

    private function authorizeExport(string $module): void
    {
        $user = auth()->user();

        if (in_array($module, ['students', 'teachers', 'parents'], true)) {
            abort_unless($user?->isAdmin() || $user?->isStaff(), 403);
            return;
        }

        abort_unless($user && in_array($user->role, ['admin', 'staff', 'teacher'], true), 403);
    }

    private function authorizeCommit(string $module, array $context): void
    {
        $user = auth()->user();

        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages(['file' => 'Đang xem dữ liệu lịch sử, không thể nạp Excel.']);
        }

        match ($module) {
            'students', 'teachers', 'parents' => abort_unless($user?->isAdmin() || $user?->isStaff(), 403),
            'scores' => $this->authorizeScoreCommit($context),
            'conduct' => $this->authorizeConductCommit($context),
            'attendance' => $this->authorizeAttendanceCommit($context),
        };
    }

    private function authorizeScoreCommit(array $context): void
    {
        $user = auth()->user();
        $classId = $context['class_id'] ?? null;
        $subjectId = $context['subject_id'] ?? null;
        $semesterId = $context['semester_id'] ?? null;
        abort_unless($user?->isTeacher() && $classId && $subjectId && $semesterId, 403);

        $assigned = TeachingAssignment::query()
            ->where('teacher_id', $user->teacher_id)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('status', TeachingAssignment::STATUS_ACTIVE)
            ->where(function ($query) use ($semesterId) {
                $query->whereNull('semester_id')->orWhere('semester_id', $semesterId);
            })
            ->exists();

        abort_unless($assigned, 403);
    }

    private function authorizeConductCommit(array $context): void
    {
        $user = auth()->user();
        $classId = $context['class_id'] ?? null;
        abort_unless($user?->isHomeroom() && $classId, 403);

        $ownsClass = $user->teacher?->homeroomClasses()
            ->whereKey($classId)
            ->exists();

        abort_unless($ownsClass, 403);
    }

    private function authorizeAttendanceCommit(array $context): void
    {
        $this->authorizeConductCommit($context);

        $date = Carbon::parse($context['attendance_date'] ?? now()->toDateString())->startOfDay();
        if (now()->gt($date->copy()->addHours(24))) {
            throw ValidationException::withMessages(['file' => 'Phiên điểm danh đã quá 24 giờ, hệ thống đã đóng sổ.']);
        }
    }

    private function context(Request $request): array
    {
        return [
            'school_year_id' => $request->input('school_year_id') ?: $this->selectedSchoolYearId($request),
            'semester_id' => $request->input('semester_id') ?: $this->selectedSemesterId($request),
            'class_id' => $request->input('class_id'),
            'subject_id' => $request->input('subject_id'),
            'attendance_date' => $request->input('attendance_date') ?: $request->input('date') ?: now()->toDateString(),
            'attendance_type' => $request->input('attendance_type') ?: AttendanceRecord::SESSION_MORNING,
        ];
    }

    private function validateRows(string $module, array $rows, array $context): array
    {
        return match ($module) {
            'students' => $this->validateStudentRows($rows, $context),
            'teachers' => $this->validateTeacherRows($rows),
            'parents' => $this->validateParentRows($rows),
            'scores' => $this->validateScoreRows($rows, $context),
            'conduct' => $this->validateConductRows($rows, $context),
            'attendance' => $this->validateAttendanceRows($rows, $context),
        };
    }

    private function exportDataset(Request $request, string $module): array
    {
        return match ($module) {
            'students' => $this->studentDataExport($request),
            'teachers' => $this->teacherDataExport($request),
            'parents' => $this->parentDataExport(),
            'scores' => $this->scoreDataExport($request),
            'conduct' => $this->conductDataExport($request),
            'attendance' => $this->attendanceDataExport($request),
        };
    }

    private function fieldOptions(array $headers): array
    {
        return collect($headers)
            ->values()
            ->map(fn (string $label, int $index) => [
                'index' => $index,
                'label' => $label,
                'selected' => $this->isDefaultExportField($label, $index),
            ])
            ->all();
    }

    private function selectedFieldIndexes(Request $request, int $columnCount): array
    {
        $fields = $request->query('fields', []);
        $fields = is_array($fields) ? $fields : [$fields];

        return collect($fields)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $index) => $index >= 0 && $index < $columnCount)
            ->unique()
            ->values()
            ->all();
    }

    private function filterExportColumns(array $headers, array $rows, array $indexes): array
    {
        if ($indexes === []) {
            return [$headers, $rows];
        }

        $headers = collect($indexes)->map(fn (int $index) => $headers[$index] ?? null)->filter()->values()->all();
        $rows = collect($rows)
            ->map(fn (array $row) => collect($indexes)->map(fn (int $index) => $row[$index] ?? '—')->values()->all())
            ->values()
            ->all();

        return [$headers, $rows];
    }

    private function isDefaultExportField(string $label, int $index): bool
    {
        $normalized = SimpleExcel::normalizeHeader($label);

        return $index < 4 || in_array($normalized, [
            'ma_so',
            'ma_hs',
            'ma_hoc_sinh',
            'ma_gv',
            'ma_giao_vien',
            'ma_phu_huynh',
            'ho_ten',
            'ho_va_ten',
            'gioi_tinh',
            'ngay_sinh',
        ], true);
    }

    private function filenameWithExtension(string $filename, string $extension): string
    {
        return preg_replace('/\.[A-Za-z0-9]+$/', '', $filename) . '.' . $extension;
    }

    private function buildPreview(array $headers, array $rows, callable $validator): array
    {
        $previewRows = [];
        $errorCount = 0;

        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            foreach ($headers as $header) {
                $key = $header['key'];
                $value = trim((string) $this->rowValue($row, array_merge([$key], $header['aliases'] ?? [])));
                $error = $validator($key, $value, $row, $rowIndex);
                if ($error) {
                    $errorCount++;
                }
                $cells[] = ['key' => $key, 'value' => $value, 'error' => $error];
            }
            $previewRows[] = ['index' => $rowIndex + 2, 'cells' => $cells];
        }

        return [
            'headers' => array_map(fn ($header) => ['key' => $header['key'], 'label' => $header['label']], $headers),
            'rows' => $previewRows,
            'valid' => $errorCount === 0,
            'error_count' => $errorCount,
        ];
    }

    private function validateStudentRows(array $rows, array $context): array
    {
        $headers = $this->studentHeaders();

        return $this->buildPreview($headers, $rows, function (string $key, string $value, array $row) use ($context) {
            if ($key === 'ho_ten' && $value === '') {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'gioi_tinh' && $value !== '' && ! in_array($this->normalizeText($value), ['nam', 'nu'], true)) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'lop' && ! $this->resolveClass($value, $context['class_id'] ?? null)) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'ngay_sinh' && $value !== '' && ! $this->tryParseDate($value)) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'trang_thai' && $value !== '' && ! in_array($this->normalizeText($value), ['dang_hoc', 'studying', 'bao_luu', 'reserved', 'chuyen_truong', 'transferred', 'tot_nghiep', 'graduated', 'nghi_hoc', 'dropped', 'inactive'], true)) {
                return 'Lỗi dữ liệu';
            }

            return null;
        });
    }

    private function validateTeacherRows(array $rows): array
    {
        $headers = $this->teacherHeaders();

        return $this->buildPreview($headers, $rows, function (string $key, string $value) {
            if ($key === 'ho_ten' && $value === '') {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'gioi_tinh' && $value !== '' && ! in_array($this->normalizeText($value), ['nam', 'nu'], true)) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'email' && $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'mon_chinh' && ($value === '' || ! $this->resolveSubject($value))) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'to_chuyen_mon' && $value !== '' && ! $this->resolveDepartment($value)) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'trang_thai' && $value !== '' && ! in_array($this->normalizeText($value), ['dang_cong_tac', 'working', 'nghi_viec', 'resigned'], true)) {
                return 'Lỗi dữ liệu';
            }

            return null;
        });
    }

    private function validateParentRows(array $rows): array
    {
        $headers = $this->parentHeaders();

        return $this->buildPreview($headers, $rows, function (string $key, string $value, array $row) {
            if ($key === 'ho_ten' && $value === '') {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'sdt' && $value === '') {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'sdt' && $this->parentPhoneConflicts($value, (string) $this->rowValue($row, ['ma_phu_huynh', 'parent_code']))) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'email' && $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'ma_hs_lien_ket') {
                $studentCodes = $this->linkedStudentCodes($value);
                if ($studentCodes === []) {
                    return 'Lỗi dữ liệu';
                }
                foreach ($studentCodes as $studentCode) {
                    if (! Student::where('student_code', $studentCode)->orWhere('id', $studentCode)->exists()) {
                        return 'Lỗi dữ liệu';
                    }
                }
            }

            return null;
        });
    }

    private function validateScoreRows(array $rows, array $context): array
    {
        $class = SchoolClass::find($context['class_id'] ?? null);
        $subject = Subject::find($context['subject_id'] ?? null);
        $semester = Semester::find($context['semester_id'] ?? null);

        $headers = $this->scoreHeaders($class, $subject, $semester);
        $columns = ($class && $subject && $semester) ? $this->scoreColumns($class, $subject, $semester) : collect();

        return $this->buildPreview($headers, $rows, function (string $key, string $value) use ($class, $subject, $columns) {
            if ($key === 'ma_hs' && $class && ! $this->studentInClass($value, $class)) {
                return 'Lỗi dữ liệu';
            }

            if (in_array($key, ['ma_hs', 'ho_ten'], true) || $value === '') {
                return null;
            }

            if ($columns->isNotEmpty()) {
                $column = $columns->first(fn (ScoreColumn $candidate) => $this->scoreColumnImportKey($candidate) === $key);
                if ($column && ! $column->isInputOpen()) {
                    return 'Lỗi dữ liệu';
                }
            }

            if ($subject && $subject->usesPassFailAssessment()) {
                return in_array($this->normalizeText($value), ['dat', 'd', 'chua_dat', 'cd', 'khong_dat'], true) ? null : 'Lỗi dữ liệu';
            }

            return is_numeric($value) && (float) $value >= 0 && (float) $value <= 10 ? null : 'Lỗi dữ liệu';
        });
    }

    private function validateConductRows(array $rows, array $context): array
    {
        $class = SchoolClass::find($context['class_id'] ?? null);
        $headers = $this->conductHeaders();
        $allowed = ['tot', 'kha', 'dat', 'chua_dat', 'excellent', 'good', 'average', 'weak'];

        return $this->buildPreview($headers, $rows, function (string $key, string $value) use ($class, $allowed) {
            if ($key === 'ma_hs' && (! $class || ! $this->studentInClass($value, $class))) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'xep_loai' && ! in_array($this->normalizeText($value), $allowed, true)) {
                return 'Lỗi dữ liệu';
            }

            return null;
        });
    }

    private function validateAttendanceRows(array $rows, array $context): array
    {
        $class = SchoolClass::find($context['class_id'] ?? null);
        $headers = $this->attendanceHeaders();
        $allowed = ['co_mat', 'present', 'v', 'di_muon', 'late', 'm', 'vang_mat', 'vang_khong_phep', 'absent', 'x', 'nghi_co_phep', 'excused', 'p'];

        return $this->buildPreview($headers, $rows, function (string $key, string $value) use ($class, $allowed) {
            if ($key === 'ma_hs' && (! $class || ! $this->studentInClass($value, $class))) {
                return 'Lỗi dữ liệu';
            }
            if ($key === 'trang_thai' && ! in_array($this->normalizeText($value), $allowed, true)) {
                return 'Lỗi dữ liệu';
            }

            return null;
        });
    }

    private function invalidContextPreview(array $headers, array $rows): array
    {
        return $this->buildPreview($headers, $rows, fn () => 'Lỗi dữ liệu');
    }

    private function commitStudents(array $rows, array $context): int
    {
        $affected = 0;
        foreach ($rows as $row) {
            $class = $this->resolveClass((string) $this->rowValue($row, ['lop', 'class_id']), $context['class_id'] ?? null);
            if (! $class) {
                continue;
            }

            $code = trim((string) $this->rowValue($row, ['ma_hs', 'ma_hoc_sinh', 'student_code']));
            $student = $code !== '' ? Student::where('student_code', $code)->first() : null;
            $student ??= new Student(['student_code' => $code !== '' ? $code : $this->nextStudentCode($this->rowValue($row, ['ngay_nhap_hoc', 'enrollment_date']))]);
            $student->fill([
                'name' => trim((string) $this->rowValue($row, ['ho_ten', 'ho_va_ten', 'name'])),
                'dob' => $this->parseDate($this->rowValue($row, ['ngay_sinh', 'dob'])),
                'gender' => $this->normalizeGender($this->rowValue($row, ['gioi_tinh', 'gender'], 'Nam')),
                'address' => trim((string) $this->rowValue($row, ['dia_chi', 'dia_chi_thuong_tru', 'address'])) ?: null,
                'place_of_birth' => trim((string) $this->rowValue($row, ['noi_sinh', 'place_of_birth'])) ?: null,
                'ethnicity' => trim((string) $this->rowValue($row, ['dan_toc', 'ethnicity'])) ?: null,
                'religion' => trim((string) $this->rowValue($row, ['ton_giao', 'religion'])) ?: null,
                'parent_phone' => trim((string) $this->rowValue($row, ['sdt_phu_huynh', 'parent_phone'])) ?: null,
                'email' => trim((string) $this->rowValue($row, ['email'])) ?: null,
                'class_id' => $class->id,
                'school_year_id' => $class->school_year_id,
                'enrollment_date' => $this->parseDate($this->rowValue($row, ['ngay_nhap_hoc', 'enrollment_date'])) ?: now()->toDateString(),
                'admission_type' => $this->normalizeAdmissionType($this->rowValue($row, ['admission_type', 'loai_nhap_hoc'])),
                'previous_school' => trim((string) $this->rowValue($row, ['previous_school', 'truong_cu'])) ?: null,
                'transfer_grade_level' => trim((string) $this->rowValue($row, ['transfer_grade_level', 'khoi_hien_tai'])) ?: null,
                'previous_class' => trim((string) $this->rowValue($row, ['previous_class', 'lop_cu'])) ?: null,
                'note' => trim((string) $this->rowValue($row, ['note', 'ghi_chu'])) ?: null,
                'status' => $this->normalizeStudentStatus($this->rowValue($row, ['trang_thai', 'status'])),
            ]);
            if (Schema::hasColumn('students', 'student_phone')) {
                $student->setAttribute('student_phone', trim((string) $this->rowValue($row, ['sdt_hoc_sinh', 'student_phone', 'phone'])) ?: null);
            }
            if (Schema::hasColumn('students', 'hometown')) {
                $student->setAttribute('hometown', trim((string) $this->rowValue($row, ['que_quan', 'hometown'])) ?: null);
            }
            if (Schema::hasColumn('students', 'native_place')) {
                $student->setAttribute('native_place', trim((string) $this->rowValue($row, ['que_quan', 'native_place'])) ?: null);
            }
            $student->save();

            StudentClassAssignment::updateOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $class->school_year_id],
                ['class_id' => $class->id, 'status' => StudentClassAssignment::STATUS_ACTIVE]
            );
            $this->syncImportedParentForStudent($student, $row);
            $this->ensureStudentUser($student);
            $affected++;
        }

        return $affected;
    }

    private function commitTeachers(array $rows): int
    {
        $affected = 0;
        foreach ($rows as $row) {
            $subject = $this->resolveSubject((string) $this->rowValue($row, ['mon_chinh', 'mon_giang_day', 'primary_subject_id', 'main_subject']));
            $department = $this->resolveDepartment((string) $this->rowValue($row, ['to_chuyen_mon', 'department_id']));
            $code = trim((string) $this->rowValue($row, ['ma_gv', 'ma_giao_vien', 'teacher_code']));
            $teacher = $code !== '' ? Teacher::where('teacher_code', $code)->first() : null;
            $teacher ??= new Teacher(['teacher_code' => $code !== '' ? $code : $this->nextTeacherCode()]);
            $teacher->fill([
                'name' => trim((string) $this->rowValue($row, ['ho_ten', 'ho_va_ten', 'name'])),
                'dob' => $this->parseDate($this->rowValue($row, ['ngay_sinh', 'dob'])),
                'gender' => $this->normalizeGender($this->rowValue($row, ['gioi_tinh', 'gender'])),
                'phone' => trim((string) $this->rowValue($row, ['sdt', 'so_dien_thoai', 'phone'])) ?: null,
                'email' => trim((string) $this->rowValue($row, ['email'])) ?: null,
                'address' => trim((string) $this->rowValue($row, ['dia_chi', 'address'])) ?: null,
                'qualification' => trim((string) $this->rowValue($row, ['trinh_do', 'qualification'])) ?: null,
                'work_status' => $this->normalizeTeacherStatus($this->rowValue($row, ['trang_thai', 'work_status'])),
                'primary_subject_id' => $subject?->id,
                'department_id' => $department?->id,
                'main_subject' => $subject?->name ?: trim((string) $this->rowValue($row, ['mon_chinh', 'mon_giang_day', 'main_subject'])),
            ]);
            $teacher->save();
            $this->ensureTeacherUser($teacher);
            $affected++;
        }

        return $affected;
    }

    private function commitParents(array $rows): int
    {
        $affected = 0;

        foreach ($rows as $row) {
            $code = trim((string) $this->rowValue($row, ['ma_phu_huynh', 'parent_code']));
            $phone = trim((string) $this->rowValue($row, ['sdt', 'so_dien_thoai', 'phone']));
            $parent = $code !== '' ? ParentProfile::where('parent_code', $code)->first() : null;
            $parent ??= $phone !== '' ? ParentProfile::where('phone', $phone)->first() : null;
            $parent ??= new ParentProfile(['parent_code' => $code !== '' ? $code : $this->nextParentCode()]);

            $parent->fill([
                'name' => trim((string) $this->rowValue($row, ['ho_ten', 'ho_va_ten', 'name'])),
                'phone' => $phone,
                'email' => trim((string) $this->rowValue($row, ['email'])) ?: null,
                'address' => trim((string) $this->rowValue($row, ['dia_chi', 'address'])) ?: null,
            ]);

            if (Schema::hasColumn('parents', 'occupation')) {
                $parent->setAttribute('occupation', trim((string) $this->rowValue($row, ['nghe_nghiep', 'occupation'])) ?: null);
            }

            $parent->save();
            $this->syncParentStudentCodes($parent, (string) $this->rowValue($row, ['ma_hs_lien_ket', 'student_code', 'student_id']));
            $this->ensureParentUser($parent);
            $affected++;
        }

        return $affected;
    }

    private function commitScores(array $rows, array $context): int
    {
        $class = SchoolClass::findOrFail($context['class_id']);
        $subject = Subject::findOrFail($context['subject_id']);
        $semester = Semester::findOrFail($context['semester_id']);
        $columns = $this->scoreColumns($class, $subject, $semester);
        $affected = 0;

        foreach ($rows as $row) {
            $student = $this->studentInClass((string) $this->rowValue($row, ['ma_hs', 'ma_hoc_sinh', 'student_code', 'student_id']), $class);
            if (! $student) {
                continue;
            }

            $header = ScoreHeader::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'semester_id' => $semester->id,
                    'school_year_id' => $semester->school_year_id,
                ],
                ['average' => null]
            );

            foreach ($columns as $column) {
                $key = $this->scoreColumnImportKey($column);
                $value = trim((string) ($row[$key] ?? ''));
                if ($value === '' || ! $column->isInputOpen()) {
                    continue;
                }

                $numericValue = $subject->usesPassFailAssessment()
                    ? (in_array($this->normalizeText($value), ['dat', 'd'], true) ? 1 : 0)
                    : (float) $value;

                ScoreDetail::updateOrCreate(
                    ['score_header_id' => $header->id, 'score_column_id' => $column->id],
                    [
                        'type' => $column->type,
                        'name' => $column->name,
                        'value' => $numericValue,
                        'weight_group' => $column->weight_group,
                    ]
                );
                $affected++;
            }

            $this->recalculateAverage($header->fresh(['subject', 'details.scoreColumn']));
        }

        return $affected;
    }

    private function commitConduct(array $rows, array $context): int
    {
        $class = SchoolClass::findOrFail($context['class_id']);
        $semester = Semester::findOrFail($context['semester_id']);
        $affected = 0;

        foreach ($rows as $row) {
            $student = $this->studentInClass((string) $this->rowValue($row, ['ma_hs', 'ma_hoc_sinh', 'student_code', 'student_id']), $class);
            if (! $student) {
                continue;
            }

            Conduct::updateOrCreate(
                ['student_id' => $student->id, 'semester_id' => $semester->id, 'school_year_id' => $semester->school_year_id],
                [
                    'class_id' => $class->id,
                    'conduct_level' => $this->normalizeConductLevel($this->rowValue($row, ['xep_loai', 'conduct_level'], 'Tốt')),
                    'comment' => trim((string) $this->rowValue($row, ['loi_phe', 'comment'])) ?: null,
                ]
            );
            $affected++;
        }

        return $affected;
    }

    private function commitAttendance(array $rows, array $context): int
    {
        $class = SchoolClass::findOrFail($context['class_id']);
        $semester = Semester::findOrFail($context['semester_id']);
        $date = Carbon::parse($context['attendance_date'])->toDateString();
        $sessionType = in_array($context['attendance_type'], [AttendanceRecord::SESSION_MORNING, AttendanceRecord::SESSION_AFTERNOON], true)
            ? $context['attendance_type']
            : AttendanceRecord::SESSION_MORNING;
        $approvedLeaves = $this->approvedLeaves($class, $date);
        $affected = 0;

        foreach ($rows as $row) {
            $student = $this->studentInClass((string) $this->rowValue($row, ['ma_hs', 'ma_hoc_sinh', 'student_code', 'student_id']), $class);
            if (! $student) {
                continue;
            }

            $status = $approvedLeaves->has((string) $student->id)
                ? 'excused'
                : $this->normalizeAttendanceStatus($this->rowValue($row, ['trang_thai', 'status'], 'Có mặt'));

            AttendanceRecord::updateOrCreate(
                ['student_id' => $student->id, 'attendance_date' => $date, 'session_key' => $sessionType],
                [
                    'class_id' => $class->id,
                    'semester_id' => $semester->id,
                    'session_type' => $sessionType,
                    'session_label' => AttendanceRecord::SESSION_TYPES[$sessionType] ?? $sessionType,
                    'session_order' => $sessionType === AttendanceRecord::SESSION_AFTERNOON ? 2 : 1,
                    'status' => $status,
                    'note' => trim((string) $this->rowValue($row, ['ghi_chu', 'note'])) ?: null,
                    'recorded_by' => auth()->id(),
                ]
            );
            $affected++;
        }

        return $affected;
    }

    private function studentTemplate(Request $request): array
    {
        $context = $this->context($request);
        $class = $this->resolveClass('', $context['class_id'] ?? null);
        $headers = $this->studentHeaders();
        $rows = ($class?->students()->orderBy('student_code')->get() ?? collect())
            ->map(fn (Student $student) => $this->rowForHeaders($headers, [
                'ma_hs' => $student->student_code,
                'ho_ten' => $student->name,
                'ngay_sinh' => $student->dob?->format('d/m/Y'),
                'gioi_tinh' => $student->gender === Student::GENDER_NU ? 'Nữ' : 'Nam',
                'noi_sinh' => $student->place_of_birth,
                'dan_toc' => $student->ethnicity,
                'ton_giao' => $student->religion,
                'dia_chi' => $student->address,
                'sdt_phu_huynh' => $student->parent_phone,
                'lop' => $student->classRoom?->name ?? $class?->name,
                'nien_khoa' => $student->schoolYear?->name ?? $class?->schoolYear?->name,
                'trang_thai' => $student->statusLabel(),
                'student_code' => $student->student_code,
                'name' => $student->name,
                'dob' => $student->dob?->format('d/m/Y'),
                'gender' => $student->gender,
                'address' => $student->address,
                'place_of_birth' => $student->place_of_birth,
                'ethnicity' => $student->ethnicity,
                'religion' => $student->religion,
                'parent_phone' => $student->parent_phone,
                'email' => $student->email,
                'enrollment_date' => $student->enrollment_date?->format('d/m/Y'),
                'admission_type' => $student->admission_type,
                'previous_school' => $student->previous_school,
                'transfer_grade_level' => $student->transfer_grade_level,
                'previous_class' => $student->previous_class,
                'class_id' => $student->class_id,
                'school_year_id' => $student->school_year_id,
                'status' => $student->status,
                'note' => $student->note,
                'id' => $student->id,
            ]))
            ->values()
            ->all();

        return [$this->labels($headers), $rows ?: [$this->rowForHeaders($headers, [
            'ma_hs' => '',
            'ho_ten' => 'Nguyễn Văn An',
            'ngay_sinh' => '15/09/2010',
            'gioi_tinh' => 'Nam',
            'noi_sinh' => 'TP Hồ Chí Minh',
            'que_quan' => 'TP Hồ Chí Minh',
            'dan_toc' => 'Kinh',
            'ton_giao' => 'Không',
            'dia_chi' => 'Địa chỉ thường trú',
            'sdt_phu_huynh' => '0901234567',
            'lop' => $class?->name ?: '10A1',
            'nien_khoa' => $class?->schoolYear?->name ?: '',
            'trang_thai' => 'Đang học',
        ])], 'mau_import_hoc_sinh.xlsx'];
    }

    private function teacherTemplate(): array
    {
        $headers = $this->teacherHeaders();
        $rows = Teacher::with(['primarySubject', 'department'])->orderBy('teacher_code')->get()
            ->map(fn (Teacher $teacher) => $this->rowForHeaders($headers, [
                'ma_gv' => $teacher->teacher_code,
                'ho_ten' => $teacher->name,
                'ngay_sinh' => $teacher->dob?->format('d/m/Y'),
                'gioi_tinh' => $teacher->gender === Teacher::GENDER_NU ? 'Nữ' : 'Nam',
                'sdt' => $teacher->phone,
                'email' => $teacher->email,
                'trinh_do' => $teacher->qualification,
                'to_chuyen_mon' => $teacher->department?->name,
                'mon_chinh' => $teacher->primarySubject?->name,
                'trang_thai' => $teacher->workStatusLabel(),
                'teacher_code' => $teacher->teacher_code,
                'name' => $teacher->name,
                'dob' => $teacher->dob?->format('d/m/Y'),
                'gender' => $teacher->gender,
                'phone' => $teacher->phone,
                'address' => $teacher->address,
                'joined_at' => $teacher->joined_at?->format('d/m/Y'),
                'work_status' => $teacher->work_status,
                'qualification' => $teacher->qualification,
                'main_subject' => $teacher->main_subject,
                'primary_subject_id' => $teacher->primary_subject_id,
                'department_id' => $teacher->department_id,
                'is_homeroom' => $teacher->is_homeroom ? '1' : '0',
                'id' => $teacher->id,
            ]))
            ->values()
            ->all();

        return [$this->labels($headers), $rows ?: [$this->rowForHeaders($headers, [
            'ma_gv' => 'GV001',
            'ho_ten' => 'Nguyễn Văn Bình',
            'ngay_sinh' => '',
            'gioi_tinh' => 'Nam',
            'sdt' => '0901234567',
            'email' => 'gv@example.com',
            'trinh_do' => 'Đại học',
            'to_chuyen_mon' => 'Tổ Toán',
            'mon_chinh' => 'Toán',
            'trang_thai' => 'Đang công tác',
        ])], 'mau_import_giao_vien.xlsx'];
    }

    private function parentTemplate(): array
    {
        $headers = $this->parentHeaders();
        $rows = ParentProfile::with('students')->orderBy('parent_code')->get()
            ->map(fn (ParentProfile $parent) => $this->rowForHeaders($headers, [
                'ma_phu_huynh' => $parent->parent_code,
                'ho_ten' => $parent->name,
                'sdt' => $parent->phone,
                'email' => $parent->email,
                'nghe_nghiep' => $parent->getAttribute('occupation'),
                'dia_chi' => $parent->address,
                'ma_hs_lien_ket' => $parent->students->pluck('student_code')->filter()->join(', '),
                'parent_code' => $parent->parent_code,
                'name' => $parent->name,
                'phone' => $parent->phone,
                'address' => $parent->address,
                'id' => $parent->id,
            ]))
            ->values()
            ->all();

        return [$this->labels($headers), $rows ?: [$this->rowForHeaders($headers, [
            'ma_phu_huynh' => 'PH0001',
            'ho_ten' => 'Nguyễn Văn B',
            'sdt' => '0901234567',
            'email' => 'ph@example.com',
            'nghe_nghiep' => 'Kinh doanh',
            'dia_chi' => 'Địa chỉ liên hệ',
            'ma_hs_lien_ket' => 'HS001',
        ])], 'mau_import_phu_huynh.xlsx'];
    }

    private function scoreTemplate(Request $request): array
    {
        $context = $this->context($request);
        $class = SchoolClass::with('students')->find($context['class_id'] ?? null);
        $subject = Subject::find($context['subject_id'] ?? null);
        $semester = Semester::find($context['semester_id'] ?? null);
        $headers = $this->scoreHeaders($class, $subject, $semester);

        $rows = $class && $class->students->isNotEmpty()
            ? $class->students()->orderBy('student_code')->get()->map(function (Student $student) use ($headers) {
                return $this->rowForHeaders($headers, [
                    'ma_hs' => $student->student_code,
                    'ho_ten' => $student->name,
                ]);
            })->all()
            : [
                $this->rowForHeaders($headers, [
                    'ma_hs' => 'HS001',
                    'ho_ten' => 'Nguyễn Văn An',
                    'diem_mieng' => '8.5',
                    'diem_15p_lan_1' => '9.0',
                    'diem_15p_lan_2' => '8.0',
                    'diem_giua_ky' => '8.5',
                    'diem_cuoi_ky' => '9.0',
                ]),
                $this->rowForHeaders($headers, [
                    'ma_hs' => 'HS002',
                    'ho_ten' => 'Trần Thị Bình',
                    'diem_mieng' => '7.5',
                    'diem_15p_lan_1' => '8.0',
                    'diem_15p_lan_2' => '8.5',
                    'diem_giua_ky' => '8.0',
                    'diem_cuoi_ky' => '8.5',
                ]),
            ];

        return [$this->labels($headers), $rows, 'mau_import_diem_so.xlsx'];
    }

    private function conductTemplate(Request $request): array
    {
        $context = $this->context($request);
        $class = SchoolClass::with('students')->find($context['class_id'] ?? null);
        $headers = $this->conductHeaders();
        $rows = $class
            ? $class->students()->orderBy('student_code')->get()->map(fn (Student $student) => $this->rowForHeaders($headers, [
                'ma_hs' => $student->student_code,
                'ho_ten' => $student->name,
                'xep_loai' => 'Tốt',
                'loi_phe' => 'Chăm ngoan, có ý thức kỷ luật tốt.',
                'student_id' => $student->id,
                'class_id' => $class->id,
                'semester_id' => $context['semester_id'] ?? null,
                'school_year_id' => $context['school_year_id'] ?? null,
            ]))->all()
            : [$this->rowForHeaders($headers, ['ma_hs' => 'HS001', 'ho_ten' => 'Nguyễn Văn An', 'xep_loai' => 'Tốt', 'loi_phe' => 'Chăm ngoan, có ý thức kỷ luật tốt.'])];

        return [$this->labels($headers), $rows, 'mau_import_hanh_kiem.xlsx'];
    }

    private function attendanceTemplate(Request $request): array
    {
        $context = $this->context($request);
        $class = SchoolClass::with('students')->find($context['class_id'] ?? null);
        $rows = $class
            ? $class->students()->orderBy('student_code')->get()->map(fn (Student $student) => [$student->student_code, $student->name, 'Có mặt', ''])->all()
            : [['HS001', 'Nguyễn Văn An', 'Có mặt', '']];

        return [$this->labels($this->attendanceHeaders()), $rows, 'mau_import_diem_danh.xlsx'];
    }

    private function studentDataExport(Request $request): array
    {
        $headers = $this->studentHeaders();
        $selectedYearId = $request->query('school_year_id') ?: $this->selectedSchoolYearId($request);
        $selectedGrade = $request->query('grade_level', 'all');
        $selectedClassId = $request->query('class_id', 'all');
        $selectedStatus = $request->query('status', 'all');
        $selectedGender = $request->query('gender', 'all');

        $rows = Student::with(['classRoom.schoolYear', 'schoolYear', 'parents'])
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when(in_array((string) $selectedGrade, ['10', '11', '12'], true), fn ($query) => $query->whereHas('classRoom', fn ($classQuery) => $classQuery->where('grade_level', $selectedGrade)))
            ->when($selectedClassId !== 'all' && $selectedClassId !== '', fn ($query) => $query->where('class_id', $selectedClassId))
            ->when($selectedStatus !== 'all' && $selectedStatus !== '', fn ($query) => $query->where('status', $selectedStatus))
            ->when($selectedGender !== 'all' && $selectedGender !== '', fn ($query) => $query->where('gender', $selectedGender))
            ->orderBy('student_code')
            ->get()
            ->map(fn (Student $student) => $this->studentDataRow($headers, $student))
            ->all();

        return [$this->labels($headers), $rows, 'danh_sach_hoc_sinh_' . now()->format('Ymd_His') . '.xlsx'];
    }

    private function teacherDataExport(Request $request): array
    {
        $headers = $this->teacherHeaders();
        $keyword = trim((string) $request->query('q', ''));
        $departmentId = $request->query('department_id', 'all');

        $rows = Teacher::with(['primarySubject', 'department'])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('teacher_code', 'like', '%' . $keyword . '%')
                        ->orWhere('name', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%')
                        ->orWhereHas('primarySubject', fn ($subject) => $subject->where('name', 'like', '%' . $keyword . '%'))
                        ->orWhereHas('department', fn ($department) => $department->where('name', 'like', '%' . $keyword . '%'));
                });
            })
            ->when($departmentId !== 'all' && $departmentId !== '', fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('name')
            ->get()
            ->map(fn (Teacher $teacher) => $this->teacherDataRow($headers, $teacher))
            ->all();

        return [$this->labels($headers), $rows, 'danh_sach_giao_vien_' . now()->format('Ymd_His') . '.xlsx'];
    }

    private function parentDataExport(): array
    {
        $headers = $this->parentHeaders();
        $rows = ParentProfile::with('students')->orderBy('parent_code')->orderBy('name')->get()
            ->map(fn (ParentProfile $parent) => $this->parentDataRow($headers, $parent))
            ->all();

        return [$this->labels($headers), $rows, 'danh_sach_phu_huynh_' . now()->format('Ymd_His') . '.xlsx'];
    }

    private function scoreDataExport(Request $request): array
    {
        $context = $this->context($request);
        $class = SchoolClass::with('students.classRoom')->find($context['class_id'] ?? null);
        $subject = Subject::find($context['subject_id'] ?? null);
        $semester = Semester::find($context['semester_id'] ?? null);
        $gradeLevel = $request->query('grade_level') ?: ($class?->grade_level);

        if ($class && $subject && $semester) {
            return $this->scoreSubjectDetailExport($class, $subject, $semester);
        }

        if ($class && $semester) {
            return $this->scoreClassSummaryExport($class, $semester);
        }

        if ($gradeLevel && $semester) {
            return $this->scoreGradeSummaryExport((int) $gradeLevel, $semester, $request);
        }

        return [['Mã HS', 'Họ tên'], [], 'danh_sach_diem_so_' . now()->format('Ymd_His') . '.xlsx'];
    }

    private function conductDataExport(Request $request): array
    {
        $context = $this->context($request);
        $class = SchoolClass::with('students')->find($context['class_id'] ?? null);
        $semester = Semester::find($context['semester_id'] ?? null);
        $headers = ['Mã HS', 'Họ tên', 'Lớp', 'Học kỳ', 'Vắng có phép', 'Vắng không phép', 'Đi muộn', 'Xếp loại', 'Lời phê'];

        if (! $class || ! $semester) {
            return [$headers, [], 'danh_sach_hanh_kiem_' . now()->format('Ymd_His') . '.xlsx'];
        }

        $records = Conduct::where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->get()
            ->keyBy('student_id');
        $summaries = $this->attendanceSummariesFor($class->students, $class, $semester);
        $labels = Conduct::LEVELS;

        $rows = $class->students->sortBy('student_code')->map(function (Student $student) use ($class, $semester, $records, $summaries, $labels) {
            $record = $records->get($student->id);
            $summary = $summaries->get($student->id, ['excused' => 0, 'absent' => 0, 'late' => 0]);

            return [
                $student->student_code,
                $student->name,
                $class->name,
                $semester->normalizedName(),
                (string) ($summary['excused'] ?? 0),
                (string) ($summary['absent'] ?? 0),
                (string) ($summary['late'] ?? 0),
                $labels[$record?->conduct_level] ?? '—',
                $record?->comment ?: '—',
            ];
        })->values()->all();

        return [$headers, $rows, 'danh_sach_hanh_kiem_' . now()->format('Ymd_His') . '.xlsx'];
    }

    private function attendanceDataExport(Request $request): array
    {
        $context = $this->context($request);
        $class = SchoolClass::with('students')->find($context['class_id'] ?? null);
        $semester = Semester::find($context['semester_id'] ?? null);
        $date = Carbon::parse($context['attendance_date'] ?? now()->toDateString())->toDateString();
        $sessionKey = $context['attendance_type'] ?: AttendanceRecord::SESSION_MORNING;
        $headers = ['Mã HS', 'Họ tên', 'Lớp', 'Ngày điểm danh', 'Phiên điểm danh', 'Trạng thái', 'Ghi chú'];

        if ($class && $semester) {
            $records = AttendanceRecord::where('class_id', $class->id)
                ->where('semester_id', $semester->id)
                ->whereDate('attendance_date', $date)
                ->where('session_key', $sessionKey)
                ->get()
                ->keyBy('student_id');

            $rows = $class->students->sortBy('student_code')->map(function (Student $student) use ($class, $date, $sessionKey, $records) {
                $record = $records->get($student->id);

                return [
                    $student->student_code,
                    $student->name,
                    $class->name,
                    Carbon::parse($date)->format('d/m/Y'),
                    AttendanceRecord::SESSION_TYPES[$sessionKey] ?? $sessionKey,
                    $record?->statusLabel() ?? AttendanceRecord::STATUSES['present'],
                    $record?->note ?: '—',
                ];
            })->values()->all();

            return [$headers, $rows, 'danh_sach_diem_danh_' . now()->format('Ymd_His') . '.xlsx'];
        }

        $rows = AttendanceRecord::with(['student.classRoom', 'classRoom'])
            ->when($context['semester_id'] ?? null, fn ($query) => $query->where('semester_id', $context['semester_id']))
            ->when($context['class_id'] ?? null, fn ($query) => $query->where('class_id', $context['class_id']))
            ->where(function ($query) {
                $query->whereIn('session_type', [AttendanceRecord::SESSION_MORNING, AttendanceRecord::SESSION_AFTERNOON])
                    ->orWhereIn('session_key', [AttendanceRecord::SESSION_MORNING, AttendanceRecord::SESSION_AFTERNOON]);
            })
            ->latest('attendance_date')
            ->get()
            ->map(fn (AttendanceRecord $record) => [
                $record->student?->student_code ?? '—',
                $record->student?->name ?? '—',
                $record->classRoom?->name ?? $record->student?->classRoom?->name ?? '—',
                $record->attendance_date?->format('d/m/Y') ?? '—',
                $record->displaySessionLabel(),
                $record->statusLabel(),
                $record->note ?: '—',
            ])
            ->all();

        return [$headers, $rows, 'danh_sach_diem_danh_' . now()->format('Ymd_His') . '.xlsx'];
    }

    private function scoreSubjectDetailExport(SchoolClass $class, Subject $subject, Semester $semester): array
    {
        $columns = $this->scoreColumns($class, $subject, $semester);
        $headers = array_merge(['Mã HS', 'Họ tên'], $columns->pluck('name')->all(), ['ĐTB môn']);
        $scoreHeaders = ScoreHeader::with(['details.scoreColumn'])
            ->where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->where('school_year_id', $semester->school_year_id)
            ->whereIn('student_id', $class->students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $rows = $class->students->sortBy('student_code')->map(function (Student $student) use ($subject, $columns, $scoreHeaders) {
            $header = $scoreHeaders->get($student->id);
            $details = collect($header?->details ?? [])->keyBy('score_column_id');
            $row = [$student->student_code, $student->name];

            foreach ($columns as $column) {
                $detail = $details->get($column->id);
                $row[] = $this->exportDetailValue($detail, $subject);
            }

            $row[] = $this->exportAverageValue($header, $subject);

            return $row;
        })->values()->all();

        return [$headers, $rows, 'bang_diem_' . Str::slug($class->name . '_' . $subject->name) . '_' . now()->format('Ymd_His') . '.xlsx'];
    }

    private function scoreClassSummaryExport(SchoolClass $class, Semester $semester): array
    {
        $subjects = $this->exportSubjectsForGrade((int) $class->grade_level);
        $headers = array_merge(
            ['Mã HS', 'Họ tên'],
            $subjects->pluck('name')->all(),
            ['Tổng kết HK1', 'Tổng kết HK2', 'Điểm Cả Năm']
        );
        $scores = $this->exportScoreHeaders($class->students, $subjects, (string) $semester->school_year_id);
        $currentScores = $scores->where('semester_id', $semester->id)->keyBy(fn (ScoreHeader $header) => $header->student_id . ':' . $header->subject_id);
        $scoresByStudent = $scores->groupBy('student_id');

        $rows = $class->students->sortBy('student_code')->map(function (Student $student) use ($subjects, $currentScores, $scoresByStudent) {
            $row = [$student->student_code, $student->name];

            foreach ($subjects as $subject) {
                $row[] = $this->exportAverageValue($currentScores->get($student->id . ':' . $subject->id), $subject);
            }

            $summary = $this->exportAnnualSummary($subjects, collect($scoresByStudent->get($student->id, [])));
            $row[] = $this->exportScoreValue($summary['hk1_gpa']);
            $row[] = $this->exportScoreValue($summary['hk2_gpa']);
            $row[] = $this->exportScoreValue($summary['year_gpa']);

            return $row;
        })->values()->all();

        return [$headers, $rows, 'bang_diem_tong_hop_' . Str::slug($class->name) . '_' . now()->format('Ymd_His') . '.xlsx'];
    }

    private function scoreGradeSummaryExport(int $gradeLevel, Semester $semester, Request $request): array
    {
        $classId = $request->query('class_id');
        $classes = SchoolClass::query()
            ->where('school_year_id', $semester->school_year_id)
            ->where('grade_level', $gradeLevel)
            ->when($classId, fn ($query) => $query->where('id', $classId))
            ->pluck('id');
        $students = Student::with('classRoom')
            ->whereIn('class_id', $classes)
            ->where('status', Student::STATUS_STUDYING)
            ->orderBy('class_id')
            ->orderBy('student_code')
            ->get();
        $subjects = $this->exportSubjectsForGrade($gradeLevel);
        $scores = $this->exportScoreHeaders($students, $subjects, (string) $semester->school_year_id)->groupBy('student_id');
        $headers = ['Mã HS', 'Họ tên', 'Lớp', 'Điểm TB Học kỳ 1', 'Điểm TB Học kỳ 2', 'Điểm TB Cả Năm'];

        $rows = $students->map(function (Student $student) use ($subjects, $scores) {
            $summary = $this->exportAnnualSummary($subjects, collect($scores->get($student->id, [])));

            return [
                $student->student_code,
                $student->name,
                $student->classRoom?->name ?? '—',
                $this->exportScoreValue($summary['hk1_gpa']),
                $this->exportScoreValue($summary['hk2_gpa']),
                $this->exportScoreValue($summary['year_gpa']),
            ];
        })->values()->all();

        return [$headers, $rows, 'bang_diem_khoi_' . $gradeLevel . '_' . now()->format('Ymd_His') . '.xlsx'];
    }

    private function exportSubjectsForGrade(int $gradeLevel): Collection
    {
        return Subject::whereIn('type', array_merge([Subject::TYPE_OFFICIAL], Subject::LEGACY_SCORABLE_TYPES))
            ->where('status', Subject::STATUS_ACTIVE)
            ->forGrade($gradeLevel)
            ->withEvaluatedAssessment()
            ->orderBy('name')
            ->get();
    }

    private function exportScoreHeaders(Collection $students, Collection $subjects, string $schoolYearId): Collection
    {
        if ($students->isEmpty() || $subjects->isEmpty()) {
            return collect();
        }

        return ScoreHeader::with(['subject', 'semester', 'details.scoreColumn'])
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->where('school_year_id', $schoolYearId)
            ->get();
    }

    private function exportAnnualSummary(Collection $subjects, Collection $studentScores): array
    {
        $scoresBySubject = $studentScores->groupBy('subject_id');
        $hk1Values = collect();
        $hk2Values = collect();
        $yearValues = collect();

        foreach ($subjects as $subject) {
            if (! $subject->usesNumericAssessment()) {
                continue;
            }

            $subjectScores = collect($scoresBySubject->get($subject->id, []));
            $hk1 = $subjectScores->first(fn (ScoreHeader $header) => (int) ($header->semester?->termIndex() ?? 0) === 1)?->average;
            $hk2 = $subjectScores->first(fn (ScoreHeader $header) => (int) ($header->semester?->termIndex() ?? 0) === 2)?->average;
            if ($hk1 !== null) {
                $hk1Values->push((float) $hk1);
            }
            if ($hk2 !== null) {
                $hk2Values->push((float) $hk2);
            }
            if ($hk1 !== null && $hk2 !== null) {
                $yearValues->push(((float) $hk1 + (float) $hk2 * 2) / 3);
            }
        }

        return [
            'hk1_gpa' => $hk1Values->isNotEmpty() ? round((float) $hk1Values->avg(), 1) : null,
            'hk2_gpa' => $hk2Values->isNotEmpty() ? round((float) $hk2Values->avg(), 1) : null,
            'year_gpa' => $yearValues->isNotEmpty() ? round((float) $yearValues->avg(), 1) : null,
        ];
    }

    private function exportDetailValue(?ScoreDetail $detail, Subject $subject): string
    {
        if (! $detail || $detail->value === null) {
            return '—';
        }

        if ($subject->usesPassFailAssessment()) {
            return (float) $detail->value >= 0.5 ? 'Đạt' : 'Chưa đạt';
        }

        return $this->exportScoreValue($detail->value);
    }

    private function exportAverageValue(?ScoreHeader $header, Subject $subject): string
    {
        if (! $header) {
            return '—';
        }

        if ($subject->usesPassFailAssessment()) {
            $details = $header->details->whereNotNull('value');

            if ($details->isEmpty()) {
                return '—';
            }

            return (float) $details->avg('value') >= 0.5 ? 'Đạt' : 'Chưa đạt';
        }

        return $this->exportScoreValue($header->average);
    }

    private function exportScoreValue(mixed $value): string
    {
        return $value === null || $value === '' ? '—' : number_format((float) $value, 1, '.', '');
    }

    private function attendanceSummariesFor(Collection $students, SchoolClass $class, Semester $semester): Collection
    {
        $summary = $students->mapWithKeys(fn (Student $student) => [$student->id => ['present' => 0, 'late' => 0, 'excused' => 0, 'absent' => 0]]);
        if ($students->isEmpty()) {
            return $summary;
        }

        AttendanceRecord::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->select('student_id', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('student_id', 'status')
            ->get()
            ->each(function ($row) use ($summary) {
                $current = $summary->get($row->student_id, ['present' => 0, 'late' => 0, 'excused' => 0, 'absent' => 0]);
                $current[$row->status] = (int) $row->total;
                $summary->put($row->student_id, $current);
            });

        return $summary;
    }

    private function scoreColumns(SchoolClass $class, Subject $subject, Semester $semester): Collection
    {
        return ScoreColumn::query()
            ->where('school_year_id', $semester->school_year_id)
            ->where('subject_id', $subject->id)
            ->where('grade_level', (int) $class->grade_level)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function recalculateAverage(ScoreHeader $header): void
    {
        $header->loadMissing(['subject', 'details.scoreColumn']);

        if ($header->subject?->usesPassFailAssessment() || $header->subject?->isNotEvaluated()) {
            $header->update(['average' => null]);
            return;
        }

        $setting = ScoreSetting::current();
        $weightedTotal = 0.0;
        $weightTotal = 0;

        foreach ($header->details as $detail) {
            if ($detail->value === null) {
                continue;
            }
            $weight = $setting->weightForScoreType($detail->scoreColumn?->type ?: $detail->type);
            $weightedTotal += (float) $detail->value * $weight;
            $weightTotal += $weight;
        }

        $header->update([
            'average' => $weightTotal > 0 ? (float) number_format($weightedTotal / $weightTotal, 1, '.', '') : null,
        ]);
    }

    private function resolveClass(string $value, ?string $fallbackId = null): ?SchoolClass
    {
        if ($fallbackId) {
            return SchoolClass::find($fallbackId);
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return SchoolClass::where(fn ($query) => $query->where('name', $value)->orWhere('id', $value))->first();
    }

    private function resolveSubject(string $value): ?Subject
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return Subject::where(fn ($query) => $query->where('name', $value)->orWhere('code', $value)->orWhere('id', $value))->first();
    }

    private function resolveDepartment(string $value): ?TeacherDepartment
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return TeacherDepartment::where(fn ($query) => $query->where('name', $value)->orWhere('code', $value)->orWhere('id', $value))->first();
    }

    private function studentInClass(string $code, SchoolClass $class): ?Student
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        return Student::query()
            ->where('class_id', $class->id)
            ->where(fn ($query) => $query->where('student_code', $code)->orWhere('id', $code))
            ->first();
    }

    private function approvedLeaves(SchoolClass $class, string $date): Collection
    {
        return ParentLeaveRequest::query()
            ->where('class_id', $class->id)
            ->whereDate('leave_date', $date)
            ->where('status', ParentLeaveRequest::STATUS_APPROVED)
            ->pluck('student_id')
            ->mapWithKeys(fn ($studentId) => [(string) $studentId => true]);
    }

    private function schemaAwareHeaders(string $table, array $baseHeaders): array
    {
        if (! Schema::hasTable($table)) {
            return $baseHeaders;
        }

        $covered = collect($baseHeaders)
            ->flatMap(fn (array $header) => array_merge([$header['key'], SimpleExcel::normalizeHeader($header['label'])], $header['aliases'] ?? []))
            ->map(fn ($key) => SimpleExcel::normalizeHeader((string) $key))
            ->unique()
            ->all();

        foreach (Schema::getColumnListing($table) as $column) {
            $normalized = SimpleExcel::normalizeHeader($column);
            if (in_array($normalized, $covered, true)) {
                continue;
            }

            $baseHeaders[] = [
                'key' => $normalized,
                'label' => $this->translatedColumnLabel($column),
                'aliases' => [$column],
            ];
            $covered[] = $normalized;
        }

        return $baseHeaders;
    }

    private function translatedColumnLabel(string $column): string
    {
        $normalized = SimpleExcel::normalizeHeader($column);
        $labels = [
            'id' => 'Mã dữ liệu hệ thống',
            'student_code' => 'Mã học sinh',
            'teacher_code' => 'Mã giáo viên',
            'parent_code' => 'Mã phụ huynh',
            'username' => 'Tên đăng nhập',
            'password_hash' => 'Mật khẩu mã hóa',
            'role' => 'Vai trò tài khoản',
            'name' => 'Họ và tên',
            'full_name' => 'Họ và tên',
            'dob' => 'Ngày sinh',
            'birthday' => 'Ngày sinh',
            'gender' => 'Giới tính',
            'place_of_birth' => 'Nơi sinh',
            'hometown' => 'Quê quán',
            'native_place' => 'Quê quán',
            'ethnicity' => 'Dân tộc',
            'religion' => 'Tôn giáo',
            'address' => 'Địa chỉ',
            'student_phone' => 'SĐT học sinh',
            'parent_name' => 'Họ tên phụ huynh',
            'parent_phone' => 'SĐT phụ huynh',
            'phone' => 'Số điện thoại',
            'email' => 'Email',
            'occupation' => 'Nghề nghiệp',
            'enrollment_date' => 'Ngày nhập học',
            'admission_type' => 'Hình thức tuyển sinh',
            'previous_school' => 'Trường học cũ',
            'transfer_grade_level' => 'Khối chuyển đến',
            'previous_class' => 'Lớp cũ',
            'avatar' => 'Ảnh đại diện',
            'note' => 'Ghi chú',
            'status' => 'Trạng thái',
            'class_id' => 'Lớp',
            'school_year_id' => 'Niên khóa',
            'academic_year_id' => 'Năm học',
            'semester_id' => 'Học kỳ',
            'subject_id' => 'Môn học',
            'teacher_id' => 'Giáo viên',
            'department_id' => 'Tổ chuyên môn',
            'primary_subject_id' => 'Môn giảng dạy',
            'main_subject' => 'Môn giảng dạy',
            'qualification' => 'Trình độ',
            'joined_at' => 'Ngày vào trường',
            'work_status' => 'Trạng thái công tác',
            'is_homeroom' => 'Giáo viên chủ nhiệm',
            'student_id' => 'Học sinh',
            'conduct_level' => 'Xếp loại',
            'comment' => 'Lời phê',
            'attendance_date' => 'Ngày điểm danh',
            'session_type' => 'Phiên điểm danh',
            'session_label' => 'Tên phiên',
            'session_order' => 'Thứ tự phiên',
            'session_key' => 'Mã phiên',
            'timetable_entry_id' => 'Tiết học',
            'recorded_by' => 'Người ghi nhận',
            'created_at' => 'Ngày tạo tài khoản',
            'updated_at' => 'Ngày cập nhật cuối',
        ];

        if (isset($labels[$normalized])) {
            return $labels[$normalized];
        }

        $fallback = str_replace('_', ' ', $normalized);
        $fallback = trim($fallback) !== '' ? $fallback : 'bo sung';

        return 'Thông tin ' . Str::title($fallback);
    }

    private function labels(array $headers): array
    {
        return array_map(fn ($header) => $header['label'], $headers);
    }

    private function rowValue(array $row, array|string $keys, mixed $default = ''): mixed
    {
        foreach ((array) $keys as $key) {
            $normalized = SimpleExcel::normalizeHeader((string) $key);
            if (array_key_exists($normalized, $row) && trim((string) $row[$normalized]) !== '') {
                return $row[$normalized];
            }
        }

        return $default;
    }

    private function rowForHeaders(array $headers, array $values): array
    {
        return array_map(function (array $header) use ($values) {
            foreach (array_merge([$header['key']], $header['aliases'] ?? []) as $key) {
                $normalized = SimpleExcel::normalizeHeader((string) $key);
                if (array_key_exists($normalized, $values)) {
                    return $values[$normalized];
                }
                if (array_key_exists((string) $key, $values)) {
                    return $values[(string) $key];
                }
            }

            return '';
        }, $headers);
    }

    private function studentDataRow(array $headers, Student $student): array
    {
        return $this->rowForHeaders($headers, [
            'ma_hs' => $student->student_code,
            'ho_ten' => $student->name,
            'ngay_sinh' => $student->dob?->format('d/m/Y'),
            'gioi_tinh' => $student->gender === Student::GENDER_NU ? 'Nữ' : 'Nam',
            'noi_sinh' => $student->place_of_birth,
            'que_quan' => $student->getAttribute('hometown') ?: $student->getAttribute('native_place'),
            'dan_toc' => $student->ethnicity,
            'ton_giao' => $student->religion,
            'dia_chi' => $student->address,
            'sdt_hoc_sinh' => $student->getAttribute('student_phone'),
            'ho_ten_phu_huynh' => $student->parents->pluck('name')->filter()->join(', '),
            'sdt_phu_huynh' => $student->parent_phone,
            'lop' => $student->classRoom?->name,
            'nien_khoa' => $student->schoolYear?->name ?? $student->classRoom?->schoolYear?->name,
            'trang_thai' => $student->statusLabel(),
            'student_code' => $student->student_code,
            'name' => $student->name,
            'dob' => $student->dob?->format('d/m/Y'),
            'gender' => $student->gender,
            'address' => $student->address,
            'place_of_birth' => $student->place_of_birth,
            'ethnicity' => $student->ethnicity,
            'religion' => $student->religion,
            'parent_phone' => $student->parent_phone,
            'email' => $student->email,
            'enrollment_date' => $student->enrollment_date?->format('d/m/Y'),
            'admission_type' => $student->admission_type,
            'previous_school' => $student->previous_school,
            'transfer_grade_level' => $student->transfer_grade_level,
            'previous_class' => $student->previous_class,
            'class_id' => $student->class_id,
            'school_year_id' => $student->school_year_id,
            'status' => $student->status,
            'note' => $student->note,
            'id' => $student->id,
            'created_at' => $student->created_at?->format('d/m/Y H:i'),
            'updated_at' => $student->updated_at?->format('d/m/Y H:i'),
        ]);
    }

    private function teacherDataRow(array $headers, Teacher $teacher): array
    {
        return $this->rowForHeaders($headers, [
            'ma_gv' => $teacher->teacher_code,
            'ho_ten' => $teacher->name,
            'ngay_sinh' => $teacher->dob?->format('d/m/Y'),
            'gioi_tinh' => $teacher->gender === Teacher::GENDER_NU ? 'Nữ' : 'Nam',
            'sdt' => $teacher->phone,
            'email' => $teacher->email,
            'trinh_do' => $teacher->qualification,
            'to_chuyen_mon' => $teacher->department?->name,
            'mon_chinh' => $teacher->primarySubject?->name ?? $teacher->main_subject,
            'trang_thai' => $teacher->workStatusLabel(),
            'teacher_code' => $teacher->teacher_code,
            'name' => $teacher->name,
            'dob' => $teacher->dob?->format('d/m/Y'),
            'gender' => $teacher->gender,
            'phone' => $teacher->phone,
            'address' => $teacher->address,
            'joined_at' => $teacher->joined_at?->format('d/m/Y'),
            'work_status' => $teacher->work_status,
            'qualification' => $teacher->qualification,
            'main_subject' => $teacher->main_subject,
            'primary_subject_id' => $teacher->primary_subject_id,
            'department_id' => $teacher->department_id,
            'is_homeroom' => $teacher->is_homeroom ? '1' : '0',
            'id' => $teacher->id,
            'created_at' => $teacher->created_at?->format('d/m/Y H:i'),
            'updated_at' => $teacher->updated_at?->format('d/m/Y H:i'),
        ]);
    }

    private function parentDataRow(array $headers, ParentProfile $parent): array
    {
        return $this->rowForHeaders($headers, [
            'ma_phu_huynh' => $parent->parent_code,
            'ho_ten' => $parent->name,
            'sdt' => $parent->phone,
            'email' => $parent->email,
            'nghe_nghiep' => $parent->getAttribute('occupation'),
            'dia_chi' => $parent->address,
            'ma_hs_lien_ket' => $parent->students->pluck('student_code')->filter()->join(', '),
            'parent_code' => $parent->parent_code,
            'name' => $parent->name,
            'phone' => $parent->phone,
            'address' => $parent->address,
            'id' => $parent->id,
            'created_at' => $parent->created_at?->format('d/m/Y H:i'),
            'updated_at' => $parent->updated_at?->format('d/m/Y H:i'),
        ]);
    }

    private function studentHeaders(): array
    {
        return $this->schemaAwareHeaders('students', [
            ['key' => 'ma_hs', 'label' => 'Mã học sinh', 'aliases' => ['ma_hoc_sinh', 'student_code']],
            ['key' => 'ho_ten', 'label' => 'Họ và tên', 'aliases' => ['ho_va_ten', 'name']],
            ['key' => 'ngay_sinh', 'label' => 'Ngày sinh', 'aliases' => ['dob']],
            ['key' => 'gioi_tinh', 'label' => 'Giới tính', 'aliases' => ['gender']],
            ['key' => 'noi_sinh', 'label' => 'Nơi sinh', 'aliases' => ['place_of_birth']],
            ['key' => 'que_quan', 'label' => 'Quê quán', 'aliases' => ['hometown', 'native_place']],
            ['key' => 'dan_toc', 'label' => 'Dân tộc', 'aliases' => ['ethnicity']],
            ['key' => 'ton_giao', 'label' => 'Tôn giáo', 'aliases' => ['religion']],
            ['key' => 'dia_chi', 'label' => 'Địa chỉ thường trú', 'aliases' => ['dia_chi_thuong_tru', 'address']],
            ['key' => 'sdt_hoc_sinh', 'label' => 'SĐT học sinh', 'aliases' => ['student_phone', 'phone']],
            ['key' => 'ho_ten_phu_huynh', 'label' => 'Họ tên phụ huynh', 'aliases' => ['parent_name']],
            ['key' => 'sdt_phu_huynh', 'label' => 'SĐT phụ huynh', 'aliases' => ['parent_phone']],
            ['key' => 'lop', 'label' => 'Lớp', 'aliases' => ['class_id']],
            ['key' => 'nien_khoa', 'label' => 'Niên khóa', 'aliases' => ['school_year_id', 'cohort']],
            ['key' => 'trang_thai', 'label' => 'Trạng thái', 'aliases' => ['status']],
        ]);
    }

    private function teacherHeaders(): array
    {
        return $this->schemaAwareHeaders('teachers', [
            ['key' => 'ma_gv', 'label' => 'Mã giáo viên', 'aliases' => ['ma_giao_vien', 'teacher_code']],
            ['key' => 'ho_ten', 'label' => 'Họ và tên', 'aliases' => ['ho_va_ten', 'name']],
            ['key' => 'ngay_sinh', 'label' => 'Ngày sinh', 'aliases' => ['dob']],
            ['key' => 'gioi_tinh', 'label' => 'Giới tính', 'aliases' => ['gender']],
            ['key' => 'sdt', 'label' => 'Số điện thoại', 'aliases' => ['so_dien_thoai', 'phone']],
            ['key' => 'email', 'label' => 'Email', 'aliases' => ['email']],
            ['key' => 'trinh_do', 'label' => 'Trình độ', 'aliases' => ['qualification']],
            ['key' => 'to_chuyen_mon', 'label' => 'Tổ chuyên môn', 'aliases' => ['department_id']],
            ['key' => 'mon_chinh', 'label' => 'Môn giảng dạy', 'aliases' => ['mon_giang_day', 'main_subject', 'primary_subject_id']],
            ['key' => 'trang_thai', 'label' => 'Trạng thái', 'aliases' => ['work_status']],
        ]);
    }

    private function parentHeaders(): array
    {
        return $this->schemaAwareHeaders('parents', [
            ['key' => 'ma_phu_huynh', 'label' => 'Mã phụ huynh', 'aliases' => ['parent_code']],
            ['key' => 'ho_ten', 'label' => 'Họ và tên', 'aliases' => ['ho_va_ten', 'name']],
            ['key' => 'sdt', 'label' => 'Số điện thoại', 'aliases' => ['so_dien_thoai', 'phone']],
            ['key' => 'email', 'label' => 'Email', 'aliases' => ['email']],
            ['key' => 'nghe_nghiep', 'label' => 'Nghề nghiệp', 'aliases' => ['occupation']],
            ['key' => 'dia_chi', 'label' => 'Địa chỉ', 'aliases' => ['address']],
            ['key' => 'ma_hs_lien_ket', 'label' => 'Mã HS liên kết', 'aliases' => ['student_code', 'student_id']],
        ]);
    }

    private function scoreHeaders(?SchoolClass $class, ?Subject $subject, ?Semester $semester): array
    {
        $columns = ($class && $subject && $semester)
            ? $this->scoreColumns($class, $subject, $semester)
            : collect();

        if ($columns->isNotEmpty()) {
            return array_merge([
                ['key' => 'ma_hs', 'label' => 'Mã học sinh', 'aliases' => ['ma_hoc_sinh', 'student_code']],
                ['key' => 'ho_ten', 'label' => 'Họ và tên', 'aliases' => ['ho_va_ten', 'name']],
            ], $columns->map(fn (ScoreColumn $column) => [
                'key' => $this->scoreColumnImportKey($column),
                'label' => $column->name,
                'aliases' => [SimpleExcel::normalizeHeader($column->name)],
            ])->all());
        }

        return [
            ['key' => 'ma_hs', 'label' => 'Mã học sinh', 'aliases' => ['ma_hoc_sinh', 'student_code']],
            ['key' => 'ho_ten', 'label' => 'Họ và tên', 'aliases' => ['ho_va_ten', 'name']],
            ['key' => 'diem_mieng', 'label' => 'Điểm kiểm tra Miệng', 'aliases' => ['oral', 'mieng', 'diem_mieng']],
            ['key' => 'diem_15p_lan_1', 'label' => 'Điểm kiểm tra 15 phút (Lần 1)', 'aliases' => ['fifteen_1', '15p_1', 'diem_15p_lan_1']],
            ['key' => 'diem_15p_lan_2', 'label' => 'Điểm kiểm tra 15 phút (Lần 2)', 'aliases' => ['fifteen_2', '15p_2', 'diem_15p_lan_2']],
            ['key' => 'diem_giua_ky', 'label' => 'Điểm kiểm tra Giữa kỳ', 'aliases' => ['midterm', 'giua_ky', 'diem_giua_ky']],
            ['key' => 'diem_cuoi_ky', 'label' => 'Điểm kiểm tra Cuối kỳ', 'aliases' => ['final', 'cuoi_ky', 'diem_cuoi_ky']],
        ];
    }

    private function scoreColumnImportKey(ScoreColumn $column): string
    {
        return SimpleExcel::normalizeHeader((string) $column->name);
    }

    private function conductHeaders(): array
    {
        return $this->schemaAwareHeaders('conducts', [
            ['key' => 'ma_hs', 'label' => 'Mã học sinh', 'aliases' => ['ma_hoc_sinh', 'student_code', 'student_id']],
            ['key' => 'ho_ten', 'label' => 'Họ và tên', 'aliases' => ['ho_va_ten', 'name']],
            ['key' => 'xep_loai', 'label' => 'Xếp loại', 'aliases' => ['conduct_level']],
            ['key' => 'loi_phe', 'label' => 'Lời phê', 'aliases' => ['comment']],
        ]);
    }

    private function attendanceHeaders(): array
    {
        return [
            ['key' => 'ma_hs', 'label' => 'Mã HS'],
            ['key' => 'ho_ten', 'label' => 'Họ tên'],
            ['key' => 'trang_thai', 'label' => 'Trạng thái'],
            ['key' => 'ghi_chu', 'label' => 'Ghi chú'],
        ];
    }

    private function normalizeText(mixed $value): string
    {
        $value = trim((string) $value);
        $value = Str::ascii($value);
        $value = Str::lower($value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);

        return trim((string) $value, '_');
    }

    private function normalizeGender(mixed $value): string
    {
        return $this->normalizeText($value) === 'nu' ? Student::GENDER_NU : Student::GENDER_NAM;
    }

    private function normalizeStudentStatus(mixed $value): string
    {
        return match ($this->normalizeText($value)) {
            'bao_luu', 'reserved' => Student::STATUS_RESERVED,
            'chuyen_truong', 'transferred' => Student::STATUS_TRANSFERRED,
            'tot_nghiep', 'graduated' => Student::STATUS_GRADUATED,
            'nghi_hoc', 'dropped', 'inactive' => Student::STATUS_DROPPED,
            default => Student::STATUS_STUDYING,
        };
    }

    private function normalizeTeacherStatus(mixed $value): string
    {
        return in_array($this->normalizeText($value), ['nghi_viec', 'resigned'], true)
            ? Teacher::STATUS_RESIGNED
            : Teacher::STATUS_WORKING;
    }

    private function normalizeAdmissionType(mixed $value): string
    {
        return in_array($this->normalizeText($value), ['chuyen_truong', 'transfer'], true)
            ? Student::ADMISSION_TRANSFER
            : Student::ADMISSION_NEW;
    }

    private function normalizeConductLevel(mixed $value): string
    {
        return match ($this->normalizeText($value)) {
            'kha', 'good' => Conduct::LEVEL_FAIR,
            'dat', 'average' => Conduct::LEVEL_PASS,
            'chua_dat', 'weak' => Conduct::LEVEL_NOT_PASS,
            default => Conduct::LEVEL_GOOD,
        };
    }

    private function normalizeAttendanceStatus(mixed $value): string
    {
        return match ($this->normalizeText($value)) {
            'di_muon', 'late', 'm' => 'late',
            'vang_mat', 'vang_khong_phep', 'absent', 'x' => 'absent',
            'nghi_co_phep', 'excused', 'p' => 'excused',
            default => 'present',
        };
    }

    private function tryParseDate(mixed $value): bool
    {
        try {
            return $this->parseDate($value) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    private function parseDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable) {
            }
        }

        return Carbon::parse($value)->toDateString();
    }

    private function nextStudentCode(mixed $date): string
    {
        $year = $this->parseDate($date) ? Carbon::parse($this->parseDate($date))->format('Y') : now()->format('Y');
        $prefix = 'HS' . $year;
        $max = Student::where('student_code', 'like', $prefix . '%')
            ->pluck('student_code')
            ->map(fn ($code) => preg_match('/^' . $prefix . '(\d+)$/', (string) $code, $matches) ? (int) $matches[1] : 0)
            ->max() ?? 0;

        do {
            $max++;
            $code = $prefix . str_pad((string) $max, 4, '0', STR_PAD_LEFT);
        } while (Student::where('student_code', $code)->exists() || User::where('username', $code)->exists());

        return $code;
    }

    private function nextTeacherCode(): string
    {
        $max = Teacher::where('teacher_code', 'like', 'GV%')
            ->pluck('teacher_code')
            ->map(fn ($code) => preg_match('/^GV(\d+)$/', (string) $code, $matches) ? (int) $matches[1] : 0)
            ->max() ?? 0;

        return 'GV' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    private function nextParentCode(): string
    {
        $max = ParentProfile::whereNotNull('parent_code')
            ->where('parent_code', 'like', 'PH%')
            ->pluck('parent_code')
            ->map(fn ($code) => preg_match('/^PH(\d+)$/', (string) $code, $matches) ? (int) $matches[1] : 0)
            ->filter()
            ->max() ?? 0;

        return 'PH' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private function syncImportedParentForStudent(Student $student, array $row): void
    {
        $phone = trim((string) $this->rowValue($row, ['sdt_phu_huynh', 'parent_phone']));
        if ($phone === '') {
            return;
        }

        $parent = ParentProfile::firstOrCreate(
            ['phone' => $phone],
            [
                'parent_code' => $this->nextParentCode(),
                'name' => trim((string) $this->rowValue($row, ['ho_ten_phu_huynh', 'parent_name'])) ?: 'Phụ huynh của ' . $student->name,
                'email' => trim((string) $this->rowValue($row, ['email_phu_huynh', 'parent_email'])) ?: null,
                'address' => trim((string) $this->rowValue($row, ['dia_chi_phu_huynh', 'parent_address', 'dia_chi'])) ?: null,
            ]
        );

        $parent->fill([
            'name' => trim((string) $this->rowValue($row, ['ho_ten_phu_huynh', 'parent_name'], $parent->name)) ?: $parent->name,
            'email' => trim((string) $this->rowValue($row, ['email_phu_huynh', 'parent_email'], $parent->email)) ?: $parent->email,
            'address' => trim((string) $this->rowValue($row, ['dia_chi_phu_huynh', 'parent_address', 'dia_chi'], $parent->address)) ?: $parent->address,
        ])->save();

        $parent->students()->syncWithoutDetaching([
            $student->id => ['relation' => ParentProfile::RELATION_GUARDIAN],
        ]);
        $this->ensureParentUser($parent);
    }

    private function linkedStudentCodes(string $value): array
    {
        return collect(preg_split('/[,;|]+/', $value) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function syncParentStudentCodes(ParentProfile $parent, string $value): void
    {
        $studentIds = [];
        foreach ($this->linkedStudentCodes($value) as $code) {
            $student = Student::where('student_code', $code)->orWhere('id', $code)->first();
            if ($student) {
                $studentIds[$student->id] = ['relation' => ParentProfile::RELATION_GUARDIAN];
            }
        }

        if ($studentIds !== []) {
            $parent->students()->syncWithoutDetaching($studentIds);
        }
    }

    private function parentPhoneConflicts(string $phone, string $parentCode = ''): bool
    {
        $phone = trim($phone);
        if ($phone === '') {
            return false;
        }

        $parent = $parentCode !== ''
            ? ParentProfile::where('parent_code', $parentCode)->first()
            : ParentProfile::where('phone', $phone)->first();

        return User::where('username', $phone)
            ->where(function ($query) use ($parent) {
                $query->where('role', '!=', 'parent')
                    ->orWhere(function ($parentQuery) use ($parent) {
                        $parentQuery->where('role', 'parent')
                            ->when($parent, fn ($inner) => $inner->where('parent_id', '!=', $parent->id));
                    });
            })
            ->exists();
    }

    private function ensureStudentUser(Student $student): void
    {
        User::updateOrCreate(
            ['username' => $student->student_code],
            [
                'full_name' => $student->name,
                'email' => $student->email,
                'phone' => $student->parent_phone,
                'role' => 'student',
                'student_id' => $student->id,
                'password_hash' => Hash::make($student->student_code),
                'is_active' => $student->status === Student::STATUS_STUDYING,
            ]
        );
    }

    private function ensureTeacherUser(Teacher $teacher): void
    {
        User::updateOrCreate(
            ['username' => $teacher->teacher_code],
            [
                'full_name' => $teacher->name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'role' => 'teacher',
                'teacher_id' => $teacher->id,
                'password_hash' => Hash::make('12345678'),
                'is_active' => $teacher->isWorking(),
            ]
        );
    }

    private function ensureParentUser(ParentProfile $parent): void
    {
        if (! $parent->phone) {
            return;
        }

        $conflict = User::where('username', $parent->phone)
            ->where(function ($query) use ($parent) {
                $query->where('role', '!=', 'parent')
                    ->orWhere(function ($parentQuery) use ($parent) {
                        $parentQuery->where('role', 'parent')
                            ->whereNotNull('parent_id')
                            ->where('parent_id', '!=', $parent->id);
                    });
            })
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'phone' => 'Số điện thoại phụ huynh đang trùng tài khoản khác.',
            ]);
        }

        User::updateOrCreate(
            ['username' => $parent->phone],
            [
                'full_name' => $parent->name,
                'email' => $parent->email,
                'phone' => $parent->phone,
                'role' => 'parent',
                'parent_id' => $parent->id,
                'password_hash' => Hash::make('12345678'),
                'is_active' => true,
            ]
        );
    }

    private function moduleRedirect(string $module, array $context): string
    {
        return match ($module) {
            'students' => route('students.index', array_filter(['class_id' => $context['class_id'] ?? null, 'school_year_id' => $context['school_year_id'] ?? null])),
            'teachers' => route('teachers.index'),
            'parents' => route('parents.index'),
            'scores' => route('scores.index'),
            'conduct' => route('conduct.index', array_filter(['class_id' => $context['class_id'] ?? null, 'semester_id' => $context['semester_id'] ?? null])),
            'attendance' => route('attendance.index', array_filter(['class_id' => $context['class_id'] ?? null, 'semester_id' => $context['semester_id'] ?? null, 'date' => $context['attendance_date'] ?? null, 'attendance_type' => $context['attendance_type'] ?? null])),
        };
    }
}
