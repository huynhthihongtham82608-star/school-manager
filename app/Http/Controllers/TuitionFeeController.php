<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\TuitionFee;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TuitionFeeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $selectedYearId = $this->selectedSchoolYearId($request);
        $selectedSemesterId = $request->query('semester_id') ?: $this->selectedSemesterId($request);
        $selectedClassId = $request->query('class_id', 'all');
        $selectedStatus = $request->query('status', 'all');
        $readOnly = $this->isHistoricalReadOnly();

        $classes = SchoolClass::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $semesters = Semester::with('schoolYear')
            ->when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        if (! $selectedSemesterId && $semesters->isNotEmpty()) {
            $selectedSemesterId = optional($semesters->first(fn ($semester) => $semester->isActive()))->id
                ?? $semesters->first()->id;
        }

        if ($selectedSemesterId && ! $readOnly) {
            $this->ensureFeeRows($selectedSemesterId, $selectedClassId);
        }

        $fees = TuitionFee::with(['student', 'classRoom', 'semester.schoolYear'])
            ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
            ->when($selectedClassId !== 'all', fn ($query) => $query->where('class_id', $selectedClassId))
            ->when($selectedStatus !== 'all', fn ($query) => $query->where('status', $selectedStatus))
            ->whereHas('student')
            ->orderByRaw("(select student_code from students where students.id = tuition_fees.student_id) asc")
            ->get();

        return view('tuition_fees.index', [
            'fees' => $fees,
            'classes' => $classes,
            'semesters' => $semesters,
            'statusLabels' => TuitionFee::statusLabels(),
            'paymentMethodLabels' => TuitionFee::paymentMethodLabels(),
            'exemptionLabels' => TuitionFee::exemptionLabels(),
            'selectedSemesterId' => $selectedSemesterId,
            'selectedClassId' => $selectedClassId,
            'selectedStatus' => $selectedStatus,
            'qrImageUrl' => $this->tuitionQrImageUrl(),
            'readOnly' => $readOnly,
        ]);
    }

    public function parentPortal(Request $request)
    {
        $user = Auth::user();
        abort_unless($user?->isParent() && $user->parentProfile, 403);

        $children = $user->parentProfile->students()
            ->with('classRoom')
            ->orderBy('student_code')
            ->get();
        $student = $children->firstWhere('id', session('selected_parent_student_id')) ?: $children->first();
        $selectedSemesterId = $request->query('semester_id') ?: $this->selectedSemesterId($request);
        $semesters = Semester::with('schoolYear')
            ->when($this->selectedSchoolYearId($request), fn ($query) => $query->where('school_year_id', $this->selectedSchoolYearId($request)))
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $fee = $student && $selectedSemesterId
            ? $this->ensureStudentFeeRow($student, $selectedSemesterId)
            : null;

        return view('tuition_fees.parent', [
            'children' => $children,
            'student' => $student,
            'fee' => $fee,
            'feeItems' => $fee ? $fee->normalizedFeeItems() : TuitionFee::configuredFeeItems(),
            'semesters' => $semesters,
            'selectedSemesterId' => $selectedSemesterId,
            'qrImageUrl' => $this->tuitionQrImageUrl(),
        ]);
    }

    public function homeroomPortal(Request $request)
    {
        $user = Auth::user();
        abort_unless($user?->isHomeroom() && $user->teacher, 403);

        $selectedSemesterId = $request->query('semester_id') ?: $this->selectedSemesterId($request);
        $homeroomClass = SchoolClass::with(['students' => fn ($query) => $query->where('status', Student::STATUS_STUDYING)->orderBy('student_code')])
            ->where('homeroom_teacher_id', $user->teacher->id)
            ->first();

        $semesters = Semester::with('schoolYear')
            ->when($homeroomClass?->school_year_id, fn ($query) => $query->where('school_year_id', $homeroomClass->school_year_id))
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        if ($selectedSemesterId && $homeroomClass) {
            $this->ensureFeeRows($selectedSemesterId, (string) $homeroomClass->id);
        }

        $fees = $homeroomClass && $selectedSemesterId
            ? TuitionFee::with(['student', 'classRoom', 'semester.schoolYear'])
                ->where('class_id', $homeroomClass->id)
                ->where('semester_id', $selectedSemesterId)
                ->whereHas('student')
                ->orderByRaw("(select student_code from students where students.id = tuition_fees.student_id) asc")
                ->get()
            : collect();

        return view('tuition_fees.homeroom', [
            'homeroomClass' => $homeroomClass,
            'fees' => $fees,
            'semesters' => $semesters,
            'selectedSemesterId' => $selectedSemesterId,
        ]);
    }

    public function update(Request $request, TuitionFee $tuitionFee)
    {
        $this->authorizeAdmin();
        $this->denyHistoricalWrite();

        $data = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys(TuitionFee::paymentMethodLabels()))],
            'exemption_type' => ['required', Rule::in(array_keys(TuitionFee::exemptionLabels()))],
            'fee_items' => ['required', 'array'],
            'fee_items.*.key' => ['required', 'string', 'max:80'],
            'fee_items.*.status' => ['required', Rule::in(array_keys(TuitionFee::statusLabels()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $submittedStatuses = collect($data['fee_items'])->keyBy('key');
        $items = collect(TuitionFee::configuredFeeItems())
            ->map(function (array $item) use ($submittedStatuses) {
                $status = $submittedStatuses->get($item['key'])['status'] ?? TuitionFee::STATUS_UNPAID;

                return [
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'amount' => round((float) $item['amount'], 2),
                    'status' => in_array($status, array_keys(TuitionFee::statusLabels()), true) ? $status : TuitionFee::STATUS_UNPAID,
                    'exemption_label' => '',
                ];
            })
            ->values()
            ->all();
        $items = TuitionFee::applyExemptionToItems($items, $data['exemption_type']);
        $amount = collect($items)->sum('amount');
        $status = collect($items)->every(fn (array $item) => $item['status'] === TuitionFee::STATUS_PAID)
            ? TuitionFee::STATUS_PAID
            : TuitionFee::STATUS_UNPAID;

        $tuitionFee->update([
            'amount' => $amount,
            'fee_items' => $items,
            'status' => $status,
            'payment_method' => $data['payment_method'],
            'exemption_type' => $data['exemption_type'],
            'paid_at' => $status === TuitionFee::STATUS_PAID ? ($tuitionFee->paid_at ?? now()) : null,
            'note' => trim((string) ($data['note'] ?? '')),
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::log('tuition_fee_updated', TuitionFee::class, (string) $tuitionFee->getKey(), 'Cập nhật học phí của ' . ($tuitionFee->student?->name ?? 'học sinh'));

        return back()->with('success', 'Đã cập nhật học phí.');
    }

    private function ensureFeeRows(?string $semesterId, string $selectedClassId): void
    {
        $semester = $semesterId ? Semester::find($semesterId) : null;

        if (! $semester) {
            return;
        }

        Student::with('classRoom')
            ->where('school_year_id', $semester->school_year_id)
            ->when($selectedClassId !== 'all', fn ($query) => $query->where('class_id', $selectedClassId))
            ->where('status', Student::STATUS_STUDYING)
            ->orderBy('student_code')
            ->get()
            ->each(function (Student $student) use ($semester) {
                TuitionFee::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                    ],
                    [
                        'class_id' => $student->class_id,
                        'school_year_id' => $semester->school_year_id,
                        'amount' => collect(TuitionFee::configuredFeeItems())->sum('amount'),
                        'fee_items' => TuitionFee::configuredFeeItems(),
                        'status' => TuitionFee::STATUS_UNPAID,
                        'payment_method' => TuitionFee::PAYMENT_CASH,
                        'exemption_type' => TuitionFee::EXEMPTION_DEFAULT,
                        'updated_by' => Auth::id(),
                    ]
                );
            });
    }

    private function ensureStudentFeeRow(Student $student, ?string $semesterId): ?TuitionFee
    {
        $semester = $semesterId ? Semester::find($semesterId) : null;

        if (! $semester) {
            return null;
        }

        return TuitionFee::firstOrCreate(
            [
                'student_id' => $student->id,
                'semester_id' => $semester->id,
            ],
            [
                'class_id' => $student->class_id,
                'school_year_id' => $semester->school_year_id,
                'amount' => collect(TuitionFee::configuredFeeItems())->sum('amount'),
                'fee_items' => TuitionFee::configuredFeeItems(),
                'status' => TuitionFee::STATUS_UNPAID,
                'payment_method' => TuitionFee::PAYMENT_CASH,
                'exemption_type' => TuitionFee::EXEMPTION_DEFAULT,
                'updated_by' => Auth::id(),
            ]
        )->load(['student', 'classRoom', 'semester.schoolYear']);
    }

    private function tuitionQrImageUrl(): ?string
    {
        $path = Setting::valueOf('tuition_qr_image');

        return $path ? Storage::url($path) : null;
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();

        if ($user?->isAdmin() || $user?->isStaff()) {
            return;
        }

        abort(403, 'Chỉ Admin được quản lý học phí.');
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi học phí.',
            ]);
        }
    }
}
