<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Guides\Exceptions;

final class GuideException extends \Exception
{
    public static function forCollectionNotFound()
    {
        return new self(lang('Bonfire.resourceNotFound', ['guide']));
    }

    public static function forNotAuthorized()
    {
        return new self(lang('Bonfire.notAuthorized'));
    }

    public static function forInvalidCollection()
    {
        return new self(lang('Guides.invalidCollection'));
    }

    public static function forInvalidPage()
    {
        return new self(lang('Guides.invalidPage'));
    }
}
