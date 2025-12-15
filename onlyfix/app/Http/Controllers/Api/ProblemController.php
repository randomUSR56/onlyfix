<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Problem;
use Illuminate\Http\Request;

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

        return response()->json($problems);
    }

    /**
     * Show the form for creating a new problem.
     */
    public function create()
    {
        return inertia('Problems/Create');
    }

    /**
     * Store a newly created problem.
     * Only admins and mechanics can create problems.
     */
    public function store(Request $request)
    {
        if (!$request->user()->hasAnyRole(['admin', 'mechanic'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:problems',
            'category' => 'required|in:engine,transmission,electrical,brakes,suspension,steering,body,other',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $problem = Problem::create($validated);

        return response()->json([
            'message' => 'Problem created successfully',
            'data' => $problem
        ], 201);
    }

    /**
     * Display the specified problem.
     */
    public function show(Problem $problem)
    {
        $problem->load('tickets');

        return response()->json($problem);
    }

    /**
     * Show the form for editing the specified problem.
     */
    public function edit(Problem $problem)
    {
        return inertia('Problems/Edit', [
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
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:problems,name,' . $problem->id,
            'category' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $problem->update($validated);

        return response()->json([
            'message' => 'Problem updated successfully',
            'data' => $problem
        ]);
    }

    /**
     * Remove the specified problem.
     * Only admins can delete problems.
     */
    public function destroy(Request $request, Problem $problem)
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $problem->delete();

        return response()->json([
            'message' => 'Problem deleted successfully'
        ]);
    }

    /**
     * Get statistics about problem occurrences.
     */
    public function statistics(Request $request)
    {
        if (!$request->user()->hasAnyRole(['admin', 'mechanic'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $problems = Problem::withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->get();

        return response()->json([
            'total_problems' => Problem::count(),
            'active_problems' => Problem::where('is_active', true)->count(),
            'problems_by_frequency' => $problems,
        ]);
    }
}
