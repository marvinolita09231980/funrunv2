<?php

namespace App\Filament\Resources\Finishers\Schemas;

use App\Models\Finisher;
use App\Models\Participant;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Fieldset;

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
                           if (Finisher::where('participants_id', $state)->exists()) {
                                Notification::make()
                                    ->title('Duplicate Participant')
                                    ->body('This participant already exists in the finishers list.')
                                    ->danger()
                                    ->send();
                                
                                $set('fullname', null);
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
                        ->numeric()
                        ->maxLength(4)
                        ->rule(function($get) {
                            return function ($attribute, $value, $fail) use ($get) {

                                $distance = $get('distance_category');

                                // Must be exactly 4 digits
                                if (strlen($value) !== 4) {
                                    return $fail('Race Bib must be exactly 4 digits.');
                                }

                                // 10K → bib must start with 1
                                if ($distance === '10K' && !str_starts_with($value, '1')) {
                                    return $fail('10K bib numbers must start with 1.');
                                }

                                // 5K → bib must start with 5
                                if ($distance === '5K' && !str_starts_with($value, '5')) {
                                    return $fail('5K bib numbers must start with 5.');
                                }

                                // 3K → bib must start with 3
                                if ($distance === '3K' && !str_starts_with($value, '3')) {
                                    return $fail('3K bib numbers must start with 3.');
                                }
                            };
                        }),
                        TimePicker::make('finish_time')
                        ->label('Finish Time')
                        ->seconds(true)        
                       
                        ->required(),
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
               
         }
         
        return $id;
    }
}
