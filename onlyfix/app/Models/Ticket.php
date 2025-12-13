<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'mechanic_id',
        'car_id',
        'status',
        'priority',
        'description',
        'accepted_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user who created the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the mechanic assigned to the ticket.
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    /**
     * Get the car this ticket is about.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Get the problems associated with this ticket.
     */
    public function problems(): BelongsToMany
    {
        return $this->belongsToMany(Problem::class, 'ticket_problems')
            ->withPivot(['notes'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include open tickets.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include assigned tickets.
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to include tickets for a specific mechanic.
     */
    public function scopeForMechanic($query, int $mechanicId)
    {
        return $query->where('mechanic_id', $mechanicId);
    }

    /**
     * Check if the ticket is assigned to a mechanic.
     */
    public function isAssigned(): bool
    {
        return !is_null($this->mechanic_id);
    }

    /**
     * Check if the ticket is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the ticket is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if the ticket is open or unassigned.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the ticket is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Assign the ticket to a mechanic.
     */
    public function assignToMechanic(int $mechanicId): void
    {
        $this->update([
            'mechanic_id' => $mechanicId,
            'status' => 'assigned',
            'accepted_at' => now(),
        ]);
    }

    /**
     * Mark the ticket as in progress.
     */
    public function markInProgress(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    /**
     * Mark the ticket as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Close the ticket.
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }
}
