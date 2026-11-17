<?php

namespace App\Filament\Resources\Awards\Tables;

use App\Models\Award;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AwardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Award Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Award::CATEGORY_TOP_FINISHERS => 'success',
                        default => 'info',
                    })
                    ->sortable(),
                TextColumn::make('distance_category')
                    ->label('Distance')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(Award::categoryOptions()),
                SelectFilter::make('distance_category')
                    ->label('Distance')
                    ->options(Award::distanceOptions()),
            ])
            ->defaultSort('name')
            ->recordUrl(null)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Award $record): bool => ! $record->finishers()->exists()),
            ])
            ->toolbarActions([]);
    }
}
