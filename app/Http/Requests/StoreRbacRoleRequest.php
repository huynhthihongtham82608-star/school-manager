<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRbacRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('manage_roles');
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('rbac_roles', 'key')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['string', 'exists:rbac_permissions,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'key' => 'mã vai trò',
            'name' => 'tên vai trò',
            'description' => 'mô tả',
            'permission_ids' => 'quyền',
        ];
    }
}
