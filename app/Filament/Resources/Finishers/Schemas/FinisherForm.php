<?php

namespace App\Filament\Resources\Finishers\Schemas;

use App\Models\Award;
use App\Models\Finisher;
use App\Models\Participant;
use Closure;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class FinisherForm
{
    public static function configure(Schema $schema): Schema
    {
       
        return $schema
            ->components([
                Fieldset::make('Search Participants')
                  ->schema([
                       
                    Select::make('fullname')
                        ->label('Full Name')
                        ->searchable()
                        ->reactive()
                        ->getSearchResultsUsing(function(string $search){
                                $year = date('Y');
                                $results = DB::table('participants')
                                    ->select(
                                        'id',
                                        DB::raw("TRIM(CONCAT(
                                            COALESCE(lastName, ''), ', ',
                                            COALESCE(firstName, ''), ' ',
                                            COALESCE(middleInitial, '')
                                        )) AS full_name")
                                    )
                                    ->where('year', $year)
                                    ->where(function ($query) use ($search) {
                                        $query->where('lastName', 'LIKE', "%{$search}%")
                                            ->orWhere('firstName', 'LIKE', "%{$search}%")
                                            ->orWhere('middleInitial', 'LIKE', "%{$search}%");
                                    })
                                    ->orderBy('lastName')
                                    ->limit(20)
                                    ->get()
                                    ->filter(fn ($row) => !empty($row->full_name))   
                                    ->pluck('full_name', 'id');
                                return $results;
                        })
                        ->getOptionLabelUsing(function ($value) {
                             if (empty($value)) {
                                    return '';
                                }

                                $year = date('Y');

                                return DB::table('participants')
                                    ->where('year', $year)
                                    ->where('id', $value)
                                    ->selectRaw("TRIM(CONCAT(
                                            COALESCE(lastName, ''), ', ',
                                            COALESCE(firstName, ''), ' ',
                                            COALESCE(middleInitial, '')
                                        )) AS full_name")
                                    ->value('full_name') ?? '';
                        })
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                           $set('award_id', null);

                           if (blank($state)) {
                                foreach (['firstName', 'lastName', 'middleInitial', 'birthDate', 'distanceCategory', 'racebib'] as $field) {
                                    $set($field, null);
                                }

                                return;
                           }

                           if (Finisher::where('participants_id', $state)->exists()) {
                                Notification::make()
                                    ->title('Duplicate Participant')
                                    ->body('This participant already exists in the finishers list.')
                                    ->danger()
                                    ->send();
                                
                                $set('fullname', null);
                                foreach (['firstName', 'lastName', 'middleInitial', 'birthDate', 'distanceCategory', 'racebib'] as $field) {
                                    $set($field, null);
                                }
                            }
                            else{
                                $exists = self::queryParticipants($get, $set);
                            }
                        })
                        ->disabled(fn ($context) => $context === 'edit')
                        ->columnSpan(2)
                        ->required(fn ($context) => $context === 'create')
                        ->visible(fn($context) => $context === 'create'),
                        TextInput::make('fullname_text')
                        ->label('Full Name')
                        ->disabled() 
                        ->columnSpan(2)
                        ->afterStateHydrated(function ($state, callable $set, $get, $record) {
                            if (!$record) return;
                            $middleInitial = $record->middleInitial ? "{$record->middleInitial}." : '';
                            $set('fullname_text', trim("{$record->lastName}, {$record->firstName} {$middleInitial}"));
                        })
                        ->dehydrated(false)
                        ->visible(fn($context) => $context === 'edit'),

                        TextInput::make('racebib')
                        ->label('Race Bib No.')
                        ->required()
                        ->reactive()
                        ->inputMode('numeric')
                        ->maxLength(5)
                        ->helperText(function (Get $get): string {
                            $distance = $get('distanceCategory');
                            $prefix = Finisher::raceBibPrefixForDistance($distance);

                            return $prefix
                                ? Participant::distanceLabel($distance)
                                    ." race bib: exactly 5 numbers starting with {$prefix}."
                                : 'Select a runner to determine the required race bib prefix.';
                        })
                        ->extraInputAttributes(function (Get $get): array {
                            $prefix = Finisher::raceBibPrefixForDistance($get('distanceCategory'));

                            return [
                                'pattern' => '[0-9]{5}',
                                'autocomplete' => 'off',
                                'oninput' => $prefix
                                    ? "this.value = '{$prefix}' + this.value.replace(/[^0-9]/g, '').replace(/^{$prefix}/, '').slice(0, 4)"
                                    : "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 5)",
                            ];
                        })
                        ->rule(function($get) {
                            return function ($attribute, $value, $fail) use ($get) {

                                $distance = $get('distanceCategory');
                                $raceBib = (string) $value;

                                if (preg_match('/^\d{5}$/', $raceBib) !== 1) {
                                    return $fail('Race Bib must contain exactly 5 numbers.');
                                }

                                $requiredPrefix = Finisher::raceBibPrefixForDistance($distance);

                                if ($requiredPrefix && ! str_starts_with($raceBib, $requiredPrefix)) {
                                    return $fail(Participant::distanceLabel($distance) . " bib numbers must start with {$requiredPrefix}.");
                                }
                            };
                        }),
                        TimePicker::make('finish_time')
                        ->label('Finish Time')
                        ->seconds(true)
                        ->default(
                            now('Asia/Manila')->format('h:i A')
                        ),
                        Select::make('award_id')
                            ->label('Award')
                            ->placeholder(fn (Get $get, ?Finisher $record): string => filled(
                                $get('distanceCategory') ?? $record?->distanceCategory
                            ) ? 'No award' : 'Select and validate a runner first')
                            ->options(function (Get $get, ?Finisher $record): array {
                                $distance = $get('distanceCategory') ?? $record?->distanceCategory;

                                return filled($distance)
                                    ? Award::groupedOptions($record?->award_id, $distance)
                                    : [];
                            })
                            ->disabled(fn (Get $get, ?Finisher $record): bool => blank(
                                $get('distanceCategory') ?? $record?->distanceCategory
                            ))
                            ->rule(function (Get $get, ?Finisher $record): Closure {
                                return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                    if (blank($value) || (int) $value === (int) $record?->award_id) {
                                        return;
                                    }

                                    $distance = $get('distanceCategory') ?? $record?->distanceCategory;

                                    if (! Award::isSelectableForDistance($value, $distance)) {
                                        $fail('The selected award does not match the runner’s registered distance category.');

                                        return;
                                    }

                                    if (
                                        ($holder = Finisher::awardHolder($value, $record?->getKey()))
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
                            ->afterStateUpdated(function (mixed $state, Set $set, ?Finisher $record): void {
                                $set('reassign_award', false);
                                $holder = Finisher::awardHolder($state, $record?->getKey());

                                if (! $holder) {
                                    return;
                                }

                                Notification::make()
                                    ->title('Award has a current winner')
                                    ->body(
                                        "{$holder->runner_name} already received this award"
                                        .(filled($holder->created_by) ? " (recorded by {$holder->created_by}). " : '. ')
                                        .'Enable “Transfer this award” only after the contest has been resolved.'
                                    )
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            })
                            ->searchable()
                            ->preload(),
                        Toggle::make('reassign_award')
                            ->label('Transfer this award to this winner')
                            ->helperText(function (Get $get, ?Finisher $record): ?string {
                                $holder = Finisher::awardHolder($get('award_id'), $record?->getKey());

                                return $holder
                                    ? "This will remove the award from {$holder->runner_name}."
                                    : null;
                            })
                            ->visible(fn (Get $get, ?Finisher $record): bool => (bool) Finisher::awardHolder(
                                $get('award_id'),
                                $record?->getKey(),
                            ))
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),
                 ])->columns(3),   
                 Fieldset::make('Runner Info')
                  ->schema([
                       
                        TextInput::make('firstName')
                        ->label('First Name')
                        ->disabled(),
                        TextInput::make('lastName')
                        ->label('Last Name')
                        ->disabled(),
                        TextInput::make('middleInitial')
                        ->label('Middle Initial')
                        ->disabled(),
                        DatePicker::make('birthDate')
                        ->label('Birth Date')
                        ->disabled(),
                        TextInput::make('distanceCategory')
                        ->label('Distance Category')
                        ->formatStateUsing(fn (?string $state): string => Participant::distanceLabel($state))
                        ->disabled(),

                  ])
                  ->columns(3),
                  

        ]);
      
    }

    public static function queryParticipants(callable $get, callable $set): int
    {    $id = $get("fullname");
         
         if($id)
         {
            $participant = Participant::where('id', $id)
                            ->first();
                $set('firstName', $participant->firstName);
                $set('lastName', $participant->lastName);
                $set('middleInitial', $participant->middleInitial);
                $set('birthDate', $participant->birthDate);
                $set('distanceCategory', $participant->distanceCategory);
                $set('racebib', Finisher::raceBibPrefixForDistance($participant->distanceCategory));
               
         }
         
        return $id;
    }
}
