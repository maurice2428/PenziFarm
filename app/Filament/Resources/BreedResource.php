<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BreedResource\Pages;
use App\Models\Breed;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Unique;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BreedResource extends Resource
{
    protected static ?string $model = Breed::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Breed(s)';

    protected static ?string $navigationLabel = 'Breeds';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
/*
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Manager', 'Vet']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Manager', 'Vet']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Manager']) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }
    */
    public static function shouldRegisterNavigation(): bool
{
    return auth()->user()?->can('view breeds') ?? false;
}

public static function canAccess(): bool
{
    return auth()->user()?->can('view breeds') ?? false;
}

public static function canViewAny(): bool
{
    return auth()->user()?->can('view breeds') ?? false;
}

public static function canCreate(): bool
{
    return auth()->user()?->can('create breeds') ?? false;
}

public static function canEdit($record): bool
{
    return auth()->user()?->can('edit breeds') ?? false;
}

public static function canDelete($record): bool
{
    return auth()->user()?->can('delete breeds') ?? false;
}

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Breed Details')
                    ->description('This breed setup powers automatic animal tag generation.')
                    ->schema([
                        Forms\Components\Select::make('parent_category')
                            ->label('Parent Category')
                            ->options(Breed::parentCategories())
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->placeholder('Select a category')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('breed_name')
                            ->label('Breed Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Example: Boer')
                            ->live(onBlur: true)
                            ->unique(
                                table: 'breeds',
                                column: 'breed_name',
                                ignoreRecord: true,
                                modifyRuleUsing: fn(Unique $rule, callable $get) => $rule->where('parent_category', $get('parent_category'))
                            )
                            ->columnSpan(1),

                        /*
                         * Forms\Components\TextInput::make('prefix')
                         *     ->label('Breed Prefix')
                         *     ->required()
                         *     ->maxLength(10)
                         *     ->placeholder('Example: BOE')
                         *     ->helperText('Used for auto-generating animal tags, e.g. BOE-0001')
                         *     ->live(onBlur: true)
                         *     ->afterStateUpdated(function ($state, callable $set) {
                         *         if ($state) {
                         *             $set('prefix', strtoupper(trim($state)));
                         *         }
                         *     })
                         *     ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state)))
                         *     ->unique(
                         *         table: 'breeds',
                         *         column: 'prefix',
                         *         ignoreRecord: true
                         *     )
                         *     ->rule('alpha_dash')
                         *     ->columnSpan(1),
                         */
                        Forms\Components\TextInput::make('prefix')
                            ->label('Breed Prefix')
                            ->default('GNK')
                            ->readOnly()
                            ->required()
                            ->dehydrated(true)
                            ->maxLength(10)
                            ->helperText('Default breed prefix is always GNK.')
                            ->dehydrateStateUsing(fn() => 'GNK')
                            ->formatStateUsing(fn() => 'GNK')
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Breed')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Only active breeds should be available for new animal registration.')
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->placeholder('Brief breed notes, production characteristics, adaptation, or remarks...')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->directory('breeds')
                            ->disk('public')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->multiple(false)
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn() => auth()->id()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-animal.png')),
                Tables\Columns\TextColumn::make('parent_category')
                    ->label('Category')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('breed_name')
                    ->label('Breed Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('prefix')
                    ->label('Prefix')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('creator.name')
    ->label('Created By')
    ->description(function ($record) {
        $role = $record->creator?->getRoleNames()?->first();

        return $role ?: 'No role';
    })
    ->badge()
    ->color(function ($record) {
        $role = $record->creator?->getRoleNames()?->first();

        return match ($role) {
            'Admin' => 'danger',
            'Manager' => 'success',
            'Finance' => 'warning',
            'Vet' => 'info',
            default => 'gray',
        };
    })
    ->searchable()
    ->sortable()
    ->toggleable(isToggledHiddenByDefault: true)
    ->visible(fn () => auth()->user()?->can('view users')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_category')
                    ->label('Parent Category')
                    ->options(Breed::parentCategories()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->can('edit breeds')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()?->can('delete breeds'))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printBreedsPdf')
                        ->label('Print PDF')
                        ->icon('heroicon-o-printer')
                        ->color('warning')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title('No breeds selected.')
                                    ->warning()
                                    ->send();

                                return null;
                            }

                            $user = auth()->user();
                            $generatedByRole = $user?->getRoleNames()?->first() ?? 'User';
                            $farmName = setting('farm.name', 'Farm Farms');
                            $now = now('Africa/Nairobi');

                            $verificationText = $farmName . ' Breed Report | Generated by: ' . $user->name
                                . ' (' . $generatedByRole . ') | Date: ' . $now->format('Y-m-d H:i:s') . ' EAT'
                                . ' | Total Records: ' . $records->count();

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

                            $pdf = Pdf::loadView('pdf.breeds-bulk-report', [
                                'breeds' => $records->load('creator.roles'),
                                'generatedBy' => $user,
                                'generatedByRole' => $generatedByRole,
                                'verificationText' => $verificationText,
                                'qrImage' => $qrImage,
                            ])->setPaper('a4', 'landscape');

                            return response()->streamDownload(
                                fn() => print ($pdf->output()),
                                'breed-bulk-report-' . now()->format('Ymd_His') . '.pdf'
                            );
                        })
                        ->visible(fn() => auth()->user()?->can('export breeds')),

Tables\Actions\DeleteBulkAction::make()
    ->visible(fn() => auth()->user()?->can('delete breeds')),
                ]),
            ])
            ->defaultSort('breed_name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBreeds::route('/'),
            'create' => Pages\CreateBreed::route('/create'),
            'edit' => Pages\EditBreed::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
