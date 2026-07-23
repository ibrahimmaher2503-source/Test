<?php

namespace App\Policies;

use App\Models\InventoryCountLine;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InventoryCountLinePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN) ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     *
     * Read-only monitoring in the session detail page (SPEC §4.6) — count lines
     * are only ever written by the scanning screen, never through Filament CRUD.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventoryCountLine $inventoryCountLine): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InventoryCountLine $inventoryCountLine): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryCountLine $inventoryCountLine): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InventoryCountLine $inventoryCountLine): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InventoryCountLine $inventoryCountLine): bool
    {
        return false;
    }
}
