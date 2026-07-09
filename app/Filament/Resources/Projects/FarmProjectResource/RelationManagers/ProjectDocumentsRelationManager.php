<?php

namespace App\Filament\Resources\Projects\FarmProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static ?string $modelLabel = 'Document';

    protected static ?string $pluralModelLabel = 'Documents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Document')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Document Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\Select::make('document_type')
                            ->label('Document Type')
                            ->options([
                                'quotation' => 'Quotation',
                                'invoice' => 'Invoice',
                                'receipt' => 'Receipt',
                                'contract' => 'Contract',
                                'permit' => 'Permit',
                                'drawing' => 'Drawing / Plan',
                                'photo' => 'Photo',
                                'report' => 'Report',
                                'other' => 'Other',
                            ])
                            ->default('other')
                            ->required()
                            ->searchable()
                            ->columnSpan(3),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('File')
                            ->directory('project-documents')
                            ->visibility('public')
                            ->required()
                            ->downloadable()
                            ->openable()
                            ->columnSpan(3),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('uploaded_by')
                            ->default(fn () => auth()->id()),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Document')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'other')->replace('_', ' ')->headline())
                    ->color(fn (?string $state): string => match ($state) {
                        'contract' => 'info',
                        'invoice', 'receipt' => 'success',
                        'permit' => 'warning',
                        'drawing', 'photo' => 'gray',
                        'report' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->placeholder('System')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Document Type')
                    ->options([
                        'quotation' => 'Quotation',
                        'invoice' => 'Invoice',
                        'receipt' => 'Receipt',
                        'contract' => 'Contract',
                        'permit' => 'Permit',
                        'drawing' => 'Drawing / Plan',
                        'photo' => 'Photo',
                        'report' => 'Report',
                        'other' => 'Other',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload Document')
                    ->icon('heroicon-o-arrow-up-tray'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->iconButton(),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete')
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
