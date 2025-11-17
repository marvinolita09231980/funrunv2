<?php

namespace App\Filament\Resources\Finishers\Pages;

use Carbon\Carbon;
use App\Models\Participant;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Finishers\FinisherResource;

class CreateFinisher extends CreateRecord
{
    protected static string $resource = FinisherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $participantId = $data['fullname'];
       
        $time = $data['finish_time'];
        $currentDatePH = Carbon::now('Asia/Manila')->format('Y-m-d');
        $data['finish_time'] = $currentDatePH . ' ' . $time;
    
        if ($participantId) {
            $participant = Participant::where('id', $participantId)->first();
            
            if ($participant) {
                $data['participants_id']       = $participant->id;
                $data['firstName']             = $participant->firstName;
                $data['lastName']              = $participant->lastName;
                $data['middleInitial']         = $participant->middleInitial;
                $data['categoryDescription']   = $participant->categoryDescription;
                $data['subDescription']        = $participant->subDescription;
                $data['gender']                = $participant->gender;
                $data['birthDate']             = $participant->birthDate;
                $data['distanceCategory']      = $participant->distanceCategory;
                $data['created_by']            = Auth::user()->username;
                $data['finisher_rank']         = '';
            }
        }
       
      
        unset($data['fullname']);

        return $data;
    }
}
