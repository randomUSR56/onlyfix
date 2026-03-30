<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\User;
use App\Services\CarService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CarController extends Controller
{
    public function __construct(
        private readonly CarService $carService
    ) {}

    /**
     * Display a listing of cars.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'user_id']);

        return Inertia::render('Cars/Index', [
            'cars' => $this->carService->getCars($request->user(), $filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new car.
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->carService->authorizeCreate($user);

        $users = $user->hasRole('admin')
            ? User::select('id', 'name', 'email')->get()
            : null;

        return Inertia::render('Cars/Create', [
            'users' => $users,
        ]);
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

        $car = $this->carService->createCar($request->user(), $validated);

        return redirect()->route('cars.show', $car)
            ->with('success', 'Car created successfully');
    }

    /**
     * Display the specified car.
     */
    public function show(Request $request, Car $car)
    {
        $user = $request->user();
        $car = $this->carService->showCar($car, $user);
        $permissions = $this->carService->getCarPermissions($car, $user);

        return Inertia::render('Cars/Show', array_merge(
            ['car' => $car],
            $permissions
        ));
    }

    /**
     * Show the form for editing the specified car.
     */
    public function edit(Request $request, Car $car)
    {
        $this->carService->authorizeModify($request->user(), $car);

        return Inertia::render('Cars/Edit', [
            'car' => $car->load('user'),
        ]);
    }

    /**
     * Update the specified car.
     */
    public function update(Request $request, Car $car)
    {
        $validated = $request->validate([
            'make' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'license_plate' => 'sometimes|string|max:255|unique:cars,license_plate,' . $car->id,
            'vin' => 'nullable|string|max:255|unique:cars,vin,' . $car->id,
            'color' => 'nullable|string|max:255',
        ]);

        $this->carService->updateCar($car, $request->user(), $validated);

        return redirect()->route('cars.show', $car)
            ->with('success', 'Car updated successfully');
    }

    /**
     * Remove the specified car.
     */
    public function destroy(Request $request, Car $car)
    {
        $this->carService->deleteCar($car, $request->user());

        return redirect()->route('cars.index')
            ->with('success', 'Car deleted successfully');
    }
}
