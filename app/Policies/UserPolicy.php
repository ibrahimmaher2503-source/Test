<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(User::ROLE_SUPER_ADMIN) ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     *
     * Branch Managers can reach the Users list (scoped to their own branch's
     * counters by the Filament resource's query, not here).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $this->branchManagerOwnsCounter($user, $model);
    }

    /**
     * Determine whether the user can create models.
     *
     * Branch Managers may only create `counter` users (enforced by the Filament
     * resource form, not here — this just gates reaching the create form).
     */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $this->branchManagerOwnsCounter($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $this->branchManagerOwnsCounter($user, $model);
    }

    /**
     * SPEC §2: Branch Manager can create/edit `counter` users scoped to their own
     * branch only — never other managers, admins, or another branch's counters.
     */
    private function branchManagerOwnsCounter(User $user, User $model): bool
    {
        return $user->hasRole(User::ROLE_BRANCH_MANAGER)
            && $model->hasRole(User::ROLE_COUNTER)
            && $model->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
