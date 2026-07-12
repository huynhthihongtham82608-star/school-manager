<?php

namespace App\Http\Controllers;

use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParentController extends Controller
{
    public function index()
    {
        $parents = ParentProfile::with(['students.classRoom', 'user'])
            ->orderBy('parent_code')
            ->orderBy('name')
            ->get();

        return view('parents.index', compact('parents'));
    }

    public function create()
    {
        $students = Student::with('classRoom')->orderBy('student_code')->get();

        return view('parents.create', compact('students'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data) {
            $parent = ParentProfile::where('phone', $data['phone'])->first();

            if (! $parent) {
                $parent = ParentProfile::create([
                    'parent_code' => $this->generateParentCode(),
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'] ?? null,
                ]);
            } else {
                $parent->update([
                    'name' => $data['name'],
                    'address' => $data['address'] ?? $parent->address,
                ]);
            }

            $this->syncStudentLinks($parent, $data['student_ids'] ?? [], $data['relation'], replace: false);
            $this->ensureParentUser($parent, resetPassword: ! $parent->user);

            AuditLogger::log('parent_saved', ParentProfile::class, (string) $parent->getKey(), 'Lưu phụ huynh ' . $parent->name);
        });

        return redirect()->route('parents.index')
            ->with('success', 'Đã lưu phụ huynh. Tài khoản đăng nhập là số điện thoại, mật khẩu mặc định 12345678.');
    }

    public function edit(ParentProfile $parent)
    {
        $parent->load(['students.classRoom', 'user']);
        $students = Student::with('classRoom')->orderBy('student_code')->get();

        return view('parents.edit', compact('parent', 'students'));
    }

    public function update(Request $request, ParentProfile $parent)
    {
        $data = $this->validatedData($request, $parent);

        DB::transaction(function () use ($parent, $data) {
            if (! $parent->parent_code) {
                $parent->parent_code = $this->generateParentCode();
            }

            $parent->fill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
            ])->save();

            $this->syncStudentLinks($parent, $data['student_ids'] ?? [], $data['relation'], replace: true);
            $this->ensureParentUser($parent);

            AuditLogger::log('parent_updated', ParentProfile::class, (string) $parent->getKey(), 'Cập nhật phụ huynh ' . $parent->name);
        });

        return redirect()->route('parents.index')->with('success', 'Đã cập nhật phụ huynh.');
    }

    public function destroy(ParentProfile $parent)
    {
        if ($parent->students()->exists()) {
            return back()->withErrors([
                'parent' => 'Không thể xóa phụ huynh đang liên kết với học sinh. Vui lòng gỡ liên kết hoặc xử lý học sinh liên quan trước.',
            ]);
        }

        $parentName = $parent->name;
        $parentId = (string) $parent->getKey();

        DB::transaction(function () use ($parent) {
            $parent->user?->delete();
            $parent->delete();
        });

        AuditLogger::log('parent_deleted', ParentProfile::class, $parentId, 'Xóa phụ huynh ' . $parentName);

        return redirect()->route('parents.index')->with('success', 'Đã xóa phụ huynh.');
    }

    public function resetPassword(ParentProfile $parent)
    {
        $this->ensureParentUser($parent, resetPassword: true);

        AuditLogger::log(
            'parent_password_reset',
            ParentProfile::class,
            (string) $parent->getKey(),
            'Đặt lại mật khẩu phụ huynh ' . $parent->name . ' bởi ' . (auth()->user()?->display_name ?? auth()->user()?->username ?? 'admin') . ' lúc ' . now()->format('d/m/Y H:i:s')
        );

        return back()->with('success', 'Đã đặt lại mật khẩu phụ huynh về 12345678.');
    }

    private function validatedData(Request $request, ?ParentProfile $parent = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relation' => ['required', Rule::in(array_keys(ParentProfile::relationLabels()))],
            'phone' => [
                'required',
                'string',
                'max:50',
                ...($parent ? [Rule::unique('parents', 'phone')->ignore($parent->id)] : []),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['exists:students,id'],
        ]);
    }

    private function syncStudentLinks(ParentProfile $parent, array $studentIds, string $relation, bool $replace): void
    {
        $sync = [];

        foreach ($studentIds as $studentId) {
            $sync[$studentId] = ['relation' => $relation];
        }

        if ($replace) {
            $parent->students()->sync($sync);
            return;
        }

        $parent->students()->syncWithoutDetaching($sync);
    }

    private function ensureParentUser(ParentProfile $parent, bool $resetPassword = false): User
    {
        if (! $parent->phone) {
            throw ValidationException::withMessages([
                'phone' => 'Số điện thoại là bắt buộc để tạo tài khoản phụ huynh.',
            ]);
        }

        $conflict = User::where('username', $parent->phone)
            ->where(function ($query) use ($parent) {
                $query->where('role', '!=', 'parent')
                    ->orWhere(function ($parentQuery) use ($parent) {
                        $parentQuery->where('role', 'parent')
                            ->where('parent_id', '!=', $parent->id);
                    });
            })
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'phone' => 'Số điện thoại này đang được dùng làm tài khoản khác.',
            ]);
        }

        $user = $parent->user ?: new User([
            'role' => 'parent',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        $user->username = $parent->phone;
        $user->role = 'parent';
        $user->parent_id = $parent->id;

        if (! $user->exists || $resetPassword) {
            $user->password_hash = Hash::make('12345678');
            $user->force_change_password = true;
            $user->is_active = true;
        }

        $user->save();

        return $user;
    }

    private function generateParentCode(): string
    {
        $latestNumber = ParentProfile::whereNotNull('parent_code')
            ->where('parent_code', 'like', 'PH%')
            ->pluck('parent_code')
            ->map(fn ($code) => preg_match('/^PH(\d{4})$/', (string) $code, $matches) ? (int) $matches[1] : null)
            ->filter()
            ->max();

        $nextNumber = ($latestNumber ?: 0) + 1;

        do {
            $code = 'PH' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (ParentProfile::where('parent_code', $code)->exists());

        return $code;
    }
}
