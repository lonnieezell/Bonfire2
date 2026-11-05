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

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

class AdmonitionStartParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }

        $line = $cursor->match('/^!!!\s+[\w-]+.*$/');

        if ($line === null) {
            return BlockStart::none();
        }

        preg_match('/^!!!\s+([\w-]+)(?:\s+"([^"]*)")?/', $line, $matches);

        $title = $matches[2] ?? ucfirst($matches[1]);

        return BlockStart::of(new AdmonitionContinueParser(new Admonition($matches[1], $title)))->at($cursor);
    }
}
