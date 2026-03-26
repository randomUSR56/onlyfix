<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        return Inertia::render('Tickets/Index', [
            'tickets' => $this->ticketService->getTickets($request->user(), $filters, $perPage),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $this->ticketService->authorizeCreate($user);

        return Inertia::render('Tickets/Create', $this->ticketService->getTicketFormData($user));
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $rules = [
            'car_id' => 'required|exists:cars,id',
            'description' => 'required|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'problem_ids' => 'required|array|min:1',
            'problem_ids.*' => 'exists:problems,id',
            'problem_notes' => 'sometimes|array',
            'problem_notes.*' => 'nullable|string',
        ];

        if ($user->hasRole('admin')) {
            $rules['user_id'] = 'sometimes|exists:users,id';
            $rules['mechanic_id'] = 'sometimes|nullable|exists:users,id';
        }

        $validated = $request->validate($rules);
        $ticket = $this->ticketService->createTicket($user, $validated);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket created successfully');
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $ticket = $this->ticketService->showTicket($ticket, $user);
        $permissions = $this->ticketService->getTicketPermissions($ticket, $user);

        return Inertia::render('Tickets/Show', array_merge(
            ['ticket' => $ticket],
            $permissions
        ));
    }

    /**
     * Show the form for editing the specified ticket.
     */
    public function edit(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $this->ticketService->authorizeView($user, $ticket);

        return Inertia::render('Tickets/Edit', array_merge(
            ['ticket' => $ticket->load(['user', 'mechanic', 'car', 'problems'])],
            $this->ticketService->getTicketFormData($user)
        ));
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        $rules = [
            'car_id' => 'sometimes|exists:cars,id',
            'description' => 'sometimes|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,assigned,in_progress,completed,closed',
            'problem_ids' => 'sometimes|array|min:1',
            'problem_ids.*' => 'exists:problems,id',
            'problem_notes' => 'sometimes|array',
            'problem_notes.*' => 'nullable|string',
        ];

        if ($user->hasRole('admin')) {
            $rules['user_id'] = 'sometimes|exists:users,id';
            $rules['mechanic_id'] = 'sometimes|nullable|exists:users,id';
        }

        $validated = $request->validate($rules);
        $this->ticketService->updateTicket($ticket, $user, $validated);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully');
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(Request $request, Ticket $ticket)
    {
        $this->ticketService->deleteTicket($ticket, $request->user());

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket deleted successfully');
    }

    /**
     * Accept/assign a ticket to the current mechanic.
     */
    public function accept(Request $request, Ticket $ticket)
    {
        $this->ticketService->transitionTicketStatus($ticket, $request->user(), 'assigned');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket accepted successfully');
    }

    /**
     * Start working on a ticket.
     */
    public function startWork(Request $request, Ticket $ticket)
    {
        $this->ticketService->transitionTicketStatus($ticket, $request->user(), 'in_progress');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Work started on ticket');
    }

    /**
     * Mark a ticket as completed.
     */
    public function complete(Request $request, Ticket $ticket)
    {
        $this->ticketService->transitionTicketStatus($ticket, $request->user(), 'completed');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket marked as completed');
    }

    /**
     * Close a ticket.
     */
    public function close(Request $request, Ticket $ticket)
    {
        $this->ticketService->transitionTicketStatus($ticket, $request->user(), 'closed');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket closed');
    }
}
