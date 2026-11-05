<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        if (
            $user?->isAdministrativeOfficer()
            && ! $user->administersCategory($data['assigned_category'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'data.assigned_category' => 'Select one of the main categories assigned to your account.',
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
