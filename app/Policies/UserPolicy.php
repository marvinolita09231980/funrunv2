<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(User $authUser, User $user): bool
    {
        if ($this->isProtectedSuperAdmin($authUser, $user)) {
            return false;
        }

        if ($authUser->isAdministrativeOfficer()) {
            return $user->hasRole('focal user')
                && $authUser->administersCategory($user->assigned_category);
        }

        return $authUser->can('View:User');
    }

    public function create(User $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(User $authUser, User $user): bool
    {
        if ($this->isProtectedSuperAdmin($authUser, $user)) {
            return false;
        }

        if ($authUser->isAdministrativeOfficer()) {
            return $authUser->can('Update:User')
                && $authUser->canManageCreatedFocalUser($user);
        }

        return $authUser->can('Update:User');
    }

    public function delete(User $authUser, User $user): bool
    {
        if (
            $this->isProtectedSuperAdmin($authUser, $user)
            || $user->hasActiveCategoryAssignments()
        ) {
            return false;
        }

        if ($authUser->isAdministrativeOfficer()) {
            return $authUser->can('Delete:User')
                && $authUser->canManageCreatedFocalUser($user);
        }

        return $authUser->can('Delete:User');
    }

    public function restore(User $authUser, User $user): bool
    {
        if ($this->isProtectedSuperAdmin($authUser, $user)) {
            return false;
        }

        return $authUser->can('Restore:User');
    }

    public function forceDelete(User $authUser, User $user): bool
    {
        if (
            $this->isProtectedSuperAdmin($authUser, $user)
            || $user->hasActiveCategoryAssignments()
        ) {
            return false;
        }

        return $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    public function restoreAny(User $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function replicate(User $authUser, User $user): bool
    {
        if ($this->isProtectedSuperAdmin($authUser, $user)) {
            return false;
        }

        return $authUser->can('Replicate:User');
    }

    public function reorder(User $authUser): bool
    {
        return $authUser->can('Reorder:User');
    }

    private function isProtectedSuperAdmin(User $authUser, User $user): bool
    {
        return $authUser->isAdminWithoutSuperAdmin() && $user->isSuperAdmin();
    }

}
