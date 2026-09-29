<?php

namespace App\Models;

use Database\Factories\WagePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Wages handed over to someone, usually as cash, for a given week.
 */
class WagePayment extends Model
{
    /** @use HasFactory<WagePaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'staff_profile_id',
        'week_start',
        'amount',
        'paid_on',
        'method',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'paid_on' => 'date',
            'amount' => 'integer',
        ];
    }

    /** @return BelongsTo<StaffProfile, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'staff_profile_id')->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return array<string, string> */
    public static function methodLabels(): array
    {
        return [
            'cash' => __('Cash'),
            'bank_transfer' => __('Bank transfer'),
            'other' => __('Other'),
        ];
    }

    public function methodLabel(): string
    {
        return self::methodLabels()[$this->method] ?? $this->method;
    }
}
