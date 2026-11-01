<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // HTTP feature tests exercise backend behavior without requiring built assets.
        $this->withoutVite();
    }
}
