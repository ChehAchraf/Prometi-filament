<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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

    protected static function booted()
    {
        static::addGlobalScope('rh_projects', function (Builder $builder) {
            if (auth()->check() && auth()->user()->role === 'rh') {
                $builder->whereHas('users', function ($query) {
                    $query->where('users.id', auth()->id());
                });
            }
        });
    }

    public function chef_de_chantier()
    {
        return $this->belongsTo(User::class, 'chef_de_chantier_id');
    }

    /**
     * Get the users assigned to this project
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->select(['users.id as id', 'users.name', 'users.email', 'users.role']);
    }

    /**
     * Get the pointages for this project
     */
    public function pointages()
    {
        return $this->hasMany(Pointage::class);
    }
}
