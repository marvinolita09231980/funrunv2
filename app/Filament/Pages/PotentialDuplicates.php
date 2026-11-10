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
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PotentialDuplicates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Potential Duplicates';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.potential-duplicates';

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
        $records = self::applyPotentialDuplicateScope(
            Participant::query()->where('year', $year),
        )
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
            ->query(
                self::applyPotentialDuplicateScope(
                    Participant::query()->with('creator'),
                )->addSelect([
                    'potential_duplicate_count' => self::matchingParticipantsQuery(),
                ])
            )
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
                        "Year {$record->year}",
                    ])->filter()->join(' • '))
                    ->searchable(['firstName', 'lastName'])
                    ->weight('bold')
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('potential_duplicate_count')
                    ->label('Potential Duplicates')
                    ->getStateUsing(fn (Participant $record): int => (int) (
                        $record->potential_duplicate_count
                        ?? self::matchingParticipantCount($record)
                    ))
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
                    )),
            ])
            ->emptyStateHeading('No potential duplicates found')
            ->emptyStateDescription('No high-probability non-exact participant matches were found for the selected registration year.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function applyPotentialDuplicateScope(Builder $query): Builder
    {
        return $query->whereExists(function ($duplicateQuery): void {
            $duplicateQuery
                ->selectRaw('1')
                ->from('participants as potential_duplicate')
                ->whereColumn('potential_duplicate.year', 'participants.year')
                ->whereColumn('potential_duplicate.birthDate', 'participants.birthDate')
                ->whereColumn('potential_duplicate.id', '<>', 'participants.id')
                ->whereRaw(
                    "LOWER(REPLACE(TRIM(potential_duplicate.lastName), ' ', '')) = LOWER(REPLACE(TRIM(participants.lastName), ' ', ''))"
                )
                ->where(fn ($firstNameQuery) => self::applySimilarFirstNameMatch(
                    $firstNameQuery,
                    'potential_duplicate',
                    'participants',
                ));
        });
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
        $records = self::applyPotentialDuplicateScope(
            Participant::query()->where('year', $year),
        )->count();

        $groups = DB::query()
            ->fromSub(
                self::applyPotentialDuplicateScope(
                    Participant::query()->where('year', $year),
                )
                    ->selectRaw(
                        "LOWER(REPLACE(TRIM(lastName), ' ', '')) AS normalized_last_name"
                    )
                    ->addSelect('birthDate')
                    ->groupByRaw(
                        "LOWER(REPLACE(TRIM(lastName), ' ', '')), birthDate"
                    ),
                'potential_duplicate_groups',
            )
            ->count();

        return [
            'year' => $year,
            'groups' => $groups,
            'records' => $records,
        ];
    }

    private static function matchingParticipantsQuery(): Builder
    {
        return Participant::query()
            ->from('participants as matching_participant')
            ->selectRaw('COUNT(*)')
            ->whereColumn('matching_participant.year', 'participants.year')
            ->whereColumn('matching_participant.birthDate', 'participants.birthDate')
            ->whereRaw(
                "LOWER(REPLACE(TRIM(matching_participant.lastName), ' ', '')) = LOWER(REPLACE(TRIM(participants.lastName), ' ', ''))"
            )
            ->where(fn ($firstNameQuery) => self::applySimilarFirstNameMatch(
                $firstNameQuery,
                'matching_participant',
                'participants',
            ));
    }

    private static function matchingParticipantCount(Participant $participant): int
    {
        return Participant::query()
            ->where('year', $participant->year)
            ->whereDate('birthDate', $participant->birthDate)
            ->whereRaw("LOWER(REPLACE(TRIM(lastName), ' ', '')) = ?", [
                self::normalizedName($participant->lastName),
            ])
            ->where(function (Builder $query) use ($participant): void {
                $normalizedFirstName = self::normalizedName($participant->firstName);

                $query
                    ->whereRaw("LOWER(REPLACE(TRIM(firstName), ' ', '')) <> ?", [
                        $normalizedFirstName,
                    ])
                    ->where(function (Builder $similarNameQuery) use (
                        $normalizedFirstName,
                        $participant,
                    ): void {
                        $similarNameQuery->whereRaw(
                            "SUBSTR(LOWER(REPLACE(TRIM(firstName), ' ', '')), 1, LENGTH(?)) = ?",
                            [$normalizedFirstName, $normalizedFirstName],
                        )
                            ->orWhereRaw(
                                "SUBSTR(?, 1, LENGTH(LOWER(REPLACE(TRIM(firstName), ' ', '')))) = LOWER(REPLACE(TRIM(firstName), ' ', ''))",
                                [$normalizedFirstName],
                            )
                            ->orWhereRaw('SOUNDEX(firstName) = SOUNDEX(?)', [
                                $participant->firstName,
                            ]);
                    });
            })
            ->count();
    }

    private static function applySimilarFirstNameMatch(
        mixed $query,
        string $candidateTable,
        string $participantTable,
    ): mixed {
        $candidateFirstName = "LOWER(REPLACE(TRIM({$candidateTable}.firstName), ' ', ''))";
        $participantFirstName = "LOWER(REPLACE(TRIM({$participantTable}.firstName), ' ', ''))";

        return $query
            ->whereRaw("{$candidateFirstName} <> {$participantFirstName}")
            ->where(function ($similarNameQuery) use (
                $candidateFirstName,
                $participantFirstName,
                $candidateTable,
                $participantTable,
            ): void {
                $similarNameQuery
                    ->whereRaw(
                        "SUBSTR({$candidateFirstName}, 1, LENGTH({$participantFirstName})) = {$participantFirstName}"
                    )
                    ->orWhereRaw(
                        "SUBSTR({$participantFirstName}, 1, LENGTH({$candidateFirstName})) = {$candidateFirstName}"
                    )
                    ->orWhereRaw(
                        "SOUNDEX({$candidateTable}.firstName) = SOUNDEX({$participantTable}.firstName)"
                    );
            });
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
