<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isDirecteurTechnique() || $user->isRh();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // Admin, RH, and Directeur Technique can view all projects
        if ($user->isAdmin() || $user->isDirecteurTechnique() || $user->isRh()) {
            return true;
        }
        
        // Chef de chantier can view projects they manage
        if ($user->isChefDeChantier() ) {
            return $project->chef_de_chantier_id === $user->id;
        }
        
        // Magasinier can view all projects
        if ($user->isMagasinier()) {
            return true;
        }
        
        // Chef de projet can view projects they're assigned to
        if ($user->isChefDeProjet()) {
            return $user->projects()->where('projects.id', $project->id)->exists();
        }
        
        // Agent can view projects they're assigned to
        if ($user->isAgent()) {
            return $user->projects()->where('projects.id', $project->id)->exists();
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only Admin and RH can create projects
        return $user->isAdmin() || $user->isRh();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // Admin and RH can update any project
        if ($user->isAdmin() || $user->isRh()) {
            return true;
        }
        
        // Chef de chantier can update projects they manage
        if ($user->isChefDeChantier()) {
            return $project->chef_de_chantier_id === $user->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        // Only Admin and RH can delete projects
        return $user->isAdmin() || $user->isRh();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        // Only Admin and RH can restore projects
        return $user->isAdmin() || $user->isRh();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        // Only Admin can permanently delete projects
        return $user->isAdmin() || $user->isRh();
    }
}
