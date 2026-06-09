<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\TweetsmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TweetsmsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_configured_requires_api_key_and_sender(): void
    {
        $service = app(TweetsmsService::class);

        $this->assertFalse($service->isConfigured());

        Setting::set('sms_api_key', 'test-key');
        $this->assertFalse($service->isConfigured());

        Setting::set('sms_sender', 'sender');
        $this->assertTrue($service->isConfigured());
    }

    public function test_send_sms_success(): void
    {
        Setting::set('sms_api_key', 'test-key');
        Setting::set('sms_sender', 'sender');

        Http::fake([
            'tweetsms.ps/api.php/maan/sendsms' => Http::response([
                'status' => 'success',
                'code' => 999,
                'desc' => 'message scheduled',
            ]),
        ]);

        $result = app(TweetsmsService::class)->sendSms('0592106097', 'test message');

        $this->assertTrue($result['success']);
        $this->assertSame(999, $result['code']);
        $this->assertSame('message scheduled', $result['desc']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://tweetsms.ps/api.php/maan/sendsms'
                && $request['api_key'] === 'test-key'
                && $request['sender'] === 'sender'
                && $request['message'] === 'test message'
                && $request['to'] === '0592106097';
        });
    }

    public function test_send_sms_failure_returns_desc(): void
    {
        Setting::set('sms_api_key', 'test-key');
        Setting::set('sms_sender', 'sender');

        Http::fake([
            'tweetsms.ps/api.php/maan/sendsms' => Http::response([
                'status' => 'error',
                'code' => -110,
                'desc' => 'wrong user name or password',
            ]),
        ]);

        $result = app(TweetsmsService::class)->sendSms('0592106097', 'test');

        $this->assertFalse($result['success']);
        $this->assertSame(-110, $result['code']);
        $this->assertSame('wrong user name or password', $result['desc']);
    }

    public function test_send_sms_not_configured(): void
    {
        $result = app(TweetsmsService::class)->sendSms('0592106097', 'test');

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    public function test_check_balance_success(): void
    {
        Setting::set('sms_api_key', 'test-key');
        Setting::set('sms_token', 'test-token');

        Http::fake([
            'tweetsms.ps/api.php/maan/chk_balance' => Http::response([
                'status' => 'success',
                'code' => 999,
                'desc' => 'balance retrieved successfully',
                'balance' => '150',
            ]),
        ]);

        $result = app(TweetsmsService::class)->checkBalance();

        $this->assertTrue($result['success']);
        $this->assertSame(999, $result['code']);
        $this->assertSame('150', $result['balance']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://tweetsms.ps/api.php/maan/chk_balance'
                && $request['api_key'] === 'test-key'
                && $request['token'] === 'test-token'
                && $request->hasHeader('X-Auth-Token', 'test-token');
        });
    }

    public function test_check_balance_missing_parameters(): void
    {
        Setting::set('sms_api_key', 'test-key');

        Http::fake([
            'tweetsms.ps/api.php/maan/chk_balance' => Http::response([
                'code' => -100,
                'desc' => 'missing parameters',
            ]),
        ]);

        $result = app(TweetsmsService::class)->checkBalance();

        $this->assertFalse($result['success']);
        $this->assertSame(-100, $result['code']);
        $this->assertSame('missing parameters', $result['desc']);
        $this->assertNull($result['balance']);
    }

    public function test_check_balance_wrong_credentials(): void
    {
        Setting::set('sms_api_key', 'bad-key');

        Http::fake([
            'tweetsms.ps/api.php/maan/chk_balance' => Http::response([
                'code' => -110,
                'desc' => 'wrong user name or password',
            ]),
        ]);

        $result = app(TweetsmsService::class)->checkBalance();

        $this->assertFalse($result['success']);
        $this->assertSame(-110, $result['code']);
        $this->assertSame('wrong user name or password', $result['desc']);
    }

    public function test_check_balance_not_configured(): void
    {
        $result = app(TweetsmsService::class)->checkBalance();

        $this->assertFalse($result['success']);
        $this->assertSame('غير مُعد', $result['desc']);
        Http::assertNothingSent();
    }

    public function test_send_sms_failure_invalid_phone_returns_error_status(): void
    {
        Setting::set('sms_api_key', 'test-key');
        Setting::set('sms_sender', 'sender');

        Http::fake([
            'tweetsms.ps/api.php/maan/sendsms' => Http::response([
                'status' => 'error',
                'code' => -120,
                'desc' => 'no numbers found',
            ]),
        ]);

        $result = app(TweetsmsService::class)->sendSms('000', 'test');

        $this->assertFalse($result['success']);
        $this->assertSame(-120, $result['code']);
        $this->assertSame('no numbers found', $result['desc']);
    }

    public function test_send_sms_success_when_status_is_success(): void
    {
        Setting::set('sms_api_key', 'test-key');
        Setting::set('sms_sender', 'sender');

        Http::fake([
            'tweetsms.ps/api.php/maan/sendsms' => Http::response([
                'status' => 'success',
                'code' => 999,
                'desc' => 'send success',
            ]),
        ]);

        $result = app(TweetsmsService::class)->sendSms('0592106097', 'test');

        $this->assertTrue($result['success']);
        $this->assertSame(999, $result['code']);
        $this->assertSame('send success', $result['desc']);
    }

    public function test_send_sms_failure_when_code_999_but_status_is_error(): void
    {
        Setting::set('sms_api_key', 'test-key');
        Setting::set('sms_sender', 'sender');

        Http::fake([
            'tweetsms.ps/api.php/maan/sendsms' => Http::response([
                'status' => 'error',
                'code' => 999,
                'desc' => 'unexpected error',
            ]),
        ]);

        $result = app(TweetsmsService::class)->sendSms('0592106097', 'test');

        $this->assertFalse($result['success']);
        $this->assertSame(999, $result['code']);
        $this->assertSame('unexpected error', $result['desc']);
    }

    public function test_send_sms_failure_uses_known_code_fallback_when_desc_missing(): void
    {
        Setting::set('sms_api_key', 'test-key');
        Setting::set('sms_sender', 'sender');

        Http::fake([
            'tweetsms.ps/api.php/maan/sendsms' => Http::response([
                'status' => 'error',
                'code' => -124,
            ]),
        ]);

        $result = app(TweetsmsService::class)->sendSms('0592106097', 'test');

        $this->assertFalse($result['success']);
        $this->assertSame(-124, $result['code']);
        $this->assertSame('you have no enough credit to send that message', $result['desc']);
    }

    public function test_send_sms_failure_extracts_code_from_raw_body_when_only_code_present(): void
    {
        Setting::set('sms_api_key', 'test-key');
        Setting::set('sms_sender', 'sender');

        Http::fake([
            'tweetsms.ps/api.php/maan/sendsms' => Http::response(
                '{"status":"error","code":-110}',
                200,
                ['Content-Type' => 'text/plain']
            ),
        ]);

        $result = app(TweetsmsService::class)->sendSms('0592106097', 'test');

        $this->assertFalse($result['success']);
        $this->assertSame(-110, $result['code']);
        $this->assertSame('invalid user name or password', $result['desc']);
    }
}
