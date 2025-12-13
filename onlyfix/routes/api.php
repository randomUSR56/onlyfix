<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\ProblemController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Serve OpenAPI spec
Route::get('/openapi.yaml', function () {
    return response()->file(base_path('openapi.yaml'), [
        'Content-Type' => 'application/yaml',
        'Content-Disposition' => 'inline; filename="openapi.yaml"',
    ]);
});

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // User routes
    Route::prefix('users')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::get('/mechanics', [UserController::class, 'mechanics']);
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::patch('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::get('/{user}/tickets', [UserController::class, 'tickets']);
        Route::get('/{user}/cars', [UserController::class, 'cars']);
    });

    // Car routes
    Route::prefix('cars')->group(function () {
        Route::get('/', [CarController::class, 'index']);
        Route::post('/', [CarController::class, 'store']);
        Route::get('/{car}', [CarController::class, 'show']);
        Route::put('/{car}', [CarController::class, 'update']);
        Route::patch('/{car}', [CarController::class, 'update']);
        Route::delete('/{car}', [CarController::class, 'destroy']);
        Route::get('/{car}/tickets', [CarController::class, 'tickets']);
    });

    // Problem routes
    Route::prefix('problems')->group(function () {
        Route::get('/', [ProblemController::class, 'index']);
        Route::post('/', [ProblemController::class, 'store']);
        Route::get('/statistics', [ProblemController::class, 'statistics']);
        Route::get('/{problem}', [ProblemController::class, 'show']);
        Route::put('/{problem}', [ProblemController::class, 'update']);
        Route::patch('/{problem}', [ProblemController::class, 'update']);
        Route::delete('/{problem}', [ProblemController::class, 'destroy']);
    });

    // Ticket routes
    Route::prefix('tickets')->group(function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/statistics', [TicketController::class, 'statistics']);
        Route::get('/{ticket}', [TicketController::class, 'show']);
        Route::put('/{ticket}', [TicketController::class, 'update']);
        Route::patch('/{ticket}', [TicketController::class, 'update']);
        Route::delete('/{ticket}', [TicketController::class, 'destroy']);

        // Ticket actions
        Route::post('/{ticket}/accept', [TicketController::class, 'accept']);
        Route::post('/{ticket}/start', [TicketController::class, 'startWork']);
        Route::post('/{ticket}/complete', [TicketController::class, 'complete']);
        Route::post('/{ticket}/close', [TicketController::class, 'close']);
    });
});
