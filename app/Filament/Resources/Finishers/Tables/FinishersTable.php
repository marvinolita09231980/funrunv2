<?php

namespace App\Filament\Resources\Finishers\Tables;

use Carbon\Carbon;
use App\Models\Finisher;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class FinishersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('participant'))
            ->columns([
                Stack::make([
                    TextColumn::make('finisher_name')
                        ->label('Finisher')
                        ->getStateUsing(function (Finisher $record): string {
                            $middleInitial = filled($record->middleInitial)
                                ? ' ' . rtrim($record->middleInitial, '.') . '.'
                                : '';

                            return "{$record->lastName}, {$record->firstName}{$middleInitial}";
                        })
                        ->weight('bold')
                        ->searchable(['firstName', 'lastName'])
                        ->wrap(),
                    Grid::make(['default' => 2])
                        ->schema([
                            TextColumn::make('racebib')
                                ->label('Race Bib')
                                ->prefix('Bib: ')
                                ->searchable()
                                ->weight('bold'),
                            TextColumn::make('finisher_rank')
                                ->label('Rank')
                                ->prefix('Rank: ')
                                ->placeholder('Rank: —')
                                ->sortable(),
                        ]),
                    TextColumn::make('distanceCategory')
                        ->label('Distance')
                        ->badge()
                        ->color('info'),
                    TextColumn::make('participant.year')
                        ->label('Year')
                        ->prefix('Event year: ')
                        ->placeholder('Event year: —'),
                    TextColumn::make('finish_time')
                        ->label('Finish Time')
                        ->prefix('Finish time: ')
                        ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('H:i:s')),
                    TextColumn::make('subDescription')
                        ->prefix('Organization: ')
                        ->placeholder('Organization: —')
                        ->wrap(),
                    TextColumn::make('created_by')
                        ->label('Recorded By')
                        ->prefix('Recorded by: '),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
             ->recordUrl(fn ($record) => 
              
                $record->created_by === Filament::auth()->user()->username 
                    ? route('filament.admin.resources.finishers.edit', $record) 
                    : null
            )
            ->filters([
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(
                        collect(range((int) date('Y'), (int) date('Y') - 5))
                            ->mapWithKeys(fn (int $year): array => [$year => $year])
                            ->all()
                    )
                    ->default(date('Y'))
                    ->selectablePlaceholder(false)
                    ->indicateUsing(fn (): array => [])
                    ->query(function (Builder $query, array $data): Builder {
                        $year = (int) ($data['value'] ?? date('Y'));

                        return $query->whereHas(
                            'participant',
                            fn (Builder $participantQuery): Builder => $participantQuery->where('year', $year)
                        );
                    }),
            ], layout: FiltersLayout::Hidden)
            ->deferFilters(false)
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
