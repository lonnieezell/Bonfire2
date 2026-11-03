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

/**
 * An indexed entry: its columns, its front matter and its raw Markdown body.
 */
final readonly class Entry
{
    /**
     * @param array<array-key, mixed> $data the front matter
     */
    public function __construct(
        public string $id,
        public string $collection,
        public ?string $blueprint,
        public string $slug,
        public string $status,
        public ?string $publishedAt,
        public string $title,
        public string $body,
        public array $data,
    ) {
    }

    /**
     * @param array<string, mixed> $row a content_entries row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['collection'],
            $row['blueprint'],
            $row['slug'],
            $row['status'],
            $row['published_at'],
            $row['title'],
            $row['body'],
            json_decode($row['data'], true, flags: JSON_THROW_ON_ERROR),
        );
    }

    /**
     * A front matter value.
     */
    public function field(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }
}
