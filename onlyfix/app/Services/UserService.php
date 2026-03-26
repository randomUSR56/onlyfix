<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Abort unless the authenticated user is an admin.
     */
    public function authorizeAdmin(User $authUser): void
    {
        if (! $authUser->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Build the index query for users with role filter and search.
     * Only admins are allowed to list users.
     */
    public function buildIndexQuery(User $authUser, array $filters): Builder
    {
        $this->authorizeAdmin($authUser);

        $query = User::with('roles')
            ->withCount(['cars', 'assignedTickets as tickets_count']);

        if (! empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Get a paginated list of users.
     */
    public function getUsers(User $authUser, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildIndexQuery($authUser, $filters)->paginate($perPage);
    }

    /**
     * Create a new user with a hashed password and role assignment.
     */
    public function createUser(User $authUser, array $validated): User
    {
        $this->authorizeAdmin($authUser);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return $user;
    }

    /**
     * Authorize that the authenticated user can view a target user's profile.
     * Users can view their own profile; admins can view anyone.
     */
    public function authorizeView(User $authUser, User $targetUser): void
    {
        if ($authUser->id !== $targetUser->id && ! $authUser->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Load and return a user with all relationships.
     */
    public function showUser(User $targetUser, User $authUser): User
    {
        $this->authorizeView($authUser, $targetUser);
        $targetUser->load(['roles', 'cars', 'tickets']);

        return $targetUser;
    }

    /**
     * Update a user (profile fields, optional role sync, optional password hash).
     */
    public function updateUser(User $targetUser, User $authUser, array $validated): User
    {
        $this->authorizeView($authUser, $targetUser);

        // Only admins can change roles
        if (isset($validated['role'])) {
            if (! $authUser->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'role' => 'Only admins can change user roles',
                ]);
            }
            $targetUser->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $targetUser->update($validated);

        return $targetUser;
    }

    /**
     * Delete a user. Only admins may do this, and they cannot delete themselves.
     */
    public function deleteUser(User $targetUser, User $authUser): void
    {
        $this->authorizeAdmin($authUser);

        if ($authUser->id === $targetUser->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account',
            ]);
        }

        $targetUser->delete();
    }

    /**
     * Get the list of mechanics with active ticket counts.
     */
    public function getMechanics(User $authUser): Collection
    {
        if (! $authUser->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        return User::role('mechanic')
            ->with('roles')
            ->withCount(['assignedTickets' => function ($query) {
                $query->whereIn('status', ['assigned', 'in_progress']);
            }])
            ->get();
    }

    /**
     * Get paginated tickets belonging to a target user.
     */
    public function getUserTickets(User $targetUser, User $authUser, int $perPage = 15): LengthAwarePaginator
    {
        if ($authUser->id !== $targetUser->id && ! $authUser->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        return $targetUser->tickets()
            ->with(['car', 'mechanic', 'problems'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get all cars belonging to a target user with ticket counts.
     */
    public function getUserCars(User $targetUser, User $authUser): Collection
    {
        if ($authUser->id !== $targetUser->id && ! $authUser->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        return $targetUser->cars()
            ->withCount('tickets')
            ->get();
    }
}
