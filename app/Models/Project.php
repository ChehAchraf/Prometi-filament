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
    ];

    public function chefDeChantier()
    {
        return $this->belongsTo(User::class, 'chef_de_chantier_id');
    }
    
}
