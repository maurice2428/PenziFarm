<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Livestock\Animals as AnimalsCluster;
use App\Filament\Resources\AnimalTransferResource\Pages;
use App\Models\Animal;
use App\Models\AnimalTransfer;
use App\Models\Breed;
use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

class AnimalTransferResource extends Resource
{
    protected static ?string $model = AnimalTransfer::class;

    // protected static ?string $cluster = AnimalsCluster::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Transfer(s)';

    protected static ?string $navigationGroup = 'Livestock';

    protected static ?int $navigationSort = 37;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view animal transfers') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view animal transfers') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create animal transfers') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit animal transfers') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete animal transfers') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Transfer Details')
                ->description('Move animals from one farm location to another. Receiving the transfer updates each animal current location.')
                ->icon('heroicon-o-map')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                ->schema([
                    Forms\Components\TextInput::make('transfer_number')
                        ->label('Transfer Number')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto generated'),
                    Forms\Components\DatePicker::make('transfer_date')
                        ->label('Transfer Date')
                        ->default(now('Africa/Nairobi'))
                        ->required(),
                    Forms\Components\DatePicker::make('expected_receive_date')
                        ->label('Expected Receive Date'),
                    Forms\Components\Select::make('from_location_id')
                        ->label('From Location')
                        ->options(fn(): array => Location::query()
                            ->active()
                            ->defaultFirst()
                            ->pluck('name', 'id')
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set): void {
                            $set('breed_id', null);
                            $set('animal_ids', []);
                        })
                        ->helperText('Optional. If selected, only animals currently in this location will appear.'),
                    Forms\Components\Select::make('to_location_id')
                        ->label('To Location')
                        ->options(fn(): array => Location::query()
                            ->active()
                            ->defaultFirst()
                            ->pluck('name', 'id')
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->different('from_location_id')
                        ->helperText('Destination location where animals will be received.'),
                    Forms\Components\Select::make('status')
                        ->label('Transfer Status')
                        ->options([
                            'draft' => 'Draft',
                            'pending' => 'Pending / In Transit',
                            'completed' => 'Completed / Received',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required(),
                    Forms\Components\TextInput::make('reason')
                        ->label('Reason')
                        ->maxLength(255)
                        ->placeholder('Breeding, feeding, isolation, sale preparation'),
                    Forms\Components\Textarea::make('notes')
                        ->label('Transfer Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Animals To Transfer')
                ->description('First choose the breed, then select animal tags. Sold, dead, culled, and archived animals are excluded.')
                ->icon('heroicon-o-queue-list')
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ])
                ->schema([
                    Forms\Components\Select::make('breed_id')
                        ->label('Breed')
                        ->dehydrated(false)
                        ->required()
                        ->live()
                        ->searchable()
                        ->preload()
                        ->options(fn(): array => Breed::query()
                            ->orderBy('parent_category')
                            ->orderBy('breed_name')
                            ->pluck('breed_name', 'id')
                            ->toArray())
                        ->afterStateUpdated(function (Forms\Set $set): void {
                            $set('animal_ids', []);
                        })
                        ->helperText('Required. Animal tags will be filtered by this breed.'),
                    Forms\Components\Select::make('animal_ids')
                        ->label('Animal Tags')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->dehydrated(false)
                        ->disabled(fn(Forms\Get $get): bool => blank($get('breed_id')))
                        ->options(function (Forms\Get $get): array {
                            if (blank($get('breed_id'))) {
                                return [];
                            }

                            return Animal::query()
                                ->with(['breed', 'location'])
                                ->where('status', 'Active')
                                ->where('is_archived', false)
                                ->where('breed_id', $get('breed_id'))
                                ->when(
                                    $get('from_location_id'),
                                    fn($query, $locationId) => $query->where('current_location_id', $locationId)
                                )
                                ->orderBy('tag_number')
                                ->get()
                                ->mapWithKeys(function (Animal $animal): array {
                                    $location = $animal->location?->name ?: 'No location';

                                    return [
                                        $animal->id => $animal->tag_number . ' — ' . $location,
                                    ];
                                })
                                ->toArray();
                        })
                        ->helperText('Only active animals matching the selected breed and origin location are shown.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('transfer_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')
                    ->label('Transfer')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromLocation.name')
                    ->label('From')
                    ->default('Mixed / Current')
                    ->searchable(),
                Tables\Columns\TextColumn::make('toLocation.name')
                    ->label('To')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transfer_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Animals')
                    ->counts('items')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn(AnimalTransfer $record): string => match ($record->status) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'draft' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('preparedBy.name')
                    ->label('Prepared By')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('received_at')
                    ->label('Received')
                    ->dateTime('d M Y H:i')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending / In Transit',
                        'completed' => 'Completed / Received',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('from_location_id')
                    ->label('From Location')
                    ->options(fn(): array => Location::query()->defaultFirst()->pluck('name', 'id')->toArray())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('to_location_id')
                    ->label('To Location')
                    ->options(fn(): array => Location::query()->defaultFirst()->pluck('name', 'id')->toArray())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('receive')
                    ->label('Receive')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Receive transferred animals?')
                    ->modalDescription('This will update the current location of all selected animals to the destination location.')
                    ->visible(fn(AnimalTransfer $record): bool =>
                        !in_array($record->status, ['completed', 'cancelled'], true) &&
                        (auth()->user()?->can('receive animal transfers') ?? false))
                    ->form([
                        Forms\Components\Textarea::make('receive_notes')
                            ->label('Receive Notes')
                            ->rows(3),
                    ])
                    ->action(function (AnimalTransfer $record, array $data): void {
                        $record->complete($data['receive_notes'] ?? null);

                        Notification::make()
                            ->success()
                            ->title('Transfer received')
                            ->body('Animal current locations have been updated.')
                            ->send();
                    }),
                Tables\Actions\Action::make('print')
                    ->label('')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->visible(fn(): bool => auth()->user()?->can('print animal transfer reports') ?? false)
                    ->action(function (AnimalTransfer $record) {
                        $record->load([
                            'fromLocation',
                            'toLocation',
                            'items.animal.breed',
                            'items.fromLocation',
                            'items.toLocation',
                            'preparedBy',
                            'receivedBy',
                        ]);

                        $pdf = Pdf::loadView('pdf.animal-transfer-report', [
                            'transfer' => $record,
                            'generatedBy' => auth()->user(),
                            'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
                        ])
                            ->setPaper('a4', 'portrait')
                            ->setOptions([
                                'isHtml5ParserEnabled' => true,
                                'isRemoteEnabled' => false,
                                'dpi' => 96,
                                'defaultFont' => 'Courier',
                                'enable_php' => true,
                            ]);

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            $record->transfer_number . '.pdf'
                        );
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn(AnimalTransfer $record): bool =>
                        !in_array($record->status, ['completed', 'cancelled'], true) &&
                        (auth()->user()?->can('edit animal transfers') ?? false)),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete animal transfer?')
                    ->modalDescription('This deletes the transfer record and its transfer item lines. It does not reverse animal locations already updated by a completed transfer.')
                    ->visible(fn(): bool => auth()->user()?->can('delete animal transfers') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelected')
                        ->label('Print Selected')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->visible(fn(): bool => auth()->user()?->can('print animal transfer reports') ?? false)
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $records->load([
                                'fromLocation',
                                'toLocation',
                                'items.animal.breed',
                                'items.fromLocation',
                                'items.toLocation',
                                'preparedBy',
                                'receivedBy',
                            ]);

                            $pdf = Pdf::loadView('pdf.animal-transfers-bulk-report', [
                                'transfers' => $records,
                                'generatedBy' => auth()->user(),
                                'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
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
                                fn() => print ($pdf->output()),
                                'animal-transfers-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf'
                            );
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected animal transfers?')
                        ->modalDescription('This deletes the selected transfer records and their transfer item lines. It does not reverse animal locations already updated by completed transfers.')
                        ->visible(fn(): bool => auth()->user()?->can('delete animal transfers') ?? false),
                ]),
            ]);
    }

    public static function syncTransferAnimals(AnimalTransfer $transfer, array $animalIds): void
    {
        $animalIds = collect($animalIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $transfer
            ->items()
            ->whereNotIn('animal_id', $animalIds)
            ->delete();

        $animals = Animal::query()
            ->with(['breed', 'location'])
            ->whereIn('id', $animalIds)
            ->get();

        foreach ($animals as $animal) {
            $transfer->items()->updateOrCreate(
                ['animal_id' => $animal->id],
                [
                    'from_location_id' => $transfer->from_location_id ?: $animal->current_location_id,
                    'to_location_id' => $transfer->to_location_id,
                    'tag_number' => $animal->tag_number,
                    'breed_name' => $animal->breed?->breed_name,
                    'sex' => $animal->sex,
                    'status' => $transfer->status === 'completed' ? 'received' : 'pending',
                    'received_at' => $transfer->status === 'completed' ? now() : null,
                ]
            );
        }

        if ($transfer->status === 'completed') {
            $transfer->complete($transfer->receive_notes);
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnimalTransfers::route('/'),
            'create' => Pages\CreateAnimalTransfer::route('/create'),
            'edit' => Pages\EditAnimalTransfer::route('/{record}/edit'),
        ];
    }
}
