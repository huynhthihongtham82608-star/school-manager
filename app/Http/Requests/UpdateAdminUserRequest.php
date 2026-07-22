<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('manage_admin_accounts');
    }

    public function rules(): array
    {
        $userId = $this->route('admin_user')?->id;

        return [
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['string', Rule::exists('rbac_roles', 'id')->where('is_active', true)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => 'tên đăng nhập',
            'full_name' => 'họ tên',
            'email' => 'email',
            'phone' => 'số điện thoại',
            'role_ids' => 'vai trò',
        ];
    }
}
