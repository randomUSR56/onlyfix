<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     * Only admins can view all users.
     */
    public function index(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $query = User::with('roles')
            ->withCount(['cars', 'assignedTickets as tickets_count']);

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

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['role', 'search'])
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Users/Create');
    }

    /**
     * Store a newly created user.
     * Only admins can create users.
     */
    public function store(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
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

        return redirect()->route('users.show', $user)
            ->with('success', 'User created successfully');
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user)
    {
        $authUser = $request->user();

        // Users can view their own profile, admins can view anyone
        if ($authUser->id !== $user->id && !$authUser->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $user->load(['roles', 'cars', 'tickets']);

        return Inertia::render('Users/Show', [
            'user' => $user
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(Request $request, User $user)
    {
        $authUser = $request->user();

        // Users can edit their own profile, admins can edit anyone
        if ($authUser->id !== $user->id && !$authUser->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Users/Edit', [
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $authUser = $request->user();

        // Users can update their own profile, admins can update anyone
        if ($authUser->id !== $user->id && !$authUser->hasRole('admin')) {
            abort(403, 'Unauthorized');
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
                return back()->withErrors([
                    'role' => 'Only admins can change user roles'
                ]);
            }
            $user->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified user.
     * Only admins can delete users, and they cannot delete themselves.
     */
    public function destroy(Request $request, User $user)
    {
        $authUser = $request->user();

        if (!$authUser->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        if ($authUser->id === $user->id) {
            return back()->withErrors([
                'user' => 'You cannot delete your own account'
            ]);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully');
    }

    /**
     * Get mechanics list.
     */
    public function mechanics(Request $request)
    {
        if (!$request->user()->hasAnyRole(['mechanic', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $mechanics = User::role('mechanic')
            ->with('roles')
            ->withCount(['assignedTickets' => function ($query) {
                $query->whereIn('status', ['assigned', 'in_progress']);
            }])
            ->get();

        return Inertia::render('Mechanics/Index', [
            'mechanics' => $mechanics
        ]);
    }
}
