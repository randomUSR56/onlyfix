<?php

namespace App\Services;

use App\Models\Car;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class CarService
{
    /**
     * Build the index query for cars with role-based scoping and search.
     */
    public function buildIndexQuery(User $user, array $filters): Builder
    {
        $query = Car::with(['user', 'tickets']);

        // Admin and mechanics can view all cars, optionally filtered by user
        if ($user->hasAnyRole(['admin', 'mechanic'])) {
            if (! empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
        } else {
            // Regular users can only view their own cars
            $query->where('user_id', $user->id);
        }

        // Search by make, model, license plate, or VIN
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Get a paginated list of cars.
     */
    public function getCars(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildIndexQuery($user, $filters)->paginate($perPage);
    }

    /**
     * Authorize that a user can create cars (mechanics cannot).
     */
    public function authorizeCreate(User $user): void
    {
        if ($user->hasRole('mechanic') && ! $user->hasRole('admin')) {
            abort(403, 'Mechanics cannot create cars');
        }
    }

    /**
     * Create a new car, enforcing ownership rules.
     */
    public function createCar(User $user, array $validated): Car
    {
        $this->authorizeCreate($user);

        // Regular users can only create cars for themselves
        if (! $user->hasRole('admin') && isset($validated['user_id'])) {
            if ($validated['user_id'] != $user->id) {
                throw ValidationException::withMessages([
                    'user_id' => 'You can only create cars for yourself',
                ]);
            }
        }

        // Default to authenticated user if no user_id provided
        $validated['user_id'] = $validated['user_id'] ?? $user->id;

        return Car::create($validated);
    }

    /**
     * Authorize that a user can view a specific car.
     */
    public function authorizeView(User $user, Car $car): void
    {
        if (! $user->hasAnyRole(['admin', 'mechanic']) && $car->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Load and return a car with all relationships.
     */
    public function showCar(Car $car, User $user): Car
    {
        $this->authorizeView($user, $car);
        $car->load(['user', 'tickets.problems']);

        return $car;
    }

    /**
     * Get the permission flags for a car relative to the given user.
     */
    public function getCarPermissions(Car $car, User $user): array
    {
        $isOwner = $car->user_id === $user->id;
        $isAdmin = $user->hasRole('admin');

        return [
            'canEdit' => $isOwner || $isAdmin,
            'canDelete' => $isOwner || $isAdmin,
        ];
    }

    /**
     * Authorize that a user can modify (update/delete) a specific car.
     */
    public function authorizeModify(User $user, Car $car): void
    {
        if (! $user->hasRole('admin') && $car->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Update a car's attributes.
     */
    public function updateCar(Car $car, User $user, array $validated): Car
    {
        $this->authorizeModify($user, $car);
        $car->update($validated);

        return $car;
    }

    /**
     * Delete a car.
     */
    public function deleteCar(Car $car, User $user): void
    {
        $this->authorizeModify($user, $car);
        $car->delete();
    }

    /**
     * Get paginated tickets for a specific car.
     */
    public function getCarTickets(Car $car, User $user, int $perPage = 15): LengthAwarePaginator
    {
        $this->authorizeView($user, $car);

        return $car->tickets()
            ->with(['user', 'mechanic', 'problems'])
            ->paginate($perPage);
    }
}
