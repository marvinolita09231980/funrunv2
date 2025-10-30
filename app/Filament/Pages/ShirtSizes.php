<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Tables\Concerns\InteractsWithTable;

class ShirtSizes extends Page implements HasTable
{
   
    use InteractsWithTable;

    //protected static ?string $navigationIcon = 'heroicon-o-tshirt';
    protected string $view = 'filament.pages.shirt-sizes';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subcategory::query()
                    ->select([
                        'subcategories.*',
                        DB::raw("(SELECT COUNT(*) FROM participants 
                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                  AND participants.subDescription = subcategories.subDescription 
                                  AND participants.shirtSize = 'XS' 
                                  AND participants.year = '2025') AS xs"),
                        DB::raw("(SELECT COUNT(*) FROM participants 
                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                  AND participants.subDescription = subcategories.subDescription 
                                  AND participants.shirtSize = 'Small' 
                                  AND participants.year = '2025') AS small"),
                        DB::raw("(SELECT COUNT(*) FROM participants 
                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                  AND participants.subDescription = subcategories.subDescription 
                                  AND participants.shirtSize = 'Medium' 
                                  AND participants.year = '2025') AS medium"),
                        DB::raw("(SELECT COUNT(*) FROM participants 
                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                  AND participants.subDescription = subcategories.subDescription 
                                  AND participants.shirtSize = 'Large' 
                                  AND participants.year = '2025') AS large"),
                        DB::raw("(SELECT COUNT(*) FROM participants 
                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                  AND participants.subDescription = subcategories.subDescription 
                                  AND participants.shirtSize = 'XL' 
                                  AND participants.year = '2025') AS xl"),
                        DB::raw("(SELECT COUNT(*) FROM participants 
                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                  AND participants.subDescription = subcategories.subDescription 
                                  AND participants.shirtSize = '2XL' 
                                  AND participants.year = '2025') AS 2xl"),
                        DB::raw("(SELECT COUNT(*) FROM participants 
                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                  AND participants.subDescription = subcategories.subDescription 
                                  AND participants.shirtSize = '3XL' 
                                  AND participants.year = '2025') AS 3xl"),
                        DB::raw("(SELECT COUNT(*) FROM participants 
                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                  AND participants.subDescription = subcategories.subDescription 
                                  AND participants.shirtSize = '4XL' 
                                  AND participants.year = '2025') AS 4xl"),
                        DB::raw("(SELECT COUNT(*) FROM participants 
                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                  AND participants.subDescription = subcategories.subDescription 
                                  AND participants.shirtSize = '5XL' 
                                  AND participants.year = '2025') AS 5xl"),
                    ])
                    ->orderBy('categoryDescription')
            )
            ->columns([
                TextColumn::make('categoryDescription')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('subDescription')
                    ->label('Description')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('xs')->label('XS')->getStateUsing(fn($record) => $record->xs ?: '--')->summarize(Sum::make()->label('Total')),
                TextColumn::make('small')->label('S')->getStateUsing(fn($record) => $record->small ?: '--')->summarize(Sum::make()->label('Total')),
                TextColumn::make('medium')->label('M')->getStateUsing(fn($record) => $record->medium ?: '--')->summarize(Sum::make()->label('Total')),
                TextColumn::make('large')->label('L')->getStateUsing(fn($record) => $record->large ?: '--')->summarize(Sum::make()->label('Total')),
                TextColumn::make('xl')->label('XL')->getStateUsing(fn($record) => $record->xl ?: '--')->summarize(Sum::make()->label('Total')),
                TextColumn::make('2xl')->label('2XL')->getStateUsing(fn($record) => $record->{'2xl'} ?: '--')->summarize(Sum::make()->label('Total')),
                TextColumn::make('3xl')->label('3XL')->getStateUsing(fn($record) => $record->{'3xl'} ?: '--')->summarize(Sum::make()->label('Total')),
                TextColumn::make('4xl')->label('4XL')->getStateUsing(fn($record) => $record->{'4xl'} ?: '--')->summarize(Sum::make()->label('Total')),
                TextColumn::make('5xl')->label('5XL')->getStateUsing(fn($record) => $record->{'5xl'} ?: '--')->summarize(Sum::make()->label('Total')),
            ])
            ->striped();
    }
}
