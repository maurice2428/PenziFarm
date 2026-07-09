<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HealthProductResource\Pages;
use App\Models\HealthProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class HealthProductResource extends Resource
{
    protected static ?string $model = HealthProduct::class;

    protected static ?string $navigationGroup = 'Animal Health';

    protected static ?string $navigationLabel = 'Product(s)';

    protected static ?string $modelLabel = 'Health Product';

    protected static ?string $pluralModelLabel = 'Health Products';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Product Master')
                    ->description('Register vaccines, dewormers, dipping chemicals and treatment drugs before procurement.')
                    ->icon('heroicon-o-beaker')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-tag')
                            ->columnSpan(4),

                        Forms\Components\Select::make('type')
                            ->label('Product Type')
                            ->required()
                            ->native(false)
                            ->options([
                                'vaccine' => 'Vaccine',
                                'dewormer' => 'Dewormer',
                                'dip' => 'Dipping Chemical',
                                'treatment' => 'Treatment Drug',
                            ])
                            ->prefixIcon('heroicon-o-squares-2x2')
                            ->columnSpan(4),

                        Forms\Components\Select::make('species')
                            ->label('Target Species')
                            ->options([
                                'Sheep' => 'Sheep',
                                'Goat' => 'Goat',
                                'Cattle' => 'Cattle',
                                'Poultry' => 'Poultry',
                                'All' => 'All Species',
                            ])
                            ->searchable()
                            ->native(false)
                            ->prefixIcon('heroicon-o-globe-alt')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('dosage_per_animal')
                            ->label('Dosage Per Animal')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->prefixIcon('heroicon-o-scale')
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('dosage_unit')
                            ->label('Dosage Unit')
                            ->default('ml')
                            ->required()
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-beaker')
                            ->columnSpan(3),

                        Forms\Components\Select::make('administration_method')
                            ->label('Administration Method')
                            ->native(false)
                            ->options([
                                'injection' => 'Injection',
                                'oral' => 'Oral',
                                'dip' => 'Dip',
                                'spray' => 'Spray',
                                'pour_on' => 'Pour-on',
                                'topical' => 'Topical',
                                'other' => 'Other',
                            ])
                            ->prefixIcon('heroicon-o-sparkles')
                            ->columnSpan(3),

                        Forms\Components\Select::make('frequency')
                            ->label('Frequency')
                            ->native(false)
                            ->options([
                                'once' => 'Once',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'semi_annually' => 'Semi Annually',
                                'annually' => 'Annually',
                                'custom' => 'Custom',
                            ])
                            ->prefixIcon('heroicon-o-arrow-path')
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('frequency_days')
                            ->label('Frequency Days')
                            ->numeric()
                            ->helperText('Used when frequency is custom, or for calculating the next due date.')
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('batch_number')
                            ->label('Default Batch No.')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(4),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->default('active')
                            ->required()
                            ->native(false)
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'restricted' => 'Restricted',
                            ])
                            ->prefixIcon('heroicon-o-check-badge')
                            ->columnSpan(4),
                    ]),

                Forms\Components\Section::make('Reference & Safety')
                    ->description('Attach product labels, vet guides, PDFs, treatment manuals, prescriptions, or reference books.')
                    ->icon('heroicon-o-document-text')
                    ->columns(12)
                    ->schema([
                        Forms\Components\FileUpload::make('reference_document')
                            ->label('Reference Document')
                            ->disk('public')
                            ->directory('health-products/documents')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(10240)
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->helperText('Upload a PDF, image, Word document, product label, booklet, or vet guide.')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('precautions')
                            ->label('Precautions / Safety Notes')
                            ->rows(3)
                            ->columnSpan(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-beaker'),

                Tables\Columns\TextColumn::make('type_label')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($record): string => match ($record->type) {
                        'vaccine' => 'success',
                        'dewormer' => 'warning',
                        'dip' => 'info',
                        'treatment' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('species')
                    ->label('Species')
                    ->badge()
                    ->placeholder('All'),

                Tables\Columns\TextColumn::make('dosage_per_animal')
                    ->label('Dosage')
                    ->formatStateUsing(fn ($state, $record): string =>
                        number_format((float) $state, 2) . ' ' . ($record->dosage_unit ?? '')
                    ),

                Tables\Columns\TextColumn::make('frequency_label')
                    ->label('Frequency')
                    ->badge()
                    ->icon('heroicon-o-arrow-path'),

                Tables\Columns\TextColumn::make('inventoryItem.current_stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn ($record): string =>
                        $record->inventoryItem?->is_low_stock ? 'danger' : 'success'
                    )
                    ->formatStateUsing(fn ($state, $record): string =>
                        number_format((float) $state, 2) . ' ' . ($record->inventoryItem?->unit ?? '')
                    ),

                Tables\Columns\TextColumn::make('reference_document')
                    ->label('Reference Doc')
                    ->state(fn ($record): string =>
                        $record->reference_document
                            ? basename($record->reference_document)
                            : 'No document'
                    )
                    ->badge()
                    ->icon(fn ($record): string =>
                        $record->reference_document
                            ? 'heroicon-o-document-arrow-down'
                            : 'heroicon-o-x-circle'
                    )
                    ->color(fn ($record): string =>
                        $record->reference_document ? 'success' : 'gray'
                    )
                    ->url(fn ($record): ?string =>
                        $record->reference_document
                            ? Storage::disk('public')->url($record->reference_document)
                            : null
                    )
                    ->openUrlInNewTab()
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('openReferenceDocument')
                    ->label('Reference')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->url(fn ($record): ?string =>
                        $record->reference_document
                            ? Storage::disk('public')->url($record->reference_document)
                            : null
                    )
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => filled($record->reference_document)),

                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('6xl')
                    ->icon('heroicon-o-pencil-square'),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->emptyStateIcon('heroicon-o-beaker')
            ->emptyStateHeading('No health products registered')
            ->emptyStateDescription('Register vaccines, dewormers, dipping chemicals, and treatment drugs before using them in health records or procurement.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHealthProducts::route('/'),
        ];
    }
}
