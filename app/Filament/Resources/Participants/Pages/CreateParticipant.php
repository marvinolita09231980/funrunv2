<?php

namespace App\Filament\Resources\Participants\Pages;

use App\Models\Participant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use App\Filament\Resources\Participants\ParticipantResource;

class CreateParticipant extends CreateRecord
{
    protected static string $resource = ParticipantResource::class;
    

    protected function mutateFormDataBeforeCreate(array $data):array
    {
            $this->validate();
            $this->data['year'] = date('Y');
            $this->data['waiver'] = true;
            $this->data['referenceNumber'] = '0';

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
       
        return $this->data;
        
    }
    
}
