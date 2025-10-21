<?php

namespace App\Filament\Resources\Participants\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\ParticipantExporter;
use App\Filament\Resources\Participants\ParticipantResource;

class ListParticipants extends ListRecords
{
    protected static string $resource = ParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [

            CreateAction::make(),

        ];
    }
}
