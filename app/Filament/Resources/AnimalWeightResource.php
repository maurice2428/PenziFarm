<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnimalWeightResource\Pages;
use App\Models\AnimalWeight;
use App\Models\Breed;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class AnimalWeightResource extends Resource
{
    protected static ?string $model = AnimalWeight::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Livestock';

    protected static ?string $navigationLabel = ' Weight(s)';

    protected static ?string $modelLabel = 'Animal Weight';

    protected static ?string $pluralModelLabel = ' Weight(s)';

    protected static ?int $navigationSort = 35;

    /* public static function getEloquentQuery(): Builder
     {
         return parent::getEloquentQuery()
             ->withoutGlobalScopes([
                 SoftDeletingScope::class,
             ])
             ->whereIn('id', function ($query) {
                 $query
                     ->selectRaw('MAX(id)')
                     ->from('animal_weights')
                     ->groupBy('animal_id');
             });
     }*/
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->whereIn('id', function ($query) {
                $query
                    ->selectRaw('MAX(id)')
                    ->from('animal_weights')
                    ->whereNull('deleted_at')
                    ->groupBy('animal_id');
            });
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view weight records') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view weight records') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view weight records') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create weight records') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit weight records') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete weight records') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Record Animal Weight')
                    ->description('Capture animal weight measurements for growth tracking, health monitoring, and performance analysis.')
                    ->icon('heroicon-o-scale')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('animal_id')
                            ->label('Animal Tag')
                            ->relationship(
                                name: 'animal',
                                titleAttribute: 'tag_number',
                                modifyQueryUsing: fn(Builder $query) => $query
                                    ->where('status', 'Active')
                                    ->where('is_archived', false)
                                    ->orderBy('tag_number')
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),

                        /*
                         * Forms\Components\TextInput::make('weight_kg')
                         * ->label('Current Weight')
                         * ->numeric()
                         * ->minValue(0.1)
                         * ->step('0.01')
                         * ->suffix('KG')
                         * ->required(),
                         */
                        Forms\Components\TextInput::make('weight_kg')
                            ->label('Current Weight')
                            ->numeric()
                            ->minValue(2.01)
                            ->step('0.01')
                            ->suffix('KG')
                            ->rules([
                                'required',
                                'numeric',
                                'gt:2',
                                'decimal:0,2',
                            ])
                            ->validationMessages([
                                'gt' => 'Weight must be greater than 2 KG.',
                                'numeric' => 'Weight must be a valid number.',
                                'decimal' => 'Weight may be a whole number or decimal like 65.5 or 65.50.',
                            ])
                            ->placeholder('e.g. 65.5')
                            ->required(),
                        Forms\Components\DateTimePicker::make('recorded_at')
                            ->label('Date & Time')
                            ->seconds(false)
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Animal Weight Intelligence')
            ->description('Latest weight per animal, growth trend, breed performance, and weight-loss alerts.')
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['animal.breed', 'recorder']))
            ->columns([
                Tables\Columns\TextColumn::make('animal.tag_number')
                    ->label('Animal Tag')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-tag'),
                Tables\Columns\TextColumn::make('animal.breed.breed_name')
                    ->label('Breed')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('animal.species')
                    ->label('Species')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('animal.sex')
                    ->label('Sex')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Male' => 'info',
                        'Female' => 'danger',
                        default => 'gray',
                    }),
                /* Tables\Columns\TextColumn::make('animal.date_of_birth')
                     ->label('Age')
                     ->formatStateUsing(function ($record) {
                         $dob = $record->animal?->date_of_birth;

                         if (!$dob) {
                             return 'Unknown';
                         }

                         return Carbon::parse($dob)->diffForHumans([
                             'parts' => 2,
                             'syntax' => CarbonInterface::DIFF_ABSOLUTE,
                         ]);
                     }),*/
                Tables\Columns\TextColumn::make('age_display')
                    ->label('Age')
                    ->state(function ($record) {
                        $animal = $record->animal;

                        if (!$animal || blank($animal->date_of_birth)) {
                            return '-';
                        }

                        $dob = \Carbon\Carbon::parse($animal->date_of_birth);

                        if ($dob->isFuture()) {
                            return 'Invalid DOB';
                        }

                        $age = $dob->diffForHumans(now(), [
                            'parts' => 2,
                            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                        ]);

                        return (bool) $animal->date_of_birth_is_estimated
                            ? 'Approx. ' . $age
                            : $age;
                    })
                    ->badge()
                    ->color(fn($record) => blank($record->animal?->date_of_birth)
                        ? 'gray'
                        : ((bool) $record->animal?->date_of_birth_is_estimated ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('previous_weight_kg')
                    ->label('Previous')
                    ->state(function ($record) {
                        $previous = \App\Models\AnimalWeight::query()
                            ->where('animal_id', $record->animal_id)
                            ->where('recorded_at', '<', $record->recorded_at)
                            ->whereNull('deleted_at')
                            ->latest('recorded_at')
                            ->first();

                        return $previous
                            ? number_format((float) $previous->weight_kg, 2) . ' KG'
                            : '-';
                    }),
                Tables\Columns\TextColumn::make('weight_kg')
                    ->label('Current')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2) . ' KG')
                    ->weight('bold')
                    ->sortable(),
                Tables\Columns\TextColumn::make('trend')
                    ->label('Trend')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        $diff = $record->weight_difference;

                        return match ($record->trend) {
                            'gaining' => 'Gained ' . number_format(abs($diff), 2) . ' KG',
                            'losing' => 'Lost ' . number_format(abs($diff), 2) . ' KG',
                            'stable' => 'No Change',
                            default => 'First Entry',
                        };
                    })
                    ->color(fn($record) => match ($record->trend) {
                        'gaining' => 'success',
                        'losing' => 'danger',
                        'stable' => 'warning',
                        default => 'info',
                    })
                    ->icon(fn($record) => match ($record->trend) {
                        'gaining' => 'heroicon-o-arrow-trending-up',
                        'losing' => 'heroicon-o-arrow-trending-down',
                        'stable' => 'heroicon-o-minus',
                        default => 'heroicon-o-plus-circle',
                    }),
                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Recorded At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('d M Y, h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('breed')
                    ->label('Breed')
                    ->options(fn() => Breed::query()
                        ->where('is_active', true)
                        ->orderBy('breed_name')
                        ->pluck('breed_name', 'id')
                        ->toArray())
                    ->query(function (Builder $query, array $data) {
                        if (!filled($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('animal', fn($q) => $q->where('breed_id', $data['value']));
                    }),
                Tables\Filters\SelectFilter::make('sex')
                    ->label('Sex')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!filled($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('animal', fn($q) => $q->where('sex', $data['value']));
                    }),
                Tables\Filters\Filter::make('losing_weight')
                    ->label('Losing Weight')
                    ->query(fn(Builder $query) => $query->whereRaw('
                        weight_kg < (
                            SELECT aw2.weight_kg
                            FROM animal_weights aw2
                            WHERE aw2.animal_id = animal_weights.animal_id
                            AND aw2.recorded_at < animal_weights.recorded_at
                            AND aw2.deleted_at IS NULL
                            ORDER BY aw2.recorded_at DESC
                            LIMIT 1
                        )
                    ')),
                Tables\Filters\Filter::make('gaining_weight')
                    ->label('Gaining Weight')
                    ->query(fn(Builder $query) => $query->whereRaw('
                        weight_kg > (
                            SELECT aw2.weight_kg
                            FROM animal_weights aw2
                            WHERE aw2.animal_id = animal_weights.animal_id
                            AND aw2.recorded_at < animal_weights.recorded_at
                            AND aw2.deleted_at IS NULL
                            ORDER BY aw2.recorded_at DESC
                            LIMIT 1
                        )
                    ')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn() =>
                        auth()->user()?->can('view weight records'))
                    ->label('')
                    ->tooltip('View weight history')
                    ->icon('heroicon-o-chart-bar'),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit latest weight')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn($record) =>
                        auth()->user()?->can('edit weight records') &&
                        !$record->trashed()),
                Tables\Actions\Action::make('animal')
                    ->label('')
                    ->tooltip('Open animal record')
                    ->icon('heroicon-o-eye')
                    ->visible(fn() =>
                        auth()->user()?->can('view animals'))
                    ->color('success')
                    ->url(
                        fn (AnimalWeight $record): ?string =>
                            $record->animal_id
                                ? AnimalResource::getUrl('profile', [
                                    'record' => $record->animal_id,
                                ])
                                : null
                    )
                    ->openUrlInNewTab(),
                /*  Tables\Actions\DeleteAction::make()
                      ->label('')
                      ->tooltip('Move to trash')
                      ->icon('heroicon-o-trash')
                      ->modalHeading('Move Weight Record to Trash')
                      ->modalDescription('This will soft delete the selected weight record. You can restore it later from the deleted records filter.')
                      ->modalSubmitActionLabel('Yes, move to trash')
                      ->successNotificationTitle('Weight record moved to trash')
                      ->visible(fn($record) => !$record->trashed()),*/
                Tables\Actions\Action::make('delete_weight_options')
                    ->label('')
                    ->tooltip('Delete weight records')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(
                        fn($record) =>
                            !$record->trashed() &&
                            auth()->user()?->can('delete weight records')
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Delete Weight Records')
                    ->modalDescription(fn($record) =>
                        "Choose how you want to delete weight records for animal {$record->animal?->tag_number}.")
                    ->form([
                        Forms\Components\Radio::make('delete_scope')
                            ->label('Delete Option')
                            ->options([
                                'latest' => 'Delete last recent record only',
                                'all' => 'Delete all weight records for this animal',
                            ])
                            ->default('latest')
                            ->required()
                            ->descriptions([
                                'latest' => 'Only the currently listed latest weight record will be moved to trash.',
                                'all' => 'All weight records for this animal will be moved to trash.',
                            ]),
                    ])
                    ->modalSubmitActionLabel('Confirm Delete')
                    ->action(function (array $data, AnimalWeight $record, $livewire) {
                        if (($data['delete_scope'] ?? 'latest') === 'all') {
                            AnimalWeight::query()
                                ->where('animal_id', $record->animal_id)
                                ->whereNull('deleted_at')
                                ->delete();

                            Notification::make()
                                ->success()
                                ->title('All weight records deleted')
                                ->body('All weight records for this animal have been moved to trash.')
                                ->send();
                        } else {
                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title('Latest weight record deleted')
                                ->body('The latest weight record has been moved to trash.')
                                ->send();
                        }

                        $livewire->dispatch('$refresh');
                    }),
                Tables\Actions\RestoreAction::make()
                    ->label('')
                    ->tooltip('Restore record')
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Restore Weight Record')
                    ->modalDescription('This will restore the deleted weight record and make it active again.')
                    ->modalSubmitActionLabel('Yes, restore')
                    ->successNotificationTitle('Weight record restored')
                    ->visible(fn($record) =>
                        auth()->user()?->can('restore weight records') &&
                        $record->trashed()),
                Tables\Actions\ForceDeleteAction::make()
                    ->label('')
                    ->tooltip('Delete permanently')
                    ->icon('heroicon-o-x-circle')
                    ->modalHeading('Permanently Delete Weight Record')
                    ->modalDescription('This action cannot be undone. The weight record will be permanently removed from the database.')
                    ->modalSubmitActionLabel('Yes, delete permanently')
                    ->successNotificationTitle('Weight record permanently deleted')
                    ->visible(fn($record) =>
                        auth()->user()?->can('force delete weight records') &&
                        $record->trashed()),
            ])
            /*
             * ->bulkActions([
             *     Tables\Actions\BulkActionGroup::make([
             *         Tables\Actions\BulkAction::make('print_selected')
             *             ->label('Weight Report')
             *             ->icon('heroicon-o-printer')
             *             ->color('success')
             *             ->action(function (Collection $records) {
             *                 $ids = $records->pluck('id')->implode(',');
             *
             *                 if (blank($ids)) {
             *                     Notification::make()
             *                         ->title('No records selected')
             *                         ->danger()
             *                         ->send();
             *
             *                     return;
             *                 }
             *
             *                 return redirect()->route('animal-weights.bulk-report', [
             *                     'ids' => $ids,
             *                 ]);
             *             }),
             *
             *         Tables\Actions\DeleteBulkAction::make()
             *             ->label('Delete Selected')
             *             ->modalHeading('Move Selected Weight Records to Trash')
             *             ->modalDescription('This will soft delete the selected weight records. They can be restored later.')
             *             ->modalSubmitActionLabel('Yes, move selected to trash')
             *             ->successNotificationTitle('Selected weight records moved to trash'),
             *
             *         Tables\Actions\RestoreBulkAction::make()
             *             ->label('Restore Selected')
             *             ->modalHeading('Restore Selected Weight Records')
             *             ->modalDescription('This will restore the selected deleted weight records.')
             *             ->modalSubmitActionLabel('Yes, restore selected')
             *             ->successNotificationTitle('Selected weight records restored'),
             *
             *         Tables\Actions\ForceDeleteBulkAction::make()
             *             ->label('Delete Permanently')
             *             ->modalHeading('Permanently Delete Selected Weight Records')
             *             ->modalDescription('This cannot be undone. The selected weight records will be permanently deleted.')
             *             ->modalSubmitActionLabel('Yes, delete permanently')
             *             ->successNotificationTitle('Selected weight records permanently deleted'),
             *     ]),
             * ])
             */
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printWeightsPdf')
                        ->label('Weight Report')
                        ->icon('heroicon-o-printer')
                        ->color('warning')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title('No weight records selected.')
                                    ->warning()
                                    ->send();

                                return null;
                            }

                            $user = auth()->user();
                            $generatedByRole = $user?->getRoleNames()?->first() ?? 'User';

                            $farmName = setting('farm.name', 'Lelekwe Farms');
                            $now = now('Africa/Nairobi');

                            // ✅ Load relations
                            $records->load([
                                'animal.breed',
                                'animal.location',
                                'recorder'
                            ]);

                            // ✅ Compute weight trends
                            $records->each(function ($w) {
                                $prev = \App\Models\AnimalWeight::query()
                                    ->where('animal_id', $w->animal_id)
                                    ->where('recorded_at', '<', $w->recorded_at)
                                    ->latest('recorded_at')
                                    ->first();

                                $w->previous_weight_kg = $prev?->weight_kg;

                                if (!$prev) {
                                    $w->trend = 'first';
                                    $w->weight_difference = null;
                                    return;
                                }

                                $diff = (float) $w->weight_kg - (float) $prev->weight_kg;

                                $w->weight_difference = $diff;
                                $w->trend = $diff > 0 ? 'gaining' : ($diff < 0 ? 'losing' : 'stable');
                            });

                            // ✅ Verification QR
                            $verificationText =
                                $farmName . ' Animal Weight Report | Generated by: '
                                . $user->name . ' (' . $generatedByRole . ')'
                                . ' | Date: ' . $now->format('Y-m-d H:i:s') . ' EAT'
                                . ' | Records: ' . $records->count();

                            $qrImage = null;

                            try {
                                $qrImage = 'data:image/png;base64,' . base64_encode(
                                    QrCode::format('png')
                                        ->size(140)
                                        ->margin(1)
                                        ->generate($verificationText)
                                );
                            } catch (\Throwable $e) {
                                \Log::error('QR generation failed: ' . $e->getMessage());
                                $qrImage = null;
                            }

                            // ✅ Generate PDF
                            $pdf = Pdf::loadView('pdf.animal-weight-bulk-report', [
                                'weights' => $records,
                                'generatedBy' => $user,
                                'generatedByRole' => $generatedByRole,
                                'verificationText' => $verificationText,
                                'qrImage' => $qrImage,
                            ])->setPaper('a4', 'landscape');

                            // ✅ STREAM DOWNLOAD (no redirect, stays on page)
                            return response()->streamDownload(
                                fn() => print ($pdf->output()),
                                'animal-weight-report-' . now()->format('Ymd_His') . '.pdf'
                            );
                        })
                        ->visible(fn() =>
                            auth()->user()?->can('export weight records') ?? false),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->visible(fn() =>
                            auth()->user()?->can('delete weight records')),
                ])
            ])
            ->defaultSort('recorded_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnimalWeights::route('/'),
            'create' => Pages\CreateAnimalWeight::route('/create'),
            'view' => Pages\ViewAnimalWeight::route('/{record}'),
            'edit' => Pages\EditAnimalWeight::route('/{record}/edit'),
        ];
    }
}
