<?php

namespace App\Filament\Resources\Participants\Pages;

use App\Models\Participant;
use App\Models\CategoryAllocation;
use App\Models\Subcategory;
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
            DeleteAction::make(),
         
        ];
    }

    protected function mutateFormDataBeforeSave(array $data):array
    {
        
        if (
            Auth::user()->isAdministrativeOfficer()
            && ! Auth::user()->administersCategory($data['categoryDescription'] ?? null)
        ) {
                Notification::make()
                    ->danger()
                    ->title('Not Authorized')
                    ->body('You may only edit participants in your administered category.')
                    ->send();
               
                throw ValidationException::withMessages([
                    'categoryDescription' => 'You may only edit participants in your administered category.',
                ]);
        }

        if (
            Auth::user()->hasRole('focal user')
            && ! Auth::user()->canManageParticipantData(
                $data['categoryDescription'] ?? null,
                $data['subDescription'] ?? null,
            )
        ) {
            Notification::make()
                ->danger()
                ->title('Not Authorized')
                ->body('You may only edit participants in your assigned subcategory.')
                ->send();

            throw ValidationException::withMessages([
                'subDescription' => 'You may only edit participants in your assigned subcategory.',
            ]);
        }

        $category = $data['categoryDescription'] ?? null;
        $year = (int) ($this->record->year ?? now()->year);
        $allocation = CategoryAllocation::query()->where('categoryDescription', $category)->first();
        $registered = Participant::query()->where('categoryDescription', $category)
            ->where('year', $year)->whereKeyNot($this->record->getKey())->count();

        if ($allocation && $registered >= $allocation->allocated_participants) {
            throw ValidationException::withMessages(['data.categoryDescription' => 'Participant slots are already full.']);
        }

        if (! Subcategory::query()->where('categoryDescription', $category)->exists()) {
            if (! $allocation) {
                throw ValidationException::withMessages(['data.categoryDescription' => 'The category allocation is not available.']);
            }

            if ($category !== 'OPEN CATEGORY') {
                $data['subDescription'] = '';
            } else {
                $data['subDescription'] ??= '';
            }
        } elseif ($category !== 'OPEN CATEGORY') {
            $subcategory = Subcategory::query()->where('categoryDescription', $category)
                ->where('subDescription', $data['subDescription'] ?? null)->first();
            $count = Participant::query()->where('categoryDescription', $category)
                ->where('subDescription', $data['subDescription'] ?? null)
                ->where('year', $year)->whereKeyNot($this->record->getKey())->count();

            if (! $subcategory || $count >= $subcategory->nop) {
                throw ValidationException::withMessages(['data.subDescription' => 'Select an available subcategory with remaining slots.']);
            }
        }

        return $data;
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
