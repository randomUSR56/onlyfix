<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of all users (admin only).
     */
    public function index()
    {
        // Only admins can view all users
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')->get();

        return inertia('Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Update a user's role (admin only).
     */
    public function updateRole(Request $request, User $user)
    {
        // Only admins can manage roles
        $this->authorize('manageRoles', $user);

        $request->validate([
            'role' => 'required|in:user,mechanic,admin',
        ]);

        // Sync roles (removes all other roles and assigns the new one)
        $user->syncRoles([$request->role]);

        return back()->with('success', 'User role updated successfully.');
    }

    /**
     * Get the current user's dashboard based on their role.
     */
    public function dashboard()
    {
        $user = auth()->user();

        // Different dashboard views based on role
        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        }

        if ($user->hasRole('mechanic')) {
            return $this->mechanicDashboard();
        }

        return $this->userDashboard();
    }

    /**
     * Admin dashboard - full system overview.
     */
    private function adminDashboard()
    {
        return inertia('Dashboard/Admin', [
            'stats' => [
                'total_users' => User::count(),
                'total_tickets' => 0, // Will be implemented with ticket system
                'open_tickets' => 0,
                'assigned_tickets' => 0,
            ],
        ]);
    }

    /**
     * Mechanic dashboard - available and assigned tickets.
     */
    private function mechanicDashboard()
    {
        return inertia('Dashboard/Mechanic', [
            'stats' => [
                'available_tickets' => 0, // Will be implemented
                'my_tickets' => 0,
                'completed_tickets' => 0,
            ],
        ]);
    }

    /**
     * User dashboard - personal tickets.
     */
    private function userDashboard()
    {
        return inertia('Dashboard/User', [
            'stats' => [
                'my_tickets' => 0, // Will be implemented
                'open_tickets' => 0,
                'completed_tickets' => 0,
            ],
        ]);
    }
}
