<?php

use App\Models\Problem;

describe('Problem Model - Fillable', function () {
    test('expected fields are fillable', function () {
        $fillable = (new Problem())->getFillable();
        expect($fillable)->toContain('name')
            ->toContain('category')
            ->toContain('description')
            ->toContain('is_active');
    });
});

describe('Problem Model - Casts', function () {
    test('is_active is cast to boolean', function () {
        $casts = (new Problem())->getCasts();
        expect($casts['is_active'])->toBe('boolean');
    });
});

describe('Problem Model - Table', function () {
    test('uses problems table', function () {
        expect((new Problem())->getTable())->toBe('problems');
    });
});
