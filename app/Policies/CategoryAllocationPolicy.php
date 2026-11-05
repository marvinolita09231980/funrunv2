<?php

namespace App\Policies;

use App\Models\CategoryAllocation;
use App\Models\User;

class CategoryAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'superadmin',
            'super_admin',
            'administrative officer',
            'ao',
        ]);
    }

    public function view(User $user, CategoryAllocation $allocation): bool
    {
        return $this->viewAny($user)
            && $user->administersCategory($allocation->categoryDescription);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'superadmin', 'super_admin']);
    }

    public function update(User $user, CategoryAllocation $allocation): bool
    {
        return $user->hasAnyRole(['admin', 'superadmin', 'super_admin']);
    }

    public function delete(User $user, CategoryAllocation $allocation): bool
    {
        return $user->hasAnyRole(['admin', 'superadmin', 'super_admin'])
            && ! $allocation->subcategories()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
