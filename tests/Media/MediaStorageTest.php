<?php

namespace Tests\Media;

use Bonfire\Media\MediaException;
use Bonfire\Media\MediaStorage;
use Bonfire\Media\Models\MediaModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Media\MediaTestCase;

/**
 * @internal
 */
final class MediaStorageTest extends MediaTestCase
{
    public function testStoresAnUploadAndRecordsItsDimensions(): void
    {
        $media = (new MediaStorage())->store($this->upload('My Cat.JPG', 30, 20), 'public', 'pets');

        $this->assertFileExists($this->dir . 'my-cat.jpg');

        $row = model(MediaModel::class)->find($media->id);
        $this->assertSame(
            ['public', 'pets', 'my-cat.jpg', 'My Cat.JPG', 'image/jpeg', 30, 20],
            [$row->container, $row->folder, $row->path, $row->filename, $row->mime, $row->width, $row->height],
        );
        $this->assertSame(filesize($this->dir . 'my-cat.jpg'), $row->size);
    }

    public function testAFileThatIsNotAnImageHasNoDimensions(): void
    {
        $media = (new MediaStorage())->store($this->upload('notes.txt', bytes: 'hello'), 'public', '');

        $row = model(MediaModel::class)->find($media->id);
        $this->assertSame(['notes.txt', null, null], [$row->path, $row->width, $row->height]);
    }

    public function testSuffixesANameAlreadyTaken(): void
    {
        $this->mediaFile('cat.jpg');
        $storage = new MediaStorage();

        $this->assertSame('cat-2.jpg', $storage->store($this->upload('cat.jpg'), 'public', '')->path);
        $this->assertSame('cat-3.jpg', $storage->store($this->upload('Cat.jpg'), 'public', '')->path);
        $this->assertStringEqualsFile($this->dir . 'cat.jpg', 'bytes');
    }

    public function testANameKeptByADeletedFileIsStillTaken(): void
    {
        $taken = $this->mediaFile('cat.jpg');
        model(MediaModel::class)->delete($taken->id);

        $this->assertSame('cat-2.jpg', (new MediaStorage())->store($this->upload('cat.jpg'), 'public', '')->path);
    }

    #[DataProvider('provideRefusesTheUpload')]
    public function testRefusesTheUpload(string $name, string $container, int $bytes): void
    {
        config('Media')->maxSize = 1;

        try {
            (new MediaStorage())->store($this->upload($name, bytes: str_repeat('a', $bytes)), $container, '');
            $this->fail('The upload was stored.');
        } catch (MediaException) {
        }

        $this->assertSame(0, model(MediaModel::class)->withDeleted()->countAllResults());
        $this->assertDirectoryDoesNotExist($this->dir);
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function provideRefusesTheUpload(): iterable
    {
        yield 'a disallowed extension' => ['shell.php', 'public', 1];

        yield 'a file over maxSize' => ['big.txt', 'public', 2048];

        yield 'an unknown container' => ['notes.txt', 'private', 1];
    }
}
