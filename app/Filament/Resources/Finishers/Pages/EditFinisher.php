<?php

namespace App\Filament\Resources\Finishers\Pages;

use App\Models\Award;
use App\Models\Finisher;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Finishers\FinisherResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $shouldTransferAward = (bool) ($data['reassign_award'] ?? false);
        unset($data['reassign_award']);

        $awardId = $data['award_id'] ?? null;

        if ($awardId) {
            Award::query()->whereKey($awardId)->lockForUpdate()->first();
            $holder = Finisher::query()
                ->where('award_id', $awardId)
                ->whereKeyNot($record->getKey())
                ->lockForUpdate()
                ->first();

            if ($holder && ! $shouldTransferAward) {
                throw ValidationException::withMessages([
                    'data.award_id' => "This award is assigned to {$holder->runner_name}. Confirm the transfer to continue.",
                ]);
            }

            $holder?->update(['award_id' => null]);
        }

        return parent::handleRecordUpdate($record, $data);
    }

}
