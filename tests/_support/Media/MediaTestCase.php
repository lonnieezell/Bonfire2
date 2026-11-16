<?php

namespace Tests\Support\Media;

use Bonfire\Media\Config\Media as MediaConfig;
use Bonfire\Media\Entities\Media;
use Bonfire\Media\Models\MediaModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\HTTP\Files\UploadedFile;
use Tests\Support\TestCase;

/**
 * Gives each test an empty `public` container at FCPATH/uploads/media-test/.
 *
 * @internal
 */
abstract class MediaTestCase extends TestCase
{
    protected $refresh = true;

    /**
     * The container's directory, with a trailing slash.
     */
    protected string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        helper('filesystem');

        $this->dir = FCPATH . 'uploads/media-test/';

        $config             = new MediaConfig();
        $config->containers = ['public' => ['path' => $this->dir, 'url' => 'uploads/media-test']];
        Factories::injectMock('config', 'Media', $config);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->dir)) {
            delete_files($this->dir, true, false, true);
            rmdir($this->dir);
        }

        Factories::reset('config');

        parent::tearDown();
    }

    /**
     * An upload of a JPEG of the given size, or of the given bytes when there are any.
     */
    protected function upload(string $name, int $width = 30, int $height = 20, ?string $bytes = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bf-media');

        if ($bytes === null) {
            imagejpeg(imagecreatetruecolor($width, $height), $path);
        } else {
            file_put_contents($path, $bytes);
        }

        // The framework refuses to move anything PHP did not receive as a real HTTP upload.
        return new class ($path, $name, 'application/octet-stream', filesize($path), UPLOAD_ERR_OK) extends UploadedFile {
            public function move(string $targetPath, ?string $name = null, bool $overwrite = false): bool
            {
                return rename($this->getPathname(), rtrim($targetPath, '/') . '/' . $name);
            }
        };
    }

    /**
     * Writes a file into the container and adds its row.
     */
    protected function mediaFile(string $path, string $folder = '', ?string $alt = null): Media
    {
        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }

        file_put_contents($this->dir . $path, 'bytes');

        $model = model(MediaModel::class);
        $id    = $model->insert([
            'container' => 'public',
            'folder'    => $folder,
            'path'      => $path,
            'filename'  => $path,
            'mime'      => 'image/jpeg',
            'size'      => 5,
            'alt'       => $alt,
        ]);

        return $model->find($id);
    }
}
