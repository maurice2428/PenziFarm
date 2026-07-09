<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BreedingGestationRuleResource\Pages;
use App\Models\Breed;
use App\Models\BreedingGestationRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BreedingGestationRuleResource extends Resource
{
    protected static ?string $model = BreedingGestationRule::class;

    protected static ?string $navigationGroup = 'Breeding Management';

    protected static ?string $navigationLabel = 'Gestation Rules';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 3;

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
                Tables\Columns\TextColumn::make('species')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('breed.breed_name')
                    ->label('Breed Override')
                    ->placeholder('Species Default')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gestation_days')
                    ->label('Days')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
