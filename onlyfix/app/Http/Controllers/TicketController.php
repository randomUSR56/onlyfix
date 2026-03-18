<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Problem;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['user', 'mechanic', 'car', 'problems']);

        // Mechanics and admins can view all tickets
        if ($user->hasAnyRole(['mechanic', 'admin'])) {
            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('mechanic_id')) {
                $query->where('mechanic_id', $request->mechanic_id);
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('car_id')) {
                $query->where('car_id', $request->car_id);
            }
        } else {
            // Regular users can only view their own tickets
            $query->where('user_id', $user->id);
        }

        // Search across ticket description, car info, and user name
        if ($request->filled('search')) {
            $search = $request->search;
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

        // Sort by priority and created date
        // Using CASE for SQLite compatibility
        $query->orderByRaw("CASE priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
            ELSE 5 END")
            ->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $tickets = $query->paginate($perPage);

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'status', 'priority', 'mechanic_id', 'user_id', 'car_id']),
        ]);
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Mechanics can only accept tickets, not create them
        if ($user->hasRole('mechanic') && ! $user->hasRole('admin')) {
            abort(403, 'Mechanics cannot create tickets');
        }

        // Get user's cars or all cars if admin
        $cars = $user->hasRole('admin')
            ? Car::with('user')->get()
            : $user->cars;

        // Get active problems
        $problems = Problem::where('is_active', true)->get();

        // Get all mechanics for admin
        $mechanics = $user->hasRole('admin')
            ? \App\Models\User::role('mechanic')->get()
            : [];

        // Get all users for admin
        $users = $user->hasRole('admin')
            ? \App\Models\User::role('user')->get()
            : [];

        return Inertia::render('Tickets/Create', [
            'cars' => $cars,
            'problems' => $problems,
            'mechanics' => $mechanics,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Mechanics can only accept tickets, not create them
        if ($user->hasRole('mechanic') && ! $user->hasRole('admin')) {
            abort(403, 'Mechanics cannot create tickets');
        }

        $rules = [
            'car_id' => 'required|exists:cars,id',
            'description' => 'required|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'problem_ids' => 'required|array|min:1',
            'problem_ids.*' => 'exists:problems,id',
            'problem_notes' => 'sometimes|array',
            'problem_notes.*' => 'nullable|string',
        ];

        // Admins can assign user and mechanic
        if ($user->hasRole('admin')) {
            $rules['user_id'] = 'sometimes|exists:users,id';
            $rules['mechanic_id'] = 'sometimes|nullable|exists:users,id';
        }

        $validated = $request->validate($rules);

        // Verify the car belongs to the user (unless admin specifies a user_id for that car)
        $car = Car::findOrFail($validated['car_id']);
        $targetUserId = $validated['user_id'] ?? $user->id;

        if ($car->user_id !== (int)$targetUserId && ! $user->hasRole('admin')) {
            return back()->withErrors([
                'car_id' => 'You can only create tickets for your own cars',
            ])->withInput();
        }

        // Create ticket
        $ticketData = [
            'user_id' => $targetUserId,
            'car_id' => $validated['car_id'],
            'description' => $validated['description'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ];

        if (isset($validated['mechanic_id'])) {
            $ticketData['mechanic_id'] = $validated['mechanic_id'];
            $ticketData['status'] = 'assigned';
            $ticketData['accepted_at'] = now();
        }

        $ticket = Ticket::create($ticketData);

        // Attach problems with optional notes
        $problemData = [];
        foreach ($validated['problem_ids'] as $index => $problemId) {
            $problemData[$problemId] = [
                'notes' => $validated['problem_notes'][$index] ?? null,
            ];
        }
        $ticket->problems()->attach($problemData);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket created successfully');
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Check authorization
        if (! $user->hasAnyRole(['mechanic', 'admin']) && $ticket->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $ticket->load(['user', 'mechanic', 'car', 'problems']);

        // Determine permissions
        $isOwner = $ticket->user_id === $user->id;
        $isAdmin = $user->hasRole('admin');
        $isMechanic = $user->hasRole('mechanic');

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
            'canEdit' => ($isOwner && $ticket->status === 'open') || $isAdmin || $isMechanic,
            'canDelete' => ($isOwner && $ticket->status === 'open') || $isAdmin,
            'canClose' => ($isOwner || $isAdmin) && $ticket->status !== 'closed',
        ]);
    }

    /**
     * Show the form for editing the specified ticket.
     */
    public function edit(Request $request, Ticket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check authorization
        if (! $user->hasAnyRole(['mechanic', 'admin']) && $ticket->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Get user's cars or all cars if admin
        $cars = $user->hasRole('admin')
            ? Car::with('user')->get()
            : $user->cars;

        // Get active problems
        $problems = Problem::where('is_active', true)->get();

        // Get all mechanics for admin
        $mechanics = $user->hasRole('admin')
            ? \App\Models\User::role('mechanic')->get()
            : [];

        // Get all users for admin
        $users = $user->hasRole('admin')
            ? \App\Models\User::role('user')->get()
            : [];

        return Inertia::render('Tickets/Edit', [
            'ticket' => $ticket->load(['user', 'mechanic', 'car', 'problems']),
            'cars' => $cars,
            'problems' => $problems,
            'mechanics' => $mechanics,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Check authorization
        if (! $user->hasAnyRole(['mechanic', 'admin']) && $ticket->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $rules = [
            'description' => 'sometimes|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,assigned,in_progress,completed,closed',
            'problem_ids' => 'sometimes|array|min:1',
            'problem_ids.*' => 'exists:problems,id',
            'problem_notes' => 'sometimes|array',
            'problem_notes.*' => 'nullable|string',
        ];

        // Admins can update user and mechanic
        if ($user->hasRole('admin')) {
            $rules['user_id'] = 'sometimes|exists:users,id';
            $rules['mechanic_id'] = 'sometimes|nullable|exists:users,id';
        }

        $validated = $request->validate($rules);

        // Regular users can only update their own open tickets
        if (! $user->hasAnyRole(['mechanic', 'admin'])) {
            if ($ticket->user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }
            if ($ticket->status !== 'open') {
                return back()->withErrors([
                    'status' => 'You can only update tickets that are still open',
                ]);
            }
            // Regular users can't change status
            unset($validated['status']);
        }

        // Track status change for notification
        $oldStatus = $ticket->status;

        // Handle mechanic assignment change
        if (isset($validated['mechanic_id']) && $validated['mechanic_id'] != $ticket->mechanic_id) {
            if (empty($validated['status']) || $validated['status'] === 'open') {
                $validated['status'] = 'assigned';
                $ticket->accepted_at = now();
            }
        }

        // Update basic fields
        $ticket->update(array_diff_key($validated, array_flip(['problem_ids', 'problem_notes'])));

        // Send notification if status changed
        if (isset($validated['status']) && $oldStatus !== $ticket->status) {
            $ticket->load(['user']);
            $ticket->notifyStatusChange($oldStatus, $ticket->status);
        }

        // Update problems if provided
        if (isset($validated['problem_ids'])) {
            $problemData = [];
            foreach ($validated['problem_ids'] as $index => $problemId) {
                $problemData[$problemId] = [
                    'notes' => $validated['problem_notes'][$index] ?? null,
                ];
            }
            $ticket->problems()->sync($problemData);
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully');
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Only admins or ticket owners (if status is open) can delete
        if (! $user->hasRole('admin')) {
            if ($ticket->user_id !== $user->id || $ticket->status !== 'open') {
                abort(403, 'Unauthorized');
            }
        }

        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket deleted successfully');
    }

    /**
     * Accept/assign a ticket to a mechanic.
     */
    public function accept(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // ONLY mechanics can accept tickets (as per new requirement)
        if (! $user->hasRole('mechanic')) {
            abort(403, 'Only mechanics can accept tickets');
        }

        if ($ticket->status !== 'open') {
            return back()->withErrors([
                'ticket' => 'This ticket has already been accepted',
            ]);
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'mechanic_id' => $user->id,
            'status' => 'assigned',
            'accepted_at' => now(),
        ]);

        $ticket->load(['user']);
        $ticket->notifyStatusChange($oldStatus, 'assigned');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket accepted successfully');
    }

    /**
     * Start working on a ticket.
     */
    public function startWork(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (! $user->hasRole('mechanic')) {
            abort(403, 'Only mechanics can start work on tickets');
        }

        if ($ticket->mechanic_id !== $user->id) {
            return back()->withErrors([
                'ticket' => 'You can only start work on tickets assigned to you',
            ]);
        }

        if (! in_array($ticket->status, ['assigned', 'open'])) {
            return back()->withErrors([
                'ticket' => 'Invalid ticket status',
            ]);
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'in_progress',
        ]);

        $ticket->load(['user']);
        $ticket->notifyStatusChange($oldStatus, 'in_progress');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Work started on ticket');
    }

    /**
     * Mark a ticket as completed.
     */
    public function complete(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (! $user->hasRole('mechanic')) {
            abort(403, 'Only mechanics can complete tickets');
        }

        if ($ticket->mechanic_id !== $user->id) {
            return back()->withErrors([
                'ticket' => 'You can only complete tickets assigned to you',
            ]);
        }

        if ($ticket->status === 'completed' || $ticket->status === 'closed') {
            return back()->withErrors([
                'ticket' => 'This ticket is already completed or closed',
            ]);
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $ticket->load(['user']);
        $ticket->notifyStatusChange($oldStatus, 'completed');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket marked as completed');
    }

    /**
     * Close a ticket.
     */
    public function close(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Ticket owner or admin can close
        if ($ticket->user_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        if ($ticket->status === 'closed') {
            return back()->withErrors([
                'ticket' => 'This ticket is already closed',
            ]);
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'closed',
        ]);

        $ticket->load(['user']);
        $ticket->notifyStatusChange($oldStatus, 'closed');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket closed');
    }

    /**
     * Get ticket statistics.
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

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

        return Inertia::render('Statistics/Tickets', [
            'statistics' => $stats,
        ]);
    }
}
