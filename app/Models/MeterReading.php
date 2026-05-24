<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeterReading extends Model
{
    use HasFactory;

    protected $attributes = [
        'is_approved' => true,
    ];

    protected $fillable = [
        'customer_id',
        'reading_value',
        'reading_date',
        'consumption',
        'price_per_unit',
        'amount',
        'net_amount',
        'is_approved',
        'sms_sent_at',
        'note',
        'source',
        'client_uuid',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'reading_value' => 'decimal:3',
        'consumption' => 'decimal:3',
        'price_per_unit' => 'decimal:4',
        'amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_approved' => 'boolean',
        'sms_sent_at' => 'datetime',
    ];

    public function hasSmsBeenSent(): bool
    {
        return $this->sms_sent_at !== null;
    }

    public function hasReadySmsBeenSent(): bool
    {
        return $this->smsLogs()->where('type', 'ready')->exists();
    }

    public function requiresCorrectionSms(array $newData): bool
    {
        if (! $this->hasSmsBeenSent()) {
            return false;
        }

        return $this->dataChangedSinceLastSms($newData);
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function dataChangedSinceLastSms(?array $data = null): bool
    {
        if (! $this->hasSmsBeenSent()) {
            return true;
        }

        $baseline = $this->latestSmsSnapshot() ?? [
            'weight' => (float) $this->reading_value,
            'price_per_unit' => (float) $this->price_per_unit,
            'reading_date' => $this->reading_date?->format('Y-m-d'),
        ];

        return $this->costFieldsDifferFromBaseline($data, $baseline);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestSmsSnapshot(): ?array
    {
        $log = $this->smsLogs()
            ->whereIn('type', ['initial', 'correction'])
            ->latest()
            ->first();

        return $log?->snapshot;
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $baseline
     */
    private function costFieldsDifferFromBaseline(?array $data, array $baseline): bool
    {
        $data ??= [
            'reading_value' => $this->reading_value,
            'price_per_unit' => $this->price_per_unit,
            'reading_date' => $this->reading_date,
        ];

        $readingDate = $data['reading_date'] ?? null;
        if ($readingDate instanceof \Carbon\CarbonInterface) {
            $readingDate = $readingDate->format('Y-m-d');
        } elseif ($readingDate !== null) {
            $readingDate = (string) $readingDate;
        }

        $baselineDate = (string) ($baseline['reading_date'] ?? '');
        $baselineWeight = (string) ($baseline['weight'] ?? $baseline['reading_value'] ?? 0);

        return bccomp((string) ($data['reading_value'] ?? 0), $baselineWeight, 3) !== 0
            || bccomp((string) ($data['price_per_unit'] ?? 0), (string) ($baseline['price_per_unit'] ?? 0), 4) !== 0
            || $readingDate !== $baselineDate;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(MeterReadingSmsLog::class);
    }
}
