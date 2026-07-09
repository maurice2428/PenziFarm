<?php

namespace App\Filament\Resources\AnimalWeightResource\Pages;

use App\Filament\Resources\AnimalWeightResource;
use App\Models\Animal;
use App\Models\AnimalWeight;
use App\Models\Breed;
use App\Models\Location;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ListAnimalWeights extends ListRecords
{
    protected static string $resource = AnimalWeightResource::class;

    private const TEMPLATE_VERSION = 'LW-WEIGHT-LOCATION-V2';

    private const HEADER_ROW = 11;

    private const FIRST_DATA_ROW = self::HEADER_ROW + 1;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadWeightTemplate')
                ->label('Download Bulk Import Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalIcon('heroicon-o-scale')
                ->modalHeading('Generate Animal Weight Excel Template')
                ->modalDescription('Select the animal location first, then choose the breed being weighed.')
                ->modalSubmitActionLabel('Generate & Download Excel Template')
                ->modalCancelActionLabel('Cancel')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->stickyModalFooter()
                /*
                 * ->form([
                 *     Forms\Components\Section::make('Weighing Scope')
                 *         ->description('Choose the exact station and breed. The template will contain only active animals that match both selections.')
                 *         ->icon('heroicon-o-map-pin')
                 *         ->columns(2)
                 *         ->schema([
                 *             Forms\Components\Select::make('location_id')
                 *                 ->label('Animal Location / Station')
                 *                 ->options(
                 *                     Location::query()
                 *                         ->where('is_active', true)
                 *                         ->orderByDesc('is_default')
                 *                         ->orderBy('name')
                 *                         ->get()
                 *                         ->mapWithKeys(fn(Location $location): array => [
                 *                             $location->id => $location->display_name,
                 *                         ])
                 *                         ->all()
                 *                 )
                 *                 ->searchable()
                 *                 ->preload()
                 *                 ->native(false)
                 *                 ->live()
                 *                 ->required()
                 *                 ->helperText('Select the current station where animals are being weighed.')
                 *                 ->afterStateUpdated(function (Forms\Set $set): void {
                 *                     $set('breed_id', null);
                 *                 }),
                 *             Forms\Components\Select::make('breed_id')
                 *                 ->label('Breed')
                 *                 ->options(function (Forms\Get $get): array {
                 *                     $locationId = $get('location_id');
                 *
                 *                     if (blank($locationId)) {
                 *                         return [];
                 *                     }
                 *
                 *                     return Breed::query()
                 *                         ->where('is_active', true)
                 *                         ->whereIn(
                 *                             'id',
                 *                             Animal::query()
                 *                                 ->select('breed_id')
                 *                                 ->where('current_location_id', $locationId)
                 *                                 ->where('status', 'Active')
                 *                                 ->where('is_archived', false)
                 *                                 ->whereNotNull('breed_id')
                 *                                 ->distinct()
                 *                         )
                 *                         ->orderBy('parent_category')
                 *                         ->orderBy('breed_name')
                 *                         ->get()
                 *                         ->mapWithKeys(fn(Breed $breed): array => [
                 *                             $breed->id => "{$breed->parent_category} - {$breed->breed_name}",
                 *                         ])
                 *                         ->all();
                 *                 })
                 *                 ->searchable()
                 *                 ->preload()
                 *                 ->native(false)
                 *                 ->live()
                 *                 ->required()
                 *                 ->disabled(fn(Forms\Get $get): bool => blank($get('location_id')))
                 *                 ->helperText('Only breeds with active animals at the selected station are shown.'),
                 *             Forms\Components\DateTimePicker::make('recorded_at')
                 *                 ->label('Default Recorded Date & Time')
                 *                 ->seconds(false)
                 *                 ->default(now())
                 *                 ->required()
                 *                 ->helperText('This date and time is prefilled in every row of the Excel template.'),
                 *             Forms\Components\Placeholder::make('scope_preview')
                 *                 ->label('Template Coverage Preview')
                 *                 ->columnSpanFull()
                 *                 ->content(function (Forms\Get $get): HtmlString {
                 *                     $locationId = $get('location_id');
                 *                     $breedId = $get('breed_id');
                 *
                 *                     if (blank($locationId)) {
                 *                         return new HtmlString('
                 *                             <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                 *                                 Select an animal location to begin.
                 *                             </div>
                 *                         ');
                 *                     }
                 *
                 *                     $location = Location::find($locationId);
                 *
                 *                     if (blank($breedId)) {
                 *                         return new HtmlString('
                 *                             <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-900 dark:bg-warning-950/30 dark:text-warning-300">
                 *                                 Location selected: <strong>' . e($location?->display_name ?? 'Unknown') . '</strong>.
                 *                                 Select a breed to calculate the final template coverage.
                 *                             </div>
                 *                         ');
                 *                     }
                 *
                 *                     $breed = Breed::find($breedId);
                 *
                 *                     $count = Animal::query()
                 *                         ->where('current_location_id', $locationId)
                 *                         ->where('breed_id', $breedId)
                 *                         ->where('status', 'Active')
                 *                         ->where('is_archived', false)
                 *                         ->count();
                 *
                 *                     return new HtmlString('
                 *                         <div class="rounded-xl border border-success-200 bg-success-50 p-4 text-sm text-success-800 dark:border-success-900 dark:bg-success-950/30 dark:text-success-300">
                 *                             <strong>' . number_format($count) . ' active animal(s)</strong> will be included from
                 *                             <strong>' . e($location?->display_name ?? 'Unknown location') . '</strong>
                 *                             under <strong>' . e($breed?->breed_name ?? 'Unknown breed') . '</strong>.
                 *                         </div>
                 *                     ');
                 *                 }),
                 *         ]),
                 *     Forms\Components\Section::make('Template Instructions')
                 *         ->description('The system preloads the animals and their last recorded weight. Only weighing fields should be edited.')
                 *         ->icon('heroicon-o-information-circle')
                 *         ->schema([
                 *             Forms\Components\Placeholder::make('instructions')
                 *                 ->label('')
                 *                 ->content(new HtmlString('
                 *                     <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm leading-7 text-primary-900 dark:border-primary-900 dark:bg-primary-950/30 dark:text-primary-200">
                 *                         <strong>Fill only:</strong>
                 *                         <code>weight_kg</code>,
                 *                         <code>recorded_at</code>, and
                 *                         <code>remarks</code>.<br><br>
                 *
                 *                         Do not change tag number, location, breed, species, sex, status, previous weight, template details, or system notes.<br><br>
                 *
                 *                         Blank weight rows are skipped safely during import.
                 *                     </div>
                 *                 ')),
                 *         ]),
                 * ])
                 */
                ->form([
                    Forms\Components\Section::make('1. Select Weighing Scope')
                        ->description('Choose the station first, then select the breed being weighed. The template will include only active animals matching both selections.')
                        ->icon('heroicon-o-map-pin')
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ])
                        ->schema([
                            Forms\Components\Select::make('location_id')
                                ->label('Animal Location / Station')
                                ->placeholder('Select  where animals are being weighed')
                                ->prefixIcon('heroicon-m-map-pin')
                                ->options(
                                    Location::query()
                                        ->where('is_active', true)
                                        ->orderByDesc('is_default')
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn(Location $location): array => [
                                            $location->id => $location->display_name,
                                        ])
                                        ->all()
                                )
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->live()
                                ->required()
                                ->helperText('Start here. Breed options will load after you select a station.')
                                ->afterStateUpdated(function (Forms\Set $set): void {
                                    $set('breed_id', null);
                                }),
                            Forms\Components\Select::make('breed_id')
                                ->label('Breed')
                                ->placeholder(fn(Forms\Get $get): string => blank($get('location_id'))
                                    ? 'Select a station first'
                                    : 'Select breed')
                                ->prefixIcon('heroicon-m-tag')
                                ->options(function (Forms\Get $get): array {
                                    $locationId = $get('location_id');

                                    if (blank($locationId)) {
                                        return [];
                                    }

                                    return Breed::query()
                                        ->where('is_active', true)
                                        ->whereIn(
                                            'id',
                                            Animal::query()
                                                ->select('breed_id')
                                                ->where('current_location_id', $locationId)
                                                ->where('status', 'Active')
                                                ->where('is_archived', false)
                                                ->whereNotNull('breed_id')
                                                ->distinct()
                                        )
                                        ->orderBy('parent_category')
                                        ->orderBy('breed_name')
                                        ->get()
                                        ->mapWithKeys(fn(Breed $breed): array => [
                                            $breed->id => "{$breed->parent_category} - {$breed->breed_name}",
                                        ])
                                        ->all();
                                })
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->live()
                                ->required()
                                ->disabled(fn(Forms\Get $get): bool => blank($get('location_id')))
                                ->helperText('Shows only breeds with active animals at the selected station.'),
                        ]),
                    Forms\Components\Section::make('2. Recording Details')
                        ->description('Set the default weighing time and review the number of animals that will be included before downloading.')
                        ->icon('heroicon-o-calendar-days')
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ])
                        ->schema([
                            Forms\Components\DateTimePicker::make('recorded_at')
                                ->label('Default Recorded Date & Time')
                                ->prefixIcon('heroicon-m-calendar-days')
                                ->seconds(false)
                                ->default(now())
                                ->required()
                                ->helperText('Pre-filled in every spreadsheet row. You can adjust individual rows later.'),
                            Forms\Components\Placeholder::make('scope_preview')
                                ->label('Template Coverage Preview')
                                ->content(function (Forms\Get $get): HtmlString {
                                    $locationId = $get('location_id');
                                    $breedId = $get('breed_id');

                                    if (blank($locationId)) {
                                        return new HtmlString('
                            <div class="flex min-h-[92px] items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <svg class="h-6 w-6 flex-none text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.2 7-11A7 7 0 1 0 5 10c0 5.8 7 11 7 11Z" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                                <div>
                                    <div class="font-semibold">Waiting for station selection</div>
                                    <div class="mt-1 text-xs opacity-80">Choose the animal location to load its available breeds.</div>
                                </div>
                            </div>
                        ');
                                    }

                                    $location = Location::find($locationId);

                                    if (blank($breedId)) {
                                        return new HtmlString('
                            <div class="flex min-h-[92px] items-center gap-3 rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-900 dark:bg-warning-950/30 dark:text-warning-300">
                                <svg class="h-6 w-6 flex-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.6 2.5 17a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0Z" />
                                </svg>
                                <div>
                                    <div class="font-semibold">Station selected</div>
                                    <div class="mt-1 text-xs opacity-90">
                                        ' . e($location?->display_name ?? 'Unknown location') . ' selected. Choose a breed to calculate the animal count.
                                    </div>
                                </div>
                            </div>
                        ');
                                    }

                                    $breed = Breed::find($breedId);

                                    $count = Animal::query()
                                        ->where('current_location_id', $locationId)
                                        ->where('breed_id', $breedId)
                                        ->where('status', 'Active')
                                        ->where('is_archived', false)
                                        ->count();

                                    return new HtmlString('
                        <div class="flex min-h-[92px] items-center gap-3 rounded-xl border border-success-200 bg-success-50 p-4 text-sm text-success-800 dark:border-success-900 dark:bg-success-950/30 dark:text-success-300">
                            <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-success-400 text-lg font-bold text-">
                                ' . number_format($count) . '
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold">Animals ready for template</div>
                                <div class="mt-1 text-xs leading-5 opacity-10">
                                    Active <strong>' . e($breed?->breed_name ?? 'Unknown breed') . '</strong> animals at
                                    <strong>' . e($location?->display_name ?? 'Unknown location') . '</strong>.
                                </div>
                            </div>
                        </div>
                    ');
                                }),
                        ]),
                    Forms\Components\Section::make('3. Before You Download')
                        ->description('The spreadsheet contains protected reference data and editable weighing columns.')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Placeholder::make('instructions')
                                ->label('')
                                ->content(new HtmlString('
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-900 dark:bg-primary-950/30">
                            <div class="text-sm font-bold text-primary-800 dark:text-primary-200">1. Download</div>
                            <div class="mt-1 text-xs leading-5 text-primary-700 dark:text-primary-300">
                                The file includes only active animals from the chosen station and breed.
                            </div>
                        </div>

                        <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-900 dark:bg-warning-950/30">
                            <div class="text-sm font-bold text-warning-800 dark:text-warning-200">2. Fill Weights</div>
                            <div class="mt-1 text-xs leading-5 text-warning-700 dark:text-warning-300">
                                Edit only <code>weight_kg</code>, <code>recorded_at</code>, and <code>remarks</code>.
                            </div>
                        </div>

                        <div class="rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-900 dark:bg-success-950/30">
                            <div class="text-sm font-bold text-success-800 dark:text-success-200">3. Upload</div>
                            <div class="mt-1 text-xs leading-5 text-success-700 dark:text-success-300">
                                Upload the completed file using the “Upload Filled Template” button.
                            </div>
                        </div>
                    </div>
                ')),
                        ]),
                ])
                ->action(function (array $data) {
                    $location = Location::query()
                        ->whereKey($data['location_id'])
                        ->where('is_active', true)
                        ->first();

                    $breed = Breed::query()
                        ->whereKey($data['breed_id'])
                        ->where('is_active', true)
                        ->first();

                    if (!$location || !$breed) {
                        Notification::make()
                            ->danger()
                            ->title('Invalid template scope')
                            ->body('Select an active location and breed before generating the template.')
                            ->send();

                        return null;
                    }

                    $animals = Animal::query()
                        ->with(['latestWeight'])
                        ->where('current_location_id', $location->id)
                        ->where('breed_id', $breed->id)
                        ->where('status', 'Active')
                        ->where('is_archived', false)
                        ->orderBy('tag_number')
                        ->get();

                    if ($animals->isEmpty()) {
                        Notification::make()
                            ->warning()
                            ->title('No active animals found')
                            ->body("No active {$breed->breed_name} animals are assigned to {$location->display_name}.")
                            ->send();

                        return null;
                    }

                    $recordedAt = Carbon::parse($data['recorded_at'])
                        ->format('Y-m-d H:i');

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();

                    $sheet->setTitle('Weight Upload');

                    $sheet->mergeCells('A1:J1');
                    $sheet->setCellValue('A1', 'ANIMAL WEIGHT UPLOAD TEMPLATE');

                    $sheet->setCellValue('A3', 'Template Version');
                    $sheet->setCellValue('B3', self::TEMPLATE_VERSION);

                    $sheet->setCellValue('A4', 'Location ID');
                    $sheet->setCellValue('B4', $location->id);

                    $sheet->setCellValue('A5', 'Location Name');
                    $sheet->setCellValue('B5', $location->display_name);

                    $sheet->setCellValue('A6', 'Breed ID');
                    $sheet->setCellValue('B6', $breed->id);

                    $sheet->setCellValue('A7', 'Breed Name');
                    $sheet->setCellValue('B7', $breed->breed_name);

                    $sheet->setCellValue('A8', 'Generated At');
                    $sheet->setCellValue('B8', now('Africa/Nairobi')->format('Y-m-d H:i:s'));

                    $sheet->mergeCells('D3:J8');
                    $sheet->setCellValue(
                        'D3',
                        "IMPORTANT INSTRUCTIONS:\n"
                            . "1. Fill only WEIGHT KG, RECORDED AT, and REMARKS.\n"
                            . "2. Do not edit location, breed details, tag numbers, or system columns.\n"
                            . "3. Weight must be greater than 2 KG.\n"
                            . "4. Blank weight rows will be skipped safely.\n"
                            . '5. The import validates both location and breed before saving records.'
                    );

                    $headers = [
                        'tag_number',
                        'species',
                        'sex',
                        'current_status',
                        'last_weight_kg',
                        'last_recorded_at',
                        'weight_kg',
                        'recorded_at',
                        'remarks',
                        'system_note',
                    ];

                    $sheet->fromArray($headers, null, 'A' . self::HEADER_ROW);

                    $row = self::FIRST_DATA_ROW;

                    foreach ($animals as $animal) {
                        $lastWeight = $animal->latestWeight;

                        $sheet->fromArray([
                            $animal->tag_number,
                            $animal->species,
                            $animal->sex,
                            $animal->status,
                            $lastWeight?->weight_kg,
                            $lastWeight?->recorded_at?->format('Y-m-d H:i'),
                            '',
                            $recordedAt,
                            '',
                            'Fill weight_kg only if this animal was weighed.',
                        ], null, "A{$row}");

                        $row++;
                    }

                    $lastRow = $row - 1;

                    $sheet->freezePane('A' . self::FIRST_DATA_ROW);
                    $sheet->setAutoFilter('A' . self::HEADER_ROW . ":J{$lastRow}");

                    foreach (range('A', 'J') as $column) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }

                    $sheet->getColumnDimension('J')->setWidth(42);
                    $sheet->getRowDimension(3)->setRowHeight(88);

                    $sheet->getStyle('A1:J1')->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 16,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '008F00'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $sheet->getStyle('A3:B8')->applyFromArray([
                        'font' => ['bold' => true],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $sheet->getStyle('D3:J8')->applyFromArray([
                        'alignment' => [
                            'wrapText' => true,
                            'vertical' => Alignment::VERTICAL_TOP,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFF7ED'],
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $sheet->getStyle('A' . self::HEADER_ROW . ':J' . self::HEADER_ROW)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '111827'],
                        ],
                    ]);

                    $sheet->getStyle('G' . self::HEADER_ROW . ':I' . self::HEADER_ROW)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F59E0B'],
                        ],
                    ]);

                    $sheet->getStyle(
                        'A' . self::FIRST_DATA_ROW . ":F{$lastRow}"
                    )->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F3F4F6'],
                        ],
                    ]);

                    $sheet->getStyle(
                        'G' . self::FIRST_DATA_ROW . ":I{$lastRow}"
                    )->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'ECFDF5'],
                        ],
                    ]);

                    $sheet->getStyle(
                        'G' . self::FIRST_DATA_ROW . ":G{$lastRow}"
                    )->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                    $sheet->getStyle(
                        'H' . self::FIRST_DATA_ROW . ":H{$lastRow}"
                    )->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm');

                    $instructions = $spreadsheet->createSheet();
                    $instructions->setTitle('Instructions');

                    $instructions->fromArray([
                        ['LELEKWE ERP - LOCATION WEIGHT IMPORT GUIDE'],
                        [''],
                        ['Selected Location', $location->display_name],
                        ['Selected Breed', $breed->breed_name],
                        ['Best Practice', 'Download a fresh template before every weighing session.'],
                        ['Normal Upload', 'Creates new weighing records for accurate growth tracking.'],
                        ['Correction Upload', 'Use only when a previous uploaded weight was incorrect.'],
                        ['Location Validation', 'Each animal must still be assigned to the chosen location during import.'],
                        ['Breed Validation', 'Each animal must still match the chosen breed during import.'],
                        ['Duplicate Warning', 'Same animal and same recorded date/time is considered duplicate.'],
                    ], null, 'A1');

                    $instructions->getStyle('A1:B1')->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 14,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '008F00'],
                        ],
                    ]);

                    foreach (range('A', 'B') as $column) {
                        $instructions->getColumnDimension($column)->setAutoSize(true);
                    }

                    $spreadsheet->setActiveSheetIndex(0);

                    $filename = 'animal-weight-template-'
                        . str($location->display_name . '-' . $breed->breed_name)->slug()
                        . '-'
                        . now('Africa/Nairobi')->format('Ymd_His')
                        . '.xlsx';

                    return response()->streamDownload(function () use ($spreadsheet) {
                        $writer = new Xlsx($spreadsheet);
                        $writer->save('php://output');
                    }, $filename, [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
                }),
            Actions\Action::make('importWeights')
                ->label('Upload Filled Template')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('warning')
                ->visible(fn(): bool => auth()->user()?->can('create weight records') ?? false)
                ->modalHeading('Livestock Bulk Weight Import')
                ->modalSubmitActionLabel('Import Weights')
                ->modalWidth(MaxWidth::ThreeExtraLarge)
                ->stickyModalFooter()
                ->form([
                    Forms\Components\Placeholder::make('upload_help')
                        ->label('Animal Weight Excel Upload Guide')
                        ->content(new HtmlString('
                            <div class="space-y-4 text-sm leading-7">
                                <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-800 dark:bg-danger-950/30">
                                    <div class="font-semibold text-danger-700 dark:text-danger-300">
                                        Duplicate Handling Options
                                    </div>

                                    <div class="mt-3 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-100 dark:bg-gray-800">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold">Mode</th>
                                                    <th class="px-3 py-2 text-left font-semibold">Purpose</th>
                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                <tr>
                                                    <td class="px-3 py-3 font-medium text-warning-700 dark:text-warning-400">
                                                        Ignore duplicate
                                                    </td>
                                                    <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
                                                        Skips records where the animal already has a weight at the same date and time.
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="px-3 py-3 font-medium text-danger-700 dark:text-danger-400">
                                                        Correct existing record
                                                    </td>
                                                    <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
                                                        Use only when a previously uploaded weight was incorrect.
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="px-3 py-3 font-medium text-success-700 dark:text-success-400">
                                                        Create new weighing record
                                                    </td>
                                                    <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
                                                        Recommended for routine weighing and long-term growth tracking.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-4 rounded-lg border border-danger-300 bg-danger-100 p-3 text-danger-800 dark:border-danger-700 dark:bg-danger-950/40 dark:text-danger-300">
                                        <strong>Important:</strong>
                                        Correction mode updates an earlier record and may affect historical trend analysis.
                                    </div>
                                </div>
                            </div>
                        ')),
                    Forms\Components\Radio::make('duplicate_mode')
                        ->label('Duplicate Handling')
                        ->helperText('For routine weighing, choose “Create new weighing record”.')
                        ->options([
                            'ignore' => 'Ignore duplicate',
                            'correct' => 'Correct existing record',
                            'new' => 'Create new weighing record',
                        ])
                        ->default('new')
                        ->required(),
                    Forms\Components\FileUpload::make('excel_file')
                        ->label('Filled Excel File')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->storeFiles(false)
                        ->required()
                        ->helperText('Upload an Excel template generated by this ERP.'),
                ])
                ->action(function (array $data): void {
                    /** @var TemporaryUploadedFile $file */
                    $file = $data['excel_file'];

                    $spreadsheet = IOFactory::load($file->getRealPath());

                    $sheet = $spreadsheet->getSheetByName('Weight Upload')
                        ?? $spreadsheet->getActiveSheet();

                    $metaVersion = trim((string) ($sheet->getCell('B3')->getValue() ?? ''));
                    $metaLocationId = trim((string) ($sheet->getCell('B4')->getValue() ?? ''));
                    $metaLocationName = trim((string) ($sheet->getCell('B5')->getValue() ?? ''));
                    $metaBreedId = trim((string) ($sheet->getCell('B6')->getValue() ?? ''));
                    $metaBreedName = trim((string) ($sheet->getCell('B7')->getValue() ?? ''));

                    if ($metaVersion !== self::TEMPLATE_VERSION) {
                        Notification::make()
                            ->title('Invalid template version')
                            ->body('Download a fresh location-based Excel template from the ERP.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    if (!is_numeric($metaLocationId)) {
                        Notification::make()
                            ->title('Invalid location information')
                            ->body('The template location ID is missing or invalid.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $location = Location::query()
                        ->whereKey((int) $metaLocationId)
                        ->where('is_active', true)
                        ->first();

                    if (!$location) {
                        Notification::make()
                            ->title('Location not found')
                            ->body('The location used to generate this template is no longer active.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    if (trim($metaLocationName) !== trim($location->display_name)) {
                        Notification::make()
                            ->title('Location name mismatch')
                            ->body("Expected {$location->display_name}, but the template location does not match.")
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    if (!is_numeric($metaBreedId)) {
                        Notification::make()
                            ->title('Invalid breed information')
                            ->body('The template breed ID is missing or invalid.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $breed = Breed::query()
                        ->whereKey((int) $metaBreedId)
                        ->where('is_active', true)
                        ->first();

                    if (!$breed) {
                        Notification::make()
                            ->title('Breed not found')
                            ->body('The breed in this Excel template no longer exists or is inactive.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    if (trim($metaBreedName) !== trim($breed->breed_name)) {
                        Notification::make()
                            ->title('Breed name mismatch')
                            ->body("Expected {$breed->breed_name}, but the template breed does not match.")
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $rows = $sheet->toArray(null, true, true, true);

                    $headerRow = $rows[self::HEADER_ROW] ?? null;

                    if (!$headerRow) {
                        Notification::make()
                            ->title('Invalid Excel template')
                            ->body('The animal weight table header row is missing.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $headers = [];

                    foreach ($headerRow as $column => $value) {
                        $headers[$column] = strtolower(
                            trim(
                                str_replace(' ', '_', (string) $value)
                            )
                        );
                    }

                    foreach ([
                        'tag_number',
                        'weight_kg',
                        'recorded_at',
                    ] as $requiredHeader) {
                        if (!in_array($requiredHeader, $headers, true)) {
                            Notification::make()
                                ->title('Invalid Excel template')
                                ->body("Missing required column: {$requiredHeader}")
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }
                    }

                    $imported = 0;
                    $updated = 0;
                    $skipped = 0;
                    $errors = [];

                    DB::beginTransaction();

                    try {
                        $highestRow = $sheet->getHighestRow();

                        for (
                            $excelRowNumber = self::FIRST_DATA_ROW;
                            $excelRowNumber <= $highestRow;
                            $excelRowNumber++
                        ) {
                            $row = [];

                            foreach ($headers as $column => $headerName) {
                                $row[$headerName] = $sheet
                                    ->getCell($column . $excelRowNumber)
                                    ->getValue();
                            }

                            $tagNumber = trim((string) ($row['tag_number'] ?? ''));
                            $weightKg = trim((string) ($row['weight_kg'] ?? ''));
                            $remarks = trim((string) ($row['remarks'] ?? ''));
                            $recordedAtRaw = $row['recorded_at'] ?? null;

                            if ($tagNumber === '' && $weightKg === '') {
                                continue;
                            }

                            if ($weightKg === '') {
                                $skipped++;

                                continue;
                            }

                            if ($tagNumber === '') {
                                $skipped++;
                                $errors[] = "Row {$excelRowNumber}: Missing tag number.";

                                continue;
                            }

                            if (!is_numeric($weightKg) || (float) $weightKg <= 2) {
                                $skipped++;
                                $errors[] = "Row {$excelRowNumber}: Weight must be greater than 2 KG.";

                                continue;
                            }

                            try {
                                $recordedAtDate = is_numeric($recordedAtRaw)
                                    ? Carbon::instance(
                                        ExcelDate::excelToDateTimeObject((float) $recordedAtRaw)
                                    )
                                    : Carbon::parse((string) $recordedAtRaw);
                            } catch (\Throwable) {
                                $skipped++;
                                $errors[] = "Row {$excelRowNumber}: Invalid recorded date and time.";

                                continue;
                            }

                            $animal = Animal::query()
                                ->where('current_location_id', $location->id)
                                ->where('breed_id', $breed->id)
                                ->where('tag_number', $tagNumber)
                                ->where('status', 'Active')
                                ->where('is_archived', false)
                                ->first();

                            if (!$animal) {
                                $skipped++;

                                $errors[] = "Row {$excelRowNumber}: Active animal {$tagNumber} was not found at {$location->display_name} under {$breed->breed_name}.";

                                continue;
                            }

                            $existing = AnimalWeight::query()
                                ->where('animal_id', $animal->id)
                                ->where('recorded_at', $recordedAtDate->format('Y-m-d H:i:s'))
                                ->whereNull('deleted_at')
                                ->first();

                            if ($existing && $data['duplicate_mode'] === 'ignore') {
                                $skipped++;

                                continue;
                            }

                            if ($existing && $data['duplicate_mode'] === 'correct') {
                                $existing->update([
                                    'weight_kg' => (float) $weightKg,
                                    'recorded_by' => auth()->id(),
                                    'remarks' => $remarks ?: null,
                                ]);

                                $updated++;

                                continue;
                            }

                            if ($existing && $data['duplicate_mode'] === 'new') {
                                $recordedAtDate = $recordedAtDate
                                    ->copy()
                                    ->addSeconds(
                                        AnimalWeight::query()
                                            ->where('animal_id', $animal->id)
                                            ->whereDate('recorded_at', $recordedAtDate->toDateString())
                                            ->whereNull('deleted_at')
                                            ->count() + 1
                                    );
                            }

                            AnimalWeight::create([
                                'animal_id' => $animal->id,
                                'weight_kg' => (float) $weightKg,
                                'recorded_at' => $recordedAtDate,
                                'recorded_by' => auth()->id(),
                                'remarks' => $remarks ?: null,
                            ]);

                            $imported++;
                        }

                        DB::commit();

                        Notification::make()
                            ->title('Weight import completed')
                            ->body("Imported: {$imported}. Corrected: {$updated}. Skipped: {$skipped}.")
                            ->success()
                            ->send();

                        if ($errors !== []) {
                            Notification::make()
                                ->title('Some rows were skipped')
                                ->body(implode("\n", array_slice($errors, 0, 10)))
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    } catch (\Throwable $exception) {
                        DB::rollBack();

                        report($exception);

                        Notification::make()
                            ->title('Excel import failed')
                            ->body('The import could not be completed. Check the Excel template and try again.')
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
            Actions\CreateAction::make()
                ->label('Record Animal Weight')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\AnimalWeightResource\Widgets\AnimalWeightStats::class,
        ];
    }
}
