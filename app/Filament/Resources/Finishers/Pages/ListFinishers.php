<?php

namespace App\Filament\Resources\Finishers\Pages;

use App\Filament\Resources\Finishers\FinisherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinishers extends ListRecords
{
    protected static string $resource = FinisherResource::class;

    /**
     * Keep filter selections in Livewire state without exposing them in the URL.
     *
     * @var array<string, mixed>|null
     */
    public ?array $tableFilters = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
