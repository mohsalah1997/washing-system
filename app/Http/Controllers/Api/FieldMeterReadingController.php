<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfflineMeterReadingRequest;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Setting;
use App\Services\MeterReadingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FieldMeterReadingController extends Controller
{
    private function normalizeClientUuid(string $rawClientId): string
    {
        $raw = Str::lower(trim($rawClientId));
        if (Str::isUuid($raw)) {
            return $raw;
        }

        $hash = md5($raw);

        return sprintf(
            '%s-%s-4%s-%s%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 13, 3),
            dechex((hexdec(substr($hash, 16, 1)) & 0x3) | 0x8),
            substr($hash, 17, 3),
            substr($hash, 20, 12)
        );
    }

    public function bootstrap(): JsonResponse
    {
        return response()->json([
            'settings' => [
                'price_per_unit' => (float) Setting::get('price_per_unit', 0),
                'minimum_amount' => (float) Setting::get('minimum_amount', 0),
            ],
        ]);
    }

    public function customers(): JsonResponse
    {
        $customers = Customer::query()
            ->orderBy('id')
            ->get(['id', 'name', 'phone'])
            ->map(function (Customer $customer) {
                $lastReading = $customer->meterReadings()
                    ->orderByDesc('reading_date')
                    ->orderByDesc('id')
                    ->first();

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'last_weight' => (float) ($lastReading?->reading_value ?? 0),
                ];
            });

        return response()->json([
            'customers' => $customers,
        ]);
    }

    public function sync(Request $request, MeterReadingCalculator $calculator): JsonResponse
    {
        $payload = $request->validate([
            'readings' => ['required', 'array', 'min:1', 'max:200'],
            ...StoreOfflineMeterReadingRequest::rulesForItem(),
        ]);

        $results = [];

        foreach ($payload['readings'] as $item) {
            $normalizedClientUuid = $this->normalizeClientUuid((string) $item['client_uuid']);

            $result = [
                'client_uuid' => $normalizedClientUuid,
                'status' => 'rejected',
            ];

            $existing = MeterReading::query()
                ->where('client_uuid', $normalizedClientUuid)
                ->first();

            if ($existing) {
                $result['status'] = 'synced';
                $result['server_id'] = $existing->id;
                $result['message'] = 'Already synced.';
                $results[] = $result;
                continue;
            }

            $customer = Customer::find($item['customer_id']);
            if (! $customer) {
                $result['message'] = 'Customer not found.';
                $results[] = $result;
                continue;
            }

            try {
                $calculated = $calculator->calculateFromWeight(
                    (float) $item['reading_value'],
                    null
                );
            } catch (\RuntimeException $e) {
                $result['message'] = $e->getMessage();
                $results[] = $result;
                continue;
            }

            DB::transaction(function () use ($item, $customer, $calculated, $normalizedClientUuid, &$result) {
                $reading = MeterReading::create([
                    'customer_id' => $customer->id,
                    'reading_value' => $calculated['reading_value'],
                    'reading_date' => $item['reading_date'],
                    'consumption' => $calculated['consumption'],
                    'price_per_unit' => $calculated['price_per_unit'],
                    'amount' => $calculated['amount'],
                    'net_amount' => $calculated['net_amount'],
                    'is_approved' => true,
                    'note' => $item['note'] ?? null,
                    'source' => 'mobile_offline_sync',
                    'client_uuid' => $normalizedClientUuid,
                ]);

                $result['status'] = 'synced';
                $result['server_id'] = $reading->id;
                $result['message'] = 'Synced successfully.';
            });

            $results[] = $result;
        }

        $syncedCount = collect($results)->where('status', 'synced')->count();

        return response()->json([
            'summary' => [
                'received' => count($results),
                'synced' => $syncedCount,
                'rejected' => count($results) - $syncedCount,
            ],
            'results' => $results,
        ]);
    }
}
