<?php

namespace App\Filament\Resources\Finishers\Pages;

use App\Filament\Resources\Finishers\FinisherResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFinisher extends EditRecord
{
    protected static string $resource = FinisherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
