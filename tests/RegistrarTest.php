<?php

namespace Tests;

use Bonfire\Config\Registrar;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
final class RegistrarTest extends TestCase
{
    /**
     * BaseConfig assigns registrar values straight onto the config,
     * so an undeclared key becomes a dynamic property (deprecated in PHP 8.2).
     */
    public function testRegistrarOnlySetsDeclaredProperties(): void
    {
        $methods = (new ReflectionClass(Registrar::class))->getMethods(ReflectionMethod::IS_STATIC);

        foreach ($methods as $method) {
            $class = 'Config\\' . $method->getName();

            foreach (array_keys(Registrar::{$method->getName()}()) as $key) {
                $this->assertTrue(property_exists($class, $key), "{$class} does not declare \${$key}");
            }
        }
    }
}
