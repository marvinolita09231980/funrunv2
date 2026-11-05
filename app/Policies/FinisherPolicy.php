<?php

namespace App\Policies;

use App\Models\Finisher;
use App\Models\User;

class FinisherPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasAnyRole(['superadmin', 'super_admin'])
            ? true
            : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Finisher');
    }

    public function view(User $user, Finisher $finisher): bool
    {
        return $user->can('View:Finisher');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Finisher');
    }

    public function update(User $user, Finisher $finisher): bool
    {
        return $user->can('Update:Finisher');
    }

    public function delete(User $user, Finisher $finisher): bool
    {
        return $user->can('Delete:Finisher');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('Delete:Finisher');
    }

    public function restore(User $user, Finisher $finisher): bool
    {
        return $user->can('Restore:Finisher');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Finisher');
    }

    public function forceDelete(User $user, Finisher $finisher): bool
    {
        return $user->can('ForceDelete:Finisher');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Finisher');
    }

    public function replicate(User $user, Finisher $finisher): bool
    {
        return $user->can('Replicate:Finisher');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Finisher');
    }
}
