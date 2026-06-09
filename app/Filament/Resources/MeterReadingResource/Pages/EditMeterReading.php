<?php

namespace App\Filament\Resources\MeterReadingResource\Pages;

use App\Filament\Resources\MeterReadingResource;
use App\Services\MeterReadingCalculator;
use App\Services\MeterReadingSmsService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditMeterReading extends EditRecord
{
    protected static string $resource = MeterReadingResource::class;

    public bool $correctionSmsConfirmed = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف')
                ->visible(fn () => ! $this->record->hasSmsBeenSent()),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('saveWithSms')
                ->label('حفظ وإرسال SMS')
                ->color('info')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn () => $this->shouldShowCorrectionSmsAction())
                ->requiresConfirmation()
                ->modalHeading('تأكيد الحفظ وإرسال SMS')
                ->modalDescription(fn () => $this->getCorrectionSmsPreview())
                ->action(function () {
                    $this->correctionSmsConfirmed = true;
                    $this->save();
                }),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث بيانات السجل';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->applyCalculatedFields($data);

        if ($this->record->requiresCorrectionSms($data) && ! $this->correctionSmsConfirmed) {
            throw ValidationException::withMessages([
                'data.reading_value' => 'تغيّرت بيانات التكلفة. استخدم "حفظ وإرسال SMS" لإبلاغ الزبون.',
            ]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->correctionSmsConfirmed) {
            $this->record->refresh();
            app(MeterReadingSmsService::class)->send($this->record, 'correction');
            $this->correctionSmsConfirmed = false;
        }
    }

    private function applyCalculatedFields(array $data): array
    {
        $calculated = app(MeterReadingCalculator::class)->calculateFromWeight(
            (float) ($data['reading_value'] ?? 0),
            isset($data['price_per_unit']) ? (float) $data['price_per_unit'] : null,
        );

        $data['consumption'] = $calculated['consumption'];
        $data['amount'] = $calculated['amount'];
        $data['net_amount'] = $calculated['net_amount'];
        $data['price_per_unit'] = $calculated['price_per_unit'];

        return $data;
    }

    private function shouldShowCorrectionSmsAction(): bool
    {
        if (! $this->record->hasSmsBeenSent()) {
            return false;
        }

        try {
            $data = $this->applyCalculatedFields($this->form->getState());

            return $this->record->requiresCorrectionSms($data);
        } catch (\Throwable) {
            return false;
        }
    }

    private function getCorrectionSmsPreview(): \Illuminate\Support\HtmlString
    {
        try {
            $data = $this->applyCalculatedFields($this->form->getState());
            $message = app(MeterReadingSmsService::class)->buildMessageFromData(
                $this->record,
                $data,
                'correction',
            );

            return app(MeterReadingSmsService::class)->formatPreviewWithSegmentCost($message);
        } catch (\Throwable) {
            return app(MeterReadingSmsService::class)->formatPreviewWithSegmentCost('');
        }
    }
}
