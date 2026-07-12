<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use UsesUuid;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moduleLabel(): string
    {
        if ($this->entity_type) {
            return class_basename($this->entity_type);
        }

        return match (true) {
            str_contains($this->action, 'login') || str_contains($this->action, 'logout') => 'Đăng nhập',
            str_contains($this->action, 'backup') => 'Sao lưu dữ liệu',
            str_contains($this->action, 'system_settings') => 'Cài đặt hệ thống',
            default => 'Hệ thống',
        };
    }

    public function actionTypeLabel(): string
    {
        return match (true) {
            str_contains($this->action, 'created') || str_contains($this->action, 'store') => 'Thêm',
            str_contains($this->action, 'updated') || str_contains($this->action, 'changed') => 'Sửa',
            str_contains($this->action, 'deleted') => 'Xóa',
            str_contains($this->action, 'reset') => 'Reset mật khẩu',
            str_contains($this->action, 'login') => 'Đăng nhập',
            str_contains($this->action, 'logout') => 'Đăng xuất',
            str_contains($this->action, 'backup') => 'Sao lưu',
            default => 'Khác',
        };
    }
}
