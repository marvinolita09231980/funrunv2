<?php

namespace App\Filament\Resources\Finishers\Pages;

use App\Models\Participant;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Finishers\FinisherResource;

class CreateFinisher extends CreateRecord
{
    protected static string $resource = FinisherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $id = $data['fullname'];
        $participants = Participant::where('id', $id)
                        ->first();

                    
        $data['participant_number']     = 
        $data['first_name']             =
        $data['last_name']              =
        $data['middle_initial']         =
        $data['category']               =
        $data['subcategory']            =
        $data['gender']                 =
        $data['distance_category']      =
        $data['racebib']                =
        $data['guntime']                =
        $data['finisher_rank']          =
        $data['created_at']             =
        $data['updated_at']             =

        dd($data);
                       
        return $this->data;
    }
}
