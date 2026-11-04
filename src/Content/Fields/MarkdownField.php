<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Fields;

/**
 * Markdown, with a preview tab.
 */
final class MarkdownField extends TextareaField
{
    public function partial(): string
    {
        return 'markdown';
    }
}
