<?php

namespace App\Filament\Resources\MeterReadingResource\Pages;

use App\Filament\Resources\MeterReadingResource;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Setting;
use App\Services\MeterReadingCalculator;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ListMeterReadings extends ListRecords
{
    protected static string $resource = MeterReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة عملية غسيل'),
            Actions\Action::make('importReadingsFromExcel')
                ->label('استيراد من Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('استيراد عمليات غسيل من Excel')
                ->modalDescription('ارفع ملف القالب، حدد تاريخ العملية وسعر الكيلو. يُطبَّق الحد الأدنى من الإعدادات تلقائيًا.')
                ->form([
                    FileUpload::make('file')
                        ->label('ملف Excel')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ]),
                    DatePicker::make('reading_date')
                        ->label('تاريخ العملية')
                        ->default(now())
                        ->required(),
                    TextInput::make('price_per_unit')
                        ->label('سعر الكيلو')
                        ->numeric()
                        ->default(fn() => (float) Setting::get('price_per_unit', 0))
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $calculator = app(MeterReadingCalculator::class);
                        $path = storage_path('app/' . $data['file']);
                        if (! file_exists($path)) {
                            throw new \RuntimeException('تعذر الوصول إلى ملف الإكسل المرفوع.');
                        }

                        $spreadsheet = IOFactory::load($path);
                        $sheet = $spreadsheet->getActiveSheet();
                        $rows = $sheet->toArray(null, true, true, true);

                        $errors = [];
                        $created = 0;

                        foreach ($rows as $index => $row) {
                            if ($index === 1) {
                                continue;
                            }

                            $id = $row['A'] ?? null;
                            $name = trim((string) ($row['B'] ?? ''));
                            $weight = $row['C'] ?? null;

                            if (! $id && ($weight === null || $weight === '')) {
                                continue;
                            }

                            if (! is_numeric((string) $id)) {
                                $errors[] = [
                                    'row' => $index,
                                    'customer_id' => (string) $id,
                                    'customer_name' => $name,
                                    'weight' => $weight,
                                    'error' => 'قيمة ID غير صحيحة.',
                                ];
                                continue;
                            }

                            $customer = Customer::find($id);
                            if (! $customer) {
                                $errors[] = [
                                    'row' => $index,
                                    'customer_id' => (int) $id,
                                    'customer_name' => $name,
                                    'weight' => $weight,
                                    'error' => "زبون بالـ ID {$id} غير موجود.",
                                ];
                                continue;
                            }

                            if ($weight === null || $weight === '' || ! is_numeric((string) $weight) || (float) $weight <= 0) {
                                $errors[] = [
                                    'row' => $index,
                                    'customer_id' => $customer->id,
                                    'customer_name' => $customer->name,
                                    'weight' => $weight,
                                    'error' => 'وزن الغسيل فارغ أو غير صحيح.',
                                ];
                                continue;
                            }

                            $weightKg = (float) $weight;

                            try {
                                $calculated = $calculator->calculateFromWeight(
                                    $weightKg,
                                    (float) $data['price_per_unit']
                                );
                            } catch (\RuntimeException $e) {
                                $errors[] = [
                                    'row' => $index,
                                    'customer_id' => $customer->id,
                                    'customer_name' => $customer->name,
                                    'weight' => $weight,
                                    'error' => $e->getMessage(),
                                ];
                                continue;
                            }

                            MeterReading::create([
                                'customer_id' => $customer->id,
                                'reading_value' => $calculated['reading_value'],
                                'reading_date' => $data['reading_date'],
                                'consumption' => $calculated['consumption'],
                                'price_per_unit' => $calculated['price_per_unit'],
                                'amount' => $calculated['amount'],
                                'net_amount' => $calculated['net_amount'],
                                'is_approved' => true,
                            ]);

                            $created++;
                        }

                        if (! empty($errors)) {
                            $errorsReportPath = $this->buildImportErrorsReport($errors);

                            Notification::make()
                                ->title('تم الاستيراد مع أخطاء')
                                ->body("تمت إضافة {$created} عملية. يوجد " . count($errors) . ' سطر فيه مشاكل.')
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('downloadErrors')
                                        ->label('تحميل ملف الأخطاء')
                                        ->url(Storage::url($errorsReportPath), shouldOpenInNewTab: true),
                                ])
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('تم الاستيراد بنجاح')
                                ->body("تمت إضافة {$created} عملية غسيل معتمدة.")
                                ->success()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('خطأ في استيراد الملف')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    private function buildImportErrorsReport(array $errors): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('B1', 'اسم الزبون');
        $sheet->setCellValue('C1', 'وزن الغسيل');
        $sheet->setCellValue('D1', 'سبب الخطأ');

        $rowNumber = 2;
        foreach ($errors as $error) {
            $sheet->setCellValue('A' . $rowNumber, $error['customer_id'] ?? '');
            $sheet->setCellValue('B' . $rowNumber, $error['customer_name'] ?? '');
            $sheet->setCellValue('C' . $rowNumber, $error['weight'] ?? '');
            $sheet->setCellValue('D' . $rowNumber, $error['error'] ?? '');
            $rowNumber++;
        }

        $directory = 'public/import_errors';
        Storage::makeDirectory($directory);

        $fileName = 'laundry_import_errors_' . now()->format('Y_m_d_His') . '.xlsx';
        $relativePath = $directory . '/' . $fileName;
        $fullPath = storage_path('app/' . $relativePath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $relativePath;
    }
}
