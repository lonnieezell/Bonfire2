<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Bonfire\Media\Entities\Media;
use Bonfire\Media\Models\MediaModel;

if (! function_exists('media')) {
    /**
     * The media file a file or image field holds the ID of, or null when the field is empty or the file is gone
     * (deleted, in the Recycler). For example `media($entry->photo)?->url()`.
     */
    function media(?string $id): ?Media
    {
        if ($id === null || $id === '') {
            return null;
        }

        $media = model(MediaModel::class)->find($id);

        return $media instanceof Media ? $media : null;
    }
}
