<?php

namespace App\Filament\Resources\Finishers\Schemas;

use App\Models\Participant;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
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
                            return DB::table('participants')
                                ->select(
                                    'id',
                                    DB::raw("CONCAT(lastName, ', ', firstName, ' ', middleInitial) AS full_name")
                                )
                                ->where('year', $year)
                                ->where(function ($query) use ($search) {
                                    $query->where('lastName', 'LIKE', "%{$search}%")
                                        ->orWhere('firstName', 'LIKE', "%{$search}%")
                                        ->orWhere('middleInitial', 'LIKE', "%{$search}%");
                                })
                                ->orderBy('lastName')
                                ->limit(20)
                                ->pluck('full_name', 'id');

                        })
                        ->getOptionLabelUsing(function ($value) {
                            if (!$value) {
                                return '';
                            }
                             $year = date('Y');
                             $p = DB::table('participants')
                                ->where('year', $year)
                                ->where('id', $value)
                                ->select(
                                    DB::raw("CONCAT(lastName, ', ', firstName, ' ', middleInitial) AS full_name")
                                )
                                ->first();

                            return $p?->full_name ?? '';
                        })
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $exists = self::queryParticipants($get, $set);
                        })
                        ->columnSpanFull()
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

                  ])->columns(3)
                  

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
                // $set('categoryDescription', $participant->categoryDescription);
                // $set('subDescription', $participant->subDescription);
                // $set('shirtSize', $participant->shirtSize);
                // $set('distanceCategory', $participant->distanceCategory);
                // $set('referenceNumber', $participant->referenceNumber);
         }
         
        return $id;
    }
}
