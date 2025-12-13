<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Car;
use App\Models\Problem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return response()->json($tickets);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
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
        if ($car->user_id !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'You can only create tickets for your own cars'
            ], 403);
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

        $ticket->load(['user', 'car', 'problems']);

        return response()->json([
            'message' => 'Ticket created successfully',
            'data' => $ticket
        ], 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Check authorization
        if (!$user->hasAnyRole(['mechanic', 'admin']) && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->load(['user', 'mechanic', 'car', 'problems']);

        return response()->json($ticket);
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Check authorization
        if (!$user->hasAnyRole(['mechanic', 'admin']) && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if ($ticket->status !== 'open') {
                return response()->json([
                    'message' => 'You can only update tickets that are still open'
                ], 403);
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

        $ticket->load(['user', 'mechanic', 'car', 'problems']);

        return response()->json([
            'message' => 'Ticket updated successfully',
            'data' => $ticket
        ]);
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
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $ticket->delete();

        return response()->json([
            'message' => 'Ticket deleted successfully'
        ]);
    }

    /**
     * Accept/assign a ticket to a mechanic.
     */
    public function accept(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['mechanic', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($ticket->status !== 'open') {
            return response()->json([
                'message' => 'This ticket has already been accepted'
            ], 422);
        }

        $ticket->update([
            'mechanic_id' => $user->id,
            'status' => 'assigned',
            'accepted_at' => now(),
        ]);

        $ticket->load(['user', 'mechanic', 'car', 'problems']);

        return response()->json([
            'message' => 'Ticket accepted successfully',
            'data' => $ticket
        ]);
    }

    /**
     * Start working on a ticket.
     */
    public function startWork(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['mechanic', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($ticket->mechanic_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'You can only start work on tickets assigned to you'
            ], 403);
        }

        if (!in_array($ticket->status, ['assigned', 'open'])) {
            return response()->json([
                'message' => 'Invalid ticket status'
            ], 422);
        }

        $ticket->update([
            'status' => 'in_progress',
        ]);

        $ticket->load(['user', 'mechanic', 'car', 'problems']);

        return response()->json([
            'message' => 'Work started on ticket',
            'data' => $ticket
        ]);
    }

    /**
     * Mark a ticket as completed.
     */
    public function complete(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['mechanic', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($ticket->mechanic_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'You can only complete tickets assigned to you'
            ], 403);
        }

        if ($ticket->status === 'completed' || $ticket->status === 'closed') {
            return response()->json([
                'message' => 'This ticket is already completed or closed'
            ], 422);
        }

        $ticket->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $ticket->load(['user', 'mechanic', 'car', 'problems']);

        return response()->json([
            'message' => 'Ticket marked as completed',
            'data' => $ticket
        ]);
    }

    /**
     * Close a ticket.
     */
    public function close(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Ticket owner or admin can close
        if ($ticket->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'message' => 'This ticket is already closed'
            ], 422);
        }

        $ticket->update([
            'status' => 'closed',
        ]);

        $ticket->load(['user', 'mechanic', 'car', 'problems']);

        return response()->json([
            'message' => 'Ticket closed',
            'data' => $ticket
        ]);
    }

    /**
     * Get ticket statistics.
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['mechanic', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

        return response()->json($stats);
    }
}
