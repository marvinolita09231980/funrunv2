<?php

namespace App\Filament\Pages;

use Filament\Tables\Table;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Tables\Concerns\InteractsWithTable;

class Dashboard extends BaseDashboard implements HasTable
{
    use InteractsWithTable;

    // protected static $navigationIcon = 'heroicon-o-home';
    protected string $view = 'filament.pages.dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subcategory::query()
                    ->select([
                        'subcategories.*',
                        DB::raw("(SELECT COUNT(*) 
                                    FROM participants 
                                    WHERE participants.categoryDescription = subcategories.categoryDescription 
                                    " . "AND (" . 
                                        "subcategories.categoryDescription <> 'OPEN CATEGORY' AND participants.subDescription = subcategories.subDescription OR 
                                        subcategories.categoryDescription = 'OPEN CATEGORY'" . 
                                    ") 
                                    AND participants.year = '2025'
                                ) AS registered"),
                        DB::raw("(SELECT COUNT(*) 
                                    FROM participants 
                                    WHERE participants.categoryDescription = subcategories.categoryDescription 
                                    " . "AND (" . 
                                        "subcategories.categoryDescription <> 'OPEN CATEGORY' AND participants.subDescription = subcategories.subDescription OR 
                                        subcategories.categoryDescription = 'OPEN CATEGORY'" . 
                                    ") 
                                    AND participants.year = '2025'
                                    AND participants.distanceCategory = '3km'
                                ) AS count_3k"),
                        DB::raw("(SELECT COUNT(*) 
                                    FROM participants 
                                    WHERE participants.categoryDescription = subcategories.categoryDescription 
                                    " . "AND (" . 
                                        "subcategories.categoryDescription <> 'OPEN CATEGORY' AND participants.subDescription = subcategories.subDescription OR 
                                        subcategories.categoryDescription = 'OPEN CATEGORY'" . 
                                    ") 
                                    AND participants.year = '2025'
                                    AND participants.distanceCategory = '5km'
                                ) AS count_5k"),
                        DB::raw("(SELECT COUNT(*) 
                                    FROM participants 
                                    WHERE participants.categoryDescription = subcategories.categoryDescription 
                                    " . "AND (" . 
                                        "subcategories.categoryDescription <> 'OPEN CATEGORY' AND participants.subDescription = subcategories.subDescription OR 
                                        subcategories.categoryDescription = 'OPEN CATEGORY'" . 
                                    ") 
                                    AND participants.year = '2025'
                                    AND participants.distanceCategory = '10km'
                                ) AS count_10k"),
                    ])
                    ->orderBy('subcategories.categoryDescription', 'asc')
            )

            ->columns([
                 TextColumn::make('categoryDescription')
                     ->label('Category')
                     ->searchable(),
                 TextColumn::make('subDescription')
                     ->label('Description')
                     ->searchable(),
                TextColumn::make('nop')
                    ->label('Allot.')
                    ->sortable(),
                TextColumn::make('registered')
                    ->label('Registered')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '--' : $state)
                    ->summarize(Sum::make()->label('Total')),
                    
                TextColumn::make('count_3k')
                    ->label('3 KM')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '--' : $state)
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('count_5k')
                    ->label('5 KM')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '--' : $state)
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('count_10k')
                    ->label('10 KM')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '--' : $state)
                    ->summarize(Sum::make()->label('Total')),
            ])
            
            ->defaultSort('created_at', 'desc');
    }
}
