<?php

namespace App\Policies;

use App\Models\ScanEvent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ScanEventPolicy
{
    /**
     * scan_events is append-only (SPEC §8) — update/delete/restore/forceDelete stay
     * denied even for super_admin, deliberately excluded from the bypass below.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, ['update', 'delete', 'restore', 'forceDelete'], true)) {
            return false;
        }

        return $user->hasRole(User::ROLE_SUPER_ADMIN) ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ScanEvent $scanEvent): bool
    {
        return false;
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
    public function update(User $user, ScanEvent $scanEvent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScanEvent $scanEvent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ScanEvent $scanEvent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ScanEvent $scanEvent): bool
    {
        return false;
    }
}
