<?php

namespace App\Policies;

use App\Models\ShopPurchase;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShopPurchasePolicy
{
    use HandlesAuthorization;
    use OwnsRecordOrSuperAdmin;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_shop::purchase');
    }

    public function view(User $user, ShopPurchase $shopPurchase): bool
    {
        return $user->can('view_shop::purchase');
    }

    public function create(User $user): bool
    {
        return $user->can('create_shop::purchase');
    }

    public function update(User $user, ShopPurchase $shopPurchase): bool
    {
        return $user->can('update_shop::purchase')
            && $this->ownsRecordOrSuperAdmin($user, $shopPurchase);
    }

    public function delete(User $user, ShopPurchase $shopPurchase): bool
    {
        return $user->can('delete_shop::purchase')
            && $this->ownsRecordOrSuperAdmin($user, $shopPurchase);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_any_shop::purchase');
    }

    public function forceDelete(User $user, ShopPurchase $shopPurchase): bool
    {
        return $user->can('force_delete_shop::purchase')
            && $this->ownsRecordOrSuperAdmin($user, $shopPurchase);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole('super_admin') && $user->can('force_delete_any_shop::purchase');
    }

    public function restore(User $user, ShopPurchase $shopPurchase): bool
    {
        return $user->can('restore_shop::purchase')
            && $this->ownsRecordOrSuperAdmin($user, $shopPurchase);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole('super_admin') && $user->can('restore_any_shop::purchase');
    }

    public function replicate(User $user, ShopPurchase $shopPurchase): bool
    {
        return $user->can('replicate_shop::purchase')
            && $this->ownsRecordOrSuperAdmin($user, $shopPurchase);
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_shop::purchase');
    }
}
