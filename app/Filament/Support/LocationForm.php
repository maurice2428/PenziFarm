<?php

namespace App\Filament\Support;

use App\Filament\Forms\Components\LocationMapPicker;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;

class LocationForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\Section::make('Station Profile')
                ->description('Register a farm station, paddock, shed, pen, quarantine area or other livestock location.')
                ->icon('heroicon-o-map-pin')
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                ])
                ->schema(self::stationProfileFields()),

            Forms\Components\Section::make('Map Pin & Address Intelligence')
                ->description('Click the map or drag the marker. The system captures the coordinates and tries to fill location details.')
                ->icon('heroicon-o-map')
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                ])
                ->schema([
                    self::mapPicker()->columnSpanFull(),
                    ...self::addressFields(),
                ]),

            Forms\Components\Section::make('Internal Notes')
                ->description('Optional operational information for this location.')
                ->icon('heroicon-o-document-text')
                ->schema([
                    self::notesField(),
                ]),
        ];
    }

    /*
     * Used only inside Animal → Current Location → Add Location.
     * A wizard keeps the right-side slide-over compact and responsive.
     */
    public static function quickCreateSchema(): array
    {
        return [
            Wizard::make([
                Step::make('Map')
                    ->description('Place the station on the map.')
                    ->icon('heroicon-o-map')
                    ->schema([
                        Forms\Components\Section::make('Map Location')
                            ->description('Click the map, drag the marker, or use your current device position.')
                            ->schema([
                                self::mapPicker()->columnSpanFull(),
                            ]),
                    ]),

                Step::make('Station Profile')
                    ->description('Name and classify the farm station.')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\Section::make('Station Information')
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                            ])
                            ->schema(self::stationProfileFields()),
                    ]),

                Step::make('Address & Notes')
                    ->description('Review captured location details.')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Section::make('Location Details')
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                            ])
                            ->schema([
                                ...self::addressFields(),
                            ]),

                        Forms\Components\Section::make('Internal Notes')
                            ->schema([
                                self::notesField(),
                            ]),
                    ]),
            ])
                ->columnSpanFull(),
        ];
    }

    private static function stationProfileFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Location Name')
                ->placeholder('Example: Muserechi, Main Sheep Shed, Quarantine Pen')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('code')
                ->label('Location Code')
                ->placeholder('Example: MUS, SHED-01')
                ->maxLength(50)
                ->unique(ignoreRecord: true),

            Forms\Components\Select::make('type')
                ->label('Location Type')
                ->options([
                    'station' => 'Farm Station',
                    'paddock' => 'Paddock',
                    'shed' => 'Shed / Barn',
                    'pen' => 'Pen / Housing Unit',
                    'quarantine' => 'Quarantine Area',
                    'grazing' => 'Grazing Area',
                    'store' => 'Store / Feed Area',
                    'other' => 'Other',
                ])
                ->default('station')
                ->required()
                ->native(false),

            Forms\Components\Toggle::make('is_active')
                ->label('Active Location')
                ->default(true)
                ->helperText('Only active locations can be selected when registering animals.'),

            Forms\Components\Toggle::make('is_default')
                ->label('Default Animal Location')
                ->default(false)
                ->helperText('Only one location should be the default.'),
        ];
    }

    private static function addressFields(): array
    {
        return [
            Forms\Components\TextInput::make('latitude')
                ->label('Latitude')
                ->numeric(),

            Forms\Components\TextInput::make('longitude')
                ->label('Longitude')
                ->numeric(),

            Forms\Components\TextInput::make('county')
                ->label('County')
                ->helperText('Auto-filled from the map where available.'),

            Forms\Components\TextInput::make('sub_county')
                ->label('Sub-County / Area')
                ->helperText('Auto-filled from the map where available.'),

            Forms\Components\TextInput::make('ward')
                ->label('Ward / Neighbourhood')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('address')
                ->label('Address')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('place_label')
                ->label('Map Place Label')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    private static function notesField(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('notes')
            ->label('Notes')
            ->rows(4)
            ->placeholder('Capacity, access instructions, stock movement guidance, manager notes, etc.')
            ->columnSpanFull();
    }

    private static function mapPicker(): LocationMapPicker
    {
        return LocationMapPicker::make('map_picker')
            ->label('Select Location on Map')
            ->dehydrated(false)
            ->live()
            ->afterStateHydrated(function (LocationMapPicker $component, $state, Forms\Get $get): void {
                $component->state([
                    'latitude' => $get('latitude'),
                    'longitude' => $get('longitude'),
                    'county' => $get('county'),
                    'sub_county' => $get('sub_county'),
                    'ward' => $get('ward'),
                    'address' => $get('address'),
                    'place_label' => $get('place_label'),
                ]);
            })
            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                if (! is_array($state)) {
                    return;
                }

                $set('latitude', $state['latitude'] ?? null);
                $set('longitude', $state['longitude'] ?? null);
                $set('county', $state['county'] ?? null);
                $set('sub_county', $state['sub_county'] ?? null);
                $set('ward', $state['ward'] ?? null);
                $set('address', $state['address'] ?? null);
                $set('place_label', $state['place_label'] ?? null);
            });
    }
}
