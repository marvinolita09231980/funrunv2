<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use App\Models\Participant;
use App\Models\Subcategory;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Concerns\InteractsWithTable;

class ShirtSizes extends Page implements HasTable
{
    
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-archive-box';
    protected string $view = 'filament.pages.shirt-sizes';

    public static function shouldRegisterNavigation(): bool
    {
              $user = Filament::auth()->user();

              if (! $user) {
                  return false; 
              }

              return ! str_contains($user->username, 'marshal');
    }

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false; 
        }

        return ! str_contains($user->username, 'marshal');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $year = (int) ($this->getTableFilterState('year')['value'] ?? date('Y'));

                $participantCategory = <<<'SQL'
                    CASE
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'provincial government of davao de oro' THEN 'plgu'
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'open' THEN 'open category'
                        WHEN LOWER(TRIM(participants.categoryDescription)) IN ('lgu', 'local government unit of davao de oro') THEN 'madac-lgu'
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'ona'
                            AND LOWER(TRIM(participants.subDescription)) = 'pdea' THEN 'padac members'
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'ona' THEN 'national government agencies'
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'pnp' THEN 'padac members'
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'governor' THEN 'plgu'
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'marshal' THEN 'open category'
                        ELSE LOWER(TRIM(participants.categoryDescription))
                    END
                    SQL;

                $participantSubcategory = <<<'SQL'
                    CASE
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'pnp' THEN 'pnp'
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'governor' THEN 'provincial governor''s office'
                        WHEN LOWER(TRIM(participants.categoryDescription)) = 'marshal' THEN 'open category'
                        ELSE LOWER(TRIM(participants.subDescription))
                    END
                    SQL;

                $subcategoryMatch = "
                    ({$participantCategory}) = LOWER(TRIM(subcategories.categoryDescription))
                    AND (
                        LOWER(TRIM(subcategories.categoryDescription)) = 'open category'
                        OR ({$participantSubcategory}) = LOWER(TRIM(subcategories.subDescription))
                    )
                ";

                return Subcategory::query()
                    ->select([
                        'subcategories.*',

                        // XS
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = 'xs'
                                    AND participants.year = '{$year}') AS xs"),

                        // Small
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = 'small'
                                    AND participants.year = '{$year}') AS small"),

                        // Medium
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = 'medium'
                                    AND participants.year = '{$year}') AS medium"),

                        // Large
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = 'large'
                                    AND participants.year = '{$year}') AS large"),

                        // XL
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = 'xl'
                                    AND participants.year = '{$year}') AS xl"),

                        // 2XL
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = '2xl'
                                    AND participants.year = '{$year}') AS 2xl"),

                        // 3XL
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = '3xl'
                                    AND participants.year = '{$year}') AS 3xl"),

                        // 4XL
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = '4xl'
                                    AND participants.year = '{$year}') AS 4xl"),

                        // 5XL
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = '5xl'
                                    AND participants.year = '{$year}') AS 5xl"),
                    ])
                    ->orderBy('categoryDescription');
            })
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
                    ->query(fn ($query) => $query),
            ], layout: FiltersLayout::Hidden)
            ->deferFilters(false)


            ->columns([
                TextColumn::make('mobile_size_summary')
                    ->label('Organization Shirt Sizes')
                    ->getStateUsing(fn ($record): string => $record->categoryDescription . ' — ' . $record->subDescription)
                    ->description(fn ($record): string => collect([
                        'XS: ' . number_format((int) $record->xs),
                        'S: ' . number_format((int) $record->small),
                        'M: ' . number_format((int) $record->medium),
                        'L: ' . number_format((int) $record->large),
                        'XL: ' . number_format((int) $record->xl),
                        '2XL: ' . number_format((int) $record->{'2xl'}),
                        '3XL: ' . number_format((int) $record->{'3xl'}),
                        '4XL: ' . number_format((int) $record->{'4xl'}),
                        '5XL: ' . number_format((int) $record->{'5xl'}),
                    ])->join(' • '))
                    ->weight('bold')
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('categoryDescription')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->visibleFrom('md'),

                TextColumn::make('subDescription')
                    ->label('Description')
                    ->sortable()
                    ->searchable()
                    ->visibleFrom('md'),

                TextColumn::make('xs')->label('XS')->getStateUsing(fn($record) => $record->xs ?: '--')->visibleFrom('md'),
                TextColumn::make('small')->label('S')->getStateUsing(fn($record) => $record->small ?: '--')->visibleFrom('md'),
                TextColumn::make('medium')->label('M')->getStateUsing(fn($record) => $record->medium ?: '--')->visibleFrom('md'),
                TextColumn::make('large')->label('L')->getStateUsing(fn($record) => $record->large ?: '--')->visibleFrom('md'),
                TextColumn::make('xl')->label('XL')->getStateUsing(fn($record) => $record->xl ?: '--')->visibleFrom('md'),
                TextColumn::make('2xl')->label('2XL')->getStateUsing(fn($record) => $record->{'2xl'} ?: '--')->visibleFrom('md'),
                TextColumn::make('3xl')->label('3XL')->getStateUsing(fn($record) => $record->{'3xl'} ?: '--')->visibleFrom('md'),
                TextColumn::make('4xl')->label('4XL')->getStateUsing(fn($record) => $record->{'4xl'} ?: '--')->visibleFrom('md'),
                TextColumn::make('5xl')->label('5XL')->getStateUsing(fn($record) => $record->{'5xl'} ?: '--')->visibleFrom('md'),
               

          ])
            ->striped();
    }

    public function getShirtSizeStats(): array
    {
        $year = (int) ($this->getTableFilterState('year')['value'] ?? date('Y'));

        $counts = Participant::query()
            ->where('year', $year)
            ->selectRaw('LOWER(TRIM(shirtSize)) AS normalized_size, COUNT(*) AS total')
            ->groupByRaw('LOWER(TRIM(shirtSize))')
            ->pluck('total', 'normalized_size');

        $sizes = collect([
            'xs' => 'XS',
            'small' => 'S',
            'medium' => 'M',
            'large' => 'L',
            'xl' => 'XL',
            '2xl' => '2XL',
            '3xl' => '3XL',
            '4xl' => '4XL',
            '5xl' => '5XL',
        ])->map(fn (string $label, string $key): array => [
            'label' => $label,
            'count' => (int) ($counts[$key] ?? 0),
        ])->all();

        return [
            'year' => $year,
            'total' => collect($sizes)->sum('count'),
            'sizes' => $sizes,
        ];
    }
}
