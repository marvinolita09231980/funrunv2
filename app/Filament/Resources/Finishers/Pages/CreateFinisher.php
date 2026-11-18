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
            ->query(
                Participant::query()
                    ->where('year', date('Y'))
                    ->whereNotExists(function ($query): void {
                        $query
                            ->selectRaw('1')
                            ->from('finishers')
                            ->whereColumn('finishers.participants_id', 'participants.id');
                    })
                    ->orderBy('lastName')
                    ->orderBy('firstName')
            )
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
            ])
            ->filters([
                Filter::make('runner_name')
                    ->schema([
                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->placeholder('e.g. Cortez')
                            ->autofocus(),
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->placeholder('e.g. Jimmy'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        $lastName = trim((string) ($data['last_name'] ?? ''));
                        $firstName = trim((string) ($data['first_name'] ?? ''));

                        if ($lastName === '' && $firstName === '') {
                            return $query->whereRaw('1 = 0');
                        }

                        return $query
                            ->when(
                                $lastName !== '',
                                fn (Builder $query): Builder => $query->where(
                                    'lastName',
                                    'like',
                                    "%{$lastName}%"
                                )
                            )
                            ->when(
                                $firstName !== '',
                                fn (Builder $query): Builder => $query->where(
                                    'firstName',
                                    'like',
                                    "%{$firstName}%"
                                )
                            );
                    })
                    ->indicateUsing(fn (): array => []),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->deferFilters(false)
            ->recordActions([
                Action::make('recordRunner')
                    ->label('Record Runner')
                    ->icon('heroicon-o-flag')
                    ->color('success')
                    ->button()
                    ->modalWidth('3xl')
                    ->closeModalByClickingAway(false)
                    ->modalHeading(fn (Participant $record): string => "Record {$record->firstName} {$record->lastName}")
                    ->modalDescription(fn (Participant $record): string => Participant::distanceLabel($record->distanceCategory) . ' runner')
                    ->modalSubmitActionLabel('Record Runner')
                    ->schema([
                        TextInput::make('racebib')
                            ->label('Race Bib Number')
                            ->default(fn (Participant $record): ?string => Finisher::raceBibPrefixForDistance(
                                $record->distanceCategory,
                            ))
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
                                ignoreRecord: false,
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
                            ->required(),
                        Grid::make([
                            'default' => 1,
                            'lg' => 2,
                        ])
                            ->schema([
                                Select::make('award_id')
                                    ->label('Award')
                                    ->placeholder('No award')
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

                                            if (($holder = Finisher::awardHolder($value)) && ! $get('reassign_award')) {
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
                                    ->preload(),
                                SchemaView::make('filament.resources.finishers.partials.award-conflict-notice')
                                    ->key('award-conflict-notice'),
                            ])
                            ->columnSpanFull(),
                        Toggle::make('reassign_award')
                            ->label('Transfer this award to the new winner')
                            ->helperText(function (Get $get): ?string {
                                $holder = Finisher::awardHolder($get('award_id'));

                                return $holder
                                    ? "This will remove the award from {$holder->runner_name}."
                                    : null;
                            })
                            ->visible(fn (Get $get): bool => (bool) Finisher::awardHolder($get('award_id')))
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),
                        TimePicker::make('finish_time')
                            ->label('Finish Time')
                            ->seconds(true)
                            ->default(fn (): string => now('Asia/Manila')->format('H:i:s'))
                            ->required(),
                    ])
                    ->action(function (Participant $record, array $data): void {
                        $previousWinner = null;

                        $created = DB::transaction(function () use ($record, $data, &$previousWinner): bool {
                            if (Finisher::where('participants_id', $record->id)->lockForUpdate()->exists()) {
                                return false;
                            }

                            $awardId = $data['award_id'] ?? null;

                            if ($awardId) {
                                Award::query()->whereKey($awardId)->lockForUpdate()->first();
                                $previousWinner = Finisher::query()
                                    ->where('award_id', $awardId)
                                    ->lockForUpdate()
                                    ->first();

                                if ($previousWinner && ! ($data['reassign_award'] ?? false)) {
                                    return false;
                                }

                                $previousWinner?->update(['award_id' => null]);
                            }

                            Finisher::create([
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
                                'award_id' => $awardId,
                                'finish_time' => Carbon::now('Asia/Manila')->format('Y-m-d') . ' ' . $data['finish_time'],
                                'created_by' => Auth::user()->username,
                            ]);

                            return true;
                        });

                        if (! $created) {
                            Notification::make()
                                ->title('Finisher could not be recorded')
                                ->body('The runner or award assignment changed. Review the details and try again.')
                                ->warning()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title($previousWinner ? 'Award transferred' : 'Runner recorded')
                            ->body(
                                $previousWinner
                                    ? "The award was transferred from {$previousWinner->runner_name} to {$record->firstName} {$record->lastName}."
                                    : "{$record->firstName} {$record->lastName} was added to the finishers list."
                            )
                            ->success()
                            ->send();
                    }),
            ])
            ->recordUrl(null)
            ->heading('Participant Lookup Results')
            ->description('Select an eligible runner to record their race result.')
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
}
