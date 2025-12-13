<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     * Only admins can view all users.
     */
    public function index(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = User::with('roles');

        // Filter by role
        if ($request->has('role')) {
            $query->role($request->role);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15);

        return response()->json($users);
    }

    /**
     * Store a newly created user.
     * Only admins can create users.
     */
    public function store(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:user,mechanic,admin',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);
        $user->load('roles');

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user)
    {
        $authUser = $request->user();

        // Users can view their own profile, admins can view anyone
        if ($authUser->id !== $user->id && !$authUser->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->load(['roles', 'cars', 'tickets']);

        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $authUser = $request->user();

        // Users can update their own profile, admins can update anyone
        if ($authUser->id !== $user->id && !$authUser->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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

        // Only admins can change roles
        if (isset($validated['role'])) {
            if (!$authUser->hasRole('admin')) {
                return response()->json([
                    'message' => 'Only admins can change user roles'
                ], 403);
            }
            $user->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        $user->load('roles');

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Remove the specified user.
     * Only admins can delete users, and they cannot delete themselves.
     */
    public function destroy(Request $request, User $user)
    {
        $authUser = $request->user();

        if (!$authUser->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($authUser->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
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
        if (!$request->user()->hasAnyRole(['mechanic', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mechanics = User::role('mechanic')
            ->with('roles')
            ->withCount(['assignedTickets' => function ($query) {
                $query->whereIn('status', ['assigned', 'in_progress']);
            }])
            ->get();

        return response()->json($mechanics);
    }

    /**
     * Get user's tickets.
     */
    public function tickets(Request $request, User $user)
    {
        $authUser = $request->user();

        // Users can view their own tickets, admins/mechanics can view anyone's
        if ($authUser->id !== $user->id && !$authUser->hasAnyRole(['mechanic', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tickets = $user->tickets()
            ->with(['car', 'mechanic', 'problems'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($tickets);
    }

    /**
     * Get user's cars.
     */
    public function cars(Request $request, User $user)
    {
        $authUser = $request->user();

        // Users can view their own cars, admins/mechanics can view anyone's
        if ($authUser->id !== $user->id && !$authUser->hasAnyRole(['mechanic', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cars = $user->cars()
            ->withCount('tickets')
            ->get();

        return response()->json($cars);
    }
}
