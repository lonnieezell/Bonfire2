<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Media\Entities;

use CodeIgniter\Entity\Entity;

/**
 * A media file: an upload in one of the containers in Config\Media.
 *
 * @property string      $id        a UUID
 * @property string      $container
 * @property string      $folder    the folder it is listed under, '' for none; never where it is on disk
 * @property string      $path      relative to the container
 * @property string      $filename  the name it was uploaded as
 * @property string      $mime
 * @property int         $size      in bytes
 * @property int|null    $width
 * @property int|null    $height
 * @property string|null $alt
 */
class Media extends Entity
{
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts = [
        'size'   => 'int',
        'width'  => '?int',
        'height' => '?int',
    ];

    /**
     * The public URL of the file.
     */
    public function url(): string
    {
        return base_url(config('Media')->containers[$this->container]['url'] . '/' . $this->path);
    }

    /**
     * Where the file is on disk.
     */
    public function diskPath(): string
    {
        return rtrim(config('Media')->containers[$this->container]['path'], '/') . '/' . $this->path;
    }

    public function isImage(): bool
    {
        return $this->width !== null;
    }
}
