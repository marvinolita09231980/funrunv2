<?php

namespace App\Filament\Resources\Participants\Pages;

use App\Filament\Pages\PotentialDuplicates;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\ParticipantExporter;
use App\Filament\Resources\Participants\ParticipantResource;
use App\Filament\Resources\Participants\Widgets\ParticipantAllocationOverview;

class ListParticipants extends ListRecords
{
    protected static string $resource = ParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('potentialDuplicates')
                ->label('Potential Duplicates')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->url(PotentialDuplicates::getUrl())
                ->visible(fn (): bool => PotentialDuplicates::canAccess()),
            CreateAction::make(),

        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ParticipantAllocationOverview::class,
        ];
    }

   
}
