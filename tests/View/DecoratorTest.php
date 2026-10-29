<?php

namespace Tests\View;

use Tests\Support\TestCase;

/**
 * @internal
 */
final class DecoratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config('Themes')->componentsLookupPaths = [SUPPORTPATH . 'Views/Components/'];
    }

    public function testViewOutputIsDecorated(): void
    {
        // framed.php holds an `<x-greeting />` tag, which only the registered decorator renders.
        $this->assertStringContainsString('Hello Ada|hi', view('Components/framed', ['slot' => 'hi']));
    }
}
