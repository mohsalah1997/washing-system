<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'purchase_date',
        'description',
        'amount',
        'method',
        'supplier',
        'note',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
