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
        // RH users should not be able to view pointages
        if ($user->isRh()) {
            return false;
        }
        
        // All other roles can view pointages, but they will be filtered based on their role
        return $user->isAdmin() || $user->isChefDeChantier() || 
               $user->isMagasinier() || $user->isChefDeProjet() || $user->isDirecteurTechnique();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pointage $pointage): bool
    {
        // Admin can view all pointages
        if ($user->isAdmin()) {
            return true;
        }
        
        // RH can view all pointages
        // if ($user->isRh()) {
        //     return true;
        // }
        
        // Chef de chantier can view pointages for their projects
        if ($user->isChefDeChantier()) {
            $managedProjectIds = $user->managedProjects()->pluck('id')->toArray();
            return in_array($pointage->project_id, $managedProjectIds);
        }
        
        // Magasinier can view all pointages
        if ($user->isMagasinier()) {
            return true;
        }
        
        // Chef de projet can view pointages for their projects
        if ($user->isChefDeProjet()) {
            $projectIds = $user->projects()->pluck('id')->toArray();
            return in_array($pointage->project_id, $projectIds);
        }
        
        // Directeur technique can view all pointages
        if ($user->isDirecteurTechnique()) {
            return true;
        }
        
        // Agent can only view their own pointages
        if ($user->isAgent()) {
            $userIds = $pointage->users()->pluck('id')->toArray();
            return in_array($user->id, $userIds);
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only Chef de chantier and Magasinier can create pointages
        return $user->isAdmin() || $user->isChefDeChantier() || $user->isMagasinier();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pointage $pointage): bool
    {
        // Admin can update any pointage
        if ($user->isAdmin()) {
            return true;
        }
        
        // Chef de chantier can update pointages for their projects
        if ($user->isChefDeChantier()) {
            $managedProjectIds = $user->managedProjects()->pluck('id')->toArray();
            return in_array($pointage->project_id, $managedProjectIds);
        }
        
        // Magasinier can update pointages
        if ($user->isMagasinier()) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pointage $pointage): bool
    {
        // Only Admin and Chef de chantier can delete pointages
        if ($user->isAdmin()) {
            return true;
        }
        
        if ($user->isChefDeChantier()) {
            $managedProjectIds = $user->managedProjects()->pluck('id')->toArray();
            return in_array($pointage->project_id, $managedProjectIds);
        }
        
        return false;
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
