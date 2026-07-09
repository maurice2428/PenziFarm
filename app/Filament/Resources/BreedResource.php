<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BreedResource\Pages;
use App\Models\Breed;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
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
        return $form->schema([
            Forms\Components\Section::make('Breed Details')
                ->description(
                    'This breed setup powers animal classification and automatic tag generation.'
                )
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
                        ->placeholder('Example: Dorper')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (
                            $state,
                            Forms\Set $set
                        ): void {
                            $set(
                                'prefix',
                                Breed::generatePrefix($state)
                            );
                        })
                        ->unique(
                            table: 'breeds',
                            column: 'breed_name',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (
                                Unique $rule,
                                Forms\Get $get
                            ): Unique => $rule->where(
                                'parent_category',
                                $get('parent_category')
                            )
                        )
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('prefix')
                        ->label('Breed Prefix')
                        ->readOnly()
                        ->required()
                        ->dehydrated()
                        ->maxLength(3)
                        ->placeholder('Generated automatically')
                        ->helperText(
                            'Automatically generated from the first three letters of the breed name.'
                        )
                        ->afterStateHydrated(function (
                            $state,
                            Forms\Set $set,
                            Forms\Get $get
                        ): void {
                            $generatedPrefix = Breed::generatePrefix(
                                $get('breed_name')
                            );

                            if (
                                filled($generatedPrefix)
                                && strtoupper((string) $state) !== $generatedPrefix
                            ) {
                                $set('prefix', $generatedPrefix);
                            }
                        })
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active Breed')
                        ->default(true)
                        ->inline(false)
                        ->helperText(
                            'Only active breeds should be available for new animal registration.'
                        )
                        ->columnSpan(1),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->placeholder(
                            'Brief breed notes, production characteristics, adaptation, or remarks...'
                        )
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
                        ->default(fn (): ?int => auth()->id())
                        ->dehydrated(),
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
                    ->defaultImageUrl(
                        url('/images/placeholder-animal.png')
                    ),

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
                    ->label('Description')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->description(function (Breed $record): string {
                        return $record->creator?->getRoleNames()?->first()
                            ?? 'No role';
                    })
                    ->badge()
                    ->color(function (Breed $record): string {
                        $role = $record->creator?->getRoleNames()?->first();

                        return match ($role) {
                            'Administrator' => 'danger',
                            'Admin' => 'danger',
                            'Manager' => 'success',
                            'Finance' => 'warning',
                            'Veterinary Officer' => 'info',
                            'Vet' => 'info',
                            default => 'gray',
                        };
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('view users') ?? false
                    ),

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
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('edit breeds') ?? false
                    ),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('delete breeds') ?? false
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printBreedsPdf')
                        ->label('Print PDF')
                        ->icon('heroicon-o-printer')
                        ->color('warning')
                        ->deselectRecordsAfterCompletion()
                        ->visible(
                            fn (): bool =>
                                auth()->user()?->can('export breeds') ?? false
                        )
                        ->action(function (Collection $records) {
                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title('No breeds selected.')
                                    ->warning()
                                    ->send();

                                return null;
                            }

                            $user = auth()->user();
                            $generatedByRole = $user?->getRoleNames()?->first()
                                ?? 'User';
                            $farmName = setting(
                                'farm.name',
                                'Penzi Farm'
                            );
                            $now = now('Africa/Nairobi');

                            $verificationText = $farmName
                                . ' Breed Report | Generated by: '
                                . ($user?->name ?? 'System')
                                . ' (' . $generatedByRole . ')'
                                . ' | Date: '
                                . $now->format('Y-m-d H:i:s')
                                . ' EAT'
                                . ' | Total Records: '
                                . $records->count();

                            $qrImage = null;

                            try {
                                $qrImage = 'data:image/png;base64,'
                                    . base64_encode(
                                        QrCode::format('png')
                                            ->size(140)
                                            ->margin(1)
                                            ->generate($verificationText)
                                    );
                            } catch (\Throwable $exception) {
                                Log::error(
                                    'Breed report QR generation failed.',
                                    [
                                        'message' => $exception->getMessage(),
                                    ]
                                );
                            }

                            $pdf = Pdf::loadView(
                                'pdf.breeds-bulk-report',
                                [
                                    'breeds' => $records->load(
                                        'creator.roles'
                                    ),
                                    'generatedBy' => $user,
                                    'generatedByRole' => $generatedByRole,
                                    'verificationText' => $verificationText,
                                    'qrImage' => $qrImage,
                                ]
                            )->setPaper('a4', 'landscape');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'breed-bulk-report-'
                                    . now('Africa/Nairobi')->format(
                                        'Ymd_His'
                                    )
                                    . '.pdf'
                            );
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(
                            fn (): bool =>
                                auth()->user()?->can('delete breeds') ?? false
                        ),
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
