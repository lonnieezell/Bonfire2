<?php

namespace Tests\Support\Media;

use Bonfire\Media\Config\Media as MediaConfig;
use Bonfire\Media\Entities\Media;
use Bonfire\Media\MediaStorage;
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
     * MediaStorage, moving files with rename(): PHP only moves a file it received as a real HTTP upload.
     */
    protected function storage(): MediaStorage
    {
        return new class () extends MediaStorage {
            protected function moveUpload(UploadedFile $file, string $directory, string $name): bool
            {
                return rename($file->getTempName(), $directory . '/' . $name);
            }
        };
    }

    /**
     * A 30 × 20 JPEG in a temp file, or the given bytes when there are any.
     *
     * @return string its path
     */
    protected function tempFile(?string $bytes = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bf-media');

        if ($bytes === null) {
            imagejpeg(imagecreatetruecolor(30, 20), $path);
        } else {
            file_put_contents($path, $bytes);
        }

        return $path;
    }

    /**
     * An upload of tempFile().
     */
    protected function upload(string $name, ?string $bytes = null): UploadedFile
    {
        $path = $this->tempFile($bytes);

        return new UploadedFile($path, $name, 'application/octet-stream', filesize($path), UPLOAD_ERR_OK);
    }

    /**
     * Puts files into $_FILES['files'] as a multiple upload, each a JPEG or the given bytes.
     *
     * @param list<string> $names
     */
    protected function filesToUpload(array $names, ?string $bytes = null): void
    {
        $files = ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];

        foreach ($names as $name) {
            $path = $this->tempFile($bytes);

            $files['name'][]     = $name;
            $files['type'][]     = 'image/jpeg';
            $files['tmp_name'][] = $path;
            $files['error'][]    = UPLOAD_ERR_OK;
            $files['size'][]     = filesize($path);
        }

        service('superglobals')->setFilesArray(['files' => $files]);
    }

    /**
     * Writes a file into the container and adds its row.
     */
    protected function mediaFile(string $path, string $folder = '', ?string $alt = null, string $mime = 'image/jpeg'): Media
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
            'mime'      => $mime,
            'size'      => 5,
            'alt'       => $alt,
        ]);

        return $model->find($id);
    }
}
