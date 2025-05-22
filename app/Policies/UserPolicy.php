<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin and RH can view all users
        return $user->isAdmin() || $user->isRh();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Admin and RH can view any user
        if ($user->isAdmin() || $user->isRh()) {
            return true;
        }
        
        // Chef de chantier can view users assigned to their projects
        if ($user->isChefDeChantier()) {
            $managedProjectIds = $user->managedProjects()->pluck('id')->toArray();
            $userProjectIds = $model->projects()->pluck('id')->toArray();
            
            return count(array_intersect($managedProjectIds, $userProjectIds)) > 0;
        }
        
        // Users can view their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only Admin and RH can create users
        return $user->isAdmin() || $user->isRh();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Admin can update any user
        if ($user->isAdmin()) {
            return true;
        }
        
        // RH can update any user except admins
        if ($user->isRh()) {
            return !$model->isAdmin();
        }
        
        // Users can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Admin can delete any user except themselves
        if ($user->isAdmin()) {
            return $user->id !== $model->id;
        }
        
        // RH can delete any user except admins and themselves
        if ($user->isRh()) {
            return !$model->isAdmin() && $user->id !== $model->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // Only Admin and RH can restore users
        return $user->isAdmin() || $user->isRh();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only Admin can permanently delete users
        return $user->isAdmin() && $user->id !== $model->id;
    }
}
