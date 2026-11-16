<?php

namespace Tests\Media;

use Bonfire\Media\Entities\Media;
use Tests\Support\Media\MediaTestCase;

/**
 * @internal
 */
final class MediaModelTest extends MediaTestCase
{
    public function testUrlJoinsTheContainerUrlAndPath(): void
    {
        $media = new Media(['container' => 'public', 'path' => 'cat.jpg']);

        $this->assertSame(base_url('uploads/media-test/cat.jpg'), $media->url());
    }
}
