<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Livestock\Animals as AnimalsCluster;
use App\Filament\Resources\VeterinaryClinicResource\Pages;
use App\Filament\Support\VeterinaryClinicForm;
use App\Models\VeterinaryClinic;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class VeterinaryClinicResource extends Resource
{
    protected static ?string $model = VeterinaryClinic::class;

    //protected static ?string $cluster = AnimalsCluster::class;

    protected static ?string $navigationGroup = 'Animal Health';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Vet Clinics';

    protected static ?string $modelLabel = 'Vet Clinic';

    protected static ?string $pluralModelLabel = 'Vet Clinics';

    protected static ?int $navigationSort = 32;

    public static function getNavigationBadge(): ?string
    {
        return (string) VeterinaryClinic::query()
            ->where('is_active', true)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::allowed('view veterinary clinics');
    }

    public static function canCreate(): bool
    {
        return static::allowed('create veterinary clinics');
    }

    public static function canEdit($record): bool
    {
        return static::allowed('edit veterinary clinics');
    }

    public static function canDelete($record): bool
    {
        return static::allowed('delete veterinary clinics');
    }

    private static function allowed(string $permission): bool
    {
        $user = auth()->user();

        return ($user?->can($permission) ?? false)
            || ($user?->hasAnyRole([
                'Administrator',
                'Admin',
                'Manager',
                'Veterinary Officer',
                'Vet',
            ]) ?? false);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(VeterinaryClinicForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('is_active', 'desc')
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Clinic / Laboratory')
                    ->description(
                        fn (VeterinaryClinic $record): string =>
                            $record->type
                            . ($record->county ? ' — ' . $record->county : '')
                    )
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->description(
                        fn (VeterinaryClinic $record): string =>
                            collect([
                                $record->phone,
                                $record->email,
                            ])->filter()->implode(' · ') ?: 'No contact details'
                    )
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('county')
                    ->label('County / Area')
                    ->description(
                        fn (VeterinaryClinic $record): string =>
                            $record->sub_county ?: 'No area recorded'
                    )
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('lab_requests_count')
                    ->label('Lab Requests')
                    ->counts('labRequests')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-no-symbol')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Service Type')
                    ->options([
                        'Veterinary Clinic' => 'Veterinary Clinic',
                        'Laboratory' => 'Laboratory',
                        'Veterinary Clinic & Laboratory' => 'Veterinary Clinic & Laboratory',
                        'Mobile Veterinary Service' => 'Mobile Veterinary Service',
                        'Government Veterinary Office' => 'Government Veterinary Office',
                        'Other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(
                        fn (VeterinaryClinic $record): string =>
                            $record->is_active ? 'Deactivate' : 'Activate'
                    )
                    ->icon(
                        fn (VeterinaryClinic $record): string =>
                            $record->is_active
                                ? 'heroicon-o-no-symbol'
                                : 'heroicon-o-check-circle'
                    )
                    ->color(
                        fn (VeterinaryClinic $record): string =>
                            $record->is_active ? 'warning' : 'success'
                    )
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('edit veterinary clinics') ?? false
                    )
                    ->action(function (VeterinaryClinic $record): void {
                        $record->update([
                            'is_active' => ! $record->is_active,
                            'updated_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title(
                                $record->name
                                . ($record->is_active ? ' activated.' : ' deactivated.')
                            )
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete clinic or laboratory?')
                    ->modalDescription(
                        'Historical lab-request snapshots remain preserved through the stored clinic name.'
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activateSelected')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(
                            fn (): bool =>
                                auth()->user()?->can('edit veterinary clinics') ?? false
                        )
                        ->action(function (Collection $records): void {
                            VeterinaryClinic::query()
                                ->whereIn('id', $records->pluck('id'))
                                ->update([
                                    'is_active' => true,
                                    'updated_by' => auth()->id(),
                                ]);

                            Notification::make()
                                ->success()
                                ->title($records->count() . ' clinic(s) activated.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivateSelected')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(
                            fn (): bool =>
                                auth()->user()?->can('edit veterinary clinics') ?? false
                        )
                        ->action(function (Collection $records): void {
                            VeterinaryClinic::query()
                                ->whereIn('id', $records->pluck('id'))
                                ->update([
                                    'is_active' => false,
                                    'updated_by' => auth()->id(),
                                ]);

                            Notification::make()
                                ->success()
                                ->title($records->count() . ' clinic(s) deactivated.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected clinics / laboratories?')
                        ->modalDescription(
                            'This removes the selected directory records. Existing lab requests retain their saved clinic name.'
                        )
                        ->visible(
                            fn (): bool =>
                                auth()->user()?->can('delete veterinary clinics') ?? false
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVeterinaryClinics::route('/'),
            'create' => Pages\CreateVeterinaryClinic::route('/create'),
            'edit' => Pages\EditVeterinaryClinic::route('/{record}/edit'),
        ];
    }
}
