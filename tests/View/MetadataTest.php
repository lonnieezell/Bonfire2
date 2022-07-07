<?php

namespace Tests\View;

use Tests\Support\TestCase;
use Bonfire\View\Metadata;

class MetadataTest extends TestCase
{
    /**
     * @var \Bonfire\View\Metadata
     */
    protected $meta;

    public function setUp(): void
    {
        parent::setUp();

        $this->meta = new Metadata();
    }

    public function testCanRenderTitleTag()
    {
        $this->assertEquals('<title>Bonfire</title>', $this->meta->render('title'));
    }

    public function testCanRenderMetaTags()
    {
        $this->assertEquals(
            '<meta charset="UTF-8" >' . "\n" .
            '<meta viewport="width=device-width, initial-scale=1" >'. "\n",
            $this->meta->render('meta', 'meta')
        );
    }

    public function testCanRenderLinkTags()
    {
        $this->meta->addLink(['rel' => 'icon', 'href' => 'favicon.ico']);

        $this->assertEquals('<link rel="icon" href="favicon.ico" >'. "\n", $this->meta->render('link'));
    }

    public function testCanRenderScriptTags()
    {
        $this->meta->addScript(['src' => 'https://example.com/app.js']);

        $this->assertEquals('<script src="https://example.com/app.js" ></script>'. "\n", $this->meta->render('script'));
    }

    public function testCanRenderStyleTags()
    {
        $this->meta->addStyle(['href' => 'https://example.com/app.css', 'rel' => 'stylesheet']);

        $this->assertEquals('<link href="https://example.com/app.css" rel="stylesheet" >'. "\n", $this->meta->render('style'));
    }
}
