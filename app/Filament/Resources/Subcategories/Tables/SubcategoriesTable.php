<?php

namespace App\Filament\Resources\Subcategories\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class SubcategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('categoryDescription')
                    ->label('Category')
                    ->searchable(),
                TextColumn::make('subDescription')
                    ->label('Description')
                    ->searchable(),
                TextColumn::make('nop')
                    ->label('No. Participants')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ;
    }
}
