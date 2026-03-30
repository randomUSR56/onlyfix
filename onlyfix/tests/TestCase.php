<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Backend tests should not fail because frontend Vue page files are missing
        config(['inertia.testing.ensure_pages_exist' => false]);
    }
}
