<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TuitionFee extends Model
{
    use HasFactory, UsesUuid;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID = 'paid';

    public const PAYMENT_CASH = 'cash';
    public const PAYMENT_TRANSFER = 'transfer';

    public const EXEMPTION_DEFAULT = 'default';
    public const EXEMPTION_POLICY = 'policy_exempt';
    public const EXEMPTION_LOCAL_PAID = 'local_paid';

    protected $fillable = [
        'student_id',
        'class_id',
        'semester_id',
        'school_year_id',
        'amount',
        'fee_items',
        'status',
        'payment_method',
        'exemption_type',
        'paid_at',
        'note',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'fee_items' => 'array',
        'paid_at' => 'datetime',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PAID => 'Đã đóng',
            self::STATUS_UNPAID => 'Chưa đóng',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? self::statusLabels()[self::STATUS_UNPAID];
    }

    public static function paymentMethodLabels(): array
    {
        return [
            self::PAYMENT_CASH => 'Tiền mặt (Đóng trực tiếp)',
            self::PAYMENT_TRANSFER => 'Chuyển khoản (Quét mã QR)',
        ];
    }

    public static function exemptionLabels(): array
    {
        return [
            self::EXEMPTION_DEFAULT => 'Mặc định (Đóng đủ 100%)',
            self::EXEMPTION_POLICY => 'Miễn đóng (Chính sách vùng miền / Hộ nghèo)',
            self::EXEMPTION_LOCAL_PAID => 'Đã đóng tại địa phương (Hộ gia đình)',
        ];
    }

    public static function defaultFeeItems(): array
    {
        return [
            ['key' => 'tuition_hk1', 'label' => 'Học phí', 'amount' => 1200000, 'status' => self::STATUS_UNPAID],
            ['key' => 'health_insurance', 'label' => 'Bảo hiểm Y tế', 'amount' => 680000, 'status' => self::STATUS_UNPAID],
            ['key' => 'accident_insurance', 'label' => 'Bảo hiểm Tai nạn', 'amount' => 150000, 'status' => self::STATUS_UNPAID],
        ];
    }

    public static function configuredFeeItems(): array
    {
        $stored = json_decode((string) Setting::valueOf('tuition_fee_items', ''), true);
        $items = is_array($stored) && $stored ? $stored : self::defaultFeeItems();

        return collect($items)
            ->map(fn (array $item) => [
                'key' => (string) ($item['key'] ?? ''),
                'label' => trim((string) ($item['label'] ?? 'Khoản thu')),
                'amount' => round((float) ($item['amount'] ?? 0), 2),
                'status' => self::STATUS_UNPAID,
            ])
            ->filter(fn (array $item) => $item['key'] !== '')
            ->values()
            ->all();
    }

    public static function applyExemptionToItems(array $items, string $exemptionType): array
    {
        return collect($items)
            ->map(function (array $item) use ($exemptionType) {
                if (($item['key'] ?? '') !== 'health_insurance') {
                    return $item;
                }

                if ($exemptionType === self::EXEMPTION_POLICY) {
                    $item['amount'] = 0;
                    $item['status'] = self::STATUS_PAID;
                    $item['exemption_label'] = '🟢 Miễn đóng BHYT';
                }

                if ($exemptionType === self::EXEMPTION_LOCAL_PAID) {
                    $item['amount'] = 0;
                    $item['status'] = self::STATUS_PAID;
                    $item['exemption_label'] = '🟢 Đã đóng BHYT tại địa phương';
                }

                return $item;
            })
            ->values()
            ->all();
    }

    public function normalizedFeeItems(): array
    {
        $stored = collect($this->fee_items ?: [])->keyBy('key');
        $items = collect(self::configuredFeeItems())
            ->map(function (array $item) use ($stored) {
                $saved = $stored->get($item['key'], []);

                return [
                    'key' => $item['key'],
                    'label' => (string) $item['label'],
                    'amount' => (float) $item['amount'],
                    'status' => in_array($saved['status'] ?? null, array_keys(self::statusLabels()), true)
                        ? $saved['status']
                        : $item['status'],
                    'exemption_label' => (string) ($saved['exemption_label'] ?? ''),
                ];
            })
            ->values()
            ->all();

        return self::applyExemptionToItems($items, $this->exemption_type ?: self::EXEMPTION_DEFAULT);
    }

    public function paymentMethodLabel(): string
    {
        return self::paymentMethodLabels()[$this->payment_method] ?? self::paymentMethodLabels()[self::PAYMENT_CASH];
    }

    public function exemptionLabel(): string
    {
        return self::exemptionLabels()[$this->exemption_type] ?? self::exemptionLabels()[self::EXEMPTION_DEFAULT];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
