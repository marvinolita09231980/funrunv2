<?php

namespace App\Filament\Resources\Finishers\Pages;

use App\Filament\Resources\Finishers\FinisherResource;
use App\Models\Finisher;
use App\Models\Participant;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreateFinisher extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = FinisherResource::class;

    protected string $view = 'filament.resources.finishers.pages.create-finisher';

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
                Stack::make([
                    TextColumn::make('runner_name')
                        ->label('Runner')
                        ->getStateUsing(function (Participant $record): string {
                            $middleInitial = filled($record->middleInitial)
                                ? ' ' . rtrim($record->middleInitial, '.') . '.'
                                : '';

                            return "{$record->lastName}, {$record->firstName}{$middleInitial}";
                        })
                        ->weight('bold')
                        ->wrap(),
                    TextColumn::make('distanceCategory')
                        ->label('Distance')
                        ->badge()
                        ->color('info'),
                    TextColumn::make('categoryDescription')
                        ->prefix('Category: ')
                        ->placeholder('Category: —')
                        ->wrap(),
                    TextColumn::make('subDescription')
                        ->prefix('Organization: ')
                        ->placeholder('Organization: —')
                        ->wrap(),
                    TextColumn::make('birthDate')
                        ->prefix('Birth date: ')
                        ->date('M d, Y')
                        ->placeholder('Birth date: —'),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
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
                    ->modalWidth('md')
                    ->modalHeading(fn (Participant $record): string => "Record {$record->firstName} {$record->lastName}")
                    ->modalDescription(fn (Participant $record): string => strtoupper((string) $record->distanceCategory) . ' runner')
                    ->modalSubmitActionLabel('Record Runner')
                    ->schema([
                        TextInput::make('racebib')
                            ->label('Race Bib Number')
                            ->placeholder('4 digits')
                            ->inputMode('numeric')
                            ->rules([
                                'required',
                                'digits:4',
                                Rule::unique('finishers', 'racebib'),
                            ])
                            ->rule(function (Participant $record): callable {
                                return function (string $attribute, mixed $value, callable $fail) use ($record): void {
                                    $distance = strtolower(trim((string) $record->distanceCategory));
                                    $requiredPrefix = match ($distance) {
                                        '10k', '10km' => '1',
                                        '5k', '5km' => '5',
                                        '3k', '3km' => '3',
                                        default => null,
                                    };

                                    if ($requiredPrefix && ! str_starts_with((string) $value, $requiredPrefix)) {
                                        $fail(strtoupper($distance) . " bib numbers must start with {$requiredPrefix}.");
                                    }
                                };
                            })
                            ->required(),
                        TextInput::make('finisher_rank')
                            ->label('Finisher Rank')
                            ->helperText('The runner’s finishing position (ikapila siya ni-finish).')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99999)
                            ->rule(function (Participant $record): callable {
                                return function (string $attribute, mixed $value, callable $fail) use ($record): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $rankExists = Finisher::query()
                                        ->where('distanceCategory', $record->distanceCategory)
                                        ->where('finisher_rank', $value)
                                        ->whereIn(
                                            'participants_id',
                                            Participant::query()
                                                ->select('id')
                                                ->where('year', $record->year)
                                        )
                                        ->exists();

                                    if ($rankExists) {
                                        $fail("Rank {$value} is already recorded for {$record->distanceCategory}.");
                                    }
                                };
                            }),
                        TimePicker::make('finish_time')
                            ->label('Finish Time')
                            ->seconds(true)
                            ->default(fn (): string => now('Asia/Manila')->format('H:i:s'))
                            ->required(),
                    ])
                    ->action(function (Participant $record, array $data): void {
                        if (Finisher::where('participants_id', $record->id)->exists()) {
                            Notification::make()
                                ->title('Runner already recorded')
                                ->body('This runner is already in the finishers list.')
                                ->warning()
                                ->send();

                            return;
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
                            'finisher_rank' => $data['finisher_rank'] ?? null,
                            'finish_time' => Carbon::now('Asia/Manila')->format('Y-m-d') . ' ' . $data['finish_time'],
                            'created_by' => Auth::user()->username,
                        ]);

                        Notification::make()
                            ->title('Runner recorded')
                            ->body("{$record->firstName} {$record->lastName} was added to the finishers list.")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordUrl(null)
            ->emptyStateHeading('Search for a runner')
            ->emptyStateDescription('Enter a last name or first name to display matching runners.');
    }
}
