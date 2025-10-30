<?php

namespace App\Filament\Pages;

use Filament\Tables\Table;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Tables\Concerns\InteractsWithTable;

class Dashboard extends BaseDashboard implements HasTable
{
    use InteractsWithTable;

    // protected static ?string $navigationIcon = 'heroicon-o-home';
    protected string $view = 'filament.pages.dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->query(Subcategory::query()
                            ->select([
                                        'subcategories.*',
                                        DB::raw("(SELECT COUNT(*) 
                                                FROM participants 
                                                WHERE participants.categoryDescription = subcategories.categoryDescription 
                                                AND participants.subDescription = subcategories.subDescription 
                                                AND participants.year = '2025') AS registered"),
                                    ])
            )
            ->columns([
                TextColumn::make('categoryDescription')
                    ->label('Category')
                    ->searchable(),
                TextColumn::make('subDescription')
                    ->label('Description')
                    ->searchable(),
                TextColumn::make('nop')
                    ->label('No. Participants')
                    ->sortable(),
                TextColumn::make('registered')
                    ->label('No. Registered')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
