<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests render pages without needing compiled front-end assets (`npm run build`).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
