<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pointage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // Keep this for backward compatibility
        'project_id',
        'date',
        'heure_debut',
        'heure_fin',
        'heures_travaillees',
        'heures_supplementaires',
        'heures_supplementaires_approuvees',
        'status',
        'is_jour_ferie',
        'coefficient',
        'commentaire',
    ];
    
    protected $casts = [
        'date' => 'date',
        'heure_debut' => 'datetime',
        'heure_fin' => 'datetime',
        'heures_supplementaires_approuvees' => 'boolean',
        'is_jour_ferie' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'pointage_user', 'pointage_id', 'user_id')
                    ->select(['users.id', 'users.name', 'users.email', 'users.role']);
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
            $this->calculateCoefficient();
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
     * Calculate the coefficient based on time and day type
     */
    public function calculateCoefficient()
    {
        if (!$this->heure_debut || !$this->heure_fin) {
            return $this;
        }
        
        $debut = new \DateTime($this->heure_debut);
        $fin = new \DateTime($this->heure_fin);
        
        // Extract only the time part
        $debutTime = $debut->format('H:i:s');
        $finTime = $fin->format('H:i:s');
        
        // If end time is before start time, it spans to the next day
        $spansNextDay = false;
        if ($fin < $debut) {
            $spansNextDay = true;
        }
        
        // Set default coefficient
        $coefficient = 1.0;
        
        if ($this->is_jour_ferie) {
            // Jour férié rules
            if (($debutTime >= '06:00:00' && $debutTime < '21:00:00') || 
                ($finTime > '06:00:00' && $finTime <= '21:00:00')) {
                $coefficient = 1.5;
            } else {
                $coefficient = 2.0;
            }
        } else {
            // Jour normal rules
            if (($debutTime >= '08:00:00' && $debutTime < '17:00:00') || 
                ($finTime > '08:00:00' && $finTime <= '17:00:00')) {
                // Normal hours - coefficient remains 1.0
            } else if (($debutTime >= '06:00:00' && $debutTime < '08:00:00') || 
                       ($debutTime >= '17:00:00' && $debutTime < '21:00:00') || 
                       ($finTime > '06:00:00' && $finTime <= '08:00:00') || 
                       ($finTime > '17:00:00' && $finTime <= '21:00:00')) {
                $coefficient = 1.25;
            } else {
                // Night hours (21:00 - 06:00)
                $coefficient = 1.5;
            }
        }
        
        $this->coefficient = $coefficient;
        
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
     * Get the weighted hours (hours multiplied by coefficient)
     */
    public function getWeightedHoursAttribute()
    {
        return round($this->effective_hours * $this->coefficient, 2);
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
