<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $role = 'user';
        if ($user->hasRole('admin')) {
            $role = 'admin';
        } elseif ($user->hasRole('mechanic')) {
            $role = 'mechanic';
        }

        return Inertia::render('Help/Index', [
            'role' => $role,
        ]);
    }
}
