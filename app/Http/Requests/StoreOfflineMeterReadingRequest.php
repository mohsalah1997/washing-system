<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfflineMeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public static function rulesForItem(string $prefix = 'readings.*.'): array
    {
        return [
            $prefix . 'client_uuid' => ['required', 'string', 'max:100'],
            $prefix . 'customer_id' => ['required', 'integer'],
            $prefix . 'reading_value' => ['required', 'numeric', 'min:0'],
            $prefix . 'reading_date' => ['required', 'date'],
            $prefix . 'note' => ['nullable', 'string'],
        ];
    }
}
