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
        $media = $this->storage()->store($this->upload('My Cat.JPG'), 'public', ' /pets/ ');

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
        $media = $this->storage()->store($this->upload('notes.txt', 'hello'), 'public', '');

        $row = model(MediaModel::class)->find($media->id);
        $this->assertSame(['notes.txt', null, null], [$row->path, $row->width, $row->height]);
    }

    public function testSuffixesANameAlreadyTaken(): void
    {
        $this->mediaFile('cat.jpg');
        $storage = $this->storage();

        $this->assertSame('cat-2.jpg', $storage->store($this->upload('cat.jpg'), 'public', '')->path);
        $this->assertSame('cat-3.jpg', $storage->store($this->upload('Cat.jpg'), 'public', '')->path);
        $this->assertStringEqualsFile($this->dir . 'cat.jpg', 'bytes');
    }

    public function testANameKeptByADeletedFileIsStillTaken(): void
    {
        $taken = $this->mediaFile('cat.jpg');
        model(MediaModel::class)->delete($taken->id);

        $this->assertSame('cat-2.jpg', $this->storage()->store($this->upload('cat.jpg'), 'public', '')->path);
    }

    public function testAFileThatCanNotBeMovedLeavesNoRow(): void
    {
        $this->expectException(MediaException::class);

        try {
            // The real move refuses a file PHP didn't receive as an HTTP upload.
            (new MediaStorage())->store($this->upload('cat.jpg'), 'public', '');
        } finally {
            $this->assertSame(0, model(MediaModel::class)->withDeleted()->countAllResults());
        }
    }

    #[DataProvider('provideRefusesTheUpload')]
    public function testRefusesTheUpload(string $name, string $container, int $bytes, string $folder = ''): void
    {
        config('Media')->maxSize = 1;

        try {
            $this->storage()->store($this->upload($name, str_repeat('a', $bytes)), $container, $folder);
            $this->fail('The upload was stored.');
        } catch (MediaException) {
        }

        $this->assertSame(0, model(MediaModel::class)->withDeleted()->countAllResults());
        $this->assertDirectoryDoesNotExist($this->dir);
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: int, 3?: string}>
     */
    public static function provideRefusesTheUpload(): iterable
    {
        yield 'a disallowed extension' => ['shell.php', 'public', 1];

        yield 'a file over maxSize' => ['big.txt', 'public', 2048];

        yield 'an unknown container' => ['notes.txt', 'private', 1];

        yield 'a folder over 255 characters' => ['notes.txt', 'public', 1, str_repeat('a', 256)];
    }
}
