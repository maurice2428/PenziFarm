<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BreedingGestationRuleResource\Pages;
use App\Models\BreedingGestationRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BreedingGestationRuleResource extends Resource
{
    protected static ?string $model = BreedingGestationRule::class;
    protected static ?string $navigationGroup = 'Breeding Management';
    protected static ?string $navigationLabel = 'Gestation Rule(s)';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view gestation rules') ?? false;
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create gestation rules') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit gestation rules') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete gestation rules') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore gestation rules') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete gestation rules') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Gestation Rule')
                    ->description('Use species defaults, or assign breed-specific gestation days.')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('species')
                            ->required()
                            ->native(false)
                            ->options([
                                'Sheep' => 'Sheep',
                                'Goat' => 'Goat',
                                'Cattle' => 'Cattle',
                            ])
                            ->columnSpan(4),
                        Forms\Components\Select::make('breed_id')
                            ->label('Breed Override')
                            ->relationship('breed', 'breed_name')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty for species default.')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('gestation_days')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->columnSpan(4),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->columnSpan(3),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('species')
            ->columns([
                Tables\Columns\TextColumn::make('species')->badge()->sortable(),
                Tables\Columns\TextColumn::make('breed.breed_name')
                    ->label('Breed Override')
                    ->placeholder('Species Default')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gestation_days')
                    ->label('Days')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (BreedingGestationRule $record): bool => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (BreedingGestationRule $record): bool => static::canDelete($record)),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn (BreedingGestationRule $record): bool => static::canRestore($record)),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn (BreedingGestationRule $record): bool => static::canForceDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('delete gestation rules') ?? false),
                Tables\Actions\RestoreBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('restore gestation rules') ?? false),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('force delete gestation rules') ?? false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBreedingGestationRules::route('/'),
            'create' => Pages\CreateBreedingGestationRule::route('/create'),
            'edit' => Pages\EditBreedingGestationRule::route('/{record}/edit'),
        ];
    }
}
