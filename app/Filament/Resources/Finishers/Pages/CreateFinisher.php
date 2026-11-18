<?php

namespace App\Filament\Resources\Finishers\Pages;

use App\Filament\Resources\Finishers\FinisherResource;
use App\Models\Award;
use App\Models\Finisher;
use App\Models\Participant;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View as SchemaView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateFinisher extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FinisherResource::class;

    protected string $view = 'filament.resources.finishers.pages.create-finisher';

    public static function canAccess(array $parameters = []): bool
    {
        return static::getResource()::canCreate();
    }

    public function getTitle(): string
    {
        return 'Record Finisher';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::eligibleParticipantsQuery()->with('finisher.awards'))
            ->columns([
                TextColumn::make('mobile_runner_summary')
                    ->label('Runner')
                    ->getStateUsing(fn (Participant $record): string => self::participantName($record))
                    ->description(fn (Participant $record): string => collect([
                        Participant::distanceLabel($record->distanceCategory),
                        filled($record->categoryDescription)
                            ? "Category: {$record->categoryDescription}"
                            : null,
                        filled($record->subDescription)
                            ? "Organization: {$record->subDescription}"
                            : null,
                        filled($record->birthDate)
                            ? 'Birth date: '.Carbon::parse($record->birthDate)->format('M d, Y')
                            : null,
                        filled(self::existingFinisher($record)?->award_names)
                            ? 'Awards: '.self::existingFinisher($record)->award_names
                            : null,
                    ])->filter()->join(' • '))
                    ->weight('bold')
                    ->searchable(['firstName', 'lastName'])
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('desktop_runner_name')
                    ->label('Runner')
                    ->getStateUsing(fn (Participant $record): string => self::participantName($record))
                    ->weight('bold')
                    ->searchable(['firstName', 'lastName'])
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('desktop_birth_date')
                    ->label('Birth Date')
                    ->getStateUsing(fn (Participant $record): mixed => $record->birthDate)
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->visibleFrom('md'),
                TextColumn::make('desktop_distance')
                    ->label('Distance')
                    ->getStateUsing(fn (Participant $record): ?string => $record->distanceCategory)
                    ->formatStateUsing(fn (?string $state): string => Participant::distanceLabel($state))
                    ->badge()
                    ->color('info')
                    ->visibleFrom('md'),
                TextColumn::make('desktop_category')
                    ->label('Category')
                    ->getStateUsing(fn (Participant $record): ?string => $record->categoryDescription)
                    ->searchable(['categoryDescription'])
                    ->placeholder('—')
                    ->badge()
                    ->color('gray')
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('desktop_organization')
                    ->label('Organization')
                    ->getStateUsing(fn (Participant $record): ?string => $record->subDescription)
                    ->searchable(['subDescription'])
                    ->placeholder('—')
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('desktop_current_awards')
                    ->label('Current Awards')
                    ->getStateUsing(fn (Participant $record): ?string => self::existingFinisher($record)?->award_names)
                    ->placeholder('—')
                    ->wrap()
                    ->visibleFrom('md'),
            ])
            ->filters([
                Filter::make('runner_name')
                    ->schema([
                        Select::make('participant_id')
                            ->label('Name')
                            ->placeholder('Type a first or last name to find a runner')
                            ->searchable()
                            ->searchDebounce(150)
                            ->live()
                            ->getSearchResultsUsing(function (string $search): array {
                                $search = trim($search);

                                if ($search === '') {
                                    return [];
                                }

                                return self::eligibleParticipantsQuery()
                                    ->where(function (Builder $query) use ($search): void {
                                        $query
                                            ->where('lastName', 'like', "{$search}%")
                                            ->orWhere('firstName', 'like', "{$search}%");
                                    })
                                    ->select([
                                        'id',
                                        'firstName',
                                        'lastName',
                                        'middleInitial',
                                        'birthDate',
                                    ])
                                    ->limit(15)
                                    ->get()
                                    ->mapWithKeys(fn (Participant $participant): array => [
                                        $participant->getKey() => self::participantLookupLabel($participant),
                                    ])
                                    ->all();
                            })
                            ->getOptionLabelUsing(function (mixed $value): ?string {
                                $participant = Participant::query()
                                    ->where('year', date('Y'))
                                    ->find($value);

                                return $participant
                                    ? self::participantLookupLabel($participant)
                                    : null;
                            }),
                    ])
                    ->columns(1)
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        $participantId = $data['participant_id'] ?? null;

                        if (blank($participantId)) {
                            return $query->whereRaw('1 = 0');
                        }

                        return $query->whereKey($participantId);
                    })
                    ->indicateUsing(fn (): array => []),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->deferFilters(false)
            ->recordActions([
                Action::make('recordRunner')
                    ->label(fn (Participant $record): string => self::existingFinisher($record)
                        ? 'Assign Award'
                        : 'Record Runner')
                    ->icon('heroicon-o-flag')
                    ->color('success')
                    ->button()
                    ->modalWidth('3xl')
                    ->closeModalByClickingAway(false)
                    ->modalHeading(fn (Participant $record): string => self::existingFinisher($record)
                        ? "Assign another award to {$record->firstName} {$record->lastName}"
                        : "Record {$record->firstName} {$record->lastName}")
                    ->modalDescription(fn (Participant $record): string => Participant::distanceLabel($record->distanceCategory) . ' runner')
                    ->modalSubmitActionLabel(fn (Participant $record): string => self::existingFinisher($record)
                        ? 'Assign Award'
                        : 'Record Runner')
                    ->schema([
                        TextInput::make('racebib')
                            ->label('Race Bib Number')
                            ->default(fn (Participant $record): ?string => self::existingFinisher($record)?->racebib
                                ?? Finisher::raceBibPrefixForDistance($record->distanceCategory))
                            ->helperText(fn (Participant $record): string => sprintf(
                                '%s race bib: exactly 5 numbers starting with %s.',
                                Participant::distanceLabel($record->distanceCategory),
                                Finisher::raceBibPrefixForDistance($record->distanceCategory) ?? 'the required digit',
                            ))
                            ->inputMode('numeric')
                            ->maxLength(5)
                            ->extraInputAttributes(function (Participant $record): array {
                                $prefix = Finisher::raceBibPrefixForDistance($record->distanceCategory);

                                return [
                                    'pattern' => '[0-9]{5}',
                                    'autocomplete' => 'off',
                                    'oninput' => $prefix
                                        ? "this.value = '{$prefix}' + this.value.replace(/[^0-9]/g, '').replace(/^{$prefix}/, '').slice(0, 4)"
                                        : "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 5)",
                                ];
                            })
                            ->rules([
                                'required',
                                'digits:5',
                            ])
                            ->unique(
                                table: Finisher::class,
                                column: 'racebib',
                                ignorable: fn (Participant $record): ?Finisher => self::existingFinisher($record),
                            )
                            ->validationMessages([
                                'unique' => 'This race bib number is already assigned to another runner.',
                            ])
                            ->rule(function (Participant $record): callable {
                                return function (string $attribute, mixed $value, callable $fail) use ($record): void {
                                    $requiredPrefix = Finisher::raceBibPrefixForDistance(
                                        $record->distanceCategory,
                                    );

                                    if ($requiredPrefix && ! str_starts_with((string) $value, $requiredPrefix)) {
                                        $fail(
                                            Participant::distanceLabel($record->distanceCategory)
                                            ." bib numbers must start with {$requiredPrefix}."
                                        );
                                    }
                                };
                            })
                            ->disabled(fn (Participant $record): bool => (bool) self::existingFinisher($record))
                            ->required(fn (Participant $record): bool => ! self::existingFinisher($record)),
                        Grid::make([
                            'default' => 1,
                            'lg' => 2,
                        ])
                            ->schema([
                                Select::make('award_id')
                                    ->label('Award')
                                    ->placeholder(fn (Participant $record): string => self::existingFinisher($record)
                                        ? 'Select an award to add'
                                        : 'No award')
                                    ->options(fn (Participant $record): array => Award::groupedOptions(
                                        distanceCategory: $record->distanceCategory,
                                    ))
                                    ->rule(function (Get $get, Participant $record): Closure {
                                        return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                            if (blank($value)) {
                                                return;
                                            }

                                            if (! Award::isSelectableForDistance($value, $record->distanceCategory)) {
                                                $fail('The selected award does not match the runner’s registered distance category.');

                                                return;
                                            }

                                            $selectedAward = Award::query()->find($value);
                                            $finisher = self::existingFinisher($record);
                                            $categoryAward = $finisher?->awardInCategory($selectedAward?->category);

                                            if ($categoryAward) {
                                                $fail(
                                                    "{$finisher->runner_name} already has {$categoryAward->name} in the "
                                                    ."{$selectedAward->category} category. A finisher can receive only one award per category."
                                                );

                                                return;
                                            }

                                            if (
                                                ($holder = Finisher::awardHolder($value, $finisher?->getKey()))
                                                && ! $get('reassign_award')
                                            ) {
                                                $fail(
                                                    "This award is assigned to {$holder->runner_name}. "
                                                    .'Confirm the award transfer to continue.'
                                                );
                                            }
                                        };
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('reassign_award', false);
                                    })
                                    ->partiallyRenderComponentsAfterStateUpdated([
                                        'award-conflict-notice',
                                        'reassign_award',
                                    ])
                                    ->searchable()
                                    ->preload()
                                    ->required(fn (Participant $record): bool => (bool) self::existingFinisher($record)),
                                SchemaView::make('filament.resources.finishers.partials.award-conflict-notice')
                                    ->key('award-conflict-notice'),
                            ])
                            ->columnSpanFull(),
                        Toggle::make('reassign_award')
                            ->label('Transfer this award to the new winner')
                            ->helperText(function (Get $get, Participant $record): ?string {
                                $holder = Finisher::awardHolder(
                                    $get('award_id'),
                                    self::existingFinisher($record)?->getKey(),
                                );

                                return $holder
                                    ? "This will remove the award from {$holder->runner_name}."
                                    : null;
                            })
                            ->visible(fn (Get $get, Participant $record): bool => (bool) Finisher::awardHolder(
                                $get('award_id'),
                                self::existingFinisher($record)?->getKey(),
                            ))
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),
                        TimePicker::make('finish_time')
                            ->label('Finish Time')
                            ->seconds(true)
                            ->default(fn (Participant $record): string => self::existingFinisher($record)?->finish_time
                                ? Carbon::parse(self::existingFinisher($record)->finish_time)->format('H:i:s')
                                : now('Asia/Manila')->format('H:i:s'))
                            ->disabled(fn (Participant $record): bool => (bool) self::existingFinisher($record))
                            ->required(fn (Participant $record): bool => ! self::existingFinisher($record)),
                    ])
                    ->action(function (Participant $record, array $data): void {
                        $previousWinner = null;

                        $result = DB::transaction(function () use ($record, $data, &$previousWinner): string|false {
                            $finisher = Finisher::query()
                                ->where('participants_id', $record->id)
                                ->lockForUpdate()
                                ->first();
                            $isExistingFinisher = (bool) $finisher;
                            $awardId = $data['award_id'] ?? null;

                            if ($isExistingFinisher && ! $awardId) {
                                return false;
                            }

                            if ($awardId) {
                                $award = Award::query()->whereKey($awardId)->lockForUpdate()->firstOrFail();

                                if ($finisher?->awardInCategory($award->category)) {
                                    return false;
                                }

                                $previousWinner = Finisher::awardHolder($awardId, $finisher?->getKey());

                                if ($previousWinner && ! ($data['reassign_award'] ?? false)) {
                                    return false;
                                }

                                $previousWinner?->removeAward($award);
                            }

                            if (! $finisher) {
                                $finisher = Finisher::create([
                                    'participants_id' => $record->id,
                                    'firstName' => $record->firstName,
                                    'lastName' => $record->lastName,
                                    'middleInitial' => $record->middleInitial,
                                    'categoryDescription' => $record->categoryDescription,
                                    'subDescription' => $record->subDescription,
                                    'gender' => $record->gender,
                                    'birthDate' => $record->birthDate,
                                    'distanceCategory' => $record->distanceCategory,
                                    'racebib' => $data['racebib'],
                                    'finish_time' => Carbon::now('Asia/Manila')->format('Y-m-d').' '.$data['finish_time'],
                                    'created_by' => Auth::user()->username,
                                ]);
                            }

                            if ($awardId) {
                                $finisher->assignAward($award);
                            }

                            return $isExistingFinisher ? 'award-assigned' : 'runner-recorded';
                        });

                        if (! $result) {
                            Notification::make()
                                ->title('Finisher could not be recorded')
                                ->body('The runner or award assignment changed. Review the details and try again.')
                                ->warning()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title($previousWinner
                                ? 'Award transferred'
                                : ($result === 'award-assigned' ? 'Award assigned' : 'Runner recorded'))
                            ->body(
                                $previousWinner
                                    ? "The award was transferred from {$previousWinner->runner_name} to {$record->firstName} {$record->lastName}."
                                    : ($result === 'award-assigned'
                                        ? "The award was added to {$record->firstName} {$record->lastName}."
                                        : "{$record->firstName} {$record->lastName} was added to the finishers list.")
                            )
                            ->success()
                            ->send();
                    }),
            ])
            ->recordUrl(null)
            ->heading('Participant Lookup Results')
            ->description('Record a race result or assign another eligible award to an existing finisher.')
            ->striped()
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Search for a runner')
            ->emptyStateDescription('Enter a last name or first name to display matching runners.');
    }

    private static function participantName(Participant $participant): string
    {
        $middleInitial = filled($participant->middleInitial)
            ? ' ' . rtrim($participant->middleInitial, '.') . '.'
            : '';

        return "{$participant->lastName}, {$participant->firstName}{$middleInitial}";
    }

    private static function participantLookupLabel(Participant $participant): string
    {
        $birthDate = filled($participant->birthDate) && $participant->birthDate !== '0000-00-00'
            ? Carbon::parse($participant->birthDate)->format('m/d/Y')
            : 'Birthdate not recorded';

        return self::participantName($participant).' - '.$birthDate;
    }

    private static function eligibleParticipantsQuery(): Builder
    {
        return Participant::query()
            ->where('year', date('Y'))
            ->orderBy('lastName')
            ->orderBy('firstName');
    }

    private static function existingFinisher(Participant $participant): ?Finisher
    {
        if ($participant->relationLoaded('finisher')) {
            return $participant->finisher;
        }

        return Finisher::query()
            ->with('awards')
            ->where('participants_id', $participant->getKey())
            ->first();
    }
}
