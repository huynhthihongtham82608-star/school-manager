<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    public static function isValid(?string $value): bool
    {
        $value = trim((string) $value);

        return $value === '' || preg_match('/^\d+$/', $value) === 1;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid(is_scalar($value) ? (string) $value : null)) {
            $fail('Số điện thoại chỉ được chứa chữ số, không nhập chữ, khoảng trắng hoặc ký tự đặc biệt.');
        }
    }
}
