<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserScopeService
{
    public function roleLabel(User $user): string
    {
        return match (true) {
            $user->isSuperAdmin() || $user->role === 'admin' => 'admin',
            $user->isStaff() => 'staff',
            $user->isTeacher() => 'teacher',
            $user->isParent() => 'parent',
            $user->isStudent() => 'student',
            default => (string) $user->role,
        };
    }

    public function canUseChatbot(User $user): bool
    {
        if ($user->is_active === false || $user->login_status === false) {
            return false;
        }

        return ! $user->isStaff()
            || $user->hasPermission('access_chatbot')
            || $user->hasPermission('system.settings');
    }

    public function isAdminLike(User $user): bool
    {
        return $user->isSuperAdmin() || $user->role === 'admin' || $user->isStaff();
    }

    public function currentStudent(User $user): ?object
    {
        if (! $user->isStudent() || ! Schema::hasTable('users')) {
            return null;
        }

        return $this->studentQuery()
            ->where('id', $user->getKey())
            ->first();
    }

    public function currentTeacher(User $user): ?object
    {
        if (! $user->isTeacher() || ! Schema::hasTable('users')) {
            return null;
        }

        $teacherIds = $this->teacherIdentityCandidates($user);

        return $this->teacherQuery()
            ->where(function ($query) use ($teacherIds) {
                $query->whereIn('id', $teacherIds);

                if (Schema::hasColumn('users', 'teacher_id')) {
                    $query->orWhereIn('teacher_id', $teacherIds);
                }

                if (Schema::hasColumn('users', 'source_profile_id')) {
                    $query->orWhereIn('source_profile_id', $teacherIds);
                }
            })
            ->first();
    }

    public function parentChildren(User $user): Collection
    {
        if (! $user->isParent() || ! Schema::hasTable('parent_student') || ! Schema::hasTable('users')) {
            return collect();
        }

        $studentIds = DB::table('parent_student')
            ->whereIn('parent_id', $this->parentIdentityCandidates($user))
            ->pluck('student_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return collect();
        }

        return $this->studentQuery()
            ->where(function ($query) use ($studentIds) {
                $query->whereIn('id', $studentIds);

                if (Schema::hasColumn('users', 'student_id')) {
                    $query->orWhereIn('student_id', $studentIds);
                }

                if (Schema::hasColumn('users', 'source_profile_id')) {
                    $query->orWhereIn('source_profile_id', $studentIds);
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function teacherTeachingClassIds(User $user): Collection
    {
        if (! $user->isTeacher() || ! Schema::hasTable('teaching_assignments')) {
            return collect();
        }

        return DB::table('teaching_assignments')
            ->whereIn('teacher_id', $this->teacherIdentityCandidates($user))
            ->when(Schema::hasColumn('teaching_assignments', 'status'), function ($query) {
                $query->where(function ($inner) {
                    $inner->whereNull('status')->orWhere('status', 'active');
                });
            })
            ->pluck('class_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    public function teacherHomeroomClassIds(User $user): Collection
    {
        if (! $user->isTeacher() || ! Schema::hasTable('classes') || ! Schema::hasColumn('classes', 'homeroom_teacher_id')) {
            return collect();
        }

        return DB::table('classes')
            ->whereIn('homeroom_teacher_id', $this->teacherIdentityCandidates($user))
            ->when(Schema::hasColumn('classes', 'status'), function ($query) {
                $query->where(function ($inner) {
                    $inner->whereNull('status')->orWhereIn('status', ['active', 'draft']);
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    public function teacherClassIds(User $user): Collection
    {
        if (! $user->isTeacher()) {
            return collect();
        }

        return $this->teacherTeachingClassIds($user)
            ->merge($this->teacherHomeroomClassIds($user))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    public function canAccessClass(User $user, string|int|null $classId): bool
    {
        if (! $classId) {
            return false;
        }

        if ($this->isAdminLike($user)) {
            return true;
        }

        if ($user->isStudent()) {
            return (string) optional($this->currentStudent($user))->class_id === (string) $classId;
        }

        if ($user->isParent()) {
            return $this->parentChildren($user)
                ->pluck('class_id')
                ->map(fn ($id) => (string) $id)
                ->contains((string) $classId);
        }

        if ($user->isTeacher()) {
            return $this->teacherClassIds($user)->contains((string) $classId);
        }

        return false;
    }

    public function resolveScopedStudent(User $user, array $arguments = []): array
    {
        if ($user->isStudent()) {
            $candidate = $this->studentFromArguments($arguments);
            $student = $this->currentStudent($user);

            if (! $student) {
                return ['student' => null, 'error' => 'Không tìm thấy hồ sơ học sinh gắn với tài khoản này.'];
            }

            if ($candidate && (string) $candidate->id !== (string) $student->id) {
                return ['student' => null, 'error' => 'Bạn chỉ được xem dữ liệu của chính mình.'];
            }

            return ['student' => $student, 'error' => null];
        }

        if ($user->isParent()) {
            $children = $this->parentChildren($user);

            if ($children->isEmpty()) {
                return ['student' => null, 'error' => 'Tài khoản phụ huynh chưa liên kết với học sinh nào.'];
            }

            $matchedChildren = $this->studentsFromCollection($children, $arguments);

            if ($matchedChildren->count() === 1) {
                return ['student' => $matchedChildren->first(), 'error' => null];
            }

            if ($matchedChildren->count() > 1) {
                return [
                    'student' => null,
                    'error' => 'Tìm thấy nhiều học sinh phù hợp. Anh/chị vui lòng chọn rõ: ' . $matchedChildren->map(fn ($child) => $this->studentDisplayName($child))->join(', '),
                ];
            }

            if ($this->hasStudentIdentifier($arguments)) {
                return ['student' => null, 'error' => 'Bạn chỉ được xem dữ liệu của học sinh đã liên kết với tài khoản phụ huynh.'];
            }

            $selectedStudentId = session('selected_parent_student_id');
            if ($selectedStudentId) {
                $selected = $this->studentByAnyId($children, (string) $selectedStudentId);

                if ($selected) {
                    return ['student' => $selected, 'error' => null];
                }
            }

            if ($children->count() > 1) {
                return [
                    'student' => null,
                    'error' => 'Anh/chị muốn xem thông tin của học sinh nào? ' . $children->map(fn ($child) => $this->studentDisplayName($child))->join(', '),
                ];
            }

            return ['student' => $children->first(), 'error' => null];
        }

        if ($user->isTeacher()) {
            $candidate = $this->studentFromArguments($arguments);

            if (! $candidate) {
                return ['student' => null, 'error' => 'Vui lòng cho biết học sinh hoặc lớp cần tra cứu.'];
            }

            return $this->canAccessClass($user, $candidate->class_id)
                ? ['student' => $candidate, 'error' => null]
                : ['student' => null, 'error' => 'Bạn chỉ được xem dữ liệu học sinh thuộc lớp được phân công hoặc lớp chủ nhiệm.'];
        }

        if ($this->isAdminLike($user)) {
            $candidate = $this->studentFromArguments($arguments);

            if ($candidate) {
                return ['student' => $candidate, 'error' => null];
            }

            return ['student' => null, 'error' => 'Vui lòng cho biết học sinh cần tra cứu.'];
        }

        return ['student' => null, 'error' => 'Vai trò tài khoản chưa được hỗ trợ.'];
    }

    public function studentQuery()
    {
        $query = DB::table('users');

        if (Schema::hasColumn('users', 'role_type')) {
            $query->where('role_type', 'student');
        } else {
            $query->where('role', 'student');
        }

        return $query;
    }

    public function teacherQuery()
    {
        $query = DB::table('users');

        if (Schema::hasColumn('users', 'role_type')) {
            $query->whereIn('role_type', ['teacher', 'homeroom']);
        } else {
            $query->whereIn('role', ['teacher', 'homeroom']);
        }

        return $query;
    }

    public function classIdsInScope(User $user): Collection
    {
        if ($this->isAdminLike($user)) {
            return Schema::hasTable('classes')
                ? DB::table('classes')->pluck('id')->map(fn ($id) => (string) $id)
                : collect();
        }

        if ($user->isTeacher()) {
            return $this->teacherClassIds($user);
        }

        if ($user->isStudent()) {
            return collect([(string) optional($this->currentStudent($user))->class_id])->filter()->values();
        }

        if ($user->isParent()) {
            return $this->parentChildren($user)
                ->pluck('class_id')
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values();
        }

        return collect();
    }

    public function teacherIdentityCandidates(User $user): array
    {
        return collect([$user->getKey(), $user->teacher_id, $user->source_profile_id])
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function parentIdentityCandidates(User $user): array
    {
        return collect([$user->getKey(), $user->parent_id, $user->source_profile_id])
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function studentDisplayName(object $student): string
    {
        return trim((string) ($student->student_code ?? '') . ' - ' . (string) ($student->name ?? $student->full_name ?? 'Học sinh'), ' -');
    }

    public function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->squish()->toString();
    }

    public function normalizeCode(string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', $this->normalize($value));
    }

    private function studentFromArguments(array $arguments): ?object
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        $studentId = trim((string) ($arguments['student_id'] ?? ''));
        $studentName = trim((string) ($arguments['student_name'] ?? $arguments['student'] ?? ''));
        $studentCode = trim((string) ($arguments['student_code'] ?? ''));

        $query = $this->studentQuery();

        if ($studentId !== '') {
            return (clone $query)->where(function ($inner) use ($studentId) {
                $inner->where('id', $studentId);

                if (Schema::hasColumn('users', 'student_id')) {
                    $inner->orWhere('student_id', $studentId);
                }

                if (Schema::hasColumn('users', 'source_profile_id')) {
                    $inner->orWhere('source_profile_id', $studentId);
                }
            })->first();
        }

        if ($studentCode !== '') {
            return (clone $query)->where('student_code', $studentCode)->first();
        }

        if ($studentName !== '') {
            $normalized = $this->normalize($studentName);

            return $query->get()
                ->first(function ($student) use ($normalized) {
                    $name = $this->normalize((string) ($student->name ?? $student->full_name ?? ''));
                    $code = $this->normalize((string) ($student->student_code ?? ''));

                    return ($name !== '' && str_contains($name, $normalized))
                        || ($normalized !== '' && str_contains($normalized, $name))
                        || ($code !== '' && str_contains($normalized, $code));
                });
        }

        return null;
    }

    private function studentsFromCollection(Collection $students, array $arguments): Collection
    {
        $studentId = trim((string) ($arguments['student_id'] ?? ''));
        $studentName = trim((string) ($arguments['student_name'] ?? $arguments['student'] ?? ''));
        $studentCode = trim((string) ($arguments['student_code'] ?? ''));

        if ($studentId !== '') {
            return $students->filter(fn ($student) => (bool) $this->studentByAnyId(collect([$student]), $studentId))->values();
        }

        if ($studentCode !== '') {
            $normalizedCode = $this->normalizeCode($studentCode);

            return $students->filter(fn ($student) => $this->normalizeCode((string) ($student->student_code ?? '')) === $normalizedCode)->values();
        }

        if ($studentName !== '') {
            $normalized = $this->normalize($studentName);

            return $students->filter(function ($student) use ($normalized) {
                $name = $this->normalize((string) ($student->name ?? $student->full_name ?? ''));
                $code = $this->normalize((string) ($student->student_code ?? ''));

                return ($name !== '' && str_contains($name, $normalized))
                    || ($normalized !== '' && str_contains($normalized, $name))
                    || ($code !== '' && str_contains($normalized, $code));
            })->values();
        }

        return collect();
    }

    private function studentByAnyId(Collection $students, string $id): ?object
    {
        return $students->first(function ($student) use ($id) {
            return collect([$student->id ?? null, $student->student_id ?? null, $student->source_profile_id ?? null])
                ->filter()
                ->map(fn ($value) => (string) $value)
                ->contains($id);
        });
    }

    private function hasStudentIdentifier(array $arguments): bool
    {
        return trim((string) ($arguments['student_id'] ?? '')) !== ''
            || trim((string) ($arguments['student_name'] ?? $arguments['student'] ?? '')) !== ''
            || trim((string) ($arguments['student_code'] ?? '')) !== '';
    }
}
