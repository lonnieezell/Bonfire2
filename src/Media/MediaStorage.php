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
use Bonfire\Media\Models\MediaModel;
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
     * @param string $folder the folder to list it under, '' for none
     *
     * @throws MediaException when the upload is refused or can't be moved
     */
    public function store(UploadedFile $file, string $container, string $folder): Media
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

        $directory = rtrim($config->containers[$container]['path'], '/');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path       = $this->freeName($container, $directory, pathinfo($name, PATHINFO_FILENAME), $extension);
        $mime       = $file->getMimeType();
        $size       = $file->getSize();
        $dimensions = @getimagesize($file->getPathname());

        if (! $this->moveUpload($file, $directory, $path)) {
            throw new MediaException(lang('Media.moveFailed', [$name]));
        }

        $model = model(MediaModel::class);
        $id    = $model->insert([
            'container' => $container,
            'folder'    => trim($folder, " /\t"),
            'path'      => $path,
            'filename'  => $name,
            'mime'      => $mime,
            'size'      => $size,
            'width'     => $dimensions === false ? null : $dimensions[0],
            'height'    => $dimensions === false ? null : $dimensions[1],
        ]);

        return $model->find($id);
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
     * `<slug>.<ext>`, or `<slug>-2.<ext>`, `<slug>-3.<ext>` and on when a file on disk or a row, deleted or not, has it.
     */
    private function freeName(string $container, string $directory, string $basename, string $extension): string
    {
        helper('url');

        $slug  = url_title($basename, '-', true);
        $slug  = $slug === '' ? 'file' : $slug;
        $model = model(MediaModel::class);

        for ($n = 1;; $n++) {
            $path = $slug . ($n === 1 ? '' : "-{$n}") . '.' . $extension;

            if (! is_file($directory . '/' . $path) && $model->withDeleted()->where(['container' => $container, 'path' => $path])->countAllResults() === 0) {
                return $path;
            }
        }
    }
}
