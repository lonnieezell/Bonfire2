<?php

namespace Tests\View;

use Bonfire\View\Metadata;
use Bonfire\View\Theme;
use Config\Services;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MetadataTest extends TestCase
{
    private Metadata $meta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->meta = new Metadata();
    }

    protected function tearDown(): void
    {
        Services::resetSingle('viewMeta');

        // render() in the App theme test sets the current theme and caches the theme list.
        self::setPrivateProperty(Theme::class, 'currentTheme', null);
        self::setPrivateProperty(Theme::class, 'themeInfo', []);

        parent::tearDown();
    }

    public function testCanRenderTitleTag()
    {
        $this->assertSame('<title>' . setting('Site.siteName') . '</title>', $this->meta->render('title'));
    }

    public function testCanRenderMetaTags()
    {
        $this->assertSame(
            '<meta charset="UTF-8" >' . "\n" .
            '<meta name="viewport" content="width=device-width, initial-scale=1" >' . "\n",
            $this->meta->render('meta'),
        );
    }

    public function testCanRenderLinkTags()
    {
        $this->meta->addLink(['rel' => 'icon', 'href' => 'favicon.ico']);

        $this->assertSame('<link rel="icon" href="favicon.ico" >' . "\n", $this->meta->render('link'));
    }

    public function testCanRenderScriptTags()
    {
        $this->meta->addScript(['src' => 'https://example.com/app.js']);

        $this->assertSame('<script src="https://example.com/app.js" ></script>' . "\n", $this->meta->render('script'));
    }

    public function testCanRenderStyleTags()
    {
        $this->meta->addStyle(['href' => 'https://example.com/app.css', 'rel' => 'stylesheet']);

        $this->assertSame('<link href="https://example.com/app.css" rel="stylesheet" >' . "\n", $this->meta->render('style'));
    }

    public function testRendersTheSameScriptOnce()
    {
        $this->meta->addScript(['src' => 'https://example.com/app.js'])
            ->addScript(['src' => 'https://example.com/app.js']);

        $this->assertCount(1, $this->meta->scripts());
        $this->assertSame('<script src="https://example.com/app.js" ></script>' . "\n", $this->meta->render('script'));
    }

    public function testRendersTheSameStyleLinkAndMetaOnce()
    {
        $this->meta->addStyle(['href' => 'a.css', 'rel' => 'stylesheet'])
            ->addStyle(['href' => 'a.css', 'rel' => 'stylesheet'])
            ->addLink(['rel' => 'icon', 'href' => 'favicon.ico'])
            ->addLink(['rel' => 'icon', 'href' => 'favicon.ico'])
            ->addMeta(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1'])
            ->addMeta(['name' => 'description', 'content' => 'Hi'])
            ->addMeta(['name' => 'description', 'content' => 'Hi']);

        $this->assertCount(1, $this->meta->styles());
        $this->assertCount(1, $this->meta->links());
        $this->assertCount(3, $this->meta->meta());
    }

    public function testKeepsEntriesThatDiffer()
    {
        $this->meta->addScript(['src' => 'a.js'])->addScript(['src' => 'b.js']);

        $this->assertCount(2, $this->meta->scripts());
    }

    public function testRendersTheSameRawScriptAndRawStyleOnce()
    {
        $this->meta->addRawScript('let a = 1;')->addRawScript('let a = 1;')->addRawScript('let b = 2;')
            ->addRawStyle('a { color: red; }')->addRawStyle('a { color: red; }');

        $this->assertSame('<script>let a = 1;</script><script>let b = 2;</script>', $this->meta->render('rawScripts'));
        $this->assertSame('<style>a { color: red; }</style>', $this->meta->render('rawStyles'));
    }

    public function testEscapesAttributeValues()
    {
        $this->meta->addMeta(['name' => 'description', 'content' => 'Say "hi" & <bye>']);

        $this->assertStringContainsString(
            '<meta name="description" content="Say &quot;hi&quot; &amp; &lt;bye&gt;" >',
            $this->meta->render('meta'),
        );
    }

    public function testEscapesAttributeValuesOfScriptsLinksAndStyles()
    {
        $this->meta->addScript(['src' => 'a.js?x=1&y="2"'])
            ->addLink(['rel' => 'x', 'href' => 'a?b=1&c=2'])
            ->addStyle(['href' => 'a.css?v=1&w=2', 'rel' => 'stylesheet']);

        $this->assertSame('<script src="a.js?x=1&amp;y=&quot;2&quot;" ></script>' . "\n", $this->meta->render('script'));
        $this->assertSame('<link rel="x" href="a?b=1&amp;c=2" >' . "\n", $this->meta->render('link'));
        $this->assertSame('<link href="a.css?v=1&amp;w=2" rel="stylesheet" >' . "\n", $this->meta->render('style'));
    }

    public function testRawStyleContentRendersInsideStyleTag()
    {
        $this->meta->addRawStyle('.hero > p { margin: 0; }');

        $this->assertSame(['.hero > p { margin: 0; }'], $this->meta->rawStyles());
        $this->assertSame('<style>.hero > p { margin: 0; }</style>', $this->meta->render('rawStyles'));
    }

    public function testAppThemeOutputsRawStyles()
    {
        service('viewMeta')->addRawStyle('.hero { color: red; }');

        $this->assertStringContainsString('<style>.hero { color: red; }</style>', render('App', 'master'));
    }
}
