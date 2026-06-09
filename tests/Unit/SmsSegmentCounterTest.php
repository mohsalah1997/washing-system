<?php

namespace Tests\Unit;

use App\Services\SmsSegmentCounter;
use Tests\TestCase;

class SmsSegmentCounterTest extends TestCase
{
    private SmsSegmentCounter $counter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->counter = app(SmsSegmentCounter::class);
    }

    public function test_short_arabic_message_counts_as_one_segment(): void
    {
        $message = 'مرحباً محمد، غسيلك جاهز للاستلام.';

        $this->assertSame(1, $this->counter->count($message));
    }

    public function test_long_arabic_message_counts_as_multiple_segments(): void
    {
        $message = str_repeat('ا', 71);

        $this->assertSame(2, $this->counter->count($message));
    }

    public function test_short_english_message_counts_as_one_segment(): void
    {
        $message = 'Hello, your laundry is ready for pickup. Cost: 15.00';

        $this->assertSame(1, $this->counter->count($message));
    }

    public function test_format_cost_line_for_one_two_and_three_segments(): void
    {
        $this->assertSame('تكلفة الإرسال: رسالة واحدة', $this->counter->formatCostLine(1));
        $this->assertSame('تكلفة الإرسال: رسالتان', $this->counter->formatCostLine(2));
        $this->assertSame('تكلفة الإرسال: 3 رسائل', $this->counter->formatCostLine(3));
    }

    public function test_append_cost_to_preview_includes_cost_line(): void
    {
        $message = 'مرحباً محمد، غسيلك جاهز.';

        $preview = $this->counter->appendCostToPreview($message);

        $this->assertStringContainsString($message, $preview);
        $this->assertStringContainsString('———', $preview);
        $this->assertStringContainsString('تكلفة الإرسال: رسالة واحدة', $preview);
    }

    public function test_append_cost_to_preview_handles_empty_message(): void
    {
        $this->assertSame('لا يوجد نص رسالة.', $this->counter->appendCostToPreview(''));
    }

    public function test_render_preview_html_shows_prominent_cost_badge(): void
    {
        $message = 'مرحباً محمد، غسيلك جاهز.';

        $html = $this->counter->renderPreviewHtml($message);

        $this->assertStringContainsString(e($message), $html);
        $this->assertStringContainsString('تكلفة الإرسال: رسالة واحدة', $html);
        $this->assertStringContainsString('border-radius:9999px', $html);
        $this->assertStringContainsString('SMS', $html);
    }

    public function test_render_preview_html_handles_empty_message(): void
    {
        $html = $this->counter->renderPreviewHtml('');

        $this->assertStringContainsString('لا يوجد نص رسالة.', $html);
    }
}
