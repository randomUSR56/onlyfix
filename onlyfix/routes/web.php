<?php

use App\Http\Controllers\CarController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Cars Management
    Route::prefix('cars')->name('cars.')->group(function () {
        Route::get('/', [CarController::class, 'index'])->name('index');
        Route::get('/create', [CarController::class, 'create'])->name('create');
        Route::post('/', [CarController::class, 'store'])->name('store');
        Route::get('/{car}', [CarController::class, 'show'])->name('show');
        Route::get('/{car}/edit', [CarController::class, 'edit'])->name('edit');
        Route::patch('/{car}', [CarController::class, 'update'])->name('update');
        Route::delete('/{car}', [CarController::class, 'destroy'])->name('destroy');

        // Car tickets
        Route::get('/{car}/tickets', [CarController::class, 'tickets'])->name('tickets');
    });

    // Tickets Management
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [TicketController::class, 'edit'])->name('edit');
        Route::patch('/{ticket}', [TicketController::class, 'update'])->name('update');
        Route::delete('/{ticket}', [TicketController::class, 'destroy'])->name('destroy');

        // Ticket workflow actions (mechanics & admins)
        Route::middleware(['role:mechanic|admin'])->group(function () {
            Route::post('/{ticket}/accept', [TicketController::class, 'accept'])->name('accept');
            Route::post('/{ticket}/start', [TicketController::class, 'startWork'])->name('start');
            Route::post('/{ticket}/complete', [TicketController::class, 'complete'])->name('complete');
        });

        // Close ticket (owner or admin)
        Route::post('/{ticket}/close', [TicketController::class, 'close'])->name('close');
    });

    // Problems Management (mechanics & admins only for create/update/delete)
    Route::prefix('problems')->name('problems.')->group(function () {
        Route::get('/', [ProblemController::class, 'index'])->name('index');

        Route::middleware(['role:mechanic|admin'])->group(function () {
            Route::get('/create', [ProblemController::class, 'create'])->name('create');
            Route::post('/', [ProblemController::class, 'store'])->name('store');
            Route::get('/{problem}/edit', [ProblemController::class, 'edit'])->name('edit');
            Route::patch('/{problem}', [ProblemController::class, 'update'])->name('update');
        });

        Route::get('/{problem}', [ProblemController::class, 'show'])->name('show');

        // Delete (admins only)
        Route::middleware(['role:admin'])->group(function () {
            Route::delete('/{problem}', [ProblemController::class, 'destroy'])->name('destroy');
        });
    });

    // Users Management (admins only)
    Route::middleware(['role:admin'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::patch('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Statistics & Reports (mechanics & admins only)
    Route::middleware(['role:mechanic|admin'])->prefix('statistics')->name('statistics.')->group(function () {
        Route::get('/tickets', [TicketController::class, 'statistics'])->name('tickets');
        Route::get('/problems', [ProblemController::class, 'statistics'])->name('problems');
    });

    // Mechanics List (mechanics & admins can view)
    Route::middleware(['role:mechanic|admin'])->group(function () {
        Route::get('/mechanics', [UserController::class, 'mechanics'])->name('mechanics.index');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
