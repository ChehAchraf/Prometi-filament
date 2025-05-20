<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pointage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'date',
        'heure_debut',
        'heure_fin',
        'heures_travaillees',
        'heures_supplementaires',
        'heures_supplementaires_approuvees',
        'status',
    ];
    
    protected $casts = [
        'date' => 'date',
        'heure_debut' => 'datetime',
        'heure_fin' => 'datetime',
        'heures_supplementaires_approuvees' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    /**
     * Calculate the total hours worked between start and end time
     * This is called before saving the record
     */
    public function calculateHoursWorked()
    {
        if ($this->heure_debut && $this->heure_fin) {
            $debut = new \DateTime($this->heure_debut);
            $fin = new \DateTime($this->heure_fin);
            
            // If end time is before start time, assume it's the next day
            if ($fin < $debut) {
                $fin->modify('+1 day');
            }
            
            $interval = $debut->diff($fin);
            $hours = $interval->h + ($interval->i / 60);
            
            $this->heures_travaillees = round($hours, 2);
            $this->calculateOvertime();
        }
        
        return $this;
    }
    
    /**
     * Calculate overtime hours (anything over 8 hours)
     */
    public function calculateOvertime()
    {
        if ($this->heures_travaillees > 8) {
            $this->heures_supplementaires = round($this->heures_travaillees - 8, 2);
        } else {
            $this->heures_supplementaires = 0;
        }
        
        return $this;
    }
    
    /**
     * Approve overtime hours
     */
    public function approveOvertime($approve = true)
    {
        $this->heures_supplementaires_approuvees = $approve;
        return $this;
    }
    
    /**
     * Get the effective hours (regular hours + approved overtime)
     */
    public function getEffectiveHoursAttribute()
    {
        $baseHours = min($this->heures_travaillees, 8);
        $overtime = $this->heures_supplementaires_approuvees ? $this->heures_supplementaires : 0;
        
        return $baseHours + $overtime;
    }
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($pointage) {
            $pointage->calculateHoursWorked();
        });
    }
}
