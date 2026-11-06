<?php

namespace App\Filament\Pages;

use BackedEnum;
use App\Models\CategoryAllocation;
use Filament\Pages\Page;
use Filament\Actions\Action;
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
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdfReport')
                ->label('Download PDF Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(fn (): StreamedResponse => $this->downloadPdfReport()),
        ];
    }

    public function downloadPdfReport(): StreamedResponse
    {
        $year = (int) ($this->getTableFilterState('year')['value'] ?? date('Y'));
        $report = self::shirtSizeReportForYear($year);
        $pdfContent = Pdf::loadView('pdf.shirt-size-report', [
            'report' => $report,
            'logoDataUri' => $this->reportLogoDataUri(),
        ])
            ->setPaper('a4', 'landscape')
            ->output();

        return response()->streamDownload(
            static function () use ($pdfContent): void {
                echo $pdfContent;
            },
            "shirt-size-report-{$year}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
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
                        OR (subcategories.id < 0 AND (
                            NOT EXISTS (SELECT 1 FROM subcategories AS configured WHERE configured.categoryDescription = subcategories.categoryDescription)
                            OR COALESCE(participants.subDescription, '') = ''
                        ))
                    )
                ";

                return Subcategory::reportQuery($year)
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
                                    AND participants.year = '{$year}') AS `2xl`"),

                        // 3XL
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = '3xl'
                                    AND participants.year = '{$year}') AS `3xl`"),

                        // 4XL
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = '4xl'
                                    AND participants.year = '{$year}') AS `4xl`"),

                        // 5XL
                        DB::raw("(SELECT COUNT(*) 
                                  FROM participants 
                                  WHERE {$subcategoryMatch}
                                    AND LOWER(TRIM(participants.shirtSize)) = '5xl'
                                    AND participants.year = '{$year}') AS `5xl`"),
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
                    ->placeholder('No subcategory')
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

        return self::shirtSizeStatsForYear($year);
    }

    public static function shirtSizeStatsForYear(int $year): array
    {
        $distance = "REPLACE(LOWER(TRIM(distanceCategory)), ' ', '')";

        $counts = Participant::query()
            ->where('year', $year)
            ->selectRaw('LOWER(TRIM(shirtSize)) AS normalized_size, COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN {$distance} IN ('3k', '3km') THEN 1 ELSE 0 END) AS count_3k")
            ->selectRaw("SUM(CASE WHEN {$distance} IN ('5k', '5km') THEN 1 ELSE 0 END) AS count_5k")
            ->selectRaw("SUM(CASE WHEN {$distance} IN ('10k', '10km') THEN 1 ELSE 0 END) AS count_10k")
            ->groupByRaw('LOWER(TRIM(shirtSize))')
            ->get()
            ->keyBy('normalized_size');

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
        ])->map(function (string $label, string $key) use ($counts): array {
            $count = $counts->get($key);

            return [
                'label' => $label,
                'count' => (int) ($count?->total ?? 0),
                'distances' => [
                    '3km' => [
                        'label' => 'Rave Run (3K)',
                        'count' => (int) ($count?->count_3k ?? 0),
                    ],
                    '5km' => [
                        'label' => '5K',
                        'count' => (int) ($count?->count_5k ?? 0),
                    ],
                    '10km' => [
                        'label' => '10K',
                        'count' => (int) ($count?->count_10k ?? 0),
                    ],
                ],
            ];
        })->all();

        $distanceTotals = collect(['3km', '5km', '10km'])
            ->mapWithKeys(fn (string $distance): array => [
                $distance => collect($sizes)->sum(
                    fn (array $size): int => $size['distances'][$distance]['count'],
                ),
            ])
            ->all();

        return [
            'year' => $year,
            'total' => collect($sizes)->sum('count'),
            'distance_totals' => $distanceTotals,
            'sizes' => $sizes,
        ];
    }

    public static function shirtSizeReportForYear(int $year): array
    {
        $sizeLabels = self::sizeLabels();
        $distanceLabels = [
            '3km' => 'Rave Run (3K)',
            '5km' => '5K',
            '10km' => '10K',
        ];
        $counts = [];

        Participant::query()
            ->where('year', $year)
            ->get([
                'categoryDescription',
                'subDescription',
                'shirtSize',
                'distanceCategory',
            ])
            ->each(function (Participant $participant) use (&$counts): void {
                [$category, $subcategory] = self::normalizedCategoryAndSubcategory($participant);
                $size = self::normalizedShirtSize($participant->shirtSize);
                $distance = self::normalizedDistance($participant->distanceCategory);

                if (! $size || ! $distance) {
                    return;
                }

                $counts[$category][$subcategory][$distance][$size] =
                    ($counts[$category][$subcategory][$distance][$size] ?? 0) + 1;
                $counts[$category]['*'][$distance][$size] =
                    ($counts[$category]['*'][$distance][$size] ?? 0) + 1;
            });

        $categories = CategoryAllocation::query()
            ->with(['subcategories' => fn ($query) => $query->orderBy('subDescription')])
            ->orderBy('categoryDescription')
            ->get();
        $groups = [];

        foreach ($categories as $category) {
            $categoryKey = strtolower(trim((string) $category->categoryDescription));
            $subcategories = $category->subcategories;

            if ($categoryKey === 'open category' || $subcategories->isEmpty()) {
                $groups[] = self::buildReportGroup(
                    (string) $category->categoryDescription,
                    'No subcategory',
                    $counts[$categoryKey]['*'] ?? [],
                    $sizeLabels,
                    $distanceLabels,
                );

                continue;
            }

            $configuredSubcategories = [];

            foreach ($subcategories as $subcategory) {
                $subcategoryKey = strtolower(trim((string) $subcategory->subDescription));
                $configuredSubcategories[] = $subcategoryKey;
                $groups[] = self::buildReportGroup(
                    (string) $category->categoryDescription,
                    (string) $subcategory->subDescription,
                    $counts[$categoryKey][$subcategoryKey] ?? [],
                    $sizeLabels,
                    $distanceLabels,
                );
            }

            $otherCounts = collect($counts[$categoryKey] ?? [])
                ->except(['*', ...$configuredSubcategories])
                ->all();

            if ($otherCounts !== []) {
                $combined = [];

                foreach ($otherCounts as $distanceCounts) {
                    foreach ($distanceCounts as $distance => $sizes) {
                        foreach ($sizes as $size => $count) {
                            $combined[$distance][$size] =
                                ($combined[$distance][$size] ?? 0) + $count;
                        }
                    }
                }

                $groups[] = self::buildReportGroup(
                    (string) $category->categoryDescription,
                    'Other / Unassigned',
                    $combined,
                    $sizeLabels,
                    $distanceLabels,
                );
            }
        }

        $grandTotals = array_fill_keys(array_keys($sizeLabels), 0);

        foreach ($groups as $group) {
            foreach ($group['totals'] as $size => $count) {
                $grandTotals[$size] += $count;
            }
        }

        return [
            'year' => $year,
            'generated_at' => now('Asia/Shanghai')->format('F j, Y g:i A'),
            'size_labels' => $sizeLabels,
            'distance_labels' => $distanceLabels,
            'groups' => $groups,
            'grand_totals' => $grandTotals,
            'grand_total' => array_sum($grandTotals),
        ];
    }

    private static function buildReportGroup(
        string $category,
        string $subcategory,
        array $counts,
        array $sizeLabels,
        array $distanceLabels,
    ): array {
        $distances = [];
        $totals = array_fill_keys(array_keys($sizeLabels), 0);

        foreach ($distanceLabels as $distance => $label) {
            $sizes = [];

            foreach ($sizeLabels as $size => $sizeLabel) {
                $sizes[$size] = (int) ($counts[$distance][$size] ?? 0);
                $totals[$size] += $sizes[$size];
            }

            $distances[$distance] = [
                'label' => $label,
                'sizes' => $sizes,
                'total' => array_sum($sizes),
            ];
        }

        return [
            'category' => $category,
            'subcategory' => $subcategory,
            'distances' => $distances,
            'totals' => $totals,
            'total' => array_sum($totals),
        ];
    }

    private static function sizeLabels(): array
    {
        return [
            'xs' => 'XS',
            'small' => 'S',
            'medium' => 'M',
            'large' => 'L',
            'xl' => 'XL',
            '2xl' => '2XL',
            '3xl' => '3XL',
            '4xl' => '4XL',
            '5xl' => '5XL',
        ];
    }

    private static function normalizedShirtSize(?string $size): ?string
    {
        return match (strtolower(trim((string) $size))) {
            'xs', 'extra small' => 'xs',
            's', 'small' => 'small',
            'm', 'medium' => 'medium',
            'l', 'large' => 'large',
            'xl' => 'xl',
            '2xl', '2x', 'xxl' => '2xl',
            '3xl', '3x', 'xxxl' => '3xl',
            '4xl', '4x' => '4xl',
            '5xl', '5x' => '5xl',
            default => null,
        };
    }

    private static function normalizedDistance(?string $distance): ?string
    {
        return match (str_replace(' ', '', strtolower(trim((string) $distance)))) {
            '3k', '3km' => '3km',
            '5k', '5km' => '5km',
            '10k', '10km' => '10km',
            default => null,
        };
    }

    private static function normalizedCategoryAndSubcategory(Participant $participant): array
    {
        $category = strtolower(trim((string) $participant->categoryDescription));
        $subcategory = strtolower(trim((string) $participant->subDescription));

        return match ($category) {
            'provincial government of davao de oro' => ['plgu', $subcategory],
            'open' => ['open category', $subcategory],
            'lgu', 'local government unit of davao de oro' => ['madac-lgu', $subcategory],
            'ona' => [
                $subcategory === 'pdea' ? 'padac members' : 'national government agencies',
                $subcategory,
            ],
            'pnp' => ['padac members', 'pnp'],
            'governor' => ['plgu', "provincial governor's office"],
            'marshal' => ['open category', 'open category'],
            default => [$category, $subcategory],
        };
    }

    private function reportLogoDataUri(): ?string
    {
        $logoPath = public_path('images/login-logo.png');

        if (! is_file($logoPath) || ! is_readable($logoPath)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath));
    }
}
