<?php

namespace App\Policies;

use App\Models\Pointage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PointagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() ;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pointage $pointage): bool
    {
        return $user->isAdmin() || $user->isAgent() || $user->isChefDeChantier() || $user->isMagasinier() || $user->isRh();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isChefDeChantier();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pointage $pointage): bool
    {
        return $user->isAdmin() || $user->isChefDeChantier();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pointage $pointage): bool
    {
        return $user->isAdmin() || $user->isChefDeChantier();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pointage $pointage): bool
    {
        return $user->isAdmin() || $user->isChefDeChantier();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pointage $pointage): bool
    {
        return $user->isAdmin() || $user->isChefDeChantier();
    }
}
