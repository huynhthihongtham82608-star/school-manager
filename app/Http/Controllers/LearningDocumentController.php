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
    public function index()
    {
        $documents = Schema::hasTable('learning_documents')
            ? LearningDocument::with(['subject', 'classRoom'])->where('is_published', true)->latest()->paginate(12)
            : collect();

        $classes = Schema::hasTable('classes') ? SchoolClass::orderBy('name')->get() : collect();
        $subjects = Schema::hasTable('subjects') ? Subject::orderBy('name')->get() : collect();

        return view('documents.index', compact('documents', 'classes', 'subjects'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isStaff(), 403);

        if (! Schema::hasTable('learning_documents')) {
            return back()->with('error', 'Chưa có bảng learning_documents. Vui lòng chạy migration trước.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'file_url' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'string', 'max:50'],
            'class_id' => ['nullable', 'string', 'max:50'],
        ]);

        $document = LearningDocument::create($data + [
            'uploaded_by' => $request->user()->id,
            'is_published' => true,
        ]);

        AuditLogger::log('learning_document_created', LearningDocument::class, $document->id, 'Thêm tài liệu học tập');

        return back()->with('success', 'Đã thêm tài liệu học tập.');
    }
}
