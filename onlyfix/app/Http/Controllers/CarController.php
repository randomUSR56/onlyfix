<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('make', 'like', "%{$search}%")
                          ->orWhere('model', 'like', "%{$search}%")
                          ->orWhere('license_plate', 'like', "%{$search}%")
                          ->orWhere('vin', 'like', "%{$search}%");
                    });
                })
                ->paginate(15);
        } else {
            // Regular users can only view their own cars
            $cars = Car::with(['user', 'tickets'])
                ->where('user_id', $user->id)
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('make', 'like', "%{$search}%")
                          ->orWhere('model', 'like', "%{$search}%")
                          ->orWhere('license_plate', 'like', "%{$search}%")
                          ->orWhere('vin', 'like', "%{$search}%");
                    });
                })
                ->paginate(15);
        }

        return Inertia::render('Cars/Index', [
            'cars' => $cars,
            'filters' => $request->only(['search', 'user_id']),
        ]);
    }

    /**
     * Show the form for creating a new car.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        
        // Mechanics can view but not create cars
        if ($user->hasRole('mechanic') && !$user->hasRole('admin')) {
            abort(403, 'Mechanics cannot create cars');
        }

        /** @var \App\Models\User $user */
        $users = $user->hasRole('admin')
            ? User::select('id', 'name', 'email')->get()
            : null;

        return Inertia::render('Cars/Create', [
            'users' => $users
        ]);
    }

    /**
     * Store a newly created car.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Mechanics can view but not create cars
        if ($user->hasRole('mechanic') && !$user->hasRole('admin')) {
            abort(403, 'Mechanics cannot create cars');
        }

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
                return back()->withErrors([
                    'user_id' => 'You can only create cars for yourself'
                ])->withInput();
            }
        }

        // If user_id not provided, use authenticated user's id
        $validated['user_id'] = $validated['user_id'] ?? $request->user()->id;

        $car = Car::create($validated);

        return redirect()->route('cars.show', $car)
            ->with('success', 'Car created successfully');
    }

    /**
     * Display the specified car.
     */
    public function show(Request $request, Car $car)
    {
        $user = $request->user();

        // Users can only view their own cars unless they're admin/mechanic
        if (!$user->hasAnyRole(['admin', 'mechanic']) && $car->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $car->load(['user', 'tickets.problems']);

        // Determine permissions
        $isOwner = $car->user_id === $user->id;
        $isAdmin = $user->hasRole('admin');

        return Inertia::render('Cars/Show', [
            'car' => $car,
            'canEdit' => $isOwner || $isAdmin,
            'canDelete' => $isOwner || $isAdmin,
        ]);
    }

    /**
     * Show the form for editing the specified car.
     */
    public function edit(Request $request, Car $car)
    {
        $user = $request->user();
        /** @var \App\Models\User $user */
        // Authorization check
        if (!$user->hasRole('admin') && $car->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Cars/Edit', [
            'car' => $car->load('user')
        ]);
    }

    /**
     * Update the specified car.
     */
    public function update(Request $request, Car $car)
    {
        $user = $request->user();

        // Users can only update their own cars unless they're admin
        if (!$user->hasRole('admin') && $car->user_id !== $user->id) {
            abort(403, 'Unauthorized');
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

        return redirect()->route('cars.show', $car)
            ->with('success', 'Car updated successfully');
    }

    /**
     * Remove the specified car.
     */
    public function destroy(Request $request, Car $car)
    {
        $user = $request->user();

        // Users can only delete their own cars unless they're admin
        if (!$user->hasRole('admin') && $car->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $car->delete();

        return redirect()->route('cars.index')
            ->with('success', 'Car deleted successfully');
    }

}
