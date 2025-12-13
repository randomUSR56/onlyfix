<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
     * Get all unique problems this car has had across all tickets.
     * Access path: Car → Tickets → Ticket_Problems → Problems
     */
    public function problems()
    {
        return Problem::query()
            ->whereHas('tickets', fn($q) =>
                $q->where('car_id', $this->id)
            )
            ->distinct();
    }

    /**
     * Get unresolved (open) tickets for this car.
     */
    public function openTickets(): HasMany
    {
        return $this->tickets()
            ->whereIn('status', ['open', 'assigned', 'in_progress']);
    }

    /**
     * Get closed/completed tickets (service history).
     */
    public function serviceHistory(): HasMany
    {
        return $this->tickets()
            ->whereIn('status', ['completed', 'closed'])
            ->orderBy('completed_at', 'desc');
    }
}
