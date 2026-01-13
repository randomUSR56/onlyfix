<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Car;
use App\Models\Problem;
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
            'filters' => $request->only(['status', 'priority', 'mechanic_id', 'user_id', 'car_id'])
        ]);
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Mechanics can only accept tickets, not create them
        if ($user->hasRole('mechanic') && !$user->hasRole('admin')) {
            abort(403, 'Mechanics cannot create tickets');
        }

        // Get user's cars or all cars if admin
        $cars = $user->hasRole('admin')
            ? Car::with('user')->get()
            : $user->cars;

        // Get active problems
        $problems = Problem::where('is_active', true)->get();

        return Inertia::render('Tickets/Create', [
            'cars' => $cars,
            'problems' => $problems
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Mechanics can only accept tickets, not create them
        if ($user->hasRole('mechanic') && !$user->hasRole('admin')) {
            abort(403, 'Mechanics cannot create tickets');
        }

        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'description' => 'required|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'problem_ids' => 'required|array|min:1',
            'problem_ids.*' => 'exists:problems,id',
            'problem_notes' => 'sometimes|array',
            'problem_notes.*' => 'nullable|string',
        ]);

        // Verify the car belongs to the user
        $car = Car::findOrFail($validated['car_id']);
        if ($car->user_id !== $user->id && !$user->hasRole('admin')) {
            return back()->withErrors([
                'car_id' => 'You can only create tickets for your own cars'
            ])->withInput();
        }

        // Create ticket
        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'car_id' => $validated['car_id'],
            'description' => $validated['description'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ]);

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
        if (!$user->hasAnyRole(['mechanic', 'admin']) && $ticket->user_id !== $user->id) {
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
    public function edit(Ticket $ticket)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Check authorization
        if (!$user->hasAnyRole(['mechanic', 'admin']) && $ticket->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Get user's cars or all cars if admin
        $cars = $user->hasRole('admin')
            ? Car::with('user')->get()
            : $user->cars;

        // Get active problems
        $problems = Problem::where('is_active', true)->get();

        return Inertia::render('Tickets/Edit', [
            'ticket' => $ticket->load(['user', 'mechanic', 'car', 'problems']),
            'cars' => $cars,
            'problems' => $problems
        ]);
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Check authorization
        if (!$user->hasAnyRole(['mechanic', 'admin']) && $ticket->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'description' => 'sometimes|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,assigned,in_progress,completed,closed',
            'problem_ids' => 'sometimes|array|min:1',
            'problem_ids.*' => 'exists:problems,id',
            'problem_notes' => 'sometimes|array',
            'problem_notes.*' => 'nullable|string',
        ]);

        // Regular users can only update their own open tickets
        if (!$user->hasAnyRole(['mechanic', 'admin'])) {
            if ($ticket->user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }
            if ($ticket->status !== 'open') {
                return back()->withErrors([
                    'status' => 'You can only update tickets that are still open'
                ]);
            }
            // Regular users can't change status
            unset($validated['status']);
        }

        // Update basic fields
        $ticket->update(array_diff_key($validated, array_flip(['problem_ids', 'problem_notes'])));

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
        if (!$user->hasRole('admin')) {
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

        if (!$user->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        if ($ticket->status !== 'open') {
            return back()->withErrors([
                'ticket' => 'This ticket has already been accepted'
            ]);
        }

        $ticket->update([
            'mechanic_id' => $user->id,
            'status' => 'assigned',
            'accepted_at' => now(),
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket accepted successfully');
    }

    /**
     * Start working on a ticket.
     */
    public function startWork(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        if ($ticket->mechanic_id !== $user->id && !$user->hasRole('admin')) {
            return back()->withErrors([
                'ticket' => 'You can only start work on tickets assigned to you'
            ]);
        }

        if (!in_array($ticket->status, ['assigned', 'open'])) {
            return back()->withErrors([
                'ticket' => 'Invalid ticket status'
            ]);
        }

        $ticket->update([
            'status' => 'in_progress',
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Work started on ticket');
    }

    /**
     * Mark a ticket as completed.
     */
    public function complete(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        if ($ticket->mechanic_id !== $user->id && !$user->hasRole('admin')) {
            return back()->withErrors([
                'ticket' => 'You can only complete tickets assigned to you'
            ]);
        }

        if ($ticket->status === 'completed' || $ticket->status === 'closed') {
            return back()->withErrors([
                'ticket' => 'This ticket is already completed or closed'
            ]);
        }

        $ticket->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

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
        if ($ticket->user_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        if ($ticket->status === 'closed') {
            return back()->withErrors([
                'ticket' => 'This ticket is already closed'
            ]);
        }

        $ticket->update([
            'status' => 'closed',
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket closed');
    }

    /**
     * Get ticket statistics.
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['mechanic', 'admin'])) {
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

        if ($user->hasRole('mechanic') && !$user->hasRole('admin')) {
            $stats['my_assigned_tickets'] = Ticket::where('mechanic_id', $user->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count();
            $stats['my_completed_tickets'] = Ticket::where('mechanic_id', $user->id)
                ->where('status', 'completed')
                ->count();
        }

        return Inertia::render('Statistics/Tickets', [
            'statistics' => $stats
        ]);
    }
}
