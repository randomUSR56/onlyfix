<?php

use App\Http\Controllers\CarController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Models\Car;
use App\Models\Ticket;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', function (\Illuminate\Http\Request $request) {
        /** @var \App\Models\User $user */
        $user = $request->user();
        
        // Admin Dashboard
        if ($user->hasRole('admin')) {
            $stats = [
                'total_users' => \App\Models\User::count(),
                'total_mechanics' => \App\Models\User::role('mechanic')->count(),
                'total_tickets' => Ticket::count(),
                'open_tickets' => Ticket::where('status', 'open')->count(),
                'in_progress_tickets' => Ticket::where('status', 'in_progress')->count(),
                'completed_tickets' => Ticket::where('status', 'completed')->count(),
            ];

            $recentTickets = Ticket::with(['car', 'user'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $recentUsers = \App\Models\User::with('roles')
                ->orderBy('created_at', 'desc')
                ->take(6)
                ->get();

            return Inertia::render('AdminDashboard', [
                'stats' => $stats,
                'recentTickets' => $recentTickets,
                'recentUsers' => $recentUsers,
            ]);
        }
        
        // Mechanic Dashboard
        if ($user->hasRole('mechanic')) {
            $stats = [
                'available_tickets' => Ticket::where('status', 'open')
                    ->whereNull('mechanic_id')
                    ->count(),
                'my_tickets' => Ticket::where('mechanic_id', $user->id)
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->count(),
                'in_progress_tickets' => Ticket::where('mechanic_id', $user->id)
                    ->where('status', 'in_progress')
                    ->count(),
                'completed_tickets' => Ticket::where('mechanic_id', $user->id)
                    ->whereIn('status', ['completed', 'closed'])
                    ->count(),
            ];
            
            // Get available tickets (not assigned)
            $availableTickets = Ticket::where('status', 'open')
                ->whereNull('mechanic_id')
                ->with(['car.user', 'problems'])
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'asc')
                ->take(5)
                ->get();
            
            // Get mechanic's assigned tickets
            $myTickets = Ticket::where('mechanic_id', $user->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->with(['car.user', 'problems'])
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'asc')
                ->take(5)
                ->get();
            
            return Inertia::render('MechanicDashboard', [
                'stats' => $stats,
                'availableTickets' => $availableTickets,
                'myTickets' => $myTickets,
            ]);
        }
        
        // User Dashboard
        $stats = [
            'total_cars' => Car::where('user_id', $user->id)->count(),
            'total_tickets' => Ticket::where('user_id', $user->id)->count(),
            'open_tickets' => Ticket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'assigned', 'in_progress'])
                ->count(),
            'completed_tickets' => Ticket::where('user_id', $user->id)
                ->whereIn('status', ['completed', 'closed'])
                ->count(),
        ];
        
        // Get recent tickets
        $recentTickets = Ticket::where('user_id', $user->id)
            ->with(['car', 'problems'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Get user's cars
        $cars = Car::where('user_id', $user->id)
            ->withCount('tickets')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
        
        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentTickets' => $recentTickets,
            'cars' => $cars,
        ]);
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

    // Users Management
    Route::prefix('users')->name('users.')->group(function () {
        // Admin-only routes
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });

        // Routes accessible by users for their own profile and admins for any user
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::patch('/{user}', [UserController::class, 'update'])->name('update');
    });

    // Help
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
