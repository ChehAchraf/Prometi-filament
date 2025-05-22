<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'chef_de_chantier_id',
        'start_date',
        'end_date',
    ];

    public function chefDeChantier()
    {
        return $this->belongsTo(User::class, 'chef_de_chantier_id');
    }
    
    /**
     * Get the users assigned to this project
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id');
    }
    
    /**
     * Get the pointages for this project
     */
    public function pointages()
    {
        return $this->hasMany(Pointage::class);
    }
    
}
