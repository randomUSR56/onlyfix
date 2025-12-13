<?php

namespace App\Policies;

use App\Models\User;

class TicketPolicy
{
    /**
     * Determine if the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        // Mechanics and admins can view all tickets
        return $user->hasAnyRole(['mechanic', 'admin']);
    }

    /**
     * Determine if the user can view a specific ticket.
     */
    public function view(User $user, $ticket): bool
    {
        // Users can view their own tickets
        // Mechanics and admins can view any ticket
        return $user->hasAnyRole(['mechanic', 'admin']) ||
               $ticket->user_id === $user->id;
    }

    /**
     * Determine if the user can create tickets.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create tickets
        return true;
    }

    /**
     * Determine if the user can update a ticket.
     */
    public function update(User $user, $ticket): bool
    {
        // Admins can update any ticket
        if ($user->hasRole('admin')) {
            return true;
        }

        // Mechanics can update any ticket
        if ($user->hasRole('mechanic')) {
            return true;
        }

        // Users can only update their own tickets
        return $ticket->user_id === $user->id;
    }

    /**
     * Determine if the user can delete a ticket.
     */
    public function delete(User $user, $ticket): bool
    {
        // Admins can delete any ticket
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can only delete their own tickets
        return $ticket->user_id === $user->id;
    }

    /**
     * Determine if the user can accept/assign tickets.
     */
    public function accept(User $user): bool
    {
        // Only mechanics and admins can accept tickets
        return $user->hasAnyRole(['mechanic', 'admin']);
    }

    /**
     * Determine if the user can manage other users.
     */
    public function manageUsers(User $user): bool
    {
        // Only admins can manage users
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can reset passwords.
     */
    public function resetPasswords(User $user): bool
    {
        // Only admins can reset passwords
        return $user->hasRole('admin');
    }
}
