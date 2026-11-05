<?php

namespace App\Filament\Resources\Subcategories\Tables;

use App\Models\Participant;
use App\Models\Subcategory;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class SubcategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->addSelect([
                'registered_participants_count' => Participant::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'participants.categoryDescription',
                        'subcategories.categoryDescription',
                    )
                    ->where('participants.year', now()->year)
                    ->where(function (Builder $participantQuery): void {
                        $participantQuery
                            ->whereRaw(
                                'UPPER(TRIM(subcategories.categoryDescription)) = ?',
                                ['OPEN CATEGORY'],
                            )
                            ->orWhereColumn(
                                'participants.subDescription',
                                'subcategories.subDescription',
                            );
                    }),
            ]))
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
                TextColumn::make('registered_participants_count')
                    ->label('No. Registered')
                    ->getStateUsing(fn (Subcategory $record): int => (int) (
                        $record->registered_participants_count
                        ?? $record->registeredParticipants()
                    ))
                    ->numeric(),
                TextColumn::make('focal_user_name')
                    ->label('Assigned Focal User'),
            ])
            ->recordUrl(null)
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
