<?php

namespace Bonfire\Guides\Exceptions;

final class GuideException extends \Exception
{
    public static function forCollectionNotFound()
    {
        return new static(lang('Bonfire.resourceNotFound', ['guide']));
    }

    public static function forNotAuthorized()
    {
        return new static(lang('Bonfire.notAuthorized'));
    }

    public static function forInvalidCollection()
    {
        return new static(lang('Guides.invalidCollection'));
    }

    public static function forInvalidPage()
    {
        return new static(lang('Guides.invalidPage'));
    }
}
