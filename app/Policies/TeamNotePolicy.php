<?php

namespace App\Policies;

use App\Models\TeamNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamNotePolicy
{
    use HandlesAuthorization;
    use OwnsRecordOrSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_team::note');
    }

    public function view(User $user, TeamNote $teamNote): bool
    {
        return $user->can('view_team::note');
    }

    public function create(User $user): bool
    {
        return $user->can('create_team::note');
    }

    public function update(User $user, TeamNote $teamNote): bool
    {
        return $user->can('update_team::note')
            && $this->ownsRecordOrSuperAdmin($user, $teamNote);
    }

    public function delete(User $user, TeamNote $teamNote): bool
    {
        return $user->can('delete_team::note')
            && $this->ownsRecordOrSuperAdmin($user, $teamNote);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_any_team::note');
    }

    public function forceDelete(User $user, TeamNote $teamNote): bool
    {
        return $user->can('force_delete_team::note')
            && $this->ownsRecordOrSuperAdmin($user, $teamNote);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole('super_admin') && $user->can('force_delete_any_team::note');
    }

    public function restore(User $user, TeamNote $teamNote): bool
    {
        return $user->can('restore_team::note')
            && $this->ownsRecordOrSuperAdmin($user, $teamNote);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole('super_admin') && $user->can('restore_any_team::note');
    }

    public function replicate(User $user, TeamNote $teamNote): bool
    {
        return $user->can('replicate_team::note')
            && $this->ownsRecordOrSuperAdmin($user, $teamNote);
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_team::note');
    }
}
