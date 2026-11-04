<?php

namespace App\Filament\Pages;

use App\Models\Participant;
use App\Models\Subcategory;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class Dashboard extends BaseDashboard implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.dashboard';

    public int $selectedYear;

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');

        $user = Filament::auth()->user();

        if ($user && str_contains($user->username, 'marshal')) {
            $this->redirectRoute('filament.admin.resources.finishers.index');
        }
    }

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
                $year = $this->selectedYear;

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
                        DB::raw("(SELECT COUNT(*)
                            FROM participants
                            WHERE {$subcategoryMatch}
                              AND participants.year = '{$year}') AS registered"),
                        DB::raw("(SELECT COUNT(*)
                            FROM participants
                            WHERE {$subcategoryMatch}
                              AND participants.year = '{$year}'
                              AND LOWER(TRIM(participants.distanceCategory)) = '3km') AS count_3k"),
                        DB::raw("(SELECT COUNT(*)
                            FROM participants
                            WHERE {$subcategoryMatch}
                              AND participants.year = '{$year}'
                              AND LOWER(TRIM(participants.distanceCategory)) = '5km') AS count_5k"),
                        DB::raw("(SELECT COUNT(*)
                            FROM participants
                            WHERE {$subcategoryMatch}
                              AND participants.year = '{$year}'
                              AND LOWER(TRIM(participants.distanceCategory)) = '10km') AS count_10k"),
                    ])
                    ->orderBy('subcategories.categoryDescription');
            })
            ->columns([
                TextColumn::make('mobile_summary')
                    ->label('Category Overview')
                    ->getStateUsing(fn ($record): string => $record->categoryDescription . ' — ' . $record->subDescription)
                    ->description(fn ($record): string => collect([
                        'Allotted: ' . number_format((int) $record->nop),
                        'Registered: ' . number_format((int) $record->registered),
                        '3 KM: ' . number_format((int) $record->count_3k),
                        '5 KM: ' . number_format((int) $record->count_5k),
                        '10 KM: ' . number_format((int) $record->count_10k),
                    ])->join(' • '))
                    ->weight('bold')
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('categoryDescription')
                    ->label('Category')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('subDescription')
                    ->label('Description')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('nop')
                    ->label('Allot.')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '--' : $state)
                    ->visibleFrom('md'),
                TextColumn::make('registered')
                    ->label('Registered')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '--' : $state)
                    ->visibleFrom('md'),
                TextColumn::make('count_3k')
                    ->label('3 KM')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '--' : $state)
                    ->visibleFrom('md'),
                TextColumn::make('count_5k')
                    ->label('5 KM')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '--' : $state)
                    ->visibleFrom('md'),
                TextColumn::make('count_10k')
                    ->label('10 KM')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '--' : $state)
                    ->visibleFrom('md'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function getDashboardStats(): array
    {
        $year = $this->selectedYear;

        $counts = Participant::query()
            ->where('year', $year)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(distanceCategory)) = '3km' THEN 1 ELSE 0 END) AS total_3k")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(distanceCategory)) = '5km' THEN 1 ELSE 0 END) AS total_5k")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(distanceCategory)) = '10km' THEN 1 ELSE 0 END) AS total_10k")
            ->first();

        $allocated = (int) Subcategory::query()->sum('nop');
        $registered = (int) ($counts->total ?? 0);
        $capacityPercentage = $allocated > 0
            ? min(100, (int) round(($registered / $allocated) * 100))
            : 0;

        return [
            'year' => $year,
            'registered' => $registered,
            'total_3k' => (int) ($counts->total_3k ?? 0),
            'total_5k' => (int) ($counts->total_5k ?? 0),
            'total_10k' => (int) ($counts->total_10k ?? 0),
            'allocated' => $allocated,
            'available' => max(0, $allocated - $registered),
            'capacity_percentage' => $capacityPercentage,
        ];
    }
}
