<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Participant;
use Illuminate\Auth\Access\HandlesAuthorization;

class ParticipantPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Participant');
    }

    public function view(AuthUser $authUser, Participant $participant): bool
    {
        return $authUser->can('View:Participant');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Participant');
    }

    public function update(AuthUser $authUser, Participant $participant): bool
    {
        return $authUser->can('Update:Participant');
    }

    public function delete(AuthUser $authUser, Participant $participant): bool
    {
        return $authUser->can('Delete:Participant');
    }

    public function restore(AuthUser $authUser, Participant $participant): bool
    {
        return $authUser->can('Restore:Participant');
    }

    public function forceDelete(AuthUser $authUser, Participant $participant): bool
    {
        return $authUser->can('ForceDelete:Participant');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Participant');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Participant');
    }

    public function replicate(AuthUser $authUser, Participant $participant): bool
    {
        return $authUser->can('Replicate:Participant');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Participant');
    }

}