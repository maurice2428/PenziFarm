<?php

namespace App\Filament\Support;

use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use App\Services\AnimalTagGeneratorService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Throwable;

class AnimalTagTallyCorrectionAction
{
    public static function make(): Action
    {
        return Action::make('correctAnimalTagTally')
            ->label('Correct Tag Tally')
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('warning')
            ->button()
            ->visible(
                fn (?Animal $record): bool =>
                    $record !== null && self::userIsAdministrator()
            )
            ->fillForm(function (?Animal $record): array {
                return [
                    'tag_sequence' => $record?->tag_sequence,
                    'reason' => null,
                    'confirmation' => false,
                ];
            })
            ->modalHeading(
                fn (?Animal $record): string =>
                    'Correct Tag Tally — '
                    . ($record?->tag_number ?? 'Animal')
            )
            ->modalDescription(
                'Only the tally number can be corrected here. The breed letter '
                . 'and birth year remain locked and are rebuilt automatically '
                . 'from the animal record.'
            )
            ->modalSubmitActionLabel('Apply Tally Correction')
            ->modalCancelActionLabel('Cancel')
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->stickyModalFooter()
            ->form([
                Forms\Components\Placeholder::make('current_identity')
                    ->label('Current Locked Identity')
                    ->content(function (?Animal $record): HtmlString {
                        if (! $record) {
                            return new HtmlString(
                                '<div style="padding:12px;color:#991b1b;">'
                                . 'Animal record unavailable.'
                                . '</div>'
                            );
                        }

                        $record->loadMissing('breed');

                        $breedName = e(
                            $record->breed?->breed_name ?? 'Unknown breed'
                        );

                        $birthYear = $record->date_of_birth
                            ? Carbon::parse($record->date_of_birth)->format('Y')
                            : '-';

                        $sequence = str_pad(
                            (string) $record->tag_sequence,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );

                        return new HtmlString(
                            '<div style="border:1px solid #bfdbfe;border-left:5px solid #2563eb;background:#eff6ff;padding:16px;border-radius:8px;">'
                            . '<div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">'
                            . '<div>'
                            . '<div style="font-size:10px;font-weight:900;color:#1d4ed8;text-transform:uppercase;">Current tag</div>'
                            . '<div style="margin-top:7px;font-family:monospace;font-size:25px;font-weight:950;color:#1e3a8a;">'
                            . e($record->tag_number)
                            . '</div>'
                            . '</div>'
                            . '<div style="padding:5px 9px;background:#dbeafe;border:1px solid #93c5fd;color:#1d4ed8;font-size:10px;font-weight:900;">LOCKED</div>'
                            . '</div>'
                            . '<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:14px;">'
                            . '<div><div style="font-size:9px;color:#64748b;font-weight:900;">BREED</div><div style="margin-top:3px;font-weight:800;">'
                            . $breedName
                            . '</div></div>'
                            . '<div><div style="font-size:9px;color:#64748b;font-weight:900;">BIRTH YEAR</div><div style="margin-top:3px;font-weight:800;">'
                            . e($birthYear)
                            . '</div></div>'
                            . '<div><div style="font-size:9px;color:#64748b;font-weight:900;">CURRENT TALLY</div><div style="margin-top:3px;font-weight:800;">#'
                            . e($sequence)
                            . '</div></div>'
                            . '</div>'
                            . '</div>'
                        );
                    })
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('tag_sequence')
                    ->label('Correct Tally Number')
                    ->helperText(
                        'You may move the tally backward or forward. Example: '
                        . 'enter 2 to change a suffix from 03 to 02, provided '
                        . 'the resulting full tag is unused.'
                    )
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(999999)
                    ->live(onBlur: true)
                    ->required(),

                Forms\Components\Placeholder::make('corrected_tag_preview')
                    ->label('New Tag Preview')
                    ->content(function (
                        Forms\Get $get,
                        ?Animal $record
                    ): HtmlString {
                        if (! $record) {
                            return self::errorBox(
                                'Animal record unavailable.'
                            );
                        }

                        $sequence = $get('tag_sequence');

                        if (blank($sequence)) {
                            return new HtmlString(
                                '<div style="border:1px dashed #d1d5db;background:#f8fafc;padding:14px;border-radius:8px;color:#64748b;">'
                                . 'Enter a tally number to preview the corrected tag.'
                                . '</div>'
                            );
                        }

                        $record->loadMissing('breed');

                        if (! $record->breed) {
                            return self::errorBox(
                                'This animal does not have a valid breed.'
                            );
                        }

                        if (blank($record->date_of_birth)) {
                            return self::errorBox(
                                'This animal does not have a date of birth.'
                            );
                        }

                        try {
                            $preview = app(
                                AnimalTagGeneratorService::class
                            )->previewSpecificTag(
                                breed: $record->breed,
                                birthDate: $record->date_of_birth,
                                sequence: (int) $sequence,
                                exceptAnimal: $record,
                            );

                            $isUnchanged =
                                strtoupper((string) $record->tag_number)
                                === strtoupper($preview['tag_number']);

                            $status = $isUnchanged
                                ? 'NO CHANGE'
                                : 'AVAILABLE';

                            $statusStyle = $isUnchanged
                                ? 'background:#f1f5f9;border:1px solid #cbd5e1;color:#475569;'
                                : 'background:#dcfce7;border:1px solid #86efac;color:#166534;';

                            $message = $isUnchanged
                                ? 'Enter a different tally number to make a correction.'
                                : 'The breed letter and birth year remain unchanged. This tag is currently available.';

                            return new HtmlString(
                                '<div style="border:1px solid #bbf7d0;border-left:5px solid #16a34a;background:#f0fdf4;padding:16px;border-radius:8px;">'
                                . '<div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">'
                                . '<div>'
                                . '<div style="font-size:10px;font-weight:900;color:#166534;text-transform:uppercase;">Corrected tag</div>'
                                . '<div style="margin-top:7px;font-family:monospace;font-size:25px;font-weight:950;color:#14532d;">'
                                . e($preview['tag_number'])
                                . '</div>'
                                . '</div>'
                                . '<div style="padding:5px 9px;font-size:10px;font-weight:900;'
                                . $statusStyle
                                . '">'
                                . e($status)
                                . '</div>'
                                . '</div>'
                                . '<div style="margin-top:10px;color:#166534;font-size:11px;">'
                                . e($message)
                                . '</div>'
                                . '</div>'
                            );
                        } catch (Throwable $exception) {
                            return self::errorBox(
                                $exception->getMessage()
                            );
                        }
                    })
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('reason')
                    ->label('Reason for Correction')
                    ->helperText(
                        'State why the original tally was wrong. This is saved '
                        . 'permanently in the audit history.'
                    )
                    ->rows(4)
                    ->minLength(10)
                    ->maxLength(1000)
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Checkbox::make('confirmation')
                    ->label(
                        'I confirm that I verified the physical animal tag '
                        . 'and the supporting records.'
                    )
                    ->accepted()
                    ->required()
                    ->columnSpanFull(),
            ])
            ->action(function (
                array $data,
                ?Animal $record,
                $livewire
            ): void {
                abort_unless(self::userIsAdministrator(), 403);

                if (! $record) {
                    throw ValidationException::withMessages([
                        'tag_sequence' => 'Animal record unavailable.',
                    ]);
                }

                $record->refresh()->loadMissing('breed');

                if (! $record->breed) {
                    throw ValidationException::withMessages([
                        'tag_sequence' =>
                            'The animal does not have a valid breed.',
                    ]);
                }

                if (blank($record->date_of_birth)) {
                    throw ValidationException::withMessages([
                        'tag_sequence' =>
                            'Date of birth is required to rebuild the tag.',
                    ]);
                }

                $animal = app(AnimalTagGeneratorService::class)
                    ->correctExistingAnimalTag(
                        animal: $record,
                        newBreed: $record->breed,
                        newBirthDate: $record->date_of_birth,
                        newSequence: (int) $data['tag_sequence'],
                        reason: (string) $data['reason'],
                        correctedBy: auth()->id(),
                    );

                Notification::make()
                    ->success()
                    ->title('Animal tag tally corrected')
                    ->body(
                        'The animal is now registered as '
                        . $animal->tag_number
                        . '. The old tag remains in the correction audit history.'
                    )
                    ->persistent()
                    ->send();

                $livewire->redirect(
                    AnimalResource::getUrl('edit', [
                        'record' => $animal,
                    ]),
                    navigate: true
                );
            });
    }

    private static function userIsAdministrator(): bool
    {
        return auth()->user()?->hasAnyRole([
            'Administrator',
            'Admin',
        ]) ?? false;
    }

    private static function errorBox(string $message): HtmlString
    {
        return new HtmlString(
            '<div style="border:1px solid #fecaca;border-left:5px solid #dc2626;background:#fef2f2;padding:14px;border-radius:8px;color:#991b1b;">'
            . '<strong>Tag unavailable.</strong><br>'
            . e($message)
            . '</div>'
        );
    }
}
