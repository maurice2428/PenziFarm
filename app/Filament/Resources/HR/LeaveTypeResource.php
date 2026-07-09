<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\LeaveTypeResource\Pages;
use App\Models\HR\LeaveType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveTypeResource extends Resource
{
    use HasResourcePermissions;

    protected static string $permissionPrefix = 'leave types';

    protected static ?string $model = LeaveType::class;

    //protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Human Resource';

    protected static ?int $navigationSort = 7;

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
            Forms\Components\Section::make('Leave Type Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100),

                    Forms\Components\TextInput::make('days_allowed')
                        ->numeric()
                        ->default(0),

                    Forms\Components\Toggle::make('is_paid')
                        ->default(true),

                    Forms\Components\Select::make('gender_rule')
                        ->options([
                            'male' => 'Male',
                            'female' => 'Female',
                            'all' => 'All',
                        ])
                        ->native(false),

                    Forms\Components\Toggle::make('requires_attachment')
                        ->default(false),

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

                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_allowed')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean(),

                Tables\Columns\IconColumn::make('requires_attachment')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->can('edit leave types')),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can('delete leave types')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete leave types')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }
}
