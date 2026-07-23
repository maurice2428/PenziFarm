<?php

namespace App\Filament\Support;

use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use App\Services\AnimalTagGeneratorService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
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
            ->modalHeading('Correct Tag Tally')
            ->modalDescription(
                'Correct only the numerical tally. The locked tag prefix and '
                . 'birth year are rebuilt automatically from the animal record.'
            )
            ->modalSubmitActionLabel('Apply Tally Correction')
            ->modalCancelActionLabel('Cancel')
            ->modalWidth(MaxWidth::ThreeExtraLarge)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->stickyModalFooter()
            ->extraModalWindowAttributes([
                'class' => 'penzi-tag-tally-modal',
            ])
            ->form([
                Forms\Components\Placeholder::make('current_identity')
                    ->hiddenLabel()
                    ->content(
                        fn (?Animal $record): HtmlString =>
                            self::currentIdentityPanel($record)
                    )
                    ->columnSpanFull(),

                Forms\Components\Grid::make([
                    'default' => 1,
                    'sm' => 3,
                ])
                    ->schema([
                        Forms\Components\Placeholder::make('locked_prefix')
                            ->label('Locked Tag Prefix')
                            ->content(function (?Animal $record): HtmlString {
                                if (! $record) {
                                    return self::compactError(
                                        'Animal record unavailable.'
                                    );
                                }

                                $parts = self::identityParts($record);

                                return self::lockedValueCard(
                                    value: $parts['prefix'],
                                    caption: 'Breed-based prefix',
                                );
                            }),

                        Forms\Components\Placeholder::make('locked_birth_year')
                            ->label('Locked Birth Year')
                            ->content(function (?Animal $record): HtmlString {
                                if (! $record || blank($record->date_of_birth)) {
                                    return self::compactError(
                                        'Birth year unavailable.'
                                    );
                                }

                                return self::lockedValueCard(
                                    value: Carbon::parse(
                                        $record->date_of_birth
                                    )->format('y'),
                                    caption: Carbon::parse(
                                        $record->date_of_birth
                                    )->format('Y'),
                                );
                            }),

                        Forms\Components\TextInput::make('tag_sequence')
                            ->label('New Tally Number')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->helperText(
                                'Enter only the numerical tally, for example 2.'
                            )
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(999999)
                            ->live(debounce: 350)
                            ->required()
                            ->extraInputAttributes([
                                'class' =>
                                    'text-center font-mono font-bold tracking-wider',
                                'inputmode' => 'numeric',
                            ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('corrected_tag_preview')
                    ->hiddenLabel()
                    ->content(function (
                        Forms\Get $get,
                        ?Animal $record
                    ): HtmlString {
                        return self::correctedTagPreview(
                            record: $record,
                            sequence: $get('tag_sequence'),
                        );
                    })
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('reason')
                    ->label('Reason for Correction')
                    ->placeholder(
                        'Example: Physical ear tag confirmed as tally 02; '
                        . 'the animal was previously entered as tally 03.'
                    )
                    ->helperText(
                        'This reason is stored permanently in the correction '
                        . 'audit history.'
                    )
                    ->rows(2)
                    ->autosize()
                    ->minLength(10)
                    ->maxLength(1000)
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Checkbox::make('confirmation')
                    ->label(
                        'I verified the physical animal tag and supporting '
                        . 'records before making this correction.'
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
                        reason: trim((string) $data['reason']),
                        correctedBy: auth()->id(),
                    );

                Notification::make()
                    ->success()
                    ->title('Animal tag tally corrected')
                    ->body(
                        'The animal is now registered as '
                        . $animal->tag_number
                        . '. The previous tag remains in the correction '
                        . 'audit history.'
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

    private static function currentIdentityPanel(
        ?Animal $record
    ): HtmlString {
        if (! $record) {
            return self::errorBox('Animal record unavailable.');
        }

        $record->loadMissing('breed');

        $parts = self::identityParts($record);

        $breedName = e(
            $record->breed?->breed_name ?? 'Unknown breed'
        );

        $tag = e(
            strtoupper((string) $record->tag_number)
        );

        return new HtmlString(
            self::modalStyles()
            . '<div class="ptt-identity-card">'
            . '  <div class="ptt-eyebrow">Current Registered Tag</div>'
            . '  <div class="ptt-current-tag">' . $tag . '</div>'
            . '  <div class="ptt-summary">'
            . '      <span>' . $breedName . '</span>'
            . '      <span class="ptt-dot">•</span>'
            . '      <span>Born ' . e($parts['birth_year_full']) . '</span>'
            . '      <span class="ptt-dot">•</span>'
            . '      <span>Current tally #' . e($parts['sequence']) . '</span>'
            . '  </div>'
            . '  <div class="ptt-lock-note">'
            . '      <span class="ptt-lock-icon"></span>'
            . '      Prefix and birth year remain locked'
            . '  </div>'
            . '</div>'
        );
    }

    private static function correctedTagPreview(
        ?Animal $record,
        mixed $sequence
    ): HtmlString {
        if (! $record) {
            return self::errorBox('Animal record unavailable.');
        }

        if (blank($sequence)) {
            return new HtmlString(
                '<div class="ptt-preview ptt-preview-neutral">'
                . '  <div>'
                . '      <div class="ptt-preview-label">Corrected Tag Preview</div>'
                . '      <div class="ptt-preview-help">'
                . '          Enter a tally number to generate the corrected tag.'
                . '      </div>'
                . '  </div>'
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
            $preview = app(AnimalTagGeneratorService::class)
                ->previewSpecificTag(
                    breed: $record->breed,
                    birthDate: $record->date_of_birth,
                    sequence: (int) $sequence,
                    exceptAnimal: $record,
                );

            $newTag = strtoupper(
                (string) ($preview['tag_number'] ?? '')
            );

            $currentTag = strtoupper(
                (string) $record->tag_number
            );

            $isUnchanged = $currentTag === $newTag;

            $wrapperClass = $isUnchanged
                ? 'ptt-preview ptt-preview-neutral'
                : 'ptt-preview ptt-preview-success';

            $badgeClass = $isUnchanged
                ? 'ptt-badge ptt-badge-neutral'
                : 'ptt-badge ptt-badge-success';

            $status = $isUnchanged
                ? 'NO CHANGE'
                : 'AVAILABLE';

            $message = $isUnchanged
                ? 'Enter a different tally number to make a correction.'
                : 'The corrected full tag is available and ready to apply.';

            return new HtmlString(
                '<div class="' . $wrapperClass . '">'
                . '  <div class="ptt-preview-main">'
                . '      <div class="ptt-preview-label">Corrected Tag Preview</div>'
                . '      <div class="ptt-tag-transition">'
                . '          <span class="ptt-old-tag">'
                .               e($currentTag)
                . '          </span>'
                . '          <span class="ptt-arrow">→</span>'
                . '          <span class="ptt-new-tag">'
                .               e($newTag)
                . '          </span>'
                . '      </div>'
                . '      <div class="ptt-preview-help">'
                .           e($message)
                . '      </div>'
                . '  </div>'
                . '  <span class="' . $badgeClass . '">'
                .       e($status)
                . '  </span>'
                . '</div>'
            );
        } catch (Throwable $exception) {
            return self::errorBox($exception->getMessage());
        }
    }

    private static function identityParts(Animal $record): array
    {
        $record->loadMissing('breed');

        $tag = strtoupper(
            trim((string) $record->tag_number)
        );

        $birthYearShort = blank($record->date_of_birth)
            ? '--'
            : Carbon::parse($record->date_of_birth)->format('y');

        $birthYearFull = blank($record->date_of_birth)
            ? 'Unknown'
            : Carbon::parse($record->date_of_birth)->format('Y');

        $sequenceNumber = max(
            1,
            (int) ($record->tag_sequence ?? 1)
        );

        $sequence = str_pad(
            (string) $sequenceNumber,
            2,
            '0',
            STR_PAD_LEFT
        );

        $suffix = $birthYearShort . $sequence;

        if (
            $tag !== ''
            && $birthYearShort !== '--'
            && str_ends_with($tag, $suffix)
        ) {
            $prefix = substr(
                $tag,
                0,
                strlen($tag) - strlen($suffix)
            );
        } else {
            $prefix = strtoupper(
                trim(
                    (string) (
                        $record->breed?->prefix
                        ?? $record->breed?->breed_code
                        ?? 'PENZI'
                    )
                )
            );
        }

        return [
            'prefix' => $prefix !== '' ? $prefix : 'PENZI',
            'birth_year_short' => $birthYearShort,
            'birth_year_full' => $birthYearFull,
            'sequence' => $sequence,
        ];
    }

    private static function lockedValueCard(
        string $value,
        string $caption
    ): HtmlString {
        return new HtmlString(
            '<div class="ptt-locked-card">'
            . '  <div class="ptt-locked-value">'
            .       e($value)
            . '  </div>'
            . '  <div class="ptt-locked-caption">'
            .       e($caption)
            . '  </div>'
            . '  <div class="ptt-locked-badge">LOCKED</div>'
            . '</div>'
        );
    }

    private static function compactError(
        string $message
    ): HtmlString {
        return new HtmlString(
            '<div class="ptt-locked-card ptt-locked-error">'
            . e($message)
            . '</div>'
        );
    }

    private static function userIsAdministrator(): bool
    {
        return auth()->user()?->hasAnyRole([
            'Administrator',
            'Admin',
        ]) ?? false;
    }

    private static function errorBox(
        string $message
    ): HtmlString {
        return new HtmlString(
            '<div class="ptt-preview ptt-preview-danger">'
            . '  <div>'
            . '      <div class="ptt-preview-label">Tag unavailable</div>'
            . '      <div class="ptt-preview-help">'
            .           e($message)
            . '      </div>'
            . '  </div>'
            . '  <span class="ptt-badge ptt-badge-danger">CHECK</span>'
            . '</div>'
        );
    }

    private static function modalStyles(): string
    {
        return <<<'HTML'
<style>
    .penzi-tag-tally-modal {
        width: min(94vw, 860px) !important;
    }

    .penzi-tag-tally-modal .fi-modal-header {
        padding-bottom: .6rem;
        text-align: center;
    }

    .penzi-tag-tally-modal .fi-modal-heading {
        width: 100%;
        text-align: center;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .penzi-tag-tally-modal .fi-modal-description {
        max-width: 650px;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
        line-height: 1.45;
    }

    .penzi-tag-tally-modal .fi-modal-content {
        padding-top: .75rem;
        padding-bottom: .75rem;
    }

    .penzi-tag-tally-modal .fi-modal-footer {
        justify-content: center;
    }

    .penzi-tag-tally-modal .fi-fo-component-ctn {
        gap: .85rem;
    }

    .ptt-identity-card {
        position: relative;
        overflow: hidden;
        padding: 14px 18px;
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        background:
            radial-gradient(
                circle at top right,
                rgba(59, 130, 246, .15),
                transparent 40%
            ),
            linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        text-align: center;
        box-shadow: 0 10px 24px rgba(37, 99, 235, .08);
    }

    .ptt-eyebrow,
    .ptt-preview-label {
        color: #1d4ed8;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .09em;
        text-transform: uppercase;
    }

    .ptt-current-tag {
        margin-top: 5px;
        color: #1e3a8a;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: clamp(22px, 4vw, 30px);
        font-weight: 950;
        letter-spacing: .04em;
        line-height: 1.1;
    }

    .ptt-summary {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px;
        margin-top: 7px;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
    }

    .ptt-dot {
        color: #93c5fd;
    }

    .ptt-lock-note {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 9px;
        padding: 4px 9px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: rgba(219, 234, 254, .75);
        color: #1d4ed8;
        font-size: 10px;
        font-weight: 800;
    }

    .ptt-lock-icon {
        font-size: 10px;
    }

    .ptt-locked-card {
        position: relative;
        min-height: 76px;
        padding: 11px 12px;
        border: 1px solid #dbeafe;
        border-radius: 11px;
        background: #f8fafc;
        text-align: center;
    }

    .ptt-locked-value {
        overflow: hidden;
        color: #0f172a;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 17px;
        font-weight: 900;
        letter-spacing: .03em;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .ptt-locked-caption {
        margin-top: 3px;
        color: #64748b;
        font-size: 10px;
        font-weight: 700;
    }

    .ptt-locked-badge {
        display: inline-flex;
        margin-top: 5px;
        padding: 2px 6px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 8px;
        font-weight: 900;
        letter-spacing: .08em;
    }

    .ptt-locked-error {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #991b1b;
    }

    .ptt-preview {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        min-height: 74px;
        padding: 11px 14px;
        border-radius: 12px;
    }

    .ptt-preview-main {
        min-width: 0;
    }

    .ptt-preview-success {
        border: 1px solid #86efac;
        background: #f0fdf4;
    }

    .ptt-preview-neutral {
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
    }

    .ptt-preview-danger {
        border: 1px solid #fecaca;
        background: #fef2f2;
    }

    .ptt-preview-success .ptt-preview-label {
        color: #166534;
    }

    .ptt-preview-danger .ptt-preview-label {
        color: #991b1b;
    }

    .ptt-tag-transition {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 4px;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: clamp(15px, 2vw, 19px);
        font-weight: 900;
    }

    .ptt-old-tag {
        color: #64748b;
        text-decoration: line-through;
    }

    .ptt-arrow {
        color: #94a3b8;
    }

    .ptt-new-tag {
        color: #166534;
    }

    .ptt-preview-help {
        margin-top: 3px;
        color: #64748b;
        font-size: 10px;
        line-height: 1.4;
    }

    .ptt-badge {
        flex: 0 0 auto;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .06em;
    }

    .ptt-badge-success {
        border: 1px solid #86efac;
        background: #dcfce7;
        color: #166534;
    }

    .ptt-badge-neutral {
        border: 1px solid #cbd5e1;
        background: #f1f5f9;
        color: #475569;
    }

    .ptt-badge-danger {
        border: 1px solid #fecaca;
        background: #fee2e2;
        color: #991b1b;
    }

    .dark .ptt-identity-card {
        border-color: #1e3a8a;
        background:
            radial-gradient(
                circle at top right,
                rgba(37, 99, 235, .25),
                transparent 42%
            ),
            linear-gradient(135deg, #0f172a 0%, #111827 100%);
    }

    .dark .ptt-current-tag,
    .dark .ptt-locked-value {
        color: #dbeafe;
    }

    .dark .ptt-summary,
    .dark .ptt-preview-help,
    .dark .ptt-locked-caption {
        color: #94a3b8;
    }

    .dark .ptt-locked-card,
    .dark .ptt-preview-neutral {
        border-color: #334155;
        background: #111827;
    }

    .dark .ptt-preview-success {
        border-color: #166534;
        background: rgba(20, 83, 45, .25);
    }

    .dark .ptt-preview-danger {
        border-color: #991b1b;
        background: rgba(127, 29, 29, .2);
    }

    @media (max-width: 640px) {
        .penzi-tag-tally-modal {
            width: calc(100vw - 1rem) !important;
        }

        .ptt-identity-card {
            padding: 12px;
        }

        .ptt-preview {
            align-items: flex-start;
            padding: 10px 11px;
        }

        .ptt-tag-transition {
            gap: 5px;
        }

        .ptt-summary .ptt-dot {
            display: none;
        }

        .ptt-summary {
            gap: 4px 10px;
        }
    }
</style>
HTML;
    }
}
