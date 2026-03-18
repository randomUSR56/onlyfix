<?php

use App\Models\User;

describe('User Model - Fillable', function () {
    test('expected fields are fillable', function () {
        $fillable = (new User())->getFillable();
        expect($fillable)->toContain('name')
            ->toContain('email')
            ->toContain('password');
    });
});

describe('User Model - Hidden', function () {
    test('sensitive fields are hidden', function () {
        $hidden = (new User())->getHidden();
        expect($hidden)->toContain('password')
            ->toContain('two_factor_secret')
            ->toContain('two_factor_recovery_codes')
            ->toContain('remember_token');
    });
});

describe('User Model - Casts', function () {
    test('email_verified_at is cast to datetime', function () {
        $casts = (new User())->getCasts();
        expect($casts['email_verified_at'])->toBe('datetime');
    });

    test('password is cast to hashed', function () {
        $casts = (new User())->getCasts();
        expect($casts['password'])->toBe('hashed');
    });
});

describe('User Model - Table', function () {
    test('uses users table', function () {
        expect((new User())->getTable())->toBe('users');
    });
});
