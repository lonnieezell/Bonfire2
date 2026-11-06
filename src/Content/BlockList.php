<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * The blocks of an entry's `blocks` field, in the order the file lists them.
 *
 * @implements IteratorAggregate<int, Block>
 */
final readonly class BlockList implements Countable, IteratorAggregate
{
    /**
     * @param list<Block> $blocks
     */
    public function __construct(private array $blocks)
    {
    }

    /**
     * @return Traversable<int, Block>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->blocks);
    }

    public function count(): int
    {
        return count($this->blocks);
    }

    /**
     * The blocks of these types, in the same order.
     */
    public function ofType(string ...$types): self
    {
        return new self(array_values(array_filter($this->blocks, static fn (Block $block): bool => in_array($block->type, $types, true))));
    }

    /**
     * The first block, or null when there are none.
     */
    public function first(): ?Block
    {
        return $this->blocks[0] ?? null;
    }

    /**
     * Every block through its partial, in order.
     *
     * @throws ContentException when a partial doesn't exist, except in production, where it is logged and gives nothing
     */
    public function render(): string
    {
        return $this->renderFor(ENVIRONMENT === 'production');
    }

    /**
     * What render() does, for either environment.
     *
     * @throws ContentException when a partial doesn't exist and this isn't production
     */
    public function renderFor(bool $production): string
    {
        return implode('', array_map(static fn (Block $block): string => $block->renderFor($production), $this->blocks));
    }
}
