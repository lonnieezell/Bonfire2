<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Media\Fields;

use Bonfire\Media\Entities\Media;

/**
 * A file field that takes images only: its picker lists only images, and a file that isn't one is refused.
 */
final class ImageField extends FileField
{
    public function accepts(Media $media): bool
    {
        return str_starts_with($media->mime, 'image/');
    }
}
