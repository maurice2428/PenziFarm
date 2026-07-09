<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Animal;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CurrentAnimalsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Current Animals';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Animal::query()
                    ->with(['breed', 'location'])
                    ->where('status', 'Active')
                    ->where('is_archived', false)
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('tag_number')
                    ->label('Tag')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('breed.breed_name')
                    ->label('Breed')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('species'),

                Tables\Columns\TextColumn::make('sex')
                    ->badge(),

                Tables\Columns\TextColumn::make('purpose')
                    ->badge(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->default('-'),

                Tables\Columns\TextColumn::make('valuation_price')
                    ->money('KES'),
            ])
            ->paginated([5, 10, 25]);
    }
}
