<?php

namespace Tests\Media;

use Bonfire\Media\Entities\Media;
use Bonfire\Media\Models\MediaModel;
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

    public function testSoftDeleteKeepsTheFileOnDisk(): void
    {
        $media = $this->mediaFile('cat.jpg');

        model(MediaModel::class)->delete($media->id);

        $this->assertNull(model(MediaModel::class)->find($media->id));
        $this->assertFileExists($this->dir . 'cat.jpg');
    }
}
