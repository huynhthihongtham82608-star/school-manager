<?php

namespace App\Http\Controllers;

use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\AdminProtectionService;

class ParentController extends Controller
{
    public function index()
    {
        $parents = ParentProfile::with(['students', 'user'])->orderBy('name')->get();
        return view('parents.index', compact('parents'));
    }

    public function create()
    {
        $students = Student::orderBy('student_code')->get();
        return view('parents.create', compact('students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'student_ids' => 'array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $parent = ParentProfile::create($request->only(['name', 'phone', 'email', 'address']));

        if (!empty($data['student_ids'])) {
            foreach ($data['student_ids'] as $sid) {
                $parent->students()->attach($sid, ['relation' => 'PH']);
            }
        }

        User::create([
            'username' => $data['username'],
            'role' => 'parent',
            'parent_id' => $parent->id,
            'password_hash' => Hash::make($data['password']),
            'is_active' => 1,
        ]);

        return redirect()->route('parents.index')->with('success', 'Đã thêm phụ huynh');
    }

    public function edit(ParentProfile $parent)
    {
        $parent->load(['students', 'user']);
        $students = Student::orderBy('student_code')->get();
        return view('parents.edit', compact('parent', 'students'));
    }

    public function update(Request $request, ParentProfile $parent)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'is_active' => 'nullable|boolean',
            'student_ids' => 'array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $parent->update($request->only(['name', 'phone', 'email', 'address']));

        if ($request->has('student_ids')) {
            $sync = [];
            foreach ($data['student_ids'] ?? [] as $sid) {
                $sync[$sid] = ['relation' => 'PH'];
            }
            $parent->students()->sync($sync);
        }

        if ($parent->user) {
            $update = [];
            if (!empty($data['password'])) {
                $update['password_hash'] = Hash::make($data['password']);
            }
            if ($request->has('is_active')) {
                $update['is_active'] = $request->boolean('is_active');

                // Check if this is an admin account being deactivated
                if ($parent->user->role === 'admin') {
                    $validation = AdminProtectionService::validateAdminChange($parent->user, $update);
                    if (!$validation['allowed']) {
                        return back()->withErrors(['error' => $validation['message']]);
                    }
                }
            }
            if ($update) {
                $parent->user->update($update);
            }
        }

        return redirect()->route('parents.index')->with('success', 'Đã cập nhật phụ huynh');
    }

    public function destroy(ParentProfile $parent)
    {
        // Check if parent's user is admin
        if ($parent->user && $parent->user->role === 'admin') {
            $validation = AdminProtectionService::validateAdminDeletion($parent->user);
            if (!$validation['allowed']) {
                return back()->withErrors(['error' => $validation['message']]);
            }
        }

        if ($parent->user) {
            $parent->user->delete();
        }
        $parent->students()->detach();
        $parent->delete();

        return redirect()->route('parents.index')->with('success', 'Đã xóa phụ huynh');
    }
}
