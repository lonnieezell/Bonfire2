<?php

namespace Tests\View;

use Bonfire\View\ComponentRenderer;
use Bonfire\View\Theme;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ComponentRendererTest extends TestCase
{
    private ComponentRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        config('Themes')->componentsLookupPaths = [SUPPORTPATH . 'Views/Components/'];
        $this->renderer                         = new ComponentRenderer();
    }

    protected function tearDown(): void
    {
        Theme::setTheme('');

        parent::tearDown();
    }

    #[DataProvider('provideRendersTags')]
    public function testRendersTags(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->renderer->render($input));
    }

    public static function provideRendersTags(): iterable
    {
        return [
            'text without tags'                  => ['<p>plain</p>', '<p>plain</p>'],
            'self-closing without attributes'    => ['<x-greeting />', 'Hello nobody'],
            'self-closing with attribute'        => ['<x-greeting name="Ada" />', 'Hello Ada'],
            'paired with slot'                   => ['<x-wrapper>hi</x-wrapper>', '[hi]'],
            'paired with attribute and slot'     => ['<x-greeting name="Ada">ignored</x-greeting>', 'Hello Ada'],
            'tags inside surrounding markup'     => ['<p><x-greeting name="Ada" /></p>', '<p>Hello Ada</p>'],
            'nested paired'                      => ['<x-wrapper><x-wrapper>hi</x-wrapper></x-wrapper>', '[[hi]]'],
            'self-closing inside paired'         => ['<x-wrapper><x-greeting name="Ada" /></x-wrapper>', '[Hello Ada]'],
            'class-based self-closing with attr' => ['<x-badge label="new" />', '<b>new</b>'],
            'class-based self-closing, no attr'  => ['<x-badge />', '<b>none</b>'],
            'class-based paired with attr'       => ['<x-badge label="new">x</x-badge>', '<b>new</b>'],
        ];
    }

    #[DataProvider('provideMissingViewThrows')]
    public function testMissingViewThrows(string $input): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('View not found for component: nope');

        $this->renderer->render($input);
    }

    public static function provideMissingViewThrows(): iterable
    {
        return [
            'self-closing' => ['<x-nope />'],
            'paired'       => ['<x-nope>hi</x-nope>'],
            'nested'       => ['<x-wrapper><x-nope /></x-wrapper>'],
        ];
    }

    public function testThemeComponentsTakePrecedenceOverLookupPaths(): void
    {
        Theme::setTheme('Admin');

        // The Admin theme's own button, not the fixture's `button.php`.
        $html = $this->renderer->render('<x-button>Save</x-button>');

        $this->assertStringContainsString('class="btn btn-primary btn-lg"', $html);
        $this->assertStringNotContainsString('<i>', $html);
    }

    public function testFallsBackToLookupPathsWhenThemeHasNoComponent(): void
    {
        Theme::setTheme('Admin');

        $this->assertSame('Hello Ada', $this->renderer->render('<x-greeting name="Ada" />'));
    }
}
