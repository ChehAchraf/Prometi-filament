<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
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

}
