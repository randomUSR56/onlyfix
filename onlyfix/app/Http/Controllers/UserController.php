<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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

        return Inertia::render('Users/Index', [
            'users' => $this->userService->getUsers($request->user(), $filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request)
    {
        $this->userService->authorizeAdmin($request->user());

        return Inertia::render('Users/Create');
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

        return redirect()->route('users.show', $user)
            ->with('success', 'User created successfully');
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user)
    {
        $user = $this->userService->showUser($user, $request->user());

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(Request $request, User $user)
    {
        $this->userService->authorizeView($request->user(), $user);

        return Inertia::render('Users/Edit', [
            'user' => $user->load('roles'),
        ]);
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

        $this->userService->updateUser($user, $request->user(), $validated);

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified user.
     * Only admins can delete users, and they cannot delete themselves.
     */
    public function destroy(Request $request, User $user)
    {
        $this->userService->deleteUser($user, $request->user());

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully');
    }
}
