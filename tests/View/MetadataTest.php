<?php

namespace Tests\View;

use Bonfire\View\Metadata;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MetadataTest extends TestCase
{
    /**
     * @var \Bonfire\View\Metadata
     */
    protected $meta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->meta = new Metadata();
    }

    public function testCanRenderTitleTag()
    {
        $this->assertSame('<title>' . setting('Site.siteName') . '</title>', $this->meta->render('title'));
    }

    public function testCanRenderMetaTags()
    {
        $this->assertSame(
            '<meta charset="UTF-8" >' . "\n" .
            '<meta viewport="width=device-width, initial-scale=1" >' . "\n",
            $this->meta->render('meta')
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
}
