<?php

namespace App\Filament\Resources\Participants\Pages;

use App\Models\Participant;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\Participants\ParticipantResource;

class CreateParticipant extends CreateRecord
{
    protected static string $resource = ParticipantResource::class;
    

    protected function mutateFormDataBeforeCreate(array $data):array
    {

           
            $year = now()->year;
            $this->validate();
            $this->data['year'] = $year;
            $this->data['waiver'] = true;
            $this->data['referenceNumber'] = '0';
            $this->data['created_by'] = Auth::user()->username;

            $exists = Participant::where('year', $this->data['year'])
            ->where('firstName', $this->data['firstName'])
            ->where('lastName', $this->data['lastName'])
            ->whereDate('birthDate', $this->data['birthDate'])
            ->where('year',$this->data['year'])
            ->exists();
            
            if ($exists) {
                Notification::make()
                    ->danger()
                    ->title('Duplicate record')
                    ->body('This participant already exists for the current year.')
                    ->send();
               
                throw ValidationException::withMessages([
                    'firstName' => 'Duplicate record! This participant already exists for the current year.',
                ]);
            }

            $cat = DB::table('subcategories as s')
                    ->leftJoin('participants as p',function($join) use ($year) {
                          $join->on('p.categoryDescription','=','s.categoryDescription')    
                               ->on('p.subDescription','=','s.subDescription')   
                               ->where('p.year','=',$year);             
                    })
                    ->select(
                        's.nop',
                        's.categoryDescription',
                        's.subDescription',
                        DB::raw('COUNT(p.id) as registered_count')
                    )
                    ->where('s.categoryDescription',$this->data['categoryDescription']) 
                    ->where('s.subDescription',$this->data['subDescription']) 
                    ->groupBy('s.nop', 's.categoryDescription', 's.subDescription')
                    ->first();
                    
    

            if($cat->registered_count >= $cat->nop)
            {
                Notification::make()
                    ->danger()
                    ->title('Maximum Participants')
                    ->body('Participant slots are already full.')
                    ->send();
    
                throw ValidationException::withMessages([
                    'Maximum Participants' => 'Participant slots are already full.',
                ]);

            }
        
        return $this->data;
        
    }
    
}
