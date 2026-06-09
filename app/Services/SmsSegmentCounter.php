<?php

namespace App\Services;

class SmsSegmentCounter
{
    public function count(string $message): int
    {
        $numOfCharsTyped = mb_strlen($message, 'utf-8');

        if (preg_match('/^([-a-zA-Z0-9_ \n\r\s,;:.!@Ã‚Â£?#$&*+=\/<>\'\^{})(%\-|])*$/', $message)) {
            $maxEnglishChar = $numOfCharsTyped <= 160 ? 160 : 153;

            return (int) ceil($numOfCharsTyped / $maxEnglishChar);
        }

        $maxArabicChar = $numOfCharsTyped <= 70 ? 70 : 67;

        return (int) ceil($numOfCharsTyped / $maxArabicChar);
    }

    public function formatCostLine(int $segments): string
    {
        return match ($segments) {
            1 => 'تكلفة الإرسال: رسالة واحدة',
            2 => 'تكلفة الإرسال: رسالتان',
            default => "تكلفة الإرسال: {$segments} رسائل",
        };
    }

    public function appendCostToPreview(string $message): string
    {
        if ($message === '') {
            return 'لا يوجد نص رسالة.';
        }

        return $message."\n\n———\n".$this->formatCostLine($this->count($message));
    }

    public function renderPreviewHtml(string $message): string
    {
        if ($message === '') {
            return '<p style="color:#6b7280;font-size:0.875rem;">لا يوجد نص رسالة.</p>';
        }

        $costLine = e($this->formatCostLine($this->count($message)));

        return <<<HTML
            <div style="white-space:pre-wrap;line-height:1.7;font-size:0.925rem;color:#374151;margin-bottom:1.25rem;">{$this->escapeMessage($message)}</div>
            <div style="display:flex;justify-content:center;margin-top:0.5rem;">
                <span style="display:inline-flex;align-items:center;gap:0.5rem;background:linear-gradient(135deg,#ecfdf5,#d1fae5);color:#047857;border:2px solid #34d399;border-radius:9999px;padding:0.625rem 1.25rem;font-weight:700;font-size:1rem;box-shadow:0 2px 8px rgba(16,185,129,0.2);">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:1.5rem;height:1.5rem;background:#10b981;color:#fff;border-radius:50%;font-size:0.75rem;font-weight:800;">SMS</span>
                    {$costLine}
                </span>
            </div>
            HTML;
    }

    private function escapeMessage(string $message): string
    {
        return e($message);
    }
}
