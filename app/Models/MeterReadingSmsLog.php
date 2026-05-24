<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReadingSmsLog extends Model
{
    protected $fillable = [
        'meter_reading_id',
        'user_id',
        'type',
        'message',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'correction' => 'تصحيح',
            'ready' => 'جاهز للاستلام',
            default => 'أولي',
        };
    }
}
