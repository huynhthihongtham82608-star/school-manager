<?php

namespace App\Http\Controllers;

use App\Models\LearningDocument;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LearningDocumentController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = $this->selectedSchoolYearId($request);
        $classIds = $selectedYearId && Schema::hasTable('classes')
            ? SchoolClass::where('school_year_id', $selectedYearId)->pluck('id')
            : collect();

        if (Schema::hasTable('learning_documents')) {
            $query = LearningDocument::with(['subject', 'classRoom'])->latest();

            if ($selectedYearId) {
                $query->whereIn('class_id', $classIds);
            }

            if (! (request()->user()->isAdmin() || request()->user()->isStaff())) {
                $query->where(function ($scope) {
                    $scope->where('is_published', true)
                        ->orWhere('uploaded_by', request()->user()->id);
                });
            }

            $documents = $query->paginate(12);

            if (! (request()->user()->isAdmin() || request()->user()->isStaff())) {
                $documents->setCollection(
                    $documents->getCollection()
                        ->filter(fn (LearningDocument $document) => $document->isVisibleToUser(request()->user()))
                        ->values()
                );
            }
        } else {
            $documents = collect();
        }

        $classes = Schema::hasTable('classes')
            ? SchoolClass::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))
                ->when($request->user()->isTeacher() && ! ($request->user()->isAdmin() || $request->user()->isStaff()), function ($query) use ($request) {
                    $query->whereIn('id', $this->teacherClassIds($request->user()));
                })
                ->orderBy('name')
                ->get()
            : collect();
        $subjects = Schema::hasTable('subjects')
            ? Subject::when($request->user()->isTeacher() && ! ($request->user()->isAdmin() || $request->user()->isStaff()), function ($query) use ($request) {
                    $query->whereIn('id', $this->teacherSubjectIds($request->user()));
                })
                ->orderBy('name')
                ->get()
            : collect();
        $manageableDocumentIds = $documents instanceof \Illuminate\Contracts\Pagination\Paginator
            ? $documents->getCollection()->filter(fn (LearningDocument $document) => $this->canManageDocument($request->user(), $document))->pluck('id')->all()
            : [];

        return view('documents.index', compact('documents', 'classes', 'subjects', 'selectedYearId', 'manageableDocumentIds'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canCreateDocument($request->user()), 403);

        if (! Schema::hasTable('learning_documents')) {
            return back()->with('error', 'Chưa có bảng learning_documents. Vui lòng chạy migration trước.');
        }

        $data = $request->validate($this->rules());
        $this->ensureTeacherDocumentScope($request->user(), $data);
        $targetRoles = $request->input('target_roles', ['all']);
        unset($data['target_roles']);

        $document = LearningDocument::create([
            ...$data,
            'description' => LearningDocument::withMeta($data['description'] ?? null, $targetRoles),
            'uploaded_by' => $request->user()->id,
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('learning_document_created', LearningDocument::class, $document->id, 'Thêm tài liệu học tập');

        return back()->with('success', 'Đã thêm tài liệu học tập.');
    }

    public function update(Request $request, LearningDocument $document)
    {
        abort_unless($this->canManageDocument($request->user(), $document), 403);

        if (! Schema::hasTable('learning_documents')) {
            return back()->with('error', 'Chưa có bảng learning_documents. Vui lòng chạy migration trước.');
        }

        $data = $request->validate($this->rules());
        $this->ensureTeacherDocumentScope($request->user(), $data);
        $targetRoles = $request->input('target_roles', ['all']);
        unset($data['target_roles']);

        $document->update([
            ...$data,
            'description' => LearningDocument::withMeta($data['description'] ?? null, $targetRoles),
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('learning_document_updated', LearningDocument::class, $document->id, 'Cập nhật tài liệu học tập');

        return back()->with('success', 'Đã cập nhật tài liệu học tập.');
    }

    public function destroy(Request $request, LearningDocument $document)
    {
        abort_unless($this->canManageDocument($request->user(), $document), 403);

        if (! Schema::hasTable('learning_documents')) {
            return back()->with('error', 'Chưa có bảng learning_documents. Vui lòng chạy migration trước.');
        }

        $documentId = $document->id;
        $document->delete();

        AuditLogger::log('learning_document_deleted', LearningDocument::class, $documentId, 'Xóa tài liệu học tập');

        return back()->with('success', 'Đã xóa tài liệu học tập.');
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'file_url' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'string', 'max:50'],
            'class_id' => ['nullable', 'string', 'max:50'],
            'is_published' => ['nullable', 'boolean'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:all,admin,teacher,homeroom,student,parent'],
        ];
    }

    private function canCreateDocument($user): bool
    {
        return $user && ($user->isAdmin() || $user->isStaff() || $user->isTeacher());
    }

    private function canManageDocument($user, LearningDocument $document): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->isStaff()) {
            return true;
        }

        return $user->isTeacher() && (string) $document->uploaded_by === (string) $user->id;
    }

    private function ensureTeacherDocumentScope($user, array $data): void
    {
        if (! $user->isTeacher() || $user->isAdmin() || $user->isStaff()) {
            return;
        }

        $classIds = $this->teacherClassIds($user);
        $subjectIds = $this->teacherSubjectIds($user);

        if (empty($data['subject_id'])) {
            abort(403, 'Giáo viên cần chọn môn học thuộc phân công khi quản lý tài liệu.');
        }

        if (! empty($data['class_id']) && ! $classIds->contains($data['class_id'])) {
            abort(403, 'Bạn chỉ được quản lý tài liệu của lớp mình được phân công.');
        }

        if (! empty($data['subject_id']) && ! $subjectIds->contains($data['subject_id'])) {
            abort(403, 'Bạn chỉ được quản lý tài liệu của môn mình được phân công.');
        }
    }

    private function teacherClassIds($user)
    {
        return $user->teacher
            ? $user->teacher->assignments()
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->pluck('class_id')
                ->filter()
                ->unique()
                ->values()
            : collect();
    }

    private function teacherSubjectIds($user)
    {
        return $user->teacher
            ? $user->teacher->assignments()
                ->where('status', TeachingAssignment::STATUS_ACTIVE)
                ->pluck('subject_id')
                ->filter()
                ->unique()
                ->values()
            : collect();
    }
}
