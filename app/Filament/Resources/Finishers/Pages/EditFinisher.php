<?php

namespace App\Filament\Resources\Finishers\Pages;

use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Finishers\FinisherResource;

class EditFinisher extends EditRecord
{
    protected static string $resource = FinisherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $time = $data['finish_time'];
        $currentDatePH = Carbon::now('Asia/Manila')->format('Y-m-d');
        $data['finish_time'] = $currentDatePH . ' ' . $time;
        return parent::mutateFormDataBeforeSave($data);
    }

}
