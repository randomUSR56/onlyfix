<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CarController extends Controller
{
    /**
     * Display a listing of cars.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Admin and mechanics can view all cars
        if ($user->hasAnyRole(['admin', 'mechanic'])) {
            $cars = Car::with(['user', 'tickets'])
                ->when($request->user_id, function ($query, $userId) {
                    $query->where('user_id', $userId);
                })
                ->paginate(15);
        } else {
            // Regular users can only view their own cars
            $cars = Car::with(['user', 'tickets'])
                ->where('user_id', $user->id)
                ->paginate(15);
        }

        return response()->json($cars);
    }

    /**
     * Store a newly created car.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'license_plate' => 'required|string|max:255|unique:cars',
            'vin' => 'nullable|string|max:255|unique:cars',
            'color' => 'nullable|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        // Regular users can only create cars for themselves
        if (!$request->user()->hasRole('admin') && isset($validated['user_id'])) {
            if ($validated['user_id'] != $request->user()->id) {
                return response()->json([
                    'message' => 'You can only create cars for yourself'
                ], 403);
            }
        }

        // If user_id not provided, use authenticated user's id
        $validated['user_id'] = $validated['user_id'] ?? $request->user()->id;

        $car = Car::create($validated);
        $car->load('user');

        return response()->json([
            'message' => 'Car created successfully',
            'data' => $car
        ], 201);
    }

    /**
     * Display the specified car.
     */
    public function show(Request $request, Car $car)
    {
        $user = $request->user();

        // Users can only view their own cars unless they're admin/mechanic
        if (!$user->hasAnyRole(['admin', 'mechanic']) && $car->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $car->load(['user', 'tickets.problems']);

        return response()->json($car);
    }

    /**
     * Update the specified car.
     */
    public function update(Request $request, Car $car)
    {
        $user = $request->user();

        // Users can only update their own cars unless they're admin
        if (!$user->hasRole('admin') && $car->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'make' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'license_plate' => 'sometimes|string|max:255|unique:cars,license_plate,' . $car->id,
            'vin' => 'nullable|string|max:255|unique:cars,vin,' . $car->id,
            'color' => 'nullable|string|max:255',
        ]);

        $car->update($validated);
        $car->load('user');

        return response()->json([
            'message' => 'Car updated successfully',
            'data' => $car
        ]);
    }

    /**
     * Remove the specified car.
     */
    public function destroy(Request $request, Car $car)
    {
        $user = $request->user();

        // Users can only delete their own cars unless they're admin
        if (!$user->hasRole('admin') && $car->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $car->delete();

        return response()->json([
            'message' => 'Car deleted successfully'
        ]);
    }

    /**
     * Get tickets for a specific car.
     */
    public function tickets(Request $request, Car $car)
    {
        $user = $request->user();

        // Users can only view tickets for their own cars unless they're admin/mechanic
        if (!$user->hasAnyRole(['admin', 'mechanic']) && $car->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tickets = $car->tickets()
            ->with(['user', 'mechanic', 'problems'])
            ->paginate(15);

        return response()->json($tickets);
    }
}
