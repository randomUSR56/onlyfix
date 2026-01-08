<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProblemController extends Controller
{
    /**
     * Display a listing of problems.
     */
    public function index(Request $request)
    {
        $query = Problem::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter active/inactive
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $problems = $query->paginate(15);

        return Inertia::render('Problems/Index', [
            'problems' => $problems,
            'filters' => $request->only(['category', 'is_active', 'search'])
        ]);
    }

    /**
     * Show the form for creating a new problem.
     * Only admins and mechanics can create problems.
     */
    public function create()
    {
        if (!auth()->user()->hasAnyRole(['admin', 'mechanic'])) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Problems/Create');
    }

    /**
     * Store a newly created problem.
     * Only admins and mechanics can create problems.
     */
    public function store(Request $request)
    {
        if (!$request->user()->hasAnyRole(['admin', 'mechanic'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:problems',
            'category' => 'required|in:engine,transmission,electrical,brakes,suspension,steering,body,other',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $problem = Problem::create($validated);

        return redirect()->route('problems.show', $problem)
            ->with('success', 'Problem created successfully');
    }

    /**
     * Display the specified problem.
     */
    public function show(Problem $problem)
    {
        $problem->load('tickets');

        return Inertia::render('Problems/Show', [
            'problem' => $problem
        ]);
    }

    /**
     * Show the form for editing the specified problem.
     */
    public function edit(Problem $problem)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'mechanic'])) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Problems/Edit', [
            'problem' => $problem
        ]);
    }

    /**
     * Update the specified problem.
     * Only admins and mechanics can update problems.
     */
    public function update(Request $request, Problem $problem)
    {
        if (!$request->user()->hasAnyRole(['admin', 'mechanic'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:problems,name,' . $problem->id,
            'category' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $problem->update($validated);

        return redirect()->route('problems.show', $problem)
            ->with('success', 'Problem updated successfully');
    }

    /**
     * Remove the specified problem.
     * Only admins can delete problems.
     */
    public function destroy(Request $request, Problem $problem)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $problem->delete();

        return redirect()->route('problems.index')
            ->with('success', 'Problem deleted successfully');
    }

    /**
     * Get statistics about problem occurrences.
     */
    public function statistics(Request $request)
    {
        if (!$request->user()->hasAnyRole(['admin', 'mechanic'])) {
            abort(403, 'Unauthorized');
        }

        $problems = Problem::withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->get();

        $stats = [
            'total_problems' => Problem::count(),
            'active_problems' => Problem::where('is_active', true)->count(),
            'problems_by_frequency' => $problems,
        ];

        return Inertia::render('Statistics/Problems', [
            'statistics' => $stats
        ]);
    }
}