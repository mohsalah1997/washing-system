<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'initial_balance',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
    ];

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * حساب الرصيد الحالي للزبون
     * الرصيد = الرصيد الافتتاحي + مجموع الدفعات - مجموع مبالغ القراءات
     * موجب = له رصيد عندنا، سالب = عليه مبلغ
     */
    public function getBalanceAttribute(): float
    {
        // لا تدخل في الرصيد إلا القراءات المعتمدة
        $totalReadings = $this->meterReadings()
            ->where('is_approved', true)
            ->sum('amount');
        $totalPayments = $this->payments()->sum('amount');

        return (float) $this->initial_balance + $totalPayments - $totalReadings;
    }
}
