<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'role',
        'status',
        'matricule',
        'fonction',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    /**
     * Get the pointages for the user
     */
    public function pointages()
    {
        return $this->belongsToMany(Pointage::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin' ? true : false;
    }

    public function isRh()
    {
        return $this->role === 'rh' ? true : false;
    }

    public function isAgent()
    {
        return $this->role === 'agent' ? true : false;
    }

    public function isChefDeChantier()
    {
        return $this->role === 'chef_de_chantier' ? true : false;
    }

    public function isMagasinier()
    {
        return $this->role === 'magasinier' ? true : false;
    }
    
    public function isChefDeProjet()
    {
        return $this->role === 'chef_de_projet' ? true : false;
    }
    
    public function isDirecteurTechnique()
    {
        return $this->role === 'directeur_technique' ? true : false;
    }
    
    /**
     * Get the projects where this user is assigned
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user', 'user_id', 'project_id');
    }
    
    /**
     * Get the projects where this user is the chef de chantier
     */
    public function managedProjects()
    {
        return $this->hasMany(Project::class, 'chef_de_chantier_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role !== 'collaborateur';
    }

}
