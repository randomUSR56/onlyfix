<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\CarService;
use Illuminate\Http\Request;

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

        return response()->json(
            $this->carService->getCars($request->user(), $filters)
        );
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
        $car->load('user');

        return response()->json([
            'message' => 'Car created successfully',
            'data' => $car,
        ], 201);
    }

    /**
     * Display the specified car.
     */
    public function show(Request $request, Car $car)
    {
        $car = $this->carService->showCar($car, $request->user());

        return response()->json($car);
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

        $car = $this->carService->updateCar($car, $request->user(), $validated);
        $car->load('user');

        return response()->json([
            'message' => 'Car updated successfully',
            'data' => $car,
        ]);
    }

    /**
     * Remove the specified car.
     */
    public function destroy(Request $request, Car $car)
    {
        $this->carService->deleteCar($car, $request->user());

        return response()->json([
            'message' => 'Car deleted successfully',
        ]);
    }

    /**
     * Get tickets for a specific car.
     */
    public function tickets(Request $request, Car $car)
    {
        $tickets = $this->carService->getCarTickets($car, $request->user());

        return response()->json($tickets);
    }
}
