<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $creator = Auth::user();
        $data['created_by'] = $creator?->username;

        if (! $creator?->isAdministrativeOfficer()) {
            return $data;
        }

        $assignedCategory = $data['assigned_category'] ?? null;

        if (! $creator->administersCategory($assignedCategory)) {
            throw ValidationException::withMessages([
                'data.assigned_category' => 'Select one of the main categories assigned to your account.',
            ]);
        }

        $data['administered_category'] = null;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (Auth::user()?->isAdministrativeOfficer()) {
            $this->record->syncRoles(['focal user']);
        }
    }
}
