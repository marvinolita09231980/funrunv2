<?php

namespace App\Filament\Resources\Finishers\Pages;

use App\Filament\Resources\Finishers\FinisherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinishers extends ListRecords
{
    protected static string $resource = FinisherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
