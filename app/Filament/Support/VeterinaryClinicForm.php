<?php

namespace App\Filament\Support;

use App\Models\VeterinaryClinic;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;

class VeterinaryClinicForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\Section::make('Clinic / Laboratory Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Clinic / Laboratory Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('code')
                        ->label('Reference Code')
                        ->maxLength(50)
                        ->live(onBlur: true)
                        ->afterStateUpdated(
                            fn ($state, Forms\Set $set) =>
                            $set('code', filled($state)
                                ? strtoupper(trim((string) $state))
                                : null)
                        )
                        ->unique(
                            table: 'veterinary_clinics',
                            column: 'code',
                            ignoreRecord: true
                        ),

                    Forms\Components\Select::make('type')
                        ->label('Service Type')
                        ->options([
                            'Veterinary Clinic' => 'Veterinary Clinic',
                            'Laboratory' => 'Laboratory',
                            'Veterinary Clinic & Laboratory' => 'Veterinary Clinic & Laboratory',
                            'Mobile Veterinary Service' => 'Mobile Veterinary Service',
                            'Government Veterinary Office' => 'Government Veterinary Office',
                            'Other' => 'Other',
                        ])
                        ->default('Veterinary Clinic')
                        ->required(),

                    Forms\Components\TextInput::make('contact_person')
                        ->label('Contact Person')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Phone Number')
                        ->tel()
                        ->maxLength(50),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('county')
                        ->label('County')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('sub_county')
                        ->label('Sub County / Area')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('address')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active and Available for Lab Requests')
                        ->default(true)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    public static function quickCreateSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Clinic / Laboratory Name')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('type')
                ->label('Service Type')
                ->options([
                    'Veterinary Clinic' => 'Veterinary Clinic',
                    'Laboratory' => 'Laboratory',
                    'Veterinary Clinic & Laboratory' => 'Veterinary Clinic & Laboratory',
                    'Mobile Veterinary Service' => 'Mobile Veterinary Service',
                    'Government Veterinary Office' => 'Government Veterinary Office',
                    'Other' => 'Other',
                ])
                ->default('Veterinary Clinic')
                ->required(),

            Forms\Components\TextInput::make('contact_person')
                ->label('Contact Person')
                ->maxLength(255),

            Forms\Components\TextInput::make('phone')
                ->label('Phone Number')
                ->tel()
                ->maxLength(50),

            Forms\Components\TextInput::make('email')
                ->email()
                ->maxLength(255),

            Forms\Components\TextInput::make('county')
                ->label('County')
                ->maxLength(255),

            Forms\Components\TextInput::make('sub_county')
                ->label('Sub County / Area')
                ->maxLength(255),

            Forms\Components\Textarea::make('address')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ];
    }

    public static function select(): Forms\Components\Select
    {
        return Forms\Components\Select::make('veterinary_clinic_id')
            ->label('Veterinary Clinic / Laboratory')
            ->relationship(
                name: 'veterinaryClinic',
                titleAttribute: 'name',
                modifyQueryUsing: fn (Builder $query) => $query
                    ->active()
                    ->orderBy('name')
            )
            ->getOptionLabelFromRecordUsing(
                fn (VeterinaryClinic $clinic): string => $clinic->display_name
            )
            ->searchable()
            ->preload()
            ->native(false)
            ->required()
            ->live()
            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                $clinic = VeterinaryClinic::find($state);

                $set('clinic_name', $clinic?->name);
            })
            ->hintAction(
                FormAction::make('refreshVeterinaryClinics')
                    ->label('Refresh')
                    ->icon('heroicon-m-arrow-path')
                    ->color('gray')
                    ->tooltip('Refresh clinic and laboratory options')
                    ->action(function (Forms\Get $get, Forms\Set $set): void {
                        $set(
                            'veterinary_clinic_id',
                            $get('veterinary_clinic_id')
                        );

                        Notification::make()
                            ->success()
                            ->title('Clinic and laboratory options refreshed')
                            ->send();
                    })
            )
            ->createOptionForm(static::quickCreateSchema())
            ->createOptionUsing(function (array $data): int {
                $data['created_by'] = auth()->id();
                $data['updated_by'] = auth()->id();
                $data['is_active'] = $data['is_active'] ?? true;

                return VeterinaryClinic::create($data)->getKey();
            })
            ->createOptionAction(
                fn (FormAction $action): FormAction => $action
                    ->label('Add Clinic / Laboratory')
                    ->icon('heroicon-m-plus')
                    ->slideOver()
                    ->modalWidth(MaxWidth::TwoExtraLarge)
                    ->stickyModalFooter()
                    ->modalHeading('Add Veterinary Clinic / Laboratory')
                    ->modalDescription(
                        'Create a clinic or laboratory without leaving this lab request.'
                    )
                    ->modalSubmitActionLabel('Save Clinic / Laboratory')
            );
    }
}
