<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class TweetsmsService
{
    private const SEND_URL = 'https://tweetsms.ps/api.php/maan/sendsms';

    private const BALANCE_URL = 'https://tweetsms.ps/api.php/maan/chk_balance';

    /** @var array<int, string> */
    private const ERROR_DESCRIPTIONS = [
        -100 => 'missing parameters',
        -110 => 'invalid user name or password',
        -111 => 'account not activated',
        -112 => 'blocked user',
        -114 => 'site sending case is stopped',
        -115 => 'invalid sender',
        -116 => 'invalid sender',
        -120 => 'no numbers found',
        -124 => 'you have no enough credit to send that message',
        -126 => 'can not send right now (may be you are sending from other source)',
    ];

    public function isConfigured(): bool
    {
        return filled($this->apiKey()) && filled($this->sender());
    }

    /**
     * @return array{success: bool, desc: string, code: int|null}
     */
    public function sendSms(string $to, string $message): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'desc' => 'إعدادات مزود SMS غير مكتملة (مفتاح API أو اسم المرسل).',
                'code' => null,
            ];
        }

        $response = Http::asForm()
            ->post(self::SEND_URL, [
                'api_key' => $this->apiKey(),
                'sender' => $this->sender(),
                'message' => $message,
                'to' => $this->normalizePhone($to),
            ]);

        return $this->parseResponse($response->json(), $response->body());
    }

    /**
     * @return array{success: bool, desc: string, balance: string|null, code: int|null}
     */
    public function checkBalance(): array
    {
        $apiKey = $this->apiKey();

        if (! filled($apiKey)) {
            return [
                'success' => false,
                'desc' => 'غير مُعد',
                'balance' => null,
                'code' => null,
            ];
        }

        $request = Http::asForm();

        $token = $this->token();
        if (filled($token)) {
            $request = $request->withHeaders(['X-Auth-Token' => $token]);
        }

        $body = ['api_key' => $apiKey];
        if (filled($token)) {
            $body['token'] = $token;
        }

        $response = $request->post(self::BALANCE_URL, $body);
        $parsed = $this->parseResponse($response->json(), $response->body());

        return [
            'success' => $parsed['success'],
            'desc' => $parsed['desc'],
            'balance' => $parsed['success'] ? $this->extractBalance($response->json()) : null,
            'code' => $parsed['code'],
        ];
    }

    private function apiKey(): ?string
    {
        return Setting::get('sms_api_key');
    }

    private function sender(): ?string
    {
        return Setting::get('sms_sender');
    }

    private function token(): ?string
    {
        return Setting::get('sms_token');
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[\s\-]+/', '', trim($phone)) ?? trim($phone);
    }

    /**
     * @return array{success: bool, desc: string, code: int|null}
     */
    private function parseResponse(mixed $data, string $rawBody = ''): array
    {
        if ($data === null || ! is_array($data)) {
            $data = $this->decodeResponseBody($rawBody);
        }

        if ($data === null) {
            return [
                'success' => false,
                'desc' => 'خطأ غير معروف من مزود SMS.',
                'code' => null,
            ];
        }

        $code = isset($data['code']) ? (int) $data['code'] : null;
        $status = strtolower(trim((string) ($data['status'] ?? '')));
        $success = $status === 'success';

        return [
            'success' => $success,
            'desc' => $this->resolveDescription($data, $code),
            'code' => $code,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeResponseBody(string $rawBody): ?array
    {
        $rawBody = preg_replace('/^\xEF\xBB\xBF/', '', trim($rawBody));
        if ($rawBody === '') {
            return null;
        }

        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $data = [];

        if (preg_match('/"code"\s*:\s*(-?\d+)/', $rawBody, $codeMatch)) {
            $data['code'] = (int) $codeMatch[1];
        }

        if (preg_match('/"desc"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $rawBody, $descMatch)) {
            $data['desc'] = stripcslashes($descMatch[1]);
        }

        if (preg_match('/"status"\s*:\s*"([^"]*)"/', $rawBody, $statusMatch)) {
            $data['status'] = $statusMatch[1];
        }

        return $data !== [] ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveDescription(array $data, ?int $code): string
    {
        if (isset($data['desc']) && $data['desc'] !== '') {
            return (string) $data['desc'];
        }

        if (isset($data['message']) && $data['message'] !== '') {
            return (string) $data['message'];
        }

        if ($code !== null && isset(self::ERROR_DESCRIPTIONS[$code])) {
            return self::ERROR_DESCRIPTIONS[$code];
        }

        return 'خطأ غير معروف من مزود SMS.';
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function extractBalance(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        if (isset($data['balance'])) {
            return (string) $data['balance'];
        }

        return isset($data['desc']) ? (string) $data['desc'] : null;
    }
}
