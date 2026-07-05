<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectPeriodNorm;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubjectController extends Controller
{
    private const GRADE_LEVELS = [10, 11, 12];

    public function index(Request $request)
    {
        $selectedStatus = $request->query('status', 'all');
        $readOnly = $this->isHistoricalReadOnly();

        $subjects = Subject::with('periodNorms')
            ->when($selectedStatus !== 'all', function ($query) use ($selectedStatus) {
                $query->where('status', $selectedStatus);
            })
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        return view('subjects.index', [
            'subjects' => $subjects,
            'selectedStatus' => $selectedStatus,
            'readOnly' => $readOnly,
            'gradeLevels' => self::GRADE_LEVELS,
        ]);
    }

    public function create()
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('subjects.index')->withErrors([
                'subject' => 'Đang xem dữ liệu lịch sử, không thể thêm môn học.',
            ]);
        }

        return view('subjects.create', [
            'gradeLevels' => self::GRADE_LEVELS,
        ]);
    }

    public function store(Request $request)
    {
        $this->denyHistoricalWrite();

        [$data, $periodNorms] = $this->validatedPayload($request);

        $subject = DB::transaction(function () use ($data, $periodNorms) {
            $subject = Subject::create($data);
            $this->syncPeriodNorms($subject, $periodNorms);

            AuditLogger::log('subject_created', Subject::class, (string) $subject->getKey(), 'Tạo môn học ' . $subject->name);

            return $subject;
        });

        return redirect()->route('subjects.index')->with('success', 'Đã thêm môn học.');
    }

    public function edit(Subject $subject)
    {
        if ($this->isHistoricalReadOnly()) {
            return redirect()->route('subjects.index')->withErrors([
                'subject' => 'Đang xem dữ liệu lịch sử, không thể chỉnh sửa môn học.',
            ]);
        }

        $subject->load('periodNorms');

        return view('subjects.edit', [
            'subject' => $subject,
            'isUsed' => $subject->isUsed(),
            'gradeLevels' => self::GRADE_LEVELS,
        ]);
    }

    public function update(Request $request, Subject $subject)
    {
        $this->denyHistoricalWrite();

        [$data, $periodNorms] = $this->validatedPayload($request, $subject);
        $oldStatus = $subject->status;

        if ($subject->isUsed() && $data['code'] !== $subject->code) {
            throw ValidationException::withMessages([
                'code' => 'Môn học đã được sử dụng, không thể sửa mã môn.',
            ]);
        }

        DB::transaction(function () use ($subject, $data, $periodNorms, $oldStatus) {
            $subject->update($data);
            $this->syncPeriodNorms($subject, $periodNorms);

            AuditLogger::log('subject_updated', Subject::class, (string) $subject->getKey(), 'Sửa môn học ' . $subject->name);

            if ($oldStatus !== $subject->status) {
                AuditLogger::log('subject_status_changed', Subject::class, (string) $subject->getKey(), 'Đổi trạng thái môn học ' . $subject->name . ' sang ' . $subject->statusLabel());
            }
        });

        return redirect()->route('subjects.index')->with('success', 'Đã cập nhật môn học.');
    }

    public function destroy(Subject $subject)
    {
        $this->denyHistoricalWrite();

        if (! $subject->canDelete()) {
            return back()->withErrors([
                'subject' => 'Không thể xóa môn học vì đã phát sinh phân công, thời khóa biểu hoặc điểm số.',
            ]);
        }

        $subject->load('periodNorms');
        $subjectName = $subject->name;
        $subjectId = (string) $subject->getKey();

        DB::transaction(function () use ($subject, $subjectName, $subjectId) {
            foreach ($subject->periodNorms as $periodNorm) {
                AuditLogger::log(
                    'subject_period_norm_deleted',
                    SubjectPeriodNorm::class,
                    (string) $periodNorm->getKey(),
                    'Xóa định mức tiết khối ' . $periodNorm->grade_level . ' của môn ' . $subjectName
                );
            }

            $subject->periodNorms()->delete();
            $subject->delete();

            AuditLogger::log('subject_deleted', Subject::class, $subjectId, 'Xóa môn học ' . $subjectName);
        });

        return redirect()->route('subjects.index')->with('success', 'Đã xóa môn học.');
    }

    private function validatedPayload(Request $request, ?Subject $subject = null): array
    {
        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
            'name' => trim((string) $request->input('name')),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'code')->ignore($subject?->getKey()),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'name')->ignore($subject?->getKey()),
            ],
            'credit' => ['required', 'integer', 'min:1', 'max:10'],
            'type' => ['required', Rule::in(array_keys(Subject::TYPES))],
            'status' => ['required', Rule::in(array_keys(Subject::STATUSES))],
            'period_norms' => ['nullable', 'array'],
            'period_norms.10' => ['nullable', 'integer', 'min:1', 'max:10'],
            'period_norms.11' => ['nullable', 'integer', 'min:1', 'max:10'],
            'period_norms.12' => ['nullable', 'integer', 'min:1', 'max:10'],
        ], [
            'code.unique' => 'Mã môn đã tồn tại.',
            'name.unique' => 'Tên môn đã tồn tại.',
            'type.in' => 'Loại môn không hợp lệ.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'period_norms.*.integer' => 'Định mức tiết phải là số nguyên.',
            'period_norms.*.min' => 'Định mức tiết tối thiểu là 1.',
            'period_norms.*.max' => 'Định mức tiết tối đa là 10.',
        ]);

        $periodNorms = [];
        foreach (self::GRADE_LEVELS as $gradeLevel) {
            $value = $validated['period_norms'][$gradeLevel] ?? null;
            $periodNorms[$gradeLevel] = $value === null || $value === '' ? null : (int) $value;
        }

        unset($validated['period_norms']);

        return [$validated, $periodNorms];
    }

    private function syncPeriodNorms(Subject $subject, array $periodNorms): void
    {
        foreach (self::GRADE_LEVELS as $gradeLevel) {
            $value = $periodNorms[$gradeLevel] ?? null;
            $existing = $subject->periodNorms()->where('grade_level', $gradeLevel)->first();

            if ($value === null) {
                if ($existing) {
                    $normId = (string) $existing->getKey();
                    $existing->delete();

                    AuditLogger::log(
                        'subject_period_norm_deleted',
                        SubjectPeriodNorm::class,
                        $normId,
                        'Xóa định mức tiết khối ' . $gradeLevel . ' của môn ' . $subject->name
                    );
                }

                continue;
            }

            if ($existing) {
                if ((int) $existing->periods_per_week !== $value) {
                    $existing->update(['periods_per_week' => $value]);

                    AuditLogger::log(
                        'subject_period_norm_updated',
                        SubjectPeriodNorm::class,
                        (string) $existing->getKey(),
                        'Sửa định mức tiết khối ' . $gradeLevel . ' của môn ' . $subject->name . ' thành ' . $value . ' tiết/tuần'
                    );
                }

                continue;
            }

            $created = $subject->periodNorms()->create([
                'grade_level' => $gradeLevel,
                'periods_per_week' => $value,
            ]);

            AuditLogger::log(
                'subject_period_norm_created',
                SubjectPeriodNorm::class,
                (string) $created->getKey(),
                'Tạo định mức tiết khối ' . $gradeLevel . ' của môn ' . $subject->name . ': ' . $value . ' tiết/tuần'
            );
        }
    }

    private function denyHistoricalWrite(): void
    {
        if ($this->isHistoricalReadOnly()) {
            throw ValidationException::withMessages([
                'history_readonly' => 'Đang xem dữ liệu lịch sử, không thể thay đổi môn học.',
            ]);
        }
    }
}
