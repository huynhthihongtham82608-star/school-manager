<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->effectiveSchoolYearId($request);
        $selectedGrade = $request->query('grade_level', 'all');
        $selectedClassId = $request->query('class_id', 'all');
        $selectedStatus = $request->query('status', 'all');
        $selectedGender = $request->query('gender', 'all');
        $readOnly = $this->isHistoricalReadOnly();

        $students = Student::with(['classRoom.schoolYear', 'schoolYear', 'user'])
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->when(in_array($selectedGrade, ['10', '11', '12'], true), function ($query) use ($selectedGrade) {
                $query->whereHas('classRoom', fn ($classQuery) => $classQuery->where('grade_level', $selectedGrade));
            })
            ->when($selectedClassId !== 'all', fn ($query) => $query->where('class_id', $selectedClassId))
            ->when($selectedStatus !== 'all', fn ($query) => $query->where('status', $selectedStatus))
            ->when($selectedGender !== 'all', fn ($query) => $query->where('gender', $selectedGender))
            ->orderBy('student_code')
            ->get();

        $classes = SchoolClass::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $importClasses = $this->availableClassesForInput();
        $years = SchoolYear::whereNull('archived_at')
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->get();

        $deleteChecks = $students->mapWithKeys(fn (Student $student) => [
            (string) $student->getKey() => $this->deleteCheck($student),
        ]);

        return view('students.index', [
            'students' => $students,
            'classes' => $classes,
            'importClasses' => $importClasses,
            'years' => $years,
            'selectedYearId' => $selectedYearId,
            'selectedGrade' => $selectedGrade,
            'selectedClassId' => $selectedClassId,
            'selectedStatus' => $selectedStatus,
            'selectedGender' => $selectedGender,
            'readOnly' => $readOnly,
            'deleteChecks' => $deleteChecks,
        ]);
    }

    public function create()
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('students.index')->withErrors([
                'student' => 'Đang xem dữ liệu lịch sử, không thể thêm học sinh.',
            ]);
        }

        return view('students.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->denyHistoricalWrite();
        $data = $this->validatedData($request);
        $class = SchoolClass::with(['schoolYear', 'semester'])->findOrFail($data['class_id']);
        $this->ensureClassCanReceiveStudent($class, $data['school_year_id']);

        return DB::transaction(function () use ($request, $data, $class) {
            $data['student_code'] = $this->generateStudentCode($data['enrollment_date']);
            $data['avatar'] = $this->storeAvatar($request);

            $student = Student::create($data);
            $this->createStudentUser($student);
            $this->recordClassHistory($student, null, $student->class_id, $student->enrollment_date, 'Nhập học');

            AuditLogger::log('student_created', Student::class, (string) $student->getKey(), 'Tạo học sinh ' . $student->name);

            return redirect()->route('students.index', ['school_year_id' => $student->school_year_id])
                ->with('success', 'Đã thêm học sinh. Tài khoản đăng nhập mặc định là mã học sinh.');
        });
    }

    public function import(Request $request)
    {
        $this->denyHistoricalWrite();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $class = SchoolClass::with(['schoolYear', 'semester', 'students'])->findOrFail($validated['class_id']);
        $this->ensureClassCanReceiveStudent($class, (string) $class->school_year_id);

        $rows = $this->readImportRows($request->file('file')->getRealPath(), $request->file('file')->getClientOriginalExtension());

        if ($rows === []) {
            return back()->withErrors(['file' => 'File import không có dữ liệu hợp lệ.']);
        }

        if ($class->currentStudentCount() + count($rows) > $class->maxCapacity()) {
            return back()->withErrors([
                'file' => 'Số học sinh import vượt quá sức chứa tối đa của lớp.',
            ]);
        }

        $created = 0;

        DB::transaction(function () use ($rows, $class, &$created) {
            $phonesInFile = [];

            foreach ($rows as $index => $row) {
                $name = trim((string) ($row['ho_ten'] ?? ''));
                $phone = trim((string) ($row['sdt_phu_huynh'] ?? ''));

                if ($name === '') {
                    throw ValidationException::withMessages([
                        'file' => 'Dòng ' . ($index + 2) . ': Họ tên là bắt buộc.',
                    ]);
                }

                if ($phone !== '') {
                    if (in_array($phone, $phonesInFile, true) || Student::where('parent_phone', $phone)->exists()) {
                        throw ValidationException::withMessages([
                            'file' => 'Dòng ' . ($index + 2) . ': SĐT phụ huynh đã tồn tại.',
                        ]);
                    }

                    $phonesInFile[] = $phone;
                }

                $enrollmentDate = $this->parseDateValue($row['ngay_nhap_hoc'] ?? null) ?: now()->toDateString();
                $admissionType = $this->normalizeAdmissionType($row['loai_nhap_hoc'] ?? null, $index + 2);
                $transferGradeLevel = $this->normalizeTransferGradeLevel($row['khoi_hien_tai'] ?? null, $index + 2)
                    ?: (int) $class->grade_level;
                $status = $this->normalizeStudentStatus($row['trang_thai'] ?? null, $index + 2);

                $student = Student::create([
                    'student_code' => $this->generateStudentCode($enrollmentDate),
                    'name' => $name,
                    'dob' => $this->parseDateValue($row['ngay_sinh'] ?? null),
                    'gender' => $this->normalizeGender($row['gioi_tinh'] ?? null, $index + 2),
                    'address' => $row['dia_chi'] ?? null,
                    'place_of_birth' => $row['noi_sinh'] ?? null,
                    'ethnicity' => trim((string) ($row['dan_toc'] ?? '')) ?: 'Kinh',
                    'religion' => trim((string) ($row['ton_giao'] ?? '')) ?: 'Không',
                    'parent_phone' => $phone ?: null,
                    'email' => trim((string) ($row['email_phu_huynh'] ?? '')) ?: null,
                    'note' => trim((string) ($row['ghi_chu'] ?? '')) ?: null,
                    'class_id' => $class->id,
                    'school_year_id' => $class->school_year_id,
                    'enrollment_date' => $enrollmentDate,
                    'admission_type' => $admissionType,
                    'previous_school' => $admissionType === Student::ADMISSION_TRANSFER ? ($row['truong_cu'] ?? null) : null,
                    'transfer_grade_level' => $admissionType === Student::ADMISSION_TRANSFER ? $transferGradeLevel : null,
                    'previous_class' => $admissionType === Student::ADMISSION_TRANSFER ? ($row['lop_cu'] ?? null) : null,
                    'status' => $status,
                ]);

                $this->createStudentUser($student);
                $this->recordClassHistory($student, null, $student->class_id, $student->enrollment_date, 'Import học sinh');
                $created++;
            }
        });

        AuditLogger::log('students_imported', Student::class, null, 'Import ' . $created . ' học sinh vào lớp ' . $class->name);

        return redirect()->route('students.index', ['school_year_id' => $class->school_year_id, 'class_id' => $class->id])
            ->with('success', 'Đã import ' . $created . ' học sinh.');
    }

    public function importTemplate()
    {
        $headers = [
            'Họ tên',
            'Ngày sinh',
            'Giới tính',
            'SĐT phụ huynh',
            'Email phụ huynh',
            'Địa chỉ',
            'Nơi sinh',
            'Dân tộc',
            'Tôn giáo',
            'Ghi chú',
            'Ngày nhập học',
            'Loại nhập học',
            'Trạng thái',
            'Trường cũ',
            'Khối hiện tại',
        ];

        $rows = [
            ['Nguyễn Văn An', '15/09/2010', 'Nam', '0901234567', 'ph_an@example.com', 'Phường 1, Quận 1', 'TP Hồ Chí Minh', 'Kinh', 'Không', '', now()->format('d/m/Y'), 'Tuyển mới', 'Đang học', '', ''],
            ['Trần Thị Bình', '20/04/2009', 'Nữ', '0912345678', 'ph_binh@example.com', 'Phường 2, Quận 3', 'Đồng Nai', 'Kinh', 'Không', 'Học sinh chuyển trường', now()->format('d/m/Y'), 'Chuyển trường', 'Đang học', 'THCS Nguyễn Du', '11'],
        ];

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'mau_import_hoc_sinh.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function edit(Student $student)
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('students.index')->withErrors([
                'student' => 'Đang xem dữ liệu lịch sử, không thể chỉnh sửa học sinh.',
            ]);
        }

        $student->load(['classRoom.schoolYear', 'schoolYear']);
        $data = $this->formData();

        if ($student->classRoom && ! $data['classes']->contains('id', $student->class_id)) {
            $data['classes']->push($student->classRoom);
            $data['classes'] = $data['classes']->sortBy([
                ['grade_level', 'asc'],
                ['name', 'asc'],
            ])->values();
        }

        return view('students.edit', $data + ['student' => $student]);
    }

    public function update(Request $request, Student $student)
    {
        $this->denyHistoricalWrite();
        $data = $this->validatedData($request, $student);
        $class = SchoolClass::with(['schoolYear', 'semester'])->findOrFail($data['class_id']);
        $this->ensureClassCanReceiveStudent($class, $data['school_year_id'], $student);

        $oldClassId = $student->class_id;
        $oldStatus = $student->status;
        $avatar = $this->storeAvatar($request, $student);

        if ($avatar !== null) {
            $data['avatar'] = $avatar;
        }

        $student->update($data);

        AuditLogger::log('student_updated', Student::class, (string) $student->getKey(), 'Cập nhật học sinh ' . $student->name);

        if ((string) $oldClassId !== (string) $student->class_id) {
            $this->recordClassHistory($student, $oldClassId, $student->class_id, now()->toDateString(), 'Đổi lớp');
            AuditLogger::log('student_class_changed', Student::class, (string) $student->getKey(), 'Đổi lớp học sinh ' . $student->name);
        }

        if ((string) $oldStatus !== (string) $student->status) {
            AuditLogger::log('student_status_changed', Student::class, (string) $student->getKey(), 'Đổi trạng thái học sinh ' . $student->name);
        }

        return redirect()->route('students.index', ['school_year_id' => $student->school_year_id])
            ->with('success', 'Đã cập nhật học sinh.');
    }

    public function destroy(Student $student)
    {
        $this->denyHistoricalWrite();
        $deleteCheck = $this->deleteCheck($student);

        if (! $deleteCheck['allowed']) {
            return back()->withErrors(['student' => $deleteCheck['message']]);
        }

        $name = $student->name;
        $studentId = (string) $student->getKey();

        DB::transaction(function () use ($student) {
            $student->user?->delete();
            $student->delete();
        });

        AuditLogger::log('student_deleted', Student::class, $studentId, 'Xóa học sinh ' . $name);

        return redirect()->route('students.index')->with('success', 'Đã xóa học sinh.');
    }

    public function resetPassword(Student $student)
    {
        $this->denyHistoricalWrite();

        $user = $student->user ?: User::create([
            'username' => $student->student_code,
            'role' => 'student',
            'student_id' => $student->id,
            'password_hash' => Hash::make('12345678'),
            'is_active' => 1,
        ]);

        $user->update([
            'password_hash' => Hash::make('12345678'),
            'force_change_password' => true,
            'is_active' => 1,
        ]);

        AuditLogger::log(
            'student_password_reset',
            Student::class,
            (string) $student->getKey(),
            'Đặt lại mật khẩu học sinh ' . $student->name . ' bởi ' . (auth()->user()?->display_name ?? auth()->user()?->username ?? 'admin') . ' lúc ' . now()->format('d/m/Y H:i:s')
        );

        return back()->with('success', 'Đã đặt lại mật khẩu học sinh về 12345678.');
    }

    private function formData(): array
    {
        return [
            'classes' => $this->availableClassesForInput(),
            'years' => SchoolYear::whereNull('archived_at')->orderByDesc('start_date')->get(),
        ];
    }

    private function availableClassesForInput()
    {
        return SchoolClass::with(['schoolYear', 'semester', 'students'])
            ->where('status', SchoolClass::STATUS_ACTIVE)
            ->whereHas('schoolYear', fn ($query) => $query->whereNull('archived_at'))
            ->where(function ($query) {
                $query->whereDoesntHave('semester')
                    ->orWhereHas('semester', fn ($semesterQuery) => $semesterQuery
                        ->where('status', '!=', 'locked')
                        ->where('status', '!=', 'archived')
                        ->whereNull('archived_at'));
            })
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
    }

    private function validatedData(Request $request, ?Student $student = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(array_keys(Student::genderLabels()))],
            'dob' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'ethnicity_choice' => ['nullable', Rule::in(['Kinh', 'Khác'])],
            'ethnicity_custom' => ['nullable', 'string', 'max:100', 'required_if:ethnicity_choice,Khác'],
            'religion_choice' => ['nullable', Rule::in(['Không', 'Khác'])],
            'religion_custom' => ['nullable', 'string', 'max:100', 'required_if:religion_choice,Khác'],
            'parent_phone' => ['nullable', 'string', 'max:50', Rule::unique('students', 'parent_phone')->ignore($student?->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'enrollment_date' => ['required', 'date'],
            'admission_type' => ['required', Rule::in(array_keys(Student::admissionTypeLabels()))],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'transfer_grade_level' => ['nullable', 'integer', Rule::in([10, 11, 12])],
            'previous_class' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'note' => ['nullable', 'string', 'max:2000'],
            'class_id' => ['required', 'exists:classes,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
            'status' => ['required', Rule::in(array_keys(Student::statuses()))],
        ]);

        $class = SchoolClass::findOrFail($validated['class_id']);

        if ((string) $class->school_year_id !== (string) $validated['school_year_id']) {
            throw ValidationException::withMessages([
                'class_id' => 'Lớp học không thuộc năm học đã chọn.',
            ]);
        }

        if ($validated['admission_type'] !== Student::ADMISSION_TRANSFER) {
            $validated['previous_school'] = null;
            $validated['transfer_grade_level'] = null;
            $validated['previous_class'] = null;
        } elseif (empty($validated['transfer_grade_level'])) {
            $validated['transfer_grade_level'] = (int) $class->grade_level;
        }

        $validated['ethnicity'] = ($validated['ethnicity_choice'] ?? 'Kinh') === 'Khác'
            ? trim((string) ($validated['ethnicity_custom'] ?? ''))
            : 'Kinh';
        $validated['religion'] = ($validated['religion_choice'] ?? 'Không') === 'Khác'
            ? trim((string) ($validated['religion_custom'] ?? ''))
            : 'Không';

        unset(
            $validated['ethnicity_choice'],
            $validated['ethnicity_custom'],
            $validated['religion_choice'],
            $validated['religion_custom'],
        );

        return $validated;
    }

    private function effectiveSchoolYearId(Request $request): ?string
    {
        if ($this->isHistoricalReadOnly()) {
            return session('history_school_year_id');
        }

        if ($request->query('school_year_id')) {
            return $request->query('school_year_id');
        }

        return SchoolYear::where('is_active', true)->value('id')
            ?: SchoolYear::orderByDesc('start_date')->orderByDesc('created_at')->value('id');
    }

    private function ensureClassCanReceiveStudent(SchoolClass $class, string $schoolYearId, ?Student $student = null): void
    {
        if ((string) $class->school_year_id !== (string) $schoolYearId) {
            throw ValidationException::withMessages([
                'class_id' => 'Lớp học không thuộc năm học đã chọn.',
            ]);
        }

        if (! $class->isActive()) {
            throw ValidationException::withMessages([
                'class_id' => 'Chỉ có thể thêm học sinh vào lớp đang hoạt động.',
            ]);
        }

        if ($class->isArchived() || $class->isLocked() || $class->schoolYear?->isArchived() || $class->semester?->isArchived() || $class->semester?->isLocked()) {
            throw ValidationException::withMessages([
                'class_id' => 'Không thể thêm hoặc chuyển học sinh vào lớp đã khóa/lưu trữ.',
            ]);
        }

        $currentCount = Student::where('class_id', $class->id)
            ->when($student, fn ($query) => $query->whereKeyNot($student->getKey()))
            ->count();

        if ($currentCount >= $class->maxCapacity()) {
            throw ValidationException::withMessages([
                'class_id' => 'Lớp đã đạt sĩ số tối đa.',
            ]);
        }
    }

    private function generateStudentCode(string $enrollmentDate): string
    {
        $year = Carbon::parse($enrollmentDate)->format('Y');
        $prefix = 'HS' . $year;
        $latestNumber = Student::where('student_code', 'like', $prefix . '%')
            ->pluck('student_code')
            ->map(function ($code) use ($prefix) {
                return preg_match('/^' . preg_quote($prefix, '/') . '(\d{4})$/', (string) $code, $matches)
                    ? (int) $matches[1]
                    : null;
            })
            ->filter()
            ->max();
        $nextNumber = ($latestNumber ?: 0) + 1;

        do {
            $code = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Student::where('student_code', $code)->exists() || User::where('username', $code)->exists());

        return $code;
    }

    private function createStudentUser(Student $student): void
    {
        User::create([
            'username' => $student->student_code,
            'role' => 'student',
            'student_id' => $student->id,
            'password_hash' => Hash::make($student->student_code),
            'is_active' => 1,
        ]);
    }

    private function storeAvatar(Request $request, ?Student $student = null): ?string
    {
        if (! $request->hasFile('avatar')) {
            return null;
        }

        $path = $request->file('avatar')->store('students', 'public');

        if ($student?->avatar) {
            Storage::disk('public')->delete($student->avatar);
        }

        return $path;
    }

    private function deleteCheck(Student $student): array
    {
        if (Schema::hasTable('score_headers') && $student->scoreHeaders()->exists()) {
            return ['allowed' => false, 'message' => 'Không thể xóa học sinh vì đã phát sinh điểm. Hãy đổi trạng thái nếu học sinh không còn học.'];
        }

        if (Schema::hasTable('attendance_records') && $student->attendanceRecords()->exists()) {
            return ['allowed' => false, 'message' => 'Không thể xóa học sinh vì đã phát sinh điểm danh. Hãy đổi trạng thái nếu học sinh không còn học.'];
        }

        if (Schema::hasTable('conducts') && $student->conductRecords()->exists()) {
            return ['allowed' => false, 'message' => 'Không thể xóa học sinh vì đã phát sinh hạnh kiểm. Hãy đổi trạng thái nếu học sinh không còn học.'];
        }

        return ['allowed' => true, 'message' => null];
    }

    private function recordClassHistory(Student $student, ?string $fromClassId, ?string $toClassId, ?string $date, string $note): void
    {
        if (! Schema::hasTable('student_transfers')) {
            return;
        }

        DB::table('student_transfers')->insert([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'from_class_id' => $fromClassId,
            'to_class_id' => $toClassId,
            'transfer_date' => $date ?: now()->toDateString(),
            'note' => $note,
        ]);
    }

    private function readImportRows(string $path, string $extension): array
    {
        $extension = Str::lower($extension);

        return $extension === 'xlsx'
            ? $this->readXlsxRows($path)
            : $this->readCsvRows($path);
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            return [];
        }

        $firstLine = fgets($handle) ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $headers = null;
        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $data);
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = trim((string) ($data[$index] ?? ''));
                }
            }

            if (array_filter($row, fn ($value) => $value !== '')) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);

            foreach ($shared->si ?? [] as $item) {
                $texts = [];

                foreach ($item->xpath('.//t') as $text) {
                    $texts[] = (string) $text;
                }

                $sharedStrings[] = implode('', $texts);
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        $rows = [];
        $headers = null;

        foreach ($sheet->sheetData->row ?? [] as $rowXml) {
            $values = [];

            foreach ($rowXml->c as $cell) {
                $ref = (string) $cell['r'];
                $columnIndex = $this->excelColumnIndex(preg_replace('/\d+/', '', $ref));
                $value = (string) ($cell->v ?? '');

                if ((string) $cell['t'] === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                }

                $values[$columnIndex] = trim($value);
            }

            ksort($values);
            $normalizedValues = [];
            $max = $values ? max(array_keys($values)) : -1;

            for ($i = 0; $i <= $max; $i++) {
                $normalizedValues[] = $values[$i] ?? '';
            }

            if ($headers === null) {
                $headers = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $normalizedValues);
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = $normalizedValues[$index] ?? '';
                }
            }

            if (array_filter($row, fn ($value) => trim((string) $value) !== '')) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function normalizeImportHeader(string $header): string
    {
        $header = trim(Str::of($header)->replace("\xEF\xBB\xBF", '')->toString());
        $header = Str::ascii($header);
        $header = Str::lower($header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = trim((string) $header, '_');

        return match ($header) {
            'ho_ten', 'ten', 'name' => 'ho_ten',
            'ngay_sinh', 'dob' => 'ngay_sinh',
            'gioi_tinh', 'gender' => 'gioi_tinh',
            'ngay_nhap_hoc', 'enrollment_date' => 'ngay_nhap_hoc',
            'loai_nhap_hoc', 'admission_type' => 'loai_nhap_hoc',
            'trang_thai', 'status' => 'trang_thai',
            'truong_cu', 'previous_school' => 'truong_cu',
            'khoi_hien_tai', 'transfer_grade_level' => 'khoi_hien_tai',
            'lop_cu', 'previous_class' => 'lop_cu',
            'sdt_phu_huynh', 'so_dien_thoai_phu_huynh', 'dien_thoai_phu_huynh', 'parent_phone' => 'sdt_phu_huynh',
            'email_phu_huynh', 'email', 'parent_email' => 'email_phu_huynh',
            'dia_chi', 'address' => 'dia_chi',
            'noi_sinh', 'place_of_birth' => 'noi_sinh',
            'dan_toc', 'ethnicity' => 'dan_toc',
            'ton_giao', 'religion' => 'ton_giao',
            'ghi_chu', 'note' => 'ghi_chu',
            default => $header,
        };
    }

    private function normalizeGender(mixed $value, int $rowNumber): string
    {
        $value = Str::lower(Str::ascii(trim((string) $value)));

        return match ($value) {
            'nam' => Student::GENDER_NAM,
            'nu' => Student::GENDER_NU,
            default => throw ValidationException::withMessages([
                'file' => 'Dòng ' . $rowNumber . ': Giới tính chỉ được nhập Nam hoặc Nữ.',
            ]),
        };
    }

    private function normalizeAdmissionType(mixed $value, int $rowNumber): string
    {
        $value = Str::lower(Str::ascii(trim((string) $value)));

        if ($value === '') {
            return Student::ADMISSION_NEW;
        }

        return match ($value) {
            'tuyen_moi', 'tuyen moi', 'new' => Student::ADMISSION_NEW,
            'chuyen_truong', 'chuyen truong', 'transfer' => Student::ADMISSION_TRANSFER,
            default => throw ValidationException::withMessages([
                'file' => 'DÃ²ng ' . $rowNumber . ': Loáº¡i nháº­p há»c chá»‰ Ä‘Æ°á»£c nháº­p Tuyá»ƒn má»›i hoáº·c Chuyá»ƒn trÆ°á»ng.',
            ]),
        };
    }

    private function normalizeStudentStatus(mixed $value, int $rowNumber): string
    {
        $value = Str::lower(Str::ascii(trim((string) $value)));

        if ($value === '') {
            return Student::STATUS_STUDYING;
        }

        return match ($value) {
            'dang_hoc', 'dang hoc', 'studying' => Student::STATUS_STUDYING,
            'bao_luu', 'bao luu', 'reserved' => Student::STATUS_RESERVED,
            'chuyen_truong', 'chuyen truong', 'transferred' => Student::STATUS_TRANSFERRED,
            'tot_nghiep', 'tot nghiep', 'graduated' => Student::STATUS_GRADUATED,
            'nghi_hoc', 'nghi hoc', 'dropped', 'inactive' => Student::STATUS_DROPPED,
            default => throw ValidationException::withMessages([
                'file' => 'Dòng ' . $rowNumber . ': Trạng thái không hợp lệ.',
            ]),
        };
    }

    private function normalizeTransferGradeLevel(mixed $value, int $rowNumber): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! in_array((int) $value, [10, 11, 12], true)) {
            throw ValidationException::withMessages([
                'file' => 'DÃ²ng ' . $rowNumber . ': Khá»‘i hiá»‡n táº¡i chá»‰ Ä‘Æ°á»£c lÃ  10, 11 hoáº·c 12.',
            ]);
        }

        return (int) $value;
    }

    private function parseDateValue(mixed $value): ?string
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
                $date = Carbon::createFromFormat($format, $value);
                return $date->toDateString();
            } catch (\Throwable) {
                // Try the next supported format.
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'file' => 'Ngày sinh trong file import không hợp lệ: ' . $value,
            ]);
        }
    }

    private function excelColumnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi học sinh.',
            ]);
        }
    }
}
