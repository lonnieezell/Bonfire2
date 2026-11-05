<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Docs\Markdown;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * An MkDocs admonition: a `!!! type "Title"` line followed by an indented body.
 */
class Admonition extends AbstractBlock
{
    public function __construct(public readonly string $type, public readonly string $title)
    {
        parent::__construct();
    }
}
