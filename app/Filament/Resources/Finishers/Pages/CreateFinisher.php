<?php

namespace App\Filament\Resources\Finishers\Pages;

use App\Filament\Resources\Finishers\FinisherResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinisher extends CreateRecord
{
    protected static string $resource = FinisherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        dd($this->data);
        return $this->data;
    }
}
