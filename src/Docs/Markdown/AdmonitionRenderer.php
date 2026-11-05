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

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

/**
 * Draws an admonition as a Bootstrap alert.
 */
class AdmonitionRenderer implements NodeRendererInterface
{
    private const COLORS = [
        'danger'  => 'danger',
        'warning' => 'warning',
        'tip'     => 'success',
    ];

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        Admonition::assertInstanceOf($node);

        /** @var Admonition $node */
        $color = self::COLORS[$node->type] ?? 'info';
        $title = new HtmlElement('strong', ['class' => 'd-block mb-1'], Xml::escape($node->title));

        return new HtmlElement('div', ['class' => 'alert alert-' . $color], [$title, $childRenderer->renderNodes($node->children())]);
    }
}
