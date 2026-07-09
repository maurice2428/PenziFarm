<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Breed;
use App\Models\Location;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use RuntimeException;
use Throwable;

class AnimalImportService
{
    private const TEMPLATE_ROWS = 1000;

    private const HEADERS = [
        'date_of_birth',
        'date_of_birth_is_estimated',
        'breed',
        'sex',
        'source',
        'purpose',
        'status',
        'is_breeder',
        'sale_ready',
        'location',
        'sire_tag',
        'dam_tag',
        'is_foundation_animal',
        'purity_status',
        'purity_override_percent',
        'purity_verified_at',
        'purity_notes',
        'valuation_price',
        'bought_on',
        'bought_from',
        'seller_phone',
        'seller_email',
        'seller_address',
        'purchase_price',
        'purchase_notes',
        'date_died',
        'cause_of_death',
        'death_comments',
        'date_culled',
        'culling_reason',
        'culling_comments',
        'notes',
    ];

    public function downloadTemplate()
    {
        /*
         * These come directly from current Penzi records whenever
         * a new template is downloaded.
         */
        $breeds = Breed::query()
            ->where('is_active', true)
            ->orderBy('parent_category')
            ->orderBy('breed_name')
            ->get();

        $locations = Location::query()
            ->active()
            ->defaultFirst()
            ->get();

        if ($breeds->isEmpty()) {
            throw new RuntimeException(
                'Create at least one active breed before downloading the import template.'
            );
        }

        if ($locations->isEmpty()) {
            throw new RuntimeException(
                'Create at least one active location before downloading the import template.'
            );
        }

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Animal Import');

        $lastColumn = $this->columnForHeader('notes');

        $sheet->fromArray([self::HEADERS], null, 'A1');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->getRowDimension(1)->setRowHeight(25);

        $sheet->getStyle("A1:{$lastColumn}1")
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');

        $sheet->getStyle("A1:{$lastColumn}1")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF1A5C38');

        $sheet->getStyle("A1:{$lastColumn}1")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $defaultLocation = $locations
            ->firstWhere('is_default', true)
            ?? $locations->first();

        $sample = array_fill(0, count(self::HEADERS), '');

        $sample[$this->headerIndex('date_of_birth')] = '2025-01-15';
        $sample[$this->headerIndex('date_of_birth_is_estimated')] = 'no';
        $sample[$this->headerIndex('breed')] = $this->breedLabel($breeds->first());
        $sample[$this->headerIndex('sex')] = 'Female';
        $sample[$this->headerIndex('source')] = 'Born on farm';
        $sample[$this->headerIndex('purpose')] = 'Breeding';
        $sample[$this->headerIndex('status')] = 'Active';
        $sample[$this->headerIndex('is_breeder')] = 'yes';
        $sample[$this->headerIndex('sale_ready')] = 'no';
        $sample[$this->headerIndex('location')] = $defaultLocation->name;
        $sample[$this->headerIndex('is_foundation_animal')] = 'no';
        $sample[$this->headerIndex('purity_status')] = 'pending';
        $sample[$this->headerIndex('valuation_price')] = '0';
        $sample[$this->headerIndex('notes')] = 'Example record — replace or delete this row.';

        $sheet->fromArray([$sample], null, 'A2');

        $this->setColumnWidths($sheet);

        /*
         * Hidden worksheet containing dynamic lookup values.
         */
        $lists = $spreadsheet->createSheet();
        $lists->setTitle('Lists');

        $breedLastRow = $this->writeLookupList(
            $lists,
            'A',
            'Active Breeds',
            $breeds
                ->map(fn (Breed $breed): string => $this->breedLabel($breed))
                ->values()
                ->all()
        );

        $locationLastRow = $this->writeLookupList(
            $lists,
            'B',
            'Active Locations',
            $locations
                ->pluck('name')
                ->values()
                ->all()
        );

        $sexLastRow = $this->writeLookupList(
            $lists,
            'C',
            'Sex',
            ['Male', 'Female']
        );

        $sourceLastRow = $this->writeLookupList(
            $lists,
            'D',
            'Source',
            ['Born on farm', 'Purchased']
        );

        $purposeLastRow = $this->writeLookupList(
            $lists,
            'E',
            'Purpose',
            ['Breeding', 'Sale', 'Dairy', 'Production']
        );

        $statusLastRow = $this->writeLookupList(
            $lists,
            'F',
            'Status',
            ['Active', 'Sold', 'Dead', 'Culled']
        );

        $yesNoLastRow = $this->writeLookupList(
            $lists,
            'G',
            'Yes No',
            ['yes', 'no']
        );

        $purityStatusLastRow = $this->writeLookupList(
            $lists,
            'H',
            'Purity Status',
            ['pending', 'dna_verified', 'manual_verified']
        );

        /*
         * Required dropdowns.
         */
        $this->applyDropdown(
            $sheet,
            $this->columnForHeader('breed'),
            $this->listFormula('A', $breedLastRow),
            'Select an active breed from the dropdown.',
            false
        );

        $this->applyDropdown(
            $sheet,
            $this->columnForHeader('sex'),
            $this->listFormula('C', $sexLastRow),
            'Select Male or Female.',
            false
        );

        $this->applyDropdown(
            $sheet,
            $this->columnForHeader('location'),
            $this->listFormula('B', $locationLastRow),
            'Select an active animal location from the dropdown.',
            false
        );

        /*
         * Optional dropdowns. Blank values use the importer defaults.
         */
        $this->applyDropdown(
            $sheet,
            $this->columnForHeader('source'),
            $this->listFormula('D', $sourceLastRow),
            'Select the source.',
            true
        );

        $this->applyDropdown(
            $sheet,
            $this->columnForHeader('purpose'),
            $this->listFormula('E', $purposeLastRow),
            'Select the purpose.',
            true
        );

        $this->applyDropdown(
            $sheet,
            $this->columnForHeader('status'),
            $this->listFormula('F', $statusLastRow),
            'Select the status.',
            true
        );

        $this->applyDropdown(
            $sheet,
            $this->columnForHeader('purity_status'),
            $this->listFormula('H', $purityStatusLastRow),
            'Select pending, dna_verified or manual_verified.',
            true
        );

        foreach ([
            'date_of_birth_is_estimated',
            'is_breeder',
            'sale_ready',
            'is_foundation_animal',
        ] as $header) {
            $this->applyDropdown(
                $sheet,
                $this->columnForHeader($header),
                $this->listFormula('G', $yesNoLastRow),
                'Select yes or no.',
                true
            );
        }

        $lists->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new XlsxWriter($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            'penzi-animal-import-template.xlsx',
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    public function import(string $filePath, ?int $userId): array
    {
        [$headers, $rows] = $this->readRows($filePath);

        $requiredHeaders = [
            'date_of_birth',
            'breed',
            'sex',
            'location',
        ];

        $missingHeaders = array_diff($requiredHeaders, $headers);

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'animal_file' => 'Missing required columns: ' . implode(', ', $missingHeaders),
            ]);
        }

        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            $row = array_slice(
                array_pad($row, count($headers), ''),
                0,
                count($headers)
            );

            $data = array_combine($headers, $row);

            if (! $this->rowHasData($data)) {
                continue;
            }

            try {
                DB::transaction(function () use ($data, $userId): void {
                    $this->createAnimalFromRow($data, $userId);
                }, 5);

                $created++;
            } catch (Throwable $exception) {
                $failed++;

                $errors[] = 'Row ' . $rowNumber . ': ' . Str::limit(
                    preg_replace('/\s+/', ' ', trim($exception->getMessage())),
                    230
                );
            }
        }

        return [
            'created' => $created,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    private function createAnimalFromRow(array $row, ?int $userId): void
    {
        $dateOfBirth = $this->requiredDate(
            $row['date_of_birth'] ?? null,
            'date_of_birth'
        );

        $breed = $this->resolveBreed($row['breed'] ?? null);

        $location = $this->resolveLocation($row['location'] ?? null);

        $sex = $this->normaliseOption(
            $row['sex'] ?? null,
            ['Male', 'Female'],
            'sex'
        );

        $source = $this->normaliseOption(
            $row['source'] ?? null,
            ['Born on farm', 'Purchased'],
            'source',
            'Born on farm'
        );

        $purpose = $this->normaliseOption(
            $row['purpose'] ?? null,
            ['Breeding', 'Sale', 'Dairy', 'Production'],
            'purpose',
            'Sale'
        );

        $status = $this->normaliseOption(
            $row['status'] ?? null,
            ['Active', 'Sold', 'Dead', 'Culled'],
            'status',
            'Active'
        );

        $isBreeder = $this->toBoolean(
            $row['is_breeder'] ?? null,
            false,
            'is_breeder'
        );

        $saleReady = $this->toBoolean(
            $row['sale_ready'] ?? null,
            false,
            'sale_ready'
        );

        if ($purpose === 'Breeding') {
            $isBreeder = true;
            $saleReady = false;
        }

        if ($isBreeder) {
            $saleReady = false;
        }

        if ($saleReady) {
            $isBreeder = false;
        }

        /*
         * Breed purity is always measured against the selected animal breed.
         * Arithmetic is automatic after the record and recorded parents exist.
         */
        $isFoundationAnimal = $this->toBoolean(
            $row['is_foundation_animal'] ?? null,
            false,
            'is_foundation_animal'
        );

        $purityStatus = $this->normaliseOption(
            $row['purity_status'] ?? null,
            ['pending', 'dna_verified', 'manual_verified'],
            'purity_status',
            'pending'
        );

        $purityOverridePercent = $this->nullablePercentage(
            $row['purity_override_percent'] ?? null,
            'purity_override_percent'
        );

        $purityVerifiedAt = $this->nullableDate(
            $row['purity_verified_at'] ?? null,
            'purity_verified_at'
        );

        $purityNotes = $this->nullableText(
            $row['purity_notes'] ?? null
        );

        if ($isFoundationAnimal) {
            $purityStatus = 'foundation';
            $purityOverridePercent = null;
            $purityVerifiedAt ??= now()->toDateString();
        } elseif (in_array($purityStatus, ['dna_verified', 'manual_verified'], true)) {
            if ($purityOverridePercent === null) {
                throw new RuntimeException(
                    'purity_override_percent is required for dna_verified or manual_verified records.'
                );
            }
        } else {
            $purityStatus = 'pending';
            $purityOverridePercent = null;
            $purityVerifiedAt = null;
        }

        $purityData = [
            'purity_breed_id' => $breed->id,
            'purity_status' => $purityStatus,
            'is_foundation_animal' => $isFoundationAnimal,
            'purity_override_percent' => $purityOverridePercent,
            'purity_verified_at' => $purityVerifiedAt,
            'purity_notes' => $purityNotes,
        ];

        /*
         * Tag is never typed into the Excel sheet.
         * It is generated from selected Breed + Date of Birth.
         */
        $tagData = app(AnimalTagGeneratorService::class)
            ->generateForBreedAndBirthDate($breed, $dateOfBirth);

        $dateDied = null;
        $causeOfDeath = null;
        $deathComments = null;

        if ($status === 'Dead') {
            $dateDied = $this->requiredDate(
                $row['date_died'] ?? null,
                'date_died'
            );

            $causeOfDeath = $this->requiredText(
                $row['cause_of_death'] ?? null,
                'cause_of_death'
            );

            $deathComments = $this->nullableText(
                $row['death_comments'] ?? null
            );
        }

        $dateCulled = null;
        $cullingReason = null;
        $cullingComments = null;

        if ($status === 'Culled') {
            $dateCulled = $this->requiredDate(
                $row['date_culled'] ?? null,
                'date_culled'
            );

            $cullingReason = $this->requiredText(
                $row['culling_reason'] ?? null,
                'culling_reason'
            );

            $cullingComments = $this->nullableText(
                $row['culling_comments'] ?? null
            );
        }

        $purchaseData = [
            'bought_on' => null,
            'bought_from' => null,
            'seller_phone' => null,
            'seller_email' => null,
            'seller_address' => null,
            'purchase_price' => null,
            'purchase_notes' => null,
        ];

        if ($source === 'Purchased') {
            $purchaseData = [
                'bought_on' => $this->nullableDate($row['bought_on'] ?? null, 'bought_on'),
                'bought_from' => $this->nullableText($row['bought_from'] ?? null),
                'seller_phone' => $this->nullableText($row['seller_phone'] ?? null),
                'seller_email' => $this->nullableText($row['seller_email'] ?? null),
                'seller_address' => $this->nullableText($row['seller_address'] ?? null),
                'purchase_price' => $this->nullableMoney($row['purchase_price'] ?? null, 'purchase_price'),
                'purchase_notes' => $this->nullableText($row['purchase_notes'] ?? null),
            ];
        }

        $animal = new Animal();

        $animal->forceFill([
            'tag_number' => $tagData['tag_number'],
            'tag_sequence' => $tagData['tag_sequence'],
            'species' => $breed->parent_category,
            'breed_id' => $breed->id,
            'sex' => $sex,
            'date_of_birth' => $dateOfBirth,
            'date_of_birth_is_estimated' => $this->toBoolean(
                $row['date_of_birth_is_estimated'] ?? null,
                false,
                'date_of_birth_is_estimated'
            ),
            'source' => $source,
            'purpose' => $purpose,
            'status' => $status,
            'is_breeder' => $isBreeder,
            'sale_ready' => $saleReady,
            'valuation_price' => $this->nullableMoney(
                $row['valuation_price'] ?? null,
                'valuation_price'
            ),
            'current_location_id' => $location->id,
            'sire_id' => $this->resolveParentId(
                $row['sire_tag'] ?? null,
                'Male',
                $breed->parent_category,
                $dateOfBirth,
                'sire_tag'
            ),
            'dam_id' => $this->resolveParentId(
                $row['dam_tag'] ?? null,
                'Female',
                $breed->parent_category,
                $dateOfBirth,
                'dam_tag'
            ),
            'date_died' => $dateDied,
            'cause_of_death' => $causeOfDeath,
            'death_comments' => $deathComments,
            'date_culled' => $dateCulled,
            'culling_reason' => $cullingReason,
            'culling_comments' => $cullingComments,
            'notes' => $this->nullableText($row['notes'] ?? null),
            'is_archived' => false,
            'created_by' => $userId,
            'updated_by' => $userId,
            ...$purchaseData,
            ...$purityData,
        ]);

        $animal->save();

        app(BreedPurityService::class)->recalculate($animal);
    }

    private function readRows(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        $rows = $spreadsheet
            ->getActiveSheet()
            ->toArray(null, true, true, false);

        $spreadsheet->disconnectWorksheets();

        if ($rows === []) {
            throw ValidationException::withMessages([
                'animal_file' => 'The uploaded file is empty.',
            ]);
        }

        $headers = array_shift($rows);

        $headers = array_map(
            fn ($header): string => $this->normaliseHeader((string) $header),
            $headers
        );

        if (count($headers) !== count(array_unique($headers))) {
            throw ValidationException::withMessages([
                'animal_file' => 'The import file has duplicate headers.',
            ]);
        }

        $indexedRows = [];

        foreach ($rows as $index => $row) {
            $indexedRows[$index + 2] = $row;
        }

        return [$headers, $indexedRows];
    }

    private function resolveBreed(mixed $value): Breed
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new RuntimeException('breed is required.');
        }

        $breeds = Breed::query()
            ->where('is_active', true)
            ->orderBy('parent_category')
            ->orderBy('breed_name')
            ->get();

        $matchedLabel = $breeds->first(
            fn (Breed $breed): bool =>
                strcasecmp($this->breedLabel($breed), $value) === 0
        );

        if ($matchedLabel) {
            return $matchedLabel;
        }

        $matchedByName = $breeds
            ->filter(
                fn (Breed $breed): bool =>
                    strcasecmp($breed->breed_name, $value) === 0
            )
            ->values();

        if ($matchedByName->count() === 1) {
            return $matchedByName->first();
        }

        throw new RuntimeException(
            "Breed '{$value}' was not found as one active breed. Use the template dropdown."
        );
    }

    private function resolveLocation(mixed $value): Location
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new RuntimeException('location is required.');
        }

        $locations = Location::query()
            ->active()
            ->defaultFirst()
            ->get();

        $location = $locations->first(
            fn (Location $item): bool =>
                strcasecmp($item->name, $value) === 0 ||
                strcasecmp($item->display_name, $value) === 0
        );

        if (! $location) {
            throw new RuntimeException(
                "Location '{$value}' was not found as active. Use the template dropdown."
            );
        }

        return $location;
    }

    private function resolveParentId(
        mixed $tagNumber,
        string $sex,
        string $species,
        string $childDateOfBirth,
        string $field
    ): ?int {
        $tagNumber = strtoupper(trim((string) $tagNumber));

        if ($tagNumber === '') {
            return null;
        }

        $parent = Animal::query()
            ->where('tag_number', $tagNumber)
            ->where('species', $species)
            ->where('sex', $sex)
            ->where('is_archived', false)
            ->first();

        if (! $parent) {
            throw new RuntimeException(
                "{$field} '{$tagNumber}' was not found as a non-archived {$sex} {$species} animal."
            );
        }

        if (
            $parent->date_of_birth
            && Carbon::parse($parent->date_of_birth)
                ->greaterThan(Carbon::parse($childDateOfBirth)->subYear())
        ) {
            throw new RuntimeException(
                "{$field} '{$tagNumber}' must be at least one year older than the imported animal."
            );
        }

        return $parent->id;
    }

    private function requiredDate(mixed $value, string $field): string
    {
        $date = $this->nullableDate($value, $field);

        if (! $date) {
            throw new RuntimeException("{$field} is required.");
        }

        if ($field === 'date_of_birth' && Carbon::parse($date)->isFuture()) {
            throw new RuntimeException('date_of_birth cannot be in the future.');
        }

        return $date;
    }

    private function nullableDate(mixed $value, string $field): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 20000) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $value)
                )->toDateString();
            } catch (Throwable) {
                // Continue with standard date parsing.
            }
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);

            if ($date->format('Y-m-d') !== $value) {
                throw new RuntimeException();
            }

            return $date->toDateString();
        } catch (Throwable) {
            throw new RuntimeException(
                "{$field} must use YYYY-MM-DD format."
            );
        }
    }

    private function normaliseOption(
        mixed $value,
        array $allowed,
        string $field,
        ?string $default = null
    ): string {
        $value = trim((string) $value);

        if ($value === '' && $default !== null) {
            return $default;
        }

        foreach ($allowed as $option) {
            if (strcasecmp($value, $option) === 0) {
                return $option;
            }
        }

        throw new RuntimeException(
            "{$field} must be one of: " . implode(', ', $allowed)
        );
    }

    private function toBoolean(
        mixed $value,
        bool $default,
        string $field
    ): bool {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return $default;
        }

        if (in_array($value, ['yes', 'y', 'true', '1'], true)) {
            return true;
        }

        if (in_array($value, ['no', 'n', 'false', '0'], true)) {
            return false;
        }

        throw new RuntimeException(
            "{$field} must be yes/no, true/false, or 1/0."
        );
    }

    private function nullableMoney(mixed $value, string $field): ?float
    {
        $value = str_replace(',', '', trim((string) $value));

        if ($value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new RuntimeException("{$field} must be a valid number.");
        }

        return (float) $value;
    }

    private function nullablePercentage(mixed $value, string $field): ?float
    {
        $percentage = $this->nullableMoney($value, $field);

        if ($percentage === null) {
            return null;
        }

        if ($percentage < 0 || $percentage > 100) {
            throw new RuntimeException("{$field} must be between 0 and 100.");
        }

        return round($percentage, 4);
    }

    private function requiredText(mixed $value, string $field): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new RuntimeException("{$field} is required.");
        }

        return $value;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function rowHasData(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function normaliseHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = strtolower(trim($header));
        $header = preg_replace('/[\s\-\/]+/', '_', $header);
        $header = preg_replace('/[^a-z0-9_]/', '', $header);
        $header = preg_replace('/_+/', '_', $header);

        return trim($header, '_');
    }

    private function breedLabel(Breed $breed): string
    {
        return trim($breed->breed_name)
            . ' (' . trim($breed->parent_category) . ')';
    }

    private function writeLookupList(
        Worksheet $sheet,
        string $column,
        string $heading,
        array $values
    ): int {
        $sheet->setCellValue("{$column}1", $heading);

        foreach (array_values($values) as $index => $value) {
            $sheet->setCellValue("{$column}" . ($index + 2), $value);
        }

        return count($values) + 1;
    }

    private function listFormula(string $column, int $lastRow): string
    {
        return "'Lists'!\${$column}\$2:\${$column}\${$lastRow}";
    }

    private function applyDropdown(
        Worksheet $sheet,
        string $column,
        string $formula,
        string $message,
        bool $allowBlank
    ): void {
        $validation = $sheet
            ->getCell("{$column}2")
            ->getDataValidation();

        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank($allowBlank);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Invalid selection');
        $validation->setError($message);
        $validation->setPromptTitle('Select from the dropdown');
        $validation->setPrompt($message);
        $validation->setFormula1($formula);

        $validation->setSqref(
            "{$column}2:{$column}" . (self::TEMPLATE_ROWS + 1)
        );
    }

    private function columnForHeader(string $header): string
    {
        return Coordinate::stringFromColumnIndex(
            $this->headerIndex($header) + 1
        );
    }

    private function headerIndex(string $header): int
    {
        $index = array_search($header, self::HEADERS, true);

        if ($index === false) {
            throw new RuntimeException(
                "Unknown import header: {$header}"
            );
        }

        return $index;
    }

    private function setColumnWidths(Worksheet $sheet): void
    {
        $widths = [
            'date_of_birth' => 16,
            'date_of_birth_is_estimated' => 22,
            'breed' => 26,
            'sex' => 14,
            'source' => 18,
            'purpose' => 16,
            'status' => 14,
            'is_breeder' => 14,
            'sale_ready' => 14,
            'location' => 28,
            'sire_tag' => 18,
            'dam_tag' => 18,
            'is_foundation_animal' => 22,
            'purity_status' => 20,
            'purity_override_percent' => 24,
            'purity_verified_at' => 20,
            'purity_notes' => 32,
            'valuation_price' => 16,
            'bought_on' => 16,
            'bought_from' => 24,
            'seller_phone' => 18,
            'seller_email' => 30,
            'seller_address' => 32,
            'purchase_price' => 16,
            'purchase_notes' => 32,
            'date_died' => 16,
            'cause_of_death' => 28,
            'death_comments' => 32,
            'date_culled' => 16,
            'culling_reason' => 28,
            'culling_comments' => 32,
            'notes' => 36,
        ];

        foreach ($widths as $header => $width) {
            $sheet
                ->getColumnDimension($this->columnForHeader($header))
                ->setWidth($width);
        }
    }
}
