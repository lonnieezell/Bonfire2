<?php

namespace Tests\Theme;

use Tests\Support\TestCase;

/**
 * The ⌘K palette's server side: the menu items it may offer.
 *
 * @internal
 */
final class CommandPaletteTest extends TestCase
{
    protected $refresh = true;

    public function testPaletteOffersOnlyTheMenuItemsTheUserMaySee(): void
    {
        // The admin group may view logs but not system info.
        $user = $this->createUser();
        $user->addGroup('admin');

        $urls = array_column($this->paletteItems($user), 'url');

        $this->assertContains(site_url(ADMIN_AREA), $urls);
        $this->assertContains(url_to('sys-logs'), $urls);
        $this->assertNotContains(url_to('sys-info'), $urls);
    }

    public function testPaletteItemsNameTheirMenuSection(): void
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');

        $this->assertContains(
            ['label' => 'Logs', 'hint' => 'Tools', 'url' => url_to('sys-logs')],
            $this->paletteItems($user),
        );
    }

    private function paletteItems($user): array
    {
        $html = $this->actingAs($user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertSame(
            1,
            preg_match('#<script type="application/json" id="bf-palette-items">(.*?)</script>#s', $html, $match),
            'No palette items found.',
        );

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
