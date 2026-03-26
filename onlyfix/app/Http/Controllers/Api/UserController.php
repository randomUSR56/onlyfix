<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Display a listing of users.
     * Only admins can view all users.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['role', 'search']);

        return response()->json(
            $this->userService->getUsers($request->user(), $filters)
        );
    }

    /**
     * Store a newly created user.
     * Only admins can create users.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:user,mechanic,admin',
        ]);

        $user = $this->userService->createUser($request->user(), $validated);
        $user->load('roles');

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user)
    {
        $user = $this->userService->showUser($user, $request->user());

        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:user,mechanic,admin',
        ]);

        $user = $this->userService->updateUser($user, $request->user(), $validated);
        $user->load('roles');

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Remove the specified user.
     * Only admins can delete users, and they cannot delete themselves.
     */
    public function destroy(Request $request, User $user)
    {
        $this->userService->deleteUser($user, $request->user());

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['roles', 'cars', 'tickets']);

        return response()->json($user);
    }

    /**
     * Get mechanics list.
     */
    public function mechanics(Request $request)
    {
        $mechanics = $this->userService->getMechanics($request->user());

        return response()->json($mechanics);
    }

    /**
     * Get user's tickets.
     */
    public function tickets(Request $request, User $user)
    {
        $tickets = $this->userService->getUserTickets($user, $request->user());

        return response()->json($tickets);
    }

    /**
     * Get user's cars.
     */
    public function cars(Request $request, User $user)
    {
        $cars = $this->userService->getUserCars($user, $request->user());

        return response()->json($cars);
    }
}
