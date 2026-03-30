<?php

use App\Models\Car;

describe('Car Model - Fillable', function () {
    test('expected fields are fillable', function () {
        $fillable = (new Car())->getFillable();
        expect($fillable)->toContain('user_id')
            ->toContain('make')
            ->toContain('model')
            ->toContain('year')
            ->toContain('license_plate')
            ->toContain('vin')
            ->toContain('color');
    });
});

describe('Car Model - Table', function () {
    test('uses cars table', function () {
        expect((new Car())->getTable())->toBe('cars');
    });
});
