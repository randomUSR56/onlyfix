<?php

/**
 * IDE Helper for Laravel Auth
 * 
 * This file helps Intelephense understand the return type of auth()->user()
 * It should not be included in production code.
 */

namespace Illuminate\Contracts\Auth {
    interface Guard
    {
        /**
         * Get the currently authenticated user.
         *
         * @return \App\Models\User|null
         */
        public function user();
    }
}

namespace Illuminate\Support\Facades {
    /**
     * @method static \App\Models\User|null user()
     */
    class Auth {}
}

/**
 * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard|\App\Models\User
 */
function auth() {}
