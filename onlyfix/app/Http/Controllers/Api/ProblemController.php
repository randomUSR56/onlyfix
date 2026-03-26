<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Problem;
use App\Services\ProblemService;
use Illuminate\Http\Request;

class ProblemController extends Controller
{
    public function __construct(
        private readonly ProblemService $problemService
    ) {}

    /**
     * Display a listing of problems.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'is_active', 'search']);

        return response()->json(
            $this->problemService->getProblems($filters)
        );
    }

    /**
     * Store a newly created problem.
     * Only admins and mechanics can create problems.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:problems',
            'category' => 'required|in:engine,transmission,electrical,brakes,suspension,steering,body,other',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $problem = $this->problemService->createProblem($request->user(), $validated);

        return response()->json([
            'message' => 'Problem created successfully',
            'data' => $problem,
        ], 201);
    }

    /**
     * Display the specified problem.
     */
    public function show(Problem $problem)
    {
        $problem = $this->problemService->showProblem($problem);

        return response()->json($problem);
    }

    /**
     * Update the specified problem.
     * Only admins and mechanics can update problems.
     */
    public function update(Request $request, Problem $problem)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:problems,name,' . $problem->id,
            'category' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $problem = $this->problemService->updateProblem($problem, $request->user(), $validated);

        return response()->json([
            'message' => 'Problem updated successfully',
            'data' => $problem,
        ]);
    }

    /**
     * Remove the specified problem.
     * Only admins can delete problems.
     */
    public function destroy(Request $request, Problem $problem)
    {
        $this->problemService->deleteProblem($problem, $request->user());

        return response()->json([
            'message' => 'Problem deleted successfully',
        ]);
    }

    /**
     * Get statistics about problem occurrences.
     */
    public function statistics(Request $request)
    {
        return response()->json(
            $this->problemService->getStatistics($request->user())
        );
    }
}
