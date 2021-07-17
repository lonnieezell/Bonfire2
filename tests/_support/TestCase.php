<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;

class TestCase extends CIUnitTestCase
{
    /**
     * When migrations are ran, will ensure
     * all migrations in all modules will run.
     */
    protected $namespace = '';
}
