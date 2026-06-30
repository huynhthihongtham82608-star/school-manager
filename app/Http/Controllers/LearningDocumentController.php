<?php

namespace App\Http\Controllers;

use App\Models\LearningDocument;
use App\Models\SchoolClass;
use App\Models\Subject;
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
                $query->where('is_published', true);
            }

            $documents = $query->paginate(12);

            if (! (request()->user()->isAdmin() || request()->user()->isStaff())) {
                $documents->setCollection(
                    $documents->getCollection()
                        ->filter(fn (LearningDocument $document) => $document->isVisibleToRole(request()->user()->role))
                        ->values()
                );
            }
        } else {
            $documents = collect();
        }

        $classes = Schema::hasTable('classes')
            ? SchoolClass::when($selectedYearId, fn ($query) => $query->where('school_year_id', $selectedYearId))->orderBy('name')->get()
            : collect();
        $subjects = Schema::hasTable('subjects') ? Subject::orderBy('name')->get() : collect();

        return view('documents.index', compact('documents', 'classes', 'subjects', 'selectedYearId'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('learning_documents')) {
            return back()->with('error', 'Chưa có bảng learning_documents. Vui lòng chạy migration trước.');
        }

        $data = $request->validate($this->rules());
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
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('learning_documents')) {
            return back()->with('error', 'Chưa có bảng learning_documents. Vui lòng chạy migration trước.');
        }

        $data = $request->validate($this->rules());
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
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

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
}
