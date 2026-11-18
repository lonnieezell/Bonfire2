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

    public function testTheMediaHelperGivesALiveFileAndNullOtherwise(): void
    {
        helper('media');

        $live = $this->mediaFile('cat.jpg');
        $gone = $this->mediaFile('gone.jpg');
        model(MediaModel::class)->delete($gone->id);

        $this->assertSame(base_url('uploads/media-test/cat.jpg'), media($live->id)?->url());
        $this->assertNull(media($gone->id), 'a file in the Recycler');
        $this->assertNull(media('6f1c2a54-0000-4000-8000-000000000000'), 'an unknown id');
        $this->assertNull(media(''), 'an empty field');
        $this->assertNull(media(null), 'a field that was never filled');
    }
}
