<?php

namespace App\Filament\Resources\Participants\Pages;

use App\Models\Participant;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Actions\SaveAction;  
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Events\RecordSaved;
use Illuminate\Validation\ValidationException;
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

    protected function mutateFormDataBeforeSave(array $data):array
    {
        
        $year = now()->year;
        $exists = Participant::where('firstName', $this->data['firstName'])
            ->where('lastName', $this->data['lastName'])
            ->whereDate('birthDate', $this->data['birthDate'])
            ->where('year',$year)
            ->where('created_by',Auth::user()->username)
            ->exists();
         
         if (!$exists) {
                Notification::make()
                    ->danger()
                    ->title('Not Authorize')
                    ->body('You are not the creator of this participant')
                    ->send();
               
                throw ValidationException::withMessages([
                    'firstName' => 'Not Authorize! You are not the creator of this participant.',
                ]);
            }

        return $this->data;
    }

    // protected function getFormActions(): array
    // {
    //     return [
    //         SaveAction::make('save')
    //             ->label('Save Changes')
    //             ->visible(fn ($record) => $record->created_by === Auth::user()->username),
    //         Action::make('cancel')
    //             ->label('Cancel')
    //             ->color('secondary')
    //             ->extraAttributes([
    //                 'class' => 'border border-gray-400 text-gray-700 hover:bg-gray-100 rounded-lg px-4 py-2 transition',
    //             ])
    //             ->url($this->getResource()::getUrl('index')),
    //     ];
    // }

      
    // protected function getFormActions(): array
    // { 
    //     return [
    //         EditAction::make()
    //             ->label('Edit Record')
    //             ->visible(fn ($record) => $record->created_by === Auth::user()->username),
    //         Action::make('cancel')
    //         ->label('Cancel')
    //         ->color('secondary')
    //         ->extraAttributes([
    //                 'class' => 'border border-gray-400 text-gray-700 hover:bg-gray-100 rounded-lg px-4 py-2 transition',
    //             ])
    //         ->url($this->getResource()::getUrl('index')),
    //     ];
    // }

   
}
