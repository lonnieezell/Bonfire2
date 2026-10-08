<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users\Libraries;

use Bonfire\Users\User;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Owns where user avatars live on disk: the directory, storing an
 * upload, and deleting a file.
 */
class AvatarStorage
{
    public function directory(): string
    {
        return FCPATH . (setting('Users.avatarDirectory') ?? 'uploads/avatars');
    }

    /**
     * Resizes the upload when configured, replaces the user's previous
     * avatar and moves the upload into place.
     *
     * @return string|null The new filename, or null when the move failed.
     */
    public function store(UploadedFile $file, User $user): ?string
    {
        $maxDimension     = setting('Users.avatarSize') ?? 140;
        [$width, $height] = getimagesize($file->getPathname());

        if ((setting('Users.avatarResize') ?? false) && ($width > (int) $maxDimension || $height > (int) $maxDimension)) {
            $image = service('image')->withFile($file->getPathname());
            $image->resize($maxDimension, $maxDimension, true);
            $image->save();
        }

        helper('text');
        $directory = $this->directory();
        $filename  = $user->id . '_' . random_string('alnum', 5) . '.jpg';

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->delete($user->avatar);

        return $file->move($directory, $filename, true) ? $filename : null;
    }

    /**
     * @return bool Whether a file was removed.
     */
    public function delete(?string $filename): bool
    {
        if (empty($filename)) {
            return false;
        }

        $path = $this->directory() . '/' . $filename;

        return file_exists($path) && @unlink($path);
    }
}
