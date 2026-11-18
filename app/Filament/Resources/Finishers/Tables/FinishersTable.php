<?php

namespace App\Filament\Resources\Finishers\Tables;

use Carbon\Carbon;
use App\Models\Finisher;
use App\Models\Participant;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class FinishersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['participant', 'award']))
            ->columns([
                TextColumn::make('mobile_finisher_summary')
                    ->label('Finisher')
                    ->getStateUsing(function (Finisher $record): string {
                        $middleInitial = filled($record->middleInitial)
                            ? ' ' . rtrim($record->middleInitial, '.') . '.'
                            : '';

                        return "{$record->lastName}, {$record->firstName}{$middleInitial}";
                    })
                    ->description(fn (Finisher $record): string => collect([
                        "Bib {$record->racebib}",
                        Participant::distanceLabel($record->distanceCategory),
                        filled($record->award?->name) ? "Award: {$record->award->name}" : null,
                        filled($record->finish_time)
                            ? 'Finish: '.Carbon::parse($record->finish_time)->format('h:i:s A')
                            : null,
                        filled($record->categoryDescription)
                            ? "Category: {$record->categoryDescription}"
                            : null,
                        filled($record->subDescription)
                            ? "Organization: {$record->subDescription}"
                            : null,
                        filled($record->created_by) ? "Recorded by {$record->created_by}" : null,
                    ])->filter()->join(' • '))
                    ->weight('bold')
                    ->searchable(['firstName', 'lastName', 'racebib'])
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('desktop_finisher_name')
                    ->label('Finisher')
                    ->getStateUsing(function (Finisher $record): string {
                        $middleInitial = filled($record->middleInitial)
                            ? ' ' . rtrim($record->middleInitial, '.') . '.'
                            : '';

                        return "{$record->lastName}, {$record->firstName}{$middleInitial}";
                    })
                    ->weight('bold')
                    ->searchable(['firstName', 'lastName'])
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('desktop_racebib')
                    ->label('Race Bib')
                    ->getStateUsing(fn (Finisher $record): string => (string) $record->racebib)
                    ->searchable(['racebib'])
                    ->weight('bold')
                    ->badge()
                    ->color('gray')
                    ->visibleFrom('md'),
                TextColumn::make('desktop_distance')
                    ->label('Distance')
                    ->getStateUsing(fn (Finisher $record): ?string => $record->distanceCategory)
                    ->formatStateUsing(fn (?string $state): string => Participant::distanceLabel($state))
                    ->badge()
                    ->color('info')
                    ->visibleFrom('md'),
                TextColumn::make('desktop_award')
                    ->label('Award')
                    ->getStateUsing(fn (Finisher $record): ?string => $record->award?->name)
                    ->placeholder('—')
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('desktop_finish_time')
                    ->label('Finish Time')
                    ->getStateUsing(fn (Finisher $record): mixed => $record->finish_time)
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('h:i:s A'))
                    ->fontFamily('mono')
                    ->sortable(['finish_time'])
                    ->visibleFrom('md'),
                TextColumn::make('desktop_category')
                    ->label('Category')
                    ->getStateUsing(fn (Finisher $record): ?string => $record->categoryDescription)
                    ->searchable(['categoryDescription'])
                    ->placeholder('—')
                    ->badge()
                    ->color('gray')
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('desktop_organization')
                    ->label('Organization')
                    ->getStateUsing(fn (Finisher $record): ?string => $record->subDescription)
                    ->placeholder('—')
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('desktop_recorded_by')
                    ->label('Recorded By')
                    ->getStateUsing(fn (Finisher $record): ?string => $record->created_by)
                    ->wrap()
                    ->visibleFrom('md'),
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
                SelectFilter::make('runner_group')
                    ->label('Runner Group')
                    ->placeholder('All runner groups')
                    ->options([
                        'padac' => 'PADAC Member',
                        'rpwuds' => 'RPWUDS',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $group = $data['value'] ?? null;

                        if (! in_array($group, ['padac', 'rpwuds'], true)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'participant',
                            fn (Builder $participantQuery): Builder => $participantQuery->inRunnerGroup($group),
                        );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'md' => 2,
            ])
            ->deferFilters(false)
            ->heading('Recorded Finishers')
            ->description('Review race results, awards, and runner classifications for the selected event year.')
            ->defaultSort('finish_time')
            ->striped()
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon('heroicon-o-flag')
            ->emptyStateHeading('No finishers recorded')
            ->emptyStateDescription('No runners match the selected year and runner group.')
            ->recordActions([
                EditAction::make()
                ->iconButton()
                ->tooltip('Edit finisher')
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
