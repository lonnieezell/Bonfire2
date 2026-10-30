<?php

namespace Tests\View;

use Bonfire\Config\Registrar;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PagerTemplatesTest extends TestCase
{
    #[DataProvider('provideTemplateRendersWithoutStrayPhp')]
    public function testTemplateRendersWithoutStrayPhp(string $template): void
    {
        $html = service('pager')->makeLinks(2, 10, 50, $template);

        $this->assertStringNotContainsString('?>', (string) $html);
    }

    public static function provideTemplateRendersWithoutStrayPhp(): iterable
    {
        foreach (array_keys(Registrar::Pager()['templates']) as $template) {
            yield $template => [$template];
        }
    }
}
