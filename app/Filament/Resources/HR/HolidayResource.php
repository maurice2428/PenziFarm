<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\HolidayResource\Pages;
use App\Models\HR\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    use HasResourcePermissions;

    protected static string $permissionPrefix = 'holidays';

    protected static ?string $model = Holiday::class;

    //protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Human Resource';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Holiday Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\DatePicker::make('holiday_date')
                        ->required(),

                    Forms\Components\Select::make('type')
                        ->options([
                            'public' => 'Public',
                            'company' => 'Company',
                            'departmental' => 'Departmental',
                        ])
                        ->default('public')
                        ->native(false),

                    Forms\Components\Textarea::make('description')
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_recurring_yearly')
                        ->default(false),

                    Forms\Components\Toggle::make('applies_to_all')
                        ->default(true),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('holiday_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_recurring_yearly')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->can('edit holidays')),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can('delete holidays')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete holidays')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
