<?php

namespace Tests\Content;

use Bonfire\Content\ContentException;
use Bonfire\Content\Models\ContentEntryModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Services;
use Tests\Support\Content\ContentTestCase;

/**
 * Globals: site-wide values in content/globals/<handle>.yaml, read with globals().
 *
 * @internal
 */
final class GlobalsTest extends ContentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        mkdir($this->site . 'globals');
        mkdir($this->blueprints . 'globals');

        $this->writeFile('globals/site.yaml', <<<'YAML'
            name: Bonfire
            posts_per_page: "12"
            maintenance: true
            launched: 2026-10-27
            tagline:
            YAML);
        file_put_contents($this->blueprints . 'globals/site.yaml', <<<'YAML'
            title: Site
            fields:
              - handle: name
                type: text
              - handle: posts_per_page
                type: integer
              - handle: maintenance
                type: toggle
              - handle: launched
                type: date
              - handle: tagline
                type: text
            YAML);
    }

    // The value is cast by the blueprint's field type

    public function testItReturnsTheValueTheFileHolds(): void
    {
        $this->assertSame('Bonfire', globals('site')->field('name'));
    }

    public function testTheBlueprintsFieldTypeCastsTheValue(): void
    {
        $site = globals('site');

        $this->assertSame(12, $site->field('posts_per_page'));
        $this->assertTrue($site->field('maintenance'));
        $this->assertSame('2026-10-27', $site->field('launched'));
    }

    public function testADatetimeKeepsItsTime(): void
    {
        $this->writeFile('globals/site.yaml', "opens: 2026-10-27 09:30:00\ncloses: \"2026-10-28\"\n");
        file_put_contents($this->blueprints . 'globals/site.yaml', "fields:\n  - handle: opens\n    type: datetime\n  - handle: closes\n    type: datetime\n");

        $this->assertSame('2026-10-27 09:30:00', globals('site')->field('opens'));
        $this->assertSame('2026-10-28 00:00:00', globals('site')->field('closes'));
    }

    public function testAToggleThatIsOffIsFalseNotTheDefault(): void
    {
        $this->writeFile('globals/site.yaml', "maintenance: false\n");

        $this->assertFalse(globals('site')->field('maintenance', 'default'));
    }

    public function testAValueThatDoesNotFitItsTypeGivesTheDefault(): void
    {
        $this->writeFile('globals/site.yaml', "posts_per_page: lots\n");

        $this->assertSame(5, globals('site')->field('posts_per_page', 5));
    }

    public function testAFieldTheBlueprintDoesNotListIsReturnedAsTheFileHoldsIt(): void
    {
        $this->writeFile('globals/site.yaml', "social:\n  - twitter\n  - mastodon\n");

        $this->assertSame(['twitter', 'mastodon'], globals('site')->field('social'));
    }

    public function testAGlobalWithoutABlueprintIsReadAsTheFileHoldsIt(): void
    {
        unlink($this->blueprints . 'globals/site.yaml');

        $this->assertSame('12', globals('site')->field('posts_per_page'));
    }

    // A missing field gives the default

    public function testAMissingFieldGivesTheDefaultPassedIn(): void
    {
        $this->assertSame('fallback', globals('site')->field('nope', 'fallback'));
        $this->assertNull(globals('site')->field('nope'));
    }

    public function testAnEmptyValueGivesTheDefaultToo(): void
    {
        $this->assertSame('Welcome', globals('site')->field('tagline', 'Welcome'));
    }

    public function testAnEmptyStringGivesTheDefaultToo(): void
    {
        $this->writeFile('globals/site.yaml', "name: ''\n");

        $this->assertSame('Welcome', globals('site')->field('name', 'Welcome'));
    }

    public function testAToggleThatIsNotTrueOrFalseGivesTheDefault(): void
    {
        $this->writeFile('globals/site.yaml', "maintenance: 'yes'\n");

        $this->assertSame('default', globals('site')->field('maintenance', 'default'));
    }

    public function testAnUnquotedDateIsTextWithOrWithoutABlueprint(): void
    {
        unlink($this->blueprints . 'globals/site.yaml');

        $this->assertSame('2026-10-27 00:00:00', globals('site')->field('launched'));
    }

    public function testAnEmptyFileHasNoValues(): void
    {
        $this->writeFile('globals/site.yaml', '');

        $this->assertSame('x', globals('site')->field('name', 'x'));
    }

    // Unknown handles and invalid files

    public function testAnUnknownHandleIsRefused(): void
    {
        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('Unknown globals: nope');

        globals('nope');
    }

    public function testAHandleThatIsAPathIsRefused(): void
    {
        $this->writeFile('secret.yaml', "name: leaked\n");

        $this->expectException(ContentException::class);

        globals('../secret');
    }

    public function testMalformedYamlIsRefused(): void
    {
        $this->writeFile('globals/site.yaml', "name: [unclosed\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('Invalid YAML');

        globals('site');
    }

    public function testAFileThatIsNotAMappingIsRefused(): void
    {
        $this->writeFile('globals/site.yaml', "- one\n- two\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('must be a YAML mapping');

        globals('site');
    }

    public function testAnInvalidBlueprintIsRefused(): void
    {
        file_put_contents($this->blueprints . 'globals/site.yaml', "title: Site\nfields:\n  - handle: name\n    type: nonsense\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('needs a `type`');

        globals('site');
    }

    public function testABlueprintCannotHaveABody(): void
    {
        file_put_contents($this->blueprints . 'globals/site.yaml', "fields:\n  - handle: body\n    type: markdown\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('body');

        globals('site');
    }

    // Auto-routing, themes and routes

    public function testItWorksWithAutoRoutingOffAndNoTheme(): void
    {
        config('Content')->autoRoute  = false;
        config('Themes')->collections = [];

        $this->assertSame('Bonfire', globals('site')->field('name'));
    }

    public function testItWorksWithAutoRoutingOn(): void
    {
        config('Content')->autoRoute = true;

        $this->assertSame('Bonfire', globals('site')->field('name'));
    }

    public function testGlobalsHaveNoRoutesAndAreNotIndexed(): void
    {
        config('Content')->autoRoute = true;
        Services::resetSingle('routes');
        Services::resetSingle('router');

        foreach (['globals/site', 'site', 'globals'] as $uri) {
            try {
                $this->get($uri);
                $this->fail("{$uri} was served.");
            } catch (PageNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(0, (new ContentEntryModel())->countAllResults());
        $this->assertNull(content()->findByUri('globals/site'));

        foreach (array_keys(service('routes')->getRoutes('GET')) as $route) {
            $this->assertStringNotContainsString('global', (string) $route);
        }
    }
}
