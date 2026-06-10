<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ParentProfile;
use App\Services\AdminProtectionService;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        
        $data = [
            'user' => $user,
        ];

        // Load role-specific data
        if ($user->isTeacher()) {
            $data['teacher'] = $user->teacher;
        } elseif ($user->isStudent()) {
            $data['student'] = $user->student;
        } elseif ($user->isParent()) {
            $data['parent'] = $user->parentProfile;
            if ($data['parent']) {
                $data['children'] = $data['parent']->students;
            }
        }

        return view('profile.show', $data);
    }

    public function edit()
    {
        $user = Auth::user();
        
        $data = [
            'user' => $user,
        ];

        // Load role-specific data for editing
        if ($user->isTeacher()) {
            $data['teacher'] = $user->teacher;
        } elseif ($user->isStudent()) {
            $data['student'] = $user->student;
            $data['classes'] = \App\Models\SchoolClass::orderBy('name')->get();
        } elseif ($user->isParent()) {
            $data['parent'] = $user->parentProfile;
            if ($data['parent']) {
                $data['children'] = $data['parent']->students;
            }
        }

        return view('profile.edit', $data);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $validated = [];

        // Common validation for all roles
        if ($user->isAdmin()) {
            // Admin can only change username, NOT role or is_active
            $validated = $request->validate([
                'username' => 'required|string|unique:users,username,' . $user->id,
            ]);
            $user->update($validated);
        } 
        elseif ($user->isTeacher()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'main_subject' => 'nullable|string|max:255',
            ]);
            
            if ($user->teacher) {
                $user->teacher->update($validated);
            }
        }
        elseif ($user->isStudent()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'gender' => 'nullable|in:male,female,other',
                'dob' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'parent_phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'class_id' => 'nullable|exists:school_classes,id',
            ]);
            
            if ($user->student) {
                $user->student->update($validated);
            }
        }
        elseif ($user->isParent()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:500',
            ]);
            
            if ($user->parentProfile) {
                $user->parentProfile->update($validated);
            }
        }

        return redirect()->route('profile.show')
            ->with('success', 'Cập nhật thông tin cá nhân thành công');
    }

    public function showChangePasswordForm()
    {
        return view('profile.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại',
            'password.required' => 'Vui lòng nhập mật khẩu mới',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
        ]);

        $user = Auth::user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password_hash)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng'])->withInput();
        }

        // Update password
        $user->update([
            'password_hash' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.show')
            ->with('success', 'Đổi mật khẩu thành công');
    }
}
