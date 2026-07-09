<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\Concerns\HasResourcePermissions;
use App\Filament\Resources\HR\DepartmentResource\Pages;
use App\Models\HR\Department;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class DepartmentResource extends Resource
{
    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected static ?string $model = Department::class;
    // protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 2;

    use HasResourcePermissions;

    protected static string $permissionPrefix = 'departments';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Department Details')
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(50),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('employees_count')->counts('employees')->label('Employees'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->can('edit departments')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('delete departments')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
