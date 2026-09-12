<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BusinessText implements ValidationRule
{
    private const INVALID_CHARS = '/[@#$%^*={}\\[\\]<>|\\\\~`;]/u';

    public function __construct(private readonly string $label = 'Trường dữ liệu')
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || trim((string) $value) === '') {
            return;
        }

        if (preg_match(self::INVALID_CHARS, (string) $value) === 1) {
            $fail($this->label . ' không được chứa ký tự đặc biệt không hợp lệ như @, #, $, %, <, > hoặc dấu ngoặc lệnh.');
        }
    }
}
