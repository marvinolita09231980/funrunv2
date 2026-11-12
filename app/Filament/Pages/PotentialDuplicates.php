<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Participants\ParticipantResource;
use App\Models\Participant;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PotentialDuplicates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Potential Duplicates';

    protected static string|\UnitEnum|null $navigationGroup = 'Registration';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.potential-duplicates';

    public bool $duplicatesReady = false;

    /** @var array<int> */
    public array $potentialDuplicateIds = [];

    /** @var array<int, int> */
    public array $potentialDuplicateCounts = [];

    /** @var array{year: int, groups: int, records: int}|array{} */
    public array $potentialDuplicateStats = [];

    public ?int $potentialDuplicatesYear = null;

    public function loadPotentialDuplicates(): void
    {
        $this->preparePotentialDuplicates($this->selectedYear());
        $this->duplicatesReady = true;
    }

    public function updatedTableFilters(): void
    {
        if ($this->getTable()->hasDeferredFilters()) {
            $this->tableDeferredFilters = $this->tableFilters;
        }

        $this->handleTableFilterUpdates();

        if ($this->duplicatesReady) {
            $this->preparePotentialDuplicates($this->selectedYear());
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->isAdministrativeOfficer() || $user->hasAnyRole([
                'admin',
                'superadmin',
                'super_admin',
            ]));
    }

    public function getTitle(): string
    {
        return 'Potential Duplicate Participants';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdfReport')
                ->label('Download Printable PDF')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->action(fn (): StreamedResponse => $this->downloadPdfReport()),
        ];
    }

    public function downloadPdfReport(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        $year = $this->selectedYear();
        $generatedAt = CarbonImmutable::now();
        $duplicateData = self::findPotentialDuplicates($year);
        $records = Participant::query()
            ->whereKey($duplicateData['ids'])
            ->orderBy('lastName')
            ->orderBy('birthDate')
            ->orderBy('firstName')
            ->orderBy('id')
            ->get()
            ->map(function (Participant $participant) use ($generatedAt): array {
                $birthDate = filled($participant->birthDate)
                    ? CarbonImmutable::parse($participant->birthDate)
                    : null;

                return [
                    'id' => $participant->getKey(),
                    'name' => collect([
                        $participant->lastName.',',
                        $participant->firstName,
                        filled($participant->middleInitial)
                            ? rtrim((string) $participant->middleInitial, '.').'.'
                            : null,
                    ])->filter()->join(' '),
                    'birth_date' => $birthDate?->format('m/d/Y') ?? 'Not provided',
                    'age' => $birthDate && $birthDate->lessThanOrEqualTo($generatedAt)
                        ? (int) $birthDate->diffInYears($generatedAt)
                        : null,
                    'address' => filled($participant->address)
                        ? trim((string) $participant->address)
                        : 'Not provided',
                    'phone_number' => filled($participant->contactNumber)
                        ? trim((string) $participant->contactNumber)
                        : 'Not provided',
                    'category' => filled($participant->categoryDescription)
                        ? trim((string) $participant->categoryDescription)
                        : 'Not provided',
                    'subcategory' => filled($participant->subDescription)
                        ? trim((string) $participant->subDescription)
                        : 'Not provided',
                    'group_key' => implode('|', [
                        self::normalizedName($participant->lastName),
                        (string) $participant->birthDate,
                    ]),
                ];
            });

        $groups = $records
            ->groupBy('group_key')
            ->map(fn ($group): array => [
                'birth_date' => $group->first()['birth_date'],
                'last_name' => trim((string) str($group->first()['name'])->before(',')),
                'records' => $group->map(fn (array $record): array => collect($record)
                    ->except('group_key')
                    ->all())
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        $report = [
            'year' => $year,
            'as_of' => $generatedAt->format('F j, Y'),
            'generated_at' => $generatedAt->format('F j, Y g:i A'),
            'record_count' => $records->count(),
            'group_count' => count($groups),
            'groups' => $groups,
        ];

        $pdfContent = Pdf::loadView('pdf.potential-duplicates-report', [
            'report' => $report,
        ])
            ->setPaper('a4', 'landscape')
            ->output();

        return response()->streamDownload(
            static function () use ($pdfContent): void {
                echo $pdfContent;
            },
            "potential-duplicates-{$year}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Participant::query()
                ->with('creator')
                ->whereKey($this->potentialDuplicateIds))
            ->columns([
                TextColumn::make('mobile_duplicate_summary')
                    ->label('Participant for Review')
                    ->getStateUsing(fn (Participant $record): string => collect([
                        $record->lastName.',',
                        $record->firstName,
                        filled($record->middleInitial)
                            ? rtrim((string) $record->middleInitial, '.').'.'
                            : null,
                    ])->filter()->join(' '))
                    ->description(fn (Participant $record): string => collect([
                        filled($record->birthDate)
                            ? \Carbon\Carbon::parse($record->birthDate)->format('M j, Y')
                            : null,
                        filled($record->contactNumber)
                            ? "Phone {$record->contactNumber}"
                            : 'Phone not provided',
                        $record->categoryDescription,
                        $record->subDescription,
                    ])->filter()->join(' • '))
                    ->weight('bold')
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('participant_name')
                    ->label('Participant')
                    ->getStateUsing(fn (Participant $record): string => collect([
                        $record->lastName.',',
                        $record->firstName,
                        filled($record->middleInitial)
                            ? rtrim((string) $record->middleInitial, '.').'.'
                            : null,
                    ])->filter()->join(' '))
                    ->description(fn (Participant $record): string => collect([
                        filled($record->birthDate)
                            ? \Carbon\Carbon::parse($record->birthDate)->format('M j, Y')
                            : null,
                        filled($record->participantNumber)
                            ? "Participant #{$record->participantNumber}"
                            : null,
                        filled($record->contactNumber)
                            ? "Phone {$record->contactNumber}"
                            : 'Phone not provided',
                        "Year {$record->year}",
                    ])->filter()->join(' • '))
                    ->searchable(['firstName', 'lastName', 'contactNumber'])
                    ->weight('bold')
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('potential_duplicate_count')
                    ->label('Potential Duplicates')
                    ->getStateUsing(fn (Participant $record): int => $this->potentialDuplicateCounts[$record->getKey()] ?? 0)
                    ->formatStateUsing(fn (int $state): string => $state === 1
                        ? '1 related variation'
                        : "{$state} related variations")
                    ->description('High Probability')
                    ->badge()
                    ->color('warning')
                    ->tooltip('Same year, birth date, and last name with a similar but non-exact first name.'),
                TextColumn::make('categoryDescription')
                    ->label('Registration')
                    ->description(fn (Participant $record): string => filled($record->subDescription)
                        ? $record->subDescription
                        : 'No subcategory recorded')
                    ->searchable()
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('creator_name')
                    ->label('Recorded By')
                    ->description(fn (Participant $record): string => $record->created_at
                        ? $record->created_at->format('M j, Y g:i A')
                        : 'Date not recorded')
                    ->wrap()
                    ->visibleFrom('lg'),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('Registration Year')
                    ->options(fn (): array => self::registrationYearOptions())
                    ->default((string) now()->year)
                    ->selectablePlaceholder(false),
            ])
            ->deferFilters(false)
            ->heading('Participant Records for Review')
            ->description('Compare the highlighted records carefully before correcting, moving, or deleting any registration.')
            ->defaultSort('lastName')
            ->striped()
            ->recordUrl(null)
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('Review participant')
                    ->url(fn (Participant $record): string => ParticipantResource::getUrl(
                        'edit',
                        ['record' => $record],
                    ))
                    ->visible(fn (Participant $record): bool => Auth::user()?->can(
                        'update',
                        $record,
                    ) ?? false),
                DeleteAction::make()
                    ->label('Delete')
                    ->iconButton()
                    ->tooltip('Delete participant')
                    ->modalHeading('Delete participant registration?')
                    ->modalDescription('This permanently removes the selected participant record. Review the related registration carefully before continuing.')
                    ->authorize(fn (Participant $record): bool => self::canDeletePotentialDuplicate(
                        Auth::user(),
                        $record,
                    ))
                    ->authorizationMessage('You may only delete potential duplicates from categories assigned to your account.')
                    ->visible(fn (Participant $record): bool => self::canDeletePotentialDuplicate(
                        Auth::user(),
                        $record,
                    ))
                    ->after(function (): void {
                        $this->preparePotentialDuplicates($this->selectedYear());
                    }),
            ])
            ->emptyStateHeading('No potential duplicates found')
            ->emptyStateDescription('No high-probability non-exact participant matches were found for the selected registration year.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function applyPotentialDuplicateScope(
        Builder $query,
        ?int $year = null,
    ): Builder {
        return $query->whereKey(
            self::findPotentialDuplicates($year ?? now()->year)['ids'],
        );
    }

    public static function canDeletePotentialDuplicate(
        ?User $user,
        Participant $participant,
    ): bool {
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['superadmin', 'super_admin'])) {
            return true;
        }

        return $user->isAdministrativeOfficer()
            && $user->administersCategory($participant->categoryDescription);
    }

    public function getPotentialDuplicateStats(): array
    {
        $year = $this->selectedYear();
        if ($this->potentialDuplicatesYear !== $year) {
            $this->preparePotentialDuplicates($year);
        }

        return $this->potentialDuplicateStats;
    }

    /**
     * @return array{ids: array<int>, counts: array<int, int>, groups: int}
     */
    private static function findPotentialDuplicates(int $year): array
    {
        /** @var array<string, array<string, array{ids: array<int>, soundex: array<string, true>}>> $groups */
        $groups = [];

        foreach (Participant::query()
            ->where('year', $year)
            ->select(['id', 'firstName', 'lastName', 'birthDate'])
            ->selectRaw('SOUNDEX(firstName) AS first_name_soundex')
            ->cursor() as $participant) {
            if (
                $participant->firstName === null
                || $participant->lastName === null
                || $participant->birthDate === null
            ) {
                continue;
            }

            $groupKey = json_encode([
                (string) $participant->birthDate,
                self::normalizedName($participant->lastName),
            ], JSON_THROW_ON_ERROR);
            $normalizedFirstName = self::normalizedName($participant->firstName);
            $phoneticName = (string) $participant->getAttribute('first_name_soundex');

            $groups[$groupKey][$normalizedFirstName] ??= [
                'ids' => [],
                'soundex' => [],
            ];
            $groups[$groupKey][$normalizedFirstName]['ids'][] = (int) $participant->getKey();
            $groups[$groupKey][$normalizedFirstName]['soundex'][$phoneticName] = true;
        }

        $counts = [];
        $duplicateGroups = 0;

        foreach ($groups as $variantsByFirstName) {
            $firstNames = array_keys($variantsByFirstName);
            $variantCount = count($firstNames);
            $groupHasDuplicates = false;

            for ($left = 0; $left < $variantCount; $left++) {
                for ($right = $left + 1; $right < $variantCount; $right++) {
                    $leftName = $firstNames[$left];
                    $rightName = $firstNames[$right];
                    $leftVariant = $variantsByFirstName[$leftName];
                    $rightVariant = $variantsByFirstName[$rightName];

                    if (! self::areFirstNameVariantsSimilar(
                        $leftName,
                        $rightName,
                        $leftVariant['soundex'],
                        $rightVariant['soundex'],
                    )) {
                        continue;
                    }

                    $groupHasDuplicates = true;
                    $leftRelatedCount = count($rightVariant['ids']);
                    $rightRelatedCount = count($leftVariant['ids']);

                    foreach ($leftVariant['ids'] as $participantId) {
                        $counts[$participantId] = ($counts[$participantId] ?? 0)
                            + $leftRelatedCount;
                    }

                    foreach ($rightVariant['ids'] as $participantId) {
                        $counts[$participantId] = ($counts[$participantId] ?? 0)
                            + $rightRelatedCount;
                    }
                }
            }

            if ($groupHasDuplicates) {
                $duplicateGroups++;
            }
        }

        ksort($counts);

        return [
            'ids' => array_map('intval', array_keys($counts)),
            'counts' => $counts,
            'groups' => $duplicateGroups,
        ];
    }

    /**
     * @param  array<string, true>  $leftSoundex
     * @param  array<string, true>  $rightSoundex
     */
    private static function areFirstNameVariantsSimilar(
        string $leftName,
        string $rightName,
        array $leftSoundex,
        array $rightSoundex,
    ): bool {
        return str_starts_with($leftName, $rightName)
            || str_starts_with($rightName, $leftName)
            || array_intersect_key($leftSoundex, $rightSoundex) !== [];
    }

    private function preparePotentialDuplicates(int $year): void
    {
        $data = self::findPotentialDuplicates($year);

        $this->potentialDuplicateIds = $data['ids'];
        $this->potentialDuplicateCounts = $data['counts'];
        $this->potentialDuplicateStats = [
            'year' => $year,
            'groups' => $data['groups'],
            'records' => count($data['ids']),
        ];
        $this->potentialDuplicatesYear = $year;
    }

    private static function normalizedName(?string $name): string
    {
        return mb_strtolower(str_replace(' ', '', trim((string) $name)));
    }

    private static function registrationYearOptions(): array
    {
        return Participant::query()
            ->whereNotNull('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year', 'year')
            ->mapWithKeys(fn (int|string $year): array => [
                (string) $year => (string) $year,
            ])
            ->all();
    }

    private function selectedYear(): int
    {
        return (int) (
            $this->getTableFilterState('year')['value']
            ?? now()->year
        );
    }
}
