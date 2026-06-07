<?php

namespace App\Policies;

use App\Models\User;

trait OwnsRecordOrSuperAdmin
{
    protected function ownsRecordOrSuperAdmin(User $user, object $record): bool
    {
        return $user->hasRole('super_admin') || (int) $record->user_id === (int) $user->id;
    }
}
