<?php

namespace App\Policies;

use App\Models\InventorySession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InventorySessionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN) ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     *
     * Inventory Sessions screen is Branch Manager (own branch) + Super Admin (all
     * branches) per SPEC §4.6. Counters never reach this resource — they work
     * through the scanning screen (Phase 7), which checks their own assignment
     * directly rather than through this policy.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventorySession $inventorySession): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER) && $inventorySession->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InventorySession $inventorySession): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER) && $inventorySession->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventorySession $inventorySession): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER) && $inventorySession->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InventorySession $inventorySession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InventorySession $inventorySession): bool
    {
        return false;
    }
}
