<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Participants\ParticipantResource;
use App\Models\Participant;
use App\Models\User;
use App\Services\AuditLogger;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PotentialDuplicates extends Page implements HasTable
{
    use InteractsWithTable;

    public const REPLACEMENT_CATEGORY = 'IN PLACE OF DUPLICATES';

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

    /** @var array<int, string> */
    public array $potentialDuplicateGroupKeys = [];

    /** @var array<string, int> */
    public array $potentialDuplicateGroupSizes = [];

    /** @var array<int, int> */
    public array $potentialDuplicateGroupPositions = [];

    /** @var array<int, int> */
    public array $potentialDuplicateOrder = [];

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
                ];
            })
            ->keyBy('id');

        $groups = collect($duplicateData['components'])
            ->map(function (array $participantIds) use ($records): array {
                $group = $records->only($participantIds)->values();
                $birthDates = $group->pluck('birth_date')->unique()->values();

                return [
                    'birth_date' => $birthDates->count() === 1
                        ? $birthDates->first()
                        : 'Varies',
                    'last_name' => trim((string) str($group->first()['name'])->before(',')),
                    'records' => $group->all(),
                ];
            })
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
                ->whereKey($this->potentialDuplicateIds)
                ->when(
                    $this->potentialDuplicateOrder !== [],
                    fn (Builder $query): Builder => $query->orderByRaw(
                        $this->potentialDuplicateOrderSql(),
                    ),
                ))
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
                        $this->potentialDuplicateGroupMarker($record),
                        filled($record->birthDate)
                            ? \Carbon\Carbon::parse($record->birthDate)->format('M j, Y')
                            : null,
                        filled($record->contactNumber)
                            ? "Phone {$record->contactNumber}"
                            : 'Phone not provided',
                        filled($record->shirtSize)
                            ? 'Shirt '.strtoupper(trim((string) $record->shirtSize))
                            : 'Shirt size not provided',
                        'Distance '.$record->distance_label,
                        $record->categoryDescription,
                        $record->subDescription,
                    ])->filter()->join(' • '))
                    ->weight('bold')
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('duplicate_group')
                    ->label('Relationship')
                    ->getStateUsing(fn (Participant $record): string => $this->potentialDuplicateGroupMarker($record))
                    ->formatStateUsing(fn (string $state, Participant $record): HtmlString => $this->potentialDuplicateGroupBracket(
                        $state,
                        $record,
                    ))
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('warning')
                    ->visibleFrom('md'),
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
                    ->tooltip('Same exact name within the year, even with different birthdates, or the same birthdate and last name with a similar first name.'),
                TextColumn::make('categoryDescription')
                    ->label('Registration')
                    ->description(fn (Participant $record): string => filled($record->subDescription)
                        ? $record->subDescription
                        : 'No subcategory recorded')
                    ->searchable(['categoryDescription', 'subDescription'])
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('shirtSize')
                    ->label('Shirt Size')
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? strtoupper(trim($state))
                        : 'Not provided')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereRaw(
                            'LOWER(TRIM(shirtSize)) LIKE ?',
                            ['%'.mb_strtolower(trim($search)).'%'],
                        )),
                TextColumn::make('distanceCategory')
                    ->label('Race Distance')
                    ->formatStateUsing(fn (?string $state): string => Participant::distanceLabel($state))
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            $search = mb_strtolower(trim($search));

                            return $query->where(function (Builder $query) use ($search): void {
                                $query
                                    ->whereRaw(
                                        "LOWER(CASE
                                            WHEN LOWER(REPLACE(TRIM(distanceCategory), ' ', '')) IN ('3k', '3km')
                                                THEN 'Rave Run (3K)'
                                            ELSE TRIM(distanceCategory)
                                        END) LIKE ?",
                                        ["%{$search}%"],
                                    )
                                    ->orWhereRaw(
                                        'LOWER(TRIM(distanceCategory)) LIKE ?',
                                        ["%{$search}%"],
                                    );
                            });
                        },
                    ),
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
                Filter::make('shirt_size')
                    ->schema([
                        TextInput::make('value')
                            ->label('Shirt Size')
                            ->placeholder('e.g. XS, M, or 2XL')
                            ->live(debounce: 300),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = mb_strtolower(trim((string) ($data['value'] ?? '')));

                        return $search === ''
                            ? $query
                            : $query->whereRaw('LOWER(TRIM(shirtSize)) LIKE ?', ["%{$search}%"]);
                    }),
                Filter::make('race_distance')
                    ->schema([
                        TextInput::make('value')
                            ->label('Race Distance')
                            ->placeholder('e.g. Rave Run (3K), 5KM, or 10KM')
                            ->live(debounce: 300),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = mb_strtolower(trim((string) ($data['value'] ?? '')));

                        if ($search === '') {
                            return $query;
                        }

                        return $query->where(function (Builder $query) use ($search): void {
                            $query
                                ->whereRaw(
                                    "LOWER(CASE
                                        WHEN LOWER(REPLACE(TRIM(distanceCategory), ' ', '')) IN ('3k', '3km')
                                            THEN 'Rave Run (3K)'
                                        ELSE TRIM(distanceCategory)
                                    END) LIKE ?",
                                    ["%{$search}%"],
                                )
                                ->orWhereRaw(
                                    'LOWER(TRIM(distanceCategory)) LIKE ?',
                                    ["%{$search}%"],
                                );
                        });
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'md' => 3,
            ])
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->splitSearchTerms()
            ->searchPlaceholder('Combine name, category, shirt size, or race distance')
            ->paginated(false)
            ->heading('Participant Records for Review')
            ->description('Relationship brackets connect records that belong to the same potential duplicate group.')
            ->defaultSort('lastName')
            ->striped()
            ->recordUrl(null)
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('Compare duplicate records')
                    ->modalHeading(fn (Participant $record): string => 'Compare Duplicate Group '
                        .$this->potentialDuplicateGroupNumber($record))
                    ->modalDescription('Review the participant details side by side, then choose which record should be assigned to a different participant.')
                    ->modalWidth('7xl')
                    ->closeModalByClickingAway(false)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (Participant $record) {
                        $participants = $this->potentialDuplicateGroupParticipants($record);

                        return view('filament.pages.partials.potential-duplicate-comparison', [
                            'participants' => $participants,
                        ]);
                    })
                    ->registerModalActions([
                        fn (): Action => $this->getTable()
                            ->getAction('replaceDuplicate')
                            ->getClone()
                            ->record(fn (array $arguments): ?Participant => Participant::query()->find(
                                $arguments['participant'] ?? null,
                            ))
                            ->hidden(false),
                    ])
                    ->action(fn (): null => null),
                Action::make('replaceDuplicate')
                    ->label('Replace Participant')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->tooltip('Assign this duplicate registration to a different participant')
                    ->modalHeading(fn (Participant $record): string => 'Replace '
                        .trim("{$record->firstName} {$record->lastName}"))
                    ->modalDescription('The original registration is shown on the left. Enter the different participant’s details in the empty fields on the right. The category will become IN PLACE OF DUPLICATES, while shirt size and race distance stay unchanged.')
                    ->modalSubmitActionLabel('Save New Participant Details')
                    ->modalWidth('5xl')
                    ->closeModalByClickingAway(false)
                    ->fillForm(fn (Participant $record): array => [
                        'originalFirstName' => $record->firstName,
                        'firstName' => null,
                        'originalLastName' => $record->lastName,
                        'lastName' => null,
                        'originalMiddleInitial' => $record->middleInitial,
                        'middleInitial' => null,
                        'originalBirthDate' => $record->birthDate,
                        'birthDate' => null,
                        'originalGender' => filled($record->gender)
                            ? ucfirst((string) $record->gender)
                            : 'Not provided',
                        'gender' => null,
                        'originalContactNumber' => $record->contactNumber,
                        'contactNumber' => null,
                        'originalAddress' => $record->address,
                        'address' => null,
                        'originalPwd' => $record->pwd ? 'Yes' : 'No',
                        'pwd' => null,
                        'originalRpwuds' => $record->rpwuds ? 'Yes' : 'No',
                        'rpwuds' => null,
                        'originalCategoryDescription' => $record->categoryDescription,
                        'categoryDescription' => self::REPLACEMENT_CATEGORY,
                        'shirtSize' => $record->shirtSize,
                        'distanceCategory' => $record->distanceCategory,
                    ])
                    ->schema([
                        Fieldset::make('Participant details')
                            ->columns(2)
                            ->schema([
                                TextInput::make('originalFirstName')
                                    ->label('Original: First Name')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('firstName')
                                    ->label('To update with: First Name')
                                    ->placeholder('Enter the new first name')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('originalLastName')
                                    ->label('Original: Last Name')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('lastName')
                                    ->label('To update with: Last Name')
                                    ->placeholder('Enter the new last name')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('originalMiddleInitial')
                                    ->label('Original: Middle Initial')
                                    ->placeholder('Not provided')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('middleInitial')
                                    ->label('To update with: Middle Initial')
                                    ->placeholder('Optional')
                                    ->maxLength(1),
                                TextInput::make('originalBirthDate')
                                    ->label('Original: Birth Date')
                                    ->disabled()
                                    ->dehydrated(false),
                                DatePicker::make('birthDate')
                                    ->label('To update with: Birth Date')
                                    ->displayFormat('m/d/Y')
                                    ->placeholder('mm/dd/yyyy')
                                    ->defaultFocusedDate(now()->subYears(18))
                                    ->minDate('1900-01-01')
                                    ->required()
                                    ->maxDate(now())
                                    ->closeOnDateSelection(),
                                TextInput::make('originalGender')
                                    ->label('Original: Gender')
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('gender')
                                    ->label('To update with: Gender')
                                    ->placeholder('Select the new gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                        'non-binary' => 'Non-binary',
                                        'transgender' => 'Transgender',
                                        'genderqueer' => 'Genderqueer',
                                        'agender' => 'Agender',
                                        'prefer not to say' => 'Prefer not to say',
                                    ])
                                    ->required(),
                                TextInput::make('originalContactNumber')
                                    ->label('Original: Phone Number')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('contactNumber')
                                    ->label('To update with: Phone Number')
                                    ->placeholder('Enter the new phone number')
                                    ->required()
                                    ->maxLength(30),
                                TextInput::make('originalAddress')
                                    ->label('Original: Address')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('address')
                                    ->label('To update with: Address')
                                    ->placeholder('Enter the new address')
                                    ->required(),
                                TextInput::make('originalPwd')
                                    ->label('Original: Person with Disability')
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('pwd')
                                    ->label('To update with: Person with Disability')
                                    ->placeholder('Select Yes or No')
                                    ->options([
                                        1 => 'Yes',
                                        0 => 'No',
                                    ])
                                    ->required(),
                                TextInput::make('originalRpwuds')
                                    ->label('Original: Recovering Person Who Used Drugs')
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('rpwuds')
                                    ->label('To update with: Recovering Person Who Used Drugs')
                                    ->placeholder('Select Yes or No')
                                    ->options([
                                        1 => 'Yes',
                                        0 => 'No',
                                    ])
                                    ->required(),
                            ]),
                        Fieldset::make('Registration values')
                            ->columns(2)
                            ->schema([
                                TextInput::make('originalCategoryDescription')
                                    ->label('Original Category')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('categoryDescription')
                                    ->label('New Category')
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('shirtSize')
                                    ->label('Retained Shirt Size')
                                    ->options([
                                        'xs' => 'XS',
                                        'small' => 'Small',
                                        'medium' => 'Medium',
                                        'large' => 'Large',
                                        'xl' => 'XL',
                                        '2xl' => '2XL',
                                        '3xl' => '3XL',
                                        '4xl' => '4XL',
                                        '5xl' => '5XL',
                                    ])
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('distanceCategory')
                                    ->label('Retained Race Distance')
                                    ->options([
                                        '3km' => 'Rave Run (3K)',
                                        '5km' => '5KM',
                                        '10km' => '10KM',
                                    ])
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->authorize(fn (?Participant $record): bool => $record !== null
                        && self::canReplacePotentialDuplicate(Auth::user(), $record))
                    ->hidden()
                    ->action(function (Participant $record, array $data): void {
                        $this->replacePotentialDuplicateParticipant($record, $data);
                    }),
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
            ->emptyStateDescription('No exact-name or high-probability participant matches were found for the selected registration year.')
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

    /**
     * Return later duplicate records to omit while retaining the earliest
     * eligible participant from each relationship group.
     *
     * @param  array<int>|null  $eligibleParticipantIds
     * @return array<int>
     */
    public static function duplicateParticipantIdsToExclude(
        int $year,
        ?array $eligibleParticipantIds = null,
    ): array {
        return collect(self::findPotentialDuplicates($year, $eligibleParticipantIds)['components'])
            ->flatMap(fn (array $participantIds): array => array_slice($participantIds, 1))
            ->map(fn (int|string $participantId): int => (int) $participantId)
            ->values()
            ->all();
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

    public static function canReplacePotentialDuplicate(
        ?User $user,
        Participant $participant,
    ): bool {
        if (! $user) {
            return false;
        }

        $canManageRecord = $user->hasAnyRole(['admin', 'superadmin', 'super_admin'])
            || (
                $user->isAdministrativeOfficer()
                && $user->administersCategory($participant->categoryDescription)
            );

        return $canManageRecord && self::isPotentialDuplicate($participant);
    }

    private static function isPotentialDuplicate(Participant $participant): bool
    {
        if (! $participant->exists || blank($participant->year)) {
            return false;
        }

        $participantId = (int) $participant->getKey();
        $group = collect(self::findPotentialDuplicates((int) $participant->year)['components'])
            ->first(fn (array $participantIds): bool => in_array($participantId, $participantIds, true));

        return is_array($group) && count($group) > 1;
    }

    /**
     * Reassign one duplicate registration to a different participant while
     * moving it to the replacement category while retaining its shirt size
     * and race distance.
     *
     * Participant model events write the complete changed fields to the audit
     * log, preserving the original and replacement values.
     *
     * @param  array<string, mixed>  $data
     */
    public function replacePotentialDuplicateParticipant(
        Participant $participant,
        array $data,
    ): void {
        $actor = Auth::user();

        abort_unless(
            self::canReplacePotentialDuplicate($actor, $participant),
            403,
        );

        $groupKey = $this->potentialDuplicateGroupKeys[$participant->getKey()] ?? null;
        $participantIds = collect($this->potentialDuplicateGroupKeys)
            ->filter(fn (string $candidateGroupKey): bool => $candidateGroupKey === $groupKey)
            ->keys()
            ->map(fn (int|string $participantId): int => (int) $participantId)
            ->values()
            ->all();

        if (! $groupKey || count($participantIds) < 2) {
            throw ValidationException::withMessages([
                'record' => 'This potential duplicate group no longer contains multiple participants.',
            ]);
        }

        DB::transaction(function () use ($participant, $participantIds, $data, $actor): void {
            /** @var Participant|null $participantToReplace */
            $participantToReplace = Participant::query()
                ->whereKey($participant->getKey())
                ->lockForUpdate()
                ->first();
            $remainingGroupSize = Participant::query()
                ->whereKey($participantIds)
                ->lockForUpdate()
                ->count();

            if (! $participantToReplace || $remainingGroupSize < 2) {
                throw ValidationException::withMessages([
                    'record' => 'The duplicate records changed while you were reviewing them. Reload and try again.',
                ]);
            }

            $firstName = trim((string) $data['firstName']);
            $lastName = trim((string) $data['lastName']);
            $birthDate = CarbonImmutable::parse((string) $data['birthDate'])->toDateString();
            $identityChanged = Participant::normalizedIdentityName($firstName)
                    !== Participant::normalizedIdentityName($participantToReplace->firstName)
                || Participant::normalizedIdentityName($lastName)
                    !== Participant::normalizedIdentityName($participantToReplace->lastName)
                || CarbonImmutable::parse((string) $participantToReplace->birthDate)->toDateString()
                    !== $birthDate;

            if (! $identityChanged) {
                throw ValidationException::withMessages([
                    'firstName' => 'Enter a different participant name or birth date to replace this duplicate registration.',
                ]);
            }

            if (Participant::query()
                ->where('year', $participantToReplace->year)
                ->whereKeyNot($participantToReplace->getKey())
                ->matchingIdentity($firstName, $lastName, $birthDate)
                ->exists()) {
                throw ValidationException::withMessages([
                    'firstName' => 'The replacement participant is already registered for this year.',
                ]);
            }

            $originalValues = $participantToReplace->getAttributes();
            unset($originalValues['created_at'], $originalValues['updated_at']);

            $participantToReplace->updateQuietly([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'middleInitial' => filled($data['middleInitial'] ?? null)
                    ? strtoupper(trim((string) $data['middleInitial']))
                    : null,
                'birthDate' => $birthDate,
                'gender' => $data['gender'],
                'address' => trim((string) $data['address']),
                'contactNumber' => trim((string) $data['contactNumber']),
                'categoryDescription' => self::REPLACEMENT_CATEGORY,
                'subDescription' => '',
                'created_by' => $actor->username,
                'pwd' => (bool) ($data['pwd'] ?? false),
                'rpwuds' => (bool) ($data['rpwuds'] ?? false),
            ]);

            $replacementValues = $participantToReplace->fresh()->getAttributes();
            unset($replacementValues['created_at'], $replacementValues['updated_at']);
            AuditLogger::recordReplacement(
                $participantToReplace,
                $originalValues,
                $replacementValues,
            );

            if ($finisher = $participantToReplace->finisher) {
                $finisher->update([
                    'firstName' => $participantToReplace->firstName,
                    'lastName' => $participantToReplace->lastName,
                    'middleInitial' => $participantToReplace->middleInitial,
                    'gender' => $participantToReplace->gender,
                    'birthDate' => $participantToReplace->birthDate,
                    'categoryDescription' => self::REPLACEMENT_CATEGORY,
                    'subDescription' => '',
                ]);
            }
        });

        $this->preparePotentialDuplicates($this->selectedYear());

        Notification::make()
            ->success()
            ->title('Participant replacement saved')
            ->body('The registration now belongs to the new participant under IN PLACE OF DUPLICATES and is registered under your account. Shirt size and race distance were kept, and the original values are available in Audit Logs.')
            ->send();
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
     * @return array{ids: array<int>, counts: array<int, int>, groups: int, components: array<int, array<int>>}
     */
    private static function findPotentialDuplicates(
        int $year,
        ?array $eligibleParticipantIds = null,
    ): array
    {
        /** @var array<string, array<string, array{ids: array<int>, soundex: array<string, true>}>> $birthDateGroups */
        $birthDateGroups = [];
        /** @var array<string, array<int>> $exactNameGroups */
        $exactNameGroups = [];

        foreach (Participant::query()
            ->where('year', $year)
            ->when(
                $eligibleParticipantIds !== null,
                fn (Builder $query): Builder => $query->whereKey($eligibleParticipantIds),
            )
            ->select(['id', 'firstName', 'lastName', 'birthDate'])
            ->selectRaw('SOUNDEX(firstName) AS first_name_soundex')
            ->cursor() as $participant) {
            if (
                $participant->firstName === null
                || $participant->lastName === null
            ) {
                continue;
            }

            $normalizedFirstName = self::normalizedName($participant->firstName);
            $normalizedLastName = self::normalizedName($participant->lastName);

            if ($normalizedFirstName === '' || $normalizedLastName === '') {
                continue;
            }

            $exactNameKey = json_encode([
                $normalizedLastName,
                $normalizedFirstName,
            ], JSON_THROW_ON_ERROR);
            $exactNameGroups[$exactNameKey][] = (int) $participant->getKey();

            if ($participant->birthDate === null) {
                continue;
            }

            $birthDateGroupKey = json_encode([
                (string) $participant->birthDate,
                $normalizedLastName,
            ], JSON_THROW_ON_ERROR);
            $phoneticName = (string) $participant->getAttribute('first_name_soundex');

            $birthDateGroups[$birthDateGroupKey][$normalizedFirstName] ??= [
                'ids' => [],
                'soundex' => [],
            ];
            $birthDateGroups[$birthDateGroupKey][$normalizedFirstName]['ids'][] = (int) $participant->getKey();
            $birthDateGroups[$birthDateGroupKey][$normalizedFirstName]['soundex'][$phoneticName] = true;
        }

        /** @var array<int, array<int, true>> $relationships */
        $relationships = [];
        $connect = static function (int $leftId, int $rightId) use (&$relationships): void {
            if ($leftId === $rightId) {
                return;
            }

            $relationships[$leftId][$rightId] = true;
            $relationships[$rightId][$leftId] = true;
        };

        foreach ($exactNameGroups as $participantIds) {
            $participantCount = count($participantIds);

            for ($left = 0; $left < $participantCount; $left++) {
                for ($right = $left + 1; $right < $participantCount; $right++) {
                    $connect($participantIds[$left], $participantIds[$right]);
                }
            }
        }

        foreach ($birthDateGroups as $variantsByFirstName) {
            $firstNames = array_keys($variantsByFirstName);
            $variantCount = count($firstNames);

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

                    foreach ($leftVariant['ids'] as $leftParticipantId) {
                        foreach ($rightVariant['ids'] as $rightParticipantId) {
                            $connect($leftParticipantId, $rightParticipantId);
                        }
                    }
                }
            }
        }

        $counts = array_map('count', $relationships);
        ksort($counts);

        $unvisited = array_fill_keys(array_keys($relationships), true);
        $components = [];

        while ($unvisited !== []) {
            $startingId = (int) array_key_first($unvisited);
            $stack = [$startingId];
            $component = [];

            while ($stack !== []) {
                $participantId = (int) array_pop($stack);

                if (! isset($unvisited[$participantId])) {
                    continue;
                }

                unset($unvisited[$participantId]);
                $component[] = $participantId;

                foreach (array_keys($relationships[$participantId] ?? []) as $relatedId) {
                    if (isset($unvisited[$relatedId])) {
                        $stack[] = (int) $relatedId;
                    }
                }
            }

            sort($component);
            $components[] = $component;
        }

        return [
            'ids' => array_map('intval', array_keys($counts)),
            'counts' => $counts,
            'groups' => count($components),
            'components' => $components,
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
        $this->potentialDuplicateGroupKeys = [];
        $this->potentialDuplicateGroupSizes = [];
        $this->potentialDuplicateGroupPositions = [];
        $this->potentialDuplicateOrder = [];

        $position = 0;
        foreach ($data['components'] as $index => $participantIds) {
            $groupKey = 'duplicate-group-'.($index + 1);
            $this->potentialDuplicateGroupSizes[$groupKey] = count($participantIds);

            foreach ($participantIds as $groupPosition => $participantId) {
                $this->potentialDuplicateGroupKeys[$participantId] = $groupKey;
                $this->potentialDuplicateGroupPositions[$participantId] = $groupPosition;
                $this->potentialDuplicateOrder[$participantId] = $position++;
            }
        }
        $this->potentialDuplicateStats = [
            'year' => $year,
            'groups' => $data['groups'],
            'records' => count($data['ids']),
        ];
        $this->potentialDuplicatesYear = $year;
    }

    private function potentialDuplicateOrderSql(): string
    {
        $cases = collect($this->potentialDuplicateOrder)
            ->map(fn (int $position, int|string $participantId): string => sprintf(
                'WHEN %d THEN %d',
                (int) $participantId,
                $position,
            ))
            ->implode(' ');

        return "CASE participants.id {$cases} ELSE ".count($this->potentialDuplicateOrder).' END';
    }

    private function potentialDuplicateGroupMarker(Participant $participant): string
    {
        $participantId = (int) $participant->getKey();
        $groupKey = $this->potentialDuplicateGroupKeys[$participantId] ?? null;
        $groupSize = $groupKey ? ($this->potentialDuplicateGroupSizes[$groupKey] ?? 1) : 1;
        $position = $this->potentialDuplicateGroupPositions[$participantId] ?? 0;
        $groupNumber = $groupKey
            ? (int) str($groupKey)->afterLast('-')->toString()
            : 0;

        $symbol = match (true) {
            $groupSize <= 1 => '•',
            $position === 0 => '┌─',
            $position === $groupSize - 1 => '└─',
            default => '├─',
        };

        return "{$symbol} Duplicate Group {$groupNumber}";
    }

    private function potentialDuplicateGroupNumber(Participant $participant): int
    {
        $groupKey = $this->potentialDuplicateGroupKeys[$participant->getKey()] ?? null;

        return $groupKey
            ? (int) str($groupKey)->afterLast('-')->toString()
            : 0;
    }

    private function potentialDuplicateGroupParticipants(Participant $participant): \Illuminate\Support\Collection
    {
        $groupKey = $this->potentialDuplicateGroupKeys[$participant->getKey()] ?? null;
        $participantIds = collect($this->potentialDuplicateGroupKeys)
            ->filter(fn (string $candidateGroupKey): bool => $candidateGroupKey === $groupKey)
            ->keys()
            ->map(fn (int|string $participantId): int => (int) $participantId)
            ->all();

        return Participant::query()
            ->with('creator')
            ->whereKey($participantIds)
            ->get()
            ->sortBy(fn (Participant $groupParticipant): int => $this->potentialDuplicateGroupPositions[$groupParticipant->getKey()] ?? PHP_INT_MAX)
            ->values();
    }

    private function potentialDuplicateGroupBracket(string $state, Participant $participant): HtmlString
    {
        $participantId = (int) $participant->getKey();
        $groupKey = $this->potentialDuplicateGroupKeys[$participantId] ?? null;
        $groupSize = $groupKey ? ($this->potentialDuplicateGroupSizes[$groupKey] ?? 1) : 1;
        $groupNumber = $groupKey
            ? (int) str($groupKey)->afterLast('-')->toString()
            : 0;
        $position = match (true) {
            str_starts_with($state, '┌') => 'top',
            str_starts_with($state, '├') => 'middle',
            str_starts_with($state, '└') => 'bottom',
            default => 'single',
        };
        $verticalLine = match ($position) {
            'top' => 'top:50%; bottom:-1.25rem;',
            'middle' => 'top:-1.25rem; bottom:-1.25rem;',
            'bottom' => 'top:-1.25rem; bottom:50%;',
            default => null,
        };
        $indicator = $position === 'single'
            ? '<span aria-hidden="true" style="position:absolute; left:0.18rem; top:50%; width:0.45rem; border-top:2px solid currentColor;"></span>'
            : '<span aria-hidden="true" style="position:absolute; left:0.4rem; '.$verticalLine.' border-left:2px solid currentColor;"></span>'
                .'<span aria-hidden="true" style="position:absolute; left:0.4rem; top:50%; width:0.75rem; border-top:2px solid currentColor;"></span>';
        $recordLabel = $groupSize === 1 ? '1 record' : "{$groupSize} records";

        return new HtmlString(
            '<div data-duplicate-bracket="'.$position.'" aria-label="Duplicate Group '.$groupNumber.', '.$recordLabel.'" style="position:relative; display:flex; min-height:3rem; align-items:center;">'
                .$indicator
                .'<span style="display:flex; flex-direction:column; margin-left:1.7rem; line-height:1.25;">'
                    .'<span>Group '.$groupNumber.'</span>'
                    .'<span style="font-family:inherit; font-size:0.72rem; font-weight:500; opacity:0.72;">'.$recordLabel.'</span>'
                .'</span>'
            .'</div>',
        );
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
