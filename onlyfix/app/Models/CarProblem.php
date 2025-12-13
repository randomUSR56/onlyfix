<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarProblem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'car_problems';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'car_id',
        'problem_id',
        'ticket_id',
        'detected_at',
        'resolved_at',
        'severity',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Get the car this problem belongs to.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Get the problem type.
     */
    public function problem(): BelongsTo
    {
        return $this->belongsTo(Problem::class);
    }

    /**
     * Get the ticket where this problem was addressed.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Check if the problem is resolved.
     */
    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }

    /**
     * Scope a query to only include unresolved problems.
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope a query to only include resolved problems.
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope a query to filter by severity.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}