<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Subcategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubcategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Subcategory');
    }

    public function view(AuthUser $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('View:Subcategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Subcategory');
    }

    public function update(AuthUser $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('Update:Subcategory');
    }

    public function delete(AuthUser $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('Delete:Subcategory');
    }

    public function restore(AuthUser $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('Restore:Subcategory');
    }

    public function forceDelete(AuthUser $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('ForceDelete:Subcategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Subcategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Subcategory');
    }

    public function replicate(AuthUser $authUser, Subcategory $subcategory): bool
    {
        return $authUser->can('Replicate:Subcategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Subcategory');
    }

}