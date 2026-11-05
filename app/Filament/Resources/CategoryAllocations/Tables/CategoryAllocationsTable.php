<?php

namespace App\Filament\Resources\CategoryAllocations\Tables;

use App\Models\CategoryAllocation;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryAllocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('categoryDescription')
                    ->label('Main Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assigned_ao_names')
                    ->label('Assigned AO')
                    ->placeholder('Not assigned')
                    ->wrap()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas(
                            'administrativeOfficers',
                            fn (Builder $officerQuery): Builder => $officerQuery
                                ->where('name', 'like', "%{$search}%"),
                        )),
                TextColumn::make('allocated_participants')
                    ->label('Allocated')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subcategories_sum_nop')
                    ->label('Distributed')
                    ->numeric()
                    ->default(0),
                TextColumn::make('remaining_participants')
                    ->label('Remaining')
                    ->getStateUsing(fn (CategoryAllocation $record): int => max(
                        0,
                        $record->allocated_participants - (int) $record->subcategories_sum_nop,
                    ))
                    ->numeric(),
            ])
            ->recordUrl(null)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
