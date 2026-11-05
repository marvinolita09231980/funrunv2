<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ParticipantPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:Participant');
    }

    public function view(User $authUser, Participant $participant): bool
    {
        return $authUser->can('View:Participant')
            && (
                ! $authUser->hasRole('focal user')
                || $authUser->canManageParticipant($participant)
            );
    }

    public function create(User $authUser): bool
    {
        return $authUser->can('Create:Participant');
    }

    public function update(User $authUser, Participant $participant): bool
    {
        return $authUser->can('Update:Participant')
            && $authUser->canManageParticipant($participant);
    }

    public function delete(User $authUser, Participant $participant): bool
    {
        return $authUser->can('Delete:Participant')
            && $authUser->canManageParticipant($participant);
    }

    public function restore(User $authUser, Participant $participant): bool
    {
        return ! $authUser->hasRole('focal user')
            && $authUser->can('Restore:Participant')
            && $authUser->canManageParticipant($participant);
    }

    public function forceDelete(User $authUser, Participant $participant): bool
    {
        return ! $authUser->hasRole('focal user')
            && $authUser->can('ForceDelete:Participant')
            && $authUser->canManageParticipant($participant);
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return ! $authUser->hasRole('focal user')
            && $authUser->can('ForceDeleteAny:Participant');
    }

    public function restoreAny(User $authUser): bool
    {
        return ! $authUser->hasRole('focal user')
            && $authUser->can('RestoreAny:Participant');
    }

    public function replicate(User $authUser, Participant $participant): bool
    {
        return ! $authUser->hasRole('focal user')
            && $authUser->can('Replicate:Participant')
            && $authUser->canManageParticipant($participant);
    }

    public function reorder(User $authUser): bool
    {
        return ! $authUser->hasRole('focal user')
            && $authUser->can('Reorder:Participant');
    }

}
