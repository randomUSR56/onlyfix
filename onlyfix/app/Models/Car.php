<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'make',
        'model',
        'year',
        'license_plate',
        'vin',
        'color',
    ];

    /**
     * Get the user that owns the car.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tickets for this car.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the problems this car has experienced (through car_problems history).
     */
    public function problems(): BelongsToMany
    {
        return $this->belongsToMany(Problem::class, 'car_problems')
            ->withPivot(['ticket_id', 'detected_at', 'resolved_at', 'severity', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get the car's problem history.
     */
    public function carProblems(): HasMany
    {
        return $this->hasMany(CarProblem::class);
    }
}
