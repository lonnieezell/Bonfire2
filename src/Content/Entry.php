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

use League\CommonMark\GithubFlavoredMarkdownConverter;

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
        public ?string $uri,
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
            $row['uri'],
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

    /**
     * The entries an `entries` field links to, in the order the file lists them. A target that is missing or in the
     * trash is skipped, and so is a field that isn't a list of ids. Drafts and scheduled entries are returned,
     * so check `$status` before showing one.
     *
     * @return list<Entry>
     */
    public function entries(string $name): array
    {
        $ids = $this->data[$name] ?? null;

        return is_array($ids) ? content()->findMany(array_values(array_filter($ids, is_string(...)))) : [];
    }

    /**
     * The Markdown body as HTML. Raw HTML in the body passes through, apart from the tags
     * GitHub's rules disallow, so only render bodies you trust.
     */
    public function content(): string
    {
        return (new GithubFlavoredMarkdownConverter(['allow_unsafe_links' => false]))->convert($this->body)->getContent();
    }
}
