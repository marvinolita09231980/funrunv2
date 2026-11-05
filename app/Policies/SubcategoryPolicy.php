<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubcategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:Subcategory');
    }

    public function view(User $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('View:Subcategory')
            && $authUser->administersCategory($subcategory->categoryDescription);
    }

    public function create(User $authUser): bool
    {
        return $authUser->can('Create:Subcategory');
    }

    public function update(User $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('Update:Subcategory')
            && $authUser->administersCategory($subcategory->categoryDescription);
    }

    public function delete(User $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('Delete:Subcategory');
    }

    public function restore(User $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('Restore:Subcategory');
    }

    public function forceDelete(User $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('ForceDelete:Subcategory');
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Subcategory');
    }

    public function restoreAny(User $authUser): bool
    {
        return $authUser->can('RestoreAny:Subcategory');
    }

    public function replicate(User $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('Replicate:Subcategory');
    }

    public function reorder(User $authUser): bool
    {
        return $authUser->can('Reorder:Subcategory');
    }

}
