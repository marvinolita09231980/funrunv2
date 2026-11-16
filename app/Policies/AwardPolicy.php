<?php

namespace App\Policies;

use App\Models\Award;
use App\Models\User;

class AwardPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasAnyRole(['superadmin', 'super_admin'])
            ? true
            : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Award $award): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Award $award): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Award $award): bool
    {
        return $this->viewAny($user) && ! $award->finishers()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
