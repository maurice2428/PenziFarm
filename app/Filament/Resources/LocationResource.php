<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Livestock\Animals as AnimalsCluster;
use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Support\LocationForm;
use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $cluster = AnimalsCluster::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Locations';

    protected static ?string $modelLabel = 'Location';

    protected static ?string $pluralModelLabel = 'Locations';

    protected static ?int $navigationSort = 11;

    public static function getNavigationBadge(): ?string
    {
        return (string) Location::query()->active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view locations') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view locations') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view locations') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create locations') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit locations') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete locations') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(LocationForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('is_default', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Location')
                    ->description(fn (Location $record): string => $record->address ?: 'No address recorded')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? str($state)->replace('_', ' ')->headline()->toString()
                        : 'Other')
                    ->color('info'),

                Tables\Columns\TextColumn::make('county')
                    ->label('County')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('sub_county')
                    ->label('Area')
                    ->toggleable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('ward')
                    ->label('Ward')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('—'),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->trueColor('warning'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('animals_count')
                    ->label('Animals')
                    ->counts('animals')
                    ->badge()
                    ->color(fn (string|int|null $state): string => ((int) $state) > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'station' => 'Farm Station',
                        'paddock' => 'Paddock',
                        'shed' => 'Shed / Barn',
                        'pen' => 'Pen / Housing Unit',
                        'quarantine' => 'Quarantine Area',
                        'grazing' => 'Grazing Area',
                        'store' => 'Store / Feed Area',
                        'other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Location'),
            ])
            ->actions([
                Tables\Actions\Action::make('openMap')
                    ->label('')
                    ->tooltip('Open Map')
                    ->icon('heroicon-o-map')
                    ->iconButton()
                    ->color('info')
                    ->visible(fn (Location $record): bool => filled($record->latitude) && filled($record->longitude))
                    ->url(fn (Location $record): string => 'https://www.openstreetmap.org/?mlat=' . $record->latitude . '&mlon=' . $record->longitude . '#map=17/' . $record->latitude . '/' . $record->longitude)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('printLocation')
                    ->label('')
                    ->tooltip('Print Location PDF')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->can('print locations') || auth()->user()?->can('export locations') || auth()->user()?->can('view locations'))
                    ->action(function (Location $record) {
                        $locations = Location::query()
                            ->withCount('animals')
                            ->whereKey($record->getKey())
                            ->get();

                        $pdf = Pdf::loadView('pdf.locations-report', [
                            'locations' => $locations,
                            'generatedBy' => auth()->user(),
                            'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
                            'reportTitle' => 'Location Report',
                            'reportSubtitle' => $record->display_name,
                        ])
                            ->setPaper('a4', 'landscape')
                            ->setOptions([
                                'isHtml5ParserEnabled' => true,
                                'isRemoteEnabled' => false,
                                'dpi' => 96,
                                'defaultFont' => 'Courier',
                                'enable_php' => true,
                            ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'location-report-' . str($record->name)->slug() . '-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf'
                        );
                    }),

                Tables\Actions\Action::make('makeDefault')
                    ->label('')
                    ->tooltip('Make Default Animal Location')
                    ->icon('heroicon-o-star')
                    ->iconButton()
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Location $record): bool => ! $record->is_default && (auth()->user()?->can('edit locations') ?? false))
                    ->action(function (Location $record): void {
                        Location::query()->update(['is_default' => false]);

                        $record->update([
                            'is_default' => true,
                            'is_active' => true,
                            'updated_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title($record->name . ' is now the default animal location.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Location $record): bool =>
                        (auth()->user()?->can('delete locations') ?? false)
                        && $record->animals()->count() === 0
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelectedLocations')
                        ->label('Print Selected')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->can('print locations') || auth()->user()?->can('export locations') || auth()->user()?->can('view locations'))
                        ->action(function (Collection $records) {
                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->warning()
                                    ->title('No locations selected')
                                    ->send();

                                return null;
                            }

                            $locations = Location::query()
                                ->withCount('animals')
                                ->whereIn('id', $records->pluck('id'))
                                ->orderByDesc('is_default')
                                ->orderBy('name')
                                ->get();

                            $pdf = Pdf::loadView('pdf.locations-report', [
                                'locations' => $locations,
                                'generatedBy' => auth()->user(),
                                'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
                                'reportTitle' => 'Locations Bulk Report',
                                'reportSubtitle' => 'Selected locations: ' . number_format($locations->count()),
                            ])
                                ->setPaper('a4', 'landscape')
                                ->setOptions([
                                    'isHtml5ParserEnabled' => true,
                                    'isRemoteEnabled' => false,
                                    'dpi' => 96,
                                    'defaultFont' => 'Courier',
                                    'enable_php' => true,
                                ]);

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'locations-report-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf'
                            );
                        }),

                    Tables\Actions\BulkAction::make('deleteSelectedLocationsSafely')
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected locations?')
                        ->modalDescription('Only locations with no animals assigned will be deleted. Locations containing animals will be skipped to protect animal records.')
                        ->modalSubmitActionLabel('Delete Eligible Locations')
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->can('delete locations') ?? false)
                        ->action(function (Collection $records): void {
                            $deleted = [];
                            $skipped = [];

                            foreach ($records as $record) {
                                $animalCount = $record->animals()->count();

                                if ($animalCount > 0) {
                                    $skipped[] = $record->name . ' (' . $animalCount . ' animal(s))';

                                    continue;
                                }

                                $deleted[] = $record->name;
                                $record->delete();
                            }

                            if ($deleted !== []) {
                                Notification::make()
                                    ->success()
                                    ->title(count($deleted) . ' location(s) deleted')
                                    ->body('Deleted: ' . implode(', ', array_slice($deleted, 0, 8)))
                                    ->send();
                            }

                            if ($skipped !== []) {
                                Notification::make()
                                    ->warning()
                                    ->title(count($skipped) . ' location(s) skipped')
                                    ->body('Skipped because animals are assigned: ' . implode(', ', array_slice($skipped, 0, 8)))
                                    ->persistent()
                                    ->send();
                            }

                            if ($deleted === [] && $skipped === []) {
                                Notification::make()
                                    ->warning()
                                    ->title('No locations selected')
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateHeading('No livestock locations have been created')
            ->emptyStateDescription('Create farm stations, paddocks, sheds, pens and quarantine areas so animals can be assigned correctly.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
