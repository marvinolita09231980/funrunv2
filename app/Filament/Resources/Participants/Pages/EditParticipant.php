<?php

namespace App\Filament\Resources\Participants\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\SaveAction; 
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Events\RecordSaved;
use App\Filament\Resources\Participants\ParticipantResource;

class EditParticipant extends EditRecord
{
    protected static string $resource = ParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create New Participant'),
            DeleteAction::make()
            ->visible(fn ($record) => $record->created_by === Auth::user()->username),
         
        ];
    }
      
    protected function getFormActions(): array
    { 
        return [
            EditAction::make()
                ->label('Edit Record')
                ->visible(fn ($record) => $record->created_by === Auth::user()->username),
            Action::make('cancel')
            ->label('Cancel')
            ->color('secondary')
            ->extraAttributes([
                    'class' => 'border border-gray-400 text-gray-700 hover:bg-gray-100 rounded-lg px-4 py-2 transition',
                ])
            ->url($this->getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array{
        $data['updated_by'] = Auth::user()->username;
        return $data;
    }
}
