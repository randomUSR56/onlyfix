<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Problem;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    /**
     * Build the index query for tickets with filters, search, and sorting.
     */
    public function buildIndexQuery(User $user, array $filters): Builder
    {
        $query = Ticket::with(['user', 'mechanic', 'car', 'problems']);

        // Mechanics and admins can view all tickets with filters
        if ($user->hasAnyRole(['mechanic', 'admin'])) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }
            if (! empty($filters['mechanic_id'])) {
                $query->where('mechanic_id', $filters['mechanic_id']);
            }
            if (! empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            if (! empty($filters['car_id'])) {
                $query->where('car_id', $filters['car_id']);
            }
        } else {
            // Regular users can only view their own tickets
            $query->where('user_id', $user->id);
        }

        // Search across ticket description, car info, and user name
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('car', function ($carQuery) use ($search) {
                      $carQuery->where('make', 'like', "%{$search}%")
                               ->orWhere('model', 'like', "%{$search}%")
                               ->orWhere('license_plate', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort by priority (urgent first) and then by creation date
        $query->orderByRaw("CASE priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
            ELSE 5 END")
            ->orderBy('created_at', 'desc');

        return $query;
    }

    /**
     * Get a paginated list of tickets.
     */
    public function getTickets(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildIndexQuery($user, $filters)->paginate($perPage);
    }

    /**
     * Authorize that a user can create tickets (mechanics cannot).
     */
    public function authorizeCreate(User $user): void
    {
        if ($user->hasRole('mechanic') && ! $user->hasRole('admin')) {
            abort(403, 'Mechanics cannot create tickets');
        }
    }

    /**
     * Create a new ticket with problem attachments.
     */
    public function createTicket(User $user, array $validated): Ticket
    {
        $this->authorizeCreate($user);

        // Verify the car belongs to the target user
        $car = Car::findOrFail($validated['car_id']);
        $targetUserId = $validated['user_id'] ?? $user->id;

        if ($car->user_id !== (int) $targetUserId && ! $user->hasRole('admin')) {
            throw ValidationException::withMessages([
                'car_id' => 'You can only create tickets for your own cars',
            ]);
        }

        $ticketData = [
            'user_id' => $targetUserId,
            'car_id' => $validated['car_id'],
            'description' => $validated['description'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ];

        // If admin assigns a mechanic during creation, mark as assigned
        if (isset($validated['mechanic_id'])) {
            $ticketData['mechanic_id'] = $validated['mechanic_id'];
            $ticketData['status'] = 'assigned';
            $ticketData['accepted_at'] = now();
        }

        $ticket = Ticket::create($ticketData);

        $this->attachProblems($ticket, $validated['problem_ids'], $validated['problem_notes'] ?? []);

        return $ticket;
    }

    /**
     * Authorize that a user can view a specific ticket.
     */
    public function authorizeView(User $user, Ticket $ticket): void
    {
        if (! $user->hasAnyRole(['mechanic', 'admin']) && $ticket->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Load and return a ticket with all relationships.
     */
    public function showTicket(Ticket $ticket, User $user): Ticket
    {
        $this->authorizeView($user, $ticket);
        $ticket->load(['user', 'mechanic', 'car', 'problems']);

        return $ticket;
    }

    /**
     * Get the permission flags for a ticket relative to the given user.
     */
    public function getTicketPermissions(Ticket $ticket, User $user): array
    {
        $isOwner = $ticket->user_id === $user->id;
        $isAdmin = $user->hasRole('admin');
        $isMechanic = $user->hasRole('mechanic');

        return [
            'canEdit' => ($isOwner && $ticket->status === 'open') || $isAdmin || $isMechanic,
            'canDelete' => ($isOwner && $ticket->status === 'open') || $isAdmin,
            'canClose' => ($isOwner || $isAdmin) && $ticket->status !== 'closed',
        ];
    }

    /**
     * Update an existing ticket (fields + problem sync).
     */
    public function updateTicket(Ticket $ticket, User $user, array $validated): Ticket
    {
        $this->authorizeView($user, $ticket);

        // Regular users can only update their own open tickets
        if (! $user->hasAnyRole(['mechanic', 'admin'])) {
            if ($ticket->user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }
            if ($ticket->status !== 'open') {
                throw ValidationException::withMessages([
                    'status' => 'You can only update tickets that are still open',
                ]);
            }
            // Regular users cannot change status directly
            unset($validated['status']);
        }

        $oldStatus = $ticket->status;

        // Handle mechanic assignment change - auto-set status to assigned
        if (isset($validated['mechanic_id']) && $validated['mechanic_id'] != $ticket->mechanic_id) {
            if (empty($validated['status']) || $validated['status'] === 'open') {
                $validated['status'] = 'assigned';
                $ticket->accepted_at = now();
            }
        }

        // Update basic fields (exclude problem-related arrays)
        $ticket->update(array_diff_key($validated, array_flip(['problem_ids', 'problem_notes'])));

        // Send notification if status changed
        if (isset($validated['status']) && $oldStatus !== $ticket->status) {
            $ticket->load(['user']);
            $ticket->notifyStatusChange($oldStatus, $ticket->status);
        }

        // Sync problems if provided
        if (isset($validated['problem_ids'])) {
            $this->syncProblems($ticket, $validated['problem_ids'], $validated['problem_notes'] ?? []);
        }

        return $ticket;
    }

    /**
     * Delete a ticket (admins always, owners only if status is open).
     */
    public function deleteTicket(Ticket $ticket, User $user): void
    {
        if (! $user->hasRole('admin')) {
            if ($ticket->user_id !== $user->id || $ticket->status !== 'open') {
                abort(403, 'Unauthorized');
            }
        }

        $ticket->delete();
    }

    /**
     * Transition a ticket through the status workflow.
     *
     * Supported transitions:
     *   - 'assigned'    (accept)
     *   - 'in_progress' (start work)
     *   - 'completed'   (complete)
     *   - 'closed'      (close)
     *
     * Handles authorization, status validation, update, and notification.
     */
    public function transitionTicketStatus(Ticket $ticket, User $user, string $targetStatus, array $data = []): Ticket
    {
        $oldStatus = $ticket->status;

        match ($targetStatus) {
            'assigned' => $this->handleAccept($ticket, $user),
            'in_progress' => $this->handleStartWork($ticket, $user),
            'completed' => $this->handleComplete($ticket, $user),
            'closed' => $this->handleClose($ticket, $user),
            default => abort(422, 'Invalid target status'),
        };

        $ticket->load(['user', 'mechanic', 'car', 'problems']);
        $ticket->notifyStatusChange($oldStatus, $targetStatus);

        return $ticket;
    }

    /**
     * Get common form data needed for ticket create/edit pages.
     */
    public function getTicketFormData(User $user): array
    {
        return [
            'cars' => $user->hasRole('admin')
                ? Car::with('user')->get()
                : $user->cars,
            'problems' => Problem::where('is_active', true)->get(),
            'mechanics' => $user->hasRole('admin')
                ? User::role('mechanic')->get()
                : [],
            'users' => $user->hasRole('admin')
                ? User::role('user')->get()
                : [],
        ];
    }

    /**
     * Get ticket statistics (mechanics see their own stats, admins see global).
     */
    public function getStatistics(User $user): array
    {
        if (! $user->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $stats = [
            'total_tickets' => Ticket::count(),
            'by_status' => Ticket::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_priority' => Ticket::select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'assigned_tickets' => Ticket::where('status', 'assigned')->count(),
            'in_progress_tickets' => Ticket::where('status', 'in_progress')->count(),
            'completed_today' => Ticket::whereDate('completed_at', today())->count(),
        ];

        if ($user->hasRole('mechanic') && ! $user->hasRole('admin')) {
            $stats['my_assigned_tickets'] = Ticket::where('mechanic_id', $user->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count();
            $stats['my_completed_tickets'] = Ticket::where('mechanic_id', $user->id)
                ->where('status', 'completed')
                ->count();
        }

        return $stats;
    }

    // -------------------------------------------------------------------------
    // Private transition handlers
    // -------------------------------------------------------------------------

    private function handleAccept(Ticket $ticket, User $user): void
    {
        if (! $user->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Only mechanics can accept tickets');
        }

        if ($ticket->status !== 'open') {
            abort(422, 'This ticket has already been accepted');
        }

        $ticket->update([
            'mechanic_id' => $user->id,
            'status' => 'assigned',
            'accepted_at' => now(),
        ]);
    }

    private function handleStartWork(Ticket $ticket, User $user): void
    {
        if (! $user->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Only mechanics can start work on tickets');
        }

        if ($ticket->mechanic_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403, 'You can only start work on tickets assigned to you');
        }

        if (! in_array($ticket->status, ['assigned', 'open'])) {
            abort(422, 'Invalid ticket status for starting work');
        }

        $ticket->update([
            'status' => 'in_progress',
        ]);
    }

    private function handleComplete(Ticket $ticket, User $user): void
    {
        if (! $user->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Only mechanics can complete tickets');
        }

        if ($ticket->mechanic_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403, 'You can only complete tickets assigned to you');
        }

        if (in_array($ticket->status, ['completed', 'closed'])) {
            abort(422, 'This ticket is already completed or closed');
        }

        $ticket->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    private function handleClose(Ticket $ticket, User $user): void
    {
        if ($ticket->user_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        if ($ticket->status === 'closed') {
            abort(422, 'This ticket is already closed');
        }

        $ticket->update([
            'status' => 'closed',
        ]);
    }

    // -------------------------------------------------------------------------
    // Private problem-pivot helpers
    // -------------------------------------------------------------------------

    /**
     * Attach problems to a newly created ticket with optional notes.
     */
    private function attachProblems(Ticket $ticket, array $problemIds, array $problemNotes = []): void
    {
        $problemData = [];
        foreach ($problemIds as $index => $problemId) {
            $problemData[$problemId] = [
                'notes' => $problemNotes[$index] ?? null,
            ];
        }
        $ticket->problems()->attach($problemData);
    }

    /**
     * Sync problems on an existing ticket with optional notes.
     */
    private function syncProblems(Ticket $ticket, array $problemIds, array $problemNotes = []): void
    {
        $problemData = [];
        foreach ($problemIds as $index => $problemId) {
            $problemData[$problemId] = [
                'notes' => $problemNotes[$index] ?? null,
            ];
        }
        $ticket->problems()->sync($problemData);
    }
}
