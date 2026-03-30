<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {}

    /**
     * Display a listing of tickets.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'priority', 'mechanic_id', 'user_id', 'car_id']);
        $perPage = $request->get('per_page', 15);

        return response()->json(
            $this->ticketService->getTickets($request->user(), $filters, $perPage)
        );
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

        $ticket = $this->ticketService->createTicket($request->user(), $validated);
        $ticket->load(['user', 'car', 'problems']);

        return response()->json([
            'message' => 'Ticket created successfully',
            'data' => $ticket,
        ], 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, Ticket $ticket)
    {
        $ticket = $this->ticketService->showTicket($ticket, $request->user());

        return response()->json($ticket);
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'description' => 'sometimes|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,assigned,in_progress,completed,closed',
            'problem_ids' => 'sometimes|array|min:1',
            'problem_ids.*' => 'exists:problems,id',
            'problem_notes' => 'sometimes|array',
            'problem_notes.*' => 'nullable|string',
        ]);

        $ticket = $this->ticketService->updateTicket($ticket, $request->user(), $validated);
        $ticket->load(['user', 'mechanic', 'car', 'problems']);

        return response()->json([
            'message' => 'Ticket updated successfully',
            'data' => $ticket,
        ]);
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(Request $request, Ticket $ticket)
    {
        $this->ticketService->deleteTicket($ticket, $request->user());

        return response()->json([
            'message' => 'Ticket deleted successfully',
        ]);
    }

    /**
     * Accept/assign a ticket to the current mechanic.
     */
    public function accept(Request $request, Ticket $ticket)
    {
        $ticket = $this->ticketService->transitionTicketStatus($ticket, $request->user(), 'assigned');

        return response()->json([
            'message' => 'Ticket accepted successfully',
            'data' => $ticket,
        ]);
    }

    /**
     * Start working on a ticket.
     */
    public function startWork(Request $request, Ticket $ticket)
    {
        $ticket = $this->ticketService->transitionTicketStatus($ticket, $request->user(), 'in_progress');

        return response()->json([
            'message' => 'Work started on ticket',
            'data' => $ticket,
        ]);
    }

    /**
     * Mark a ticket as completed.
     */
    public function complete(Request $request, Ticket $ticket)
    {
        $ticket = $this->ticketService->transitionTicketStatus($ticket, $request->user(), 'completed');

        return response()->json([
            'message' => 'Ticket marked as completed',
            'data' => $ticket,
        ]);
    }

    /**
     * Close a ticket.
     */
    public function close(Request $request, Ticket $ticket)
    {
        $ticket = $this->ticketService->transitionTicketStatus($ticket, $request->user(), 'closed');

        return response()->json([
            'message' => 'Ticket closed',
            'data' => $ticket,
        ]);
    }

    /**
     * Get ticket statistics.
     */
    public function statistics(Request $request)
    {
        return response()->json(
            $this->ticketService->getStatistics($request->user())
        );
    }
}
