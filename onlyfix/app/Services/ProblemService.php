<?php

namespace App\Services;

use App\Models\Problem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProblemService
{
    /**
     * Build the index query for problems with category, active, and search filters.
     */
    public function buildIndexQuery(array $filters): Builder
    {
        $query = Problem::query();

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Get a paginated list of problems.
     */
    public function getProblems(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildIndexQuery($filters)->paginate($perPage);
    }

    /**
     * Create a new problem. Only admins and mechanics are allowed.
     */
    public function createProblem(User $user, array $validated): Problem
    {
        if (! $user->hasAnyRole(['admin', 'mechanic'])) {
            abort(403, 'Unauthorized');
        }

        $validated['is_active'] = $validated['is_active'] ?? true;

        return Problem::create($validated);
    }

    /**
     * Load and return a problem with its tickets.
     */
    public function showProblem(Problem $problem): Problem
    {
        $problem->load('tickets');

        return $problem;
    }

    /**
     * Update an existing problem. Only admins and mechanics are allowed.
     */
    public function updateProblem(Problem $problem, User $user, array $validated): Problem
    {
        if (! $user->hasAnyRole(['admin', 'mechanic'])) {
            abort(403, 'Unauthorized');
        }

        $problem->update($validated);

        return $problem;
    }

    /**
     * Delete a problem. Only admins are allowed.
     */
    public function deleteProblem(Problem $problem, User $user): void
    {
        if (! $user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $problem->delete();
    }

    /**
     * Get statistics about problem occurrences (frequency, totals).
     */
    public function getStatistics(User $user): array
    {
        if (! $user->hasAnyRole(['admin', 'mechanic'])) {
            abort(403, 'Unauthorized');
        }

        $problems = Problem::withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->get();

        return [
            'total_problems' => Problem::count(),
            'active_problems' => Problem::where('is_active', true)->count(),
            'problems_by_frequency' => $problems,
        ];
    }
}
