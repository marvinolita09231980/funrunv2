<?php

namespace App\Filament\Resources\Finishers\Tables;

use Carbon\Carbon;
use App\Models\Finisher;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\DateColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\DateTimeColumn; // for datetime fields

class FinishersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('racebib')->label('Race Bib'),
                TextColumn::make('firstName')->label('First Name'),
                TextColumn::make('lastName')->label('Last Name'),
                TextColumn::make('middleInitial')->label('MI'),
                TextColumn::make('gender')->label('Gender'),
                TextColumn::make('distanceCategory')->label('Distance'),
                TextColumn::make('finish_time')
                ->label('Finish Time')
                ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('H:i:s')),
                TextColumn::make('created_by')
                ->label('Recorded By'),
                            
            ])
             ->recordUrl(fn ($record) => 
              
                $record->created_by === Filament::auth()->user()->username 
                    ? route('filament.admin.resources.finishers.edit', $record) 
                    : null
            )
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                ->visible(function ($record) {
                    $user = Filament::auth()->user();

                    return 
                        $record->created_by === $user->username
                        || $user->username === 'superadmin';
                }),
                // ->visible(fn ($record) => $record->created_by === Filament::auth()->user()->username),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
