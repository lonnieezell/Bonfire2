<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Media;

use Bonfire\Media\Entities\Media;
use Bonfire\Media\Fields\ImageField;
use Bonfire\Media\Models\MediaModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Puts uploads into a container and adds their rows to the library.
 */
class MediaStorage
{
    /**
     * Checks the upload, moves it into the container under a slug of its name, and records it.
     *
     * @param string $folder     the folder to list it under, '' for none
     * @param bool   $imagesOnly refuses a file that isn't an image, as an image field does
     *
     * @throws MediaException when the upload is refused or can't be moved
     */
    public function store(UploadedFile $file, string $container, string $folder, bool $imagesOnly = false): Media
    {
        $config = config('Media');
        $name   = $file->getClientName();

        if (! isset($config->containers[$container])) {
            throw new MediaException(lang('Media.unknownContainer', [$container]));
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new MediaException($name . ': ' . $file->getErrorString());
        }

        $extension = strtolower($file->getClientExtension());

        if (! in_array($extension, $config->allowedExtensions, true)) {
            throw new MediaException(lang('Media.extensionNotAllowed', [$name]));
        }

        if ($file->getSize() > $config->maxSize * 1024) {
            throw new MediaException(lang('Media.tooLarge', [$name, $config->maxSize]));
        }

        if ($imagesOnly && ! str_starts_with($file->getMimeType(), ImageField::MIME_PREFIX)) {
            throw new MediaException(lang('Media.notAnImageUpload', [$name]));
        }

        $dimensions = @getimagesize($file->getPathname());
        $model      = model(MediaModel::class);
        $row        = [
            'container' => $container,
            'folder'    => $folder,
            'filename'  => $name,
            'mime'      => $file->getMimeType(),
            'size'      => $file->getSize(),
            'width'     => $dimensions === false ? null : $dimensions[0],
            'height'    => $dimensions === false ? null : $dimensions[1],
        ];

        if (! $model->validate($row)) {
            throw new MediaException($name . ': ' . implode(' ', $model->errors()));
        }

        $directory = rtrim($config->containers[$container]['path'], '/');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $media = $this->addRow($model, $row, $directory);

        if ($media === null || ! $this->moveUpload($file, $directory, $media->path)) {
            if ($media !== null) {
                $model->delete($media->id, true);
            }

            throw new MediaException(lang('Media.moveFailed', [$name]));
        }

        return $media;
    }

    /**
     * Adds the row under a free path, before the file is moved: the unique (container, path) key keeps a concurrent
     * upload of the same name from taking the path in between. One that loses that race chooses again.
     *
     * @param array<string, mixed> $row
     */
    private function addRow(MediaModel $model, array $row, string $directory): ?Media
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $row['path'] = $this->freeName($row['container'], $directory, $row['filename']);

            try {
                $id = $model->insert($row);
            } catch (DatabaseException $e) {
                log_message('warning', 'Media: could not add {path}: {message}', ['path' => $row['path'], 'message' => $e->getMessage()]);

                continue;
            }

            if ($id !== false) {
                return $model->find($id);
            }
        }

        return null;
    }

    /**
     * Moves the upload into place, which PHP allows only for a file it received as an HTTP upload.
     */
    protected function moveUpload(UploadedFile $file, string $directory, string $name): bool
    {
        try {
            return $file->move($directory, $name);
        } catch (HTTPException) {
            return false;
        }
    }

    /**
     * `<slug>.<ext>` for an upload named `<name>.<ext>`, or `<slug>-2.<ext>`, `<slug>-3.<ext>` and on when a file on disk
     * or a row, deleted or not, has it.
     */
    private function freeName(string $container, string $directory, string $uploadedName): string
    {
        helper('url');

        $extension = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));
        $slug      = url_title(pathinfo($uploadedName, PATHINFO_FILENAME), '-', true);
        $slug      = $slug === '' ? 'file' : $slug;
        $model     = model(MediaModel::class);

        for ($n = 1;; $n++) {
            $path = $slug . ($n === 1 ? '' : "-{$n}") . '.' . $extension;

            if (! is_file($directory . '/' . $path) && $model->withDeleted()->where(['container' => $container, 'path' => $path])->countAllResults() === 0) {
                return $path;
            }
        }
    }
}
