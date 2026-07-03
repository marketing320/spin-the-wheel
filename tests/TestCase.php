<?php

namespace Tests;

use App\Support\Settings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Settings memoizes values in a static cache; clear it between tests so
        // a value written by one test never leaks into the next.
        Settings::flush();
    }
}
